<?php
use App\Models\Setting;

if (!function_exists('getSetting')) {
    function getSetting($key, $default = null) {
        return Setting::where('key', $key)->value('value') ?? $default;
    }
}