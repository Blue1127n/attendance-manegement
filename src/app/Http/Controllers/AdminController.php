<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function attendanceList(Request $request)
{
    // 表示対象の日付（パラメータがあれば使用、なければ今日）
    $day = $request->input('day')
        ? Carbon::parse($request->input('day'))->startOfDay()
        : Carbon::today();

    $attendances = Attendance::with(['user', 'breaks'])  //指定された日の勤怠情報を breaks 関係も一緒に取得
        ->where('date', $day->toDateString())
        ->orderBy('user_id')
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

    return view('admin.attendance.list', [
        'attendances' => $attendances, //勤怠データ
        'currentDay' => $day, //現在表示している日
        'prevDay' => $day->copy()->subDay()->format('Y-m-d'), //前日の年月日（例：2024-10-01）
        'nextDay' => $day->copy()->addDay()->format('Y-m-d'), //翌日の年月日（例：2024-12-01）
    ]);
}
}
