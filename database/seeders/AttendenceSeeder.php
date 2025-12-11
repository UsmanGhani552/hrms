<?php

namespace Database\Seeders;

use App\Models\Attendence;
use App\Models\Holiday;
use App\Models\User;
use App\Services\ZKTecoService;
use DateTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttendenceSeeder extends Seeder
{
    protected $zkService;
    public function __construct(ZKTecoService $zkService)
    {
        $this->zkService = $zkService;
    }
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendences = $this->zkService->getAttendance();

        if ($attendences) {
            $currentAttendence = Attendence::where('timestamp', '!=', null)->orderBy('timestamp', 'desc')->first();
            $attendences = array_filter($attendences, function ($attendence) use ($currentAttendence) {
                return !$currentAttendence || strtotime($attendence['timestamp']) > strtotime($currentAttendence->timestamp);
            });
            // $attendences = array_filter($attendences, function ($attendence) {
            //     return strtotime($attendence['timestamp']) >= strtotime('2025-12-04 00:00:00') && strtotime($attendence['timestamp']) <= strtotime('2025-12-06 23:59:59') && $attendence['user_id'] == 3042;
            // });
            // dd($attendences);
            $users = User::pluck('id')->toArray();
            $lastPulledDates = [];
            foreach ($users as $userId) {
                $lastPulledDate = Attendence::where('user_id', $userId)
                    ->orderBy('timestamp', 'desc')
                    ->first();

                $lastPulledDates[$userId] = $lastPulledDate ? $lastPulledDate->timestamp : now()->subYear()->format('Y-m-d H:i:s');
            }

            foreach ($attendences as $attendence) {
                $user = User::where('id', $attendence['user_id'])->first();
                if (!$user) {
                    continue; // Skip if user not found
                }
                $punchTime = date('H:i:s', strtotime($attendence['timestamp']));
                $checkInFrom = $user->shift->check_in_from; // "17:00:00"  // "08:00:00"
                $checkInTo = $user->shift->check_in_to;     // "03:00:00"  // "17:00:00"

                $type = ($checkInFrom < $checkInTo) ? (($punchTime >= $checkInFrom && $punchTime <= $checkInTo) ? 'check in' : 'check out') : (($punchTime >= $checkInFrom || $punchTime <= $checkInTo) ? 'check in' : 'check out');

                $attendence = Attendence::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'timestamp' => $attendence['timestamp'],
                    ],
                    [
                        'date' => date('Y-m-d', strtotime($attendence['timestamp'])),
                        'type' => $type,
                        'updated_at' => now(),
                    ]
                );
                Log::info('Attendence fetched/created: ' . $attendence);
            }


            foreach ($users as $userId) {
                $attendences = Attendence::where('user_id', $userId)->orderBy('timestamp', 'asc')->get();
                for ($i = 0; $i < count($attendences); $i++) {
                    if (isset($attendences[$i + 1])) {
                        $time1 = strtotime($attendences[$i]->timestamp);
                        $date1 = new DateTime($attendences[$i]->timestamp);
                        $time2 = strtotime($attendences[$i + 1]->timestamp);
                        $date2 = new DateTime($attendences[$i + 1]->timestamp);
                        if (
                            $date1 === $date2
                            && $attendences[$i]->type === $attendences[$i + 1]->type
                            && abs($time2 - $time1) < 3600
                            && $time2 > $time1 // ensure correct order
                        ) {
                            if ($attendences[$i]->type === 'check in') {
                                $attendences[$i + 1]->delete();
                                unset($attendences[$i + 1]);
                                $i++;
                            } else {
                                $attendences[$i]->delete();
                                unset($attendences[$i]);
                            }
                        }
                    }
                }
                $attendencesArray = $attendences->values();

                for ($i = 0; $i < count($attendencesArray); $i++) {
                    if (isset($attendencesArray[$i + 1])) {

                        if (
                            $attendencesArray[$i]['type'] === 'check in' && $attendencesArray[$i + 1]['type'] === 'check out'
                            && (strtotime($attendencesArray[$i + 1]['timestamp']) - strtotime($attendencesArray[$i]['timestamp'])) < 57600
                        ) { // 16 hours
                            $entry = $attendences->where('id', $attendencesArray[$i + 1]['id'])->first();
                            $entry['date'] = $attendencesArray[$i]['date'];
                            $entry->save();
                        }
                    }
                }
                foreach ($users as $userId) {
                    $this->processOffDays($userId);
                    $this->processAbsentDays($userId, $lastPulledDates[$userId]);
                }
            }
        }
    }

    public function processOffDays($userId)
    {
        $attendences = Attendence::where('user_id', $userId)
            ->orderBy('timestamp', 'asc')
            ->get();

        if ($attendences->isEmpty()) {
            return;
        }

        $lastPulledDate = $attendences->first()->timestamp;

        $startDate = Carbon::parse($attendences->first()->timestamp)->startOfDay();
        $endDate = now()->endOfDay();

        $existingDates = $attendences->groupBy(function ($record) {
            return Carbon::parse($record->date)->format('Y-m-d');
        });
        $publicHolidays = Holiday::pluck('date')->toArray();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $type = null;
            if (in_array($dateStr, $publicHolidays)) {
                $type = 'holiday';
            }
            // Skip weekends (optional)
            if ($currentDate->isWeekend()) {
                $type = 'weekend';
            }

            // Check if date exists in attendance
            if (!isset($existingDates[$dateStr]) && $type != null) {
                // This is an off day/absent day

                Attendence::updateOrCreate([
                    'user_id' => $userId,
                    'timestamp' => $currentDate->copy()->setTime(0, 0),
                ], [
                    'date' => $dateStr,
                    'type' => $type,
                ]);
            }

            $currentDate->addDay();
        }
        return $lastPulledDate;
    }
    public function processAbsentDays($userId, $lastPulledDate)
    {
        $attendences = Attendence::where('user_id', $userId)
            ->orderBy('timestamp', 'asc')
            ->get();

        if ($attendences->isEmpty()) {
            return;
        }
        // $lastPulledDate = $attendences->first()->timestamp;
        $day = Carbon::parse($lastPulledDate);
        $startDate = $day->subDays(2)->startOfDay();
        $endDate = now()->subDay()->endOfDay();

        $existingDates = $attendences->groupBy(function ($record) {
            return Carbon::parse($record->date)->format('Y-m-d');
        });
        $publicHolidays = Holiday::pluck('date')->toArray();
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $type = null;
            if (in_array($dateStr, $publicHolidays)) {
                $type = 'holiday';
            }
            // Skip weekends (optional)
            if ($currentDate->isWeekend()) {
                $type = 'weekend';
            }

            // Check if date exists in attendance
            if (!isset($existingDates[$dateStr])) {
                // This is an off day/absent day
                Attendence::updateOrCreate([
                    'user_id' => $userId,
                    'timestamp' => $currentDate->copy()->setTime(0, 0),
                ], [
                    'date' => $dateStr,
                    'type' => $type ?? 'absent',
                ]);
            }

            $currentDate->addDay();
        }
    }
}
