@extends('layouts.app')

@section('title', '管理者ログイン')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
<div class="container">
    <div class="login-container">
        <h2>管理者ログイン</h2>
        <form action="{{ route('admin.login') }}" method="POST" novalidate>
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

            <button type="submit" class="btn">管理者ログインする</button>
        </form>
    </div>
</div>
@endsection