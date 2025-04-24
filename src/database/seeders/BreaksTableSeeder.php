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
            $date = Carbon::parse($attendance->date); // 日付をCarbonで扱いやすくするため
            $month = $date->format('Y-m'); // "2025-03"など、月ごとの情報

            // 25%の確率で複数休憩（2〜3回）、それ以外は1回1時間固定休憩にする
            $multiple = rand(0, 100) < 25;

            $breaks = []; // 休憩時間の配列を初期化する
            $totalMinutes = 0; // 合計休憩時間を追跡する

            if ($multiple) {
                $breakCount = rand(2, 3); // 分割休憩の回数：2回か3回
                $startTime = Carbon::createFromTimeString('12:00:00'); // 休憩の開始基準時間

                for ($i = 0; $i < $breakCount; $i++) {
                    $duration = rand(20, 40); // 20〜40分の休憩をランダム
                    if ($totalMinutes + $duration > 120) break; // 最大120分（2時間）を超えないようにする

                    $breakStart = $startTime->copy()->addMinutes($i * 60); // 1時間ずつずらす
                    $breakEnd = $breakStart->copy()->addMinutes($duration);

                    $breaks[] = [
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart->format('H:i:s'),
                        'break_end' => $breakEnd->format('H:i:s'),
                    ];

                    $totalMinutes += $duration;
                }

                // 分割でも合計が1時間に満たない場合 → 最後に追加で補う
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
                // 1時間ぴったりの休憩を1回
                $breaks[] = [
                    'attendance_id' => $attendance->id,
                    'break_start' => '12:00:00',
                    'break_end' => '13:00:00',
                ];
            }

            // 保存処理（DBに登録）
            foreach ($breaks as $break) {
                BreakTime::create($break);
            }
        }
    }
}
