<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header___inner">
            <a href="#">
                <img src="{{ asset('logo.svg') }}" alt="ヘッダーロゴ">
            </a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>