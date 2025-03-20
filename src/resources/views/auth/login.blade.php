@extends('layouts.app')

@section('title', 'ログイン')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="login-container">
        <h2>ログイン</h2>
        <form action="{{ route('login') }}" method="POST" novalidate>
            @csrf

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password">
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn">ログインする</button>
        </form>

        <p><a href="{{ route('register') }}">会員登録はこちら</a></p>
    </div>
</div>
@endsection