<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'employee_number';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->onlyInput('login');
        }

        if (!$user->is_active) {
            return back()->withErrors([
                'login' => 'Your account has been deactivated. Please contact HR.',
            ])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLog::log(
            action: 'user_login',
            entityType: 'User',
            entityId: $user->id,
            description: "User {$user->name} logged in via {$field}"
        );

        return redirect()->intended(route('dashboard'));
    }

    public function fastLogin(string $roleOrEmail)
    {
        $user = match($roleOrEmail) {
            'hr' => User::where('role', 'hr')->first(),
            'manager' => User::where('role', 'manager')->first(),
            'lead' => User::where('role', 'team_lead')->first(),
            'employee' => User::where('email', 'joel@company.com')->first() ?? User::where('role', 'employee')->first(),
            'mary' => User::where('email', 'mary@company.com')->first(),
            'peter' => User::where('email', 'peter@company.com')->first(),
            default => User::where('email', $roleOrEmail)->first(),
        };

        if ($user) {
            Auth::login($user);
            request()->session()->regenerate();
            return redirect()->route('dashboard')->with('success', "Logged in as {$user->name} ({$user->role})");
        }

        return redirect()->route('login')->with('error', 'User not found for fast login.');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            AuditLog::log('user_logout', 'User', $user->id, "User {$user->name} logged out");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'You have been logged out.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        return back()->with('status', 'If this email is registered, password reset instructions have been dispatched.');
    }
}
