@extends('layouts.user')

@section('title', '勤怠一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/list.css') }}">
@endpush

@section('content')
<div class="attendance-list-container">
    <h1 class="title"><span class="vertical-line"></span>勤怠一覧</h1>

    <div class="month-navigation">
        <form action="{{ route('user.attendance.list') }}" method="GET" class="nav-button">
            <input type="hidden" name="month" value="{{ $prevMonth }}">
            <button type="submit" class="month-button">
                <img src="{{ asset('images/left-arrow.png') }}" alt="前月" class="arrow-icon">
                <span class="month-label">前月</span>
            </button>
        </form>

        <div class="current-month">
            <img src="{{ asset('images/calendar.png') }}" alt="カレンダーアイコン" class="calendar-icon">
            <span>{{ $currentMonth->format('Y/m') }}</span>
            {{-- $currentMonthコントローラーで渡している 現在の月（Carbonインスタンス）$currentMonth = Carbon::now(); --}}
        </div>

        <form action="{{ route('user.attendance.list') }}" method="GET" class="nav-button">
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
                <div>{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d(D)') }}</div>
                <div>{{ $attendance->start_time ?? '' }}</div>
                <div>{{ $attendance->end_time ?? '' }}</div>
                <div>{{ $attendance->break_time ?? '' }}</div>
                <div>{{ $attendance->total_time ?? '' }}</div>
                <div><a href="{{ route('user.attendance.detail', $attendance->id) }}" class="detail-link">詳細</a></div>
            </div>
        @endforeach
    </div>
</div>
@endsection

{{-- @foreach は、**配列やコレクションのデータを「1つずつ順番に取り出して表示する」**ための構文 --}}
{{-- @foreach ($attendances as $attendance)は$attendances に入ってる複数の勤怠データを$attendance に1件ずつ取り出して処理してる --}}
{{-- @foreach ($データ一覧 as $1件ずつのデータ)
        表示するHTMLや値
        @endforeach --}}
{{-- \Carbon\Carbon::parse($attendance->date) $attendance->date が「文字列の形（例: '2025-04-20'）」で保存されている場合に、
    それを Carbonの日時として使えるように変換するコードです
    parse() は、Carbon（日付ライブラリ）で日付文字列を「日付オブジェクト」に変換するためのメソッド--}}
