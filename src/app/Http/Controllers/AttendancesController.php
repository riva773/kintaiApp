<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendancesController extends Controller
{
    public function create()
    {
        return view('create');
    }
}
