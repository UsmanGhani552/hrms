<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userStats(Request $request)
    {
        $months = $request->input('months', 0);

        // Calculate the start and end dates based on the number of months
        $startDate = now()->subMonths($months)->startOfMonth()->format('Y-m-d');
        $endDate = now()->subMonths($months)->endOfMonth()->format('Y-m-d');
        $users = User::with(['attendences' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('date', [$startDate, $endDate])
                ->whereIn('type', ['check in', 'check out', 'absent'])
                ->orderBy('date', 'asc');
        }])->get()->toArray();

        $allUsersAttendence = [];
        foreach ($users as $key => $user) {
            $presentDays = [];
            $absentDays = [];
            foreach ($user['attendences'] as $key => $attendence) {
                if ($attendence['type'] === 'check in' || $attendence['type'] === 'check out') {
                    $presentDays[$attendence['date']]['type'] = $attendence['type'];
                } elseif ($attendence['type'] === 'absent') {
                    $absentDays[$attendence['date']]['type'] = $attendence['type'];
                }
            }
            $allUsersAttendence[] = [
                'user_id' => $user['id'],
                'name' => $user['name'],
                'total_present' => count($presentDays),
                'total_absent' => count($absentDays),
                'total_days' => count($presentDays) + count($absentDays),
            ];
        }
        return ResponseTrait::success('User Stats', $allUsersAttendence);
        // dd($allUsersAttendence);
    }
}
