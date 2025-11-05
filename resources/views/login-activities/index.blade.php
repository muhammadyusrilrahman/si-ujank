@extends('layouts.app')

@section('title', 'Histori Login')
@section('page-title', 'Histori Login')

@section('breadcrumb')
    <ol class="breadcrumb float-sm-right">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Histori Login</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-history mr-2"></i> Daftar Histori Login
                    </h3>
                    <span class="badge badge-light">
                        Total: {{ $loginActivities->total() }}
                    </span>
                </div>
                <div class="card-body p-0">
                    @if ($loginActivities->isEmpty())
                        <div class="p-4 text-center text-muted">
                            Belum ada histori login yang tercatat.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        @if ($isSuperAdmin)
                                            <th style="width: 25%;">Pengguna</th>
                                        @endif
                                        <th style="width: 25%;">Alamat IP</th>
                                        <th class="d-none d-lg-table-cell">Perangkat</th>
                                        <th style="width: 20%;">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($loginActivities as $activity)
                                        <tr>
                                            @if ($isSuperAdmin)
                                                <td>
                                                    {{ optional($activity->user)->name ?? 'Pengguna tidak diketahui' }}
                                                </td>
                                            @endif
                                            <td>
                                                <span class="font-weight-semibold">{{ $activity->ip_address ?? 'IP tidak tercatat' }}</span>
                                            </td>
                                            <td class="d-none d-lg-table-cell">
                                                {{ $activity->user_agent ? \Illuminate\Support\Str::limit($activity->user_agent, 120) : 'Perangkat tidak tercatat' }}
                                            </td>
                                            <td>
                                                <span class="d-block">{{ optional($activity->created_at)->format('d M Y H:i') }}</span>
                                                <small class="text-muted">{{ optional($activity->created_at)->diffForHumans() }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @if ($loginActivities->hasPages())
                    <div class="card-footer">
                        {{ $loginActivities->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
