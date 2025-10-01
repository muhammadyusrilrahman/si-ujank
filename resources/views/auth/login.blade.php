@extends('layouts.auth')

@section('title', 'Masuk Aplikasi')

@section('logo')
<div class="login-logo">
    <div class="mb-2">
        <span class="brand-avatar">{{ \Illuminate\Support\Str::of(config('app.name', 'SI'))->substr(0, 2)->upper() }}</span>
    </div>
    <a href="{{ url('/') }}"><b>{{ config('app.name', 'SI-UJANK') }}</b></a>
</div>
@endsection

@section('content')
<div class="card card-outline card-primary">
    <div class="card-header text-center">
        <h3 class="card-title mb-0">Silakan Masuk</h3>
    </div>
    <div class="card-body login-card-body">
        <p class="login-box-msg">Masukkan kredensial Anda untuk mengakses dashboard.</p>

        @if ($errors->any())
            <div class="alert alert-danger small" role="alert">
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
                <label class="d-block mb-1">SKPD / Instansi</label>
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
