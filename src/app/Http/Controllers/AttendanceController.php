<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function index()
    {
    $user = Auth::user(); //現在ログイン中のユーザー情報を取得
    $today = Carbon::today()->toDateString(); //今日の日付を「2025-04-20」形式で取得

    //attendanceテーブルから「ログインユーザーの今日の勤怠情報」を1件だけ取得（なければnull）
    $attendance = Attendance::where('user_id', $user->id)//Attendance::whereはattendanceテーブルから探すuser_id が $user->id（ログイン中のユーザーのID）と一致する行を検索する
                            ->where('date', $today)//date カラムが $today（今日の日付）と一致する行
                            ->first();

    //もし勤怠データが見つからなければ、「勤務外」としてダミーのオブジェクトを作る
    if (!$attendance) {
        $attendance = (object) [
            'status' => '勤務外'
    ];
    }
    //attendance.indexビューに、$attendance変数を渡して表示
    return view('attendance.index', compact('attendance'));
    }

    public function clockIn()
    {
    $user = Auth::user();
    //万が一ログインしていなければ、ログイン画面にリダイレクトし「ログインしてください」というエラーメッセージを表示
    //これは二重チェックなので基本的には削除してOKです
    if (!$user) {
        return redirect()->route('login')->with('error', 'ログインしてください');
    }

    $today = Carbon::today()->toDateString();

    //今日すでに勤怠があるかDB確認
    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();
    //もしすでに出勤していたら、勤怠画面に戻して「既に出勤済みです」と表示   &&（かつ）
    if ($attendance && $attendance->clock_in) {
        return redirect()->route('user.attendance')->with('error', '既に出勤済みです');
    }

    //まだ出勤してなければ出勤記録をattendances テーブルに新規作成保存
    Attendance::create([
        'user_id' => $user->id,
        'date' => $today,
        'clock_in' => now(),
        'status' => '出勤中',
    ]);
    //最後に勤怠画面へリダイレクト
    return redirect()->route('user.attendance');
    }

    public function startBreak()
    {
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();
    //$attendance が存在しない !$attendance 勤務外（今日まだ出勤していない） ||（または）
    //$attendance->status !== '出勤中' 出勤中以外 勤怠画面に戻して「出勤していないため休憩できません」と表示
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
    //$attendance->breaks()これは Eloquentリレーション（Attendance モデル → Break モデル）を通して「この勤怠の休憩一覧」を取得する意味
    //->whereNull('break_end')break_end が null（未入力） の休憩を絞り込む つまり「まだ終わってない休憩」
    //->latest() created_at の 降順（新しい順）で並べる Laravelの latest() はデフォルトで created_at を使います
    //->first() 最初の1件（＝一番新しいやつ）を取得

    //「まだ break_end が入っていない休憩（＝休憩中の記録）があるなら、その終了時刻に今の時刻を入れる」という意味です
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

    public function correctionRequest(AttendanceCorrectionRequest $request, $id)
    {
    $attendance = Attendance::with('breaks')->where('user_id', Auth::id())->findOrFail($id);

    // 修正申請（親）を作成
    $attendanceRequest = AttendanceRequest::create([
        'user_id' => Auth::id(),
        'attendance_id' => $attendance->id,
        'requested_clock_in' => $request->clock_in,
        'requested_clock_out' => $request->clock_out,
        'remarks' => trim($request->remarks),
        'status' => '承認待ち',
    ]);

    // 修正申請の休憩（子）を複数登録
    foreach ($request->breaks as $break) {
        if (!empty($break['start']) && !empty($break['end'])) {
            $attendanceRequest->breaks()->create([
                'requested_break_start' => $break['start'],
                'requested_break_end' => $break['end'],
            ]);
        }
    }

    return redirect()->route('user.attendance.detail', ['id' => $attendance->id]);
    }

    public function requestList()
    {
    $user = Auth::user();

    $pending = AttendanceRequest::with(['user', 'attendance'])
        ->where('user_id', $user->id)
        ->where('status', '承認待ち')
        ->orderBy('updated_at', 'desc')
        ->get();

    $approved = AttendanceRequest::with(['user', 'attendance'])
        ->where('user_id', $user->id)
        ->where('status', '承認済み')
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('attendance.request.list', compact('pending', 'approved'));
    }

    public function show($id)
    {
    $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

    // 修正申請を取得
    $request = AttendanceRequest::with('breaks')
        ->where('attendance_id', $id)
        ->where('user_id', Auth::id())
        ->whereIn('status', ['承認待ち', '承認済み'])
        ->latest()
        ->first();

    if ($request) {
        $attendance->clock_in = $request->requested_clock_in;
        $attendance->clock_out = $request->requested_clock_out;
        $attendance->remarks = $request->remarks;

        // 修正申請に紐づく休憩をすべて上書き表示用に
        $attendance->breaks = $request->breaks->map(function ($break) {
            return (object)[
                'break_start' => $break->requested_break_start,
                'break_end' => $break->requested_break_end,
            ];
        });
    }

    return view('attendance.detail', compact('attendance', 'request'));
    }
}
