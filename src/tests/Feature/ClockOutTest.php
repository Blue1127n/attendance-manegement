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

    public function testClockOutButton()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8),
            'status' => '出勤中',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        $this->post(route('user.attendance.clockOut'));

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }

    public function testAdminShowsClockOut()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.clockIn'));

        Attendance::where('user_id', $user->id)->first()->update([
        'clock_in' => now()->subMinutes(5),
        ]);

        $this->post(route('user.attendance.clockOut'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => '退勤済',
        ]);

        $clockOut = Attendance::where('user_id', $user->id)->first()->clock_out;
        $formatted = Carbon::parse($clockOut)->format('H:i');

        $response = $this->get('/admin/attendance/list');
        $response->assertSeeText($formatted);
    }
}