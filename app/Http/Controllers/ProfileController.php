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
            'avatar' => ['nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png,gif,webp'],
        ]);

        $user->phone = $request->phone;
        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $extension = strtolower($file->getClientOriginalExtension());
            $allowedImageTypes = [
                'jpg' => IMAGETYPE_JPEG,
                'jpeg' => IMAGETYPE_JPEG,
                'png' => IMAGETYPE_PNG,
                'gif' => IMAGETYPE_GIF,
                'webp' => IMAGETYPE_WEBP,
            ];
            $imageInfo = @getimagesize($file->getPathname());

            if (!$imageInfo || ($allowedImageTypes[$extension] ?? null) !== $imageInfo[2]) {
                return back()->withErrors(['avatar' => 'Upload a valid JPG, PNG, GIF, or WebP image.']);
            }

            $directory = storage_path('app/public/profile-photos');

            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                return back()->withErrors(['avatar' => 'The profile photo could not be saved. Please contact support.']);
            }

            $filename = bin2hex(random_bytes(16)) . '.' . $extension;
            $file->move($directory, $filename);
            $user->avatar = 'profile-photos/' . $filename;
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
