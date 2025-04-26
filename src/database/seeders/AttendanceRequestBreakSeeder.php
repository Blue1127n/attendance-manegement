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
        $requests = AttendanceRequest::whereIn('status', ['承認待ち', '承認済み'])->get();

        foreach ($requests as $request) {
            $breakCount = rand(1, 3);
            $startTime = Carbon::createFromTime(12, 0, 0);

            for ($i = 0; $i < $breakCount; $i++) {
                $breakStart = $startTime->copy()->addMinutes($i * 90);
                $breakEnd = $breakStart->copy()->addMinutes(rand(20, 40));

                AttendanceRequestBreak::create([
                    'attendance_request_id' => $request->id,
                    'requested_break_start' => $breakStart->format('H:i:s'),
                    'requested_break_end' => $breakEnd->format('H:i:s'),
                ]);
            }
        }
    }
}
