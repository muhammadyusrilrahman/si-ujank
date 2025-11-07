@extends('layouts.app')

@section('title', 'Data Pegawai')
@section('page-title', 'Data Pegawai')

@php
    use Illuminate\Pagination\LengthAwarePaginator;

    $currentUser = auth()->user();
    $statusPerkawinanOptions = $statusPerkawinanOptions ?? [];
    $statusAsnOptions = $statusAsnOptions ?? [];
    $tipeJabatanOptions = $tipeJabatanOptions ?? [];
    $perPageValue = (int) ($perPage ?? 25);
    $perPageOptions = array_values($perPageOptions ?? [25, 50, 100]);
    $canManagePegawai = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();
    $skpds = $skpds ?? collect();

    $columns = [
        ['key' => 'nip', 'label' => 'NIP Pegawai'],
        ['key' => 'nama', 'label' => 'Nama Pegawai'],
        ['key' => 'nik', 'label' => 'NIK Pegawai'],
        ['key' => 'npwp', 'label' => 'NPWP Pegawai'],
        ['key' => 'tanggal_lahir', 'label' => 'Tanggal Lahir Pegawai'],
        ['key' => 'tipe_jabatan', 'label' => 'Tipe Jabatan'],
        ['key' => 'jabatan', 'label' => 'Nama Jabatan'],
        ['key' => 'eselon', 'label' => 'Eselon'],
        ['key' => 'status_asn', 'label' => 'Status ASN'],
        ['key' => 'golongan', 'label' => 'Golongan'],
        ['key' => 'masa_kerja', 'label' => 'Masa Kerja Golongan'],
        ['key' => 'alamat', 'label' => 'Alamat'],
        ['key' => 'status_perkawinan', 'label' => 'Status Pernikahan'],
        ['key' => 'jumlah_pasangan', 'label' => 'Jumlah Suami/Istri'],
        ['key' => 'jumlah_anak', 'label' => 'Jumlah Anak'],
        ['key' => 'jumlah_tanggungan', 'label' => 'Jumlah Tanggungan'],
        ['key' => 'pasangan_pns', 'label' => 'Pasangan PNS'],
        ['key' => 'nip_pasangan', 'label' => 'NIP Pasangan'],
        ['key' => 'kode_bank', 'label' => 'Kode Bank'],
        ['key' => 'nama_bank', 'label' => 'Nama Bank'],
        ['key' => 'rekening', 'label' => 'Nomor Rekening Bank Pegawai'],
    ];

    $items = [];
    $pagination = [
        'from' => 0,
        'to' => 0,
        'total' => 0,
        'links' => [],
    ];

    if ($pegawais instanceof LengthAwarePaginator) {
        $items = $pegawais->map(function ($pegawai) use ($currentUser, $statusPerkawinanOptions, $statusAsnOptions, $tipeJabatanOptions) {
            $canManageRow = $currentUser->isSuperAdmin() || ($currentUser->isAdminUnit() && $pegawai->skpd_id === $currentUser->skpd_id);

            return [
                'id' => $pegawai->id,
                'fields' => [
                    'nip' => $pegawai->nip ?? '-',
                    'nama' => $pegawai->nama_lengkap ?? '-',
                    'nik' => $pegawai->nik ?? '-',
                    'npwp' => $pegawai->npwp ?? '-',
                    'tanggal_lahir' => optional($pegawai->tanggal_lahir)?->format('d-m-Y') ?? '-',
                    'tipe_jabatan' => $tipeJabatanOptions[$pegawai->tipe_jabatan] ?? '-',
                    'jabatan' => $pegawai->jabatan ?? '-',
                    'eselon' => $pegawai->eselon ?? '-',
                    'status_asn' => $statusAsnOptions[$pegawai->status_asn] ?? '-',
                    'golongan' => $pegawai->golongan ?? '-',
                    'masa_kerja' => $pegawai->masa_kerja ?? '-',
                    'alamat' => $pegawai->alamat_rumah ?? '-',
                    'status_perkawinan' => $statusPerkawinanOptions[$pegawai->status_perkawinan] ?? '-',
                    'jumlah_pasangan' => $pegawai->jumlah_istri_suami !== null ? (string) $pegawai->jumlah_istri_suami : '0',
                    'jumlah_anak' => $pegawai->jumlah_anak !== null ? (string) $pegawai->jumlah_anak : '0',
                    'jumlah_tanggungan' => $pegawai->jumlah_tanggungan !== null ? (string) $pegawai->jumlah_tanggungan : '0',
                    'pasangan_pns' => $pegawai->pasangan_pns ? 'YA' : 'TIDAK',
                    'nip_pasangan' => $pegawai->nip_pasangan ?? '-',
                    'kode_bank' => $pegawai->kode_bank ?? '-',
                    'nama_bank' => $pegawai->nama_bank ?? '-',
                    'rekening' => $pegawai->nomor_rekening_pegawai ?? '-',
                ],
                'links' => [
                    'edit' => $canManageRow ? route('pegawais.edit', $pegawai) : null,
                    'destroy' => $canManageRow ? route('pegawais.destroy', $pegawai) : null,
                ],
            ];
        })->values()->all();

        $paginatorArray = $pegawais->toArray();
        $pagination = [
            'from' => $pegawais->firstItem() ?? 0,
            'to' => $pegawais->lastItem() ?? 0,
            'total' => $pegawais->total() ?? 0,
            'links' => collect($paginatorArray['links'] ?? [])->map(function ($link) {
                return [
                    'url' => $link['url'],
                    'label' => $link['label'],
                    'active' => $link['active'],
                ];
            })->all(),
        ];
    }

    $queryParams = collect(request()->except(['page', 'ids', '_token', '_method']))->all();

    $props = [
        'searchQuery' => (string) ($search ?? ''),
        'perPage' => $perPageValue,
        'perPageOptions' => $perPageOptions,
        'statusMessage' => session('status'),
        'importErrors' => $errors->get('file'),
        'skpdError' => $errors->first('skpd_id'),
        'routes' => [
            'index' => route('pegawais.index'),
            'create' => $canManagePegawai ? route('pegawais.create') : null,
            'export' => route('pegawais.export'),
            'template' => route('pegawais.template'),
            'import' => $canManagePegawai ? route('pegawais.import') : null,
            'bulkDelete' => $canManagePegawai ? route('pegawais.bulk-destroy') : null,
        ],
        'permissions' => [
            'canManage' => $canManagePegawai,
            'canCreate' => $canManagePegawai,
            'canImport' => $canManagePegawai,
        ],
        'items' => $items,
        'pagination' => $pagination,
        'queryParams' => $queryParams,
        'importConfig' => [
            'showSkpdSelect' => $currentUser->isSuperAdmin(),
            'skpdOptions' => $skpds->map(function ($skpd) {
                return [
                    'id' => (string) $skpd->id,
                    'name' => $skpd->name,
                ];
            })->values()->all(),
            'selectedSkpd' => old('skpd_id', ''),
        ],
        'csrfToken' => csrf_token(),
        'columns' => $columns,
    ];
@endphp

@section('content')
    <div
        id="pegawai-index-root"
        data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
    ></div>

    <noscript>
        <div class="alert alert-warning mt-3">
            Halaman data pegawai memerlukan JavaScript agar dapat digunakan sepenuhnya. Silakan aktifkan JavaScript pada peramban Anda.
        </div>
    </noscript>
@endsection

