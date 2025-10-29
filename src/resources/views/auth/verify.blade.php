@extends('layouts.app')

@section('title','メール認証')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth_verify.css') }}">
@endsection

@section('content')
<div class="verify-wrap">
    <div class="verify-inner" role="main" aria-labelledby="verify-title">


        @if (session('status'))
        <div class="alert success" role="status">{{ session('status') }}</div>
        @endif

        <p id="verify-title" class="lead">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。</p>

        <form method="POST" action="{{ route('verification.send') }}" class="cta-form" aria-label="認証メールを再送して認証を進める">
            @csrf
            <a href="http://localhost:8025" target="_blank" rel="noopener" class="cta-btn">認証はこちらから</a>
        </form>

        <form method="POST" action="{{ route('verification.send') }}" class="resend-form" aria-label="認証メールを再送する">
            @csrf
            <button type="submit" class="resend-link">認証メールを再送する</button>
        </form>
    </div>
</div>
@endsection