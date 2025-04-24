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
    $month = $request->input('month') //これはURLやフォームなどから **month という名前の入力（クエリパラメータ）**を受け取る処理 例：/attendance/list?month=2025-04 とアクセスされた場合、2025-04 が取得されます
        ? Carbon::parse($request->input('month')) //三項演算子 ? :   これは「if の短縮版」のようなものです
        : Carbon::now();
        //if ($request->input('month')) {
        //$month = Carbon::parse($request->input('month'));
        //} else {
        //$month = Carbon::now();
        //}と同じ意味です
        //Carbon::parse(...)これは「文字列の日付」をCarbonオブジェクト（日付型）に変換する関数 （2025-04-01 になります）
        //Carbon::now()  now() は現在時刻を表す Carbon オブジェクト  URLに month がついていないときは「今月」を自動的に表示する

    //$month->copy() が入っている理由は **「元の $month を壊さないようにするため」**です
    //$month = Carbon::parse('2025-04');  // 2025-04-01（見た目はそうでも、内部は「2025-04」）
    //$startOfMonth = $month->startOfMonth();  // ここで $month が「4月1日」に変化！
    //$endOfMonth = $month->endOfMonth();      // この時点では $month はすでに「4月1日」になってるからOKそうに見えるけど…
    // このあとで「前月・次月」を使う場合に困る！
    //$month->copy()->subMonth()->format('Y-m');  // ←すでに変わってると意図通り動かなくなる可能性あり
    //copy() を使うメリット $month 本体を 変更せずに startOfMonth や endOfMonth を使える 安全・予測どおりに動く 複数の場所で $month を使いたいときに安心
    //$month->startOfMonth()  $month 自体が「その月の1日」に変更される
    //$month->copy()->startOfMonth()  $month はそのまま残して、「コピーした月」を1日に変更して使う
    $startOfMonth = $month->copy()->startOfMonth()->toDateString(); //$month の月の 最初の日を取得
    $endOfMonth = $month->copy()->endOfMonth()->toDateString(); //$month の月の 最後の日を取得

    $attendances = Attendance::with('breaks') //指定された月の間にある 自分の勤怠情報を breaks 関係も一緒に取得
        ->where('user_id', $user->id)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->orderBy('date', 'asc') //date カラム（＝出勤日）を昇順（asc: ascending）で並べる  ※もし desc（降順）にすると、逆順になります
        ->get() //勤怠データを全部取得
        ->map(function ($attendance) { //コレクション（配列のようなもの）を1つずつ処理するためのメソッドです
            //get() で取得した勤怠データ（複数件）に対して1件ずつ $attendance に入れて出勤時間や合計時間などを整えて加工された新しい配列として返す
            //出勤・退勤時間を整形 休憩合計を算出 合計勤務時間を計算 つまり「取得した勤怠データを、見やすく整えてから画面に渡す」ために使ってる

            // 出勤時間と退勤時間（フォーマット整える）
            $attendance->start_time = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $attendance->end_time = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

            // 休憩時間の合計（分単位）複数の休憩がある場合も全部の「休憩時間（分）」を足し合わせる
            $totalBreakMinutes = $attendance->breaks->reduce(function ($carry, $break) { //その日の すべての休憩時間の合計（分単位） を計算する処理
                //reduce() とは？Laravelの Collection にある関数で、配列の 合計・合成処理などをする時によく使います
                //reduce(コールバック関数, 初期値)コールバック関数は：function ($carry, $break) $carry：今までの合計（前回の結果） $break：現在の休憩データ（1件）
                //$attendance->breaks：この日の「全休憩レコード（複数）」を取得 それらを1つずつ $break に入れて合計していく $carry は累積合計、最初は 0
                if ($break->break_start && $break->break_end) { //「開始時間と終了時間が両方ある休憩だけ」を対象にする 入力途中など、片方が空ならスキップする
                    $start = Carbon::parse($break->break_start); //文字列型の時刻（例：'15:00'）を Carbon インスタンスに変換
                    $end = Carbon::parse($break->break_end);
                    return $carry + $end->diffInMinutes($start); //diffInMinutes() で「その1回の休憩の分数」を取得 　それを$carry に加算して返す（これが次回の $carry に入る）
                }
                return $carry; //もし片方が null なら、その $break はスキップして現在の $carry をそのまま返す
            }, 0);

            // 表示形式に整形
            //$totalBreakMinutes = その日の全休憩の合計（分）
            $attendance->break_time = $totalBreakMinutes
                ? sprintf('%d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60) //「◯時間◯分」形式（例：1:15）に整形して、画面に表示されるようにな
                : '';

            // 合計勤務時間（出勤～退勤 - 休憩）
            //退勤してたら、出勤〜退勤の時間から休憩時間を引いて表示形式に整える
            //例：出勤8:00、退勤17:00、休憩1時間 → 合計勤務時間 8:00
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

    // Bladeに渡すデータ
    return view('attendance.list', [
        'attendances' => $attendances, //attendances：整形済の勤怠データ一覧
        'currentMonth' => $month, //currentMonth：現在の表示月
        'prevMonth' => $month->copy()->subMonth()->format('Y-m'), //前月の年月（例：2024-10） prevMonth：前月（ナビゲーション用）
        'nextMonth' => $month->copy()->addMonth()->format('Y-m'), //翌月の年月（例：2024-12） nextMonth：翌月（ナビゲーション用）
    ]);
    }

    public function correctionRequest(AttendanceCorrectionRequest $request, $id)
    {
    //Attendanceモデルから、idが一致する勤怠レコードを1件取得 with('breaks') を使って、その勤怠に紐づく休憩時間も一緒に取得
    //user_id が ログインユーザー自身 のものであることを条件にしている findOrFail($id) → $id に一致する勤怠がなければ エラー（404）を出す
    $attendance = Attendance::with('breaks')->where('user_id', Auth::id())->findOrFail($id);

    // 修正申請（親）を作成
    $attendanceRequest = AttendanceRequest::create([
        'user_id' => Auth::id(), //Auth::id() の意味 現在ログインしているユーザーのIDを自動で取得してセットします これは「この修正申請は誰が出したか？」を記録するため
        'attendance_id' => $attendance->id,
        'requested_clock_in' => $request->clock_in,
        'requested_clock_out' => $request->clock_out,
        'remarks' => trim($request->remarks), //ユーザーが入力した備考（remarks）の前後にある空白を削除します
        'status' => '承認待ち',
    ]);

    // 修正申請の休憩（子）を複数登録  ユーザーが送信した「休憩時間の修正データ（複数）」を、1件ずつデータベースに保存
    foreach ($request->breaks as $break) { //$request->breaks は、休憩の配列（start, end） になってる
        if (!empty($break['start']) && !empty($break['end'])) { //empty() は「値が空じゃないか？」を確認する関数
            $attendanceRequest->breaks()->create([ //両方入力されていたら breaks() リレーション経由で保存
                'requested_break_start' => $break['start'], //つまり：「開始と終了が両方ある休憩だけを保存」ってこと
                'requested_break_end' => $break['end'],
            ]);
        }
    }

    return redirect()->route('user.attendance.detail', ['id' => $attendance->id]); //修正申請が完了したあとに、該当の勤怠詳細ページへリダイレクト
    } //パラメータ id を渡しているので、URLとしては例えば：/attendances/5/detailみたいなページに飛ぶことになります

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
    //「ユーザー情報」「休憩時間」も with() でまとめて勤怠データ（attendances テーブル）を $id から1件取得
    $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

    // 修正申請を取得
    //今表示している勤怠に対する申請でログインユーザーが出した申請で承認待ち か 承認済み のものを最新の1件だけ取得
    $request = AttendanceRequest::with('breaks')
        ->where('attendance_id', $id)
        ->where('user_id', Auth::id())
        ->whereIn('status', ['承認待ち', '承認済み'])
        ->latest()
        ->first();

    //修正申請が存在するなら、勤怠データに上書き表示
    if ($request) {
        $attendance->clock_in = $request->requested_clock_in;
        $attendance->clock_out = $request->requested_clock_out;
        $attendance->remarks = $request->remarks;

        // 修正申請に紐づく休憩をすべて上書き表示用に
        //元の $attendance->breaks に 置き換えて表示用にする
        //map() は1件ずつ整えて (object) にしている（配列じゃなくてオブジェクトに）
        $attendance->breaks = $request->breaks->map(function ($break) {
            return (object)[
                'break_start' => $break->requested_break_start,
                'break_end' => $break->requested_break_end,
            ];
        });
    }
    //勤怠詳細ページに $attendance（上書き後も含む）と $request（申請があれば）を渡す
    return view('attendance.detail', compact('attendance', 'request'));
    }
}
