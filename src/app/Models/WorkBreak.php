<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkBreak extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    protected $fillable = [
        'attendance_id',
        'user_id',
        'sequence_no',
        'break_started_at',
        'break_ended_at',
    ];

    protected $casts = [
        'break_started_at' => 'datetime',
        'break_ended_at' => 'datetime',
    ];
}
