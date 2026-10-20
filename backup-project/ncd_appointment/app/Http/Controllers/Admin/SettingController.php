<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Setting;

class SettingController extends Controller
{
    public function index()
    {
        // Get all settings grouped by their 'group' field
        $settings = Setting::all()->groupBy('group');
        
        return Inertia::render('Admin/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:settings,id',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $settingData) {
            $setting = Setting::find($settingData['id']);
            if ($setting) {
                // If type is boolean, ensure it saves as 1 or 0 string for simplicity, or just save the boolean
                if ($setting->type === 'boolean') {
                    $setting->value = $settingData['value'] ? '1' : '0';
                } else {
                    $setting->value = (string) $settingData['value'];
                }
                $setting->save();
            }
        }

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว');
    }
}
