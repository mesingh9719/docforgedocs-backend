<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index()
    {
        return response()->json(Setting::all()->groupBy('group'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $settingData) {
            Setting::where('key', $settingData['key'])->update(['value' => $settingData['value']]);
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    // Initialize default settings if needed (useful for seeding)
    public function defaults()
    {
        $defaults = [
            ['key' => 'site_name', 'value' => 'DocForge', 'group' => 'general', 'type' => 'string'],
            ['key' => 'maintenance_mode', 'value' => '0', 'group' => 'general', 'type' => 'boolean'],
            ['key' => 'support_email', 'value' => 'support@docforge.com', 'group' => 'general', 'type' => 'string'],
        ];

        foreach ($defaults as $default) {
            Setting::firstOrCreate(['key' => $default['key']], $default);
        }
    }
}
