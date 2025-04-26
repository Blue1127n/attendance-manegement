<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceCorrectionTest extends TestCase
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

    public function testClockTimesShowsError()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'remarks' => 'テストのため不正な時間',
            'breaks' => [],
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $response->assertSessionHasErrors(['clock_in']);
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('clock_in'));
    }

    public function testBreakStartShowsError()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テストのため不正な時間',
            'breaks' => [
                ['start' => '19:00', 'end' => '20:00']
            ]
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $response->assertSessionHasErrors(['breaks.0.start']);
        $this->assertEquals('休憩時間が勤務時間外です', session('errors')->first('breaks.0.start'));
    }

    public function testBreakEndShowsError()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テストのため不正な時間',
            'breaks' => [
                ['start' => '17:00', 'end' => '19:00']
            ]
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $response->assertSessionHasErrors(['breaks.0.start']);
        $this->assertEquals('休憩時間が勤務時間外です', session('errors')->first('breaks.0.start'));
    }

    public function testRemarkShowsError()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '19:00',
            'remarks' => '',
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $response->assertSessionHasErrors(['remarks']);
        $this->assertEquals('備考を記入してください', session('errors')->first('remarks'));
    }

    public function testCorrectionSent()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '19:00',
            'remarks' => '退勤時間の修正',
            'breaks' => [],
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $response->assertStatus(302);

        $this->assertDatabaseHas('attendance_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_out' => '19:00:00',
            'remarks' => '退勤時間の修正',
            'status' => '承認待ち',
    ]);
    }

    public function testPendingShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '19:00',
            'remarks' => '退勤時間の修正',
            'breaks' => [],
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('退勤時間の修正');
        $response->assertSee('承認待ち');
    }

    public function testApprovedShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '19:00',
            'remarks' => '退勤時間の修正',
            'breaks' => [],
        ];

        $response = $this->post("/attendance/{$attendance->id}/correction", $postData);

        $request = \App\Models\AttendanceRequest::first();

        $request->update(['status' => '承認済み']);

        $response = $this->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('退勤時間の修正');
    }

    public function testRequestDetailShown()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '19:00',
            'remarks' => '退勤時間の修正',
            'breaks' => [],
        ];

        $this->post("/attendance/{$attendance->id}/correction", $postData);

        $request = \App\Models\AttendanceRequest::first();
        $response = $this->get("/admin/stamp_correction_request/approve/{$request->id}");

        $response->assertStatus(200);
        $response->assertSee('退勤時間の修正');
    }
}

