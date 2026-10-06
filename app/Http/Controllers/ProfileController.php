<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $employee = $user->employee;

        return view('profile.show', compact('user', 'employee'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $request->validate([
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->phone = $request->phone;
        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $user->avatar = $request->file('avatar')->store('profile-photos', 'public');
        }

        $user->save();

        if ($request->hasFile('avatar') && $oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        if ($employee) {
            $employee->phone = $request->phone;
            $employee->save();
        }

        AuditLog::log('profile_updated', 'User', $user->id, "User {$user->name} updated profile phone");

        return back()->with('success', 'Profile updated successfully.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        AuditLog::log('password_changed', 'User', $user->id, "User {$user->name} changed password");

        return back()->with('success', 'Password successfully changed.');
    }
}
