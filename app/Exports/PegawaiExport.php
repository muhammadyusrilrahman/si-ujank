<?php

namespace App\Exports;

use App\Models\Pegawai;
use Illuminate\Contracts\Auth\Authenticatable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PegawaiExport implements FromCollection, WithHeadings, WithMapping
{
    protected Authenticatable $user;
    protected array $tipeOptions;
    protected array $statusAsnOptions;
    protected array $statusPerkawinanOptions;

    public function __construct(Authenticatable $user, array $tipeOptions, array $statusAsnOptions, array $statusPerkawinanOptions)
    {
        $this->user = $user;
        $this->tipeOptions = $tipeOptions;
        $this->statusAsnOptions = $statusAsnOptions;
        $this->statusPerkawinanOptions = $statusPerkawinanOptions;
    }

    public function collection()
    {
        $query = Pegawai::orderBy('nama_lengkap');

        if (! $this->user->isSuperAdmin()) {
            $query->where('skpd_id', $this->user->skpd_id);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'NIP Pegawai',
            'Nama Pegawai',
            'NIK Pegawai',
            'NPWP Pegawai',
            'Tanggal Lahir Pegawai',
            'Tipe Jabatan',
            'Nama Jabatan',
            'Eselon',
            'Status ASN',
            'Golongan',
            'Masa Kerja Golongan',
            'Alamat',
            'Status Pernikahan',
            'Jumlah Istri/Suami',
            'Jumlah Anak',
            'Jumlah Tanggungan',
            'Pasangan PNS',
            'NIP Pasangan',
            'Kode Bank',
            'Nama Bank',
            'Nomor Rekening Bank Pegawai',
        ];
    }

    public function map($pegawai): array
    {
        return [
            $pegawai->nip,
            $pegawai->nama_lengkap,
            $pegawai->nik,
            $pegawai->npwp,
            optional($pegawai->tanggal_lahir)?->format('d-m-Y'),
            $this->tipeOptions[$pegawai->tipe_jabatan] ?? $pegawai->tipe_jabatan,
            $pegawai->jabatan,
            $pegawai->eselon,
            $this->statusAsnOptions[$pegawai->status_asn] ?? $pegawai->status_asn,
            $pegawai->golongan,
            $pegawai->masa_kerja,
            $pegawai->alamat_rumah,
            $this->statusPerkawinanOptions[$pegawai->status_perkawinan] ?? $pegawai->status_perkawinan,
            $pegawai->jumlah_istri_suami,
            $pegawai->jumlah_anak,
            $pegawai->jumlah_tanggungan,
            $pegawai->pasangan_pns ? 'YA' : 'TIDAK',
            $pegawai->nip_pasangan,
            $pegawai->kode_bank,
            $pegawai->nama_bank,
            $pegawai->nomor_rekening_pegawai,
        ];
    }
}
