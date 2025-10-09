<?php

namespace App\Http\Controllers;

use App\Exports\TppCalculationTemplateExport;
use App\Models\Pegawai;
use App\Models\TppCalculation;
use App\Models\User;
use App\Services\TppCalculationService;
use App\Services\XlsxService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TppCalculationController extends Controller
{
    private TppCalculationService $calculationService;
    private XlsxService $xlsxService;

    public function __construct(TppCalculationService $calculationService, XlsxService $xlsxService)
    {
        $this->calculationService = $calculationService;
        $this->xlsxService = $xlsxService;
    }

    public function index(Request $request): View
    {
        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $perPageOptions = [25, 50, 100];
        $selectedType = $this->resolveType($request->query('type'));

        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['nullable', 'integer', Rule::in(array_keys($monthOptions))],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('type', $validated) && $validated['type'] !== null) {
            $selectedType = $this->resolveType($validated['type']);
        }

        $selectedYear = array_key_exists('tahun', $validated) ? (int) $validated['tahun'] : null;
        $selectedMonth = array_key_exists('bulan', $validated) ? (int) $validated['bulan'] : null;
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : $perPageOptions[0];
        $searchTerm = array_key_exists('search', $validated) ? trim((string) $validated['search']) : null;
        if ($searchTerm === '') {
            $searchTerm = null;
        }

        $filtersReady = $selectedYear !== null && $selectedMonth !== null;

        $currentUser = $request->user();

        $calculations = collect();
        $summary = null;

        if ($filtersReady) {
            $query = TppCalculation::query()
                ->with(['pegawai' => function ($relation) {
                    $relation->select('id', 'nama_lengkap', 'nip', 'jabatan', 'golongan', 'tipe_jabatan', 'skpd_id');
                }])
                ->where('jenis_asn', $selectedType)
                ->where('tahun', $selectedYear)
                ->where('bulan', $selectedMonth);

            $query = $this->restrictToUserScope($query, $currentUser);

            if ($searchTerm !== null) {
                $query->whereHas('pegawai', function ($pegawaiQuery) use ($searchTerm) {
                    $search = '%' . $searchTerm . '%';
                    $pegawaiQuery->where(function ($inner) use ($search) {
                        $inner->where('nama_lengkap', 'like', $search)
                            ->orWhere('nip', 'like', $search);
                    });
                });
            }

            $calculations = $query->orderBy('pegawai_id')
                ->paginate($perPage)
                ->withQueryString();

            $summary = $this->calculationService->summarize($calculations->getCollection());

            $calculations->getCollection()->transform(function (TppCalculation $calculation) {
                return [
                    'model' => $calculation,
                    'data' => $this->calculationService->formatForView($calculation),
                ];
            });
        }

        return view('tpps.perhitungan.index', [
            'typeLabels' => $typeLabels,
            'monthOptions' => $monthOptions,
            'perPageOptions' => $perPageOptions,
            'selectedType' => $selectedType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'perPage' => $perPage,
            'filtersReady' => $filtersReady,
            'searchTerm' => $searchTerm,
            'calculations' => $calculations,
            'summary' => $summary,
            'extraFieldMap' => $this->calculationService->extraFieldMap(),
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $selectedType = $this->resolveType($request->query('type'));

        $request->validate([
            'type' => ['nullable', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['nullable', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        if ($request->has('type')) {
            $selectedType = $this->resolveType($request->query('type'));
        }

        $defaultYear = $request->integer('tahun') ?? (int) date('Y');
        $defaultMonth = $request->has('bulan') ? (int) $request->integer('bulan') : null;

        $pegawaiOptions = $this->pegawaiOptionsForType($selectedType, $currentUser);

        $filterParams = array_filter([
            'type' => $selectedType,
            'tahun' => $defaultYear,
            'bulan' => $defaultMonth,
        ], fn ($value) => $value !== null && $value !== '');

        return view('tpps.perhitungan.form', [
            'calculation' => null,
            'pegawai' => null,
            'pegawaiOptions' => $pegawaiOptions,
            'typeLabels' => $typeLabels,
            'monthOptions' => $monthOptions,
            'selectedType' => $selectedType,
            'defaultYear' => $defaultYear,
            'defaultMonth' => $defaultMonth,
            'payload' => $this->calculationService->formPayload(),
            'isUpdate' => false,
            'filterParams' => $filterParams,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $rules = $this->validationRules($typeLabels, $monthOptions, true);
        $validated = Validator::make($request->all(), $rules)->validate();

        $selectedType = $this->resolveType($validated['jenis_asn']);
        $pegawai = $this->resolvePegawai((int) $validated['pegawai_id'], $currentUser);

        if (! $this->pegawaiMatchesType($pegawai, $selectedType)) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai yang dipilih tidak sesuai dengan jenis ASN.',
            ]);
        }

        $existing = TppCalculation::where('pegawai_id', $pegawai->id)
            ->where('jenis_asn', $selectedType)
            ->where('tahun', (int) $validated['tahun'])
            ->where('bulan', (int) $validated['bulan'])
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Perhitungan untuk pegawai, tahun, dan bulan tersebut sudah ada. Gunakan menu ubah untuk memperbarui data.',
            ]);
        }

        $data = $this->prepareCalculationData(
            $currentUser,
            $validated,
            $pegawai,
            $selectedType
        );

        TppCalculation::create($data);

        return redirect()
            ->route('tpps.perhitungan', [
                'type' => $selectedType,
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
            ])
            ->with('success', 'Perhitungan TPP berhasil disimpan.');
    }

    public function edit(Request $request, TppCalculation $calculation): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);
        $this->ensureCanManageCalculation($calculation, $currentUser);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $selectedType = $calculation->jenis_asn;

        $pegawaiOptions = $this->pegawaiOptionsForType($selectedType, $currentUser, $calculation->pegawai);

        $filterParams = [
            'type' => $selectedType,
            'tahun' => $calculation->tahun,
            'bulan' => $calculation->bulan,
        ];

        return view('tpps.perhitungan.form', [
            'calculation' => $calculation,
            'pegawai' => $calculation->pegawai,
            'pegawaiOptions' => $pegawaiOptions,
            'typeLabels' => $typeLabels,
            'monthOptions' => $monthOptions,
            'selectedType' => $selectedType,
            'defaultYear' => $calculation->tahun,
            'defaultMonth' => $calculation->bulan,
            'payload' => $this->calculationService->formPayload($calculation, $calculation->pegawai),
            'isUpdate' => true,
            'filterParams' => $filterParams,
        ]);
    }

    public function update(Request $request, TppCalculation $calculation): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);
        $this->ensureCanManageCalculation($calculation, $currentUser);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $rules = $this->validationRules($typeLabels, $monthOptions, false);
        $validated = Validator::make($request->all(), $rules)->validate();

        $selectedType = $this->resolveType($validated['jenis_asn']);
        $pegawaiId = (int) ($validated['pegawai_id'] ?? $calculation->pegawai_id);
        $pegawai = $this->resolvePegawai($pegawaiId, $currentUser);

        if (! $this->pegawaiMatchesType($pegawai, $selectedType)) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai yang dipilih tidak sesuai dengan jenis ASN.',
            ]);
        }

        $existing = TppCalculation::where('pegawai_id', $pegawai->id)
            ->where('jenis_asn', $selectedType)
            ->where('tahun', (int) $validated['tahun'])
            ->where('bulan', (int) $validated['bulan'])
            ->where('id', '!=', $calculation->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Perhitungan untuk pegawai, tahun, dan bulan tersebut sudah ada.',
            ]);
        }

        $data = $this->prepareCalculationData(
            $currentUser,
            $validated,
            $pegawai,
            $selectedType
        );

        $calculation->update($data);

        return redirect()
            ->route('tpps.perhitungan', [
                'type' => $selectedType,
                'tahun' => $validated['tahun'],
                'bulan' => $validated['bulan'],
            ])
            ->with('success', 'Perhitungan TPP berhasil diperbarui.');
    }

    public function destroy(Request $request, TppCalculation $calculation): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);
        $this->ensureCanManageCalculation($calculation, $currentUser);

        $params = [
            'type' => $calculation->jenis_asn,
            'tahun' => $calculation->tahun,
            'bulan' => $calculation->bulan,
        ];

        $calculation->delete();

        return redirect()
            ->route('tpps.perhitungan', $params)
            ->with('success', 'Perhitungan TPP berhasil dihapus.');
    }

    public function copy(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
            'source_tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'source_bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $targetType = $this->resolveType($validated['type']);
        $sourceYear = (int) $validated['source_tahun'];
        $sourceMonth = (int) $validated['source_bulan'];
        $targetYear = (int) $validated['tahun'];
        $targetMonth = (int) $validated['bulan'];
        $overwrite = (bool) ($validated['overwrite'] ?? false);

        if ($sourceYear === $targetYear && $sourceMonth === $targetMonth) {
            return redirect()
                ->route('tpps.perhitungan', [
                    'type' => $targetType,
                    'tahun' => $targetYear,
                    'bulan' => $targetMonth,
                ])
                ->with('error', 'Periode sumber dan tujuan tidak boleh sama.');
        }

        $sourceQuery = TppCalculation::query()
            ->with('pegawai:id,nama_lengkap,skpd_id')
            ->where('jenis_asn', $targetType)
            ->where('tahun', $sourceYear)
            ->where('bulan', $sourceMonth);

        $sourceQuery = $this->restrictToUserScope($sourceQuery, $currentUser);

        $sourceCalculations = $sourceQuery->get();

        if ($sourceCalculations->isEmpty()) {
            return redirect()
                ->route('tpps.perhitungan', [
                    'type' => $targetType,
                    'tahun' => $targetYear,
                    'bulan' => $targetMonth,
                ])
                ->with('error', 'Tidak ada data perhitungan pada periode sumber.');
        }

        $created = 0;
        $updated = 0;

        foreach ($sourceCalculations as $source) {
            $this->ensureCanManageCalculation($source, $currentUser);

            $data = $source->toArray();

            $columns = array_merge(
                [
                    'kelas_jabatan', 'golongan', 'beban_kerja', 'kondisi_kerja',
                    'presensi_ketidakhadiran', 'presensi_persen_ketidakhadiran', 'presensi_persen_kehadiran', 'presensi_nilai',
                    'kinerja_persen', 'kinerja_nilai', 'jumlah_tpp', 'bruto',
                    'pfk_pph21', 'pfk_bpjs4', 'pfk_bpjs1', 'netto', 'tanda_terima',
                ],
                array_values($this->calculationService->extraFieldMap())
            );

            $attributes = array_intersect_key($data, array_flip($columns));

            $attributes['pegawai_id'] = $source->pegawai_id;
            $attributes['jenis_asn'] = $targetType;
            $attributes['tahun'] = $targetYear;
            $attributes['bulan'] = $targetMonth;
            $attributes['user_id'] = $currentUser->id ?? null;
            $attributes['skpd_id'] = $source->skpd_id;

            $existing = TppCalculation::where('pegawai_id', $source->pegawai_id)
                ->where('jenis_asn', $targetType)
                ->where('tahun', $targetYear)
                ->where('bulan', $targetMonth)
                ->first();

            if ($existing) {
                if ($overwrite) {
                    $existing->update($attributes);
                    $updated++;
                }

                continue;
            }

            TppCalculation::create($attributes);
            $created++;
        }

        $message = 'Penyalinan perhitungan selesai.';
        if ($created > 0) {
            $message .= " Ditambahkan {$created} data.";
        }
        if ($updated > 0) {
            $message .= " Diperbarui {$updated} data.";
        }

        return redirect()
            ->route('tpps.perhitungan', [
                'type' => $targetType,
                'tahun' => $targetYear,
                'bulan' => $targetMonth,
            ])
            ->with('success', trim($message));
    }

    public function import(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
            'file' => ['required', 'file', 'mimes:xlsx,xls'],
            'overwrite' => ['nullable', 'boolean'],
        ]);

        $type = $this->resolveType($validated['type']);
        $year = (int) $validated['tahun'];
        $month = (int) $validated['bulan'];
        $overwrite = (bool) ($validated['overwrite'] ?? false);

        $rows = $this->xlsxService->import($request->file('file'));

        if ($rows->isEmpty()) {
            return redirect()
                ->route('tpps.perhitungan', [
                    'type' => $type,
                    'tahun' => $year,
                    'bulan' => $month,
                ])
                ->with('error', 'Berkas tidak memiliki data.');
        }

        $errors = [];
        $imported = 0;
        $updated = 0;
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            try {
                $mapped = $this->mapImportRow($row, $currentUser, $type, $year, $month);
                $pegawai = $mapped['pegawai'];
                $data = $this->prepareCalculationData(
                    $currentUser,
                    $mapped['validated'],
                    $pegawai,
                    $mapped['validated']['jenis_asn']
                );

                $existing = TppCalculation::where('pegawai_id', $pegawai->id)
                    ->where('jenis_asn', $mapped['validated']['jenis_asn'])
                    ->where('tahun', $mapped['validated']['tahun'])
                    ->where('bulan', $mapped['validated']['bulan'])
                    ->first();

                if ($existing) {
                    if ($overwrite) {
                        $existing->update($data);
                        $updated++;
                    }
                    continue;
                }

                TppCalculation::create($data);
                $imported++;
            } catch (\Throwable $exception) {
                $errors[] = "Baris {$rowNumber}: {$exception->getMessage()}";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages([
                'file' => $errors,
            ]);
        }

        $message = "Import berhasil. Ditambahkan {$imported} data.";
        if ($overwrite) {
            $message .= " Diperbarui {$updated} data.";
        }

        return redirect()
            ->route('tpps.perhitungan', [
                'type' => $type,
                'tahun' => $year,
                'bulan' => $month,
            ])
            ->with('success', $message);
    }

    public function export(Request $request)
    {
        $currentUser = $request->user();

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        $type = $this->resolveType($validated['type']);
        $year = (int) $validated['tahun'];
        $month = (int) $validated['bulan'];

        $query = TppCalculation::query()
            ->with(['pegawai' => function ($relation) {
                $relation->select('id', 'nama_lengkap', 'nip', 'jabatan', 'golongan', 'tipe_jabatan', 'skpd_id');
            }])
            ->where('jenis_asn', $type)
            ->where('tahun', $year)
            ->where('bulan', $month);

        $query = $this->restrictToUserScope($query, $currentUser);

        $calculations = $query->orderBy('pegawai_id')->get();

        if ($calculations->isEmpty()) {
            return redirect()
                ->route('tpps.perhitungan', [
                    'type' => $type,
                    'tahun' => $year,
                    'bulan' => $month,
                ])
                ->with('error', 'Tidak ada data perhitungan untuk diekspor.');
        }

        $extraLabels = [
            'plt20' => 'TPP PLT 20%',
            'ppkd20' => 'TPP PPKD 20%',
            'bud20' => 'TPP BUD 20%',
            'kbud20' => 'TPP KBUD 20%',
            'tim_tapd20' => 'TPP Tim TAPD 20%',
            'tim_tpp20' => 'TPP Tim TPP 20%',
            'bendahara_penerimaan10' => 'TPP Bendahara Penerimaan 10%',
            'bendahara_pengeluaran30' => 'TPP Bendahara Pengeluaran 30%',
            'pengurus_barang20' => 'TPP Pengurus Barang 20%',
            'pejabat_pengadaan10' => 'TPP Pejabat Pengadaan 10%',
            'tim_tapd20_from_beban' => 'TPP Tim TAPD (20% dari Beban Kerja)',
            'ppk5' => 'TPP PPK 5%',
            'pptk5' => 'TPP PPTK 5%',
        ];

        $extraKeys = array_keys($extraLabels);

        $headings = array_merge(
            [
                'No',
                'Nama Pegawai',
                'NIP',
                'Jabatan',
                'Kelas Jabatan',
                'Golongan',
                'Beban Kerja',
            ],
            array_values($extraLabels),
            [
                'Kondisi Kerja',
                'Jumlah TPP',
                'Presensi - Ketidakhadiran',
                'Presensi - % Ketidakhadiran',
                'Presensi - % Kehadiran',
                'Presensi - Nilai (Rp)',
                'Kinerja - Persentase',
                'Kinerja - Nilai (Rp)',
                'Bruto',
                'PFK - PPh 21',
                'PFK - BPJS 4%',
                'PFK - BPJS 1%',
                'Netto',
                'Tanda Terima',
            ]
        );

        $rows = [$headings];

        foreach ($calculations as $index => $calculation) {
            $row = $this->calculationService->formatForView($calculation);
            $extras = $row['extras'] ?? [];
            $pegawai = $row['pegawai'] ?? [];

            $rows[] = array_merge(
                [
                    $index + 1,
                    $pegawai['nama'] ?? '-',
                    $pegawai['nip'] ?? '-',
                    $pegawai['jabatan'] ?? '-',
                    $row['kelas_jabatan'] ?? '-',
                    $row['golongan'] ?? '-',
                    $row['beban_kerja'] ?? 0,
                ],
                array_map(fn ($key) => $extras[$key] ?? 0, $extraKeys),
                [
                    $row['kondisi_kerja'] ?? 0,
                    $row['jumlah_tpp'] ?? 0,
                    $row['presensi']['ketidakhadiran'] ?? 0,
                    $row['presensi']['persentase_ketidakhadiran'] ?? 0,
                    $row['presensi']['persentase_kehadiran'] ?? 0,
                    $row['presensi']['nilai'] ?? 0,
                    $row['kinerja']['persentase'] ?? 0,
                    $row['kinerja']['nilai'] ?? 0,
                    $row['bruto'] ?? 0,
                    $row['pfk']['pph21'] ?? 0,
                    $row['pfk']['bpjs4'] ?? 0,
                    $row['pfk']['bpjs1'] ?? 0,
                    $row['netto'] ?? 0,
                    $row['tanda_terima'] ?? '',
                ]
            );
        }

        $filename = sprintf(
            'perhitungan-tpp-%s-%d-%s.xlsx',
            $type,
            $year,
            str_pad((string) $month, 2, '0', STR_PAD_LEFT)
        );

        return $this->xlsxService->download($rows, $filename);
    }

    public function template()
    {
        $export = new TppCalculationTemplateExport();
        if (method_exists($export, 'sheets')) {
            return $this->xlsxService->downloadMultiSheet($export->sheets(), 'template-perhitungan-tpp.xlsx');
        }

        return $this->xlsxService->download($export->rows(), 'template-perhitungan-tpp.xlsx');
    }

    private function validationRules(array $typeLabels, array $monthOptions, bool $requirePegawai): array
    {
        $rules = [
            'jenis_asn' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
            'kelas_jabatan' => ['nullable', 'string', 'max:100'],
            'golongan' => ['nullable', 'string', 'max:100'],
            'beban_kerja' => ['required', 'numeric', 'min:0'],
            'kondisi_kerja' => ['required', 'numeric', 'min:0'],
            'presensi_ketidakhadiran' => ['nullable', 'numeric', 'min:0'],
            'presensi_persen_ketidakhadiran' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'presensi_persen_kehadiran' => ['nullable', 'numeric', 'min:0', 'max:40'],
            'presensi_nilai' => ['nullable', 'numeric', 'min:0'],
            'kinerja_persen' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'kinerja_nilai' => ['nullable', 'numeric', 'min:0'],
            'pfk_pph21' => ['nullable', 'numeric', 'min:0'],
            'tanda_terima' => ['nullable', 'string', 'max:255'],
        ];

        foreach (array_keys($this->calculationService->extraFieldMap()) as $key) {
            $rules[$key] = ['nullable', 'numeric', 'min:0'];
        }

        if ($requirePegawai) {
            $rules['pegawai_id'] = ['required', 'integer', 'exists:pegawais,id'];
        } else {
            $rules['pegawai_id'] = ['nullable', 'integer', 'exists:pegawais,id'];
        }

        return $rules;
    }

    private function prepareCalculationData(
        Authenticatable $user,
        array $validated,
        Pegawai $pegawai,
        string $jenisAsn
    ): array {
        $normalized = $this->calculationService->normalizeInput($validated);

        if (($normalized['tanda_terima'] ?? '') === '') {
            $normalized['tanda_terima'] = (string) ($pegawai->nomor_rekening_pegawai ?? '');
        }

        return array_merge($normalized, [
            'pegawai_id' => $pegawai->id,
            'user_id' => $user->id ?? null,
            'skpd_id' => $pegawai->skpd_id,
            'jenis_asn' => $jenisAsn,
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
            'kelas_jabatan' => $validated['kelas_jabatan'] ?? null,
            'golongan' => $validated['golongan'] ?? null,
        ]);
    }

    private function resolvePegawai(int $pegawaiId, Authenticatable $user): Pegawai
    {
        $pegawai = Pegawai::findOrFail($pegawaiId);

        if (! $user->isSuperAdmin() && (int) $pegawai->skpd_id !== (int) $user->skpd_id) {
            abort(403, 'Anda tidak dapat mengelola pegawai dari SKPD lain.');
        }

        return $pegawai;
    }

    private function ensureCanManageCalculation(TppCalculation $calculation, Authenticatable $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $calculation->skpd_id !== (int) $user->skpd_id) {
            abort(403, 'Anda tidak dapat mengelola perhitungan TPP dari SKPD lain.');
        }
    }

    private function restrictToUserScope(Builder $query, Authenticatable $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->where('skpd_id', $user->skpd_id);
    }

    private function typeLabels(): array
    {
        $types = config('tpp.types', []);
        $labels = [];

        foreach ($types as $key => $meta) {
            $labels[$key] = $meta['label'] ?? strtoupper($key);
        }

        return $labels;
    }

    private function monthOptions(): array
    {
        return config('tpp.months', []);
    }

    private function resolveType(?string $type): string
    {
        $normalized = strtolower((string) $type);
        if ($normalized === 'cpns') {
            $normalized = 'pns';
        }

        $available = array_keys($this->typeLabels());
        if (in_array($normalized, $available, true)) {
            return $normalized;
        }

        return $available[0] ?? 'pns';
    }

    private function pegawaiOptionsForType(string $type, User $user, ?Pegawai $current = null): Collection
    {
        $statuses = $this->allowedStatusValues($type);

        $query = Pegawai::query()
            ->select('id', 'nama_lengkap', 'nip', 'status_asn', 'skpd_id')
            ->whereIn('status_asn', $statuses)
            ->orderBy('nama_lengkap');

        if (! $user->isSuperAdmin()) {
            $query->where('skpd_id', $user->skpd_id);
        }

        $options = $query->get();

        if ($current !== null && $options->doesntContain('id', $current->id)) {
            $options->push($current);
        }

        return $options->sortBy('nama_lengkap')->values();
    }

    private function allowedStatusValues(string $type): array
    {
        $map = config('tpp.types', []);
        $values = $map[$type]['status_asn'] ?? [];

        if ($type === 'pns') {
            $values = array_merge($values, ['1', '3', 'pns', 'PNS', 'cpns', 'CPNS']);
        } elseif ($type === 'pppk') {
            $values = array_merge($values, ['2', 'pppk', 'PPPK']);
        }

        return array_values(array_unique(array_map('strval', $values)));
    }

    private function pegawaiMatchesType(Pegawai $pegawai, string $type): bool
    {
        $status = (string) ($pegawai->status_asn ?? '');
        return in_array($status, $this->allowedStatusValues($type), true);
    }

    /**
     * @param  array<string,mixed>  $row
     * @return array{validated:array<string,mixed>,pegawai:Pegawai}
     */
    private function mapImportRow(array $row, Authenticatable $user, string $defaultType, int $defaultYear, int $defaultMonth): array
    {
        $collection = collect($row);
        $jenisAsnValue = strtolower(trim((string) ($collection->get('jenis_asn') ?? '')));
        $jenisAsn = $jenisAsnValue === '' ? $defaultType : $this->resolveType($jenisAsnValue);

        $year = $this->mapYear($collection->get('tahun'), $defaultYear);
        $month = $this->mapMonth($collection->get('bulan'), $defaultMonth);

        $nip = $this->stringValue($collection, ['nip', 'nip_pegawai']);
        $nik = $this->stringValue($collection, ['nik', 'nik_pegawai']);

        if (! $nip && ! $nik) {
            throw new \InvalidArgumentException('Kolom NIP atau NIK wajib diisi.');
        }

        $pegawaiQuery = Pegawai::query();
        if ($nip) {
            $pegawaiQuery->where('nip', $nip);
        } else {
            $pegawaiQuery->where('nik', $nik);
        }

        if (! $user->isSuperAdmin()) {
            $pegawaiQuery->where('skpd_id', $user->skpd_id);
        }

        $pegawai = $pegawaiQuery->first();
        if (! $pegawai) {
            throw new \InvalidArgumentException('Pegawai dengan NIP/NIK tersebut tidak ditemukan atau tidak berada pada SKPD Anda.');
        }

        if (! $this->pegawaiMatchesType($pegawai, $jenisAsn)) {
            throw new \InvalidArgumentException('Status ASN pegawai tidak sesuai dengan jenis ASN yang dipilih.');
        }

        $validated = [
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $jenisAsn,
            'tahun' => $year,
            'bulan' => $month,
            'kelas_jabatan' => $collection->get('kelas_jabatan'),
            'golongan' => $collection->get('golongan'),
            'beban_kerja' => $collection->get('beban_kerja') ?? 0,
            'kondisi_kerja' => $collection->get('kondisi_kerja') ?? 0,
            'presensi_ketidakhadiran' => $collection->get('presensi_ketidakhadiran') ?? 0,
            'presensi_persen_ketidakhadiran' => $collection->get('persentase_ketidakhadiran') ?? $collection->get('presensi_persen_ketidakhadiran'),
            'presensi_persen_kehadiran' => $collection->get('persentase_kehadiran') ?? $collection->get('presensi_persen_kehadiran'),
            'presensi_nilai' => $collection->get('nilai_presensi') ?? $collection->get('presensi_nilai') ?? 0,
            'kinerja_persen' => $collection->get('kinerja_persen') ?? 0,
            'kinerja_nilai' => $collection->get('kinerja_nilai') ?? null,
            'pfk_pph21' => $collection->get('pfk_pph21') ?? $collection->get('pph_pasals_21') ?? 0,
            'tanda_terima' => $collection->get('tanda_terima') ?? '',
        ];

        foreach ($this->calculationService->extraFieldMap() as $key => $column) {
            $validated[$key] = $collection->get($key) ?? $collection->get($column) ?? 0;
        }

        return [
            'validated' => $validated,
            'pegawai' => $pegawai,
        ];
    }

    private function mapYear($value, int $defaultYear): int
    {
        if ($value === null || trim((string) $value) === '') {
            return $defaultYear;
        }

        return (int) $value;
    }

    private function mapMonth($value, int $defaultMonth): int
    {
        if ($value === null || trim((string) $value) === '') {
            return $defaultMonth;
        }

        $monthOptions = $this->monthOptions();

        if (is_numeric($value)) {
            $month = (int) $value;
            if (! array_key_exists($month, $monthOptions)) {
                throw new \InvalidArgumentException('Nilai bulan tidak valid.');
            }

            return $month;
        }

        $upper = strtoupper((string) $value);
        foreach ($monthOptions as $number => $label) {
            if (strtoupper($label) === $upper) {
                return (int) $number;
            }
        }

        throw new \InvalidArgumentException("Bulan '{$value}' tidak dikenali.");
    }

    private function stringValue(Collection $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                $value = trim((string) $row->get($key));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }
}
