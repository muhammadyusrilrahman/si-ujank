@csrf
<div class="form-group">
    <label for="name">Nama SKPD / Instansi</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $skpd->name ?? '') }}" required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="alias">Alias / Singkatan <span class="text-muted small">(Opsional)</span></label>
    <input type="text" name="alias" id="alias" class="form-control @error('alias') is-invalid @enderror" value="{{ old('alias', $skpd->alias ?? '') }}">
    @error('alias')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="npwp">NPWP Instansi <span class="text-muted small">(Opsional)</span></label>
    <input type="text" name="npwp" id="npwp" class="form-control @error('npwp') is-invalid @enderror" value="{{ old('npwp', $skpd->npwp ?? '') }}" maxlength="25" placeholder="99.999.999.9-999.999">
    @error('npwp')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
