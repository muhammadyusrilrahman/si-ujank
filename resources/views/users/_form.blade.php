@csrf
@php
    $currentUser = auth()->user();
    $isEdit = isset($user) && $user;
    $selectedSkpd = old('skpd_id', optional($user)->skpd_id);
    if (! $currentUser->isSuperAdmin() && empty($selectedSkpd)) {
        $selectedSkpd = $currentUser->skpd_id;
    }
    $roleValue = old('role', optional($user)->role);
@endphp
<div class="form-group">
    <label for="name">Nama Lengkap</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', optional($user)->name) }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="email">Email</label>
    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', optional($user)->email) }}" required>
    @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="username">Username</label>
    <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', optional($user)->username) }}" required>
    @error('username')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="skpd_id">SKPD / Instansi @if ($currentUser->isSuperAdmin())<small class="text-muted">(opsional untuk Super Admin)</small>@endif</label>
    <select name="skpd_id" id="skpd_id" class="form-control @error('skpd_id') is-invalid @enderror" {{ $currentUser->isSuperAdmin() ? '' : 'disabled' }}>
        <option value="" {{ $selectedSkpd ? '' : 'selected' }} {{ $currentUser->isSuperAdmin() ? '' : 'disabled' }}>Tidak terikat pada SKPD</option>
        @foreach ($skpds as $skpd)
            <option value="{{ $skpd->id }}" {{ (string) $selectedSkpd === (string) $skpd->id ? 'selected' : '' }}>{{ $skpd->name }}</option>
        @endforeach
    </select>
    @if (! $currentUser->isSuperAdmin())
        <input type="hidden" name="skpd_id" value="{{ $currentUser->skpd_id }}">
    @endif
    @error('skpd_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="role">Peran Pengguna</label>
    <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required {{ $currentUser->isUserRegular() ? 'disabled' : '' }}>
        <option value="" disabled {{ $roleValue ? '' : 'selected' }}>Pilih peran</option>
        @foreach ($roleOptions as $value => $label)
            <option value="{{ $value }}" {{ $roleValue === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @if ($currentUser->isUserRegular())
        <input type="hidden" name="role" value="{{ $roleValue }}">
    @endif
    @error('role')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="password">Password {{ $isEdit ? '(isi jika ingin mengubah)' : '' }}</label>
    <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" {{ $isEdit ? '' : 'required' }}>
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="password_confirmation">Konfirmasi Password {{ $isEdit ? '(isi jika mengubah password)' : '' }}</label>
    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" {{ $isEdit ? '' : 'required' }}>
</div>
