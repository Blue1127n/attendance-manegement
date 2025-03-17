<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'coachtech')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @stack('styles')
</head>

<body>
    <div class="container">
        <header class="header">
            <div class="header__logo">
                <img src="{{ asset('images/logo.png') }}" alt="Logo">
            </div>
        </header>

        <main>
            @yield('content')
        </main>
    </div>
</body>

</html>
