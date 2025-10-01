@csrf
@php
    $currentUser = auth()->user();
    $pegawai = $pegawai ?? null;
    $tipeJabatanOptions = $tipeJabatanOptions ?? [];
    $statusAsnOptions = $statusAsnOptions ?? [];
    $statusPerkawinanOptions = $statusPerkawinanOptions ?? [];
    $selectedSkpd = old('skpd_id', optional($pegawai)->skpd_id);
    if (! $currentUser->isSuperAdmin()) {
        $selectedSkpd = $currentUser->skpd_id;
    }
    $tanggalLahir = old('tanggal_lahir', optional($pegawai)->tanggal_lahir ? optional($pegawai)->tanggal_lahir->format('Y-m-d') : null);
    $selectedStatusPerkawinan = (string) old('status_perkawinan', optional($pegawai)->status_perkawinan);
    $selectedStatusAsn = (string) old('status_asn', optional($pegawai)->status_asn);
    $selectedTipeJabatan = (string) old('tipe_jabatan', optional($pegawai)->tipe_jabatan);
@endphp
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="nama_lengkap">Nama Pegawai</label>
        <input type="text" name="nama_lengkap" id="nama_lengkap" class="form-control @error('nama_lengkap') is-invalid @enderror" value="{{ old('nama_lengkap', optional($pegawai)->nama_lengkap) }}" required>
        @error('nama_lengkap')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="nik">NIK Pegawai</label>
        <input type="text" name="nik" id="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', optional($pegawai)->nik) }}" required>
        @error('nik')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="nip">NIP Pegawai</label>
        <input type="text" name="nip" id="nip" class="form-control @error('nip') is-invalid @enderror" value="{{ old('nip', optional($pegawai)->nip) }}">
        @error('nip')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="npwp">NPWP Pegawai</label>
        <input type="text" name="npwp" id="npwp" class="form-control @error('npwp') is-invalid @enderror" value="{{ old('npwp', optional($pegawai)->npwp) }}">
        @error('npwp')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', optional($pegawai)->email) }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="tempat_lahir">Tempat Lahir</label>
        <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror" value="{{ old('tempat_lahir', optional($pegawai)->tempat_lahir) }}" required>
        @error('tempat_lahir')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="tanggal_lahir">Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control @error('tanggal_lahir') is-invalid @enderror" value="{{ $tanggalLahir }}" required>
        @error('tanggal_lahir')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="jenis_kelamin">Jenis Kelamin</label>
        <select name="jenis_kelamin" id="jenis_kelamin" class="form-control @error('jenis_kelamin') is-invalid @enderror" required>
            <option value="" disabled {{ old('jenis_kelamin', optional($pegawai)->jenis_kelamin) ? '' : 'selected' }}>Pilih jenis kelamin</option>
            @foreach (['Laki-laki', 'Perempuan'] as $jk)
                <option value="{{ $jk }}" {{ old('jenis_kelamin', optional($pegawai)->jenis_kelamin) === $jk ? 'selected' : '' }}>{{ $jk }}</option>
            @endforeach
        </select>
        @error('jenis_kelamin')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="status_perkawinan">Status Perkawinan</label>
        <select name="status_perkawinan" id="status_perkawinan" class="form-control @error('status_perkawinan') is-invalid @enderror" required>
            <option value="" disabled {{ $selectedStatusPerkawinan ? '' : 'selected' }}>Pilih status</option>
            @foreach ($statusPerkawinanOptions as $value => $label)
                <option value="{{ $value }}" {{ $selectedStatusPerkawinan === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <small class="form-text text-muted">Gunakan angka: 1 = Sudah Menikah, 2 = Belum Menikah atau Cerai Hidup/Mati.</small>
        @error('status_perkawinan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label for="jumlah_istri_suami">Jumlah Istri/Suami</label>
        <input type="number" name="jumlah_istri_suami" id="jumlah_istri_suami" min="0" class="form-control @error('jumlah_istri_suami') is-invalid @enderror" value="{{ old('jumlah_istri_suami', optional($pegawai)->jumlah_istri_suami) }}">
        @error('jumlah_istri_suami')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-3">
        <label for="jumlah_anak">Jumlah Anak</label>
        <input type="number" name="jumlah_anak" id="jumlah_anak" min="0" class="form-control @error('jumlah_anak') is-invalid @enderror" value="{{ old('jumlah_anak', optional($pegawai)->jumlah_anak) }}">
        @error('jumlah_anak')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="jabatan">Nama Jabatan</label>
        <input type="text" name="jabatan" id="jabatan" class="form-control @error('jabatan') is-invalid @enderror" value="{{ old('jabatan', optional($pegawai)->jabatan) }}">
        @error('jabatan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="eselon">Eselon</label>
        <input type="text" name="eselon" id="eselon" class="form-control @error('eselon') is-invalid @enderror" value="{{ old('eselon', optional($pegawai)->eselon) }}">
        @error('eselon')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="golongan">Golongan</label>
        <input type="text" name="golongan" id="golongan" class="form-control @error('golongan') is-invalid @enderror" value="{{ old('golongan', optional($pegawai)->golongan) }}">
        @error('golongan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="masa_kerja">Masa Kerja Golongan</label>
        <input type="text" name="masa_kerja" id="masa_kerja" class="form-control @error('masa_kerja') is-invalid @enderror" value="{{ old('masa_kerja', optional($pegawai)->masa_kerja) }}">
        @error('masa_kerja')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="jumlah_tanggungan">Jumlah Tanggungan</label>
        <input type="number" name="jumlah_tanggungan" id="jumlah_tanggungan" min="0" class="form-control @error('jumlah_tanggungan') is-invalid @enderror" value="{{ old('jumlah_tanggungan', optional($pegawai)->jumlah_tanggungan) }}">
        @error('jumlah_tanggungan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-12">
        <label for="alamat_rumah">Alamat</label>
        <textarea name="alamat_rumah" id="alamat_rumah" class="form-control @error('alamat_rumah') is-invalid @enderror" rows="3">{{ old('alamat_rumah', optional($pegawai)->alamat_rumah) }}</textarea>
        @error('alamat_rumah')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label for="tipe_jabatan">Tipe Jabatan</label>
        <select name="tipe_jabatan" id="tipe_jabatan" class="form-control @error('tipe_jabatan') is-invalid @enderror" required>
            <option value="" disabled {{ $selectedTipeJabatan ? '' : 'selected' }}>Pilih tipe jabatan</option>
            @foreach ($tipeJabatanOptions as $value => $label)
                <option value="{{ $value }}" {{ $selectedTipeJabatan === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('tipe_jabatan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-6">
        <label for="status_asn">Status ASN</label>
        <select name="status_asn" id="status_asn" class="form-control @error('status_asn') is-invalid @enderror" required>
            <option value="" disabled {{ $selectedStatusAsn ? '' : 'selected' }}>Pilih status ASN</option>
            @foreach ($statusAsnOptions as $value => $label)
                <option value="{{ $value }}" {{ $selectedStatusAsn === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('status_asn')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row align-items-center">
    <div class="form-group col-md-4">
        <label for="pasangan_pns">Pasangan PNS</label>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="pasangan_pns" id="pasangan_pns" value="1" {{ old('pasangan_pns', optional($pegawai)->pasangan_pns) ? 'checked' : '' }}>
            <label class="form-check-label" for="pasangan_pns">Ya</label>
        </div>
    </div>
    <div class="form-group col-md-4">
        <label for="nip_pasangan">NIP Pasangan</label>
        <input type="text" name="nip_pasangan" id="nip_pasangan" class="form-control @error('nip_pasangan') is-invalid @enderror" value="{{ old('nip_pasangan', optional($pegawai)->nip_pasangan) }}">
        @error('nip_pasangan')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="skpd_id">SKPD / Instansi</label>
        <select name="skpd_id" id="skpd_id" class="form-control @error('skpd_id') is-invalid @enderror" {{ $currentUser->isSuperAdmin() ? '' : 'disabled' }} required>
            <option value="" disabled {{ $selectedSkpd ? '' : 'selected' }}>Pilih SKPD</option>
            @foreach ($skpds as $skpd)
                <option value="{{ $skpd->id }}" {{ (string) $selectedSkpd === (string) $skpd->id ? 'selected' : '' }}>{{ $skpd->name }}</option>
            @endforeach
        </select>
        @if (! $currentUser->isSuperAdmin())
            <input type="hidden" name="skpd_id" value="{{ $currentUser->skpd_id }}">
        @endif
        @error('skpd_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label for="kode_bank">Kode Bank</label>
        <input type="text" name="kode_bank" id="kode_bank" class="form-control @error('kode_bank') is-invalid @enderror" value="{{ old('kode_bank', optional($pegawai)->kode_bank) }}">
        @error('kode_bank')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="nama_bank">Nama Bank</label>
        <input type="text" name="nama_bank" id="nama_bank" class="form-control @error('nama_bank') is-invalid @enderror" value="{{ old('nama_bank', optional($pegawai)->nama_bank) }}">
        @error('nama_bank')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="form-group col-md-4">
        <label for="nomor_rekening_pegawai">Nomor Rekening Bank Pegawai</label>
        <input type="text" name="nomor_rekening_pegawai" id="nomor_rekening_pegawai" class="form-control @error('nomor_rekening_pegawai') is-invalid @enderror" value="{{ old('nomor_rekening_pegawai', optional($pegawai)->nomor_rekening_pegawai) }}">
        @error('nomor_rekening_pegawai')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>






