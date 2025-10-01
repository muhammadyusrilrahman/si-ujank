<?php

namespace App\Exports;

use App\Models\Pegawai;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class PegawaiExport
{
    protected Authenticatable $user;
    protected array $headings;
    protected array $tipeOptions;
    protected array $statusAsnOptions;
    protected array $statusPerkawinanOptions;

    public function __construct(
        Authenticatable $user,
        array $headings,
        array $tipeOptions,
        array $statusAsnOptions,
        array $statusPerkawinanOptions
    ) {
        $this->user = $user;
        $this->headings = $headings;
        $this->tipeOptions = $tipeOptions;
        $this->statusAsnOptions = $statusAsnOptions;
        $this->statusPerkawinanOptions = $statusPerkawinanOptions;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function rows(): array
    {
        return $this->collection()
            ->map(fn (Pegawai $pegawai) => $this->map($pegawai))
            ->all();
    }

    protected function collection(): Collection
    {
        return Pegawai::query()
            ->when(! $this->user->isSuperAdmin(), function ($query) {
                $query->where('skpd_id', $this->user->skpd_id);
            })
            ->orderBy('nama_lengkap')
            ->get([
                'nama_lengkap',
                'nik',
                'nip',
                'npwp',
                'tanggal_lahir',
                'tipe_jabatan',
                'jabatan',
                'eselon',
                'status_asn',
                'golongan',
                'masa_kerja',
                'alamat_rumah',
                'status_perkawinan',
                'jumlah_istri_suami',
                'jumlah_anak',
                'jumlah_tanggungan',
                'pasangan_pns',
                'nip_pasangan',
                'kode_bank',
                'nama_bank',
                'nomor_rekening_pegawai',
            ]);
    }

    protected function map(Pegawai $pegawai): array
    {
        $tanggalLahir = optional($pegawai->tanggal_lahir);
        if ($tanggalLahir && method_exists($tanggalLahir, 'format')) {
            $tanggalLahir = $tanggalLahir->format('d-m-Y');
        }

        return [
            $pegawai->nip,
            $pegawai->nama_lengkap,
            $pegawai->nik,
            $pegawai->npwp,
            $tanggalLahir,
            $this->tipeOptions[$pegawai->tipe_jabatan] ?? $pegawai->tipe_jabatan,
            $pegawai->jabatan,
            $pegawai->eselon,
            $this->statusAsnOptions[$pegawai->status_asn] ?? $pegawai->status_asn,
            $pegawai->golongan,
            $pegawai->masa_kerja,
            $pegawai->alamat_rumah,
            $this->statusPerkawinanOptions[$pegawai->status_perkawinan] ?? $pegawai->status_perkawinan,
            (int) $pegawai->jumlah_istri_suami,
            (int) $pegawai->jumlah_anak,
            (int) $pegawai->jumlah_tanggungan,
            $pegawai->pasangan_pns ? 'YA' : 'TIDAK',
            $pegawai->nip_pasangan,
            $pegawai->kode_bank,
            $pegawai->nama_bank,
            $pegawai->nomor_rekening_pegawai,
        ];
    }
}
