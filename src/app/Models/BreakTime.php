<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'string',
        'break_end' => 'string',
    ];

    public function getBreakStartAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function getBreakEndAttribute($value)
{
        return $value ? Carbon::parse($value)->format('H:i') : null;
}

    public function attendance()
{
        return $this->belongsTo(Attendance::class);
}
}
