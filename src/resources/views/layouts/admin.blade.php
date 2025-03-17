@extends('layouts.app')

@section('header')
    <div class="header">
        <div class="header__logo">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </div>

        <nav>
            <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
            <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
            <a href="{{ route('admin.request.list') }}">申請一覧</a>
            <a href="{{ route('logout') }}">ログアウト</a>
        </nav>
    </div>
@endsection
