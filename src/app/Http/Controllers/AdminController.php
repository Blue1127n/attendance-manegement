<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function attendanceDetail($id)
    {
    $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

    return view('admin.attendance.detail', compact('attendance'));
    }

    //$id で対象の勤怠レコードを取得
    public function updateAttendance(AdminAttendanceUpdateRequest $request, $id)
    {
    DB::beginTransaction(); //複数テーブルにまたがってデータを更新するので、途中でエラーがあったときはすべて巻き戻すようにする

    try {
        $attendance = Attendance::findOrFail($id); //該当IDの勤怠データを取得。なければ 404に。

        //出勤・退勤・備考を更新
        $attendance->clock_in = $request->clock_in;
        $attendance->clock_out = $request->clock_out;
        $attendance->remarks = $request->remarks;
        $attendance->save();

        // 既存の休憩時間を全削除してから新しいデータだけを再保存（「休憩1つ→2つ」などもOK）
        $attendance->breaks()->delete();

        //フォームから送られてきた休憩データを1件ずつ保存
        //開始・終了の両方が揃っているときだけ保存(データが不完全なときの不正登録を防止)
        foreach ($request->breaks as $break) {
            if ($break['start'] && $break['end']) {
                $attendance->breaks()->create([
                    'break_start' => $break['start'],
                    'break_end' => $break['end'],
                ]);
            }
        }

        DB::commit(); //すべて成功したらDBに反映

        return redirect()->route('admin.attendance.detail', $attendance->id);
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'エラーが発生しました。もう一度お試しください。'])->withInput();
    }
    }

    public function staffList()
    {
        $users = User::all(); //全ユーザー取得
        return view('admin.staff.list', compact('users'));
    }

}
