@extends('layouts.user')

@section('title', '申請一覧')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/request-list.css') }}">
@endpush

@section('content')
<div class="request-list-container">
    <h1 class="title"><span class="vertical-line"></span>申請一覧</h1>

    <ul class="tabs">
        <li class="active" data-tab="pending">承認待ち</li>
        <li data-tab="approved">承認済み</li>
    </ul>

    <div class="tab-content" id="pending">
        <table>
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending as $request)
                    <tr>
                        <td>{{ $request->status }}</td>
                        <td>{{ $request->user->last_name }} {{ $request->user->first_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                        <td>{{ $request->remarks }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                        <td><a href="{{ route('user.attendance.detail', $request->attendance_id) }}" class="detail-link">詳細</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="tab-content" id="approved" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($approved as $request)
                    <tr>
                        <td>{{ $request->status }}</td>
                        <td>{{ $request->user->last_name }} {{ $request->user->first_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                        <td>{{ $request->remarks }}</td>
                        <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                        <td><a href="{{ route('user.attendance.detail', $request->attendance_id) }}" class="detail-link">詳細</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- タブ切り替えのため --}}
<script>
    document.querySelectorAll('.tabs li').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tabs li').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(tc => tc.style.display = 'none');
            tab.classList.add('active');
            document.getElementById(tab.dataset.tab).style.display = 'block';
        });
    });
</script>
@endsection
