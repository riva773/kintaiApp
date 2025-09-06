@extends('layouts.guest')
@section('title','会員登録')
@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="form-container">
        <form action="{{ route('login') }}" method="post">
            @csrf
            <h1 class="main-title">ログイン</h1>

            <div class="form-email">
                <label for="email" class="label-email">メールアドレス</label>
                <input type="email" name="email" id="" class="input-email">
                @error('email')
                <span class="error-message">{{ $message}}</span>
                @enderror
            </div>

            <div class="form-password">
                <label for="password" class="label-password">パスワード</label>
                <input type="password" name="password" id="" class="input-password">
                @error('password')
                <span class="error-message">{{ $message}}</span>
                @enderror
            </div>

            <div class="form-button">
                <button type="submit" class="form-submit-button">
                    ログインする
                </button>
            </div>

            <div class="form-register-link">
                <a href="{{ route('register') }}" class="register-link">会員登録はこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection