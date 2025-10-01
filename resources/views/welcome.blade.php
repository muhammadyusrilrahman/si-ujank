@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>150</h3>
                <p>New Orders</p>
            </div>
            <div class="icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>53<sup style="font-size: 20px">%</sup></h3>
                <p>Bounce Rate</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>44</h3>
                <p>User Registrations</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>65</h3>
                <p>Unique Visitors</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <section class="col-lg-7 connectedSortable">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-area mr-1"></i> Performance Overview</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Gunakan area ini untuk menampilkan ringkasan performa aplikasi Anda. Anda dapat mengganti konten ini dengan chart atau data lainnya.</p>
            </div>
        </div>
    </section>

    <section class="col-lg-5 connectedSortable">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-inbox mr-1"></i> Aktivitas Terbaru</h3>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">Update konfigurasi aplikasi</li>
                    <li class="list-group-item">Menambahkan data pengguna baru</li>
                    <li class="list-group-item">Backup database mingguan</li>
                </ul>
            </div>
        </div>
    </section>
</div>
@endsection
