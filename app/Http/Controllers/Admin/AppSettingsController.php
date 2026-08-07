<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppSetting;

class AppSettingsController extends Controller
{
    public function index()
    {
        $settings = AppSetting::orderBy('key')->get();
        return view('admin.settings.index', compact('settings'));
    }

    public function edit($id)
    {
        $setting = AppSetting::findOrFail($id);
        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        foreach ($request->except(['_token']) as $key => $value) {
            AppSetting::where('key', $key)->update(['value' => $value]);
        }

        // Always clear cached settings
        \Cache::forget('app_settings');

        return redirect()->route('app_settings.index')->with('success', 'Settings updated successfully!');
    }

}
