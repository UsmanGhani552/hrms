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
                $user = User::with('shift')->find($attendence['user_id']);
                if (!$user || !$user->shift) continue;

                $punchDateTime = Carbon::parse($attendence['timestamp']);
                $punchTime = $punchDateTime->format('H:i:s');

                $checkInFrom = $user->shift->check_in_from;
                $checkInTo = $user->shift->check_in_to;

                $type = 'check out'; // default fallback

                // 🕗 Morning Shift
                if ($user->shift_id == 1) {
                    if ($punchTime >= $checkInFrom && $punchTime <= $checkInTo) {
                        $type = 'check in';
                    } else {
                        $type = 'check out';
                    }

                    // 🌙 Night Shift (crosses midnight)
                } elseif ($user->shift_id == 2) {
                    if ($punchTime >= $checkInFrom || $punchTime <= $checkInTo) {
                        $type = 'check in';
                    } else {
                        $type = 'check out';
                    }
                }
                // ✅ Handle after-midnight punches (belong to previous day)
                if ($punchTime < '05:00:00') {
                    $punchDateTime->subDay();
                }
                // 💾 Save or update attendance
                Attendence::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'timestamp' => $punchDateTime->toDateTimeString(),
                    ],
                    [
                        'type' => $type,
                        'updated_at' => now(),
                    ]
                );

                Log::info("Attendance synced: User={$user->id}, Shift={$user->shift->name}, Type={$type}, Time={$punchDateTime}");
            }
        }
    }
}
