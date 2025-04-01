@extends('layouts.admin')

@section('title', '管理者勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin_list.css') }}">
@endpush

@section('content')
<div class="attendance-list-container">
    <h1 class="title"><span class="vertical-line"></span>{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('Y/m/d') }}の勤怠</h1>

    <div class="day-navigation">
        <form action="{{ route('admin.attendance.list') }}" method="GET" class="nav-button">
            <input type="hidden" name="day" value="{{ $prevDay }}">
            <button type="submit" class="day-button">
                <img src="{{ asset('images/left-arrow.png') }}" alt="前日" class="arrow-icon">
                <span class="day-label">前日</span>
            </button>
        </form>

        <div class="current-day">
            <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            <span>{{ $currentDay->format('('Y/m/d')') }}</span>
        </div>

        <form action="{{ route('admin.attendance.list') }}" method="GET" class="nav-button">
            <input type="hidden" name="day" value="{{ $nextDay }}">
            <button type="submit" class="day-button">
                <span class="day-label">翌日</span>
                <img src="{{ asset('images/right-arrow.png') }}" alt="翌日" class="arrow-icon">
            </button>
        </form>
    </div>

    <div class="attendance-header">
        <div class="row">
            <div>名前</div>
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
                <div>{{ $attendance->attendance->id }}</div>
                <div>{{ $attendance->start_time ?? '' }}</div>
                <div>{{ $attendance->end_time ?? '' }}</div>
                <div>{{ $attendance->break_time ?? '' }}</div>
                <div>{{ $attendance->total_time ?? '' }}</div>
                <div><a href="{{ route('admin.attendance.detail', $attendance->id) }}" class="detail-link">詳細</a></div>
            </div>
        @endforeach
    </div>
</div>
@endsection