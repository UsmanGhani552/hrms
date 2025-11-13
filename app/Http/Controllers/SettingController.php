<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreSettingRequest;
use App\Models\Setting;
use App\Traits\ResponseTrait;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all();
        return ResponseTrait::success('Settings fetched successfully', $settings);
    }

    public function update(StoreSettingRequest $request)
    {
        foreach ($request->input('settings') as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }
        return ResponseTrait::success('Settings updated successfully');
    }
    public function getSettings() {
        $leaves = getSetting('leaves');
        $ratings = getSetting('ratings');
        return ResponseTrait::success('Settings fetched successfully', compact('leaves', 'ratings'));
    }
}
