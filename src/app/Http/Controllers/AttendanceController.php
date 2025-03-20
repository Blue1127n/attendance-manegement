<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $today)
                                ->first();

        // 勤怠データがない場合は「勤務外」とする
        if (!$attendance) {
            $attendance = new Attendance([
                'status' => '勤務外'
            ]);
        }

        return view('attendance.index', compact('attendance'));
    }

    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 既に出勤しているか確認
        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['clock_in' => now(), 'status' => '出勤中']
        );

        return redirect()->route('user.attendance');
    }

    public function startBreak()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $today)
                                ->first();

        if ($attendance && $attendance->status === '出勤中') {
            $attendance->update(['status' => '休憩中']);
        }

        return redirect()->route('user.attendance');
    }

    public function endBreak()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $today)
                                ->first();

        if ($attendance && $attendance->status === '休憩中') {
            $attendance->update(['status' => '出勤中']);
        }

        return redirect()->route('user.attendance');
    }

    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $today)
                                ->first();

        if ($attendance && $attendance->status !== '退勤済') {
            $attendance->update([
                'clock_out' => now(),
                'status' => '退勤済'
            ]);
        }

        return redirect()->route('user.attendance');
    }
}
