<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
    @yield('css')
    <title>@yield('title')</title>
</head>

<body>
    <header class="header">
        <div class="header-inner">
            <div class="logo">
                <a href="#">
                    <img src="{{ asset('img/logo.svg') }}" alt="ヘッダーロゴ" class="logo-img">
                </a>
            </div>
            <div class="nav">
                <nav class="header-nav">
                    <a href="#">勤怠</a>
                    <a href="#">勤怠一覧</a>
                    <a href="#">申請</a>
                    <a href="#">ログアウト</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>