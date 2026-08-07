<?php

namespace App\Http\Controllers\Performance;

use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceSetting;
use Illuminate\Http\Request;

class PerformanceSettingController extends Controller
{
    public function index()
    {
        $settings = PerformanceSetting::all()->keyBy('key');
        return view('admin.performance.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [
            'attendance_weight' => 'required|numeric|min:0|max:100',
            'leave_weight' => 'required|numeric|min:0|max:100',
            'break_weight' => 'required|numeric|min:0|max:100',
            'late_weight' => 'required|numeric|min:0|max:100',
            'dress_code_weight' => 'required|numeric|min:0|max:100',
            'work_performance_weight' => 'required|numeric|min:0|max:100',
            'behavior_weight' => 'required|numeric|min:0|max:100',
        ];

        $request->validate($rules);

        $totalWeight = array_sum($request->only(array_keys($rules)));
        if (abs($totalWeight - 100.00) > 0.01) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['total_weight' => 'The sum of all weights must be exactly 100%. Current sum: ' . $totalWeight . '%']);
        }

        foreach ($request->only(array_keys($rules)) as $key => $value) {
            PerformanceSetting::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->route('admin.performance.settings.index')
            ->with('success', 'Performance weights updated successfully.');
    }
}
