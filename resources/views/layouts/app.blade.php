<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
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
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-text font-weight-light">{{ config('app.name', 'AdminLTE') }}</span>
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

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
@stack('scripts')
</body>
</html>
