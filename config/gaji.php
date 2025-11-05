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
        'gaji_pokok' => [
            'label' => 'Gaji Pokok',
            'category' => 'allowance',
        ],
        'perhitungan_suami_istri' => [
            'label' => 'Perhitungan Suami/Istri',
            'category' => 'allowance',
        ],
        'perhitungan_anak' => [
            'label' => 'Perhitungan Anak',
            'category' => 'allowance',
        ],
        'tunjangan_keluarga' => [
            'label' => 'Tunjangan Keluarga',
            'category' => 'allowance',
        ],
        'tunjangan_jabatan' => [
            'label' => 'Tunjangan Jabatan',
            'category' => 'allowance',
        ],
        'tunjangan_fungsional' => [
            'label' => 'Tunjangan Fungsional',
            'category' => 'allowance',
        ],
        'tunjangan_fungsional_umum' => [
            'label' => 'Tunjangan Fungsional Umum',
            'category' => 'allowance',
        ],
        'tunjangan_beras' => [
            'label' => 'Tunjangan Beras',
            'category' => 'allowance',
        ],
        'tunjangan_pph' => [
            'label' => 'Tunjangan PPh',
            'category' => 'allowance',
        ],
        'pembulatan_gaji' => [
            'label' => 'Pembulatan Gaji',
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
        'tunjangan_khusus_papua' => [
            'label' => 'Tunjangan Khusus Papua',
            'category' => 'allowance',
        ],
        'tunjangan_jaminan_hari_tua' => [
            'label' => 'Tunjangan Jaminan Hari Tua',
            'category' => 'allowance',
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
    'total_allowance_fields' => [
        'gaji_pokok',
        'tunjangan_keluarga',
        'tunjangan_jabatan',
        'tunjangan_fungsional',
        'tunjangan_fungsional_umum',
        'tunjangan_beras',
        'tunjangan_pph',
        'pembulatan_gaji',
        'iuran_jaminan_kesehatan',
        'iuran_jaminan_kecelakaan_kerja',
        'iuran_jaminan_kematian',
        'iuran_simpanan_tapera',
        'tunjangan_khusus_papua',
    ],
    'total_deduction_fields' => [
        'iuran_jaminan_kesehatan',
        'iuran_jaminan_kecelakaan_kerja',
        'iuran_jaminan_kematian',
        'iuran_simpanan_tapera',
        'iuran_pensiun',
        'tunjangan_jaminan_hari_tua',
        'potongan_iwp',
        'potongan_pph_21',
        'zakat',
        'bulog',
    ],
];
