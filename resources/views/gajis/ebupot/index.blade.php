@extends('gajis.layout')

@section('title', 'Arsip E-Bupot')

@section('card-tools')
    <a href="{{ route('gajis.ebupot.create', array_filter([
        'type' => $filters['type'] ?? null,
        'tahun' => $filters['tahun'] ?? null,
        'bulan' => $filters['bulan'] ?? null,
    ])) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i>
        Buat E-Bupot
    </a>
@endsection

@section('card-body')
    <form method="GET" action="{{ route('gajis.ebupot.index') }}" class="row align-items-end mb-4">
        <div class="col-md-3 mb-3">
            <label for="filter-type" class="form-label">Jenis ASN</label>
            <select id="filter-type" name="type" class="form-control">
                <option value="">Semua</option>
                @foreach ($typeLabels as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['type'] ?? '') === $key)>
                        {{ strtoupper($key) }} - {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label for="filter-year" class="form-label">Tahun</label>
            <input type="number" min="2000" max="{{ date('Y') + 5 }}" id="filter-year" name="tahun" value="{{ $filters['tahun'] ?? '' }}" class="form-control">
        </div>
        <div class="col-md-3 mb-3">
            <label for="filter-month" class="form-label">Bulan</label>
            <select id="filter-month" name="bulan" class="form-control">
                <option value="">Semua</option>
                @foreach ($monthOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($filters['bulan'] ?? '') === (string) $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary mr-2 flex-fill">
                <i class="fas fa-filter"></i>
                Terapkan
            </button>
            <a href="{{ route('gajis.ebupot.index') }}" class="btn btn-outline-secondary flex-fill">
                Atur Ulang
            </a>
        </div>
    </form>

    @if ($reports->isEmpty())
        <div class="alert alert-info mb-0">
            Belum ada arsip e-Bupot untuk filter yang dipilih.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-primary">
                    <tr class="text-center">
                        <th>#</th>
                        <th>Periode</th>
                        <th>Jenis ASN</th>
                        @if (auth()->user()?->isSuperAdmin())
                            <th>SKPD</th>
                        @endif
                        <th>NPWP Pemotong</th>
                        <th>ID TKU</th>
                        <th>Kode Objek</th>
                        <th>Jumlah Data</th>
                        <th>Total Penghasilan</th>
                        <th>Dibuat Oleh</th>
                        <th>Diperbarui</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reports as $index => $report)
                        <tr>
                            <td class="text-center">{{ $reports->firstItem() + $index }}</td>
                            <td class="text-center">{{ str_pad($report->bulan, 2, '0', STR_PAD_LEFT) }}/{{ $report->tahun }}</td>
                            <td class="text-center">{{ strtoupper($report->jenis_asn) }}</td>
                            @if (auth()->user()?->isSuperAdmin())
                                <td>{{ optional($report->skpd)->name ?? '—' }}</td>
                            @endif
                            <td>{{ $report->npwp_pemotong ?: '—' }}</td>
                            <td>{{ $report->id_tku ?: '—' }}</td>
                            <td>{{ $report->kode_objek ?: '—' }}</td>
                            <td class="text-center">{{ number_format($report->entry_count) }}</td>
                            <td class="text-right">{{ number_format((float) $report->total_gross, 2, ',', '.') }}</td>
                            <td>{{ optional($report->user)->name ?? '—' }}</td>
                            <td class="text-center">{{ optional($report->updated_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('gajis.ebupot.download', ['report' => $report->id, 'format' => 'xlsx']) }}" class="btn btn-outline-primary">
                                        <i class="fas fa-file-excel"></i>
                                    </a>
                                    <a href="{{ route('gajis.ebupot.download', ['report' => $report->id, 'format' => 'xml']) }}" class="btn btn-outline-secondary flex-fill">
                                        <i class="fas fa-file-code"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $reports->withQueryString()->links() }}
        </div>
    @endif
@endsection
