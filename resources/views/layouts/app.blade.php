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
    <style>
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
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto align-items-center">
            @auth
                <li class="nav-item mr-3 text-sm text-muted">
                    <i class="fas fa-user mr-1"></i>{{ auth()->user()->name }}
                </li>
                @if (auth()->user()->skpd)
                    <li class="nav-item mr-3 text-sm text-muted">
                        <i class="fas fa-building mr-1"></i>{{ auth()->user()->skpd->name }}
                    </li>
                @endif
            @endauth
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a>
            </li>
            @auth
                <li class="nav-item">
                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link nav-link" style="padding: 0 10px;">
                            <i class="fas fa-sign-out-alt"></i> Keluar
                        </button>
                    </form>
                </li>
            @endauth
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center">
            <img src="{{ asset('SI-UJANK.png') }}" alt="{{ config('app.name', 'SI-UJANK') }}" style="height: 56px; width: auto; margin-right: 0.75rem;">
            <span class="brand-text font-weight-light">{{ config('app.name', 'SI-UJANK') }}</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @auth
                        @if (auth()->user()->isSuperAdmin())
                            <li class="nav-item">
                                <a href="{{ route('skpds.index') }}" class="nav-link {{ request()->routeIs('skpds.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-landmark"></i>
                                    <p>SKPD / Instansi</p>
                                </a>
                            </li>
                        @endif
                        @if (auth()->user()->isAdminUnit())
                            <li class="nav-item">
                                <a href="{{ route('skpds.profile') }}" class="nav-link {{ request()->routeIs('skpds.profile') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-building"></i>
                                    <p>Profil Instansi</p>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Pengguna</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('pegawais.index') }}" class="nav-link {{ request()->routeIs('pegawais.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-id-badge"></i>
                                <p>Pegawai</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('gajis.index', ['type' => 'pns']) }}" class="nav-link {{ request()->routeIs('gajis.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-file-invoice-dollar"></i>
                                <p>Gaji</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('tpps.index', ['type' => 'pns']) }}" class="nav-link {{ request()->routeIs('tpps.index') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-hand-holding-usd"></i>
                                <p>TPP</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('tpps.perhitungan') }}" class="nav-link {{ request()->routeIs('tpps.perhitungan') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-calculator"></i>
                                <p>Perhitungan TPP</p>
                            </a>
                        </li>
                        @if (auth()->user()->isSuperAdmin())
                            @php
                                $isPanduanActive = request()->routeIs('digital-books.*') || request()->routeIs('video-tutorials.*');
                            @endphp
                            <li class="nav-item has-treeview {{ $isPanduanActive ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ $isPanduanActive ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-book-reader"></i>
                                    <p>
                                        Panduan
                                        <i class="right fas fa-angle-left"></i>
                                    </p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="{{ route('digital-books.index') }}" class="nav-link {{ request()->routeIs('digital-books.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Buku Panduan</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('video-tutorials.index') }}" class="nav-link {{ request()->routeIs('video-tutorials.*') ? 'active' : '' }}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Video Tutorial</p>
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('login-activities.index') }}" class="nav-link {{ request()->routeIs('login-activities.*') ? 'active' : '' }}">
                                <i class="nav-icon fas fa-history"></i>
                                <p>Histori Login</p>
                            </a>
                        </li>
                    @endauth
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('page-title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        @yield('breadcrumb')
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-inline">Version 1.0</div>
        <strong>&copy; {{ date('Y') }} {{ config('app.name', 'AdminLTE') }}.</strong> All rights reserved.
    </footer>
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

        let skipOverlayOnce = false;

        const requestSkipOverlay = () => {
            skipOverlayOnce = true;
        };

        const showOverlay = () => {
            overlay.classList.add('active');
        };

        const hideOverlay = () => {
            overlay.classList.remove('active');
        };

        window.addEventListener('pageshow', hideOverlay);

        document.addEventListener('DOMContentLoaded', hideOverlay, { once: true });

        window.addEventListener('beforeunload', () => {
            if (skipOverlayOnce) {
                skipOverlayOnce = false;
                return;
            }
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
                requestSkipOverlay();
                return;
            }

            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            if (link.dataset.noLoader === 'true') {
                requestSkipOverlay();
                return;
            }

            showOverlay();
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.dataset.noLoader === 'true') {
                requestSkipOverlay();
                return;
            }

            showOverlay();
        });
    })();
</script>
    @stack('scripts')
    @yield('inline-scripts')
</body>
</html>
