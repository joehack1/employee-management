<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Employee Leave Management System</title>
    @include('partials.theme')
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-config')

    <style>
        /* ---------- Motion tokens ---------- */
        :root { --ease-out: cubic-bezier(.22, 1, .36, 1); --ease-spring: cubic-bezier(.34, 1.56, .64, 1); }

        /* One orchestrated page-load sequence: items rise in order via --d */
        @keyframes rise   { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
        @keyframes slide-in { from { opacity: 0; transform: translateX(-24px); } to { opacity: 1; transform: none; } }
        @keyframes pop    { from { opacity: 0; transform: scale(.55); } to { opacity: 1; transform: scale(1); } }
        @keyframes drift-a { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(40px, 30px) scale(1.12); } }
        @keyframes drift-b { 0%, 100% { transform: translate(0, 0) scale(1); } 50% { transform: translate(-36px, -26px) scale(1.08); } }
        @keyframes pending-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .55); } 50% { box-shadow: 0 0 0 7px rgba(245, 158, 11, 0); } }
        @keyframes shake  { 0%, 100% { transform: translateX(0); } 20% { transform: translateX(-6px); } 40% { transform: translateX(5px); } 60% { transform: translateX(-3px); } 80% { transform: translateX(2px); } }
        @keyframes spin   { to { transform: rotate(360deg); } }
        @keyframes sheen  { from { transform: translateX(-120%) skewX(-20deg); } to { transform: translateX(320%) skewX(-20deg); } }

        .rise { opacity: 0; animation: rise .75s var(--ease-out) forwards; animation-delay: calc(var(--d, 0) * 90ms + 80ms); }
        .slide-in { opacity: 0; animation: slide-in .8s var(--ease-out) forwards; animation-delay: calc(var(--d, 0) * 110ms + 100ms); }

        .blob-a { animation: drift-a 14s ease-in-out infinite; }
        .blob-b { animation: drift-b 17s ease-in-out infinite; }

        .cal-cell { opacity: 0; animation: pop .5s var(--ease-spring) forwards; animation-delay: calc(var(--i) * 26ms + 700ms); }
        .cal-pending { animation: pop .5s var(--ease-spring) forwards, pending-pulse 2.4s ease-in-out 2.2s infinite; animation-delay: calc(var(--i) * 26ms + 700ms), 2.2s; }

        .alert-error { animation: rise .45s var(--ease-out) both, shake .5s .3s both; }
        .alert-info  { animation: rise .45s var(--ease-out) both; }

        /* Inputs */
        .field { transition: border-color .25s ease, box-shadow .25s ease, background-color .25s ease, transform .25s var(--ease-out); }
        .field:hover { border-color: rgb(96 165 250); }
        .field:focus { transform: translateY(-1px); }
        .field-icon { transition: color .25s ease, transform .3s var(--ease-out); }
        .group:focus-within .field-icon { color: rgb(37 99 235); transform: scale(1.1); }

        /* Primary button */
        .btn-primary { position: relative; overflow: hidden; transition: transform .25s var(--ease-out), box-shadow .25s ease, background-color .25s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(37, 99, 235, .55); }
        .btn-primary:active { transform: translateY(0) scale(.985); }
        .btn-primary::after { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 30%; background: linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent); transform: translateX(-120%) skewX(-20deg); }
        .btn-primary:hover::after { animation: sheen .9s var(--ease-out); }
        .btn-primary .btn-label, .btn-primary .btn-spinner { transition: opacity .25s ease, transform .3s var(--ease-out); }
        .btn-primary .btn-spinner { position: absolute; opacity: 0; transform: scale(.6); }
        .btn-primary.is-loading { pointer-events: none; }
        .btn-primary.is-loading .btn-label { opacity: 0; transform: translateY(-8px); }
        .btn-primary.is-loading .btn-spinner { opacity: 1; transform: scale(1); }
        .btn-spinner > svg { animation: spin .8s linear infinite; }

        /* Demo cards */
        .demo-card { transition: transform .3s var(--ease-out), box-shadow .3s ease, border-color .25s ease, background-color .25s ease; }
        .demo-card:hover { transform: translateY(-3px); box-shadow: 0 12px 24px -12px rgba(15, 23, 42, .25); }
        .demo-card:active { transform: translateY(0) scale(.98); }
        .demo-card .avatar { transition: transform .35s var(--ease-spring); }
        .demo-card:hover .avatar { transform: rotate(-6deg) scale(1.1); }
        .demo-card .go { opacity: 0; transform: translateX(-6px); transition: opacity .25s ease, transform .3s var(--ease-out); }
        .demo-card:hover .go, .demo-card:focus-visible .go { opacity: 1; transform: translateX(0); }

        /* Misc */
        .link-underline { background: linear-gradient(currentColor, currentColor) 0 100% / 0 1px no-repeat; transition: background-size .3s var(--ease-out), color .2s ease; }
        .link-underline:hover { background-size: 100% 1px; }
        .theme-toggle { transition: transform .4s var(--ease-spring), background-color .2s ease, color .2s ease; }
        .theme-toggle:hover { transform: rotate(18deg) scale(1.08); }
        .card-wrap { transition: box-shadow .4s ease; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .001ms !important; animation-delay: 0ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
            .rise, .slide-in, .cal-cell { opacity: 1; }
        }
    </style>
