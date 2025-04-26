<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use App\Http\Requests\AttendanceCorrectionRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function index()
    {
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if (!$attendance) {
        $attendance = (object) [
            'status' => '勤務外'
    ];
    }

    return view('attendance.index', compact('attendance'));
    }

    public function clockIn()
    {
    $user = Auth::user();

    if (!$user) {
        return redirect()->route('login')->with('error', 'ログインしてください');
    }

    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if ($attendance && $attendance->clock_in) {
        return redirect()->route('user.attendance')->with('error', '既に出勤済みです');
    }

    Attendance::create([
        'user_id' => $user->id,
        'date' => $today,
        'clock_in' => now(),
        'status' => '出勤中',
    ]);

    return redirect()->route('user.attendance');
    }

    public function startBreak()
    {
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if (!$attendance || $attendance->status !== '出勤中') {
        return redirect()->route('user.attendance')->with('error', '出勤していないため休憩できません');
    }

    if ($attendance->status === '休憩中') {
        return redirect()->route('user.attendance')->with('error', 'すでに休憩中です');
    }

    $attendance->breaks()->create([
        'break_start' => now(),
    ]);

    $attendance->update(['status' => '休憩中']);

    return redirect()->route('user.attendance');
    }

    public function endBreak()
    {
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if (!$attendance || $attendance->status !== '休憩中') {
        return redirect()->route('user.attendance')->with('error', '休憩していないため戻ることができません');
    }

    $lastBreak = $attendance->breaks()->whereNull('break_end')->latest()->first();

    if ($lastBreak) {
        $lastBreak->update(['break_end' => now()]);
    }

    $attendance->update(['status' => '出勤中']);

    return redirect()->route('user.attendance');
    }

    public function clockOut()
    {
    $user = Auth::user();
    $today = Carbon::today()->toDateString();

    $attendance = Attendance::where('user_id', $user->id)
                            ->where('date', $today)
                            ->first();

    if (!$attendance || !$attendance->clock_in) {
        return redirect()->route('user.attendance')->with('error', '出勤していないため退勤できません');
    }

    if ($attendance->status === '退勤済') {
        return redirect()->route('user.attendance')->with('error', 'すでに退勤済みです');
    }

    $attendance->update([
        'clock_out' => now(),
        'status' => '退勤済'
    ]);

    return redirect()->route('user.attendance');
    }

    public function list(Request $request)
    {
    $user = Auth::user();

    $month = $request->input('month')
        ? Carbon::parse($request->input('month'))
        : Carbon::now();

    $startOfMonth = $month->copy()->startOfMonth()->toDateString();
    $endOfMonth = $month->copy()->endOfMonth()->toDateString();

    $attendances = Attendance::with('breaks')
        ->where('user_id', $user->id)
        ->whereBetween('date', [$startOfMonth, $endOfMonth])
        ->orderBy('date', 'asc')
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

    return view('attendance.list', [
        'attendances' => $attendances,
        'currentMonth' => $month,
        'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
        'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
    ]);
    }

    public function correctionRequest(AttendanceCorrectionRequest $request, $id)
    {

    $attendance = Attendance::with('breaks')->where('user_id', Auth::id())->findOrFail($id);

    $attendanceRequest = AttendanceRequest::create([
        'user_id' => Auth::id(),
        'attendance_id' => $attendance->id,
        'requested_clock_in' => $request->clock_in,
        'requested_clock_out' => $request->clock_out,
        'remarks' => trim($request->remarks),
        'status' => '承認待ち',
    ]);

    foreach ($request->breaks as $break) {
        if (!empty($break['start']) && !empty($break['end'])) {
            $attendanceRequest->breaks()->create([
                'requested_break_start' => $break['start'],
                'requested_break_end' => $break['end'],
            ]);
        }
    }

    return redirect()->route('user.attendance.detail', ['id' => $attendance->id]);
    }

    public function requestList()
    {
    $user = Auth::user();

    $pending = AttendanceRequest::with(['user', 'attendance'])
        ->where('user_id', $user->id)
        ->where('status', '承認待ち')
        ->orderBy('updated_at', 'desc')
        ->get();

    $approved = AttendanceRequest::with(['user', 'attendance'])
        ->where('user_id', $user->id)
        ->where('status', '承認済み')
        ->orderBy('updated_at', 'desc')
        ->get();

    return view('attendance.request.list', compact('pending', 'approved'));
    }

    public function show($id)
    {

    $attendance = Attendance::with(['user', 'breaks'])->findOrFail($id);


    $request = AttendanceRequest::with('breaks')
        ->where('attendance_id', $id)
        ->where('user_id', Auth::id())
        ->whereIn('status', ['承認待ち', '承認済み'])
        ->latest()
        ->first();

    if ($request) {
        $attendance->clock_in = $request->requested_clock_in;
        $attendance->clock_out = $request->requested_clock_out;
        $attendance->remarks = $request->remarks;

        $attendance->breaks = $request->breaks->map(function ($break) {
            return (object)[
                'break_start' => $break->requested_break_start,
                'break_end' => $break->requested_break_end,
            ];
        });
    }

    return view('attendance.detail', compact('attendance', 'request'));
    }
}
