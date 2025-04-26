@extends('layouts.admin')

@section('title', '管理者修正申請承認')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/approve.css') }}">
@endpush

@section('content')
<div class="approve-container">
    <h1 class="title"><span class="vertical-line"></span>勤怠詳細</h1>

    <div class="request-card">
        <div class="row">
            <div class="label">名前</div>
            <div class="value">{{ $attendanceRequest->user->last_name }}&nbsp;&nbsp;&nbsp;{{ $attendanceRequest->user->first_name }}</div>
        </div>
        <div class="row">
            <div class="label">日付</div>
            <div class="value">
                <span class="year">{{ \Carbon\Carbon::parse($attendanceRequest->attendance->date)->format('Y年') }}</span>
                <span class="month-day">{{ \Carbon\Carbon::parse($attendanceRequest->attendance->date)->format('n月j日') }}</span>
            </div>
        </div>
        <div class="row">
            <div class="label">出勤・退勤</div>
            <div class="value">
                <span class="clock_in">{{ \Carbon\Carbon::parse($attendanceRequest->requested_clock_in)->format('H:i') }}</span>
                <span class="time-separator">〜</span>
                <span class="clock_out">{{ \Carbon\Carbon::parse($attendanceRequest->requested_clock_out)->format('H:i') }}</span>
            </div>
        </div>
        @foreach ($attendanceRequest->breaks as $index => $break)
            <div class="row">
                <div class="label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</div>
                <div class="value">
                    <span class="break_start">{{ \Carbon\Carbon::parse($break->requested_break_start)->format('H:i') }}</span>
                    <span class="time-separator">〜</span>
                    <span class="break_end">{{ \Carbon\Carbon::parse($break->requested_break_end)->format('H:i') }}</span>
                </div>
            </div>
        @endforeach
        <div class="row">
            <div class="label">備考</div>
            <div class="value">
                <span class="remarks">{{ $attendanceRequest->remarks }}</span>
            </div>
        </div>
    </div>

    <div class="button-container">
        @if ($attendanceRequest->status === '承認済み')
            {{-- 承認済み：ボタン無効 --}}
            <button class="correction-button" disabled>修正済み</button>
        @else
            {{-- 承認待ち：ボタン有効 --}}
            <form action="{{ route('admin.request.approve.update', ['attendance_correct_request' => $attendanceRequest->id]) }}" method="POST">
                @csrf
                <button type="submit" class="correction-button">承認</button>
            </form>
        @endif
    </div>
</div>
@endsection
