@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@php
    $stats = $stats ?? [
        'users' => 0,
        'pegawai_pns_cpns' => 0,
        'pegawai_pppk' => 0,
    ];
    $skpdLabel = $skpdLabel ?? 'SKPD Anda';
    $isSuperAdmin = $isSuperAdmin ?? auth()->user()->isSuperAdmin();
    $isAdminUnit = $isAdminUnit ?? auth()->user()->isAdminUnit();
    $loginActivities = $loginActivities ?? collect();
    $feedbacks = $feedbacks ?? collect();
@endphp

@section('content')
@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-4 col-md-6 col-12">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($stats['users']) }}</h3>
                <p>Pengguna {{ $skpdLabel }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-users-cog"></i>
            </div>
            <a href="{{ route('users.index') }}" class="small-box-footer">
                Lihat pengguna <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 col-12">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($stats['pegawai_pns_cpns']) }}</h3>
                <p>Pegawai PNS &amp; CPNS {{ $skpdLabel }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-id-badge"></i>
            </div>
            <a href="{{ route('pegawais.index') }}" class="small-box-footer">
                Kelola pegawai <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-xl-4 col-md-6 col-12">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($stats['pegawai_pppk']) }}</h3>
                <p>Pegawai PPPK {{ $skpdLabel }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <a href="{{ route('pegawais.index') }}" class="small-box-footer">
                Kelola pegawai <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <section class="col-lg-7 connectedSortable">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> Tentang Aplikasi</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    <strong>SI-UJANK</strong> adalah aplikasi penunjang kebutuhan penatausahaan keuangan yang mana aplikasi ini dibangun dengan konsep data preparation.
                    Pengguna akan diberikan kemudahan untuk menyiapkan berkas dan data gaji, TPP, e-bupot dan sebagainya.
                </p>
            </div>
        </div>
    </section>

    <section class="col-lg-5 connectedSortable">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-history mr-1"></i> Histori Masuk Aplikasi</h3>
                <a href="{{ route('login-activities.index') }}" class="btn btn-sm btn-outline-primary">
                    Lihat semua
                </a>
            </div>
            <div class="card-body">
                @if ($loginActivities->isNotEmpty())
                    <div class="mb-3 text-muted small">
                        Menampilkan 3 histori login terbaru.
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ($loginActivities as $activity)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-semibold">
                                            <i class="fas fa-sign-in-alt text-primary mr-2"></i>
                                            @if ($isSuperAdmin)
                                                {{ optional($activity->user)->name ?? 'Pengguna tidak diketahui' }}
                                            @else
                                                Anda
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">
                                            {{ $activity->ip_address ?? 'IP tidak tercatat' }}
                                            @if (!empty($activity->user_agent))
                                                - {{ \Illuminate\Support\Str::limit($activity->user_agent, 60) }}
                                            @endif
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        {{ optional($activity->created_at)->diffForHumans() }}
                                    </small>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-center text-muted">
                        Belum ada histori login yang tercatat.
                    </div>
                @endif
            </div>
        </div>
    </section>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-book mr-2"></i> Buku Panduan Terbaru
                </h3>
                @if ($isSuperAdmin)
                    <a href="{{ route('digital-books.index') }}" class="btn btn-sm btn-outline-primary">
                        Kelola Buku
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                @if(!empty($digitalBooks) && $digitalBooks->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach ($digitalBooks as $book)
                            <a href="{{ $book->file_url }}" target="_blank" rel="noopener" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="font-weight-semibold">{{ $book->title }}</div>
                                    @if(!empty($book->description))
                                        <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($book->description, 120) }}</small>
                                    @endif
                                </div>
                                <span class="badge badge-primary badge-pill"><i class="fas fa-external-link-alt"></i></span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        Belum ada buku panduan aktif.
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mt-3 mt-lg-0">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-video mr-2"></i> Video Tutorial Terbaru
                </h3>
                @if ($isSuperAdmin)
                    <a href="{{ route('video-tutorials.index') }}" class="btn btn-sm btn-outline-primary">
                        Kelola Video
                    </a>
                @endif
            </div>
            <div class="card-body p-0">
                @if(!empty($videoTutorials) && $videoTutorials->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach ($videoTutorials as $video)
                            <a href="{{ $video->video_url }}" target="_blank" rel="noopener" class="list-group-item list-group-item-action d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="font-weight-semibold">{{ $video->title }}</div>
                                    @if(!empty($video->description))
                                        <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($video->description, 120) }}</small>
                                    @endif
                                </div>
                                <span class="badge badge-danger badge-pill"><i class="fas fa-play"></i></span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="p-4 text-center text-muted">
                        Belum ada video tutorial aktif.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($isSuperAdmin || $isAdminUnit)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-comments mr-2"></i> Feedback Aplikasi
                </h3>
                <span class="badge badge-info">
                    {{ $isSuperAdmin ? 'Super Admin' : 'Admin Unit' }}
                </span>
            </div>
            <div class="card-body">
                @if ($isAdminUnit && ! $isSuperAdmin)
                    <p class="text-muted small mb-3">
                        Sampaikan kritik dan saran Anda, feedback akan tampil dengan nama <strong>{{ auth()->user()->name }}</strong>.
                    </p>
                    <form action="{{ route('feedbacks.store') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="feedback-message" class="font-weight-semibold">Kritik &amp; Saran</label>
                            <textarea name="message" id="feedback-message" rows="4" class="form-control @error('message') is-invalid @enderror" maxlength="2000" required>{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-paper-plane mr-1"></i> Kirim Feedback
                        </button>
                    </form>

                    <h6 class="font-weight-semibold mb-3">Feedback Anda</h6>
                    @if ($feedbacks->isEmpty())
                        <div class="text-muted">
                            Belum ada feedback aktif. Feedback yang dinonaktifkan oleh super admin tidak ditampilkan.
                        </div>
                    @else
                        <div class="mb-3 text-muted small">
                            Menampilkan {{ $feedbacks->count() }} feedback terbaru.
                        </div>
                        @foreach ($feedbacks as $feedback)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-semibold">{{ auth()->user()->name }} <span class="text-muted">(Anda)</span></div>
                                        <small class="text-muted d-block">
                                            Dikirim {{ optional($feedback->created_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                    <span class="badge {{ $feedback->reply ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $feedback->reply ? 'Sudah dibalas' : 'Menunggu balasan' }}
                                    </span>
                                </div>
                                <div class="mt-3 text-dark">{!! nl2br(e($feedback->message)) !!}</div>
                                @if ($feedback->reply)
                                    <div class="mt-3 p-3 bg-light border rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="font-weight-semibold text-success">
                                                <i class="fas fa-reply mr-1"></i> Balasan Super Admin
                                            </div>
                                            <small class="text-muted">
                                                {{ optional($feedback->replied_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="mt-2 text-dark">{!! nl2br(e($feedback->reply)) !!}</div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                @elseif ($isSuperAdmin)
                    <div class="mb-3 text-muted small">
                        Menampilkan 10 feedback terbaru dari seluruh admin unit.
                    </div>
                    @if ($feedbacks->isEmpty())
                        <div class="text-muted">
                            Belum ada feedback yang masuk.
                        </div>
                    @else
                        @foreach ($feedbacks as $feedback)
                            @php
                                $author = $feedback->author;
                                $skpdName = optional(optional($author)->skpd)->name;
                                $replyError = $errors->has('reply') && old('feedback_id') == $feedback->id;
                                $replyValue = $replyError ? old('reply') : ($feedback->reply ?? '');
                            @endphp
                            <div class="border rounded p-3 mb-3 {{ $feedback->is_active ? '' : 'bg-light' }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-semibold">
                                            {{ optional($author)->name ?? 'Pengguna tidak diketahui' }}
                                            @if ($skpdName)
                                                <span class="text-muted">({{ $skpdName }})</span>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">
                                            Masuk {{ optional($feedback->created_at)->diffForHumans() }}
                                        </small>
                                    </div>
                                    <span class="badge {{ $feedback->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $feedback->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <div class="mt-3 text-dark">{!! nl2br(e($feedback->message)) !!}</div>
                                @if ($feedback->reply)
                                    <div class="mt-3 p-3 bg-white border rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="font-weight-semibold text-success">
                                                <i class="fas fa-reply mr-1"></i> Balasan Terkirim oleh {{ optional($feedback->replier)->name ?? 'Super Admin' }}
                                            </div>
                                            <small class="text-muted">
                                                {{ optional($feedback->replied_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="mt-2 text-dark">{!! nl2br(e($feedback->reply)) !!}</div>
                                    </div>
                                @endif

                                <div class="mt-3">
                                    <form action="{{ route('feedbacks.reply', $feedback) }}" method="POST" class="mb-2">
                                        @csrf
                                        <input type="hidden" name="feedback_id" value="{{ $feedback->id }}">
                                        <label for="reply-{{ $feedback->id }}" class="small font-weight-semibold">
                                            {{ $feedback->reply ? 'Perbarui Balasan' : 'Balas Feedback' }}
                                        </label>
                                        <textarea name="reply" id="reply-{{ $feedback->id }}" rows="3" class="form-control form-control-sm {{ $replyError ? 'is-invalid' : '' }}">{{ $replyValue }}</textarea>
                                        @if ($replyError)
                                            <div class="invalid-feedback d-block">{{ $errors->first('reply') }}</div>
                                        @endif
                                        <button type="submit" class="btn btn-primary btn-sm mt-2">
                                            <i class="fas fa-reply mr-1"></i> Simpan Balasan
                                        </button>
                                    </form>
                                    <form action="{{ route('feedbacks.toggle', $feedback) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('patch')
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">
                                            {{ $feedback->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endif

@endsection

