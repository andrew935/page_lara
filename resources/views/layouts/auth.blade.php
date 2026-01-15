<!DOCTYPE html>
<html lang="en" data-theme-mode="dark" data-header-styles="dark" data-menu-styles="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain-Monitor-PRO</title>
    <link rel="icon" href="{{ asset('img/2.jpg') }}" type="image/x-icon">
    <link href="{{ asset('build/assets/icon-fonts/icons.css') }}" rel="stylesheet">
    @include('layouts.components.styles')
    @vite(['resources/sass/app.scss'])
    @yield('styles')
</head>
<body class="authentication-page d-flex align-items-center justify-content-center min-vh-100">
    <div class="auth-wrapper w-100">
        @yield('content')
    </div>

    @include('layouts.components.scripts')
    @yield('scripts')
</body>
</html>

