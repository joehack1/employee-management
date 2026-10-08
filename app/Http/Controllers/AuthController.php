<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    public function startImpersonation(Request $request)
    {
        $administrator = Auth::user();
        abort_unless($administrator?->isSuperAdmin(), 403);
        abort_if($request->session()->has('impersonator_id'), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $target = User::whereKey($validated['user_id'])
            ->where('is_active', true)
            ->where('role', '!=', 'administrator')
            ->firstOrFail();

        $request->session()->put('impersonator_id', $administrator->id);
        $request->session()->regenerate();
        AuditLog::log(
            action: 'account_impersonation_started',
            entityType: 'User',
            entityId: $target->id,
            description: "Administrator {$administrator->email} switched into {$target->email}"
        );
        Auth::login($target);

        return redirect()->route('dashboard')->with('success', "You are now viewing the account for {$target->name}.");
    }

    public function stopImpersonation(Request $request)
    {
        $administratorId = $request->session()->pull('impersonator_id');
        abort_unless($administratorId, 403);

        $administrator = User::whereKey($administratorId)
            ->where('role', 'administrator')
            ->where('is_active', true)
            ->first();

        if (!$administrator) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'The administrator account is no longer active. Please sign in again.');
        }

        $viewedUserId = Auth::id();
        Auth::login($administrator);
        $request->session()->regenerate();

        AuditLog::log(
            action: 'account_impersonation_ended',
            entityType: 'User',
            entityId: $viewedUserId,
            description: "Administrator {$administrator->email} stopped viewing another account"
        );

        return redirect()->route('dashboard')->with('success', 'You have returned to your administrator account.');
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
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Only dispatch reset links for accounts that already exist in the system.
        $user = User::where('email', $request->string('email'))->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'We could not find an account with that email address.'])
                ->onlyInput('email');
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'We have emailed your password reset link.')
            : back()->withErrors(['email' => 'We could not send the reset link right now. Please try again shortly.']);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                Auth::login($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $request->session()->regenerate();

            AuditLog::log(
                action: 'password_reset',
                entityType: 'User',
                entityId: Auth::id(),
                description: 'User reset their password via the reset link'
            );

            return redirect()->route('dashboard')->with('success', 'Your password has been reset. You are now signed in.');
        }

        return back()->withErrors([
            'email' => match ($status) {
                Password::INVALID_TOKEN => 'This password reset link is invalid or has expired.',
                Password::INVALID_USER => 'We could not find a user with that email address.',
                default => 'The password reset attempt failed. Please request a new link and try again.',
            },
        ]);
    }
}