</head>
<body class="h-full min-h-screen flex">

    {{-- Theme toggle --}}
    <button type="button" onclick="toggleTheme()"
            class="theme-toggle fixed top-4 right-4 z-20 p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
            title="Toggle dark mode" aria-label="Toggle dark mode">
        <svg class="icon-moon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
        <svg class="icon-sun w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
    </button>

    {{-- ============ Left brand panel (desktop) ============ --}}
    <aside class="hidden lg:flex lg:w-1/2 xl:w-[55%] relative overflow-hidden bg-blue-600 text-white flex-col justify-between p-12 xl:p-16">
        {{-- drifting light --}}
        <div class="blob-a absolute -top-24 -left-24 w-96 h-96 rounded-full bg-blue-400/40 blur-3xl"></div>
        <div class="blob-b absolute -bottom-32 -right-20 w-[28rem] h-[28rem] rounded-full bg-blue-800/50 blur-3xl"></div>

        <div class="relative slide-in" style="--d:0">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur flex items-center justify-center ring-1 ring-white/25">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">LeaveFlow</span>
            </div>
        </div>

        <div class="relative max-w-md">
            <h1 class="slide-in text-4xl xl:text-5xl font-extrabold tracking-tight leading-[1.1]" style="--d:1">Plan time off without the paperwork.</h1>
            <p class="slide-in mt-4 text-blue-100 text-base leading-relaxed" style="--d:2">Request leave, track approvals and see your balance in one place.</p>

            {{-- Animated month: the one memorable moment --}}
            @php
                $approved = [8, 9, 10];
                $pending  = [22, 23, 24];
                $today    = 14;
                $offset   = 2; // month starts on Wednesday
            @endphp
            <div class="slide-in mt-10 rounded-2xl bg-white/10 backdrop-blur-md ring-1 ring-white/20 p-5 shadow-2xl shadow-blue-900/30" style="--d:3">
                <div class="grid grid-cols-7 gap-1.5 text-center text-[11px] font-semibold text-blue-100/80 mb-2">
                    @foreach(['M','T','W','T','F','S','S'] as $d)
                        <span>{{ $d }}</span>
                    @endforeach
                </div>
                <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-medium">
                    @for($i = 0; $i < $offset; $i++)
                        <span></span>
                    @endfor
                    @for($day = 1; $day <= 31; $day++)
                        @php
                            $col = ($day + $offset - 1) % 7;
                            $weekend = $col >= 5;
                            $idx = $day + $offset;
                        @endphp
                        @if(in_array($day, $approved))
                            <span class="cal-cell py-1.5 rounded-lg bg-emerald-500 text-white font-semibold" style="--i:{{ $idx }}">{{ $day }}</span>
                        @elseif(in_array($day, $pending))
                            <span class="cal-cell cal-pending py-1.5 rounded-lg bg-amber-500 text-white font-semibold" style="--i:{{ $idx }}">{{ $day }}</span>
                        @elseif($day === $today)
                            <span class="cal-cell py-1.5 rounded-lg bg-white text-blue-700 font-bold" style="--i:{{ $idx }}">{{ $day }}</span>
                        @else
                            <span class="cal-cell py-1.5 rounded-lg {{ $weekend ? 'text-blue-200/50' : 'text-white/85 bg-white/5' }}" style="--i:{{ $idx }}">{{ $day }}</span>
                        @endif
                    @endfor
                </div>
                <div class="mt-4 flex items-center gap-4 text-[11px] text-blue-100">
                    <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></i>Approved</span>
                    <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-sm bg-amber-500"></i>Pending</span>
                    <span class="flex items-center gap-1.5"><i class="w-2.5 h-2.5 rounded-sm bg-white"></i>Today</span>
                </div>
            </div>
        </div>

        <p class="relative slide-in text-xs text-blue-100/70" style="--d:5">Employee Leave Management System</p>
    </aside>

    {{-- ============ Right: form ============ --}}
    <main class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-12 overflow-y-auto">
        <div class="w-full max-w-md mx-auto">

            {{-- Mobile / tablet brand --}}
            <div class="text-center lg:text-left mb-8">
                <div class="rise lg:hidden mx-auto w-14 h-14 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-bold shadow-lg shadow-blue-500/25" style="--d:0">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2 class="rise mt-4 lg:mt-0 text-3xl font-extrabold text-slate-900 tracking-tight" style="--d:1">LeaveFlow Portal</h2>
                
            </div>

            <div class="rise card-wrap bg-white py-8 px-6 shadow-xl shadow-slate-200/50 rounded-2xl border border-slate-100 sm:px-10 hover:shadow-2xl hover:shadow-slate-200/60" style="--d:3">
                @if(session('info'))
                    <div class="alert-info mb-4 p-3 rounded-xl bg-blue-50 text-blue-800 text-xs border border-blue-200">
                        {{ session('info') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert-error mb-4 p-3 rounded-xl bg-rose-50 text-rose-800 text-xs border border-rose-200" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form id="login-form" class="space-y-5" action="{{ route('login') }}" method="POST">
                    @csrf

                    <div class="rise" style="--d:4">
                        <label for="login" class="block text-xs font-semibold text-slate-700">Email or Employee Number</label>
                        <div class="group relative mt-1.5">
                            <svg class="field-icon absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <input id="login" name="login" type="text" autocomplete="username" required value="{{ old('login', 'joel@company.com') }}" placeholder="e.g. joel@company.com or EMP-003" class="field appearance-none block w-full pl-10 pr-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">You can log in using either your corporate email or employee ID.</p>
                    </div>

                    <div class="rise" style="--d:5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                            <a href="{{ route('password.request') }}" class="link-underline text-xs font-medium text-blue-600 hover:text-blue-500">Forgot password?</a>
                        </div>
                        <div class="group relative mt-1.5">
                            <svg class="field-icon absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <input id="password" name="password" type="password" autocomplete="current-password" required value="password" placeholder="••••••••" class="field appearance-none block w-full pl-10 pr-11 py-2.5 border border-slate-300 rounded-xl shadow-xs placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <button type="button" id="toggle-password" aria-label="Show password" aria-pressed="false"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                <svg id="eye-open" class="w-4 h-4 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="eye-closed" class="w-4 h-4 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="rise flex items-center justify-between" style="--d:6">
                        <label for="remember" class="flex items-center cursor-pointer select-none">
                            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded transition cursor-pointer">
                            <span class="ml-2 block text-xs text-slate-700">Remember me on this device</span>
                        </label>
                    </div>

                    <div class="rise" style="--d:7">
                        <button id="submit-btn" type="submit" class="btn-primary w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500">
                            <span class="btn-label">Sign in to account</span>
                            <span class="btn-spinner" aria-hidden="true">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" opacity=".25"></circle>
                                    <path d="M22 12a10 10 0 00-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>

                {{-- Fast One-Click Demo Switcher --}}
                <div class="rise mt-8 border-t border-slate-100 pt-6" style="--d:8">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider text-center mb-3">Instant Demo Sign-in</p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <a href="{{ route('fast.login', 'hr') }}" class="demo-card p-2.5 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 flex items-center gap-2 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                            <div class="avatar w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold shrink-0">HR</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 leading-tight truncate">Sarah Jenkins</p>
                                <p class="text-[10px] text-slate-400">HR Manager</p>
                            </div>
                            <svg class="go w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="{{ route('fast.login', 'lead') }}" class="demo-card p-2.5 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50/50 flex items-center gap-2 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                            <div class="avatar w-7 h-7 rounded-lg bg-amber-600 text-white flex items-center justify-center font-bold shrink-0">TL</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 leading-tight truncate">James Vance</p>
                                <p class="text-[10px] text-slate-400">Team Lead</p>
                            </div>
                            <svg class="go w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                        <a href="{{ route('fast.login', 'employee') }}" class="demo-card p-2.5 rounded-xl border border-slate-200 hover:border-emerald-400 hover:bg-emerald-50/50 flex items-center gap-2 text-left col-span-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500">
                            <div class="avatar w-7 h-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold shrink-0">DEV</div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-800 leading-tight">Joel Loter (Senior Developer)</p>
                                <p class="text-[10px] text-slate-400">EMP-003 • 13 Days Available • 3 Days Pending</p>
                            </div>
                            <svg class="go w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        (function () {
            // Show / hide password
            var pw = document.getElementById('password');
            var btn = document.getElementById('toggle-password');
            var open = document.getElementById('eye-open');
            var closed = document.getElementById('eye-closed');
            btn.addEventListener('click', function () {
                var show = pw.type === 'password';
                pw.type = show ? 'text' : 'password';
                open.classList.toggle('hidden', show);
                closed.classList.toggle('hidden', !show);
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });

            // Loading state on submit
            var form = document.getElementById('login-form');
            var submit = document.getElementById('submit-btn');
            form.addEventListener('submit', function () {
                submit.classList.add('is-loading');
                submit.setAttribute('aria-busy', 'true');
            });
            // Reset if the page is restored from back/forward cache
            window.addEventListener('pageshow', function (e) {
                if (e.persisted) {
                    submit.classList.remove('is-loading');
                    submit.removeAttribute('aria-busy');
                }
            });
        })();
    </script>
</body>
</html>