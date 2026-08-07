<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        return view('admin.profile.edit', compact('user', 'employee'));
    }

    public function update(Request $request)
    {
        try {
            $user = Auth::user();
            // Employee model ko direct use karein kyunki upar imported hai
            $employee = Employee::where('user_id', $user->id)->first();

            $request->validate([
                'name' => 'required|string|max:255',
                'email' => "required|email|unique:users,email,{$user->id}",
                'contact_no' => 'nullable|string|max:20',
                'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'password' => 'nullable|min:6|confirmed',
            ]);

            $user->name = $request->name;
            $user->email = $request->email;
            $user->contact_no = $request->contact_no ?? $user->contact_no;

            // ✅ Handle profile pic using Storage Disk
            if ($request->hasFile('profile_pic')) {
                $uploadedFile = $request->file('profile_pic');

                // Purani file delete karein (Disk se)
                if ($user->profile_pic && Storage::disk('public')->exists($user->profile_pic)) {
                    Storage::disk('public')->delete($user->profile_pic);
                }

                // Nayi file save karein (storage/app/public/profile_pics mein jayegi)
                $path = $uploadedFile->store('profile_pics', 'public');
                $user->profile_pic = $path;
            }

            // Update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            // Update employee record if exists
            if ($employee) {
                $employee->name = $request->name;
                $employee->email = $request->email;
                $employee->contact_no = $request->contact_no;

                // User aur Employee dono ka same path rahega
                $employee->profile_pic = $user->profile_pic;
                $employee->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                // ✅ Update: asset('storage/...') ki jagah Storage::url() use karein
                // Ye automatically config/filesystems.php se 'public_storage' wala path uthayega
                'profile_pic_url' => $user->profile_pic ? Storage::disk('public')->url($user->profile_pic) : null
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while updating profile.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}