<?php

use App\Http\Controllers\AttendancesController;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/attendance', [AttendancesController::class, 'create'])->name('attendance.create');
