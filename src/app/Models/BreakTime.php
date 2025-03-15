<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
