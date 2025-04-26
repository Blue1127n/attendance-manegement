<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    private function createWorkingUser()
    {
        $user = User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in' => now()->subHours(1),
            'status' => '出勤中',
        ]);

        return $user;
    }

    public function testBreakStart()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $this->post(route('user.attendance.startBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    public function testBreakTwice()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.startBreak'));
        $this->post(route('user.attendance.endBreak'));
        $this->post(route('user.attendance.startBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    public function testBreakEnd()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.startBreak'));
        $this->post(route('user.attendance.endBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    public function testBreakEndTwice()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.startBreak'));
        $this->post(route('user.attendance.endBreak'));
        $this->post(route('user.attendance.startBreak'));

        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');
    }

    public function testBreakInList()
    {
        $user = $this->createWorkingUser();
        $this->actingAs($user);

        $this->post(route('user.attendance.startBreak'));
        sleep(1);
        $this->post(route('user.attendance.endBreak'));

        $response = $this->get('/attendance/list');
        $response->assertSee(':');
    }
}
