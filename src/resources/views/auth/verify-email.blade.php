@extends('layouts.app')

@section('title', 'メール認証')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="verify-email-container">
    <h2>
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </h2>

        <a href="http://localhost:8025/" class="verify-button">認証はこちらから</a>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="resend-button">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection
