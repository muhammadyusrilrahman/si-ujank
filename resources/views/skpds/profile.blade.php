@extends('layouts.app')

@section('title', 'Profil Instansi')
@section('page-title', 'Kelola Profil SKPD / Instansi')

@section('content')
<div class="row">
    <div class="col-lg-8 col-xl-6">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Informasi Instansi</h3>
            </div>
            <form action="{{ route('skpds.profile.update') }}" method="POST">
                @method('PUT')
                <div class="card-body">
                    @include('skpds._form', ['skpd' => $skpd])
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

