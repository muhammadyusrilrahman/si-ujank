<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tpp extends Model
{
    use HasFactory;

    protected $fillable = [
        'pegawai_id',
        'tahun',
        'bulan',
        'jenis_asn',
        'tpp_beban_kerja',
        'tpp_tempat_bertugas',
        'tpp_kondisi_kerja',
        'tpp_kelangkaan_profesi',
        'tpp_prestasi_kerja',
        'tunjangan_pph',
        'tunjangan_jaminan_hari_tua',
        'iuran_jaminan_kesehatan',
        'iuran_jaminan_kecelakaan_kerja',
        'iuran_jaminan_kematian',
        'iuran_simpanan_tapera',
        'iuran_pensiun',
        'potongan_iwp',
        'potongan_pph_21',
        'zakat',
        'bulog',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'jenis_asn' => 'string',
        'tpp_beban_kerja' => 'decimal:2',
        'tpp_tempat_bertugas' => 'decimal:2',
        'tpp_kondisi_kerja' => 'decimal:2',
        'tpp_kelangkaan_profesi' => 'decimal:2',
        'tpp_prestasi_kerja' => 'decimal:2',
        'tunjangan_pph' => 'decimal:2',
        'tunjangan_jaminan_hari_tua' => 'decimal:2',
        'iuran_jaminan_kesehatan' => 'decimal:2',
        'iuran_jaminan_kecelakaan_kerja' => 'decimal:2',
        'iuran_jaminan_kematian' => 'decimal:2',
        'iuran_simpanan_tapera' => 'decimal:2',
        'iuran_pensiun' => 'decimal:2',
        'potongan_iwp' => 'decimal:2',
        'potongan_pph_21' => 'decimal:2',
        'zakat' => 'decimal:2',
        'bulog' => 'decimal:2',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }
}
