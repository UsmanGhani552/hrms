<?php

namespace App\Http\Controllers;

use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;
use App\Models\Leave;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index() {
        $user = auth()->user();
        $leaves = Leave::with('user');
        if ($user->hasRole('employee')) {
            $leaves = $leaves->where('user_id', $user->id)->get();
        } else {
            $leaves = $leaves->get();
        }
        return ResponseTrait::success('Leaves fetched successfully', $leaves);
    }

    public function store(StoreLeaveRequest $request) {
        try {
            $leave = Leave::createLeave($request->validated());
            return ResponseTrait::success('Leave created successfully', $leave);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Leave creation failed', $th->getMessage());
        }
    }
    public function update(UpdateLeaveRequest $request, Leave $leave) {
        try {
            $leave->updateLeave($request->validated());
            return ResponseTrait::success('Leave updated successfully', $leave);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Leave update failed', $th->getMessage());
        }
    }

    public function delete(Leave $leave) {
        try {
            $leave->delete();
            return ResponseTrait::success('Leave deleted successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Leave deletion failed', $th->getMessage());
        }
    }

    public function approve(ApproveLeaveRequest $request, Leave $leave) {
        try {
            $leave->approveLeave($request->validated());
            return ResponseTrait::success('Leave approved successfully', $leave);
        } catch (\Throwable $th) {
            return ResponseTrait::error('Leave approval failed', $th->getMessage());
        }
        
    }
}
