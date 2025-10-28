<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;



class StaffController extends Controller
{
    public function index()
    {
        $users = User::where('role', 'general')->get();
        return view('admin.staffs.index', compact('users'));
    }
}
