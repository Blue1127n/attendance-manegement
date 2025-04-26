<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceStatusTest extends TestCase
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

    public function testStatusIdle()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('勤務外');
    }

    public function testStatusWorking()
    {
        $user = $this->createUser();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now(),
            'status' => '出勤中',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    public function testStatusOnBreak()
    {
        $user = $this->createUser();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now(),
            'status' => '休憩中',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    public function testStatusFinished()
    {
        $user = $this->createUser();
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'status' => '退勤済',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }
}
