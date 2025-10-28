@extends('layouts.guest')
@section('title','管理者ログイン')
@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css')}}">
@endsection
@section('content')

<div class="container">
    <div class="form-container">
        <form action="{{ route('login') }}" method="post">
            @csrf
            <h1 class="main-title">管理者ログイン</h1>

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
                    管理者ログインする
                </button>
            </div>
        </form>
    </div>
</div>
@endsection