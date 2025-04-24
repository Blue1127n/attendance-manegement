<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function createUser()
    {
        return User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendances()
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);
    }

    //勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function testNameShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // 勤怠データ作成（今日の日付で）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        // 勤怠詳細画面にアクセス（該当勤怠IDを使う）
        // /attendances/→ 勤怠のURLパスのはじまり（リソース名）
        //$attendance->id→ 今作成した勤怠レコードのIDを使って、「どの日の勤怠か」を特定してる
        // /detail→ 詳細画面を表示するためのURLパスの終わり
        $response = $this->get('/attendances/' . $attendance->id . '/detail');

        // 「田中　一郎」が表示されているか確認
        $response->assertStatus(200);
        $response->assertSee('田中');
        $response->assertSee('一郎');
    }

    //勤怠詳細画面の「日付」が選択した日付になっている
    public function testDateShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $date = Carbon::create(2025, 4, 10); //任意の日付を指定

        // 勤怠データ作成（今日の日付で）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $response = $this->get('/attendances/' . $attendance->id . '/detail');

        $response->assertStatus(200);
        $response->assertSee('2025年'); //Blade のフォーマットに合わせて書く
        $response->assertSee('4月10日');
    }

    //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function testTimesShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // 勤怠データ作成（今日の日付で）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $response = $this->get('/attendances/' . $attendance->id . '/detail');

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function testBreaksShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        // 勤怠データ作成（今日の日付で）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        // 休憩データ追加（BreakTime モデル使用）
        $attendance->breaks()->create([
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->get('/attendances/' . $attendance->id . '/detail');

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
