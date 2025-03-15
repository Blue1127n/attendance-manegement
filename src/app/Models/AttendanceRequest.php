<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'status' => 'string', // ENUM型は文字列として扱うので記述しておく
        'requested_clock_in' => 'string',
        'requested_clock_out' => 'string',
        'requested_break_start' => 'string',
        'requested_break_end' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
