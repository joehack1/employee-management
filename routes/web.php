<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeManagementController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveCalendarController;
use App\Http\Controllers\LeavePolicyController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicHolidayController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Public / Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:6,1')
        ->name('password.update');
});

// Demo fast login switcher
Route::get('/fast-login/{roleOrEmail}', [AuthController::class, 'fastLogin'])->name('fast.login');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Password
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Leave Applications (Employee Side)
    Route::get('/leave', [LeaveApplicationController::class, 'index'])->name('leave.index');
    Route::middleware('can:apply-leave')->group(function () {
        Route::get('/leave/apply', [LeaveApplicationController::class, 'create'])->name('leave.create');
        Route::post('/leave/apply', [LeaveApplicationController::class, 'store'])->name('leave.store');
        Route::post('/leave/calculate-ajax', [LeaveApplicationController::class, 'calculateAjax'])->name('leave.calculate');
    });
    Route::get('/leave/history', [LeaveApplicationController::class, 'history'])->name('leave.history');
    Route::get('/leave/{id}', [LeaveApplicationController::class, 'show'])->name('leave.show');
    Route::post('/leave/{id}/cancel', [LeaveApplicationController::class, 'requestCancellation'])->name('leave.cancel');
    Route::post('/leave/{id}/comment', [LeaveApprovalController::class, 'addComment'])->name('leave.comment');

    // Leave Approvals (Team Lead & HR)
    Route::get('/approvals', [LeaveApprovalController::class, 'pending'])->name('approvals.pending');
    Route::post('/approvals/{id}/lead-approve', [LeaveApprovalController::class, 'teamLeadApprove'])->name('approvals.leadApprove');
    Route::post('/approvals/{id}/lead-reject', [LeaveApprovalController::class, 'teamLeadReject'])->name('approvals.leadReject');
    Route::post('/approvals/{id}/hr-approve', [LeaveApprovalController::class, 'hrApprove'])->name('approvals.hrApprove');
    Route::post('/approvals/{id}/hr-reject', [LeaveApprovalController::class, 'hrReject'])->name('approvals.hrReject');
    Route::post('/approvals/{id}/manager-approve', [LeaveApprovalController::class, 'managerApprove'])->name('approvals.managerApprove');
    Route::post('/approvals/{id}/manager-reject', [LeaveApprovalController::class, 'managerReject'])->name('approvals.managerReject');
    Route::post('/approvals/{id}/approve-cancellation', [LeaveApprovalController::class, 'approveCancellation'])->name('approvals.approveCancellation');

    // Calendar
    Route::get('/calendar', [LeaveCalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [LeaveCalendarController::class, 'eventsJson'])->name('calendar.events');

    // Attachments (Secure Download)
    Route::get('/attachments/{id}/download', [AttachmentController::class, 'download'])->name('attachments.download');

    // HR & Admin Routes
    Route::middleware('can:manage-hr')->group(function () {
        // Employee Management & Ledger
        Route::get('/employees', [EmployeeManagementController::class, 'index'])->name('employees.index');
        Route::get('/employees/create', [EmployeeManagementController::class, 'create'])->name('employees.create');
        Route::post('/employees', [EmployeeManagementController::class, 'store'])->name('employees.store');
        Route::get('/employees/{id}/edit', [EmployeeManagementController::class, 'edit'])->name('employees.edit');
        Route::put('/employees/{id}', [EmployeeManagementController::class, 'update'])->name('employees.update');
        Route::post('/employees/{id}/toggle', [EmployeeManagementController::class, 'toggleStatus'])->name('employees.toggle');
        Route::post('/employees/{id}/reset-password', [EmployeeManagementController::class, 'resetPassword'])->name('employees.resetPassword');
        Route::get('/employees/{id}/leave-account', [EmployeeManagementController::class, 'leaveAccount'])->name('employees.leaveAccount');
        Route::post('/employees/{id}/adjust-balance', [EmployeeManagementController::class, 'adjustBalance'])->name('employees.adjustBalance');

        // Departments & Teams
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::post('/departments', [DepartmentController::class, 'storeDepartment'])->name('departments.store');
        Route::post('/teams', [DepartmentController::class, 'storeTeam'])->name('teams.store');
        Route::post('/departments/{id}/toggle', [DepartmentController::class, 'toggleDepartment'])->name('departments.toggle');

        // Leave Types
        Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('leave_types.index');
        Route::get('/leave-types/{id}/edit', [LeaveTypeController::class, 'edit'])->name('leave_types.edit');
        Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('leave_types.store');
        Route::put('/leave-types/{id}', [LeaveTypeController::class, 'update'])->name('leave_types.update');
        Route::post('/leave-types/{id}/toggle', [LeaveTypeController::class, 'toggle'])->name('leave_types.toggle');
        Route::delete('/leave-types/{id}', [LeaveTypeController::class, 'destroy'])->name('leave_types.destroy');

        // Leave Policies
        Route::get('/policies', [LeavePolicyController::class, 'index'])->name('policies.index');
        Route::post('/policies', [LeavePolicyController::class, 'store'])->name('policies.store');
        Route::put('/policies/{id}', [LeavePolicyController::class, 'update'])->name('policies.update');

        // Holidays & Working Calendar
        Route::get('/holidays', [PublicHolidayController::class, 'index'])->name('holidays.index');
        Route::post('/holidays', [PublicHolidayController::class, 'store'])->name('holidays.store');
        Route::delete('/holidays/{id}', [PublicHolidayController::class, 'destroy'])->name('holidays.destroy');
        Route::post('/working-days', [PublicHolidayController::class, 'updateWorkingDays'])->name('working_days.update');

        // Audit Trail
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');

        // Run Reminders manually
        Route::post('/admin/run-reminders', function () {
            Artisan::call('leave:reminders');
            return back()->with('success', 'Leave reminder scan executed successfully!');
        })->name('admin.reminders.run');
    });

    Route::middleware('can:manage-analytics')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
    });
});
