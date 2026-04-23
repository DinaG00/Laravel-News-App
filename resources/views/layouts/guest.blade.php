<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FinPAPER') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=georgia:ital,wght@0,400;0,700;1,400;1,700&family=figtree:400,500,600&family=playfair+display:700,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="newspaper-body">
        <div class="auth-split-bg">
            <div class="auth-split-overlay"></div>
            <div class="auth-split-container">
                <!-- Left side -->
                <div class="auth-split-left">
                    <div class="auth-split-brand">
                        <span class="auth-split-logo-text">FP</span>
                        <span class="auth-split-logo-full">FinPAPER</span>
                    </div>
                    <p class="auth-split-tagline">From headlines to market insight.</p>
                </div>

                <!-- Right side -->
                <div class="auth-split-right">
                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
