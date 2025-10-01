@extends('layouts.app')

@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')

@section('content')
@php
    $currentUser = auth()->user();
    $statusPerkawinanOptions = $statusPerkawinanOptions ?? [];
    $statusAsnOptions = $statusAsnOptions ?? [];
    $tipeJabatanOptions = $tipeJabatanOptions ?? [];
    $perPage = $perPage ?? 25;
    $perPageOptions = $perPageOptions ?? [25, 50, 100];
    $canManagePegawai = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();
    $dataColumnCount = 21;
    $columnCount = $dataColumnCount + ($canManagePegawai ? 2 : 0);
@endphp
<div class="row mb-3">
    <div class="col-lg-6">
        <form method="GET" action="{{ route('pegawais.index') }}" class="form-inline">
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Cari nama, NIK, NIP, atau jabatan" value="{{ $search }}">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i> Cari</button>
                </div>
            </div>
        </form>
    </div>
    <div class="col-lg-6 mt-3 mt-lg-0 d-flex justify-content-lg-end flex-wrap gap-2">
        <a href="{{ route('pegawais.export') }}" class="btn btn-success mr-2"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
        @if ($canManagePegawai)
            <button type="submit" class="btn btn-danger mr-2 mb-2" id="bulk-delete-button" form="bulk-delete-form" disabled>
                <i class="fas fa-trash"></i> Hapus Terpilih
            </button>
            <a href="{{ route('pegawais.template') }}" class="btn btn-outline-secondary mr-2 mb-2"><i class="fas fa-download"></i> Template</a>
            <form action="{{ route('pegawais.import') }}" method="POST" enctype="multipart/form-data" class="form-inline mr-2 mb-2">
                @csrf
                <div class="input-group">
                    <div class="custom-file">
                        <input type="file" name="file" class="custom-file-input" id="pegawai-import-file" accept=".xlsx,.xls" required>
                        <label class="custom-file-label" for="pegawai-import-file">Pilih file...</label>
                    </div>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Import</button>
                    </div>
                </div>
            </form>
            <a href="{{ route('pegawais.create') }}" class="btn btn-primary mb-2"><i class="fas fa-plus"></i> Tambah Pegawai</a>
        @endif
    </div>
</div>

