@extends('layouts.admin')

@section('title', 'スタッフ一覧')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/staff_list.css') }}">
@endpush

@section('content')
<div class="staff-list-container">
    <h1 class="title"><span class="vertical-line"></span>スタッフ一覧</h1>

    <table>
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{ $user->last_name }} {{ $user->first_name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><a href="{{ route('admin.staff.attendance', ['id' => $user->id]) }}" class="detail-link">詳細</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection