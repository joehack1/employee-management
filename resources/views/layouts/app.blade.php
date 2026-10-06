<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee Leave Management System')</title>
    <!-- Tailwind CSS CDN for instant rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        const tealBrand = {
            50: '#edf8f7', 100: '#d9efed', 200: '#b8e1de', 300: '#8bcbc7',
            400: '#58b1ac', 500: '#35a29e', 600: '#1d9692', 700: '#187f7c',
            800: '#175553', 900: '#164745', 950: '#0b2928'
        };
        const redBrand = {
            50: '#fbf1f1', 100: '#f6e2e2', 200: '#edcaca', 300: '#dfa4a5',
            400: '#cb7476', 500: '#b54c4f', 600: '#a01e22', 700: '#861a1d',
            800: '#70191b', 900: '#5d191b', 950: '#330b0c'
        };
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: tealBrand,
                        teal: tealBrand,
                        emerald: tealBrand,
                        indigo: tealBrand,
                        cyan: tealBrand,
                        green: tealBrand,
                        purple: tealBrand,
                        violet: tealBrand,
                        rose: redBrand,
                        red: redBrand,
                        amber: redBrand,
                        orange: redBrand,
                        yellow: redBrand,
                        brand: {
                            50: tealBrand[50],
                            100: tealBrand[100],
                            500: tealBrand[500],
                            600: tealBrand[600],
                            700: tealBrand[700],
                            800: tealBrand[800],
                            900: tealBrand[900],
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js for lightweight UI interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800">
    <div class="min-h-full flex flex-col">
        <!-- Top Navigation -->
        <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold shadow-md shadow-blue-500/20">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <a href="{{ route('dashboard') }}" class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2">
                                    LeaveFlow <span class="text-xs px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-semibold border border-blue-200">HRIS</span>
                                </a>
                                <p class="text-xs text-slate-500">Employee Leave Management</p>
                            </div>
                        </div>

                        <!-- Main Navigation Links -->
                        <nav class="hidden md:ml-8 md:flex md:space-x-1 items-center">
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">
                                Dashboard
                            </a>
                            <a href="{{ route('leave.create') }}" class="{{ request()->routeIs('leave.create') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition flex items-center gap-1.5 font-medium text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Apply Leave
                            </a>
                            <a href="{{ route('leave.index') }}" class="{{ request()->routeIs('leave.index') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">
                                My Requests
                            </a>
                            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.index') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">
                                Calendar
                            </a>
                            @if(auth()->user()->isManager())
                                <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">Analytics</a>
                            @endif

                            @if(auth()->user()->isTeamLead() || auth()->user()->isHr() || auth()->user()->isManager())
                                @php
                                    $approvalStatuses = auth()->user()->isManager()
                                        ? ['pending_manager']
                                        : (auth()->user()->isHr() ? ['pending_hr', 'cancellation_requested'] : ['pending_team_lead']);
                                    $pendingCount = \App\Models\LeaveApplication::whereIn('status', $approvalStatuses)->count();
                                @endphp
                                <a href="{{ route('approvals.pending') }}" class="{{ request()->routeIs('approvals.pending') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition flex items-center gap-1.5">
                                    Approvals
                                    @if($pendingCount > 0)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">
                                            {{ $pendingCount }}
                                        </span>
                                    @endif
                                </a>
                            @endif

                            @if(auth()->user()->isHr())
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.outside="open = false" class="text-slate-600 hover:text-slate-900 hover:bg-slate-100 px-3 py-2 rounded-lg text-sm transition flex items-center gap-1">
                                        HR Administration
                                        <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div x-show="open" x-cloak class="absolute left-0 mt-2 w-56 rounded-xl shadow-lg bg-white ring-1 ring-black/5 divide-y divide-slate-100 focus:outline-none z-50">
                                        <div class="py-1">
                                            <a href="{{ route('employees.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Employees Directory</a>
                                            <a href="{{ route('departments.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Departments & Teams</a>
                                            <a href="{{ route('leave_types.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Leave Types</a>
                                            <a href="{{ route('policies.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Leave Policies</a>
                                            <a href="{{ route('holidays.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Holidays & Working Days</a>
                                        </div>
                                        <div class="py-1">
                                            <a href="{{ route('reports.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Reports & Analytics</a>
                                            <a href="{{ route('audit.index') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Audit Trail</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </nav>
                    </div>

                    <!-- Right Top Navigation: Fast Demo Switcher, Notification Bell, User Profile -->
                    <div class="flex items-center gap-3">
                        <!-- Fast Role Switcher (Essential for testing all roles effortlessly) -->
                        <div class="hidden lg:flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                            <span class="text-slate-500 font-medium px-2">Switch:</span>
                            <a href="{{ route('fast.login', 'hr') }}" title="Sarah Jenkins (HR Manager)" class="px-2 py-1 rounded-lg {{ auth()->user()->isHr() ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">HR</a>
                            <a href="{{ route('fast.login', 'manager') }}" title="Alex Morgan (Manager)" class="px-2 py-1 rounded-lg {{ auth()->user()->isManager() ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Manager</a>
                            <a href="{{ route('fast.login', 'lead') }}" title="James Vance (Team Lead)" class="px-2 py-1 rounded-lg {{ auth()->user()->role === 'team_lead' ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Team Lead</a>
                            <a href="{{ route('fast.login', 'employee') }}" title="Joel Loter (Senior Dev)" class="px-2 py-1 rounded-lg {{ auth()->user()->email === 'joel@company.com' ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Joel (Dev)</a>
                            <a href="{{ route('fast.login', 'mary') }}" title="Mary Wanjiku (Engineer)" class="px-2 py-1 rounded-lg {{ auth()->user()->email === 'mary@company.com' ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Mary</a>
                        </div>

                        <!-- Notification Bell -->
                        @php
                            $unreadCount = auth()->user()->unreadNotifications->count();
                            $recentNotes = auth()->user()->notifications()->take(5)->get();
                        @endphp
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                </svg>
                                @if($unreadCount > 0)
                                    <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white ring-2 ring-white">
                                        {{ $unreadCount }}
                                    </span>
                                @endif
                            </button>

                            <!-- Notification Dropdown -->
                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 divide-y divide-slate-100 z-50">
                                <div class="px-4 py-3 flex items-center justify-between">
                                    <span class="text-sm font-semibold text-slate-900">Notifications</span>
                                    @if($unreadCount > 0)
                                        <form action="{{ route('notifications.readAll') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 font-medium">Mark all as read</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                                    @forelse($recentNotes as $note)
                                        <div class="p-3.5 hover:bg-slate-50 transition {{ $note->unread() ? 'bg-blue-50/40' : '' }}">
                                            <div class="flex items-start gap-2.5">
                                                <div class="mt-0.5">
                                                    @if(($note->data['type'] ?? '') === 'success')
                                                        <div class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs">✓</div>
                                                    @elseif(($note->data['type'] ?? '') === 'danger')
                                                        <div class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xs">✕</div>
                                                    @else
                                                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs">ℹ</div>
                                                    @endif
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-semibold text-slate-900">{{ $note->data['title'] ?? 'Notification' }}</p>
                                                    <p class="text-xs text-slate-600 mt-0.5 line-clamp-2">{{ $note->data['message'] ?? '' }}</p>
                                                    <p class="text-[10px] text-slate-400 mt-1">{{ $note->created_at->diffForHumans() }}</p>
                                                </div>
                                                @if($note->unread())
                                                    <form action="{{ route('notifications.read', $note->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" title="Mark read" class="text-slate-400 hover:text-slate-600 text-xs">●</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-6 text-center text-xs text-slate-400">No notifications yet</div>
                                    @endforelse
                                </div>
                                <div class="p-2 text-center bg-slate-50 rounded-b-2xl">
                                    <a href="{{ route('notifications.index') }}" class="text-xs text-slate-600 hover:text-blue-600 font-medium">View all notifications</a>
                                </div>
                            </div>
                        </div>

                        <!-- User Profile Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false" class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition focus:outline-none">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-sm flex items-center justify-center shadow-xs">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="hidden sm:block text-left text-xs">
                                    <p class="font-semibold text-slate-900 leading-tight">{{ auth()->user()->name }}</p>
                                    <p class="text-slate-500 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                                </div>
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 divide-y divide-slate-100 z-50">
                                <div class="p-3">
                                    <p class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                    @if(auth()->user()->employee)
                                        <span class="mt-2 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium {{ auth()->user()->employee->current_status === 'working' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (auth()->user()->employee->current_status === 'on_leave' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                            <span class="w-1.5 h-1.5 rounded-full {{ auth()->user()->employee->current_status === 'working' ? 'bg-emerald-500' : (auth()->user()->employee->current_status === 'on_leave' ? 'bg-rose-500' : 'bg-amber-500') }}"></span>
                                            Status: {{ ucfirst(str_replace('_', ' ', auth()->user()->employee->current_status)) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('profile.show') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">My Profile & Security</a>
                                    <a href="{{ route('leave.history') }}" class="flex items-center px-4 py-2 text-xs text-slate-700 hover:bg-slate-50">Leave History & Ledger</a>
                                </div>
                                <div class="py-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full text-left flex items-center px-4 py-2 text-xs text-rose-600 hover:bg-rose-50 font-medium">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            @if(session('success'))
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-3 text-sm shadow-xs">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs mb-3">
                    <div class="flex items-center gap-2 font-semibold">
                        <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                        <span>{{ session('error') ?? 'Please check the form for errors:' }}</span>
                    </div>
                    @if($errors->any())
                        <ul class="list-disc list-inside mt-2 text-xs text-rose-700 space-y-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 pb-12">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
                <p>&copy; {{ date('Y') }} LeaveFlow System. Designed for Enterprise Leave Management.</p>
                <div class="flex items-center gap-4 text-slate-400">
                    <span>Working Calendar: Mon-Fri</span>
                    <span>•</span>
                    <span>3-Day Advance Rule Enabled</span>
                    <span>•</span>
                    <span>Audit Trail Protected</span>
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
