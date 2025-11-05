@csrf
@php
    $digitalBook = $digitalBook ?? null;
@endphp
<div class="form-group">
    <label for="title">Judul</label>
    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', optional($digitalBook)->title) }}" required>
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="file_url">Tautan Buku Digital</label>
    <input type="url" name="file_url" id="file_url" class="form-control @error('file_url') is-invalid @enderror" value="{{ old('file_url', optional($digitalBook)->file_url) }}" required>
    <small class="form-text text-muted">Masukkan URL file (Google Drive, OneDrive, atau repositori lain).</small>
    @error('file_url')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="description">Deskripsi</label>
    <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', optional($digitalBook)->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group form-check">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', optional($digitalBook)->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Aktifkan buku digital</label>
</div>
