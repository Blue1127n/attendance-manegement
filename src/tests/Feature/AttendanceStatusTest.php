<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createUser()
    {
        //return は 関数の「戻り値」を返すという意味
        //この場合「作成したユーザーオブジェクトを戻す（使えるようにする）」という意味
        return User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    //勤務外の場合勤怠ステータスが正しく表示される
    public function testStatusIdle()
    {
        $user = $this->createUser(); //ユーザーデータを作成
        $this->actingAs($user); //ログイン状態を作成

        $response = $this->get('/attendance'); //ページへアクセスして表示確認 勤務外（出勤前）画面
        $response->assertSee('勤務外'); //勤務外になっているかを確認
    }

    //出勤中の場合勤怠ステータスが正しく表示される
    public function testStatusWorking()
    {
        $user = $this->createUser();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]); //出勤データを作成

        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('出勤中'); //出勤中になっているかを確認
    }

    //休憩中の場合勤怠ステータスが正しく表示される
    public function testStatusOnBreak()
    {
        $user = $this->createUser();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now(),
            'status' => '休憩中',
        ]); //休憩データを作成

        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    //退勤済の場合勤怠ステータスが正しく表示される
    public function testStatusFinished()
    {
        $user = $this->createUser();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'status' => '退勤済',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }
}
