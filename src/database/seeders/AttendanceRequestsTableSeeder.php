<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\AttendanceRequest;
use App\Models\User;
use App\Models\Attendance;

class AttendanceRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::first();
        $attendance = Attendance::first();

        AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '09:15:00',
            'requested_clock_out' => '18:15:00',
            'requested_break_start' => '12:00:00',
            'requested_break_end' => '12:45:00',
            'remarks' => '出勤時間を15分修正',
            'status' => '承認待ち',
        ]);
    }
}
