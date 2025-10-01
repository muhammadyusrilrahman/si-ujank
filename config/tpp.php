<?php

return [
    'types' => [
        'pns' => [
            'label' => 'PNS',
            'status_asn' => ['1', '3'],
        ],
        'pppk' => [
            'label' => 'PPPK',
            'status_asn' => ['2'],
        ],
    ],
    'months' => [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
        13 => 'THR',
        14 => 'Gaji 13',
    ],
    'monetary_fields' => [
        'tpp_beban_kerja' => [
            'label' => 'TPP Beban Kerja',
            'category' => 'allowance',
        ],
        'tpp_tempat_bertugas' => [
            'label' => 'TPP Tempat Bertugas',
            'category' => 'allowance',
        ],
        'tpp_kondisi_kerja' => [
            'label' => 'TPP Kondisi Kerja',
            'category' => 'allowance',
        ],
        'tpp_kelangkaan_profesi' => [
            'label' => 'TPP Kelangkaan Profesi',
            'category' => 'allowance',
        ],
        'tpp_prestasi_kerja' => [
            'label' => 'TPP Prestasi Kerja',
            'category' => 'allowance',
        ],
        'tunjangan_pph' => [
            'label' => 'Tunjangan PPh',
            'category' => 'allowance',
        ],
        'tunjangan_jaminan_hari_tua' => [
            'label' => 'Tunjangan Jaminan Hari Tua',
            'category' => 'allowance',
        ],
        'iuran_jaminan_kesehatan' => [
            'label' => 'Iuran Jaminan Kesehatan',
            'category' => 'deduction',
        ],
        'iuran_jaminan_kecelakaan_kerja' => [
            'label' => 'Iuran Jaminan Kecelakaan Kerja',
            'category' => 'deduction',
        ],
        'iuran_jaminan_kematian' => [
            'label' => 'Iuran Jaminan Kematian',
            'category' => 'deduction',
        ],
        'iuran_simpanan_tapera' => [
            'label' => 'Iuran Simpanan Tapera',
            'category' => 'deduction',
        ],
        'iuran_pensiun' => [
            'label' => 'Iuran Pensiun',
            'category' => 'deduction',
        ],
        'potongan_iwp' => [
            'label' => 'Potongan IWP',
            'category' => 'deduction',
        ],
        'potongan_pph_21' => [
            'label' => 'Potongan PPh 21',
            'category' => 'deduction',
        ],
        'zakat' => [
            'label' => 'Zakat',
            'category' => 'deduction',
        ],
        'bulog' => [
            'label' => 'Bulog',
            'category' => 'deduction',
        ],
    ],
];
