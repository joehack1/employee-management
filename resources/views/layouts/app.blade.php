<!DOCTYPE html>
<html lang="en" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Employee Leave Management System')</title>
    @include('partials.theme')
    <!-- Tailwind CSS CDN for instant rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.tailwind-config')
    <!-- Alpine.js for lightweight UI interactivity -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        @keyframes flash-countdown { from { transform: scaleX(1); } to { transform: scaleX(0); } }
        .flash-countdown { transform-origin: left center; animation: flash-countdown 6s linear forwards; }
        .dashboard-hero {
            background-image: linear-gradient(rgba(255, 255, 255, .84), rgba(255, 255, 255, .84)), url('{{ asset('2205_w026_n002_1930b_p1_1930.jpg') }}');
            background-position: center;
            background-size: cover;
        }
        .dark .dashboard-hero {
            background-image: linear-gradient(rgba(0, 0, 0, .88), rgba(0, 0, 0, .88)), url('{{ asset('2205_w026_n002_1930b_p1_1930.jpg') }}');
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800">
    <div class="min-h-full flex flex-col">
        <!-- Top Navigation -->
        <header x-data="{ mobileOpen: false }" class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
            <div class="max-w-[1600px] mx-auto px-3 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 gap-2 sm:gap-4">
                    <div class="flex min-w-0">
                        <div class="flex-shrink-0 flex items-center gap-2 sm:gap-3 min-w-0">
                            <div class="w-9 h-9 sm:w-10 sm:h-10 flex-shrink-0 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold shadow-md shadow-blue-500/20">
                                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <a href="{{ route('dashboard') }}" class="text-[15px] sm:text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2 truncate">
                                    LeaveFlow <span class="hidden sm:inline text-xs px-2 py-0.5 rounded-md bg-blue-600 text-white font-semibold">HRIS</span>
                                </a>
                                <p class="hidden sm:block text-xs text-slate-500 truncate">Employee Leave Management</p>
                            </div>
                        </div>

                        <!-- Main Navigation Links -->
                        <nav class="hidden xl:flex xl:ml-8 xl:space-x-1 items-center whitespace-nowrap">
                            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">
                                Dashboard
                            </a>
                            @can('apply-leave')
                            <a href="{{ route('leave.create') }}" class="{{ request()->routeIs('leave.create') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition flex items-center gap-1.5 font-medium text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Apply Leave
                            </a>
                            @endcan
                            <a href="{{ route('leave.index') }}" class="{{ request()->routeIs('leave.index') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">
                                My Requests
                            </a>
                            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.index') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">
                                Calendar
                            </a>
                            @if(auth()->user()->isManager())
                                <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition">Analytics</a>
                            @endif

                            @if(auth()->user()->isTeamLead() || auth()->user()->isHr() || auth()->user()->isManager())
                                @php
                                    $approvalStatuses = auth()->user()->isManager()
                                        ? ['pending_manager']
                                        : (auth()->user()->isHr() ? ['pending_hr', 'cancellation_requested'] : ['pending_team_lead']);
                                    $pendingCount = \App\Models\LeaveApplication::whereIn('status', $approvalStatuses)->count();
                                @endphp
                                <a href="{{ route('approvals.pending') }}" class="{{ request()->routeIs('approvals.pending') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }} px-3 py-2 rounded-lg text-sm transition flex items-center gap-1.5">
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

                    <!-- Right Top Navigation: Mobile Menu, Fast Demo Switcher, Theme, Bell, Profile -->
                    <div class="flex items-center gap-1 sm:gap-2 lg:gap-3">

                        <!-- Mobile Menu Toggle -->
                        <button type="button" @click="mobileOpen = !mobileOpen"
                                class="xl:hidden p-1.5 sm:p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none"
                                title="Menu" aria-label="Toggle navigation menu" :aria-expanded="mobileOpen.toString()">
                            <svg x-show="!mobileOpen" class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <svg x-show="mobileOpen" x-cloak class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        <!-- Fast Role Switcher (Essential for testing all roles effortlessly) -->
                        <div class="hidden xl:flex items-center bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs">
                            <span class="text-slate-500 font-medium px-2">Switch:</span>
                            <a href="{{ route('fast.login', 'hr') }}" title="Sarah Jenkins (HR Manager)" class="px-2 py-1 rounded-lg {{ auth()->user()->isHr() ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">HR</a>
                            <a href="{{ route('fast.login', 'manager') }}" title="Alex Morgan (Manager)" class="px-2 py-1 rounded-lg {{ auth()->user()->isManager() ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Manager</a>
                            <a href="{{ route('fast.login', 'lead') }}" title="James Vance (Team Lead)" class="px-2 py-1 rounded-lg {{ auth()->user()->role === 'team_lead' ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Team Lead</a>
                            <a href="{{ route('fast.login', 'employee') }}" title="Joel Loter (Senior Dev)" class="px-2 py-1 rounded-lg {{ auth()->user()->email === 'joel@company.com' ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Joel (Dev)</a>
                            <a href="{{ route('fast.login', 'mary') }}" title="Mary Wanjiku (Engineer)" class="px-2 py-1 rounded-lg {{ auth()->user()->email === 'mary@company.com' ? 'bg-white shadow-xs font-semibold text-blue-700' : 'text-slate-600 hover:text-slate-900' }}">Mary</a>
                        </div>

                        <!-- Dark Mode Toggle -->
                        <button type="button" onclick="toggleTheme()"
                                class="theme-toggle p-2 rounded-xl text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition focus:outline-none"
                                title="Toggle dark mode" aria-label="Toggle dark mode">
                            <svg class="icon-moon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                            </svg>
                            <svg class="icon-sun w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </button>

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
                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-[min(24rem,calc(100vw-2rem))] sm:w-96 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 divide-y divide-slate-100 z-50">
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
                                                        <div class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs">✓</div>
                                                    @elseif(($note->data['type'] ?? '') === 'danger')
                                                        <div class="w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs">✕</div>
                                                    @else
                                                        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">ℹ</div>
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
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="Profile picture" class="w-9 h-9 rounded-full object-cover shadow-xs">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-semibold text-sm flex items-center justify-center shadow-xs">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif
                                <div class="hidden sm:block text-left text-xs">
                                    <p class="font-semibold text-slate-900 leading-tight">{{ auth()->user()->name }}</p>
                                    <p class="text-slate-500 capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                                </div>
                                <svg class="hidden sm:block w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>

                            <div x-show="open" x-cloak class="absolute right-0 mt-2 w-56 rounded-2xl shadow-xl bg-white ring-1 ring-black/5 divide-y divide-slate-100 z-50">
                                <div class="p-3">
                                    <p class="text-xs font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email }}</p>
                                    @if(auth()->user()->employee)
                                        <span class="mt-2 inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium {{ auth()->user()->employee->current_status === 'working' ? 'bg-emerald-600 text-white' : (auth()->user()->employee->current_status === 'on_leave' ? 'bg-rose-600 text-white' : 'bg-amber-600 text-white') }}">
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

                <!-- Mobile Navigation Panel -->
                <div x-show="mobileOpen" x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="xl:hidden border-t border-slate-200 bg-white shadow-lg">
                    <div class="max-w-[1600px] mx-auto px-3 sm:px-6 py-4 space-y-1 max-h-[calc(100vh-4rem)] overflow-y-auto">
                        <a @click="mobileOpen = false" href="{{ route('dashboard') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>
                            Dashboard
                        </a>
                        @can('apply-leave')
                        <a @click="mobileOpen = false" href="{{ route('leave.create') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('leave.create') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Apply Leave
                        </a>
                        @endcan
                        <a @click="mobileOpen = false" href="{{ route('leave.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('leave.index') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            My Requests
                        </a>
                        <a @click="mobileOpen = false" href="{{ route('calendar.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('calendar.index') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Calendar
                        </a>
                        @if(auth()->user()->isManager())
                        <a @click="mobileOpen = false" href="{{ route('reports.index') }}" class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.*') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Analytics
                        </a>
                        @endif

                        @if(auth()->user()->isTeamLead() || auth()->user()->isHr() || auth()->user()->isManager())
                        <a @click="mobileOpen = false" href="{{ route('approvals.pending') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('approvals.pending') ? 'bg-blue-600 text-white font-semibold' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            <span class="flex items-center gap-2.5">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Approvals
                            </span>
                            @if($pendingCount > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">{{ $pendingCount }}</span>
                            @endif
                        </a>
                        @endif

                        @if(auth()->user()->isHr())
                        <div class="pt-3 mt-3 border-t border-slate-100 space-y-1">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 pb-1">HR Administration</p>
                            <a @click="mobileOpen = false" href="{{ route('employees.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Employees Directory</a>
                            <a @click="mobileOpen = false" href="{{ route('departments.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Departments & Teams</a>
                            <a @click="mobileOpen = false" href="{{ route('leave_types.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Leave Types</a>
                            <a @click="mobileOpen = false" href="{{ route('policies.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Leave Policies</a>
                            <a @click="mobileOpen = false" href="{{ route('holidays.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Holidays & Working Days</a>
                            <a @click="mobileOpen = false" href="{{ route('reports.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Reports & Analytics</a>
                            <a @click="mobileOpen = false" href="{{ route('audit.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition">Audit Trail</a>
                        </div>
                        @endif

                        <div class="pt-3 mt-3 border-t border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 pb-2">Quick Switch (Demo)</p>
                            <div class="flex flex-wrap gap-1.5 px-3">
                                <a @click="mobileOpen = false" href="{{ route('fast.login', 'hr') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-medium {{ auth()->user()->isHr() ? 'bg-white shadow-xs text-blue-700 font-semibold' : 'bg-slate-100 text-slate-600' }}">HR</a>
                                <a @click="mobileOpen = false" href="{{ route('fast.login', 'manager') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-medium {{ auth()->user()->isManager() ? 'bg-white shadow-xs text-blue-700 font-semibold' : 'bg-slate-100 text-slate-600' }}">Manager</a>
                                <a @click="mobileOpen = false" href="{{ route('fast.login', 'lead') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-medium {{ auth()->user()->role === 'team_lead' ? 'bg-white shadow-xs text-blue-700 font-semibold' : 'bg-slate-100 text-slate-600' }}">Team Lead</a>
                                <a @click="mobileOpen = false" href="{{ route('fast.login', 'employee') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-medium {{ auth()->user()->email === 'joel@company.com' ? 'bg-white shadow-xs text-blue-700 font-semibold' : 'bg-slate-100 text-slate-600' }}">Joel (Dev)</a>
                                <a @click="mobileOpen = false" href="{{ route('fast.login', 'mary') }}" class="px-2.5 py-1.5 rounded-lg text-xs font-medium {{ auth()->user()->email === 'mary@company.com' ? 'bg-white shadow-xs text-blue-700 font-semibold' : 'bg-slate-100 text-slate-600' }}">Mary</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 mt-4 w-full">
            @if(session('success'))
                <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 6000)" x-show="visible" x-cloak x-transition:leave.opacity.duration.400ms role="status" class="relative mb-3 overflow-hidden border border-slate-200 border-l-4 border-l-[#1d9692] bg-white px-4 py-3 text-sm text-slate-800">
                    <span class="mr-2 text-[10px] font-bold uppercase tracking-wider text-[#1d9692]">Success</span>
                    <span>{{ session('success') }}</span>
                    <span aria-hidden="true" class="flash-countdown absolute bottom-0 left-0 h-[2px] w-full bg-[#1d9692]"></span>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div x-data="{ visible: true }" x-init="setTimeout(() => visible = false, 6000)" x-show="visible" x-cloak x-transition:leave.opacity.duration.400ms role="alert" class="relative mb-3 overflow-hidden border border-slate-200 border-l-4 border-l-[#a01e22] bg-white px-4 py-3 text-sm text-slate-800">
                    <div>
                        <span class="mr-2 text-[10px] font-bold uppercase tracking-wider text-[#a01e22]">{{ session('error') ? 'Error' : 'Check details' }}</span>
                        <span>{{ session('error') ?? 'Please check the form for errors:' }}</span>
                    </div>
                    @if($errors->any())
                        <ul class="mt-2 ml-1 space-y-1 border-l border-slate-200 pl-3 text-xs text-slate-600">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <span aria-hidden="true" class="flash-countdown absolute bottom-0 left-0 h-[2px] w-full bg-[#a01e22]"></span>
                </div>
            @endif
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 pb-12">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
            <div class="max-w-[1600px] mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
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
