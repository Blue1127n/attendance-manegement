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

    public function testMyRecords()
    {
        $user = $this->createUser();
        $this->createAttendances($user);
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::today()->format('m/d'));
        $response->assertSee(Carbon::yesterday()->format('m/d'));
    }

    public function testCurrentMonth()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y/m'));
    }

    public function testPrevMonth()
    {
        $user = $this->createUser();

        $lastMonth = Carbon::now()->subMonth();
        Attendance::create([
            'user_id' => $user->id,
            'date' => $lastMonth->copy()->startOfMonth()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance/list?month=' . $lastMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));
    }

    public function testNextMonth()
    {
        $user = $this->createUser();

        $nextMonth = Carbon::now()->addMonth();
        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->startOfMonth()->toDateString(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user);
        $response = $this->get('/attendance/list?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

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
