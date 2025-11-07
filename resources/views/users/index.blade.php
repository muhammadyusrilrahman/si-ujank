@extends('layouts.app')

@section('title', 'Data Pengguna')
@section('page-title', 'Daftar Pengguna')

@php
    use App\Models\User;
    use Illuminate\Pagination\LengthAwarePaginator;

    $currentUser = auth()->user();
    $statusMessage = session('status');
    $errorMessage = $errors->first('user');

    $roleBadges = [
        User::ROLE_SUPER_ADMIN => ['label' => 'Super Admin', 'variant' => 'danger'],
        User::ROLE_ADMIN_UNIT => ['label' => 'Admin Unit', 'variant' => 'primary'],
        User::ROLE_USER_REGULAR => ['label' => 'User Reguler', 'variant' => 'secondary'],
    ];

    $items = [];
    $pagination = null;

    if ($users instanceof LengthAwarePaginator && $users->count() > 0) {
        $items = $users->map(function ($user) use ($currentUser, $roleBadges) {
            $canManage = $currentUser->isSuperAdmin() || ($currentUser->isAdminUnit() && $user->skpd_id === $currentUser->skpd_id);
            $canEdit = $canManage;
            $canDelete = $canManage && ! $currentUser->is($user);

            $roleMeta = $roleBadges[$user->role] ?? [
                'label' => ucfirst(str_replace('_', ' ', $user->role ?? '')),
                'variant' => 'secondary',
            ];

            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'skpd' => optional($user->skpd)->name ?? '-',
                'role' => $roleMeta,
                'can_edit' => $canEdit,
                'edit_url' => $canEdit ? route('users.edit', $user) : null,
                'can_delete' => $canDelete,
                'delete_url' => $canDelete ? route('users.destroy', $user) : null,
            ];
        })->values()->all();

        $paginatorArray = $users->toArray();
        $pagination = [
            'from' => $users->firstItem(),
            'links' => collect($paginatorArray['links'] ?? [])->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })->all(),
        ];
    }

    $canCreate = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();

    $props = [
        'searchQuery' => (string) ($search ?? ''),
        'routes' => [
            'index' => route('users.index'),
            'create' => $canCreate ? route('users.create') : null,
        ],
        'permissions' => [
            'canCreate' => $canCreate,
        ],
        'statusMessage' => $statusMessage,
        'errorMessage' => $errorMessage,
        'items' => $items,
        'pagination' => $pagination,
        'csrfToken' => csrf_token(),
    ];
@endphp

@section('content')
    <div
        id="user-index-root"
        data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
    ></div>

    <noscript>
        <div class="alert alert-warning mt-3">
            Halaman daftar pengguna memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>
@endsection

