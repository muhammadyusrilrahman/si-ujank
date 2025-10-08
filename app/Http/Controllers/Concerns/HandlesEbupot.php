<?php

namespace App\Http\Controllers\Concerns;

use App\Models\EbupotReport;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

trait HandlesEbupot
{
    private const TER_STATUS_GROUP_A = ['TK/0', 'TK/1', 'K/0', 'HB/0', 'HB/1'];
    private const TER_STATUS_GROUP_B = ['TK/2', 'TK/3', 'K/1', 'K/2', 'HB/2', 'HB/3'];

    private const TER_A_BANDS = [
        [5_400_000, 0.0],
        [5_650_000, 0.25],
        [5_950_000, 0.5],
        [6_300_000, 0.75],
        [6_750_000, 1.0],
        [7_500_000, 1.25],
        [8_550_000, 1.5],
        [9_650_000, 1.75],
        [10_050_000, 2.0],
        [10_350_000, 2.25],
        [10_700_000, 2.5],
        [11_050_000, 3.0],
        [11_600_000, 3.5],
        [12_500_000, 4.0],
        [13_750_000, 5.0],
        [15_100_000, 6.0],
        [16_950_000, 7.0],
        [19_750_000, 8.0],
        [24_150_000, 9.0],
        [26_450_000, 10.0],
        [28_000_000, 11.0],
        [30_050_000, 12.0],
        [32_400_000, 13.0],
        [35_400_000, 14.0],
        [39_100_000, 15.0],
        [43_850_000, 16.0],
        [47_800_000, 17.0],
        [51_400_000, 18.0],
        [56_300_000, 19.0],
        [62_200_000, 20.0],
        [68_600_000, 21.0],
        [77_500_000, 22.0],
        [89_000_000, 23.0],
        [103_000_000, 24.0],
        [125_000_000, 25.0],
        [157_000_000, 26.0],
        [206_000_000, 27.0],
        [337_000_000, 28.0],
        [454_000_000, 29.0],
        [550_000_000, 30.0],
        [695_000_000, 31.0],
        [910_000_000, 32.0],
        [1_400_000_000, 33.0],
    ];

    private const TER_B_BANDS = [
        [6_200_000, 0.0],
        [6_500_000, 0.25],
        [6_850_000, 0.5],
        [7_300_000, 0.75],
        [9_200_000, 1.0],
        [10_750_000, 1.5],
        [11_250_000, 2.0],
        [11_600_000, 2.5],
        [12_600_000, 3.0],
        [13_600_000, 4.0],
        [14_950_000, 5.0],
        [16_400_000, 6.0],
        [18_450_000, 7.0],
        [21_850_000, 8.0],
        [26_000_000, 9.0],
        [27_700_000, 10.0],
        [29_350_000, 11.0],
        [31_450_000, 12.0],
        [33_950_000, 13.0],
        [37_100_000, 14.0],
        [41_100_000, 15.0],
        [45_800_000, 16.0],
        [49_500_000, 17.0],
        [53_800_000, 18.0],
        [58_500_000, 19.0],
        [64_000_000, 20.0],
        [71_000_000, 21.0],
        [80_000_000, 22.0],
        [93_000_000, 23.0],
        [109_000_000, 24.0],
        [129_000_000, 25.0],
        [163_000_000, 26.0],
        [211_000_000, 27.0],
        [374_000_000, 28.0],
        [459_000_000, 29.0],
        [555_000_000, 30.0],
        [704_000_000, 31.0],
        [957_000_000, 32.0],
        [1_405_000_000, 33.0],
    ];

