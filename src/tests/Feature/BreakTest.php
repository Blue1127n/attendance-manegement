<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkingUser()
    {
        $user = User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(1), //現在の時刻から1時間引く という意味 現在の時刻から1時間前を取得
            //つまり「1時間前に出勤したことにする」っていうデータを入れてるイメージ
            'status' => '出勤中',
        ]);

        return $user;
    }

    //休憩入ボタンが正しく機能する
    public function testBreakStart()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $this->post(route('user.attendance.startBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    //休憩は一日に何回でもできる
    public function testBreakTwice()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.startBreak'));
        $this->post(route('user.attendance.endBreak'));
        $this->post(route('user.attendance.startBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    //休憩戻ボタンが正しく機能する
    public function testBreakEnd()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.startBreak'));
        $this->post(route('user.attendance.endBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    //休憩戻は一日に何回でもできる
    public function testBreakEndTwice()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        // 1回目
        $this->post(route('user.attendance.startBreak'));
        $this->post(route('user.attendance.endBreak'));

        // 2回目
        $this->post(route('user.attendance.startBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    //休憩時刻が勤怠一覧画面で確認できる
    public function testBreakInList()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        // 休憩入〜戻を行う
        $this->post(route('user.attendance.startBreak'));
        sleep(1); // 1秒だけ処理をストップ（待機） する命令 1秒だけプログラムを止める
        //「すぐに2つのデータを登録したら、時刻が同じになって区別できない」ってときに
        //sleep(1) を間に入れると、1秒ズレるから「時間に差が出る」ようにできる
        //addMinutes(1) で「1分後」subMinutes(10) で「10分前」っていうふうに変えられる
        $this->post(route('user.attendance.endBreak'));

        $response = $this->get('/attendance/list');

        // "0:00" ではない表示を期待（例："0:01" など）
        $response->assertSee(':');
    }
}
