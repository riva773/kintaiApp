<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceApprovalBreak extends Model
{
    use HasFactory;

    public function attendanceApproval()
    {
        return $this->belongsTo(AttendanceApproval::class);
    }

    protected $casts = [
        'proposed_break_started_at' => 'datetime',
        'proposed_break_ended_at' => 'datetime',
    ];

    protected $fillable = [
        'sequence_no',
        'proposed_break_started_at',
        'proposed_break_ended_at',
        'attendance_approval_id',
    ];
}
