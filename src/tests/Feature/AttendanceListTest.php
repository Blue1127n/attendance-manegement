<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function createUser()
    {
        return User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendances($user)
    {
        // 今日と昨日の勤怠データ作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::yesterday(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);
    }

    //自分が行った勤怠情報が全て表示されている
    public function testMyRecords()
    {
        $user = $this->createUser();
        $this->createAttendances($user);
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200); //「レスポンスのステータスコードが 200 OK であることを確認する」ためのテストコード
        //200は「ページが正常に表示された」ことを意味 たとえばURLが間違っていたりログインしていないと、403や404になったりするので、それを防ぐチェック
        $response->assertSee(Carbon::today()->format('m/d'));
        $response->assertSee(Carbon::yesterday()->format('m/d'));
    }

    //勤怠一覧画面に遷移した際に現在の月が表示される
    //指定がなければ今月（当月）を使うので、テストでは明示的にmonthパラメータを付けなくてもOK
    public function testCurrentMonth()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    //「前月」を押下した時に表示月の前月の情報が表示される
    public function testPrevMonth()
    {
        $user = $this->createUser();

        //今から1か月前の日時を取得
        $lastMonth = Carbon::now()->subMonth(); //$lastMonthは「先月の今日」のこと Carbon::now()：今の日時 ->subMonth()：そこから1か月前を取得
        Attendance::create([
            'user_id' => $user->id,
            'date' => $lastMonth->copy()->startOfMonth()->toDateString(), //「前月の1日の日付（文字列）」を取得して、'date' カラムに入れている
            //->copy() は元の $lastMonth を壊さずコピーを作る ->startOfMonth() は「その月の1日」にする ->toDateString() は Y-m-d（例：2025-03-01）形式に変換
            //「前月の1日の日付」を使って勤怠データを作成しているという意味
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user);
        //「勤怠一覧ページに前月のパラメータを付けてアクセス」しているコード URLで ?month=2025-03 のようにパラメータを渡す
        //format('Y-m') で「年-月」の形式（例：2025-03）にする これにより、前月の勤怠一覧が表示されるかを確認する
        $response = $this->get('/attendance/list?month=' . $lastMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));
    }

    //「翌月」を押下した時に表示月の前月の情報が表示される
    public function testNextMonth()
    {
        $user = $this->createUser();

        //「来月の日付」を取得するコード 今が 4月9日 なら 5月9日 になる
        $nextMonth = Carbon::now()->addMonth(); //$nextMonth = 「来月（next month）」 Carbon::now()：今の日時 addMonth()：今から1か月後に進める関数
        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->startOfMonth()->toDateString(), //来月の1日の日付（例：2025-05-01）を取得して勤怠データに使う
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/list?month=' . $nextMonth->format('Y-m')); // /attendance/list ページに「?month=2025-05」のような形でアクセス

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    //「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function testDetailPage()
    {
        $user = $this->createUser();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(route('user.attendance.detail', $attendance->id));
    }
}
