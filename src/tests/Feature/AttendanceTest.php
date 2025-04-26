<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceTest extends TestCase
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

    public function testClockIn()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('出勤');

        $this->post(route('user.attendance.clockIn'));

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    public function testClockInOnce()
    {

        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.clockIn'));

        $attendance = Attendance::where('user_id', $user->id)->first();
        $attendance->update([
            'status' => '退勤済',
            'clock_out' => now(),
        ]);

        $response = $this->get('/attendance');
        $response->assertDontSee('<button type="submit" class="btn btn-primary">出勤</button>', false);
    }

    public function testAdminShowsClockIn()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.clockIn'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '出勤中',
        ]);

        $clockIn = Attendance::where('user_id', $user->id)->first()->clock_in;

        $formatted = Carbon::parse($clockIn)->format('H:i');

        $response = $this->get('/admin/attendance/list');
        $response->assertSee($formatted);
    }
}
