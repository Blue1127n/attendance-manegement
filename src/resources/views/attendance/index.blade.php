@extends('layouts.user')

@section('title', '勤怠登録')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="index-container">
        <div class="status-labels">
        @if ($attendance->status === '勤務外')
            <span class="status-label active">勤務外</span>
        @elseif ($attendance->status === '出勤中')
            <span class="status-label active">出勤中</span>
        @elseif ($attendance->status === '休憩中')
            <span class="status-label active">休憩中</span>
        @elseif ($attendance->status === '退勤済')
            <span class="status-label active">退勤済</span>
        @endif
    </div>

        <h2>{{ now()->format('Y年m月d日 (D)') }}</h2>

        <h1>{{ now()->format('H:i') }}</h1>

        @if ($attendance->status === '勤務外')
            <form action="{{ route('user.attendance.clockIn') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">出勤</button>
            </form>

        @elseif ($attendance->status === '出勤中')
            <form action="{{ route('user.attendance.clockOut') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">退勤</button>
            </form>
            <form action="{{ route('user.attendance.startBreak') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning">休憩入</button>
            </form>

        @elseif ($attendance->status === '休憩中')
            <form action="{{ route('user.attendance.endBreak') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">休憩戻</button>
            </form>

        @elseif ($attendance->status === '退勤済')
            <p>お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection
