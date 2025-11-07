@extends('layouts.app')

@section('title', 'Histori Login')
@section('page-title', 'Histori Login')

@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Histori Login</li>
    </ol>
@endsection

@section('content')
@php
    use Illuminate\Pagination\LengthAwarePaginator;
    use Illuminate\Support\Str;

    $items = [];
    $pagination = [
        'links' => [],
    ];
    $total = 0;

    if ($loginActivities instanceof LengthAwarePaginator) {
        $total = (int) $loginActivities->total();

        if ($loginActivities->count() > 0) {
            $items = $loginActivities->map(function ($activity) use ($isSuperAdmin) {
                $user = optional($activity->user);
                $userName = $isSuperAdmin
                    ? ($user->name ?? 'Pengguna tidak diketahui')
                    : ($user->name ?? 'Anda');

                return [
                    'id' => $activity->id ?? spl_object_hash($activity),
                    'userName' => $userName,
                    'ipAddress' => $activity->ip_address ?? 'IP tidak tercatat',
                    'userAgent' => $activity->user_agent
                        ? Str::limit($activity->user_agent, 120)
                        : null,
                    'timestamp' => optional($activity->created_at)->format('d M Y H:i') ?? '-',
                    'relativeTime' => optional($activity->created_at)->diffForHumans() ?? '',
                ];
            })->values()->all();
        }

        $pagination['links'] = collect($loginActivities->toArray()['links'] ?? [])
            ->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })
            ->all();
    }

    $props = [
        'items' => $items,
        'total' => $total,
        'isSuperAdmin' => (bool) $isSuperAdmin,
        'pagination' => $pagination,
        'routes' => [
            'index' => route('login-activities.index'),
        ],
    ];
@endphp

    <div
        id="login-activity-index-root"
        data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
    ></div>

    <noscript>
        <div class="alert alert-warning mt-3">
            Halaman histori login memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>
@endsection

