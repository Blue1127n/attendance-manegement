@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff_list.css') }}">
@endpush

@section('content')
<div class="staff-list-container">
    <h1 class="title"><span class="vertical-line"></span>スタッフ一覧</h1>

    <div class="staff-table">
        <div class="row header">
            <div>名前</div>
            <div>メールアドレス</div>
            <div>月次勤怠</div>
        </div>

        @foreach ($users as $user)
            <div class="row">
                <div>{{ $user->last_name }}&nbsp;&nbsp;&nbsp;{{ $user->first_name }}</div>
                <div>{{ $user->email }}</div>
                <div><a href="{{ route('admin.staff.attendance', ['id' => $user->id]) }}" class="detail-link">詳細</a></div>
            </div>
        @endforeach
    </div>
</div>
@endsection
