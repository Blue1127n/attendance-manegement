<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminAttendanceStaffListTest extends TestCase
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

    private function createAttendancesFor($user, $month = null)
    {
        $month = $month ?? Carbon::now();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        for ($day = $startOfMonth; $day->lte($endOfMonth); $day->addDay()) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'status' => '退勤済',
            ]);
        }
    }

    public function testStaffInfoShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $this->actingAs($user1);

        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('田中 次郎');
        $response->assertSee('admin@example.com');
        $response->assertSee('田中 一郎');
        $response->assertSee('user1@example.com');
        $response->assertSee('鈴木 花子');
        $response->assertSee('user2@example.com');
    }

    public function testStaffAttendancesShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $this->createAttendancesFor($user3);

        $this->actingAs($user1);

        $response = $this->get('/admin/attendance/staff/' . $user3->id);

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee(Carbon::now()->format('Y/m'));
        $response->assertSee(Carbon::today()->format('m/d'));
    }

    public function testSeePrevMonthShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $prevMonth = Carbon::now()->subMonth()->startOfMonth();

        $this->createAttendancesFor($user3, $prevMonth);

        $this->actingAs($user1);

        $response = $this->get('/admin/attendance/staff/' . $user3->id . '?month=' . $prevMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee($prevMonth->format('Y/m'));
    }

    public function testSeeNextMonthShown()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $nextMonth = Carbon::now()->addMonth()->startOfMonth();

        $this->createAttendancesFor($user3, $nextMonth);

        $this->actingAs($user1);

        $response = $this->get('/admin/attendance/staff/' . $user3->id . '?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee($nextMonth->format('Y/m'));
    }

    public function testDetailLinkWorks()
    {
        [$user1, $user2, $user3] = $this->createUser();

        $attendance = Attendance::create([
            'user_id' => $user3->id,
            'date' => now()->toDateString(),
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'status' => '退勤済',
        ]);

        $this->actingAs($user1);

        $response = $this->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('鈴木');
        $response->assertSee('花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }
}

