<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function createUser()
    {
        return User::forceCreate([
            'last_name' => '田中',
            'first_name' => '次郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);
    }

    private function createAttendances($user)
    {
        return Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);
    }

    public function testDetailShown()
    {
        $user = $this->createUser();
        $attendance = $this->createAttendances($user);
        $this->actingAs($user);

        $response = $this->get('admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('田中');
        $response->assertSee('次郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function testClockTimesShowsError()
    {
        $user = $this->createUser();
        $attendance = $this->createAttendances($user);
        $this->actingAs($user);

        $postData = [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'remarks' => 'テストのため不正な時間',
            'breaks' => [],
        ];

        $response = $this->post("/admin/attendance/{$attendance->id}/update", $postData);

        $response->assertSessionHasErrors(['clock_in']);
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', session('errors')->first('clock_in'));
    }

    public function testBreakStartShowsError()
    {
        $user = $this->createUser();
        $attendance = $this->createAttendances($user);
        $this->actingAs($user);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テストのため不正な時間',
            'breaks' => [
                ['start' => '19:00', 'end' => '20:00']
            ]
        ];

        $response = $this->post("/admin/attendance/{$attendance->id}/update", $postData);

        $response->assertSessionHasErrors(['breaks.0.start']);
        $this->assertEquals('休憩時間が勤務時間外です', session('errors')->first('breaks.0.start'));
    }

    public function testBreakEndShowsError()
    {
        $user = $this->createUser();
        $attendance = $this->createAttendances($user);
        $this->actingAs($user);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'remarks' => 'テストのため不正な時間',
            'breaks' => [
                ['start' => '17:00', 'end' => '19:00']
            ]
        ];

        $response = $this->post("/admin/attendance/{$attendance->id}/update", $postData);

        $response->assertSessionHasErrors(['breaks.0.start']);
        $this->assertEquals('休憩時間が勤務時間外です', session('errors')->first('breaks.0.start'));
    }

    public function testRemarkShowsError()
    {
        $user = $this->createUser();
        $attendance = $this->createAttendances($user);
        $this->actingAs($user);

        $postData = [
            'clock_in' => '09:00',
            'clock_out' => '19:00',
            'remarks' => '',
        ];

        $response = $this->post("/admin/attendance/{$attendance->id}/update", $postData);

        $response->assertSessionHasErrors(['remarks']);
        $this->assertEquals('備考を記入してください', session('errors')->first('remarks'));
    }
}
