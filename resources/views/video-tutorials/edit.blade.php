@extends('layouts.app')

@section('title', 'Ubah Video Tutorial')
@section('page-title', 'Ubah Video Tutorial')

@section('content')
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Form Video Tutorial</h3>
    </div>
    <form action="{{ route('video-tutorials.update', $videoTutorial) }}" method="POST">
        @method('PUT')
        <div class="card-body">
            @include('video-tutorials._form', ['videoTutorial' => $videoTutorial])
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('video-tutorials.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
