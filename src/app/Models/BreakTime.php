<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks'; // テーブル名と相違のためこれを追加する

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
        return $value ? Carbon::parse($value)->format('H:i') : null; // 休憩開始時間を "HH:MM" 形式で取得するために記述
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
