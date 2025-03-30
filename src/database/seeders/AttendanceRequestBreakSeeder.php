<?php

namespace Database\Seeders;

use App\Models\AttendanceRequestBreak;
use App\Models\AttendanceRequest;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceRequestBreakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 承認待ち or 承認済みの申請を対象にする（却下は除外も可）
        $requests = AttendanceRequest::whereIn('status', ['承認待ち', '承認済み'])->get();

        foreach ($requests as $request) {
            // 1～3 回の休憩申請をランダムに作成
            $breakCount = rand(1, 3);
            $startTime = Carbon::createFromTime(12, 0, 0); // 初期休憩開始 12:00

            for ($i = 0; $i < $breakCount; $i++) {
                $breakStart = $startTime->copy()->addMinutes($i * 90); // 90分ごとにずらす
                $breakEnd = $breakStart->copy()->addMinutes(rand(20, 40)); // 20〜40分の休憩

                AttendanceRequestBreak::create([
                    'attendance_request_id' => $request->id,
                    'requested_break_start' => $breakStart->format('H:i:s'),
                    'requested_break_end' => $breakEnd->format('H:i:s'),
                ]);
            }
        }
    }
}
