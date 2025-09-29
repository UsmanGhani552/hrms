<?php

namespace App\Http\Controllers;

use App\Models\Attendence;
use App\Services\ZKTecoService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $zkService;

    public function __construct(ZKTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    public function fetchAttendance()
    {
        try {
            $attendences = Attendence::with('user:id,name,email')->get();
            return response()->json([
                'message' => 'Attendance logs fetched successfully',
                'logs' => $attendences,
            ]);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to fetch attendance logs: ' . $th->getMessage());
        }
    }

    public function fetchUsers() {
        try {
            $users  = $this->zkService->getUsers();
            return response()->json([
            'message' => 'Attendance users',
            'users' => $users,
        ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
