<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function userStats()
    {
        $users = User::with(['attendences' => function ($query) {
            $query->whereBetween('date', [
                now()->subMonths(1)->startOfMonth()->format('Y-m-d'),
                now()->subMonths(1)->endOfMonth()->format('Y-m-d')
            ])
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
