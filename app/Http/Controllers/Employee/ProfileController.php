<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $currentShiftAssignment = $employee ? $employee->employeeShifts()->with('shift')->latest('assigned_at')->first() : null;
        $settings = $employee ? get_employee_settings($employee->id) : [];

        return view('employee.profile.edit', compact('user', 'employee', 'currentShiftAssignment', 'settings'));
    }

    public function update(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Profile updates are not allowed for employees.',
        ], 403);
    }

    public function updateCoverPhoto(Request $request)
    {
        $request->validate([
            'cover_pic' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($employee->cover_pic && Storage::disk('public')->exists($employee->cover_pic)) {
            Storage::disk('public')->delete($employee->cover_pic);
        }

        $path = $request->file('cover_pic')->store('cover_pics', 'public');
        $employee->cover_pic = $path;
        $employee->cover_pic_position = json_encode(['x' => 50, 'y' => 35, 'zoom' => 100]);
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cover photo updated successfully.',
            'cover_pic_url' => Storage::disk('public')->url($path),
            'cover_pic_position' => parse_cover_pic_position($employee->cover_pic_position),
        ]);
    }

    public function updateCoverPosition(Request $request)
    {
        $validated = $request->validate([
            'x' => 'required|numeric|min:0|max:100',
            'y' => 'required|numeric|min:0|max:100',
            'zoom' => 'required|numeric|min:100|max:200',
        ]);

        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if (!$employee->cover_pic) {
            return response()->json([
                'success' => false,
                'message' => 'No cover photo to adjust.',
            ], 422);
        }

        $position = [
            'x' => round((float) $validated['x'], 1),
            'y' => round((float) $validated['y'], 1),
            'zoom' => round((float) $validated['zoom'], 1),
        ];

        $employee->cover_pic_position = json_encode($position);
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cover position saved.',
            'cover_pic_position' => $position,
        ]);
    }

    public function removeCoverPhoto()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($employee->cover_pic && Storage::disk('public')->exists($employee->cover_pic)) {
            Storage::disk('public')->delete($employee->cover_pic);
        }

        $employee->cover_pic = null;
        $employee->cover_pic_position = null;
        $employee->save();

        return response()->json([
            'success' => true,
            'message' => 'Cover photo removed.',
        ]);
    }
}
