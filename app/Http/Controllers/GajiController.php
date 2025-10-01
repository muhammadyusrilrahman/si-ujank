<?php

namespace App\Http\Controllers;

use App\Exports\GajiExport;
use App\Exports\GajiTemplateExport;
use App\Imports\GajiImport;
use App\Models\Gaji;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\XlsxService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GajiController extends Controller
{
    private XlsxService $xlsxService;

    public function __construct(XlsxService $xlsxService)
    {
        $this->xlsxService = $xlsxService;
    }

    public function index(Request $request): View
    {
        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $perPageOptions = $this->perPageOptions();
        $selectedType = $this->resolveType($request->query('type'));

        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['nullable', 'integer', Rule::in(array_keys($monthOptions))],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $selectedYear = array_key_exists('tahun', $validated) ? (int) $validated['tahun'] : null;
        $selectedMonth = array_key_exists('bulan', $validated) ? (int) $validated['bulan'] : null;
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : $perPageOptions[0];
        $filtersReady = $selectedYear !== null && $selectedMonth !== null;
        $searchTerm = array_key_exists('search', $validated) ? trim((string) $validated['search']) : null;
        if ($searchTerm === '') {
            $searchTerm = null;
        }

        $viewData = [
            'typeLabels' => $typeLabels,
            'selectedType' => $selectedType,
            'monthOptions' => $monthOptions,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'filtersReady' => $filtersReady,
            'searchTerm' => $searchTerm,
            'perPage' => $perPage,
            'perPageOptions' => $perPageOptions,
            'allowanceFields' => $this->allowanceFields(),
            'deductionFields' => $this->deductionFields(),
        ];

        if ($filtersReady) {
            $currentUser = $request->user();

            $baseQuery = Gaji::query()
                ->whereIn('jenis_asn', $this->jenisAsnScope($selectedType))
                ->where('tahun', $selectedYear)
                ->where('bulan', $selectedMonth)
                ->when(! $currentUser->isSuperAdmin(), function ($query) use ($currentUser) {
                    $query->whereHas('pegawai', function ($sub) use ($currentUser) {
                        $sub->where('skpd_id', $currentUser->skpd_id);
                    });
                })
                ->when($searchTerm !== null, function ($query) use ($searchTerm) {
                    $query->whereHas('pegawai', function ($pegawaiQuery) use ($searchTerm) {
                        $search = '%' . $searchTerm . '%';
                        $pegawaiQuery->where(function ($inner) use ($search) {
                            $inner->where('nama_lengkap', 'like', $search)
                                ->orWhere('nip', 'like', $search);
                        });
                    });
                });

            $gajis = (clone $baseQuery)
                ->with(['pegawai' => function ($relation) {
                    $relation->select('id', 'nama_lengkap', 'nip', 'status_asn', 'skpd_id');
                }])
                ->orderBy('pegawai_id')
                ->paginate($perPage)
                ->withQueryString();

            $monetaryFieldKeys = array_keys($this->monetaryFields());
            $monetaryTotals = $this->aggregateMonetaryTotals($baseQuery, $monetaryFieldKeys);

            $allowanceKeys = array_keys($viewData['allowanceFields']);
            $deductionKeys = array_keys($viewData['deductionFields']);
            $allowanceTotal = $this->sumTotalsByKey($monetaryTotals, $allowanceKeys);
            $deductionTotal = $this->sumTotalsByKey($monetaryTotals, $deductionKeys);

            $viewData['gajis'] = $gajis;
            $viewData['monetaryTotals'] = $monetaryTotals;
            $viewData['summaryTotals'] = [
                'allowance' => $allowanceTotal,
                'deduction' => $deductionTotal,
                'transfer' => $allowanceTotal - $deductionTotal,
            ];
        } else {
            $viewData['gajis'] = null;
            $viewData['monetaryTotals'] = [];
            $viewData['summaryTotals'] = [
                'allowance' => 0.0,
                'deduction' => 0.0,
                'transfer' => 0.0,
            ];
        }

        return view('gajis.index', $viewData);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $selectedType = $this->resolveType($request->query('type'));
        $monthOptions = $this->monthOptions();

        $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['nullable', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        $defaultYear = $request->integer('tahun') ?? (int) date('Y');
        $defaultMonth = $request->has('bulan') ? (int) $request->integer('bulan') : null;

        return view('gajis.create', [
            'selectedType' => $selectedType,
            'typeLabels' => $this->typeLabels(),
            'monthOptions' => $monthOptions,
            'defaultYear' => $defaultYear,
            'defaultMonth' => $defaultMonth,
            'pegawaiOptions' => $this->pegawaiOptionsForType($selectedType, $currentUser),
            'monetaryFields' => $this->monetaryFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $rules = [
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'pegawai_id' => ['required', 'integer', 'exists:pegawais,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
        ] + $this->monetaryValidationRules();

        $validated = $request->validate($rules);
        $selectedType = $this->resolveType($validated['type']);
        $pegawai = $this->findPegawaiForUser((int) $validated['pegawai_id'], $currentUser);
        $this->ensurePegawaiMatchesType($pegawai, $selectedType);

        $duplicateExists = Gaji::where('pegawai_id', $pegawai->id)
            ->where('tahun', $validated['tahun'])
            ->where('bulan', $validated['bulan'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data gaji untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        $data = array_merge([
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $this->resolveJenisAsnForPegawai($selectedType, $pegawai),
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
        ], $this->extractMonetaryValues($request));

        try {
            Gaji::create($data);
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data gaji untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        return redirect()->route('gajis.index', [
            'type' => $selectedType,
            'tahun' => $data['tahun'],
            'bulan' => $data['bulan'],
        ])->with('status', 'Data gaji berhasil ditambahkan.');
    }

    public function edit(Request $request, Gaji $gaji): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->load('pegawai');
        $this->ensureCanManageGaji($gaji, $currentUser);

        $selectedType = $this->resolveType($request->query('type', $gaji->jenis_asn));
        $monthOptions = $this->monthOptions();

        return view('gajis.edit', [
            'gaji' => $gaji,
            'selectedType' => $selectedType,
            'typeLabels' => $this->typeLabels(),
            'monthOptions' => $monthOptions,
            'defaultYear' => $gaji->tahun,
            'defaultMonth' => $gaji->bulan,
            'pegawaiOptions' => $this->pegawaiOptionsForType($selectedType, $currentUser, $gaji->pegawai),
            'monetaryFields' => $this->monetaryFields(),
        ]);
    }

    public function update(Request $request, Gaji $gaji): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->load('pegawai');
        $this->ensureCanManageGaji($gaji, $currentUser);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $rules = [
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'pegawai_id' => ['required', 'integer', 'exists:pegawais,id'],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
        ] + $this->monetaryValidationRules();

        $validated = $request->validate($rules);
        $selectedType = $this->resolveType($validated['type']);
        $pegawai = $this->findPegawaiForUser((int) $validated['pegawai_id'], $currentUser);
        $this->ensurePegawaiMatchesType($pegawai, $selectedType);

        $duplicate = Gaji::where('pegawai_id', $pegawai->id)
            ->where('tahun', $validated['tahun'])
            ->where('bulan', $validated['bulan'])
            ->where('id', '!=', $gaji->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data gaji untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        $updateData = array_merge([
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $this->resolveJenisAsnForPegawai($selectedType, $pegawai),
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
        ], $this->extractMonetaryValues($request));

        $gaji->update($updateData);

        return redirect()->route('gajis.index', [
            'type' => $selectedType,
            'tahun' => $updateData['tahun'],
            'bulan' => $updateData['bulan'],
        ])->with('status', 'Data gaji berhasil diperbarui.');
    }

    public function destroy(Request $request, Gaji $gaji): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->load('pegawai');
        $this->ensureCanManageGaji($gaji, $currentUser);

        $gaji->delete();

        $redirectParams = array_filter([
            'type' => $this->resolveType($request->input('type', $gaji->jenis_asn)),
            'tahun' => $request->input('tahun', $gaji->tahun),
            'bulan' => $request->input('bulan', $gaji->bulan),
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->route('gajis.index', $redirectParams)->with('status', 'Data gaji berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $deleteAll = $request->boolean('delete_all');

        if ($deleteAll) {
            $validated = $request->validate([
                'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
                'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
                'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
            ]);

            $selectedType = $this->resolveType($validated['type']);
            $selectedYear = (int) $validated['tahun'];
            $selectedMonth = (int) $validated['bulan'];

            $query = Gaji::query()
                ->whereIn('jenis_asn', $this->jenisAsnScope($selectedType))
                ->where('tahun', $selectedYear)
                ->where('bulan', $selectedMonth);

            if (! $currentUser->isSuperAdmin()) {
                $query->whereHas('pegawai', function ($sub) use ($currentUser) {
                    $sub->where('skpd_id', $currentUser->skpd_id);
                });
            }

            $total = (clone $query)->count();

            $redirectParams = array_filter([
                'type' => $selectedType,
                'tahun' => $selectedYear,
                'bulan' => $selectedMonth,
                'per_page' => $request->input('per_page'),
            ], fn ($value) => $value !== null && $value !== '');

            if ($total === 0) {
                return redirect()->route('gajis.index', $redirectParams)->with('status', 'Tidak ada data gaji yang dihapus.');
            }

            $deleted = $query->delete();

            return redirect()->route('gajis.index', $redirectParams)->with('status', "Berhasil menghapus {$deleted} data gaji.");
        }

        $redirectParams = array_filter([
            'type' => $request->filled('type') ? $this->resolveType($request->input('type')) : null,
            'tahun' => $request->input('tahun'),
            'bulan' => $request->input('bulan'),
            'per_page' => $request->input('per_page'),
        ], fn ($value) => $value !== null && $value !== '');

        $rawIds = $request->input('ids');
        if (! is_array($rawIds) || empty($rawIds)) {
            return redirect()->route('gajis.index', $redirectParams)->with('status', 'Pilih data gaji yang ingin dihapus.');
        }

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:gajis,id'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('gajis.index', $redirectParams)
                ->withErrors($validator)
                ->with('status', 'Tidak dapat menghapus data gaji terpilih.');
        }

        $ids = array_values(array_unique(array_map('intval', $validator->validated()['ids'])));

        $query = Gaji::whereIn('id', $ids);

        if (! $currentUser->isSuperAdmin()) {
            $query->whereHas('pegawai', function ($sub) use ($currentUser) {
                $sub->where('skpd_id', $currentUser->skpd_id);
            });
        }

        $deleted = $query->delete();
        $notDeleted = count($ids) - $deleted;

        if ($deleted === 0) {
            return redirect()->route('gajis.index', $redirectParams)->with('status', 'Tidak ada data gaji yang dihapus.');
        }

        $message = "Berhasil menghapus {$deleted} data gaji terpilih.";
        if ($notDeleted > 0) {
            $message .= " {$notDeleted} data tidak dapat dihapus.";
        }

        return redirect()->route('gajis.index', $redirectParams)->with('status', $message);
    }
    public function export(Request $request): StreamedResponse
    {
        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        $selectedType = $this->resolveType($validated['type']);
        $selectedYear = (int) $validated['tahun'];
        $selectedMonth = (int) $validated['bulan'];
        $allowanceFields = $this->allowanceFields();
        $deductionFields = $this->deductionFields();
        $monetaryLabels = $this->monetaryFields();

        $export = new GajiExport(
            $request->user(),
            $selectedType,
            $selectedYear,
            $selectedMonth,
            $this->exportHeadings($allowanceFields, $deductionFields),
            $monetaryLabels,
            $this->jenisAsnScope($selectedType),
            $allowanceFields,
            $deductionFields,
            $this->tipeJabatanOptions(),
            $this->statusAsnOptions(),
            $this->statusPerkawinanOptions()
        );

        $rows = array_merge([
            $export->headings(),
        ], $export->rows());

        $filename = sprintf(
            'gaji-%s-%d-%s.xlsx',
            $selectedType,
            $selectedYear,
            str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT)
        );

        return $this->xlsxService->download($rows, $filename);
    }

    public function template(Request $request): StreamedResponse
    {
        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        $selectedType = $this->resolveType($validated['type']);
        $allowanceFields = $this->allowanceFields();
        $deductionFields = $this->deductionFields();

        $template = new GajiTemplateExport(
            $this->exportHeadings($allowanceFields, $deductionFields),
            [$this->templateSampleRow($allowanceFields, $deductionFields)]
        );

        $rows = array_merge([
            $template->headings(),
        ], $template->rows());

        $filename = sprintf('template-gaji-%s.xlsx', $selectedType);

        return $this->xlsxService->download($rows, $filename);
    }

    public function import(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        $selectedType = $this->resolveType($validated['type']);
        $selectedYear = (int) $validated['tahun'];
        $selectedMonth = (int) $validated['bulan'];

        try {
            $rows = $this->xlsxService->import($request->file('file'));
            $importer = new GajiImport(
                $currentUser,
                $monthOptions,
                $typeLabels,
                $this->asnTypeMap(),
                $this->monetaryFields(),
                $selectedType,
                $selectedYear,
                $selectedMonth
            );
            $importer->import($rows);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $e->getMessage(),
            ]);
        }

        return redirect()->route('gajis.index', [
            'type' => $selectedType,
            'tahun' => $selectedYear,
            'bulan' => $selectedMonth,
        ])->with('status', 'Data gaji berhasil diimpor.');
    }

    private function gajiTypes(): array
    {
        return config('gaji.types', []);
    }

    private function typeLabels(): array
    {
        $labels = [];
        foreach ($this->gajiTypes() as $key => $value) {
            $labels[$key] = $value['label'] ?? strtoupper($key);
        }

        return $labels;
    }

    private function asnTypeMap(): array
    {
        $map = [];
        foreach ($this->gajiTypes() as $key => $config) {
            $rawStatuses = $config['status_asn'] ?? [];
            if (! is_array($rawStatuses)) {
                $rawStatuses = [$rawStatuses];
            }

            $statuses = array_map('strval', $rawStatuses);
            $map[$key] = $statuses;

            if ($key === 'pns') {
                $map['cpns'] = $statuses;
            }
        }

        if (! array_key_exists('cpns', $map)) {
            $map['cpns'] = ['3'];
        }

        return $map;
    }

    private function monthOptions(): array
    {
        return config('gaji.months', []);
    }

    private function perPageOptions(): array
    {
        return [25, 50, 100];
    }

    private function monetaryConfig(): array
    {
        return config('gaji.monetary_fields', []);
    }

    private function monetaryFields(): array
    {
        $fields = [];
        foreach ($this->monetaryConfig() as $field => $meta) {
            $fields[$field] = $meta['label'] ?? ucwords(str_replace('_', ' ', $field));
        }

        return $fields;
    }

    private function allowanceFields(): array
    {
        $fields = [];
        foreach ($this->monetaryConfig() as $field => $meta) {
            if (($meta['category'] ?? '') === 'allowance') {
                $fields[$field] = $meta['label'] ?? ucwords(str_replace('_', ' ', $field));
            }
        }

        return $fields;
    }

    private function deductionFields(): array
    {
        $fields = [];
        foreach ($this->monetaryConfig() as $field => $meta) {
            if (($meta['category'] ?? '') === 'deduction') {
                $fields[$field] = $meta['label'] ?? ucwords(str_replace('_', ' ', $field));
            }
        }

        return $fields;
    }

    private function monetaryValidationRules(): array
    {
        $rules = [];
        foreach (array_keys($this->monetaryFields()) as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    private function extractMonetaryValues(Request $request): array
    {
        $values = [];
        foreach (array_keys($this->monetaryFields()) as $field) {
            $raw = $request->input($field);
            $values[$field] = $raw === null || $raw === '' ? 0.0 : (float) $raw;
        }

        return $values;
    }

    private function pegawaiOptionsForType(string $type, User $user, ?Pegawai $current = null)
    {
        $statuses = $this->allowedStatusValues($type);
        $query = Pegawai::query()
            ->select('id', 'nama_lengkap', 'nip', 'status_asn', 'skpd_id')
            ->when(! $user->isSuperAdmin(), function ($builder) use ($user) {
                $builder->where('skpd_id', $user->skpd_id);
            });

        if (! empty($statuses)) {
            $query->where(function ($sub) use ($statuses, $current) {
                $sub->whereIn('status_asn', $statuses);
                if ($current) {
                    $sub->orWhere('id', $current->id);
                }
            });
        }

        return $query->orderBy('nama_lengkap')->get();
    }

    private function findPegawaiForUser(int $pegawaiId, User $user): Pegawai
    {
        $query = Pegawai::query()->where('id', $pegawaiId);
        if (! $user->isSuperAdmin()) {
            $query->where('skpd_id', $user->skpd_id);
        }

        $pegawai = $query->first();
        if (! $pegawai) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai tidak ditemukan atau tidak dapat diakses.',
            ]);
        }

        return $pegawai;
    }

    private function ensurePegawaiMatchesType(Pegawai $pegawai, string $type): void
    {
        $allowed = $this->allowedStatusValues($type);
        if (! in_array((string) $pegawai->status_asn, $allowed, true)) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Pegawai tidak sesuai dengan jenis ASN yang dipilih.',
            ]);
        }
    }

    private function ensureCanManageGaji(Gaji $gaji, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) optional($gaji->pegawai)->skpd_id !== (int) $user->skpd_id) {
            abort(403, 'Anda tidak dapat mengelola data gaji dari SKPD lain.');
        }
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

    private function jenisAsnScope(string $type): array
    {
        return $type === 'pns' ? ['pns', 'cpns'] : [$type];
    }

    private function resolveJenisAsnForPegawai(string $type, Pegawai $pegawai): string
    {
        $status = strtolower((string) $pegawai->status_asn);
        if ($type === 'pns' && in_array($status, ['3', 'cpns'], true)) {
            return 'cpns';
        }

        if ($type === 'pppk') {
            return 'pppk';
        }

        return 'pns';
    }

    private function allowedStatusValues(string $type): array
    {
        $map = $this->asnTypeMap();
        $values = $map[$type] ?? [];

        if ($type === 'pns') {
            $values = array_merge($values, ['1', '3', 'pns', 'PNS', 'cpns', 'CPNS']);
        } elseif ($type === 'pppk') {
            $values = array_merge($values, ['2', 'pppk', 'PPPK']);
        }

        return array_values(array_unique(array_map('strval', $values)));
    }

    private function exportHeadings(array $allowanceFields, array $deductionFields): array
    {
        return array_merge([
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
        ], array_values($allowanceFields), array_values($deductionFields), [
            'Jumlah Gaji & Tunjangan',
            'Jumlah Potongan',
            'Jumlah Ditransfer',
        ]);
    }

    private function templateSampleRow(array $allowanceFields, array $deductionFields): array
    {
        $row = [
            '',
            '',
            '',
            '',
            '1980-01-01',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '1',
            0,
            0,
            0,
            'TIDAK',
            '',
            '',
            '',
            '',
        ];

        $row = array_merge($row, array_fill(0, count($allowanceFields) + count($deductionFields), 0.0));
        $row[] = 0.0;
        $row[] = 0.0;
        $row[] = 0.0;

        return $row;
    }

    private function tipeJabatanOptions(): array
    {
        return [
            '1' => 'Jabatan Struktural',
            '2' => 'Jabatan Fungsional',
            '3' => 'Jabatan Fungsional Umum',
        ];
    }

    private function statusAsnOptions(): array
    {
        return [
            '1' => 'PNS',
            '2' => 'PPPK',
            '3' => 'CPNS',
        ];
    }

    private function statusPerkawinanOptions(): array
    {
        return [
            '1' => 'Sudah menikah',
            '2' => 'Belum menikah / Cerai hidup atau mati',
        ];
    }

    /**
     * @param Builder  $query
     * @param string[] $fields
     * @return array<string,float>
     */
    private function aggregateMonetaryTotals(Builder $query, array $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        $expressions = collect($fields)->map(function (string $field): string {
            return sprintf('COALESCE(SUM(%s), 0) as %s', $field, $field);
        })->implode(', ');

        $result = (clone $query)->selectRaw($expressions)->first();

        if ($result === null) {
            return array_fill_keys($fields, 0.0);
        }

        $totals = [];
        foreach ($fields as $field) {
            $totals[$field] = (float) ($result->{$field} ?? 0);
        }

        return $totals;
    }

    /**
     * @param array<string,float|int> $totals
     * @param string[]                $fields
     */
    private function sumTotalsByKey(array $totals, array $fields): float
    {
        $sum = 0.0;
        foreach ($fields as $field) {
            $sum += (float) ($totals[$field] ?? 0);
        }

        return $sum;
    }
}






