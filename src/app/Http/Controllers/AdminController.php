<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\AttendanceRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    public function attendanceList(Request $request)
    {
    $day = $request->input('day')
        ? Carbon::parse($request->input('day'))->startOfDay()
        : Carbon::today();

    $attendances = Attendance::with(['user', 'breaks'])
        ->where('date', $day->toDateString())
        ->orderBy('user_id')
        ->get()
        ->map(function ($attendance) {

            $attendance->start_time = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $attendance->end_time = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

            $totalBreakMinutes = $attendance->breaks->reduce(function ($carry, $break) {
                if ($break->break_start && $break->break_end) {
                    $start = Carbon::parse($break->break_start);
                    $end = Carbon::parse($break->break_end);
                    return $carry + $end->diffInMinutes($start);
                }
                return $carry;
            }, 0);

            $attendance->break_time = $totalBreakMinutes
                ? sprintf('%d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60)
                : '';

            if ($attendance->clock_in && $attendance->clock_out) {
                $start = Carbon::parse($attendance->clock_in);
                $end = Carbon::parse($attendance->clock_out);
                $workMinutes = $end->diffInMinutes($start) - $totalBreakMinutes;
                $attendance->total_time = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
            } else {
                $attendance->total_time = '';
            }

            return $attendance;
        });

    return view('admin.attendance.list', [
        'attendances' => $attendances,
        'currentDay' => $day,
        'prevDay' => $day->copy()->subDay()->format('Y-m-d'),
        'nextDay' => $day->copy()->addDay()->format('Y-m-d'),
    ]);
    }

    public function attendanceDetail($id)
    {
    $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);

    return view('admin.attendance.detail', compact('attendance'));
    }

    public function updateAttendance(AdminAttendanceUpdateRequest $request, $id)
    {
    DB::beginTransaction();

    try {
        $attendance = Attendance::findOrFail($id);

        $attendance->clock_in = $request->clock_in;
        $attendance->clock_out = $request->clock_out;
        $attendance->remarks = $request->remarks;
        $attendance->save();

        $attendance->breaks()->delete();

        foreach ($request->breaks as $break) {
            if ($break['start'] && $break['end']) {
                $attendance->breaks()->create([
                    'break_start' => $break['start'],
                    'break_end' => $break['end'],
                ]);
            }
        }

        DB::commit();

        return redirect()->route('admin.attendance.detail', $attendance->id)->with('corrected', true);
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'エラーが発生しました。もう一度お試しください。'])->withInput();
    }
    }

    public function staffList()
    {
        $users = User::all();
        return view('admin.staff.list', compact('users'));
    }

    public function staffAttendance(Request $request, $id)
    {
    $user = User::findOrFail($id);

    $month = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
    $startOfMonth = $month->copy()->startOfMonth()->toDateString();
    $endOfMonth = $month->copy()->endOfMonth()->toDateString();

    $attendances = Attendance::with('breaks')
        ->where('user_id', $id)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->orderBy('date')
        ->get()
        ->map(function ($attendance) {

            $attendance->start_time = $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $attendance->end_time = $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '';

            $totalBreak = $attendance->breaks->sum(function ($break) {
                return \Carbon\Carbon::parse($break->break_end)->diffInMinutes($break->break_start);
            });

            $attendance->break_time = $totalBreak ? sprintf('%d:%02d', floor($totalBreak / 60), $totalBreak % 60) : '';
            $attendance->total_time = ($attendance->clock_in && $attendance->clock_out)
                ? sprintf('%d:%02d',
                    floor((\Carbon\Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) / 60),
                    (\Carbon\Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) % 60
                )
                : '';
            return $attendance;
        });

    return view('admin.attendance.staff', [
        'user' => $user,
        'attendances' => $attendances,
        'currentMonth' => $month,
        'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
        'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
    ]);
    }

    public function exportStaffAttendanceCsv(Request $request, $id)
    {
    $user = User::findOrFail($id);
    $month = $request->input('month') ? Carbon::parse($request->input('month')) : Carbon::now();
    $startOfMonth = $month->copy()->startOfMonth()->toDateString();
    $endOfMonth = $month->copy()->endOfMonth()->toDateString();

    $attendances = Attendance::with('breaks')
        ->where('user_id', $id)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->orderBy('date')
        ->get();

    return new StreamedResponse(function () use ($attendances, $user) {
        $handle = fopen('php://output', 'w');

        $headers = ['日付', '出勤', '退勤', '休憩時間', '合計時間'];

        fputcsv($handle, array_map(function ($value) {
            return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
        }, $headers));

        foreach ($attendances as $attendance) {
            $clockIn = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
            $clockOut = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

            $totalBreak = $attendance->breaks->sum(function ($break) {
                return Carbon::parse($break->break_end)->diffInMinutes($break->break_start);
            });

            $breakTime = $totalBreak ? sprintf('%d:%02d', floor($totalBreak / 60), $totalBreak % 60) : '';

            $totalTime = ($attendance->clock_in && $attendance->clock_out)
                ? sprintf('%d:%02d',
                    floor((Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) / 60),
                    (Carbon::parse($attendance->clock_out)->diffInMinutes($attendance->clock_in) - $totalBreak) % 60
                ) : '';

            $row = [
                Carbon::parse($attendance->date)->format('Y-m-d'),
                $clockIn,
                $clockOut,
                $breakTime,
                $totalTime
            ];

            fputcsv($handle, array_map(function ($value) {
                return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
            }, $row));
        }

        fclose($handle);
    }, 200, [
        "Content-Type" => "text/csv; charset=Shift_JIS",
        "Content-Disposition" => "attachment; filename=attendance_{$user->last_name}_{$user->first_name}.csv",
    ]);
    }

    public function requestList()
    {

    $pending = AttendanceRequest::with(['user', 'attendance'])
        ->where('status', '承認待ち')
        ->orderBy('updated_at', 'desc')
        ->get();

    $approved = AttendanceRequest::with(['user', 'attendance'])
        ->where('status', '承認済み')
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('admin.request.list', compact('pending', 'approved'));
    }

    public function approveRequest($id)
    {
    $attendanceRequest = AttendanceRequest::with(['user', 'attendance', 'attendance_request_breaks'])->findOrFail($id);

    return view('admin.request.approve', compact('attendanceRequest'));
    }

    public function updateApprove(Request $request, $id)
    {
    DB::beginTransaction();

    try {
        $attRequest = AttendanceRequest::with(['attendance', 'attendance_request_breaks'])->findOrFail($id);

        $attendance = $attRequest->attendance;
        if (!$attendance) {
            throw new \Exception('対応する勤怠データが見つかりません');
        }
        $attendance->clock_in = $attRequest->requested_clock_in;
        $attendance->clock_out = $attRequest->requested_clock_out;
        $attendance->remarks = $attRequest->remarks;
        $attendance->save();

        $attendance->breaks()->delete();
        foreach ($attRequest->attendance_request_breaks as $break) {
            $attendance->breaks()->create([
                'break_start' => $break->requested_break_start,
                'break_end' => $break->requested_break_end,
            ]);
        }

        $attRequest->status = '承認済み';
        $attRequest->save();

        DB::commit();

        return redirect()->route('admin.request.approve.show', $attRequest->id)->with('corrected', true);
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'エラーが発生しました']);
    }
    }
}


