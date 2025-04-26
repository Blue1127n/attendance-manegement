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

        return [$user1, $user2, $user3];
    }

    public function testPendingRequestsShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認待ち',
        ]);

        $this->actingAs($user1);

        $response = $this->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('承認待ち');
    }

    public function testApprovedRequestsShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認済み',
        ]);

        $this->actingAs($user1);

        $response = $this->get('/admin/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('承認済み');
    }

    public function testRequestDetailShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $request = AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤修正申請',
            'status' => '承認済み',
        ]);

        $this->actingAs($user1);

        $response = $this->get('/admin/stamp_correction_request/approve/' . $request->id);

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('退勤修正申請');
    }

    public function testApproveRequestWorks()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $request = AttendanceRequest::create([
            'user_id' => $user3->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '09:00:00',
            'requested_clock_out' => '19:00:00',
            'remarks' => '出退勤修正申請',
            'status' => '承認待ち',
        ]);

        AttendanceRequestBreak::create([
            'attendance_request_id' => $request->id,
            'requested_break_start' => '12:00:00',
            'requested_break_end' => '13:00:00',
        ]);

        $this->actingAs($user1);

        $request->load('attendance');

        $this->assertNotNull($request->attendance);

        $response = $this->post(route('admin.request.approve.update', $request->id));

        $response->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '09:00:00',
            'clock_out' => '19:00:00',
        ]);

        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request->id,
            'status' => '承認済み',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);
    }
}
