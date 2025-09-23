<?php

namespace App\Imports;

use App\Models\Pegawai;
use App\Models\Skpd;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PegawaiImport implements ToCollection, WithHeadingRow
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

    public function collection(Collection $rows)
    {
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;

            if ($row->filter()->isEmpty()) {
                continue;
            }

            try {
                $data = $this->mapRow($row);
                Pegawai::updateOrCreate(
                    ['nik' => $data['nik']],
                    $data
                );
            } catch (\Throwable $e) {
                $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'file' => $errors,
            ]);
        }
    }

    protected function mapRow(Collection $row): array
    {
        $skpdId = $this->user->skpd_id;

        if (! $skpdId) {
            if ($this->user->isSuperAdmin()) {
                $skpdName = $this->stringValue($row, ['skpd']);
                if ($skpdName === null) {
                    throw new \InvalidArgumentException('Kolom SKPD wajib diisi untuk setiap baris.');
                }

                $skpd = Skpd::where('name', $skpdName)->first();
                if (! $skpd) {
                    throw new \InvalidArgumentException("SKPD '{$skpdName}' tidak ditemukan.");
                }

                $skpdId = $skpd->id;
            } else {
                throw new \InvalidArgumentException('Pengguna tidak memiliki SKPD bawaan untuk impor data.');
            }
        }

        $namaLengkap = $this->requireString($row, ['nama_lengkap', 'nama_pegawai'], 'Nama Pegawai');
        $nik = $this->requireString($row, ['nik', 'nik_pegawai'], 'NIK Pegawai');
        $tempatLahir = $this->requireString($row, ['tempat_lahir'], 'Tempat Lahir');
        $jenisKelamin = $this->requireString($row, ['jenis_kelamin'], 'Jenis Kelamin');

        $statusPerkawinan = $this->mapOption(
            $row['status_perkawinan'] ?? $row['status_pernikahan'] ?? null,
            $this->statusPerkawinanOptions,
            'Status Pernikahan'
        );

        $statusAsn = $this->mapOption(
            $row['status_asn'] ?? null,
            $this->statusAsnOptions,
            'Status ASN'
        );

        $tipeJabatan = $this->mapOption(
            $row['tipe_jabatan'] ?? null,
            $this->tipeJabatanOptions,
            'Tipe Jabatan'
        );

        $tanggalLahirRaw = $row['tanggal_lahir']
            ?? $row['tanggal_lahir (yyyy-mm-dd)']
            ?? $row['tanggal_lahir_pegawai']
            ?? null;

        if ($tanggalLahirRaw) {
            try {
                $tanggalLahir = Carbon::parse($tanggalLahirRaw)->format('Y-m-d');
            } catch (\Throwable $e) {
                throw new \InvalidArgumentException('Tanggal lahir tidak valid. Gunakan format YYYY-MM-DD.');
            }
        } else {
            throw new \InvalidArgumentException('Tanggal lahir wajib diisi.');
        }

        $pasanganPns = $this->normalizeBoolean($row['pasangan_pns'] ?? null, 'Pasangan PNS');

        return [
            'skpd_id' => $skpdId,
            'nama_lengkap' => $namaLengkap,
            'nik' => $nik,
            'nip' => $this->stringValue($row, ['nip', 'nip_pegawai']),
            'npwp' => $this->stringValue($row, ['npwp', 'npwp_pegawai']),
            'tempat_lahir' => $tempatLahir,
            'tanggal_lahir' => $tanggalLahir,
            'jenis_kelamin' => $jenisKelamin,
            'status_perkawinan' => $statusPerkawinan,
            'jumlah_istri_suami' => $this->integerValue($row, ['jumlah_istri_suami', 'jumlah_istri/suami']),
            'jumlah_anak' => $this->integerValue($row, ['jumlah_anak']),
            'jabatan' => $this->stringValue($row, ['jabatan', 'nama_jabatan']),
            'eselon' => $this->stringValue($row, ['eselon']),
            'golongan' => $this->stringValue($row, ['golongan']),
            'email' => $this->stringValue($row, ['email']),
            'alamat_rumah' => $this->stringValue($row, ['alamat_rumah', 'alamat']),
            'masa_kerja' => $this->stringValue($row, ['masa_kerja', 'masa_kerja_golongan']),
            'jumlah_tanggungan' => $this->integerValue($row, ['jumlah_tanggungan']),
            'pasangan_pns' => $pasanganPns,
            'nip_pasangan' => $this->stringValue($row, ['nip_pasangan']),
            'kode_bank' => $this->stringValue($row, ['kode_bank']),
            'nama_bank' => $this->stringValue($row, ['nama_bank']),
            'nomor_rekening_pegawai' => $this->stringValue($row, ['nomor_rekening_pegawai', 'nomor_rekening_bank_pegawai']),
            'tipe_jabatan' => $tipeJabatan,
            'status_asn' => $statusAsn,
        ];
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
                $value = trim((string) $row[$key]);
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
                $value = $row[$key];
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


