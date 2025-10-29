@extends('layouts.app')

@section('title','メール認証のお願い')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth_verify.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="card">
        <h1 class="page-title">メールアドレスの認証が必要です</h1>

        @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        @error('status')
        <div class="alert alert-warning">{{ $message }}</div>
        @enderror

        <p class="mb-4">
            ご登録のメールアドレス宛に<strong>認証用リンク</strong>を送信しました。<br>
            メール内のボタン（リンク）をクリックして認証を完了してください。
        </p>

        <p class="mb-4 text-gray-600 text-sm">
            メールが届いていない場合は、以下から<strong>認証メールを再送</strong>できます。
        </p>

        <form method="POST" action="{{ route('verification.send') }}" class="actions">
            @csrf
            <button type="submit" class="btn btn-primary">認証メールを再送する</button>
            @env('local')
            <a href="http://localhost:8025" target="_blank" rel="noopener" class="btn">MailHogを開く</a>
            @endenv
            <a href="{{ route('attendance.create') }}" class="btn btn-ghost">勤怠登録画面へ戻る</a>
        </form>

        <hr class="my-4">

        <p class="text-sm text-gray-600">
            誤ってこの画面に来た場合や、すでに認証済みで表示される際は、
            <a class="link" href="{{ route('attendance.create') }}">勤怠登録画面</a>へお進みください。
        </p>
    </div>
</div>
@endsection