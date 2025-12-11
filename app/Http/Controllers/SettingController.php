<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreSettingRequest;
use App\Models\Setting;
use App\Traits\ImageUploadTrait;
use App\Traits\ResponseTrait;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use ImageUploadTrait;
    public function index()
    {
        $settings = Setting::all();
        return ResponseTrait::success('Settings fetched successfully', $settings);
    }

    public function update(StoreSettingRequest $request)
    {
        foreach ($request->settings as $key => $value) {
            if ($key == 'privacy_policy'){
                $value = $this->uploadImage($request, 'settings.privacy_policy','images/privacy_policies');
            }
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