    private const TER_C_BANDS = [
        [6_600_000, 0.0],
        [6_950_000, 0.25],
        [7_350_000, 0.5],
        [7_800_000, 0.75],
        [8_850_000, 1.0],
        [9_800_000, 1.25],
        [10_950_000, 1.5],
        [11_200_000, 1.75],
        [12_050_000, 2.0],
        [12_950_000, 3.0],
        [14_350_000, 4.0],
        [15_900_000, 5.0],
        [17_650_000, 6.0],
        [19_650_000, 7.0],
        [22_150_000, 8.0],
        [25_350_000, 9.0],
        [27_400_000, 10.0],
        [29_250_000, 11.0],
        [31_650_000, 12.0],
        [34_400_000, 13.0],
        [37_600_000, 14.0],
        [41_450_000, 15.0],
        [45_900_000, 16.0],
        [50_150_000, 17.0],
        [54_850_000, 18.0],
        [60_300_000, 19.0],
        [66_300_000, 20.0],
        [73_600_000, 21.0],
        [82_100_000, 22.0],
        [91_500_000, 23.0],
        [103_500_000, 24.0],
        [118_500_000, 25.0],
        [137_000_000, 26.0],
        [160_500_000, 27.0],
        [190_500_000, 28.0],
        [229_500_000, 29.0],
        [282_000_000, 30.0],
        [356_000_000, 31.0],
        [472_000_000, 32.0],
        [702_000_000, 33.0],
    ];

    private const TER_DEFAULT_RATE = 34.0;

    private function buildEbupotXml(Collection $entries, string $tin, int $taxMonth, int $taxYear): string
    {
        $sanitizedTin = $this->digitsOnly($tin);
        if ($sanitizedTin === '') {
            $sanitizedTin = '0000000000000000';
        }

        $document = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><MmPayrollBulk xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></MmPayrollBulk>');
        $document->addChild('TIN', $sanitizedTin);
        $list = $document->addChild('ListOfMmPayroll');

        foreach ($entries as $entry) {
            $periodMonth = (int) ($entry['masa_pajak'] ?? $taxMonth);
            if ($periodMonth < 1 || $periodMonth > 12) {
                $periodMonth = $taxMonth;
            }

            $periodYear = (int) ($entry['tahun_pajak'] ?? $taxYear);
            if ($periodYear < 2000) {
                $periodYear = $taxYear;
            }

            $item = $list->addChild('MmPayroll');
            $item->addChild('TaxPeriodMonth', (string) $periodMonth);
            $item->addChild('TaxPeriodYear', (string) $periodYear);
            $item->addChild('CounterpartOpt', $entry['status_pegawai'] ?? 'Resident');

            $passportValue = $entry['nomor_passport'] ?? '';
            if ($passportValue === '') {
                $passport = $item->addChild('CounterpartPassport');
                $passport->addAttribute('xsi:nil', 'true');
            } else {
                $item->addChild('CounterpartPassport', $passportValue);
            }

            $item->addChild('CounterpartTin', $this->digitsOnly($entry['npwp_nik_tin'] ?? ''));
            $item->addChild('StatusTaxExemption', $entry['status'] ?? '');
            $item->addChild('Position', $entry['posisi'] ?? 'IRT');
            $item->addChild('TaxCertificate', $entry['sertifikat_fasilitas'] ?? 'N/A');
            $item->addChild('TaxObjectCode', $entry['kode_objek_pajak'] ?? '21-100-01');
            $item->addChild('Gross', $this->formatDecimal((float) ($entry['gross'] ?? 0.0)));
            $item->addChild('Rate', $this->formatDecimal((float) ($entry['tarif'] ?? 0.0)));
            $item->addChild('IDPlaceOfBusinessActivity', $this->digitsOnly($entry['id_tku'] ?? ''));

            $withholdingDate = $entry['tgl_pemotongan'] ?? '';
            if ($withholdingDate === '') {
                $withholdingDate = Carbon::create($taxYear, max(1, min(12, $taxMonth)), 1)
                    ->endOfMonth()
                    ->format('Y-m-d');
            }

            $item->addChild('WithholdingDate', $withholdingDate);
        }

        return (string) $document->asXML();
    }

