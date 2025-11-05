<?php

namespace App\Services;

use App\Models\Pegawai;
use App\Models\TppCalculation;
use Illuminate\Support\Collection;

class TppCalculationService
{
    public const EXTRA_FIELDS = [
        'plt20' => 'extra_plt20',
        'ppkd20' => 'extra_ppkd20',
        'bud20' => 'extra_bud20',
        'kbud20' => 'extra_kbud20',
        'tim_tapd20' => 'extra_tim_tapd20',
        'tim_tpp20' => 'extra_tim_tpp20',
        'bendahara_penerimaan10' => 'extra_bendahara_penerimaan10',
        'bendahara_pengeluaran30' => 'extra_bendahara_pengeluaran30',
        'pengurus_barang20' => 'extra_pengurus_barang20',
        'pejabat_pengadaan10' => 'extra_pejabat_pengadaan10',
        'tim_tapd20_from_beban' => 'extra_tim_tapd20_from_beban',
        'ppk5' => 'extra_ppk5',
        'pptk5' => 'extra_pptk5',
    ];

    /**
     * @return array<string,string>
     */
    public function extraFieldMap(): array
    {
        return self::EXTRA_FIELDS;
    }

    /**
     * Normalize raw input and calculate derived numeric fields.
     *
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function normalizeInput(array $input): array
    {
        $bebanKerja = $this->number($input['beban_kerja'] ?? 0);
        $kondisiKerja = $this->number($input['kondisi_kerja'] ?? 0);

        $extras = [];
        $extrasTotal = 0.0;
        foreach (self::EXTRA_FIELDS as $key => $column) {
            $value = $this->number($input[$key] ?? 0);
            $extras[$column] = $value;
            $extrasTotal += $value;
        }

        $jumlahTpp = $this->roundAmount($bebanKerja + $kondisiKerja + $extrasTotal);

        $presensiKetidakhadiran = max(0.0, $this->number($input['presensi_ketidakhadiran'] ?? 0));
        $presensiPersentaseKetidakhadiran = $this->roundAmount(min(40.0, $presensiKetidakhadiran * 3));
        $presensiPersentaseKehadiran = $this->roundAmount(max(0.0, 40.0 - $presensiPersentaseKetidakhadiran));
        $presensiNilai = $this->roundCurrency($jumlahTpp * ($presensiPersentaseKehadiran / 100));

        $kinerjaPersentaseInput = $input['kinerja_persen'] ?? 60;
        $kinerjaPersentase = $this->roundAmount(max(0.0, min(60.0, $this->number($kinerjaPersentaseInput))));
        $kinerjaNilai = $this->roundCurrency($jumlahTpp * ($kinerjaPersentase / 100));

        $pfkPph21 = $this->number($input['pfk_pph21'] ?? 0);
        $pfkBpjs4 = $this->number($input['pfk_bpjs4'] ?? 0);
        $pfkBpjs1 = $this->number($input['pfk_bpjs1'] ?? 0);

        $bruto = $this->roundAmount($presensiNilai + $kinerjaNilai + $pfkPph21 + $pfkBpjs4);
        $netto = $this->roundAmount($bruto - ($pfkPph21 + $pfkBpjs4 + $pfkBpjs1));

        return array_merge($extras, [
            'beban_kerja' => $bebanKerja,
            'kondisi_kerja' => $kondisiKerja,
            'jumlah_tpp' => $jumlahTpp,
            'bruto' => $bruto,
            'pfk_pph21' => $pfkPph21,
            'pfk_bpjs4' => $pfkBpjs4,
            'pfk_bpjs1' => $pfkBpjs1,
            'netto' => $netto,
            'presensi_ketidakhadiran' => $presensiKetidakhadiran,
            'presensi_persen_ketidakhadiran' => $presensiPersentaseKetidakhadiran,
            'presensi_persen_kehadiran' => $presensiPersentaseKehadiran,
            'presensi_nilai' => $presensiNilai,
            'kinerja_persen' => $kinerjaPersentase,
            'kinerja_nilai' => $kinerjaNilai,
            'tanda_terima' => trim((string) ($input['tanda_terima'] ?? '')),
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    public function formPayload(?TppCalculation $calculation = null, ?Pegawai $pegawai = null): array
    {
        $defaultAccount = optional($pegawai)->nomor_rekening_pegawai ?? '';

        $payload = [
            'kelas_jabatan' => optional($calculation)->kelas_jabatan ?? (optional($pegawai)->tipe_jabatan ?? ''),
            'golongan' => optional($calculation)->golongan ?? (optional($pegawai)->golongan ?? ''),
            'beban_kerja' => optional($calculation)->beban_kerja ?? 0,
            'kondisi_kerja' => optional($calculation)->kondisi_kerja ?? 0,
            'presensi_ketidakhadiran' => optional($calculation)->presensi_ketidakhadiran ?? 0,
            'presensi_persen_ketidakhadiran' => optional($calculation)->presensi_persen_ketidakhadiran ?? 0,
            'presensi_persen_kehadiran' => optional($calculation)->presensi_persen_kehadiran ?? 40,
            'presensi_nilai' => optional($calculation)->presensi_nilai ?? 0,
            'kinerja_persen' => optional($calculation)->kinerja_persen ?? 60,
            'kinerja_nilai' => optional($calculation)->kinerja_nilai ?? 0,
            'pfk_pph21' => optional($calculation)->pfk_pph21 ?? 0,
            'tanda_terima' => optional($calculation)->tanda_terima ?? $defaultAccount,
        ];

        foreach (self::EXTRA_FIELDS as $key => $column) {
            $payload[$key] = optional($calculation)->{$column} ?? 0;
        }

        $payload['jumlah_tpp'] = optional($calculation)->jumlah_tpp ?? 0;
        $payload['bruto'] = optional($calculation)->bruto ?? 0;
        $payload['pfk_bpjs4'] = optional($calculation)->pfk_bpjs4 ?? 0;
        $payload['pfk_bpjs1'] = optional($calculation)->pfk_bpjs1 ?? 0;
        $payload['netto'] = optional($calculation)->netto ?? 0;

        return $payload;
    }

    /**
     * @return array<string,mixed>
     */
    public function formatForView(TppCalculation $calculation): array
    {
        $extras = [];
        foreach (self::EXTRA_FIELDS as $key => $column) {
            $extras[$key] = $this->number($calculation->{$column});
        }

        $pegawai = $calculation->pegawai;

        return [
            'id' => $calculation->id,
            'pegawai' => [
                'nama' => $pegawai->nama_lengkap ?? '-',
                'nip' => $pegawai->nip ?? '-',
                'jabatan' => $pegawai->jabatan ?? '-',
            ],
            'kelas_jabatan' => $calculation->kelas_jabatan ?? '-',
            'golongan' => $calculation->golongan ?? '-',
            'beban_kerja' => $this->number($calculation->beban_kerja),
            'kondisi_kerja' => $this->number($calculation->kondisi_kerja),
            'extras' => $extras,
            'jumlah_tpp' => $this->number($calculation->jumlah_tpp),
            'presensi' => [
                'ketidakhadiran' => (int) $calculation->presensi_ketidakhadiran,
                'persentase_ketidakhadiran' => $this->number($calculation->presensi_persen_ketidakhadiran),
                'persentase_kehadiran' => $this->number($calculation->presensi_persen_kehadiran),
                'nilai' => round((float) $calculation->presensi_nilai),
            ],
            'kinerja' => [
                'persentase' => $this->number($calculation->kinerja_persen),
                'nilai' => round((float) $calculation->kinerja_nilai),
            ],
            'bruto' => $this->number($calculation->bruto),
            'pfk' => [
                'pph21' => $this->number($calculation->pfk_pph21),
                'bpjs4' => $this->number($calculation->pfk_bpjs4),
                'bpjs1' => $this->number($calculation->pfk_bpjs1),
            ],
            'netto' => $this->number($calculation->netto),
            'tanda_terima' => $calculation->tanda_terima ?? '',
        ];
    }

