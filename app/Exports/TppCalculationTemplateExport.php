<?php

namespace App\Exports;

class TppCalculationTemplateExport
{
    /**
     * @var array<string,string>
     */
    private array $fieldDescriptions = [
        'jenis_asn' => 'Jenis ASN (pns atau pppk) yang akan dihitung.',
        'tahun' => 'Tahun periode (format YYYY).',
        'bulan' => 'Bulan periode (1-12, 13=THR, 14=Gaji 13).',
        'nip' => 'NIP pegawai. Isi salah satu NIP atau NIK.',
        'nik' => 'NIK pegawai jika tidak memiliki NIP.',
        'kelas_jabatan' => 'Kelas jabatan pegawai.',
        'golongan' => 'Golongan / ruang pangkat.',
        'beban_kerja' => 'Nilai beban kerja (Rp).',
        'kondisi_kerja' => 'Nilai kondisi kerja (Rp).',
        'plt20' => 'Tambahan PLT 20%.',
        'ppkd20' => 'Tambahan PPKD 20%.',
        'bud20' => 'Tambahan BUD 20%.',
        'kbud20' => 'Tambahan KBUD 20%.',
        'tim_tapd20' => 'Tambahan Tim TAPD 20%.',
        'tim_tpp20' => 'Tambahan Tim TPP 20%.',
        'bendahara_penerimaan10' => 'Tambahan Bendahara Penerimaan 10%.',
        'bendahara_pengeluaran30' => 'Tambahan Bendahara Pengeluaran 30%.',
        'pengurus_barang20' => 'Tambahan Pengurus Barang 20%.',
        'pejabat_pengadaan10' => 'Tambahan Pejabat Pengadaan 10%.',
        'tim_tapd20_from_beban' => 'Tambahan Tim TAPD 20% dari beban kerja.',
        'ppk5' => 'Tambahan PPK 5%.',
        'pptk5' => 'Tambahan PPTK 5%.',
        'presensi_ketidakhadiran' => 'Jumlah ketidakhadiran (hari).',
        'presensi_persen_ketidakhadiran' => 'Persentase ketidakhadiran (0-100).',
        'presensi_persen_kehadiran' => 'Persentase kehadiran (0-100). Boleh dikosongkan.',
        'presensi_nilai' => 'Nilai presensi (Rp).',
        'kinerja_persen' => 'Persentase kinerja (0-100).',
            'kinerja_nilai' => 'Nilai kinerja (Rp). Kosongkan untuk dihitung otomatis.',
            'pfk_pph21' => 'Potongan PPh Pasal 21 (Rp).',
            'tanda_terima' => 'Nomor rekening pegawai. Bila dikosongkan akan diisi otomatis dari data pegawai.',
    ];

    /**
     * @return array<int,array<int|string>>
     */
    public function rows(): array
    {
        $headings = [
            'jenis_asn',
            'tahun',
            'bulan',
            'nip',
            'nik',
            'kelas_jabatan',
            'golongan',
            'beban_kerja',
            'kondisi_kerja',
            'plt20',
            'ppkd20',
            'bud20',
            'kbud20',
            'tim_tapd20',
            'tim_tpp20',
            'bendahara_penerimaan10',
            'bendahara_pengeluaran30',
            'pengurus_barang20',
            'pejabat_pengadaan10',
            'tim_tapd20_from_beban',
            'ppk5',
            'pptk5',
            'presensi_ketidakhadiran',
            'presensi_persen_ketidakhadiran',
            'presensi_persen_kehadiran',
            'presensi_nilai',
            'kinerja_persen',
            'kinerja_nilai',
            'pfk_pph21',
            'tanda_terima',
        ];

        $sampleRow = [
            'pns',
            date('Y'),
            1,
            '198001011990031001',
            '3301010101010001',
            '9',
            'III/c',
            7500000,
            1250000,
            1500000,
            0,
            0,
            0,
            1500000,
            0,
            0,
            0,
            0,
            750000,
            1500000,
            375000,
            375000,
            0,
            5,
            95,
            2500000,
            60,
            4500000,
            1200000,
            '1234567890123456',
        ];

        $noteRow = ['Keterangan:', 'Isi kolom menggunakan angka atau teks sesuai kebutuhan. Angka tanpa pemisah ribuan.'];

        return [
            $noteRow,
            $headings,
            $sampleRow,
        ];
    }

    /**
     * @return array<int,array<int|string>>
     */
    public function sheets(): array
    {
        return [
            [
                'title' => 'Template',
                'rows' => $this->rows(),
            ],
            [
                'title' => 'Keterangan',
                'rows' => $this->descriptionRows(),
            ],
        ];
    }

    /**
     * @return array<int,array<int|string>>
     */
    private function descriptionRows(): array
    {
        $rows = [
            ['Field', 'Keterangan'],
        ];

        foreach ($this->fieldDescriptions as $field => $description) {
            $rows[] = [$field, $description];
        }

        return $rows;
    }
}