@if ($errors->has('file'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        @foreach ($errors->get('file') as $message)
            <div>{{ $message }}</div>
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($canManagePegawai)
    <form id="bulk-delete-form" method="POST" action="{{ route('pegawais.bulk-destroy') }}" class="d-none">
        @csrf
        @method('DELETE')
        @foreach (request()->except(['ids', 'page', '_token', '_method']) as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>
@endif
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        @if ($canManagePegawai)
                            <th style="width: 50px" class="text-center">
                                <input type="checkbox" id="select-all-pegawais" aria-label="Pilih semua pegawai">
                            </th>
                        @endif
                        <th>NIP Pegawai</th>
                        <th>Nama Pegawai</th>
                        <th>NIK Pegawai</th>
                        <th>NPWP Pegawai</th>
                        <th>Tanggal Lahir Pegawai</th>
                        <th>Tipe Jabatan</th>
                        <th>Nama Jabatan</th>
                        <th>Eselon</th>
                        <th>Status ASN</th>
                        <th>Golongan</th>
                        <th>Masa Kerja Golongan</th>
                        <th>Alamat</th>
                        <th>Status Pernikahan</th>
                        <th>Jumlah Istri_Suami</th>
                        <th>Jumlah Anak</th>
                        <th>Jumlah Tanggungan</th>
                        <th>Pasangan PNS</th>
                        <th>NIP Pasangan</th>
                        <th>Kode Bank</th>
                        <th>Nama Bank</th>
                        <th>Nomor Rekening Bank Pegawai</th>
                        @if ($canManagePegawai)
                            <th style="width: 160px">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pegawais as $pegawai)
                        @php
                            $canManage = $currentUser->isSuperAdmin() || ($currentUser->isAdminUnit() && $pegawai->skpd_id === $currentUser->skpd_id);
                        @endphp
                        <tr>
                            @if ($canManagePegawai)
                                <td class="text-center">
                                    <input type="checkbox" name="ids[]" value="{{ $pegawai->id }}" class="pegawai-select-checkbox" form="bulk-delete-form">
                                </td>
                            @endif
                            <td>{{ $pegawai->nip ?? '-' }}</td>
                            <td>{{ $pegawai->nama_lengkap }}</td>
                            <td>{{ $pegawai->nik }}</td>
                            <td>{{ $pegawai->npwp ?? '-' }}</td>
                            <td>{{ optional($pegawai->tanggal_lahir)?->format('d-m-Y') ?? '-' }}</td>
                            <td>{{ $tipeJabatanOptions[$pegawai->tipe_jabatan] ?? '-' }}</td>
                            <td>{{ $pegawai->jabatan ?? '-' }}</td>
                            <td>{{ $pegawai->eselon ?? '-' }}</td>
                            <td>{{ $statusAsnOptions[$pegawai->status_asn] ?? '-' }}</td>
                            <td>{{ $pegawai->golongan ?? '-' }}</td>
                            <td>{{ $pegawai->masa_kerja ?? '-' }}</td>
                            <td>{{ $pegawai->alamat_rumah ?? '-' }}</td>
                            <td>{{ $statusPerkawinanOptions[$pegawai->status_perkawinan] ?? '-' }}</td>
                            <td>{{ $pegawai->jumlah_istri_suami ?? 0 }}</td>
                            <td>{{ $pegawai->jumlah_anak ?? 0 }}</td>
                            <td>{{ $pegawai->jumlah_tanggungan ?? 0 }}</td>
                            <td>{{ $pegawai->pasangan_pns ? 'YA' : 'TIDAK' }}</td>
                            <td>{{ $pegawai->nip_pasangan ?? '-' }}</td>
                            <td>{{ $pegawai->kode_bank ?? '-' }}</td>
                            <td>{{ $pegawai->nama_bank ?? '-' }}</td>
                            <td>{{ $pegawai->nomor_rekening_pegawai ?? '-' }}</td>
                            @if ($canManagePegawai)
                                <td>
                                    @if ($canManage)
                                        <a href="{{ route('pegawais.edit', $pegawai) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('pegawais.destroy', $pegawai) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus data pegawai ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" class="text-center py-4 text-muted">Belum ada data pegawai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="text-muted small mb-2 mb-md-0">
                Menampilkan {{ $pegawais->firstItem() ?? 0 }} - {{ $pegawais->lastItem() ?? 0 }} dari {{ $pegawais->total() }} data pegawai
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                <form method="GET" action="{{ route('pegawais.index') }}" class="form-inline mb-2 mb-md-0">
                    @foreach (request()->except(['per_page', 'page', 'ids', '_token', '_method']) as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    <label for="per-page-select" class="mr-2 mb-0">Tampilkan</label>
                    <select id="per-page-select" name="per_page" class="custom-select custom-select-sm" onchange="this.form.submit()">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="ml-2">data</span>
                </form>
                @if ($pegawais->hasPages())
                    <div class="mb-0">
                        {{ $pegawais->onEachSide(1)->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

















@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const importInput = document.getElementById('pegawai-import-file');
        if (importInput) {
            importInput.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (label && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                }
            });
        }

        const bulkForm = document.getElementById('bulk-delete-form');
        const bulkButton = document.getElementById('bulk-delete-button');
        if (!bulkForm || !bulkButton) {
            return;
        }

        const selectAll = document.getElementById('select-all-pegawais');
        const itemCheckboxes = Array.from(document.querySelectorAll('.pegawai-select-checkbox'));

        const updateButtonState = () => {
            const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
            bulkButton.disabled = checkedCount === 0;

            if (selectAll) {
                selectAll.checked = checkedCount > 0 && checkedCount === itemCheckboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < itemCheckboxes.length;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', (event) => {
                itemCheckboxes.forEach((checkbox) => {
                    checkbox.checked = event.target.checked;
                });
                updateButtonState();
            });
        }

        itemCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateButtonState);
        });

        bulkForm.addEventListener('submit', (event) => {
            if (!confirm('Hapus semua data pegawai terpilih?')) {
                event.preventDefault();
            }
        });

        updateButtonState();
    });
</script>
@endpush















