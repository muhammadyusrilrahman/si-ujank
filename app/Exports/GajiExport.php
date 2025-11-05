<?php

namespace App\Exports;

use App\Models\Gaji;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class GajiExport
{
    protected Authenticatable $user;
    protected string $type;
    protected array $jenisAsnScope;
    protected int $year;
    protected ?int $month;
    protected array $headings;
    /** @var string[] */
    protected array $monetaryFieldOrder;
    /** @var array<string,string> */
    protected array $monetaryLabels;
    /** @var array<string,string> */
    protected array $allowanceFields;
    /** @var array<string,string> */
    protected array $deductionFields;
    /** @var string[] */
    protected array $totalAllowanceFields;
    /** @var string[] */
    protected array $totalDeductionFields;
    /** @var array<string,string> */
    protected array $tipeJabatanOptions;
    /** @var array<string,string> */
    protected array $statusAsnOptions;
    /** @var array<string,string> */
    protected array $statusPerkawinanOptions;

    public function __construct(
        Authenticatable $user,
        string $type,
        int $year,
        ?int $month,
        array $headings,
        array $monetaryLabels,
        array $jenisAsnScope,
        array $allowanceFields,
        array $deductionFields,
        array $totalAllowanceFields,
        array $totalDeductionFields,
        array $tipeJabatanOptions,
        array $statusAsnOptions,
        array $statusPerkawinanOptions
    ) {
        $this->user = $user;
        $this->type = $type;
        $this->year = $year;
        $this->month = $month;
        $this->headings = $headings;
        $this->monetaryLabels = $monetaryLabels;
        $this->jenisAsnScope = $jenisAsnScope;
        $this->allowanceFields = $allowanceFields;
        $this->deductionFields = $deductionFields;
        $this->totalAllowanceFields = $totalAllowanceFields;
        $this->totalDeductionFields = $totalDeductionFields;
        $this->tipeJabatanOptions = $tipeJabatanOptions;
        $this->statusAsnOptions = $statusAsnOptions;
        $this->statusPerkawinanOptions = $statusPerkawinanOptions;
        $this->monetaryFieldOrder = array_keys($monetaryLabels);
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function rows(): array
    {
        return $this->collection()->map(function ($gaji) {
            return $this->map($gaji);
        })->all();
    }

    protected function collection(): Collection
    {
        $query = Gaji::query()
            ->with(['pegawai' => function ($relation) {
                $relation->select(
                    'id',
                    'nama_lengkap',
                    'nip',
                    'nik',
                    'npwp',
                    'tanggal_lahir',
                    'tipe_jabatan',
                    'jabatan',
                    'eselon',
                    'status_asn',
                    'golongan',
                    'masa_kerja',
                    'alamat_rumah',
                    'status_perkawinan',
                    'jumlah_istri_suami',
                    'jumlah_anak',
                    'jumlah_tanggungan',
                    'pasangan_pns',
                    'nip_pasangan',
                    'kode_bank',
                    'nama_bank',
                    'nomor_rekening_pegawai',
                    'skpd_id'
                );
            }])
            ->whereIn('jenis_asn', $this->jenisAsnScope)
            ->where('tahun', $this->year);

        if ($this->month !== null) {
            $query->where('bulan', $this->month);
        }

        if (! $this->user->isSuperAdmin()) {
            $query->whereHas('pegawai', function ($sub) {
                $sub->where('skpd_id', $this->user->skpd_id);
            });
        }

        return $query
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderBy('pegawai_id')
            ->get();
    }

    protected function map($gaji): array
    {
        $pegawai = $gaji->pegawai;

        $tanggalLahir = $this->formatTanggalLahir(optional($pegawai)->tanggal_lahir);
        $tipeJabatan = $this->resolveOptionCode(optional($pegawai)->tipe_jabatan, $this->tipeJabatanOptions);
        $statusAsn = $this->resolveOptionCode(optional($pegawai)->status_asn, $this->statusAsnOptions);
        $statusPerkawinan = $this->resolveOptionCode(optional($pegawai)->status_perkawinan, $this->statusPerkawinanOptions);

        $row = [
            optional($pegawai)->nip,
            optional($pegawai)->nama_lengkap,
            optional($pegawai)->nik,
            optional($pegawai)->npwp,
            $tanggalLahir,
            $tipeJabatan,
            optional($pegawai)->jabatan,
            optional($pegawai)->eselon,
            $statusAsn,
            optional($pegawai)->golongan,
            optional($pegawai)->masa_kerja,
            optional($pegawai)->alamat_rumah,
            $statusPerkawinan,
            (int) optional($pegawai)->jumlah_istri_suami,
            (int) optional($pegawai)->jumlah_anak,
            (int) optional($pegawai)->jumlah_tanggungan,
            optional($pegawai)->pasangan_pns ? 'YA' : 'TIDAK',
            optional($pegawai)->nip_pasangan,
            optional($pegawai)->kode_bank,
            optional($pegawai)->nama_bank,
            optional($pegawai)->nomor_rekening_pegawai,
        ];

        foreach ($this->monetaryFieldOrder as $field) {
            $row[] = (float) $gaji->{$field};
        }

        $totalAllowance = $this->sumFields($gaji, $this->totalAllowanceFields);
        $totalDeduction = $this->sumFields($gaji, $this->totalDeductionFields);
        $row[] = $totalAllowance;
        $row[] = $totalDeduction;
        $row[] = $totalAllowance - $totalDeduction;

        return $row;
    }

    protected function formatTanggalLahir($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('d-m-Y');
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('d-m-Y');
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return null;
            }

            try {
                return Carbon::parse($trimmed)->format('d-m-Y');
            } catch (\Throwable $exception) {
                return $trimmed;
            }
        }

        return (string) $value;
    }

    protected function resolveOptionCode($value, array $options): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            return null;
        }

        if (array_key_exists($stringValue, $options)) {
            return $stringValue;
        }

        foreach ($options as $code => $label) {
            if (strcasecmp((string) $label, $stringValue) === 0) {
                return (string) $code;
            }
        }

        return $stringValue;
    }
    protected function sumFields(Gaji $gaji, array $fields): float
    {
        $total = 0.0;
        foreach ($fields as $field) {
            $total += (float) $gaji->{$field};
        }

        return $total;
    }
}





