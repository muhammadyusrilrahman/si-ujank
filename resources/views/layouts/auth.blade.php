<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <link rel="icon" type="image/png" href="{{ asset('SI-UJANK.png') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .vue-login-loaded .login-fallback {
            display: none !important;
        }

        .loading-overlay {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(2px);
            z-index: 2000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease-in-out;
            visibility: hidden;
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
            visibility: visible;
        }

        .loading-overlay .spinner {
            width: 3.5rem;
            height: 3.5rem;
            border: 0.4rem solid rgba(255, 255, 255, 0.28);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: loader-spin 0.8s linear infinite;
        }

        .loading-overlay .loading-text {
            margin-top: 1.25rem;
            font-weight: 600;
            color: #ffffff;
            letter-spacing: 0.08em;
        }

        @keyframes loader-spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition login-page">
<div class="login-box">
    @hasSection('logo')
        @yield('logo')
    @else
        <div class="login-logo">
            <a href="{{ url('/') }}"><b>{{ config('app.name', 'AdminLTE') }}</b></a>
        </div>
    @endif

    @yield('content')
</div>

<div id="global-loading-overlay" class="loading-overlay" role="status" aria-live="polite" aria-label="Memuat halaman">
    <div class="text-center">
        <div class="spinner"></div>
        <div class="loading-text">Memuat...</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script>
    (function () {
        const overlay = document.getElementById('global-loading-overlay');
        if (!overlay) {
            return;
        }

        const showOverlay = () => overlay.classList.add('active');
        const hideOverlay = () => overlay.classList.remove('active');

        window.addEventListener('pageshow', hideOverlay);
        document.addEventListener('DOMContentLoaded', hideOverlay, { once: true });

        window.addEventListener('beforeunload', () => {
            showOverlay();
        });

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) {
                return;
            }

            if (event.defaultPrevented) {
                return;
            }

            if (link.target && link.target !== '_self') {
                return;
            }

            if (link.hasAttribute('download') || link.getAttribute('href') === null) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            if (link.dataset.noLoader === 'true') {
                return;
            }

            showOverlay();
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.dataset.noLoader === 'true') {
                return;
            }

            showOverlay();
        });
    })();
</script>
@stack('scripts')
</body>
</html>
