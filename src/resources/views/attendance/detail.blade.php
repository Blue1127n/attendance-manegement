@extends('layouts.user')

@section('title', '勤怠詳細')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail-container">
    <h1 class="title"><span class="vertical-line"></span>勤怠詳細</h1>

    <div class="detail-card">
        <div class="row">
            <div class="label">名前</div>
            <div class="value">{{ $attendance->user->last_name }} {{ $attendance->user->first_name }}</div>
        </div>
        <div class="row">
            <div class="label">日付</div>
            <div class="value">
                <span class="year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                <span class="month-day">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
            </div>
        </div>
        <div class="row">
            <div class="label">出勤・退勤</div>
            <div class="value">
                <input type="time" name="clock_in" value="{{ old('clock_in', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}">
                〜
                <input type="time" name="clock_out" value="{{ old('clock_out', \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')) }}">
            </div>
        </div>
        @foreach ($attendance->breaks as $break)
        <div class="row">
            <div class="label">休憩</div>
            <div class="value">
                {{ \Carbon\Carbon::parse($break->break_start)->format('H:i') }} 〜
                {{ \Carbon\Carbon::parse($break->break_end)->format('H:i') }}
            </div>
        </div>
        @endforeach
        <div class="row">
            <div class="label">備考</div>
            <div class="value">{{ $attendance->remarks ?? 'なし' }}</div>
        </div>
    </div>

    <div class="button-container">
        <a href="#" class="correction-button">修正</a>
    </div>
</div>
@endsection