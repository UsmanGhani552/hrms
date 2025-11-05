<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendence\UpdateAttendenceRequest;
use App\Models\Attendence;
use App\Services\ZKTecoService;
use App\Traits\ResponseTrait;
use Database\Seeders\AttendenceSeeder;
use Database\Seeders\FetchUserSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            $user = Auth::user();
            $attendences = Attendence::with('user:id,name,email,shift_id')->orderBy('date', 'desc');
            if ($user->hasRole('employee')) {
                $attendences = $attendences->where('user_id', $user->id)->get();
            } else {
                $attendences = $attendences->get();
                // $attendences = Attendence::with('user:id,name,email')->where('timestamp', '>=', now()->subDays(15))->where('user_id',2013)->orderBy('id', 'desc')->get();
            }

            return ResponseTrait::success('Attendance logs fetched successfully', [
                $attendences,
                DB::table('shifts')->get()
            ]);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to fetch attendance logs: ' . $th->getMessage());
        }
    }

    public function fetchAttendenceByUserId()
    {
        try {
            $user_id = Auth::user()->id;
            $attendences = Attendence::with('user:id,name,email,shift_id')->where('user_id', $user_id)->orderBy('date', 'asc')->get();
            return ResponseTrait::success('Attendance logs fetched successfully', [
                $attendences,
                DB::table('shifts')->get()
            ]);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to fetch attendance logs: ' . $th->getMessage());
        }
    }

    public function update(UpdateAttendenceRequest $request)
    {
        try {
            // dd($request->validated());
            Attendence::updateAttendence($request->validated());
            return ResponseTrait::success('Attendance updated successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to fetch attendance logs: ' . $th->getMessage());
        }
    }

    public function fetchCurrentAttendence(FetchUserSeeder $fetchUserSeeder, AttendenceSeeder $attendenceSeeder)
    {
        try {
            $fetchUserSeeder->run();
            $attendenceSeeder->run();
            return ResponseTrait::success('Attendance fetched successfully');
        } catch (\Exception $e) {
            return ResponseTrait::error('❌ Error fetching attendance: ' . $e->getMessage());
        }
    }
}
