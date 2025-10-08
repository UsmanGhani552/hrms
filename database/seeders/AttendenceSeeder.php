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
                $punchDateTime = Carbon::parse($attendence['timestamp']);
                $punchTime = date('H:i:s', strtotime($attendence['timestamp']));
                $checkInFrom = $user->shift->check_in_from; // "17:00:00"  // "08:00:00"
                $checkInTo = $user->shift->check_in_to;     // "03:00:00"  // "17:00:00"

                $type = ($checkInFrom < $checkInTo) ? (($punchTime >= $checkInFrom && $punchTime <= $checkInTo) ? 'check in' : 'check out') : (($punchTime >= $checkInFrom || $punchTime <= $checkInTo) ? 'check in' : 'check out');
                // ✅ Handle after-midnight punches (belong to previous day)
                if ($punchTime < '09:30:00') {
                    $punchDateTime->subDay();
                }
                $attendence = Attendence::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'timestamp' => $punchDateTime,
                    ],
                    [
                        'type' => $type,
                        'updated_at' => now(),
                    ]
                );
                Log::info('Attendence fetched/created: ' . $attendence);
            }
        }
    }
}
