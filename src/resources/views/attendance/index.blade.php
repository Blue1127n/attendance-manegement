@extends('layouts.user')

@section('title', '勤怠登録')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endpush

{{-- 退勤済のときだけヘッダーを差し替える --}}
@if ($attendance->status === '退勤済')
    @section('header')
        <div class="header">
            <div class="header__nav">
                <nav>
                    <a href="{{ route('user.attendance.list') }}">今月の出勤一覧</a>
                    <a href="{{ route('user.request.list') }}">申請一覧</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="logout-button">ログアウト</button>
                    </form>
                </nav>
            </div>
        </div>
    @endsection
@endif

@section('content')
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
        {{-- 今の日付を 日本語の曜日付き で表示 例2025年4月20日 (日) --}}
        <h1>{{ now()->translatedFormat('Y年n月j日 (D)') }}</h1>

        <h2>{{ now()->format('H:i') }}</h2>

        @if ($attendance->status === '勤務外')
            <form action="{{ route('user.attendance.clockIn') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">出勤</button>
            </form>

        @elseif ($attendance->status === '出勤中')
            <div class="button-group">
                <form action="{{ route('user.attendance.clockOut') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger">退勤</button>
                </form>
                <form action="{{ route('user.attendance.startBreak') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-warning">休憩入</button>
                </form>
            </div>

        @elseif ($attendance->status === '休憩中')
            <form action="{{ route('user.attendance.endBreak') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">休憩戻</button>
            </form>

        @elseif ($attendance->status === '退勤済')
            <p>お疲れ様でした。</p>
        @endif
    </div>
@endsection
