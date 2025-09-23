<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pegawai extends Model
{
    use HasFactory;

        protected $fillable = [
        'skpd_id',
        'nama_lengkap',
        'nik',
        'nip',
        'npwp',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'status_perkawinan',
        'jumlah_istri_suami',
        'jumlah_anak',
        'jabatan',
        'eselon',
        'golongan',
        'email',
        'alamat_rumah',
        'masa_kerja',
        'jumlah_tanggungan',
        'pasangan_pns',
        'nip_pasangan',
        'kode_bank',
        'nama_bank',
        'nomor_rekening_pegawai',
        'tipe_jabatan',
        'status_asn',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'pasangan_pns' => 'boolean',
    ];

    public function skpd()
    {
        return $this->belongsTo(Skpd::class);
    }
}





