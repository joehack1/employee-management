@extends('layouts.app')

@section('title', 'Notifications Center - LeaveFlow')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Notification Center</h1>
            <p class="text-xs text-slate-500">Alerts regarding submissions, approvals, rejections, and reminders</p>
        </div>
        @if(auth()->user()->unreadNotifications->count() > 0)
            <form action="{{ route('notifications.readAll') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                    Mark All as Read
                </button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 divide-y divide-slate-100">
        @forelse($notifications as $note)
            <div class="py-4 flex items-start justify-between gap-4 {{ $note->unread() ? 'bg-blue-50/30 -mx-6 px-6' : '' }}">
                <a href="{{ route('notifications.open', $note->id) }}" class="flex flex-1 items-start gap-3 hover:bg-slate-50 rounded-xl transition">
                    <div class="mt-1">
                        @if(($note->data['type'] ?? '') === 'success')
                            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">✓</div>
                        @elseif(($note->data['type'] ?? '') === 'danger')
                            <div class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs">✕</div>
                        @elseif(($note->data['type'] ?? '') === 'warning')
                            <div class="w-8 h-8 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-xs">!</div>
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs">ℹ</div>
                        @endif
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">{{ $note->data['title'] ?? 'Notification' }}</h3>
                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ $note->data['message'] ?? '' }}</p>
                        <div class="flex items-center gap-3 mt-1.5 text-[10px] text-slate-400">
                            <span>{{ $note->created_at->format('d M Y \a\t H:i') }}</span>
                            @if(isset($note->data['url']))
                                <span>•</span>
                                <span class="text-blue-600 font-semibold">Open notification &rarr;</span>
                            @endif
                        </div>
                    </div>
                </a>

                @if($note->unread())
                    <form action="{{ route('notifications.read', $note->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1 rounded-lg text-xs text-slate-500 hover:text-slate-800 hover:bg-slate-100">
                            Mark Read
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="py-12 text-center text-slate-400 text-xs">
                No notifications logged in your account.
            </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div class="p-2">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
