<?php

use App\Http\Controllers\AttendancesController;
use App\Http\Controllers\ApprovalsController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

Route::redirect('/', '/login');



require __DIR__ . '/admin.php';


Route::get('/attendance', [AttendancesController::class, 'create'])->name('attendance.create')->middleware(['auth', 'verified','ensure.nonAdmin']);

Route::post('/attendance', [AttendancesController::class, 'store'])->name('attendance.store')->middleware(['auth','verified','ensure.nonAdmin']);

Route::get('/attendance/list', [AttendancesController::class, 'index'])->name('attendance.index')->middleware(['auth', 'verified','ensure.nonAdmin']);

Route::get('/attendance/detail/{id}', [AttendancesController::class, 'show'])->name('attendance.show')->middleware(['auth', 'verified','ensure.nonAdmin']);

Route::post('/attendance/{attendance}/corrections', [AttendancesController::class, 'submitCorrection'])->name('attendance.corrections.submit')->middleware(['auth', 'verified','ensure.nonAdmin']);

Route::get('/stamp_correction_request/list', [ApprovalsController::class, 'index'])->name('approvals.index')->middleware(['auth', 'verified',]);

Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [ApprovalsController::class, 'show'])->middleware(['auth', 'verified', EnsureAdmin::class])->name('approvals.show');

Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [ApprovalsController::class, 'approve'])->middleware(['auth', 'verified', EnsureAdmin::class])->name('approvals.approve');

Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('attendance.create')
            ->with('status', 'メール認証が完了しました。');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('attendance.create');
        }
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', '認証メールを再送しました。');
    })->middleware(['throttle:6,1'])->name('verification.send');
});
