<?php

namespace App\Exports;

use App\Models\Tpp;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TppExport
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
    protected array $totalTppFields;
    /** @var string[] */
    protected array $totalPotonganFields;
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
        array $totalTppFields,
        array $totalPotonganFields,
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
        $this->totalTppFields = $totalTppFields;
        $this->totalPotonganFields = $totalPotonganFields;
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
        return $this->collection()->map(function ($tpp) {
            return $this->map($tpp);
        })->all();
    }

    protected function collection(): Collection
    {
        $query = Tpp::query()
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

    protected function map($tpp): array
    {
        $pegawai = $tpp->pegawai;

        $tanggalLahir = $this->formatTanggalLahir(optional($pegawai)->tanggal_lahir);
        $tipeJabatan = $this->resolveOptionCode(optional($pegawai)->tipe_jabatan, $this->tipeJabatanOptions);
        $statusAsn = $this->resolveOptionCode(optional($pegawai)->status_asn, $this->statusAsnOptions);

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
            optional($pegawai)->kode_bank,
            optional($pegawai)->nama_bank,
            optional($pegawai)->nomor_rekening_pegawai,
        ];

        foreach ($this->monetaryFieldOrder as $field) {
            $row[] = (float) $tpp->{$field};
        }

        $totalAllowance = $this->sumFields($tpp, $this->totalTppFields);
        $totalDeduction = $this->sumFields($tpp, $this->totalPotonganFields);
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

    protected function sumFields(Tpp $tpp, array $fields): float
    {
        $total = 0.0;
        foreach ($fields as $field) {
            $total += (float) $tpp->{$field};
        }

        return $total;
    }
}
