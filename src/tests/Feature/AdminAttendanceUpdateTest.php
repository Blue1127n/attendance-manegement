<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestBreak;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminAttendanceUpdateTest extends TestCase
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

    //承認待ちの修正申請が全て表示されている
    public function testPendingRequestsShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // まず勤怠データを作成（attendance_idが必要）
        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        //修正申請データを1件だけ作成
        //AttendanceRequest テーブルに「承認待ち」のデータを入れておく必要がある
        AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id, //外部キー正しく指定
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認待ち',
        ]);

        $this->actingAs($user1);

        //修正申請一覧ページへアクセス
        $response = $this->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('承認待ち');
    }

    //承認済みの修正申請が全て表示されている
    public function testApprovedRequestsShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // まず勤怠データを作成（attendance_idが必要）
        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        //修正申請データを1件だけ作成
        //AttendanceRequest テーブルに「承認待ち」のデータを入れておく必要がある
        AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id, //外部キー正しく指定
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認済み',
        ]);

        $this->actingAs($user1);

        //修正申請一覧ページへアクセス
        $response = $this->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('承認済み');
    }

    //修正申請の詳細内容が正しく表示されている
    public function testRequestDetailShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // まず勤怠データを作成（attendance_idが必要）
        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $request = AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id, //外部キー正しく指定
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認済み',
        ]);

        $this->actingAs($user1);

        //申請ID（$request->id）でアクセスする必要がある
        //Route::get('/stamp_correction_request/approve/{attendance_correct_request}',
        // [AdminController::class, 'approveRequest'])->name('admin.request.approve.show');
        //つまり {attendance_correct_request} の部分には「申請のID」を使わないといけない
        $response = $this->get('/admin/stamp_correction_request/approve/' . $request->id);

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('退勤修正申請');
    }

    //修正申請の承認処理が正しく行われる
    public function testApproveRequestWorks()
    {
        [$user1, $user2, $user3] = $this->createUser();

        // まず勤怠データを作成（attendance_idが必要）
        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        //修正申請
        $request = AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認待ち',
        ]);

        //休憩申請（←これが必要！）
        \App\Models\AttendanceRequestBreak::create([
            'attendance_request_id' => $request->id,
            'requested_break_start' => '12:00:00',
            'requested_break_end' => '13:00:00',
        ]);

        $this->actingAs($user1);

        //POSTで承認処理を実行
        $response = $this->post('/admin/stamp_correction_request/approve/' . $request->id);

        $response->assertRedirect(); // 正常にリダイレクトするか

        //勤怠が更新されたか
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '19:00:00',
        ]);

        //修正申請のステータスが更新されたか
        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request->id,
            'status' => '承認済み',
        ]);

        // 休憩テーブルに新しいレコードが作成されたか
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
       ]);
    }
}
