<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();
        abort_unless($currentUser !== null, 401);

        $perPageOptions = [25, 50, 100];

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
            'search' => ['nullable', 'string', 'max:255'],
            'skpd_id' => ['nullable', 'integer', 'exists:skpds,id'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? $perPageOptions[0]);
        $searchTerm = isset($validated['search']) ? trim((string) $validated['search']) : null;
        if ($searchTerm === '') {
            $searchTerm = null;
        }

        $query = Pegawai::query()
            ->with('skpd:id,name')
            ->orderBy('nama_lengkap');

        if ($currentUser->isSuperAdmin()) {
            if (! empty($validated['skpd_id'])) {
                $query->where('skpd_id', $validated['skpd_id']);
            }
        } else {
            $query->where('skpd_id', $currentUser->skpd_id);
        }

        if ($searchTerm !== null) {
            $query->where(function ($inner) use ($searchTerm) {
                $like = '%' . $searchTerm . '%';
                $inner->where('nama_lengkap', 'like', $like)
                    ->orWhere('nik', 'like', $like)
                    ->orWhere('nip', 'like', $like)
                    ->orWhere('jabatan', 'like', $like);
            });
        }

        $pegawais = $query->paginate($perPage)->appends($request->query());

        $items = $pegawais->map(function (Pegawai $pegawai) use ($currentUser) {
            $canManageRow = $currentUser->isSuperAdmin()
                || ($currentUser->isAdminUnit() && $pegawai->skpd_id === $currentUser->skpd_id);
            return [
                'id' => $pegawai->id,
                'name' => $pegawai->nama_lengkap,
                'skpd' => optional($pegawai->skpd)->name ?? '-',
                'fields' => [
                    'nip' => $pegawai->nip ?? '-',
                    'nama' => $pegawai->nama_lengkap,
                    'nik' => $pegawai->nik ?? '-',
                    'npwp' => $pegawai->npwp ?? '-',
                    'tanggal_lahir' => optional($pegawai->tanggal_lahir)?->format('d-m-Y') ?? '-',
                    'tipe_jabatan' => $pegawai->tipe_jabatan ?? '-',
                    'jabatan' => $pegawai->jabatan ?? '-',
                    'eselon' => $pegawai->eselon ?? '-',
                    'status_asn' => $pegawai->status_asn ?? '-',
                    'golongan' => $pegawai->golongan ?? '-',
                    'masa_kerja' => $pegawai->masa_kerja ?? '-',
                    'alamat' => $pegawai->alamat_rumah ?? '-',
                    'status_perkawinan' => $pegawai->status_perkawinan ?? '-',
                    'jumlah_pasangan' => $pegawai->jumlah_istri_suami ?? 0,
                    'jumlah_anak' => $pegawai->jumlah_anak ?? 0,
                    'jumlah_tanggungan' => $pegawai->jumlah_tanggungan ?? 0,
                    'pasangan_pns' => $pegawai->pasangan_pns ? 'YA' : 'TIDAK',
                    'nip_pasangan' => $pegawai->nip_pasangan ?? '-',
                    'kode_bank' => $pegawai->kode_bank ?? '-',
                    'nama_bank' => $pegawai->nama_bank ?? '-',
                        'rekening' => $pegawai->nomor_rekening_pegawai ?? '-',
                ],
                'links' => [
                    'edit' => $canManageRow ? route('pegawais.edit', $pegawai) : null,
                    'destroy' => $canManageRow ? route('pegawais.destroy', $pegawai) : null,
                ],
            ];
        })->values()->all();

        return response()->json([
            'data' => $items,
            'filters' => [
                'search' => $searchTerm ?? '',
                'per_page' => $perPage,
            ],
            'options' => [
                'per_page' => $perPageOptions,
            ],
            'permissions' => [
                'can_manage' => $currentUser->isSuperAdmin() || $currentUser->isAdminUnit(),
            ],
            'meta' => [
                'pagination' => [
                    'current_page' => $pegawais->currentPage(),
                    'per_page' => $pegawais->perPage(),
                    'total' => $pegawais->total(),
                    'from' => $pegawais->firstItem(),
                    'to' => $pegawais->lastItem(),
                    'links' => $pegawais->toArray()['links'] ?? [],
                ],
            ],
        ]);
    }

}
