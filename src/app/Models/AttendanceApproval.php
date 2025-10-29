<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'proposed_clock_in_at',
        'proposed_clock_out_at',
        'proposed_remarks',
        'status',
    ];

    protected $casts = [
        'proposed_clock_in_at' => 'datetime',
        'proposed_clock_out_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breaks()
    {
        return $this->hasMany(AttendanceApprovalBreak::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
