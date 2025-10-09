<?php

namespace Database\Seeders;

use App\Models\Attendence;
use App\Models\User;
use App\Services\ZKTecoService;
use Illuminate\Database\Seeder;
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
                        'date' => date('Y-m-d', strtotime($attendence['timestamp'])),
                        'timestamp' => $attendence['timestamp'],
                    ],
                    [
                        'type' => $type,
                        'updated_at' => now(),
                    ]
                );
                Log::info('Attendence fetched/created: ' . $attendence);
            }

            $users = User::pluck('id')->toArray();
            foreach ($users as $userId) {
                $attendences = Attendence::where('user_id', $userId)->orderBy('timestamp', 'asc')->get()->toArray();

                for ($i = 0; $i < count($attendences); $i++) {
                    if (isset($attendences[$i]) && isset($attendences[$i + 1] )) {
                        if (
                            $attendences[$i]['type'] === $attendences[$i + 1]['type'] &&
                            abs(strtotime($attendences[$i + 1]['timestamp']) - strtotime($attendences[$i]['timestamp'])) < 3600
                        ) {
                            if ($attendences[$i]['type'] === 'check in') {
                                Attendence::where('id', $attendences[$i + 1]['id'])->delete();
                                unset($attendences[$i + 1]);
                                $attendences = array_values($attendences);
                            } else {
                                Attendence::where('id', $attendences[$i]['id'])->delete();
                                unset($attendences[$i]);
                                $attendences = array_values($attendences);
                            }
                        }
                        if (
                            $attendences[$i]['type'] === 'check in' && $attendences[$i + 1]['type'] === 'check out'
                            && (strtotime($attendences[$i + 1]['timestamp']) - strtotime($attendences[$i]['timestamp'])) < 57600
                        ) { // 16 hours
                            Attendence::where('id', $attendences[$i + 1]['id'])->update(['date' => $attendences[$i]['date']]);
                            $attendences[$i + 1]['date'] = $attendences[$i]['date'];
                        }
                    }
                }
            }
        }
    }
}
