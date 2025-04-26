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
            $date = Carbon::parse($attendance->date);
            $month = $date->format('Y-m');

            $multiple = rand(0, 100) < 25;

            $breaks = [];
            $totalMinutes = 0;

            if ($multiple) {
                $breakCount = rand(2, 3);
                $startTime = Carbon::createFromTimeString('12:00:00');

                for ($i = 0; $i < $breakCount; $i++) {
                    $duration = rand(20, 40);
                    if ($totalMinutes + $duration > 120) break;

                    $breakStart = $startTime->copy()->addMinutes($i * 60);
                    $breakEnd = $breakStart->copy()->addMinutes($duration);

                    $breaks[] = [
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart->format('H:i:s'),
                        'break_end' => $breakEnd->format('H:i:s'),
                    ];

                    $totalMinutes += $duration;
                }

                if ($totalMinutes < 60) {
                    $remaining = 60 - $totalMinutes;
                    $lastStart = Carbon::createFromTimeString('15:00:00');
                    $breaks[] = [
                        'attendance_id' => $attendance->id,
                        'break_start' => $lastStart->format('H:i:s'),
                        'break_end' => $lastStart->copy()->addMinutes($remaining)->format('H:i:s'),
                    ];
                }
            } else {
                $breaks[] = [
                    'attendance_id' => $attendance->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ];
            }

            foreach ($breaks as $break) {
                BreakTime::create($break);
            }
        }
    }
}
