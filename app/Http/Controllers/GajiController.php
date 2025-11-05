<?php

namespace App\Http\Controllers;

use App\Exports\GajiExport;
use App\Exports\GajiTemplateExport;
use App\Imports\GajiImport;
use App\Http\Controllers\Concerns\HandlesEbupot;
use App\Models\Gaji;
use App\Models\EbupotReport;
use App\Models\Pegawai;
use App\Models\Skpd;
use App\Models\User;
use App\Services\XlsxService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GajiController extends Controller
{
    use HandlesEbupot;
    private XlsxService $xlsxService;

    public function __construct(XlsxService $xlsxService)
    {
        $this->xlsxService = $xlsxService;
    }

    public function index(Request $request): View
    {
        $currentUser = $request->user();
        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();
        $perPageOptions = $this->perPageOptions();
        $selectedType = $this->resolveType($request->query('type'));

        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['nullable', 'integer', Rule::in(array_keys($monthOptions))],
            'per_page' => ['nullable', 'integer', Rule::in($perPageOptions)],
            'search' => ['nullable', 'string', 'max:255'],
            'skpd_id' => ['nullable', 'integer', 'exists:skpds,id'],
        ]);

        $selectedYear = array_key_exists('tahun', $validated) ? (int) $validated['tahun'] : null;
        $selectedMonth = array_key_exists('bulan', $validated) ? (int) $validated['bulan'] : null;
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : $perPageOptions[0];
        $filtersReady = $selectedYear !== null && $selectedMonth !== null;
        $searchTerm = array_key_exists('search', $validated) ? trim((string) $validated['search']) : null;
        if ($searchTerm === '') {
            $searchTerm = null;
        }

        $selectedSkpdId = null;
        if ($currentUser->isSuperAdmin()) {
            $selectedSkpdId = array_key_exists('skpd_id', $validated) && $validated['skpd_id'] !== null
                ? (int) $validated['skpd_id']
                : null;
        } else {
            $selectedSkpdId = (int) $currentUser->skpd_id;
        }

        $totalAllowanceFields = $this->totalAllowanceFields();
        $totalDeductionFields = $this->totalDeductionFields();

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
            'monetaryFields' => $this->monetaryFields(),
            'allowanceFields' => $this->allowanceFields(),
            'deductionFields' => $this->deductionFields(),
            'selectedSkpdId' => $selectedSkpdId,
            'totalAllowanceFields' => $totalAllowanceFields,
            'totalDeductionFields' => $totalDeductionFields,
        ];

        if ($filtersReady) {
            $baseQuery = Gaji::query()
                ->whereIn('jenis_asn', $this->jenisAsnScope($selectedType))
                ->where('tahun', $selectedYear)
                ->where('bulan', $selectedMonth)
                ->when($selectedSkpdId !== null, function ($query) use ($selectedSkpdId) {
                    $query->whereHas('pegawai', function ($sub) use ($selectedSkpdId) {
                        $sub->where('skpd_id', $selectedSkpdId);
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

            $allowanceTotal = $this->sumTotalsByKey($monetaryTotals, $totalAllowanceFields);
            $deductionTotal = $this->sumTotalsByKey($monetaryTotals, $totalDeductionFields);

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

        $viewData['skpds'] = $currentUser->isSuperAdmin() ? Skpd::cachedOptions() : collect();

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
            'totalAllowanceFields' => $this->totalAllowanceFields(),
            'totalDeductionFields' => $this->totalDeductionFields(),
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

        $duplicateExists = Gaji::query()
            ->where('pegawai_id', $pegawai->id)
            ->where('tahun', (int) $validated['tahun'])
            ->where('bulan', (int) $validated['bulan'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data gaji untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        $monetaryValues = array_map(
            fn (float $amount) => round($amount, 2),
            $this->extractMonetaryValues($request)
        );

        $familyAllowance = ($monetaryValues['perhitungan_suami_istri'] ?? 0.0)
            + ($monetaryValues['perhitungan_anak'] ?? 0.0);
        $monetaryValues['tunjangan_keluarga'] = round($familyAllowance, 2);

        $data = array_merge([
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $this->resolveJenisAsnForPegawai($selectedType, $pegawai),
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
        ], $monetaryValues);

        try {
            Gaji::create($data);
        } catch (QueryException $exception) {
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

    public function show(Request $request, Gaji $gaji): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->loadMissing(['pegawai.skpd']);
        $this->ensureCanManageGaji($gaji, $currentUser);

        return view('gajis.show', [
            'gaji' => $gaji,
        ]);
    }

    public function edit(Request $request, Gaji $gaji): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->loadMissing('pegawai');
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
            'totalAllowanceFields' => $this->totalAllowanceFields(),
            'totalDeductionFields' => $this->totalDeductionFields(),
        ]);
    }

    public function update(Request $request, Gaji $gaji): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->loadMissing('pegawai');
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

        $duplicateExists = Gaji::query()
            ->where('pegawai_id', $pegawai->id)
            ->where('tahun', (int) $validated['tahun'])
            ->where('bulan', (int) $validated['bulan'])
            ->where('id', '<>', $gaji->id)
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data gaji untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        $monetaryValues = array_map(
            fn (float $amount) => round($amount, 2),
            $this->extractMonetaryValues($request)
        );

        $familyAllowance = ($monetaryValues['perhitungan_suami_istri'] ?? 0.0)
            + ($monetaryValues['perhitungan_anak'] ?? 0.0);
        $monetaryValues['tunjangan_keluarga'] = round($familyAllowance, 2);

        $data = array_merge([
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $this->resolveJenisAsnForPegawai($selectedType, $pegawai),
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
        ], $monetaryValues);

        try {
            $gaji->update($data);
        } catch (QueryException $exception) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data gaji untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        return redirect()->route('gajis.index', [
            'type' => $selectedType,
            'tahun' => $data['tahun'],
            'bulan' => $data['bulan'],
        ])->with('status', 'Data gaji berhasil diperbarui.');
    }

    public function destroy(Request $request, Gaji $gaji): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $gaji->loadMissing('pegawai');
        $this->ensureCanManageGaji($gaji, $currentUser);

        $redirectParams = array_filter([
            'type' => $request->filled('type') ? $this->resolveType($request->input('type')) : $this->resolveType($gaji->jenis_asn),
            'tahun' => $request->input('tahun', $gaji->tahun),
            'bulan' => $request->input('bulan', $gaji->bulan),
            'per_page' => $request->input('per_page'),
            'search' => $request->input('search'),
        ], fn ($value) => $value !== null && $value !== '');

        $gaji->delete();

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

        $query = Gaji::query()->whereIn('id', $ids);

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
        $monetaryLabels = $this->monetaryFields();
        $totalAllowanceFields = $this->totalAllowanceFields();
        $totalDeductionFields = $this->totalDeductionFields();

        $export = new GajiExport(
            $request->user(),
            $selectedType,
            $selectedYear,
            $selectedMonth,
            $this->exportHeadings($monetaryLabels),
            $monetaryLabels,
            $this->jenisAsnScope($selectedType),
            $this->allowanceFields(),
            $this->deductionFields(),
            $totalAllowanceFields,
            $totalDeductionFields,
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
        $monetaryLabels = $this->monetaryFields();

        $template = new GajiTemplateExport(
            $this->exportHeadings($monetaryLabels),
            [$this->templateSampleRow($monetaryLabels)]
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
            'skpd_id' => ['nullable', 'integer', 'exists:skpds,id'],
        ]);

        $selectedType = $this->resolveType($validated['type']);
        $selectedYear = (int) $validated['tahun'];
        $selectedMonth = (int) $validated['bulan'];
        $selectedSkpdId = $currentUser->isSuperAdmin()
            ? ($validated['skpd_id'] ?? null)
            : $currentUser->skpd_id;

        if ($currentUser->isSuperAdmin() && $selectedSkpdId === null) {
            return back()
                ->withErrors(['skpd_id' => 'Pilih SKPD tujuan impor.'])
                ->withInput($request->except('file'));
        }

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
                $selectedMonth,
                $selectedSkpdId !== null ? (int) $selectedSkpdId : null
            );
            $importer->import($rows);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('gajis.index', [
            'type' => $selectedType,
            'tahun' => $selectedYear,
            'bulan' => $selectedMonth,
            'skpd_id' => $currentUser->isSuperAdmin() ? $selectedSkpdId : null,
        ])->with('status', 'Data gaji berhasil diimpor.');
    }

    public function indexEbupot(Request $request): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['nullable', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['nullable', 'integer', Rule::in(array_keys($monthOptions))],
        ]);

        $query = EbupotReport::query()
            ->where('source', 'gaji')
            ->with(['user:id,name', 'skpd:id,name'])
            ->when(! $currentUser->isSuperAdmin(), function ($builder) use ($currentUser) {
                $builder->where('skpd_id', $currentUser->skpd_id);
            });

        if (array_key_exists('type', $validated) && $validated['type'] !== null) {
            $query->where('jenis_asn', $this->resolveType($validated['type']));
        }

        if (array_key_exists('tahun', $validated) && $validated['tahun'] !== null) {
            $query->where('tahun', (int) $validated['tahun']);
        }

        if (array_key_exists('bulan', $validated) && $validated['bulan'] !== null) {
            $query->where('bulan', (int) $validated['bulan']);
        }

        $reports = $query
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->paginate(15)
            ->withQueryString();

        return view('gajis.ebupot.index', [
            'typeLabels' => $typeLabels,
            'monthOptions' => $monthOptions,
            'filters' => [
                'type' => $validated['type'] ?? null,
                'tahun' => $validated['tahun'] ?? null,
                'bulan' => $validated['bulan'] ?? null,
            ],
            'reports' => $reports,
        ]);
    }

    public function createEbupot(Request $request): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

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

        $currentUser->loadMissing('skpd');
        $defaultNpwpPemotong = optional($currentUser->skpd)->npwp ?? '';
        $defaultIdTku = $defaultNpwpPemotong !== '' ? $defaultNpwpPemotong . '000000' : '';
        $defaultKodeObjek = '21-100-01';
        $normalizedMonth = max(1, min(12, $selectedMonth));
        $defaultCutOffDate = Carbon::create($selectedYear, $normalizedMonth, 1)->endOfMonth()->format('Y-m-d');

        $entries = $this->prepareEbupotEntries(
            $currentUser,
            $selectedType,
            $selectedYear,
            $selectedMonth,
            $defaultNpwpPemotong,
            $defaultIdTku,
            $defaultKodeObjek,
            $defaultCutOffDate
        );

        return view('gajis.ebupot.create', [
            'typeLabels' => $typeLabels,
            'monthOptions' => $monthOptions,
            'defaultNpwpPemotong' => $defaultNpwpPemotong,
            'defaultIdTku' => $defaultIdTku,
            'defaultKodeObjek' => $defaultKodeObjek,
            'selectedType' => $selectedType,
            'selectedYear' => $selectedYear,
            'selectedMonth' => $selectedMonth,
            'prefilledEntries' => $entries,
        ]);
    }

    public function storeEbupot(Request $request): StreamedResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $typeLabels = $this->typeLabels();
        $monthOptions = $this->monthOptions();

        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys($typeLabels))],
            'tahun' => ['required', 'integer', 'min:2000', 'max:' . (date('Y') + 5)],
            'bulan' => ['required', 'integer', Rule::in(array_keys($monthOptions))],
            'default_npwp_pemotong' => ['nullable', 'string', 'max:25'],
            'default_id_tku' => ['nullable', 'string', 'max:50'],
            'default_kode_objek' => ['nullable', 'string', 'max:20'],
        ]);

        $selectedType = $this->resolveType($validated['type']);
        $selectedYear = (int) $validated['tahun'];
        $selectedMonth = (int) $validated['bulan'];
        $defaultTin = (string) $request->input('default_npwp_pemotong', '');
        $defaultIdTku = (string) $request->input('default_id_tku', '');
        $defaultKodeObjek = (string) $request->input('default_kode_objek', '21-100-01');
        $defaultCutOff = Carbon::create($selectedYear, max(1, min(12, $selectedMonth)), 1)->endOfMonth()->format('Y-m-d');

        $entries = $this->prepareEbupotEntries(
            $currentUser,
            $selectedType,
            $selectedYear,
            $selectedMonth,
            $defaultTin,
            $defaultIdTku,
            $defaultKodeObjek,
            $defaultCutOff
        );

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'entries' => 'Data gaji untuk periode ini tidak tersedia.',
            ]);
        }

        $report = $this->persistEbupotReport(
            $currentUser,
            $selectedType,
            $selectedYear,
            $selectedMonth,
            $defaultTin,
            $defaultIdTku,
            $defaultKodeObjek,
            $defaultCutOff,
            $entries,
            'gaji'
        );

        $exportType = strtolower((string) $request->input('export', 'xlsx'));

        if ($exportType === 'xml') {
            $xmlContent = $this->buildEbupotXml(
                $entries,
                $report->npwp_pemotong ?? '',
                $selectedMonth,
                $selectedYear
            );
            $xmlFilename = sprintf(
                'ebupot-%s-%d-%s.xml',
                $selectedType,
                $selectedYear,
                str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT)
            );

            return response()->streamDownload(function () use ($xmlContent) {
                echo $xmlContent;
            }, $xmlFilename, [
                'Content-Type' => 'application/xml',
            ]);
        }

        $headings = $this->ebupotHeadings();
        $rows = $this->mapEbupotRows($entries);

        $filename = sprintf(
            'ebupot-%s-%d-%s.xlsx',
            $selectedType,
            $selectedYear,
            str_pad((string) $selectedMonth, 2, '0', STR_PAD_LEFT)
        );

        return $this->xlsxService->download(array_merge([$headings], $rows), $filename);
    }

    public function downloadEbupot(Request $request, EbupotReport $report): StreamedResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);
        $this->ensureCanAccessEbupotReport($report, $currentUser);
        abort_if($report->source !== 'gaji', 404);

        $entries = collect($report->payload['entries'] ?? []);

        if ($entries->isEmpty()) {
            abort(404, 'Data e-Bupot tidak ditemukan.');
        }

        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'xml') {
            $xmlContent = $this->buildEbupotXml(
                $entries,
                $report->npwp_pemotong ?? '',
                $report->bulan,
                $report->tahun
            );

            $filename = sprintf(
                'ebupot-%s-%d-%s.xml',
                $report->jenis_asn,
                $report->tahun,
                str_pad((string) $report->bulan, 2, '0', STR_PAD_LEFT)
            );

            return response()->streamDownload(function () use ($xmlContent) {
                echo $xmlContent;
            }, $filename, [
                'Content-Type' => 'application/xml',
            ]);
        }

        $headings = $this->ebupotHeadings();
        $rows = $this->mapEbupotRows($entries);

        $filename = sprintf(
            'ebupot-%s-%d-%s.xlsx',
            $report->jenis_asn,
            $report->tahun,
            str_pad((string) $report->bulan, 2, '0', STR_PAD_LEFT)
        );

        return $this->xlsxService->download(array_merge([$headings], $rows), $filename);
    }
    private function prepareEbupotEntries(User $currentUser, string $selectedType, int $selectedYear, int $selectedMonth, string $defaultTin, string $defaultIdTku, string $defaultKodeObjek, string $defaultCutOffDate): Collection
    {
        $allowanceFields = array_keys($this->allowanceFields());

        $gajiQuery = Gaji::query()
            ->with(['pegawai' => function ($relation) {
                $relation->select('id', 'nama_lengkap', 'nik', 'status_perkawinan', 'jumlah_istri_suami', 'jumlah_anak', 'skpd_id');
            }])
            ->whereIn('jenis_asn', $this->jenisAsnScope($selectedType))
            ->where('tahun', $selectedYear)
            ->where('bulan', $selectedMonth);

        if (! $currentUser->isSuperAdmin()) {
            $gajiQuery->whereHas('pegawai', function ($query) use ($currentUser) {
                $query->where('skpd_id', $currentUser->skpd_id);
            });
        }

        $gajis = $gajiQuery->orderBy('pegawai_id')->get();

        return $gajis->map(function (Gaji $gaji) use ($allowanceFields, $defaultTin, $defaultIdTku, $defaultKodeObjek, $defaultCutOffDate, $selectedMonth, $selectedYear) {
            $pegawai = $gaji->pegawai;
            $statusCode = ($pegawai !== null && (int) ($pegawai->status_perkawinan ?? 0) === 1) ? 'K' : 'TK';
            $dependants = ($pegawai !== null ? (int) ($pegawai->jumlah_istri_suami ?? 0) : 0)
                + ($pegawai !== null ? (int) ($pegawai->jumlah_anak ?? 0) : 0);

            $allowanceTotal = 0.0;
            foreach ($allowanceFields as $field) {
                $allowanceTotal += (float) ($gaji->{$field} ?? 0.0);
            }

            $penghasilanKotor = $allowanceTotal
                - (float) ($gaji->pembulatan_gaji ?? 0.0)
                - (float) ($gaji->potongan_pph_21 ?? 0.0)
                - (float) ($gaji->iuran_jaminan_kecelakaan_kerja ?? 0.0)
                - (float) ($gaji->iuran_jaminan_kematian ?? 0.0);

            if ($penghasilanKotor < 0) {
                $penghasilanKotor = 0.0;
            }

            $terA = $this->calculateTerRate($penghasilanKotor, self::TER_A_BANDS);
            $terB = $this->calculateTerRate($penghasilanKotor, self::TER_B_BANDS);
            $terC = $this->calculateTerRate($penghasilanKotor, self::TER_C_BANDS);

            $status = sprintf('%s/%d', $statusCode, $dependants);
            $tarif = $this->determineTarif($status, $terA, $terB, $terC);

            $masaPajak = (int) ($gaji->bulan ?? $selectedMonth);
            if ($masaPajak < 1 || $masaPajak > 12) {
                $masaPajak = $selectedMonth;
            }

            $tahunPajak = (int) ($gaji->tahun ?? $selectedYear);
            if ($tahunPajak < 2000) {
                $tahunPajak = $selectedYear;
            }

            return [
                'npwp_pemotong' => $this->digitsOnly($defaultTin),
                'masa_pajak' => $masaPajak,
                'tahun_pajak' => $tahunPajak,
                'status_pegawai' => 'Resident',
                'npwp_nik_tin' => $this->digitsOnly((string) ($pegawai->nik ?? '')),
                'nomor_passport' => '',
                'status' => $status,
                'posisi' => 'IRT',
                'sertifikat_fasilitas' => 'N/A',
                'kode_objek_pajak' => $defaultKodeObjek,
                'gross' => $penghasilanKotor,
                'gross_formatted' => number_format($penghasilanKotor, 2, '.', ''),
                'tarif' => $tarif,
                'tarif_formatted' => number_format($tarif, 4, '.', ''),
                'id_tku' => $this->digitsOnly($defaultIdTku),
                'tgl_pemotongan' => $defaultCutOffDate,
            ];
        })->values();
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
    private function normalizeNumericInput($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return 0.0;
            }

            $normalized = str_replace(["\u{00A0}", ' '], '', $trimmed);

            if (preg_match('/^-?[0-9]+(?:[.,][0-9]+)?$/', $normalized) === 1) {
                return (float) str_replace(',', '.', $normalized);
            }

            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);

            $filtered = preg_replace('/[^0-9.\-]/', '', $normalized);
            if ($filtered === '' || $filtered === '-' || $filtered === '.') {
                return 0.0;
            }

            return (float) $filtered;
        }

        return 0.0;
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

    private function totalAllowanceFields(): array
    {
        return config('gaji.total_allowance_fields', []);
    }

    private function totalDeductionFields(): array
    {
        return config('gaji.total_deduction_fields', []);
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

    private function exportHeadings(array $monetaryLabels): array
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
        ], array_values($monetaryLabels), [
            'Jumlah Gaji dan Tunjangan',
            'Jumlah Potongan',
            'Jumlah Ditransfer',
        ]);
    }

    private function templateSampleRow(array $monetaryLabels): array
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

        $row = array_merge($row, array_fill(0, count($monetaryLabels), 0.0));
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














































