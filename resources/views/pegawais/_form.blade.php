@csrf
@php
    $currentUser = auth()->user();
    $pegawai = $pegawai ?? null;
    $skpds = $skpds ?? collect();
    $tipeJabatanOptions = $tipeJabatanOptions ?? [];
    $statusAsnOptions = $statusAsnOptions ?? [];
    $statusPerkawinanOptions = $statusPerkawinanOptions ?? [];

    $selectedSkpd = old('skpd_id', optional($pegawai)->skpd_id);
    if (! $currentUser->isSuperAdmin()) {
        $selectedSkpd = $currentUser->skpd_id;
    }

    $tanggalLahir = old('tanggal_lahir', optional($pegawai)->tanggal_lahir
        ? optional($pegawai)->tanggal_lahir->format('Y-m-d')
        : null);

    $skpdOptionsData = collect($skpds)->map(function ($skpdItem) {
        return [
            'id' => (string) $skpdItem->id,
            'name' => $skpdItem->name,
        ];
    })->values();

    $tipeJabatanData = collect($tipeJabatanOptions)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $statusAsnData = collect($statusAsnOptions)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $statusPerkawinanData = collect($statusPerkawinanOptions)->map(function ($label, $value) {
        return [
            'value' => (string) $value,
            'label' => $label,
        ];
    })->values();

    $forcedSkpdId = $currentUser->isSuperAdmin()
        ? null
        : ($currentUser->skpd_id !== null ? (string) $currentUser->skpd_id : null);

    $props = [
        'mode' => $pegawai ? 'edit' : 'create',
        'options' => [
            'skpds' => $skpdOptionsData,
            'tipeJabatan' => $tipeJabatanData,
            'statusAsn' => $statusAsnData,
            'statusPerkawinan' => $statusPerkawinanData,
            'genders' => [
                ['value' => 'Laki-laki', 'label' => 'Laki-laki'],
                ['value' => 'Perempuan', 'label' => 'Perempuan'],
            ],
        ],
        'old' => [
            'nama_lengkap' => old('nama_lengkap', optional($pegawai)->nama_lengkap),
            'nik' => old('nik', optional($pegawai)->nik),
            'nip' => old('nip', optional($pegawai)->nip),
            'npwp' => old('npwp', optional($pegawai)->npwp),
            'email' => old('email', optional($pegawai)->email),
            'tempat_lahir' => old('tempat_lahir', optional($pegawai)->tempat_lahir),
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => old('jenis_kelamin', optional($pegawai)->jenis_kelamin),
            'status_perkawinan' => old('status_perkawinan', optional($pegawai)->status_perkawinan),
            'jumlah_istri_suami' => old('jumlah_istri_suami', optional($pegawai)->jumlah_istri_suami),
            'jumlah_anak' => old('jumlah_anak', optional($pegawai)->jumlah_anak),
            'jabatan' => old('jabatan', optional($pegawai)->jabatan),
            'eselon' => old('eselon', optional($pegawai)->eselon),
            'golongan' => old('golongan', optional($pegawai)->golongan),
            'masa_kerja' => old('masa_kerja', optional($pegawai)->masa_kerja),
            'jumlah_tanggungan' => old('jumlah_tanggungan', optional($pegawai)->jumlah_tanggungan),
            'alamat_rumah' => old('alamat_rumah', optional($pegawai)->alamat_rumah),
            'tipe_jabatan' => old('tipe_jabatan', optional($pegawai)->tipe_jabatan),
            'status_asn' => old('status_asn', optional($pegawai)->status_asn),
            'pasangan_pns' => old('pasangan_pns', optional($pegawai)->pasangan_pns),
            'nip_pasangan' => old('nip_pasangan', optional($pegawai)->nip_pasangan),
            'skpd_id' => $selectedSkpd !== null ? (string) $selectedSkpd : '',
            'kode_bank' => old('kode_bank', optional($pegawai)->kode_bank),
            'nama_bank' => old('nama_bank', optional($pegawai)->nama_bank),
            'nomor_rekening_pegawai' => old('nomor_rekening_pegawai', optional($pegawai)->nomor_rekening_pegawai),
        ],
        'permissions' => [
            'canSelectSkpd' => $currentUser->isSuperAdmin(),
            'forcedSkpdId' => $forcedSkpdId,
        ],
        'errors' => $errors->toArray(),
    ];
@endphp

<div
    id="pegawai-form-root"
    data-props='@json($props, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)'
></div>

<noscript>
    <div class="alert alert-warning mt-3">
        Form membutuhkan JavaScript agar dapat digunakan. Silakan aktifkan JavaScript pada peramban Anda.
    </div>
</noscript>

