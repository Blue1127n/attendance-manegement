<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BreakTime;
use App\Models\Attendance;
use Carbon\Carbon;

class BreaksTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            // 30%の確率で休憩を入れる
            if (rand(0, 100) < 30) {
                $breakCount = rand(1, 3); // 1〜3回の休憩
                $startTime = Carbon::createFromTimeString('12:00:00');

                for ($i = 0; $i < $breakCount; $i++) {
                    $breakStart = $startTime->copy()->addMinutes($i * 60);
                    $breakEnd = $breakStart->copy()->addMinutes(15);

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart->format('H:i:s'),
                        'break_end' => $breakEnd->format('H:i:s'),
                    ]);
                }
            }
        }
    }
}
