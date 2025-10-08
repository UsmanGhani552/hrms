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
        $shiftStartTime = $user->shift->check_in_from; // e.g. 13:00:00
        $shiftEndTime = $user->shift->check_in_to;     // e.g. 22:00:00

        // Build shift window based on *yesterday's* date first
        $yesterday = $punchDateTime->copy()->subDay();
        $shiftStartYesterday = Carbon::parse($yesterday->format('Y-m-d') . ' ' . $shiftStartTime);
        $shiftEndYesterday = Carbon::parse($yesterday->format('Y-m-d') . ' ' . $shiftEndTime);

        // Handle overnight shift (like 17:00 to 03:00)
        if ($shiftEndYesterday->lessThan($shiftStartYesterday)) {
            $shiftEndYesterday->addDay();
        }

        // Build today's shift window too
        $shiftStartToday = Carbon::parse($punchDateTime->format('Y-m-d') . ' ' . $shiftStartTime);
        $shiftEndToday = Carbon::parse($punchDateTime->format('Y-m-d') . ' ' . $shiftEndTime);
        if ($shiftEndToday->lessThan($shiftStartToday)) {
            $shiftEndToday->addDay();
        }

        // Now check where the punch fits better
        if ($punchDateTime->between($shiftStartYesterday, $shiftEndYesterday)) {
            $belongsTo = $shiftStartYesterday->toDateString();
        } else {
            $belongsTo = $shiftStartToday->toDateString();
        }

        // Determine check in/out relative to *belongsTo* window
        $checkInFrom = Carbon::parse($belongsTo . ' ' . $shiftStartTime);
        $checkInTo = Carbon::parse($belongsTo . ' ' . $shiftEndTime);
        if ($checkInTo->lessThan($checkInFrom)) {
            $checkInTo->addDay();
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

        \Log::info("Attendance saved", [
            'user' => $user->name,
            'punch' => $punchDateTime,
            'belongs_to' => $belongsTo,
            'type' => $type,
        ]);
    }
}
    }
}
