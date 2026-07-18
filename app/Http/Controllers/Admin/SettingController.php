<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings.edit', [
            'settings' => SystemSetting::query()->pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'school_logo' => ['nullable', 'image', 'max:2048'],
            'public_results_enabled' => ['boolean'],
        ]);

        if ($request->hasFile('school_logo')) {
            $data['school_logo_path'] = $request->file('school_logo')->store('settings', 'public');
        }

        unset($data['school_logo']);

        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        if (! $request->boolean('public_results_enabled')) {
            SystemSetting::updateOrCreate(['key' => 'public_results_enabled'], ['value' => '0']);
        }

        return back()->with('status', 'Settings updated.');
    }
}
