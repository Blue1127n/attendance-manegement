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

        // 今日の勤怠データを取得（なければnull）
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $today)
                                ->first();

        // 勤怠データがない場合は「勤務外」とする
        if (!$attendance) {
            $attendance = (object) [
                'status' => '勤務外'
        ];
    }

        return view('attendance.index', compact('attendance'));
}

    public function clockIn()
{
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    // すでに出勤している場合は処理しない
    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if ($attendance && $attendance->clock_in) {
        return redirect()->route('user.attendance')->with('error', '既に出勤済みです。');
    }

    // 初回出勤記録を作成
    Attendance::create([
        ['user_id' => $user->id, 'date' => $today],
        ['clock_in' => now(), 'status' => '出勤中']
    ]);

    return redirect()->route('user.attendance');
}

    public function startBreak()
{
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if (!$attendance || $attendance->status !== '出勤中') {
        return redirect()->route('user.attendance')->with('error', '出勤していないため休憩できません。');
    }

    if ($attendance->status === '休憩中') {
        return redirect()->route('user.attendance')->with('error', 'すでに休憩中です。');
    }

    $attendance->update(['status' => '休憩中']);

    return redirect()->route('user.attendance');
}

    public function endBreak()
{
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if (!$attendance || $attendance->status !== '休憩中') {
        return redirect()->route('user.attendance')->with('error', '休憩していないため戻ることができません。');
    }

    $attendance->update(['status' => '出勤中']);

    return redirect()->route('user.attendance');
}

    public function clockOut()
{
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    // もし出勤記録がなかったらエラーメッセージを返す
    if (!$attendance || !$attendance->clock_in) {
        return redirect()->route('user.attendance')->with('error', '出勤していないため退勤できません。');
    }

    if ($attendance->status === '退勤済') {
        return redirect()->route('user.attendance')->with('error', 'すでに退勤済みです。');
    }

    $attendance->update([
        'clock_out' => now(),
        'status' => '退勤済'
    ]);

    return redirect()->route('user.attendance');
}

}
