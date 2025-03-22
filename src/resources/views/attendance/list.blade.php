@extends('layouts.user')

@section('title', '勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/list.css') }}">
@endpush

@section('content')
<div class="attendance-list-container">
    <h1 class="title">
        <span class="vertical-line"></span>
        勤怠一覧</h1>

    <div class="month-navigation">
        <form action="{{ route('user.attendance.list') }}" method="GET" class="nav-button">
            <input type="hidden" name="month" value="{{ $prevMonth }}">
            <button type="submit">← 前月</button>
        </form>

        <div class="current-month">
            <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            {{ $currentMonth->format('Y/m') }}
        </div>

        <form action="{{ route('user.attendance.list') }}" method="GET" class="nav-button">
            <input type="hidden" name="month" value="{{ $nextMonth }}">
            <button type="submit">翌月 →</button>
        </form>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $attendance)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($attendance->date)->format('m/d(D)') }}</td>
                    <td>{{ $attendance->start_time ?? '' }}</td>
                    <td>{{ $attendance->end_time ?? '' }}</td>
                    <td>{{ $attendance->break_time ?? '' }}</td>
                    <td>{{ $attendance->total_time ?? '' }}</td>
                    <td><a href="{{ route('user.attendance.detail', $attendance->id) }}">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection