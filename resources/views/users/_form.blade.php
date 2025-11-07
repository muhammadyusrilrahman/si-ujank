@csrf
@php
    $currentUser = auth()->user();
    $user = $user ?? null;
    $roleOptions = $roleOptions ?? [];
    $skpds = $skpds ?? collect();

    $isEdit = $user !== null;
    $selectedSkpd = old('skpd_id', optional($user)->skpd_id);

    if (! $currentUser->isSuperAdmin() && empty($selectedSkpd)) {
        $selectedSkpd = $currentUser->skpd_id;
    }

    $roleValue = old('role', optional($user)->role);

    $skpdOptionsData = collect($skpds)->map(function ($skpdItem) {
        return [
            'id' => (string) $skpdItem->id,
            'name' => $skpdItem->name,
        ];
    })->values();

    $roleOptionsData = collect($roleOptions)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $forcedSkpdId = $currentUser->isSuperAdmin()
        ? null
        : ($currentUser->skpd_id !== null ? (string) $currentUser->skpd_id : null);

    $forcedRole = $currentUser->isUserRegular() && $roleValue
        ? (string) $roleValue
        : null;

    $props = [
        'mode' => $isEdit ? 'edit' : 'create',
        'user' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'skpd_id' => $user->skpd_id ? (string) $user->skpd_id : '',
            'role' => $user->role ? (string) $user->role : '',
        ] : null,
        'options' => [
            'skpds' => $skpdOptionsData,
            'roles' => $roleOptionsData,
        ],
        'old' => [
            'name' => old('name', optional($user)->name),
            'email' => old('email', optional($user)->email),
            'username' => old('username', optional($user)->username),
            'skpd_id' => $selectedSkpd !== null ? (string) $selectedSkpd : '',
            'role' => $roleValue !== null ? (string) $roleValue : '',
        ],
        'permissions' => [
            'canSelectSkpd' => $currentUser->isSuperAdmin(),
            'forcedSkpdId' => $forcedSkpdId,
            'canSelectRole' => ! $currentUser->isUserRegular(),
            'forcedRole' => $forcedRole,
        ],
        'messages' => [
            'passwordLabelSuffix' => $isEdit ? '(isi jika ingin mengubah)' : '',
            'passwordConfirmLabelSuffix' => $isEdit ? '(isi jika mengubah password)' : '',
            'skpdOptionalHint' => $currentUser->isSuperAdmin() ? 'opsional untuk Super Admin' : null,
        ],
        'errors' => $errors->toArray(),
    ];
@endphp

<div
    id="user-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form membutuhkan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