    /**
     * @param  Collection<int,TppCalculation>  $calculations
     * @return array<string,float>
     */
    public function summarize(Collection $calculations): array
    {
        $totals = [
            'beban_kerja' => 0.0,
            'kondisi_kerja' => 0.0,
            'extras' => 0.0,
            'jumlah_tpp' => 0.0,
            'bruto' => 0.0,
            'presensi_nilai' => 0.0,
            'kinerja_nilai' => 0.0,
            'pfk_pph21' => 0.0,
            'pfk_bpjs4' => 0.0,
            'pfk_bpjs1' => 0.0,
            'netto' => 0.0,
        ];

        foreach ($calculations as $calculation) {
            $totals['beban_kerja'] += $this->number($calculation->beban_kerja);
            $totals['kondisi_kerja'] += $this->number($calculation->kondisi_kerja);
            $totals['jumlah_tpp'] += $this->number($calculation->jumlah_tpp);
            $totals['bruto'] += $this->number($calculation->bruto);
            $totals['presensi_nilai'] += round((float) $calculation->presensi_nilai);
            $totals['kinerja_nilai'] += round((float) $calculation->kinerja_nilai);
            $totals['pfk_pph21'] += $this->number($calculation->pfk_pph21);
            $totals['pfk_bpjs4'] += $this->number($calculation->pfk_bpjs4);
            $totals['pfk_bpjs1'] += $this->number($calculation->pfk_bpjs1);
            $totals['netto'] += $this->number($calculation->netto);

            foreach (self::EXTRA_FIELDS as $column) {
                $totals['extras'] += $this->number($calculation->{$column});
            }
        }

        foreach ($totals as $key => $value) {
            $totals[$key] = $this->roundAmount($value);
        }

        return $totals;
    }

    private function number($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $normalized = str_replace([' ', '.'], '', (string) $value);
        $normalized = str_replace(',', '.', $normalized);

        return round((float) $normalized, 2);
    }

    private function roundAmount(float $value): float
    {
        return round($value, 2);
    }

    private function roundCurrency(float $value): float
    {
        return round($value);
    }

    private function clampPercentage($value, float $default): float
    {
        if ($value === null || $value === '') {
            $percentage = $default;
        } else {
            $percentage = $this->number($value);
        }

        if ($percentage < 0) {
            return 0.0;
        }

        if ($percentage > 100) {
            return 100.0;
        }

        return $percentage;
    }
}
