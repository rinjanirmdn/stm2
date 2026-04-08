<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'e-Docking Control System')</title>
    @vite(['resources/css/style.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="st-app">
    <main class="st-content st-content--layout" style="padding-top: 16px;">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
