@extends('layouts.admin')

@section('title', '管理者勤怠詳細')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin_detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail-container">
    <h1 class="title"><span class="vertical-line"></span>勤怠詳細</h1>

        <form action="{{ route('admin.attendance.correction', ['id' => $attendance->id]) }}" method="POST">
            @csrf
            <div class="detail-card">
                <div class="row">
                    <div class="label">名前</div>
                    <div class="value">{{ $attendance->user->last_name }}&nbsp;&nbsp;&nbsp;{{ $attendance->user->first_name }}</div>
                </div>
                <div class="row">
                    <div class="label">日付</div>
                    <div class="value">
                        <span class="year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span class="month-day">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                    </div>
                </div>
                <div class="row">
                    <div class="label">出勤・退勤</div>
                    <div class="value">
                        <input type="time" name="clock_in" class="time-input" value="{{ old('clock_in', \Carbon\Carbon::parse($attendance->clock_in)->format('H:i')) }}">
                        <span class="time-separator">〜</span>
                        <input type="time" name="clock_out" class="time-input" value="{{ old('clock_out', \Carbon\Carbon::parse($attendance->clock_out)->format('H:i')) }}">
                        @if ($errors->has('clock_in') || $errors->has('clock_out'))
                            <div class="error">{{ $errors->first('clock_in') ?: $errors->first('clock_out') }}</div>
                        @endif
                    </div>
                </div>
                @foreach ($attendance->breaks as $index => $break)
                <div class="row">
                    <div class="label">休憩</div>
                    <div class="value">
                        <input type="time" name="breaks[{{ $index }}][start]" class="time-input" value="{{ old('breaks.$index.start', \Carbon\Carbon::parse($break->break_start)->format('H:i')) }}">
                        <span class="time-separator">〜</span>
                        <input type="time" name="breaks[{{ $index }}][end]" class="time-input" value="{{ old('breaks.$index.end', \Carbon\Carbon::parse($break->break_end)->format('H:i')) }}">
                        @error("breaks.$index")
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @endforeach
                @php $lastIndex = count($attendance->breaks); @endphp
                <div class="row">
                    <div class="label">休憩</div>
                    <div class="value">
                        <input type="time" name="breaks[{{ $lastIndex }}][start]" class="time-input" value="{{ old('breaks.' . $lastIndex . '.start') }}">
                        <span class="time-separator">〜</span>
                        <input type="time" name="breaks[{{ $lastIndex }}][end]" class="time-input" value="{{ old('breaks.' . $lastIndex . '.end') }}">
                        @error("breaks.$lastIndex")
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="label">備考</div>
                    <div class="value">
                        <textarea name="remarks" class="remarks-field">{{ old('remarks', $attendance->remarks) }}</textarea>
                        @error('remarks')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="button-container">
                <button type="submit" class="correction-button">修正</button>
            </div>
        </form>
</div>
@endsection
