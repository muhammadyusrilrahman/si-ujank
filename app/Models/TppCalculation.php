<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pegawai;
use App\Models\Skpd;
use App\Models\User;

class TppCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'pegawai_id',
        'user_id',
        'skpd_id',
        'jenis_asn',
        'tahun',
        'bulan',
        'kelas_jabatan',
        'golongan',
        'beban_kerja',
        'kondisi_kerja',
        'extra_plt20',
        'extra_ppkd20',
        'extra_bud20',
        'extra_kbud20',
        'extra_tim_tapd20',
        'extra_tim_tpp20',
        'extra_bendahara_penerimaan10',
        'extra_bendahara_pengeluaran30',
        'extra_pengurus_barang20',
        'extra_pejabat_pengadaan10',
        'extra_tim_tapd20_from_beban',
        'extra_ppk5',
        'extra_pptk5',
        'presensi_ketidakhadiran',
        'presensi_persen_ketidakhadiran',
        'presensi_persen_kehadiran',
        'presensi_nilai',
        'kinerja_persen',
        'kinerja_nilai',
        'jumlah_tpp',
        'bruto',
        'pfk_pph21',
        'pfk_bpjs4',
        'pfk_bpjs1',
        'netto',
        'tanda_terima',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'beban_kerja' => 'decimal:2',
        'kondisi_kerja' => 'decimal:2',
        'extra_plt20' => 'decimal:2',
        'extra_ppkd20' => 'decimal:2',
        'extra_bud20' => 'decimal:2',
        'extra_kbud20' => 'decimal:2',
        'extra_tim_tapd20' => 'decimal:2',
        'extra_tim_tpp20' => 'decimal:2',
        'extra_bendahara_penerimaan10' => 'decimal:2',
        'extra_bendahara_pengeluaran30' => 'decimal:2',
        'extra_pengurus_barang20' => 'decimal:2',
        'extra_pejabat_pengadaan10' => 'decimal:2',
        'extra_tim_tapd20_from_beban' => 'decimal:2',
        'extra_ppk5' => 'decimal:2',
        'extra_pptk5' => 'decimal:2',
        'presensi_ketidakhadiran' => 'integer',
        'presensi_persen_ketidakhadiran' => 'decimal:2',
        'presensi_persen_kehadiran' => 'decimal:2',
        'presensi_nilai' => 'decimal:2',
        'kinerja_persen' => 'decimal:2',
        'kinerja_nilai' => 'decimal:2',
        'jumlah_tpp' => 'decimal:2',
        'bruto' => 'decimal:2',
        'pfk_pph21' => 'decimal:2',
        'pfk_bpjs4' => 'decimal:2',
        'pfk_bpjs1' => 'decimal:2',
        'netto' => 'decimal:2',
    ];

    public function pegawai()
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skpd()
    {
        return $this->belongsTo(Skpd::class);
    }
}
