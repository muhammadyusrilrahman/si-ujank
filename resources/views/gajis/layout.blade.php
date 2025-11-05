@extends('layouts.app')

@push('styles')
<style>
    .gaji-layout .card-tools {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .gaji-layout .card-tools form {
        margin: 0;
    }

    .gaji-layout .btn {
        font-size: 0.75rem !important;
        padding: 0.35rem 0.75rem !important;
        border-radius: 0.4rem !important;
        font-weight: 500;
    }

    .gaji-layout .btn i {
        font-size: 0.8rem;
        margin-right: 0.35rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid gaji-layout">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">@yield('title')</h3>
                    <div class="card-tools">
                        @yield('card-tools')
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @yield('card-body')
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @yield('page-scripts')
@endsection
