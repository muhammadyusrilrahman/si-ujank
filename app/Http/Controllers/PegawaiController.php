<?php

namespace App\Http\Controllers;

use App\Exports\PegawaiExport;
use App\Exports\PegawaiTemplateExport;
use App\Imports\PegawaiImport;
use App\Models\Pegawai;
use App\Models\Skpd;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Services\XlsxService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PegawaiController extends Controller
{
    private XlsxService $xlsxService;

    public function __construct(XlsxService $xlsxService)
    {
        $this->xlsxService = $xlsxService;
    }

    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $search = $request->query('q');
        $perPageOptions = [25, 50, 100];
        $perPage = $request->integer('per_page');
        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $pegawais = Pegawai::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('nama_lengkap', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('jabatan', 'like', "%{$search}%");
                });
            })
            ->when(! $currentUser->isSuperAdmin(), function ($query) use ($currentUser) {
                $query->where('skpd_id', $currentUser->skpd_id);
            })
            ->orderBy('nama_lengkap')
            ->paginate($perPage)
            ->withQueryString();

        return view('pegawais.index', [
            'pegawais' => $pegawais,
            'search' => $search,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'statusPerkawinanOptions' => $this->statusPerkawinanOptions(),
            'statusAsnOptions' => $this->statusAsnOptions(),
            'tipeJabatanOptions' => $this->tipeJabatanOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $skpds = $currentUser->isSuperAdmin()
            ? Skpd::orderBy('name')->get()
            : Skpd::where('id', $currentUser->skpd_id)->get();

        return view('pegawais.create', [
            'skpds' => $skpds,
            'statusPerkawinanOptions' => $this->statusPerkawinanOptions(),
            'statusAsnOptions' => $this->statusAsnOptions(),
            'tipeJabatanOptions' => $this->tipeJabatanOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $validated = $this->validateData($request, null, $currentUser);
        Pegawai::create($validated);

        return redirect()->route('pegawais.index')->with('status', 'Data pegawai berhasil ditambahkan.');
    }

    public function edit(Request $request, Pegawai $pegawai): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        if (! $currentUser->isSuperAdmin() && $pegawai->skpd_id !== $currentUser->skpd_id) {
            abort(403, 'Anda tidak dapat mengelola pegawai dari SKPD lain.');
        }

        $skpds = $currentUser->isSuperAdmin()
            ? Skpd::orderBy('name')->get()
            : Skpd::where('id', $currentUser->skpd_id)->get();

        return view('pegawais.edit', [
            'pegawai' => $pegawai,
            'skpds' => $skpds,
            'statusPerkawinanOptions' => $this->statusPerkawinanOptions(),
            'statusAsnOptions' => $this->statusAsnOptions(),
            'tipeJabatanOptions' => $this->tipeJabatanOptions(),
        ]);
    }

    public function update(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        if (! $currentUser->isSuperAdmin() && $pegawai->skpd_id !== $currentUser->skpd_id) {
            abort(403, 'Anda tidak dapat mengelola pegawai dari SKPD lain.');
        }

        $validated = $this->validateData($request, $pegawai, $currentUser);
        $pegawai->update($validated);

        return redirect()->route('pegawais.index')->with('status', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, Pegawai $pegawai): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        if (! $currentUser->isSuperAdmin() && $pegawai->skpd_id !== $currentUser->skpd_id) {
            abort(403, 'Anda tidak dapat menghapus pegawai dari SKPD lain.');
        }

        $pegawai->delete();

        return redirect()->route('pegawais.index')->with('status', 'Data pegawai berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:pegawais,id'],
        ]);

        $ids = array_unique($validated['ids']);
        $query = Pegawai::whereIn('id', $ids);

        if (! $currentUser->isSuperAdmin()) {
            $query->where('skpd_id', $currentUser->skpd_id);
        }

        $deleted = $query->delete();
        $notDeleted = count($ids) - $deleted;
        $redirectParams = $request->except(['ids', 'page', '_token', '_method']);

        if ($deleted === 0) {
            return redirect()->route('pegawais.index', $redirectParams)->with('status', 'Tidak ada data pegawai yang dihapus.');
        }

        $message = "Berhasil menghapus {$deleted} data pegawai terpilih.";

        if ($notDeleted > 0) {
            $message .= " {$notDeleted} data tidak dapat dihapus.";
        }

        return redirect()->route('pegawais.index', $redirectParams)->with('status', $message);
    }    public function export(Request $request): StreamedResponse
    {
        $export = new PegawaiExport(
            $request->user(),
            $this->exportHeadings(),
            $this->tipeJabatanOptions(),
            $this->statusAsnOptions(),
            $this->statusPerkawinanOptions()
        );

        $rows = array_merge([
            $export->headings(),
        ], $export->rows());

        return $this->xlsxService->download($rows, 'pegawai.xlsx');
    }

    public function template(): StreamedResponse
    {
        $template = new PegawaiTemplateExport(
            $this->exportHeadings(),
            [$this->templateSampleRow()]
        );

        $rows = array_merge([
            $template->headings(),
        ], $template->rows());

        return $this->xlsxService->download($rows, 'template-pegawai.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        try {
            $rows = $this->xlsxService->import($request->file('file'));
            $importer = new PegawaiImport(
                $request->user(),
                $this->tipeJabatanOptions(),
                $this->statusAsnOptions(),
                $this->statusPerkawinanOptions()
            );
            $importer->import($rows);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $e->getMessage(),
            ]);
        }

        return redirect()->route('pegawais.index')
            ->with('status', 'Data pegawai berhasil diimpor.');
    }

    private function exportHeadings(): array
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
            'Jumlah Istri_Suami',
            'Jumlah Anak',
            'Jumlah Tanggungan',
            'Pasangan PNS',
            'NIP Pasangan',
            'Kode Bank',
            'Nama Bank',
            'Nomor Rekening Bank Pegawai',
        ];
    }

    private function templateSampleRow(): array
    {
        return [
            '', // NIP Pegawai
            '', // Nama Pegawai
            '', // NIK Pegawai
            '', // NPWP Pegawai
            '01-01-1980', // Tanggal Lahir Pegawai (HH-BB-TTTT)
            '', // Tipe Jabatan
            '', // Nama Jabatan
            '', // Eselon
            '', // Status ASN
            '', // Golongan
            '', // Masa Kerja Golongan
            '', // Alamat
            '1', // Status Pernikahan (1 = Sudah Menikah, 2 = Belum Menikah atau Cerai Hidup/Mati)
            0,  // Jumlah Istri_Suami
            0,  // Jumlah Anak
            0,  // Jumlah Tanggungan
            'TIDAK', // Pasangan PNS
            '', // NIP Pasangan
            '', // Kode Bank
            '', // Nama Bank
            '', // Nomor Rekening Bank Pegawai
        ];
    }

    // ... rest of controller (create, store, etc.) remain unchanged ...

    protected function validateData(Request $request, ?Pegawai $pegawai, $currentUser): array
    {
        $pegawaiId = $pegawai?->id;
        $tipeOptions = array_keys($this->tipeJabatanOptions());
        $statusAsnOptions = array_keys($this->statusAsnOptions());
        $statusPerkawinanOptions = array_keys($this->statusPerkawinanOptions());

        $validated = $request->validate([
            'skpd_id' => ['nullable', 'exists:skpds,id'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:32', Rule::unique('pegawais', 'nik')->ignore($pegawaiId)],
            'nip' => ['nullable', 'string', 'max:32', Rule::unique('pegawais', 'nip')->ignore($pegawaiId)],
            'npwp' => ['nullable', 'string', 'max:64'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'string', 'max:20'],
            'status_perkawinan' => ['required', Rule::in($statusPerkawinanOptions)],
            'jumlah_istri_suami' => ['nullable', 'integer', 'min:0'],
            'jumlah_anak' => ['nullable', 'integer', 'min:0'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'eselon' => ['nullable', 'string', 'max:100'],
            'golongan' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'alamat_rumah' => ['nullable', 'string'],
            'masa_kerja' => ['nullable', 'string', 'max:100'],
            'jumlah_tanggungan' => ['nullable', 'integer', 'min:0'],
            'pasangan_pns' => ['nullable', 'boolean'],
            'nip_pasangan' => ['nullable', 'string', 'max:32'],
            'kode_bank' => ['nullable', 'string', 'max:20'],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'nomor_rekening_pegawai' => ['nullable', 'string', 'max:50'],
            'tipe_jabatan' => ['required', Rule::in($tipeOptions)],
            'status_asn' => ['required', Rule::in($statusAsnOptions)],
        ], [
            'skpd_id.exists' => 'SKPD tidak valid.',
        ]);

        if ($currentUser->isSuperAdmin()) {
            if (empty($validated['skpd_id'])) {
                throw ValidationException::withMessages([
                    'skpd_id' => 'SKPD wajib dipilih.',
                ]);
            }
        } else {
            $validated['skpd_id'] = $currentUser->skpd_id;
        }

        $validated['jumlah_istri_suami'] = (int) ($validated['jumlah_istri_suami'] ?? 0);
        $validated['jumlah_anak'] = (int) ($validated['jumlah_anak'] ?? 0);
        $validated['jumlah_tanggungan'] = (int) ($validated['jumlah_tanggungan'] ?? 0);
        $validated['pasangan_pns'] = $request->boolean('pasangan_pns');
        $validated['tipe_jabatan'] = (string) $validated['tipe_jabatan'];
        $validated['status_asn'] = (string) $validated['status_asn'];
        $validated['status_perkawinan'] = (string) $validated['status_perkawinan'];

        return $validated;
    }

    protected function tipeJabatanOptions(): array
    {
        return [
            '1' => 'Jabatan Struktural',
            '2' => 'Jabatan Fungsional',
            '3' => 'Jabatan Fungsional Umum',
        ];
    }

    protected function statusAsnOptions(): array
    {
        return [
            '1' => 'PNS',
            '2' => 'PPPK',
            '3' => 'CPNS',
        ];
    }

    protected function statusPerkawinanOptions(): array
    {
        return [
            '1' => 'Sudah menikah',
            '2' => 'Belum menikah / Cerai hidup atau mati',
        ];
    }
}






