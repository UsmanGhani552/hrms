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

                // Build today’s shift window
                $checkInFrom = Carbon::parse($punchDateTime->format('Y-m-d') . ' ' . $user->shift->check_in_from);
                $checkInTo = Carbon::parse($punchDateTime->format('Y-m-d') . ' ' . $user->shift->check_in_to);

                // handle overnight shifts (e.g. 17:00 -> 03:00)
                if ($checkInTo->lessThan($checkInFrom)) {
                    $checkInTo->addDay();
                }

                // 👇 NEW LOGIC:
                // if someone punches after midnight (like 12:20 AM) but before their shift start (1PM),
                // that punch belongs to the previous day
                if ($punchDateTime->format('H:i:s') < $user->shift->check_in_from) {
                    $checkInFrom->subDay();
                    $checkInTo->subDay();
                }

                // classify
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
                    'belongs_to' => $checkInFrom->toDateString(),
                    'shift' => "{$user->shift->check_in_from} - {$user->shift->check_in_to}",
                ]);
            }
        }
    }
}
