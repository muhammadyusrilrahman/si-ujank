@csrf
@php
    $videoTutorial = $videoTutorial ?? null;
@endphp
<div class="form-group">
    <label for="title">Judul</label>
    <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', optional($videoTutorial)->title) }}" required>
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="video_url">Tautan Video</label>
    <input type="url" name="video_url" id="video_url" class="form-control @error('video_url') is-invalid @enderror" value="{{ old('video_url', optional($videoTutorial)->video_url) }}" required>
    <small class="form-text text-muted">Masukkan URL video (YouTube, Vimeo, atau platform internal).</small>
    @error('video_url')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group">
    <label for="description">Deskripsi</label>
    <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description', optional($videoTutorial)->description) }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="form-group form-check">
    <input type="checkbox" name="is_active" id="is_active" value="1" class="form-check-input" {{ old('is_active', optional($videoTutorial)->is_active ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active">Aktifkan video tutorial</label>
</div>
