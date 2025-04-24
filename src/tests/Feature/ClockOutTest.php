<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    private function createUser()
    {
        return User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    //退勤ボタンが正しく機能する
    public function testClockOutButton()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8), //8時間前に出勤
            'status' => '出勤中',
        ]);

        // 出勤中のときに「退勤」ボタンが表示されるか確認する
        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        // 退勤処理を実行する
        $this->post(route('user.attendance.clockOut'));

        // ステータスが「退勤済」になってるか確認
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    //退勤時刻が管理画面で確認できる
    public function testAdminShowsClockOut()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.clockIn')); // 出勤処理
        sleep(1); // 時間差をつけるため1秒だけプログラムを止める
        $this->post(route('user.attendance.clockOut')); //退勤処理

        // DBに退勤記録があるか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '退勤済',
        ]);

        $clockOut = Attendance::where('user_id', $user->id)->first()->clock_out; //管理画面に退勤時間が表示されているか
        $formatted = Carbon::parse($clockOut)->format('H:i');

        // 管理画面に退勤時刻が表示されているか確認（ルートに合わせて）
        $response = $this->get('/admin/attendance/list'); //管理画面URLに合わせて
        $response->assertSee($formatted); //bladeで表示されてるか確認
    }
}