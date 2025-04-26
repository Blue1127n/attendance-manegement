<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceRequest;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Arr;

class AttendanceRequestsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('email', '!=', 'admin@example.com')->get();

        foreach ($users as $user) {
            $attendances = Attendance::where('user_id', $user->id)
                ->whereIn(\DB::raw('MONTH(date)'), [1, 2, 3, 4, 5])
                ->inRandomOrder()
                ->take(rand(2, 3))
                ->get();

            foreach ($attendances as $attendance) {
                AttendanceRequest::create([
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'requested_clock_in' => \Carbon\Carbon::parse($attendance->clock_in)->addMinutes(15)->format('H:i:s'),
                    'requested_clock_out' => \Carbon\Carbon::parse($attendance->clock_out)->addMinutes(15)->format('H:i:s'),
                    'remarks' => '出退勤時間と休憩時間の調整',
                    'status' => Arr::random(['承認待ち', '承認済み']),
                ]);
            }
        }
    }
}
