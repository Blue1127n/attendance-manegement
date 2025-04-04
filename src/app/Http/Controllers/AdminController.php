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

        // セッションに「修正済み」フラグを持たせて戻る
        return redirect()->route('admin.attendance.detail', $attendance->id)->with('corrected', true);
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

    public function staffAttendance(Request $request, $id)
{
    $user = User::findOrFail($id); // users テーブルから IDが一致するユーザーを取得
    //存在しないIDでアクセスされたらfindOrFail自動でエラーを表示してくれる

    // 月の取得
    $month = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now(); //指定なければ今月を表示対象とする
    $startOfMonth = $month->copy()->startOfMonth()->toDateString(); //その月の最初と最後の日を取得 例: 2025-03-01
    $endOfMonth = $month->copy()->endOfMonth()->toDateString(); // 例: 2025-03-31

    $attendances = Attendance::with('breaks') //attendances テーブルから、breaks（休憩時間）も一緒に読み込みます（with('breaks')）
        ->where('user_id', $id)//where('user_id', $id) で「user_id がそのユーザーのもの」を絞り込み
        ->whereBetween('date', [$startOfMonth, $endOfMonth]) //date がその月の日付範囲に含まれるレコードを取得
        ->orderBy('date')
        ->get() //結果を配列として全部取得
        ->map(function ($attendance) {
            // 加工して勤務時間など表示用に追加
            $attendance->start_time = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : ''; //出勤時間（clock_in）を "09:00" などに整形
            $attendance->end_time = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : ''; //退勤時間（clock_out）を "18:00" などに整形

            $totalBreak = $attendance->breaks->sum(function ($break) {
                return \Carbon\Carbon::parse($break->break_end)->diffInMinutes($break->break_start);
            });

            $attendance->break_time = $totalBreak ? sprintf('%d:%02d', floor($totalBreak / 60), $totalBreak % 60) : '';
            $attendance->total_time = ($attendance->clock_in && $attendance->clock_out)
                ? sprintf('%d:%02d',
                    floor((\Carbon\Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) / 60),
                    (\Carbon\Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) % 60
                )
                : '';
            return $attendance;
        });

    return view('admin.attendance.staff', [
        'user' => $user, //$user（ユーザー情報）対象スタッフの情報
        'attendances' => $attendances, //今月の勤怠データ
        'currentMonth' => $month, //currentMonth：表示中の年月
        'prevMonth' => $month->copy()->subMonth()->format('Y-m'), //prevMonth：前月（ボタン用）
        'nextMonth' => $month->copy()->addMonth()->format('Y-m'), //nextMonth：翌月（ボタン用）
    ]);
}
}