<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Leave Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div class="mx-auto w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-bold shadow-lg shadow-blue-500/25">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h2 class="mt-4 text-3xl font-extrabold text-slate-900 tracking-tight">LeaveFlow Portal</h2>
        <p class="mt-1 text-sm text-slate-500">Sign in to your enterprise employee leave account</p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/50 rounded-2xl border border-slate-100 sm:px-10">
            @if(session('info'))
                <div class="mb-4 p-3 rounded-xl bg-blue-50 text-blue-800 text-xs border border-blue-200">
                    {{ session('info') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-50 text-rose-800 text-xs border border-rose-200">
                    {{ $errors->first() }}
                </div>
            @endif

            <form class="space-y-5" action="{{ route('login') }}" method="POST">
                @csrf
                <div>
                    <label for="login" class="block text-xs font-semibold text-slate-700">Email or Employee Number</label>
                    <div class="mt-1.5">
                        <input id="login" name="login" type="text" autocomplete="username" required value="{{ old('login', 'joel@company.com') }}" placeholder="e.g. joel@company.com or EMP-003" class="appearance-none block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">You can log in using either your corporate email or employee ID.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-blue-600 hover:text-blue-500">Forgot password?</a>
                    </div>
                    <div class="mt-1.5">
                        <input id="password" name="password" type="password" autocomplete="current-password" required value="password" placeholder="••••••••" class="appearance-none block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                        <label for="remember" class="ml-2 block text-xs text-slate-700">Remember me on this device</label>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition">
                        Sign in to account
                    </button>
                </div>
            </form>

            <!-- Fast One-Click Demo Switcher -->
            <div class="mt-8 border-t border-slate-100 pt-6">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider text-center mb-3">Instant Demo Sign-in</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('fast.login', 'hr') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 transition flex items-center gap-2 text-left">
                        <div class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold">HR</div>
                        <div>
                            <p class="font-semibold text-slate-800 leading-tight">Sarah Jenkins</p>
                            <p class="text-[10px] text-slate-400">HR Manager</p>
                        </div>
                    </a>
                    <a href="{{ route('fast.login', 'lead') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50/50 transition flex items-center gap-2 text-left">
                        <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center font-bold">TL</div>
                        <div>
                            <p class="font-semibold text-slate-800 leading-tight">James Vance</p>
                            <p class="text-[10px] text-slate-400">Team Lead</p>
                        </div>
                    </a>
                    <a href="{{ route('fast.login', 'employee') }}" class="p-2.5 rounded-xl border border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/50 transition flex items-center gap-2 text-left col-span-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">DEV</div>
                        <div>
                            <p class="font-semibold text-slate-800 leading-tight">Joel Loter (Senior Developer)</p>
                            <p class="text-[10px] text-slate-400">EMP-003 • 13 Days Available • 3 Days Pending</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
