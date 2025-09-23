<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PegawaiTemplateExport implements FromCollection, WithHeadings
{
    protected array $statusPerkawinanOptions;
    protected array $statusAsnOptions;
    protected array $tipeJabatanOptions;

    public function __construct(array $tipeJabatanOptions, array $statusAsnOptions, array $statusPerkawinanOptions)
    {
        $this->tipeJabatanOptions = $tipeJabatanOptions;
        $this->statusAsnOptions = $statusAsnOptions;
        $this->statusPerkawinanOptions = $statusPerkawinanOptions;
    }

    public function collection()
    {
        $notes = [
            'Catatan: gunakan kode angka berikut ->',
            'Status Perkawinan: ' . $this->formatOptions($this->statusPerkawinanOptions),
            'Status ASN: ' . $this->formatOptions($this->statusAsnOptions),
            'Tipe Jabatan: ' . $this->formatOptions($this->tipeJabatanOptions),
            'Pasangan PNS: isi YA atau TIDAK',
        ];

        $rows = new Collection([
            array_fill(0, count($this->headings()), ''),
        ]);

        foreach ($notes as $note) {
            $rows->prepend([$note]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'skpd',
            'nama_lengkap',
            'nik',
            'nip',
            'npwp',
            'tempat_lahir',
            'tanggal_lahir (YYYY-MM-DD)',
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
    }

    protected function formatOptions(array $options): string
    {
        return collect($options)->map(fn ($label, $value) => "$value=$label")->implode(', ');
    }
}


