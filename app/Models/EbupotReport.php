<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EbupotReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skpd_id',
        'source',
        'jenis_asn',
        'tahun',
        'bulan',
        'npwp_pemotong',
        'id_tku',
        'kode_objek',
        'cut_off_date',
        'entry_count',
        'total_gross',
        'payload',
    ];

    protected $casts = [
        'tahun' => 'integer',
        'bulan' => 'integer',
        'cut_off_date' => 'date',
        'entry_count' => 'integer',
        'total_gross' => 'decimal:2',
        'payload' => 'array',
        'source' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skpd()
    {
        return $this->belongsTo(Skpd::class);
    }
}
