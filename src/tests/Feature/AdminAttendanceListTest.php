<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
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
            'email' => 'user@example.com',
            'password' => Hash::make('password234'),
            'email_verified_at' => now(),
        ]);

        return [$user1, $user2];
    }

    private function createAttendancesFor($user, $clockIn, $clockOut, $date = null)
    {
        Attendance::create([
            'user_id' => $user->id,
            'date' => $date ?? Carbon::today(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => '退勤済',
        ]);
    }

    public function testTodayAttendances()
    {
        [$user1, $user2] = $this->createUser();
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00');
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00');

        $this->actingAs($user1);

        $response = $this->get('admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee('09:00');
        $response->assertSee('10:00');
    }

    public function testTodayDateShown()
    {
        [$user1, $user2] = $this->createUser();
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00');
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00');

        $this->actingAs($user1);

        $response = $this->get('admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee(Carbon::today()->format('Y年n月j日'));
    }

    public function testSeeYesterdayRecords()
    {
        [$user1, $user2] = $this->createUser();
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00', Carbon::yesterday());
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00', Carbon::yesterday());

        $this->actingAs($user1);

        $response = $this->get('admin/attendance/list?day=' . Carbon::yesterday()->toDateString());

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee(Carbon::yesterday()->format('Y年n月j日'));
    }

    public function testSeeTomorrowRecords()
    {
        [$user1, $user2] = $this->createUser();
        $this->createAttendancesFor($user1, '09:00:00', '18:00:00', Carbon::tomorrow());
        $this->createAttendancesFor($user2, '10:00:00', '19:00:00', Carbon::tomorrow());

        $this->actingAs($user1);

        $response = $this->get('admin/attendance/list?day=' . Carbon::tomorrow()->toDateString());

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('田中 一郎');
        $response->assertSee(Carbon::tomorrow()->format('Y年n月j日'));
    }
}
