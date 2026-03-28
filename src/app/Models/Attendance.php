<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'note',
        'status',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function breaks() {
        return $this->hasMany(AttendanceBreak::class)->orderBy('break_start_at');
    }

    public function correctionRequests() {
        return $this->hasMany(StampCorrectionRequest::class);
    }
}