    private function persistEbupotReport(
        User $currentUser,
        string $jenisAsn,
        int $year,
        int $month,
        string $defaultTin,
        string $defaultIdTku,
        string $defaultKodeObjek,
        string $defaultCutOff,
        Collection $entries,
        string $source
    ): EbupotReport {
        $sanitizedTin = $this->digitsOnly($defaultTin);
        $sanitizedIdTku = $this->digitsOnly($defaultIdTku);
        $payload = [
            'entries' => $entries->map(fn (array $entry): array => $entry)->toArray(),
            'source' => $source,
        ];

        return EbupotReport::updateOrCreate(
            [
                'source' => $source,
                'skpd_id' => $currentUser->skpd_id,
                'jenis_asn' => $jenisAsn,
                'tahun' => $year,
                'bulan' => $month,
            ],
            [
                'user_id' => $currentUser->id,
                'npwp_pemotong' => $sanitizedTin,
                'id_tku' => $sanitizedIdTku,
                'kode_objek' => $defaultKodeObjek,
                'cut_off_date' => $defaultCutOff,
                'entry_count' => $entries->count(),
                'total_gross' => $entries->sum(fn (array $entry): float => (float) ($entry['gross'] ?? 0)),
                'payload' => $payload,
            ]
        );
    }

    private function ebupotHeadings(): array
    {
        return [
            'NPWP Pemotong',
            'Masa Pajak',
            'Tahun Pajak',
            'Status Pegawai',
            'NPWP/NIK/TIN',
            'Nomor Passport',
            'Status',
            'Posisi',
            'Sertifikat/Fasilitas',
            'Kode Objek Pajak',
            'Penghasilan Kotor',
            'Tarif',
            'ID TKU',
            'Tanggal Pemotongan',
        ];
    }

    private function mapEbupotRows(Collection $entries): array
    {
        return $entries
            ->map(function (array $entry): array {
                $gross = array_key_exists('gross_formatted', $entry)
                    ? (string) $entry['gross_formatted']
                    : number_format((float) ($entry['gross'] ?? 0), 2, '.', '');
                $tarif = array_key_exists('tarif_formatted', $entry)
                    ? (string) $entry['tarif_formatted']
                    : number_format((float) ($entry['tarif'] ?? 0), 4, '.', '');

                return [
                    $entry['npwp_pemotong'],
                    $entry['masa_pajak'],
                    $entry['tahun_pajak'],
                    $entry['status_pegawai'],
                    $entry['npwp_nik_tin'],
                    $entry['nomor_passport'],
                    $entry['status'],
                    $entry['posisi'],
                    $entry['sertifikat_fasilitas'],
                    $entry['kode_objek_pajak'],
                    $gross,
                    $tarif,
                    $entry['id_tku'],
                    $entry['tgl_pemotongan'],
                ];
            })
            ->toArray();
    }

    private function ensureCanAccessEbupotReport(EbupotReport $report, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) ($report->skpd_id ?? 0) !== (int) ($user->skpd_id ?? 0)) {
            abort(403, 'Anda tidak memiliki akses ke data e-Bupot tersebut.');
        }
    }

    private function formatDecimal(float $value): string
    {
        if (abs($value - round($value)) < 0.00001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    }

    private function digitsOnly(string $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', $value);

        return $digits ?? '';
    }

    private function calculateTerRate(float $gross, array $bands): float
    {
        foreach ($bands as [$max, $rate]) {
            if ($gross <= $max) {
                return $rate;
            }
        }

        return self::TER_DEFAULT_RATE;
    }

    private function determineTarif(string $status, float $terA, float $terB, float $terC): float
    {
        $normalized = strtoupper($status);

        if (in_array($normalized, self::TER_STATUS_GROUP_A, true)) {
            return $terA;
        }

        if (in_array($normalized, self::TER_STATUS_GROUP_B, true)) {
            return $terB;
        }

        return $terC;
    }
}
