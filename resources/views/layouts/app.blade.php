@php
    $appName = config('app.name', 'SI-UJANK');
    $appLogo = asset('SI-UJANK.png');
    $appVersion = 'Version 1.0';
    $documentTitle = trim($__env->yieldContent('title', $appName)) ?: $appName;
    $pageTitle = trim($__env->yieldContent('page-title', $documentTitle)) ?: $documentTitle;
    $breadcrumbHtml = trim($__env->yieldContent('breadcrumb'));
    $contentHtml = $__env->yieldContent('content');

    $authUser = auth()->user();
    $userData = [
        'name' => optional($authUser)->name ?? '',
        'skpd' => optional(optional($authUser)->skpd)->name ?? '',
        'isSuperAdmin' => optional($authUser)->isSuperAdmin() ?? false,
        'isAdminUnit' => optional($authUser)->isAdminUnit() ?? false,
    ];

    $navigation = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'fas fa-home',
            'url' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
        ],
    ];

    if ($authUser) {
        if ($authUser->isSuperAdmin()) {
            $navigation[] = [
                'key' => 'skpds',
                'label' => 'SKPD / Instansi',
                'icon' => 'fas fa-landmark',
                'url' => route('skpds.index'),
                'active' => request()->routeIs('skpds.*'),
            ];
        }

        if ($authUser->isAdminUnit()) {
            $navigation[] = [
                'key' => 'skpd-profile',
                'label' => 'Profil Instansi',
                'icon' => 'fas fa-building',
                'url' => route('skpds.profile'),
                'active' => request()->routeIs('skpds.profile'),
            ];
        }

        $navigation = array_merge($navigation, [
            [
                'key' => 'users',
                'label' => 'Pengguna',
                'icon' => 'fas fa-users',
                'url' => route('users.index'),
                'active' => request()->routeIs('users.*'),
            ],
            [
                'key' => 'pegawais',
                'label' => 'Pegawai',
                'icon' => 'fas fa-id-badge',
                'url' => route('pegawais.index'),
                'active' => request()->routeIs('pegawais.*'),
            ],
            [
                'key' => 'gajis',
                'label' => 'Gaji',
                'icon' => 'fas fa-file-invoice-dollar',
                'url' => route('gajis.index', ['type' => 'pns']),
                'active' => request()->routeIs('gajis.*'),
            ],
            [
                'key' => 'tpps',
                'label' => 'TPP',
                'icon' => 'fas fa-hand-holding-usd',
                'url' => route('tpps.index', ['type' => 'pns']),
                'active' => request()->routeIs('tpps.index'),
            ],
            [
                'key' => 'tpps-calculation',
                'label' => 'Perhitungan TPP',
                'icon' => 'fas fa-calculator',
                'url' => route('tpps.perhitungan'),
                'active' => request()->routeIs('tpps.perhitungan'),
            ],
        ]);

        if ($authUser->isSuperAdmin()) {
            $isPanduanActive = request()->routeIs('digital-books.*') || request()->routeIs('video-tutorials.*');
            $navigation[] = [
                'key' => 'panduan',
                'label' => 'Panduan',
                'icon' => 'fas fa-book-reader',
                'active' => $isPanduanActive,
                'expanded' => $isPanduanActive,
                'children' => [
                    [
                        'key' => 'digital-books',
                        'label' => 'Buku Panduan',
                        'url' => route('digital-books.index'),
                        'active' => request()->routeIs('digital-books.*'),
                    ],
                    [
                        'key' => 'video-tutorials',
                        'label' => 'Video Tutorial',
                        'url' => route('video-tutorials.index'),
                        'active' => request()->routeIs('video-tutorials.*'),
                    ],
                ],
            ];
        }

        $navigation[] = [
            'key' => 'login-activities',
            'label' => 'Histori Login',
            'icon' => 'fas fa-history',
            'url' => route('login-activities.index'),
            'active' => request()->routeIs('login-activities.*'),
        ];
    }

    $flashTypes = ['success', 'error', 'warning', 'info', 'status'];
    $flashMessages = collect($flashTypes)
        ->flatMap(function ($type) {
            $value = session()->get($type);
            if (empty($value)) {
                return [];
            }
            $messages = is_array($value) ? $value : [$value];
            $mappedType = $type === 'status' ? 'success' : $type;
            return collect($messages)->map(fn ($message) => [
                'type' => $mappedType,
                'message' => e((string) $message),
            ]);
        })
        ->values()
        ->all();

    $normalizedNavigation = collect($navigation)->map(function ($item, $index) {
        $item['key'] = $item['key'] ?? 'nav-item-' . $index;
        $item['icon'] = $item['icon'] ?? 'fas fa-circle';
        $item['children'] = collect($item['children'] ?? [])->map(function ($child, $childIndex) use ($item) {
            $child['key'] = $child['key'] ?? ($item['key'] . '-child-' . $childIndex);
            $child['active'] = $child['active'] ?? false;
            $child['url'] = $child['url'] ?? '#';
            return $child;
        })->values()->all();
        $item['expanded'] = $item['expanded'] ?? false;
        $item['active'] = $item['active'] ?? false;
        $item['url'] = $item['url'] ?? '#';
        return $item;
    })->values()->all();

    $layoutProps = [
        'app' => [
            'name' => $appName,
            'logo' => $appLogo,
            'version' => $appVersion,
        ],
        'user' => $userData,
        'navigation' => $normalizedNavigation,
        'page' => [
            'title' => $pageTitle,
            'breadcrumbHtml' => $breadcrumbHtml,
            'contentHtml' => $contentHtml,
        ],
        'flashes' => $flashMessages,
        'routes' => [
            'dashboard' => route('dashboard'),
            'logout' => route('logout'),
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $documentTitle }}</title>

    <link rel="icon" type="image/png" href="{{ $appLogo }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .vue-app-loaded .vue-shell-fallback {
            display: none !important;
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div
        id="app-shell-root"
        data-props='@json($layoutProps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
    >
        <div class="wrapper vue-shell-fallback">
            <nav class="main-header navbar navbar-expand navbar-white navbar-light">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-bars"></i></span>
                    </li>
                    <li class="nav-item d-none d-sm-inline-block">
                        <a href="{{ route('dashboard') }}" class="nav-link">Dashboard</a>
                    </li>
                </ul>
                <ul class="navbar-nav ml-auto align-items-center">
                    @if ($userData['name'])
                        <li class="nav-item mr-3 text-sm text-muted">
                            <i class="fas fa-user mr-1"></i>{{ $userData['name'] }}
                        </li>
                    @endif
                    @if ($userData['skpd'])
                        <li class="nav-item mr-3 text-sm text-muted">
                            <i class="fas fa-building mr-1"></i>{{ $userData['skpd'] }}
                        </li>
                    @endif
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-expand-arrows-alt"></i></span>
                    </li>
                    @if ($authUser)
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-link nav-link" style="padding: 0 10px;">
                                    <i class="fas fa-sign-out-alt"></i> Keluar
                                </button>
                            </form>
                        </li>
                    @endif
                </ul>
            </nav>

            <aside class="main-sidebar sidebar-dark-primary elevation-4">
                <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center">
                    <img src="{{ $appLogo }}" alt="{{ $appName }}" style="height: 56px; width: auto; margin-right: 0.75rem;">
                    <span class="brand-text font-weight-light">{{ $appName }}</span>
                </a>
                <div class="sidebar">
                    <nav class="mt-2">
                        <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                            @foreach ($normalizedNavigation as $item)
                                @php
                                    $hasChildren = ! empty($item['children']);
                                @endphp
                                <li class="nav-item {{ $hasChildren ? 'has-treeview ' . ($item['expanded'] ? 'menu-open' : '') : '' }}">
                                    @if ($hasChildren)
                                        <a href="#" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                                            <i class="nav-icon {{ $item['icon'] }}"></i>
                                            <p>
                                                {{ $item['label'] }}
                                                <i class="right fas fa-angle-left"></i>
                                            </p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            @foreach ($item['children'] as $child)
                                                <li class="nav-item">
                                                    <a href="{{ $child['url'] }}" class="nav-link {{ $child['active'] ? 'active' : '' }}">
                                                        <i class="far fa-circle nav-icon"></i>
                                                        <p>{{ $child['label'] }}</p>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <a href="{{ $item['url'] }}" class="nav-link {{ $item['active'] ? 'active' : '' }}">
                                            <i class="nav-icon {{ $item['icon'] }}"></i>
                                            <p>{{ $item['label'] }}</p>
                                        </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                </div>
            </aside>

            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1 class="m-0">{{ $pageTitle }}</h1>
                            </div>
                            @if ($breadcrumbHtml)
                                <div class="col-sm-6">
                                    {!! $breadcrumbHtml !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <section class="content">
                    <div class="container-fluid">
                        @foreach ($flashMessages as $flash)
                            <div class="alert alert-{{ $flash['type'] === 'error' ? 'danger' : $flash['type'] }} alert-dismissible fade show" role="alert">
                                {!! $flash['message'] !!}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endforeach
                        {!! $contentHtml !!}
                    </div>
                </section>
            </div>

            <footer class="main-footer">
                <div class="float-right d-none d-sm-inline">{{ $appVersion }}</div>
                <strong>&copy; {{ date('Y') }} {{ $appName }}.</strong> All rights reserved.
            </footer>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    @stack('scripts')
    @yield('inline-scripts')
</body>
</html>

