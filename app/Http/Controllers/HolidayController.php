<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holiday\StoreHolidayRequest;
use App\Http\Requests\Holiday\UpdateHolidayRequest;
use App\Models\Holiday;
use App\Traits\ResponseTrait;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index() {
        $holidays = Holiday::with('shiftHolidays')->get();
        return ResponseTrait::success('Holidays fetched successfully', $holidays);
    }

    public function store(StoreHolidayRequest $request) {
        try {
            Holiday::createHoliday($request->validated());
            return ResponseTrait::success('Holiday created successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to create holiday');
        }
    }
    public function update(UpdateHolidayRequest $request, Holiday $holiday) {
        try {
            $holiday->updateHoliday($request->validated());
            return ResponseTrait::success('Holiday updated successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to update holiday');
        }
    }

    public function delete(Holiday $holiday) {
        try {
            $holiday->delete();
            return ResponseTrait::success('Holiday deleted successfully');
        } catch (\Throwable $th) {
            return ResponseTrait::error('Failed to delete holiday');
        }
    }
}
