<?php

use App\Http\Controllers\AttendancesController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;


Route::get('/attendance', [AttendancesController::class, 'create'])->name('attendance.create')->middleware('auth');

Route::post('/attendance', [AttendancesController::class, 'store'])->name('attendance.store');
