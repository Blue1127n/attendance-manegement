<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminAttendanceStaffListTest extends TestCase
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
            'email' => 'user1@example.com',
            'password' => Hash::make('password234'),
            'email_verified_at' => now(),
        ]);

        $user3 = User::forceCreate([
            'last_name' => '鈴木',
            'first_name' => '花子',
            'email' => 'user2@example.com',
            'password' => Hash::make('password235'),
            'email_verified_at' => now(),
        ]);

        return [$user1, $user2, $user3]; // ← 3人を配列で返す！
    }

    private function createAttendancesFor($user, $month = null)
    {
        // 今月の1日〜今日まで（または月末まで）を対象に勤怠データを作成
        $month = $month ?? Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // たとえば、1日〜10日分だけ作りたい場合は以下のように制限できる
        // for ($day = $startOfMonth; $day->lte(Carbon::now()); $day->addDay())

        for ($day = $startOfMonth; $day->lte($endOfMonth); $day->addDay()) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'status' => '退勤済',
            ]);
        }
    }

    //管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function testStaffInfoShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $this->actingAs($user1);

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('admin@example.com');
        $response->assertSee('田中 一郎');
        $response->assertSee('user1@example.com');
        $response->assertSee('鈴木 花子');
        $response->assertSee('user2@example.com');
    }

    //ユーザーの勤怠情報が正しく表示される
    public function testStaffAttendancesShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $this->createAttendancesFor($user3);

        $this->actingAs($user1);

        // user3 の勤怠一覧ページにアクセス
        //Route::get('/admin/attendance/staff/{id}', [AdminController::class, 'staffAttendance'])->name('admin.staff.attendance');
        $response = $this->get('/admin/attendance/staff/' . $user3->id);

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee(Carbon::now()->format('Y/m')); //HTML表示と同じ形式に合わせる
        $response->assertSee(Carbon::today()->format('m/d'));
    }

    //「前月」を押下した時に表示月の前月の情報が表示される
    public function testSeePrevMonthShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // 前月の日付を取得
        $prevMonth = Carbon::now()->subMonth()->startOfMonth(); //subMonth()　前月

        // 勤怠データ作成（前月の日付を明示的に渡す）
        $this->createAttendancesFor($user3, $prevMonth);

        $this->actingAs($user1);

        // URLパラメータに month を渡す
        $response = $this->get('/admin/attendance/staff/' . $user3->id . '?month=' . $prevMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee($prevMonth->format('Y/m'));
    }

    //「翌月」を押下した時に表示月の前月の情報が表示される
    public function testSeeNextMonthShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // 前月の日付を取得
        $nextMonth = Carbon::now()->addMonth()->startOfMonth(); //addMonth()　翌月

        // 勤怠データ作成（前月の日付を明示的に渡す）
        $this->createAttendancesFor($user3, $nextMonth);

        $this->actingAs($user1);

        // URLパラメータに month を渡す
        $response = $this->get('/admin/attendance/staff/' . $user3->id . '?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee($nextMonth->format('Y/m'));
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function testDetailLinkWorks()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // 勤怠データ1件だけ作成（今日の日付）
        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user1);

        //「詳細」ボタンから遷移するパスにアクセス
        //Route::get('/admin/attendance/{id}', [AdminController::class, 'attendanceDetail'])->name('admin.attendance.detail');
        ///admin/attendance/{attendance_id} にアクセスする→{attendance_id} は Attendance モデルの ID→必要なのは勤怠（Attendance）データの ID
        $response = $this->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }
}

