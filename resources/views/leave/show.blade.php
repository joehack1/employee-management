@extends('layouts.app')

@section('title', 'Leave Application ' . $application->application_number . ' - LeaveFlow')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ openCancelModal: false, openHrApprove: false, openHrReject: false, openLeadApprove: false, openLeadReject: false }">
    <!-- Breadcrumb & Status Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('leave.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                &larr; Back to Requests
            </a>
            <div class="flex flex-wrap items-center gap-3 mt-2">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $application->application_number }}</h1>
                <span class="px-3 py-1 rounded-xl text-xs font-semibold border {{ $application->status_badge['class'] }}">
                    {{ $application->status_badge['label'] }}
                </span>
                @if($application->is_emergency)
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-600 text-white">
                        🚨 Emergency Exception
                    </span>
                @endif
                @if($application->is_half_day)
                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-300">
                        Half Day ({{ ucfirst($application->half_day_type) }})
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 mt-1">Submitted on {{ $application->created_at->format('d M Y \a\t H:i') }}</p>
        </div>

        <!-- Action Buttons depending on role -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Team Lead Actions -->
            @if($isTeamLead && $application->status === 'pending_team_lead')
                <button @click="openLeadApprove = true" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs">
                    Approve (Team Lead)
                </button>
                <button @click="openLeadReject = true" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs">
                    Reject
                </button>
            @endif

            <!-- HR Actions -->
            @if($isHr && $application->status === 'pending_hr')
                <button @click="openHrApprove = true" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs">
                    Final Approve (HR)
                </button>
                <button @click="openHrReject = true" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs">
                    Reject (HR)
                </button>
            @endif

            <!-- Cancellation Approval for HR -->
            @if($isHr && $application->status === 'cancellation_requested')
                <form action="{{ route('approvals.approveCancellation', $application->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold shadow-xs">
                        Approve Cancellation (Refund Days)
                    </button>
                </form>
            @endif

            <!-- Employee Cancellation Request (Req 25) -->
            @if($isOwner && $application->status === 'approved')
                <button @click="openCancelModal = true" class="px-4 py-2 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-semibold transition">
                    Request Cancellation
                </button>
            @endif
        </div>
    </div>

    <!-- Main Grid: Application Details + Approval Timeline -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Application Details -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200 space-y-6">
                <!-- Employee Summary -->
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-800 font-bold text-base flex items-center justify-center">
                            {{ substr($application->employee->first_name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900">{{ $application->employee->full_name }}</h2>
                            <p class="text-xs text-slate-500">
                                {{ $application->employee->job_title }} • {{ $application->employee->department->name ?? 'General' }}
                                @if($application->employee->team) ({{ $application->employee->team->name }}) @endif
                            </p>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-slate-400">{{ $application->employee->employee_number }}</span>
                </div>

                <!-- Leave Period Highlight Box -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Leave Type</p>
                        <p class="text-sm font-extrabold text-slate-900 mt-0.5">{{ $application->leaveType->name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Requested Period</p>
                        <p class="text-sm font-extrabold text-slate-900 mt-0.5">
                            {{ $application->start_date->format('d M') }} - {{ $application->end_date->format('d M Y') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Duration</p>
                        <p class="text-sm font-extrabold text-blue-700 mt-0.5">
                            {{ $application->total_days }} {{ Str::plural('working day', $application->total_days) }}
                        </p>
                    </div>
                </div>

                <!-- Reason Details -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Reason for Request</h3>
                    <div class="mt-2 p-4 rounded-2xl bg-slate-50 border border-slate-100 text-xs text-slate-700 leading-relaxed">
                        {{ $application->reason ?: 'No reason provided.' }}
                    </div>
                </div>

                <!-- Sick Leave Details if provided -->
                @if($application->doctor_hospital_info || $application->medical_reason)
                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-xs space-y-2">
                        <h3 class="font-bold text-emerald-900 flex items-center gap-1.5">
                            <span>🏥</span> Medical / Hospital Information
                        </h3>
                        @if($application->doctor_hospital_info)
                            <p class="text-slate-700"><strong>Doctor/Facility:</strong> {{ $application->doctor_hospital_info }}</p>
                        @endif
                        @if($application->medical_reason)
                            <p class="text-slate-700"><strong>Diagnosis:</strong> {{ $application->medical_reason }}</p>
                        @endif
                    </div>
                @endif

                <!-- Attachments (Req 28) -->
                @if($application->attachments->count() > 0 || $application->manual_attachment_expected)
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Supporting Documents</h3>
                        @if($application->manual_attachment_expected)
                            <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">Employee will deliver the supporting document manually to HR.</p>
                        @endif
                        <div class="mt-2 space-y-2">
                            @foreach($application->attachments as $att)
                                <div class="p-3 rounded-2xl border border-slate-200 flex items-center justify-between bg-slate-50/50">
                                    <div class="flex items-center gap-2.5">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">{{ $att->original_name }}</p>
                                            <p class="text-[10px] text-slate-400">{{ round($att->file_size / 1024, 1) }} KB • Uploaded {{ $att->created_at->format('d M Y') }}</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('attachments.download', $att->id) }}" class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs transition">
                                        Download Securely
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Rejection Notice Banner -->
                @if($application->status === 'rejected')
                    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                        <p class="font-bold flex items-center gap-1.5 text-rose-900">
                            <span>✕</span> Application Rejected
                        </p>
                        <p><strong>Reason:</strong> {{ $application->rejection_reason }}</p>
                        @if($application->rejectedByUser)
                            <p class="text-[11px] text-rose-600 mt-1">Rejected by {{ $application->rejectedByUser->name }}</p>
                        @endif
                    </div>
                @endif

                <!-- Cancellation Notice Banner -->
                @if($application->status === 'cancelled')
                    <div class="p-4 rounded-2xl bg-slate-100 border border-slate-300 text-xs text-slate-700 space-y-1">
                        <p class="font-bold text-slate-900">🚫 Leave Cancelled</p>
                        <p><strong>Reason:</strong> {{ $application->cancellation_reason ?? 'Cancelled upon request' }}</p>
                        <p class="text-[11px] text-slate-500">Days refunded back to employee balance.</p>
                    </div>
                @elseif($application->status === 'cancellation_requested')
                    <div class="p-4 rounded-2xl bg-orange-50 border border-orange-300 text-xs text-orange-900 space-y-1">
                        <p class="font-bold">⚠️ Cancellation Requested</p>
                        <p><strong>Employee's Reason:</strong> {{ $application->cancellation_reason }}</p>
                        <p class="text-[11px] text-orange-700">Awaiting HR confirmation to process refund.</p>
                    </div>
                @endif

                <!-- Breakdown of Individual Days -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Daily Calendar Breakdown</h3>
                    <div class="border border-slate-200 rounded-2xl overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead class="bg-slate-50 font-bold text-slate-600 uppercase">
                                <tr>
                                    <th class="px-4 py-2 text-left">Date</th>
                                    <th class="px-4 py-2 text-left">Day</th>
                                    <th class="px-4 py-2 text-center">Working Day?</th>
                                    <th class="px-4 py-2 text-right">Counted Weight</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($application->applicationDays as $day)
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-slate-800">{{ $day->date->format('d M Y') }}</td>
                                        <td class="px-4 py-2 text-slate-500">{{ $day->date->format('l') }}</td>
                                        <td class="px-4 py-2 text-center">
                                            @if($day->is_working_day)
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-600 text-white">Working Day</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-500">Weekend / Holiday</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-slate-900">{{ $day->weight }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Requirement 10: Comments & Communication Thread -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <h3 class="text-base font-bold text-slate-900">Application Notes & Comments</h3>
                
                <div class="space-y-3">
                    @forelse($application->comments as $c)
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900">{{ $c->user->name }} ({{ ucfirst($c->user->role) }})</span>
                                <span class="text-[10px] text-slate-400">{{ $c->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <p class="text-slate-700">{{ $c->comment }}</p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">No comments logged on this request.</p>
                    @endforelse
                </div>

                <!-- Add Comment Form -->
                <form action="{{ route('leave.comment', $application->id) }}" method="POST" class="pt-2 flex gap-2">
                    @csrf
                    <input type="text" name="comment" required placeholder="Write a note or comment on this application..." class="flex-1 px-3.5 py-2 border border-slate-300 rounded-xl text-xs">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs">
                        Post Comment
                    </button>
                </form>
            </div>
        </div>

        <!-- Right: Approval Workflow Timeline & History (Req 7, 10, 21) -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">Approval Workflow Timeline</h3>
                
                <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                    <!-- Step 1: Submission -->
                    <div class="relative">
                        <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-blue-600 border-2 border-white ring-2 ring-blue-100"></span>
                        <p class="text-xs font-bold text-slate-900">Application Submitted</p>
                        <p class="text-[11px] text-slate-500">{{ $application->created_at->format('d M Y H:i') }}</p>
                        <p class="text-[11px] text-slate-600 mt-0.5">By {{ $application->employee->full_name }}</p>
                    </div>

                    <!-- Step 2: Team Lead Review -->
                    @php $leadApproval = $application->approvals->where('level', 'team_lead')->first(); @endphp
                    <div class="relative">
                        @if($leadApproval)
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full {{ $leadApproval->action === 'approved' ? 'bg-emerald-600' : 'bg-rose-600' }} border-2 border-white ring-2 ring-emerald-100"></span>
                            <p class="text-xs font-bold text-slate-900">
                                Team Lead: {{ ucfirst($leadApproval->action) }}
                            </p>
                            <p class="text-[11px] text-slate-500">{{ $leadApproval->created_at->format('d M Y H:i') }}</p>
                            <p class="text-[11px] text-slate-600 mt-0.5">Reviewed by {{ $leadApproval->approver->name }}</p>
                            @if($leadApproval->comment)
                                <p class="text-[11px] text-slate-700 bg-slate-50 p-2 rounded-lg mt-1 italic border border-slate-200">
                                    "{{ $leadApproval->comment }}"
                                </p>
                            @endif
                        @else
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-slate-300 border-2 border-white"></span>
                            <p class="text-xs font-bold text-slate-400">Team Lead Review</p>
                            <p class="text-[11px] text-slate-400">Awaiting review</p>
                        @endif
                    </div>

                    <!-- Step 3: HR Final Approval -->
                    @php $hrApproval = $application->approvals->where('level', 'hr')->first(); @endphp
                    <div class="relative">
                        @if($hrApproval)
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full {{ in_array($hrApproval->action, ['approved', 'cancellation_approved']) ? 'bg-emerald-600' : 'bg-rose-600' }} border-2 border-white ring-2 ring-emerald-100"></span>
                            <p class="text-xs font-bold text-slate-900">
                                HR Review: {{ ucfirst(str_replace('_', ' ', $hrApproval->action)) }}
                            </p>
                            <p class="text-[11px] text-slate-500">{{ $hrApproval->created_at->format('d M Y H:i') }}</p>
                            <p class="text-[11px] text-slate-600 mt-0.5">Reviewed by {{ $hrApproval->approver->name }}</p>
                            @if($hrApproval->comment)
                                <p class="text-[11px] text-slate-700 bg-slate-50 p-2 rounded-lg mt-1 italic border border-slate-200">
                                    "{{ $hrApproval->comment }}"
                                </p>
                            @endif
                        @else
                            <span class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-slate-300 border-2 border-white"></span>
                            <p class="text-xs font-bold text-slate-400">HR Review & Sign-Off</p>
                            <p class="text-[11px] text-slate-400">Pending</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Team Lead & HR Actions -->
    <!-- Team Lead Approve Modal -->
    <div x-show="openLeadApprove" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openLeadApprove = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Approve as Team Lead</h3>
            <form action="{{ route('approvals.leadApprove', $application->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Comment (Optional)</label>
                    <textarea name="comment" rows="3" placeholder="e.g. Approved. Tasks handed over." class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openLeadApprove = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">Approve & Forward to HR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Team Lead Reject Modal -->
    <div x-show="openLeadReject" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openLeadReject = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-rose-600">Reject Application</h3>
            <form action="{{ route('approvals.leadReject', $application->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Reason for Rejection *</label>
                    <textarea name="rejection_reason" required rows="3" placeholder="e.g. Too many team members away." class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openLeadReject = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white">Reject Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HR Approve Modal -->
    <div x-show="openHrApprove" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openHrApprove = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">HR Final Approval</h3>
            <p class="text-xs text-slate-500 mt-1">Will deduct {{ $application->total_days }} days from employee ledger.</p>
            <form action="{{ route('approvals.hrApprove', $application->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Approval Comment (Optional)</label>
                    <textarea name="comment" rows="3" placeholder="e.g. Approved. Please ensure handover is complete." class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openHrApprove = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">Approve & Deduct</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HR Reject Modal -->
    <div x-show="openHrReject" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openHrReject = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-rose-600">Reject Application (HR)</h3>
            <form action="{{ route('approvals.hrReject', $application->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Reason for Rejection *</label>
                    <textarea name="rejection_reason" required rows="3" placeholder="e.g. Insufficient coverage." class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openHrReject = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white">Reject Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Employee Cancellation Request Modal (Req 25) -->
    <div x-show="openCancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openCancelModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Request Leave Cancellation</h3>
            <p class="text-xs text-slate-500 mt-1">
                Provide a reason for cancelling your approved leave. Upon HR review, days will be returned to your balance ledger.
            </p>
            <form action="{{ route('leave.cancel', $application->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Reason for Cancellation <span class="text-rose-500">*</span></label>
                    <textarea name="cancellation_reason" required rows="3" placeholder="e.g. Project deadline shifted, cancelling travel plans." class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCancelModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Dismiss</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
