<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ APP_NAME }} - {{ $title }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset_url('img/favicon/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset_url('img/favicon/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset_url('img/favicon/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset_url('img/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset_url('img/favicon/android-chrome-192x192.png') }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ asset_url('img/favicon/android-chrome-512x512.png') }}">

    <script>
        // Theme initialization (run immediately to prevent flash)
        (function () {
            const normalizeTheme = theme => {
                if (theme === 'auto' || theme === 'system') {
                    return 'system';
                }

                return theme === 'light' || theme === 'dark' ? theme : 'system';
            };
            const theme = normalizeTheme(localStorage.getItem('theme'));
            const actualTheme = theme === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : theme;
            document.documentElement.setAttribute('data-bs-theme', actualTheme);
        })();
    </script>

    <link rel="stylesheet" href="{{ asset_url('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset_url('bootstrap-icons/font/bootstrap-icons.min.css') }}">
</head>

<body class="page-loading">
    @include('layouts.partials.nav')

    @php
    \app\core\Session::flash();
    @endphp

    <main class="container py-4">
        {{ $slot }}
    </main>

    @include('layouts.partials.footer-nav')

    <script src="{{ asset_url('js/jquery.js') }}"></script>
    <script src="{{ asset_url('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset_url('js/theme.js') }}"></script>
    <script src="{{ asset_url('js/main.js') }}"></script>
</body>

</html>
