<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @yield('css')
    <link rel="stylesheet" href="{{ asset('css/header.css') }}">
</head>

<body>
    <header class="header">
        <div class="logo">
            <img src="{{ asset('img/logo.svg') }}" alt="ヘッダーロゴ" class="logo-img">
        </div>
    </header>
    <main>
        @yield('content')
    </main>
</body>

</html>