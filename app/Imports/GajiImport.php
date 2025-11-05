<?php

namespace App\Imports;

use App\Models\Gaji;
use App\Models\Pegawai;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GajiImport
{
    protected Authenticatable $user;
    protected array $monthNumberLookup;
    protected array $monthNameLookup;
    protected array $typeLabels;
    protected array $asnTypeMap;
    /** @var array<string,string> */
    protected array $monetaryLabels;
    protected string $defaultType;
    protected int $defaultYear;
    protected int $defaultMonth;
    protected ?int $defaultSkpdId;

    public function __construct(
        Authenticatable $user,
        array $monthOptions,
        array $typeLabels,
        array $asnTypeMap,
        array $monetaryLabels,
        string $defaultType,
        int $defaultYear,
        int $defaultMonth,
        ?int $defaultSkpdId = null
    ) {
        $this->user = $user;
        $this->typeLabels = $typeLabels;
        $this->asnTypeMap = $asnTypeMap;
        $this->monetaryLabels = $monetaryLabels;
        $this->defaultType = $defaultType;
        $this->defaultYear = $defaultYear;
        $this->defaultMonth = $defaultMonth;
        $this->defaultSkpdId = $defaultSkpdId;

        $this->monthNumberLookup = $monthOptions;
        $this->monthNameLookup = [];

        foreach ($monthOptions as $number => $label) {
            $upper = strtoupper($label);
            $this->monthNameLookup[$upper] = (int) $number;
            $this->monthNameLookup[str_replace([' ', '-'], '', $upper)] = (int) $number;
        }
    }

    public function import(Collection $rows): void
    {
        $errors = [];
        $rowNumber = 1; // header

        foreach ($rows as $rowData) {
            $rowNumber++;
            $row = collect($rowData ?? []);

            if ($row->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
                continue;
            }

            try {
                $data = $this->mapRow($row);
                Gaji::updateOrCreate(
                    [
                        'pegawai_id' => $data['pegawai_id'],
                        'tahun' => $data['tahun'],
                        'bulan' => $data['bulan'],
                    ],
                    $data
                );
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
        $jenisAsnValue = trim((string) ($row->get('jenis_asn') ?? ''));
        $jenisAsn = $this->mapJenisAsn($jenisAsnValue === '' ? $this->defaultType : $jenisAsnValue);

        $tahun = $this->resolveYear($row);
        $bulan = $this->resolveMonth($row);

        $nip = $this->stringValue($row, ['nip', 'nip_pegawai']);
        $nik = $this->stringValue($row, ['nik', 'nik_pegawai']);

        if (! $nip && ! $nik) {
            throw new \InvalidArgumentException('Kolom NIP atau NIK wajib diisi untuk menentukan pegawai.');
        }

        $pegawaiQuery = Pegawai::query();

        if ($nip) {
            $pegawaiQuery->where('nip', $nip);
        } else {
            $pegawaiQuery->where('nik', $nik);
        }

        if ($this->defaultSkpdId !== null) {
            $pegawaiQuery->where('skpd_id', $this->defaultSkpdId);
        } elseif (! $this->user->isSuperAdmin()) {
            $pegawaiQuery->where('skpd_id', $this->user->skpd_id);
        }

        $statusFilter = array_map('strval', $this->asnTypeMap[$jenisAsn] ?? []);

        if ($jenisAsn !== $this->defaultType) {
            $statusFilter = array_merge(
                $statusFilter,
                array_map('strval', $this->asnTypeMap[$this->defaultType] ?? [])
            );
        }

        if ($jenisAsn === 'pns' || $jenisAsn === 'cpns') {
            $statusFilter = array_merge($statusFilter, ['1', '3', 'pns', 'PNS', 'cpns', 'CPNS']);
        } elseif ($jenisAsn === 'pppk') {
            $statusFilter = array_merge($statusFilter, ['2', 'pppk', 'PPPK']);
        }

        $statusFilter = array_values(array_unique(array_merge(
            $statusFilter,
            array_map('strtoupper', $statusFilter),
            array_map('strtolower', $statusFilter)
        )));

        $pegawaiQuery->whereIn('status_asn', $statusFilter);

        $pegawai = $pegawaiQuery->first();

        if (! $pegawai) {
            throw new \InvalidArgumentException('Pegawai tidak ditemukan atau tidak sesuai dengan jenis ASN.');
        }

        if ($this->defaultSkpdId !== null && (int) $pegawai->skpd_id !== $this->defaultSkpdId) {
            throw new \InvalidArgumentException('Pegawai tidak termasuk dalam SKPD yang dipilih untuk impor.');
        }

        $data = [
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $jenisAsn,
            'tahun' => $tahun,
            'bulan' => $bulan,
        ];

        foreach (array_keys($this->monetaryLabels) as $field) {
            $data[$field] = $this->decimalValue($row->get($field), $field);
        }

        $familyAllowance = ($data['perhitungan_suami_istri'] ?? 0.0) + ($data['perhitungan_anak'] ?? 0.0);
        $data['tunjangan_keluarga'] = round($familyAllowance, 2);

        return $data;
    }

    protected function resolveYear(Collection $row): int
    {
        $value = $row->get('tahun');
        if ($value === null || trim((string) $value) === '') {
            return $this->defaultYear;
        }

        if (! is_numeric($value)) {
            throw new \InvalidArgumentException('Nilai Tahun tidak valid.');
        }

        return (int) $value;
    }

    protected function resolveMonth(Collection $row): int
    {
        $value = $this->stringValue($row, ['bulan']);

        if ($value === null) {
            return $this->defaultMonth;
        }

        return $this->mapMonth($value);
    }

    protected function mapJenisAsn(string $value): string
    {
        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return $this->defaultType;
        }

        if (in_array($normalized, ['pns', 'pppk', 'cpns'], true)) {
            return $normalized;
        }

        if (in_array($normalized, ['1'], true)) {
            return 'pns';
        }

        if (in_array($normalized, ['3'], true)) {
            return 'cpns';
        }

        foreach ($this->typeLabels as $key => $label) {
            if ($normalized === strtolower($label)) {
                return $key;
            }
        }

        throw new \InvalidArgumentException('Jenis ASN harus bernilai PNS, CPNS, atau PPPK.');
    }

    protected function mapMonth(string $value): int
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Bulan wajib diisi.');
        }

        if (is_numeric($trimmed)) {
            $number = (int) $trimmed;
            if (! array_key_exists($number, $this->monthNumberLookup)) {
                throw new \InvalidArgumentException('Bulan tidak valid. Gunakan angka 1-14 atau nama bulan.');
            }

            return $number;
        }

        $upper = strtoupper($trimmed);
        $normalized = str_replace([' ', '-'], '', $upper);

        if (isset($this->monthNameLookup[$upper])) {
            return $this->monthNameLookup[$upper];
        }

        if (isset($this->monthNameLookup[$normalized])) {
            return $this->monthNameLookup[$normalized];
        }

        throw new \InvalidArgumentException("Bulan '{$value}' tidak dikenali. Gunakan angka atau nama bulan (misalnya Januari, THR, Gaji 13).");
    }

    protected function requireInteger(Collection $row, string $key, string $label): int
    {
        $value = $row->get($key);
        if ($value === null || trim((string) $value) === '') {
            throw new \InvalidArgumentException("Kolom {$label} wajib diisi.");
        }

        return (int) $value;
    }

    protected function requireString(Collection $row, string $key, string $label): string
    {
        $value = $this->stringValue($row, [$key]);

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

    protected function decimalValue($value, string $label): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = str_replace([' ', '.'], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            throw new \InvalidArgumentException("Nilai untuk kolom {$label} tidak valid.");
        }

        return (float) $normalized;
    }
}
