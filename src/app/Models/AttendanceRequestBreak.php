<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequestBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id',
        'requested_break_start',
        'requested_break_end',
    ];

    public function attendanceRequest()
{
        return $this->belongsTo(AttendanceRequest::class);
}
}
