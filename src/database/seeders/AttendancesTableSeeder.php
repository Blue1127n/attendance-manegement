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
        $user = User::where('email', 'user@example.com')->first();

        $months = [1, 2, 3];

        foreach ($months as $month) {
            for ($i = 1; $i <= 16; $i++) {
                $date = Carbon::create(2025, $month, $i);

                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'clock_in' => '09:00:00',
                    'clock_out' => '18:00:00',
                    'status' => '退勤済',
                    'remarks' => 'テストデータ（' . $month . '月' . $i . '日）',
                ]);
            }
        }
    }
}
