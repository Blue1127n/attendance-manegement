<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\AttendanceRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        //redirect()->route(...)処理後にリダイレクトadmin.attendance.detail という名前のルート（勤怠詳細画面）へ移動する処理
        //['id' => $attendance->id] によって、該当の勤怠IDの詳細ページに戻る
        //->with('corrected', true)セッションに「corrected = true」というフラグを一時的に保存
        //この値は、リダイレクト先の Blade テンプレートで session('corrected') として取得できる
        //これによって「修正済みかどうか」を画面側で判断できる
        //コードの意味は「修正が完了したので、該当の勤怠詳細画面に戻って、さらに 'corrected' => true という情報も渡すよ！」
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

    public function exportStaffAttendanceCsv(Request $request, $id)
    {
    $user = User::findOrFail($id); //users テーブルから、指定された $id に一致するユーザー（スタッフ）を1人取得します
    $month = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now(); //フォームなどから「月」が送られてきたらその月、なければ今月を対象にします
    $startOfMonth = $month->copy()->startOfMonth()->toDateString(); //$month の月の最初の日と最後の日を取得します（例：2025年3月なら 03-01 と 03-31）
    $endOfMonth = $month->copy()->endOfMonth()->toDateString();

    $attendances = Attendance::with('breaks') //この勤怠に対応する休憩時間も一緒に取得
        ->where('user_id', $id) //そのスタッフの勤怠だけ
        ->whereBetween('date', [$startOfMonth, $endOfMonth]) //whereBetween('date', [...])：その月の日付の範囲だけ
        ->orderBy('date') //日付順に並べる
        ->get(); //全部取り出す

    //ここからCSVを1行ずつ書き出していく処理を始める
    return new StreamedResponse(function () use ($attendances, $user) {
        $handle = fopen('php://output', 'w'); //CSVファイルを画面から直接出力するための準備
        //fopen('php://output', 'w') は、通常のファイルではなく ブラウザに直接出力する特別な「出力ストリーム」 を開きます
        //$handle はその出力の「ハンドル（取っ手）」のようなもので、fputcsv() 関数などでこの $handle を使って出力します
        //例えるなら$handle は「プリンタの取っ手」。fputcsv() で「印刷する内容（CSVの行）」を渡して印刷（＝画面に出力）します

        // ヘッダー（Shift-JISに変換）
        $headers = ['日付', '出勤', '退勤', '休憩時間', '合計時間']; //これはCSVの1行目のカラム名
        //日本語が文字化けしないようにUTF-8からShift-JISに変換して書き出す
        fputcsv($handle, array_map(function ($value) {
            return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
        }, $headers));

        //CSVに取得した勤怠データを1行ずつ追加
        foreach ($attendances as $attendance) {
            $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

            //1日の休憩時間（合計）を「分単位」で計算する
            //$attendance->breaks：その日の休憩時間の一覧  sum(...)：全ての休憩時間の「合計分数」を計算する
            //Carbon::parse(...)->diffInMinutes(...)：終了時刻と開始時刻の差を「分」で計算
            $totalBreak = $attendance->breaks->sum(function ($break) {
                return Carbon::parse($break->break_end)->diffInMinutes($break->break_start);
            });

            //休憩の合計時間（分単位）を 「〇時間:〇〇分」形式に変換
            //floor($totalBreak / 60)：時間部分（例：75分 → 1時間）$totalBreak % 60：分部分（例：75分 → 15分）
            //sprintf()：整形して表示（例："1:15"）もし $totalBreak が0（休憩なし）だったら ''（空文字）になる
            $breakTime = $totalBreak ? sprintf('%d:%02d', floor($totalBreak / 60), $totalBreak % 60) : '';
            //出勤～退勤の合計勤務時間（休憩を引いた分） を 〇時間:〇〇分 の形式にするためのコード
            //1.clock_in ～ clock_out の時間差を分で計算 2.そこから $totalBreak（休憩時間）を引く
            //3.sprintf() で 時間:分 に整形する 4.clock_in or clock_out がなければ空文字を返す
            $totalTime = ($attendance->clock_in && $attendance->clock_out)
                ? sprintf('%d:%02d',
                    floor((Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) / 60),
                    (Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) % 60
                ) : '';

            // 各データも Shift-JIS に1行ずつ変換して出力
            $row = [
                Carbon::parse($attendance->date)->format('Y-m-d'),
                $clockIn,
                $clockOut,
                $breakTime,
                $totalTime
            ];

            ////日本語が文字化けしないようにUTF-8からShift-JISに変換して書き出す
            fputcsv($handle, array_map(function ($value) {
                return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
            }, $row));
        }

        //ファイルの設定（名前や形式）
        fclose($handle);
    }, 200, [
        "Content-Type" => "text/csv; charset=Shift_JIS", //Content-Type：ファイル形式はCSVですよというお知らせ
        //Content-Disposition：ダウンロード時のファイル名（ユーザー名付き）
        "Content-Disposition" => "attachment; filename=attendance_{$user->last_name}_{$user->first_name}.csv",
    ]);
    }

    public function requestList()
    {

    // 「承認待ち」の申請一覧（status が '承認待ち'）
    $pending = AttendanceRequest::with(['user', 'attendance'])
        ->where('status', '承認待ち')
        ->orderBy('updated_at', 'desc')
        ->get();

    // 「承認済み」の申請一覧（status が '承認済み'）
    $approved = AttendanceRequest::with(['user', 'attendance'])
        ->where('status', '承認済み')
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('admin.request.list', compact('pending', 'approved'));
    }

    public function approveRequest($id)
{
    $attendanceRequest = AttendanceRequest::with(['user', 'attendance', 'attendance_request_breaks'])->findOrFail($id);

    return view('admin.request.approve', compact('attendanceRequest'));
}

public function updateApprove(Request $request, $id)
{
    DB::beginTransaction();

    try {
        $attRequest = AttendanceRequest::with(['attendance', 'attendance_request_breaks'])->findOrFail($id);
        //修正申請の取得 findOrFail($id) 存在しないIDなら404で落ちる
        //リレーション付きで読み込みwith(['attendance', 'attendance_request_breaks'])

        // attendances テーブルを更新
        $attendance = $attRequest->attendance;
        if (!$attendance) {
            throw new \Exception('対応する勤怠データが見つかりません');
        }
        $attendance->clock_in = $attRequest->requested_clock_in;
        $attendance->clock_out = $attRequest->requested_clock_out;
        $attendance->remarks = $attRequest->remarks;
        $attendance->save();

        // breaks テーブルを全削除後、再登録（attendance_request_breaks を使用）
        $attendance->breaks()->delete();
        foreach ($attRequest->attendance_request_breaks as $break) {
            $attendance->breaks()->create([
                'break_start' => $break->requested_break_start,
                'break_end' => $break->requested_break_end,
            ]);
        }

        // 修正申請のステータス更新
        $attRequest->status = '承認済み';
        $attRequest->save();

        DB::commit();

        return redirect()->route('admin.request.approve.show', $attRequest->id)->with('corrected', true);
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'エラーが発生しました']);
    }
}
}


