<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function createUser()
    {
        $user1 = User::forceCreate([
            'last_name' => '田中',
            'first_name' => '次郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $user2 = User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'user@example.com',
            'password' => Hash::make('password234'),
            'email_verified_at' => now(),
        ]);

        return [$user1, $user2]; // ← 2人を配列で返す！
    }

    private function createAttendancesFor($user, $clockIn, $clockOut, $date = null)
    {
        Attendance::create([
            'user_id' => $user->id,
            'date' => $date ?? Carbon::today(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => '退勤済',
        ]);
    }

    //その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function testTodayAttendances()
    {
        [$user1, $user2] = $this->createUser(); //2人分作成して代入
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00');
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00');

        $this->actingAs($user1); //ユーザー1を「管理者的に」ログインさせて確認

        $response = $this->get('admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee('09:00');
        $response->assertSee('10:00');
    }

    //遷移した際に現在の日付が表示される
    public function testTodayDateShown()
    {
        [$user1, $user2] = $this->createUser(); //2人分作成して代入
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00');
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00');

        $this->actingAs($user1); //ユーザー1を「管理者的に」ログインさせて確認

        $response = $this->get('admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee(Carbon::today()->format('Y年n月j日'));
    }

    //「前日」を押下した時に前の日の勤怠情報が表示される
    public function testSeeYesterdayRecords()
    {
        [$user1, $user2] = $this->createUser(); //2人分作成して代入
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00', Carbon::yesterday());
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00', Carbon::yesterday());

        $this->actingAs($user1); //ユーザー1を「管理者的に」ログインさせて確認

        //「前日」ボタン押下する　day=YYYY-MM-DD を渡す
        //これは文字列です ?day=：クエリパラメータの開始 → dayという名前で日付を渡す
        //->toDateString()：それを Y-m-d（例：2025-04-09）の文字列に変換 返すのは "2025-04-09" のような文字列です
        //PHPでは文字列の連結に . を使う admin/attendance/list?day=2025-04-09という1つのURL文字列になる
        $response = $this->get('admin/attendance/list?day=' . Carbon::yesterday()->toDateString());

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee(Carbon::yesterday()->format('Y年n月j日'));
    }

    //「翌日」を押下した時に次の日の勤怠情報が表示される
    public function testSeeTomorrowRecords()
    {
        [$user1, $user2] = $this->createUser();
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00', Carbon::tomorrow());
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00', Carbon::tomorrow());

        $this->actingAs($user1);

        $response = $this->get('admin/attendance/list?day=' . Carbon::tomorrow()->toDateString());

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee(Carbon::tomorrow()->format('Y年n月j日'));
    }
}
