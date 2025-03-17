@extends('layouts.app')

@section('header')
    <div class="header">
        <div class="header__logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </div>

        <nav>
            <a href="{{ route('user.attendance') }}">勤怠</a>
            <a href="{{ route('user.attendance.list') }}">勤怠一覧</a>
            <a href="{{ route('user.request.list') }}">申請</a>
            <a href="{{ route('logout') }}">ログアウト</a>
        </nav>
    </div>
@endsection
