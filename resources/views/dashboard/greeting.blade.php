@php
    $timeGreeting = now()->hour < 12 ? 'Good morning' : (now()->hour < 17 ? 'Good afternoon' : 'Good evening');
@endphp

<section x-cloak x-show="visible" x-transition:leave="transition ease-in duration-500" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2" class="dashboard-greeting rounded-3xl border border-blue-100 bg-gradient-to-r from-blue-50 to-white p-5 sm:px-6 shadow-xs" aria-label="Dashboard greeting">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-blue-700">Welcome back</p>
            <h2 class="mt-1 text-xl font-bold text-slate-900">{{ $timeGreeting }}, {{ auth()->user()->name }}</h2>
            <p class="mt-1 text-xs text-slate-500">Here’s what’s happening today.</p>
        </div>
    </div>
</section>
