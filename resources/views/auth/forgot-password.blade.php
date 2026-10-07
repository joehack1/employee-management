<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LeaveFlow</title>
    @include('partials.theme')
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-config')
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <button type="button" onclick="toggleTheme()"
            class="theme-toggle fixed top-4 right-4 z-10 p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none"
            title="Toggle dark mode" aria-label="Toggle dark mode">
        <svg class="icon-moon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
        <svg class="icon-sun w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
    </button>
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <h2 class="text-2xl font-bold text-slate-900">Reset your password</h2>
        <p class="mt-1 text-xs text-slate-500">Enter your registered email address and we'll send you instructions.</p>
    </div>

    <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl border border-slate-100 sm:px-10">
            @if(session('status'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-50 text-emerald-800 text-xs border border-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-800 text-xs border border-rose-200" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="space-y-4" action="{{ route('password.email') }}" method="POST">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700">Email address</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}" placeholder="name@company.com" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl sm:text-sm @error('email') border-rose-400 @enderror">
                    @error('email')
                        <p class="mt-1 text-[11px] text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition">
                    Send Reset Link
                </button>
                <div class="text-center pt-2">
                    <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-800">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
