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

@section('content')
<div class="card card-outline card-primary login-card">
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
                <p class="login-box-msg mb-0">Masukkan kredensial Anda untuk mengakses dashboard.</p>
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

            <form action="{{ route('login.attempt') }}" method="POST" autocomplete="off">
                @csrf
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" placeholder="Username" value="{{ old('username') }}" required autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                </div>

                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                </div>

            <div class="form-group mb-3">
                <label class="d-block mb-1">SKPD / Instansi <small class="text-muted">(wajib untuk non Super Admin)</small></label>
                <div class="input-group dropdown">
                        <div class="dropdown-menu w-100 p-0" id="skpd-dropdown">
                            <div class="p-2 border-bottom">
                                <input type="text" class="form-control" id="skpd-search" placeholder="Cari SKPD / Instansi">
                            </div>
                            <div class="list-group list-group-flush" id="skpd-list" style="max-height: 220px; overflow-y: auto;">
                                @foreach ($skpdOptions as $skpd)
                                    <button type="button" class="list-group-item list-group-item-action skpd-option" data-id="{{ $skpd->id }}" data-name="{{ $skpd->name }}" data-search="{{ strtolower($skpd->name) }}">
                                        {{ $skpd->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <input type="text" readonly class="form-control @error('skpd_id') is-invalid @enderror" id="skpd-display" placeholder="Pilih SKPD / Instansi" value="{{ optional($skpdOptions->firstWhere('id', old('skpd_id')))->name }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                    </div>
                    <input type="hidden" name="skpd_id" id="skpd-id" value="{{ old('skpd_id') }}">
                </div>

                <div class="form-group">
                    <label class="d-block">Captcha</label>
                    <div class="d-flex align-items-center mb-2">
                        <img src="{{ route('captcha') }}" alt="Captcha" class="img-fluid border rounded" id="captcha-image" style="height: 56px; width: 180px; object-fit: cover;">
                        <button class="btn btn-outline-secondary ml-2" type="button" id="refresh-captcha" aria-label="Refresh captcha">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                    <input type="text" name="captcha" class="form-control @error('captcha') is-invalid @enderror" placeholder="Masukkan kode keamanan" required>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt mr-1"></i> Masuk Dashboard
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const refreshButton = document.getElementById('refresh-captcha');
        const captchaImage = document.getElementById('captcha-image');
        const searchInput = document.getElementById('skpd-search');
        const dropdown = document.getElementById('skpd-dropdown');
        const displayInput = document.getElementById('skpd-display');
        const list = document.getElementById('skpd-list');
        const hiddenInput = document.getElementById('skpd-id');

        const refreshCaptcha = () => {
            const url = new URL('{{ route('captcha') }}', window.location.origin);
            url.searchParams.set('t', Date.now());
            captchaImage.src = url.href;
        };

        const toggleDropdown = (show) => {
            dropdown.classList.toggle('show', show);
            displayInput.setAttribute('aria-expanded', show ? 'true' : 'false');
        };

        const filterOptions = () => {
            const query = searchInput.value.trim().toLowerCase();
            const options = dropdown.querySelectorAll('.skpd-option');
            let anyVisible = false;

            options.forEach(option => {
                const matches = option.dataset.search.includes(query);
                option.classList.toggle('d-none', !matches);
                if (matches) {
                    anyVisible = true;
                }
            });

            dropdown.classList.toggle('dropdown-empty', !anyVisible);
        };

        const selectOption = (option) => {
            displayInput.value = option.dataset.name;
            hiddenInput.value = option.dataset.id;
            toggleDropdown(false);
        };

        displayInput.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleDropdown(!dropdown.classList.contains('show'));

            if (dropdown.classList.contains('show')) {
                searchInput.focus({ preventScroll: true });
                searchInput.select();
            }
        });

        list.addEventListener('click', (event) => {
            const option = event.target.closest('.skpd-option');
            if (!option) {
                return;
            }

            selectOption(option);
        });

        document.addEventListener('click', (event) => {
            if (!dropdown.contains(event.target) && event.target !== displayInput) {
                toggleDropdown(false);
            }
        });

        searchInput.addEventListener('input', filterOptions);

        if (hiddenInput.value) {
            const initial = dropdown.querySelector(`.skpd-option[data-id="${hiddenInput.value}"]`);
            if (initial) {
                selectOption(initial);
            }
        }

        refreshButton.addEventListener('click', refreshCaptcha);
        captchaImage.addEventListener('click', refreshCaptcha);
    });
</script>
@endpush
