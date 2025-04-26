<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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

    public function getRequestedBreakStartAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function getRequestedBreakEndAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}
}
