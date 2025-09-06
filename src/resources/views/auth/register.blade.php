@extends('layouts.guest')
@section('title','会員登録')
@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="form-container">
        <form action="{{ route('register') }}" method="post">
            @csrf

            <h1 class="main-title">会員登録</h1>

            <div class="form-name">
                <label for="name" class="label-name">名前</label>
                <input type="text" name="name" id="" class="input-name">
                @error('name')
                <div class="error">
                    <span class="error-message">{{ $message }}</span>
                </div>
                @enderror
            </div>

            <div class="form-email">
                <label for="email" class="label-email">メールアドレス</label>
                <input type="email" name="email" id="" class="input-email">
                @error('email')
                <div class="error">
                    <span class="error-message">{{ $message}}</span>
                </div>
                @enderror
            </div>

            <div class="form-password">
                <label for="password" class="label-password">パスワード</label>
                <input type="password" name="password" id="" class="input-password">
                @error('password')
                <div class="error">
                    <span class="error-message">{{ $message }}</span>
                </div>
                @enderror
            </div>

            <div class="form-password-confirmation">
                <label for="password_confirmation" class="label-password-confirmation">パスワード確認</label>
                <input type="password" name="password_confirmation" id="" class="input-password-confirmation">
                @error('password_confirmation')
                <div class="error">
                    <span class="error-message">
                        {{ $message}}
                    </span>
                </div>
                @enderror
            </div>

            <div class="form-button">
                <button type="submit" class="form-submit-button">
                    登録する
                </button>
            </div>

            <div class="form-login-link">
                <a href="{{ route('login') }}" class="login-link">ログインはこちら</a>
            </div>
        </form>
    </div>
</div>
@endsection