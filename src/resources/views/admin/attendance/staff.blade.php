@extends('layouts.admin')

@section('title', $user->last_name . $user->first_name . 'さんの勤怠')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/staff-attendance.css') }}">
@endpush

@section('content')
<div class="staff-attendance-container">
    <h1 class="title"><span class="vertical-line"></span>{{ $user->last_name }}{{ $user->first_name }}さんの勤怠</h1>

    <div class="month-navigation">
        <form action="{{ route('admin.staff.attendance', ['id' => $user->id]) }}" method="GET" class="nav-button">
            <input type="hidden" name="month" value="{{ $prevMonth }}">
            <button type="submit" class="month-button">
                <img src="{{ asset('images/left-arrow.png') }}" alt="前月" class="arrow-icon">
                <span class="month-label">前月</span>
            </button>
        </form>

        <div class="current-month">
            <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            <span>{{ $currentMonth->format('Y/m') }}</span>
        </div>

        <form action="{{ route('admin.staff.attendance', ['id' => $user->id]) }}" method="GET" class="nav-button">
            <input type="hidden" name="month" value="{{ $nextMonth }}">
            <button type="submit" class="month-button">
                <span class="month-label">翌月</span>
                <img src="{{ asset('images/right-arrow.png') }}" alt="翌月" class="arrow-icon">
            </button>
        </form>
    </div>

    <div class="attendance-header">
        <div class="row">
            <div>日付</div>
            <div>出勤</div>
            <div>退勤</div>
            <div>休憩</div>
            <div>合計</div>
            <div>詳細</div>
        </div>
    </div>

    <div class="attendance-body">
        @foreach ($attendances as $attendance)
            <div class="row">
                <div>{{ \Carbon\Carbon::parse($attendance->date)->format('m/d(D)') }}</div>
                <div>{{ $attendance->start_time ?? '' }}</div>
                <div>{{ $attendance->end_time ?? '' }}</div>
                <div>{{ $attendance->break_time ?? '' }}</div>
                <div>{{ $attendance->total_time ?? '' }}</div>
                <div><a href="{{ route('admin.attendance.detail', $attendance->id) }}" class="detail-link">詳細</a></div>
            </div>
        @endforeach
    </div>

    <div class="csv-button">
        <button type="button">CSV出力</button>
    </div>
</div>
@endsection
