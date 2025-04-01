@extends('layouts.app')

@section('header')
    <div class="header">
        <div class="header__nav">
            <nav>
                <a href="{{ route('admin.attendance.list') }}">勤怠一覧</a>
                <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
                <a href="{{ route('admin.request.list') }}">申請一覧</a>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-button">ログアウト</button>
                </form>
            </nav>
        </div>
    </div>
@endsection



