<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Attendance extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(WorkBreak::class)->orderBy('sequence_no');
    }

    public function approvals()
    {
        return $this->hasMany(AttendanceApproval::class);
    }

    public function pendingApproval()
    {
        return $this->hasOne(AttendanceApproval::class)->where('status', 'pending');
    }

    protected $fillable = [
        'user_id',
        'status',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'remarks'
    ];

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'work_date' => 'datetime',
    ];
}
