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
        $attendances = $this->zkService->getAttendance();

        if ($attendances) {
            foreach ($attendances as $attendance) {
                $user = User::with('shift')->find($attendance['user_id']);
                if (!$user || !$user->shift) continue;

                $punchDateTime = Carbon::parse($attendance['timestamp']);
                $checkInFrom = Carbon::parse($punchDateTime->format('Y-m-d') . ' ' . $user->shift->check_in_from);
                $checkInTo = Carbon::parse($punchDateTime->format('Y-m-d') . ' ' . $user->shift->check_in_to);

                // Handle overnight or next-day check-out
                if ($checkInTo->lessThanOrEqualTo($checkInFrom)) {
                    $checkInTo->addDay();
                }

                // If punch is after midnight but before check_in_to (like 12:12 AM for 1PM–10PM shift),
                // and belongs to previous shift day, shift the 'checkInFrom' and 'checkInTo' back one day.
                if ($punchDateTime->lessThan($checkInFrom) && $punchDateTime->between($checkInFrom->copy()->subDay(), $checkInTo->copy()->subDay())) {
                    $checkInFrom->subDay();
                    $checkInTo->subDay();
                }

                $type = $punchDateTime->between($checkInFrom, $checkInTo)
                    ? 'check in'
                    : 'check out';

                Attendence::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'timestamp' => $attendance['timestamp'],
                    ],
                    [
                        'type' => $type,
                        'updated_at' => now(),
                    ]
                );

                Log::info("Attendance saved", [
                    'user_id' => $user->id,
                    'timestamp' => $attendance['timestamp'],
                    'type' => $type,
                    'from' => $checkInFrom,
                    'to' => $checkInTo,
                ]);
            }
        }
    }
}
