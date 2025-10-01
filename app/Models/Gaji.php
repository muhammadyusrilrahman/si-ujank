<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gaji extends Model
{
    use HasFactory;

    protected $fillable = [
        'pegawai_id',
        'tahun',
        'bulan',
        'jenis_asn',
        'gaji_pokok',
        'perhitungan_suami_istri',
        'perhitungan_anak',
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
        'iuran_pensiun',
        'tunjangan_khusus_papua',
        'tunjangan_jaminan_hari_tua',
        'potongan_iwp',
        'potongan_pph_21',
        'zakat',
        'bulog',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'jenis_asn' => 'string',
        'gaji_pokok' => 'decimal:2',
        'perhitungan_suami_istri' => 'decimal:2',
        'perhitungan_anak' => 'decimal:2',
        'tunjangan_keluarga' => 'decimal:2',
        'tunjangan_jabatan' => 'decimal:2',
        'tunjangan_fungsional' => 'decimal:2',
        'tunjangan_fungsional_umum' => 'decimal:2',
        'tunjangan_beras' => 'decimal:2',
        'tunjangan_pph' => 'decimal:2',
        'pembulatan_gaji' => 'decimal:2',
        'iuran_jaminan_kesehatan' => 'decimal:2',
        'iuran_jaminan_kecelakaan_kerja' => 'decimal:2',
        'iuran_jaminan_kematian' => 'decimal:2',
        'iuran_simpanan_tapera' => 'decimal:2',
        'iuran_pensiun' => 'decimal:2',
        'tunjangan_khusus_papua' => 'decimal:2',
        'tunjangan_jaminan_hari_tua' => 'decimal:2',
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
