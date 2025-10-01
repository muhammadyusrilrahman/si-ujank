<?php

namespace App\Imports;

use App\Models\Pegawai;
use App\Models\Skpd;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PegawaiImport
{
    protected Authenticatable $user;
    protected array $tipeJabatanOptions;
    protected array $statusAsnOptions;
    protected array $statusPerkawinanOptions;

    public function __construct(Authenticatable $user, array $tipeJabatanOptions, array $statusAsnOptions, array $statusPerkawinanOptions)
    {
        $this->user = $user;
        $this->tipeJabatanOptions = $tipeJabatanOptions;
        $this->statusAsnOptions = $statusAsnOptions;
        $this->statusPerkawinanOptions = $statusPerkawinanOptions;
    }

    public function import(Collection $rows): void
    {
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $rowData) {
            $rowNumber++;
            $row = collect($rowData ?? []);

            if ($row->filter(function ($value) {
                return $value !== null && $value !== '';
            })->isEmpty()) {
                continue;
            }

            try {
                $data = $this->mapRow($row);
                Pegawai::updateOrCreate(['nik' => $data['nik']], $data);
            } catch (\Throwable $e) {
                $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }
    }

    protected function mapRow(Collection $row): array
    {
        $nik = $this->requireString($row, ['nik_pegawai', 'nik'], 'NIK Pegawai');
        $existing = Pegawai::where('nik', $nik)->first();
        $skpdId = $this->resolveSkpd($row, $existing);

        $namaLengkap = $this->requireString($row, ['nama_pegawai', 'nama_lengkap'], 'Nama Pegawai');
        $nip = $this->stringValue($row, ['nip_pegawai', 'nip']);
        $npwp = $this->stringValue($row, ['npwp_pegawai', 'npwp']);
        $tipeJabatan = $this->mapOption(
            $row->get('tipe_jabatan'),
            $this->tipeJabatanOptions,
            'Tipe Jabatan'
        );
        $statusAsn = $this->mapOption(
            $row->get('status_asn'),
            $this->statusAsnOptions,
            'Status ASN'
        );
        $statusPerkawinan = $this->mapOption(
            $row->get('status_perkawinan') ?? $row->get('status_pernikahan'),
            $this->statusPerkawinanOptions,
            'Status Pernikahan'
        );

        $tanggalLahirRaw = $row->get('tanggal_lahir_pegawai')
            ?? $row->get('tanggal_lahir');
        if ($tanggalLahirRaw) {
            try {
                $tanggalLahir = Carbon::parse($tanggalLahirRaw)->format('Y-m-d');
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException('Tanggal lahir tidak valid. Gunakan format HH-BB-TTTT atau YYYY-MM-DD.');
            }
        } else {
            $tanggalLahir = optional($existing)->tanggal_lahir?->format('Y-m-d') ?? '1980-01-01';
        }

        $tempatLahir = $this->stringValue($row, ['tempat_lahir'])
            ?? optional($existing)->tempat_lahir
            ?? '-';
        $jenisKelamin = $this->stringValue($row, ['jenis_kelamin'])
            ?? optional($existing)->jenis_kelamin
            ?? 'Laki-laki';

        return [
            'skpd_id' => $skpdId,
            'nama_lengkap' => $namaLengkap,
            'nik' => $nik,
            'nip' => $nip,
            'npwp' => $npwp,
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => $jenisKelamin,
            'status_perkawinan' => $statusPerkawinan,
            'jumlah_istri_suami' => $this->integerValue($row, ['jumlah_istri_suami']),
            'jumlah_anak' => $this->integerValue($row, ['jumlah_anak']),
            'jabatan' => $this->stringValue($row, ['nama_jabatan', 'jabatan']) ?? optional($existing)->jabatan,
            'eselon' => $this->stringValue($row, ['eselon']) ?? optional($existing)->eselon,
            'golongan' => $this->stringValue($row, ['golongan']) ?? optional($existing)->golongan,
            'email' => optional($existing)->email,
            'alamat_rumah' => $this->stringValue($row, ['alamat', 'alamat_rumah']) ?? optional($existing)->alamat_rumah,
            'masa_kerja' => $this->stringValue($row, ['masa_kerja_golongan', 'masa_kerja']) ?? optional($existing)->masa_kerja,
            'jumlah_tanggungan' => $this->integerValue($row, ['jumlah_tanggungan']),
            'pasangan_pns' => $this->normalizeBoolean($row->get('pasangan_pns'), 'Pasangan PNS'),
            'nip_pasangan' => $this->stringValue($row, ['nip_pasangan']) ?? optional($existing)->nip_pasangan,
            'kode_bank' => $this->stringValue($row, ['kode_bank']) ?? optional($existing)->kode_bank,
            'nama_bank' => $this->stringValue($row, ['nama_bank']) ?? optional($existing)->nama_bank,
            'nomor_rekening_pegawai' => $this->stringValue($row, ['nomor_rekening_bank_pegawai', 'nomor_rekening_pegawai']) ?? optional($existing)->nomor_rekening_pegawai,
            'tipe_jabatan' => $tipeJabatan,
            'status_asn' => $statusAsn,
        ];
    }

    protected function resolveSkpd(Collection $row, ?Pegawai $existing): int
    {
        if ($existing && $existing->skpd_id) {
            return $existing->skpd_id;
        }

        $skpdId = $this->user->skpd_id;
        if ($skpdId) {
            return $skpdId;
        }

        if (! $this->user->isSuperAdmin()) {
            throw new \InvalidArgumentException('Pengguna tidak memiliki SKPD bawaan untuk impor data.');
        }

        $skpdName = $this->stringValue($row, ['skpd', 'nama_skpd']);
        if ($skpdName === null) {
            throw new \InvalidArgumentException('Untuk super admin, kolom SKPD wajib diisi untuk setiap baris.');
        }

        $skpd = Skpd::where('name', $skpdName)->first();
        if (! $skpd) {
            throw new \InvalidArgumentException("SKPD '{$skpdName}' tidak ditemukan.");
        }

        return $skpd->id;
    }

    protected function mapOption($value, array $options, string $label): string
    {
        $value = (string) trim((string) $value);
        if ($value === '') {
            throw new \InvalidArgumentException("{$label} wajib diisi.");
        }

        if (! array_key_exists($value, $options)) {
            throw new \InvalidArgumentException("{$label} harus salah satu dari: " . implode(', ', array_keys($options)));
        }

        return $value;
    }

    protected function requireString(Collection $row, array $keys, string $label): string
    {
        $value = $this->stringValue($row, $keys);

        if ($value === null) {
            throw new \InvalidArgumentException("Kolom {$label} wajib diisi.");
        }

        return $value;
    }

    protected function stringValue(Collection $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                $value = trim((string) $row->get($key));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    protected function integerValue(Collection $row, array $keys): int
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                $value = $row->get($key);
                if ($value === '' || $value === null) {
                    continue;
                }

                return (int) $value;
            }
        }

        return 0;
    }

    protected function normalizeBoolean($value, string $label): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $normalized = strtoupper(trim((string) $value));

        if (in_array($normalized, ['1', 'YA', 'Y', 'TRUE'], true)) {
            return true;
        }

        if (in_array($normalized, ['0', 'TIDAK', 'N', 'FALSE'], true)) {
            return false;
        }

        throw new \InvalidArgumentException("{$label} harus bernilai YA atau TIDAK.");
    }
}




