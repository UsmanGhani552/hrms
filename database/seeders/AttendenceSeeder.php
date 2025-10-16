<?php

namespace Database\Seeders;

use App\Models\Attendence;
use App\Models\User;
use App\Services\ZKTecoService;
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

            $users = User::pluck('id')->toArray();
            foreach ($users as $userId) {
                $attendences = Attendence::where('user_id', $userId)->orderBy('timestamp', 'asc')->get();
                for ($i = 0; $i < count($attendences); $i++) {
                    if (isset($attendences[$i + 1])) {
                        if ($attendences[$i]->type === $attendences[$i + 1]->type && abs(strtotime($attendences[$i + 1]->timestamp) - strtotime($attendences[$i]->timestamp)) < 3600) {
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

        $startDate = Carbon::parse($attendences->first()->timestamp)->startOfDay();
        $endDate = now()->endOfDay();

        $existingDates = $attendences->groupBy(function ($record) {
            return Carbon::parse($record->timestamp)->format('Y-m-d');
        });

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $type = null;
            // Skip weekends (optional)
            if ($currentDate->isWeekend()) {
                $type = 'weekend';
            }

            // Check if date exists in attendance
            if (!isset($existingDates[$dateStr])) {
                // This is an off day/absent day
                Attendence::updateOrCreate([
                    'user_id' => $userId,
                    'timestamp' => $currentDate->copy()->setTime(9, 0),
                ], [
                    'date' => $dateStr,
                    'type' => $type || 'absent',
                ]);
            }

            $currentDate->addDay();
        }
    }
}
