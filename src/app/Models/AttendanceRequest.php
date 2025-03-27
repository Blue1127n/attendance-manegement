<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'requested_clock_in',
        'requested_clock_out',
        'requested_break_start',
        'requested_break_end',
        'remarks',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function getRequestedClockInAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function getRequestedClockOutAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function getRequestedBreakStartAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function getRequestedBreakEndAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function user()
{
        return $this->belongsTo(User::class);
}

    public function attendance()
{
        return $this->belongsTo(Attendance::class);
}
}
