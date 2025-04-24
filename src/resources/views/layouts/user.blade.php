@extends('layouts.app')

@section('header')
<div class="header">
    <div class="header__nav">
        <nav>
            <a href="{{ route('user.attendance') }}">勤怠</a>
            <a href="{{ route('user.attendance.list') }}">勤怠一覧</a>
            <a href="{{ route('user.request.list') }}">申請</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="logout-button">ログアウト</button>
            </form>
        </nav>
    </div>
</div>
@endsection
