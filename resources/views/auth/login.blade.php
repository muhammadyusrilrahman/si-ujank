@extends('layouts.auth')

@section('title', 'Masuk Aplikasi')

@push('styles')
<style>
    .login-box {
        width: 100%;
        max-width: 960px;
    }

    .login-card {
        display: flex;
        flex-direction: column;
        border: none;
        border-radius: 1.5rem;
        overflow: hidden;
        box-shadow: 0 18px 48px rgba(15, 23, 42, 0.18);
        background: transparent;
    }

    .login-card .logo-side {
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2.5rem 2rem;
    }

    .login-card .logo-side .login-logo {
        margin-bottom: 0;
    }

    .login-card .form-side {
        flex: 1 1 auto;
        padding: 2rem;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-card .form-side .login-form-wrapper {
        max-width: 420px;
        margin: 0 auto;
        width: 100%;
    }

    .login-card .form-side .login-form-wrapper .login-box-msg {
        color: #475569;
    }

    .login-logo .logo-container {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 1.75rem;
        background: rgba(255, 255, 255, 0.92);
        border-radius: 1.25rem;
        box-shadow: 0 28px 68px rgba(15, 23, 42, 0.18);
        text-decoration: none;
    }

    .login-logo .logo-container img {
        height: 240px;
        width: auto;
    }

    .js-enabled .login-fallback {
        display: none !important;
    }

    @media (min-width: 768px) {
        .login-card {
            flex-direction: row;
        }

        .login-card .logo-side {
            flex: 0 0 45%;
            max-width: 45%;
            padding: 3rem;
        }

        .login-card .form-side {
            flex: 0 0 55%;
            max-width: 55%;
            padding: 3rem;
        }
    }

    @media (max-width: 767.98px) {
        .login-card .logo-side {
            padding: 2rem 1.5rem;
        }

        .login-logo .logo-container {
            flex-direction: column;
            text-align: center;
        }
    }
</style>
@endpush

@section('logo')
    <span class="d-none"></span>
@endsection

@php
    $skpdOptionsData = $skpdOptions
        ->map(fn ($skpd) => [
            'id' => $skpd->id,
            'name' => $skpd->name,
        ])
        ->values();

    $selectedSkpd = $skpdOptions->firstWhere('id', old('skpd_id'));

    $loginProps = [
        'csrfToken' => csrf_token(),
        'routes' => [
            'home' => url('/'),
            'login' => url('/login'),
            'captcha' => route('captcha'),
        ],
        'assets' => [
            'logo' => asset('SI-UJANK.png'),
        ],
        'appName' => config('app.name', 'SI-UJANK'),
        'skpdOptions' => $skpdOptionsData,
        'old' => [
            'username' => old('username', ''),
            'skpd_id' => old('skpd_id'),
            'skpd_name' => optional($selectedSkpd)->name,
        ],
        'errors' => $errors->toArray(),
    ];
@endphp

@section('content')
    <div id="login-root"
         data-props='@json($loginProps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'>
    </div>

    <div class="card card-outline card-primary login-card login-fallback mt-4">
        <div class="logo-side">
            <div class="login-logo">
                <a href="{{ url('/') }}" class="logo-container">
                    <img src="{{ asset('SI-UJANK.png') }}" alt="{{ config('app.name', 'SI-UJANK') }}">
                </a>
            </div>
        </div>
        <div class="form-side">
            <div class="login-form-wrapper">
                <div class="text-center mb-4">
                    <h3 class="h4 mb-2">Silakan Masuk</h3>
                    <p class="login-box-msg mb-0">
                        Masukkan kredensial Anda untuk mengakses dashboard.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger small mb-4" role="alert">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" novalidate>
                    @csrf
                    <div class="form-group">
                        <label for="fallback-username">Nama Pengguna</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                            <input
                                id="fallback-username"
                                name="username"
                                type="text"
                                class="form-control @error('username') is-invalid @enderror"
                                placeholder="Masukkan username"
                                autocomplete="username"
                                required
                                value="{{ old('username', '') }}"
                            >
                        </div>
                        @error('username')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="fallback-password">Kata Sandi</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            </div>
                            <input
                                id="fallback-password"
                                name="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="Masukkan kata sandi"
                                autocomplete="current-password"
                                required
                            >
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="fallback-skpd">SKPD / Instansi</label>
                        <select
                            id="fallback-skpd"
                            name="skpd_id"
                            class="form-control @error('skpd_id') is-invalid @enderror"
                        >
                            <option value="">Pilih SKPD / Instansi</option>
                            @foreach ($skpdOptions as $skpd)
                                <option value="{{ $skpd->id }}" @selected(old('skpd_id') == $skpd->id)>
                                    {{ $skpd->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            Wajib dipilih untuk Admin Unit / User Reguler.
                        </small>
                        @error('skpd_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="fallback-captcha">Captcha</label>
                        <div class="d-flex align-items-center mb-2">
                            <img
                                id="fallback-captcha"
                                src="{{ route('captcha') }}?t={{ time() }}"
                                alt="Captcha"
                                class="img-fluid"
                                style="height: 48px;"
                            >
                            <button
                                type="button"
                                class="btn btn-link btn-sm ml-2 p-0"
                                onclick="document.getElementById('fallback-captcha').src='{{ route('captcha') }}?t=' + Date.now(); return false;"
                            >
                                Muat ulang
                            </button>
                        </div>
                        <input
                            type="text"
                            name="captcha"
                            class="form-control @error('captcha') is-invalid @enderror"
                            placeholder="Masukkan kode captcha"
                            required
                        >
                        @error('captcha')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>

    <noscript>
        <div class="alert alert-warning mt-3">
            Halaman login membutuhkan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>
@endsection

