<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
