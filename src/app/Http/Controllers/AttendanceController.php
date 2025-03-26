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

    if (!$user) {
        return redirect()->route('login')->with('error', 'ログインしてください');
    }

    $today = Carbon::today()->toDateString();

    // すでに出勤している場合は処理しない
    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if ($attendance && $attendance->clock_in) {
        return redirect()->route('user.attendance')->with('error', '既に出勤済みです');
    }

    // 初回出勤記録を作成
    Attendance::create([
        'user_id' => $user->id,
        'date' => $today,
        'clock_in' => now(),
        'status' => '出勤中',
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
        return redirect()->route('user.attendance')->with('error', '出勤していないため休憩できません');
    }

    if ($attendance->status === '休憩中') {
        return redirect()->route('user.attendance')->with('error', 'すでに休憩中です');
    }

    // 休憩開始記録を作成する
    $attendance->breaks()->create([
        'break_start' => now(),
    ]);

    // ステータス更新する
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
        return redirect()->route('user.attendance')->with('error', '休憩していないため戻ることができません');
    }

    // 最後の休憩データに終了時間を記録する
    $lastBreak = $attendance->breaks()->whereNull('break_end')->latest()->first();

    if ($lastBreak) {
        $lastBreak->update(['break_end' => now()]);
    }

    // ステータス更新する
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
        return redirect()->route('user.attendance')->with('error', '出勤していないため退勤できません');
    }

    if ($attendance->status === '退勤済') {
        return redirect()->route('user.attendance')->with('error', 'すでに退勤済みです');
    }

    $attendance->update([
        'clock_out' => now(),
        'status' => '退勤済'
    ]);

    return redirect()->route('user.attendance');
}

    public function list(Request $request)
{
    $user = Auth::user();

    // 月指定があれば使い、なければ今月を使う
    $month = $request->input('month')
        ? Carbon::parse($request->input('month'))
        : Carbon::now();

    $startOfMonth = $month->copy()->startOfMonth()->toDateString(); //$month の月の 最初の日を取得
    $endOfMonth = $month->copy()->endOfMonth()->toDateString(); //$month の月の 最後の日を取得

    $attendances = Attendance::with('breaks') //指定された月の間にある 自分の勤怠情報を breaks 関係も一緒に取得
        ->where('user_id', $user->id)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->orderBy('date', 'asc')
        ->get()
        ->map(function ($attendance) {
            // 出勤時間と退勤時間（フォーマット整える）
            $attendance->start_time = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $attendance->end_time = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

            // 休憩時間の合計（分単位）
            $totalBreakMinutes = $attendance->breaks->reduce(function ($carry, $break) {
                if ($break->break_start && $break->break_end) {
                    $start = Carbon::parse($break->break_start);
                    $end = Carbon::parse($break->break_end);
                    return $carry + $end->diffInMinutes($start);
                }
                return $carry;
            }, 0);

            // 表示形式に整形
            $attendance->break_time = $totalBreakMinutes
                ? sprintf('%d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60)
                : '';

            // 合計勤務時間（出勤～退勤 - 休憩）
            if ($attendance->clock_in && $attendance->clock_out) {
                $start = Carbon::parse($attendance->clock_in);
                $end = Carbon::parse($attendance->clock_out);
                $workMinutes = $end->diffInMinutes($start) - $totalBreakMinutes;
                $attendance->total_time = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
            } else {
                $attendance->total_time = '';
            }

            return $attendance;
        });

    return view('attendance.list', [
        'attendances' => $attendances, //勤怠データ
        'currentMonth' => $month, //現在表示している月
        'prevMonth' => $month->copy()->subMonth()->format('Y-m'), //前月の年月（例：2024-10）
        'nextMonth' => $month->copy()->addMonth()->format('Y-m'), //翌月の年月（例：2024-12）
    ]);
}

    public function attendanceDetail($id)
{
    $attendance = Attendance::with(['user', 'breaks'])
                    ->where('user_id', Auth::id()) // 自分のデータだけ取得
                    ->findOrFail($id);

    return view('attendance.detail', compact('attendance'));
}

}
