<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::all();
        $months = [1, 2, 3, 4, 5];
        $year = 2025;

        foreach ($users as $user) {
            foreach ($months as $month) {
                // 1〜28日の中からランダムに16日分選ぶ（重複なし）
                $days = collect(range(1, 28))->shuffle()->take(16);

                foreach ($days as $day) {
                    $date = Carbon::create($year, $month, $day);

                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date->toDateString(),
                        'clock_in' => '09:00:00',
                        'clock_out' => '18:00:00',
                        'status' => '退勤済',
                        'remarks' => '',
                    ]);
                }
            }
        }
    }
}
