<?php

use App\Http\Controllers\AttendancesController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureAdmin;


require __DIR__ . '/admin.php';


Route::get('/attendance', [AttendancesController::class, 'create'])->name('attendance.create')->middleware('auth');

Route::post('/attendance', [AttendancesController::class, 'store'])->name('attendance.store');

Route::get('/attendance/list', [AttendancesController::class, 'index'])->name('attendance.index')->middleware('auth');

Route::get('/attendance/detail/{id}', [AttendancesController::class, 'show'])->name('attendance.show')->middleware(('auth'));

Route::post('/attendance/{attendance}/corrections', [AttendancesController::class, 'submitCorrection'])->name('attendance.corrections.submit')->middleware('auth');

Route::get('/stamp_correction_request/list', [ApprovalsController::class, 'index'])->name('approvals.index')->middleware('auth');

Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [ApprovalsController::class, 'show'])->middleware(['auth', EnsureAdmin::class])->name('approvals.show');

Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [ApprovalsController::class, 'approve'])->middleware(['auth', EnsureAdmin::class])->name('approvals.approve');

Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
