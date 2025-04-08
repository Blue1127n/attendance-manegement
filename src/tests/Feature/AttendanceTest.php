<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function createUser()
    {
        //ダミーのユーザーのデータを作成する
        return User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    //出勤ボタンが正しく機能する
    public function testClockInWorks()
    {

        $user = $this->createUser(); //ユーザーデータを作成
        $this->actingAs($user); //ログイン状態を作成

        $response = $this->get('/attendance'); // 勤務外（出勤前）画面に出勤ボタンが表示されているか
        $response->assertSee('出勤');

        $this->post(route('user.attendance.clockIn')); // 出勤処理を実行

        $response = $this->get('/attendance'); // 再度画面アクセス、ステータスが「出勤中」に変わっているか
        $response->assertSee('出勤中');
    }

    //出勤は一日一回のみできる
    public function testClockInOnlyOncePerDay()
    {

        $user = $this->createUser(); //ユーザーデータを作成
        $this->actingAs($user);

        $this->post(route('user.attendance.clockIn')); // 1回目の出勤処理

        // ステータスを退勤済に変更（simulate 一日終わった状態）
        $attendance = Attendance::where('user_id', $user->id)->first();
        $attendance->update([
            'status' => '退勤済',
            'clock_out' => now(),
        ]);

        // 再度アクセス、出勤ボタンが表示されないことを確認
        $response = $this->get('/attendance');
        //出勤 だけじゃなく、ボタンの HTML そのものをチェックする
        //$response->assertDontSee(...)は「画面にこの文字列が表示されていないことを確認する」を意味する
        //Bladeの中に書かれている「出勤ボタン」のHTMLタグそのものこの HTML ボタンが 画面に出ていないことを確認したいという意図
        //第二引数の falseはHTMLとして正確に一致するかを判断するためのオプション
        //true（デフォルト）だと：テキストだけ見てしまう（タグ無視）
        //false にすると：HTMLタグを含むそのままの文字列と比較
        //false を指定することで、「完全に一致するこのボタンが出てないこと」をチェックできる
        $response->assertDontSee('<button type="submit" class="btn btn-primary">出勤</button>', false);
    }

    //出勤時刻が管理画面で確認できる
    public function testClockInTimeShownInAdmin()
    {

        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.clockIn'));

        // DBに記録されているか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        // 管理画面に出勤時間が表示されているか（例：10:00）
        $clockIn = Attendance::where('user_id', $user->id)->first()->clock_in;
        //$formatted は 「整形された（format済みの）出勤時刻」 を保存する変数
        //'2025-04-07 09:15:00' みたいな日付＋時間の文字列が$formatted には入る
        $formatted = Carbon::parse($clockIn)->format('H:i');

        $response = $this->get('/admin/attendance/list'); // 管理画面URLに合わせて
        $response->assertSee($formatted); //bladeで表示されてるか確認
    }
}
