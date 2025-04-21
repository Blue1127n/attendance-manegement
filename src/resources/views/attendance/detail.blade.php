@extends('layouts.user')

@section('title', '勤怠詳細')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endpush

@section('content')
<div class="attendance-detail-container">
    <h1 class="title"><span class="vertical-line"></span>勤怠詳細</h1>

    @if (isset($request) && in_array($request->status, ['承認待ち', '承認済み']))
        {{-- 非編集モード（承認済み or 承認待ち） --}}

        <div class="detail-card">
            <div class="row">
                <div class="label">名前</div>
                <div class="value">{{ $attendance->user->last_name }}&nbsp;&nbsp;&nbsp;{{ $attendance->user->first_name }}</div>
            </div>
            <div class="row">
                <div class="label">日付</div>
                <div class="value">
                        <span class="year-static">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span class="month-day">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                </div>
            </div>
            <div class="row">
                <div class="label">出勤・退勤</div>
                <div class="value">
                    <span class="time-text">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</span>
                    <span class="time-separator">〜</span>
                    <span class="time-text">{{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}</span>
                </div>
            </div>

            {{-- これは「複数ある休憩時間を順番に表示するためのループ処理」です
                $attendance->breaks は、その勤怠データに紐づく休憩時間一覧
                たとえば、2回休憩した日には2件分のデータが入っています
                as $index => $break の形で書くと：$index：ループの番号（0から始まる）$break：その回の休憩データ（1件） --}}
            @foreach ($attendance->breaks as $index => $break)
            <div class="row">
                {{-- もし $index が 0 なら「休憩」と表示し、それ以外（＝1回目以降）なら「休憩2」「休憩3」…と表示する
                    各休憩時間を1セットずつ表示 1回目は「休憩」、2回目からは「休憩2」「休憩3」…になるように表示 --}}
                <div class="label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</div>
                <div class="value">
                    <span class="time-text">{{ \Carbon\Carbon::parse($break->break_start)->format('H:i') }}</span>
                    <span class="time-separator">〜</span>
                    <span class="time-text">{{ \Carbon\Carbon::parse($break->break_end)->format('H:i') }}</span>
                </div>
            </div>
            @endforeach
            <div class="row">
                <div class="label">備考</div>
                <div class="value">{{ $attendance->remarks }}</div>
            </div>
        </div>

        @if ($request->status === '承認待ち')
        <div class="approval-message-container">
            <p class="approval-message">＊承認待ちのため修正はできません。</p>
        </div>
        @elseif ($request->status === '承認済み')
        <div class="approval-message-container">
            <p class="approval-message">＊承認済みです。</p>
        </div>
        @endif

    @else
        {{-- 編集モード（申請が存在しない時だけ） --}}
        <form action="{{ route('user.attendance.correction', ['id' => $attendance->id]) }}" method="POST">
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
                    <div class="label">{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</div>
                    <div class="value">
                        <input type="time" name="breaks[{{ $index }}][start]" class="time-input" value="{{ old('breaks.' . $index . '.start', \Carbon\Carbon::parse($break->break_start)->format('H:i')) }}">
                        <span class="time-separator">〜</span>
                        <input type="time" name="breaks[{{ $index }}][end]" class="time-input" value="{{ old('breaks.' . $index . '.end', \Carbon\Carbon::parse($break->break_end)->format('H:i')) }}">
                        @error("breaks.$index.start")
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                @endforeach
                @php $lastIndex = count($attendance->breaks); @endphp
                <div class="row">
                    <div class="label">休憩{{ $lastIndex === 0 ? '' : $lastIndex + 1 }}</div>
                    <div class="value">
                        <input type="time" name="breaks[{{ $lastIndex }}][start]" class="time-input" value="{{ old('breaks.' . $lastIndex . '.start') }}">
                        <span class="time-separator">〜</span>
                        <input type="time" name="breaks[{{ $lastIndex }}][end]" class="time-input" value="{{ old('breaks.' . $lastIndex . '.end') }}">
                        @error("breaks.$lastIndex.start")
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
    @endif
</div>
@endsection
