<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_break1_start',
        'requested_break1_end',
        'requested_break2_start',
        'requested_break2_end',
        'requested_note',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'requested_break1_start' => 'datetime',
        'requested_break1_end' => 'datetime',
        'requested_break2_start' => 'datetime',
        'requested_break2_end' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function attendance() {
        return $this->belongsTo(\App\Models\Attendance::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function approver() {
        return $this->belongsTo(User::class, 'approved_by');
    }
}