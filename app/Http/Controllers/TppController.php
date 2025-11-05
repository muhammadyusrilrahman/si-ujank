<?php

namespace App\Http\Controllers;

use App\Exports\TppExport;
use App\Exports\TppTemplateExport;
use App\Imports\TppImport;
use App\Http\Controllers\Concerns\HandlesEbupot;
use App\Services\XlsxService;
use App\Models\EbupotReport;
use App\Models\Pegawai;
use App\Models\Tpp;
use App\Models\User;
use App\Models\Skpd;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TppController extends Controller
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
        ]);

        $selectedYear = array_key_exists('tahun', $validated) ? (int) $validated['tahun'] : null;
        $selectedMonth = array_key_exists('bulan', $validated) ? (int) $validated['bulan'] : null;
        $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : $perPageOptions[0];
        $filtersReady = $selectedYear !== null && $selectedMonth !== null;
        $searchTerm = array_key_exists('search', $validated) ? trim((string) $validated['search']) : null;
        if ($searchTerm === '') {
            $searchTerm = null;
        }

        $totalTppFields = $this->totalTppFields();
        $totalPotonganFields = $this->totalPotonganFields();

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
            'totalTppFields' => $totalTppFields,
            'totalPotonganFields' => $totalPotonganFields,
            'skpds' => $currentUser->isSuperAdmin() ? Skpd::cachedOptions() : collect(),
        ];

        if ($filtersReady) {
            $baseQuery = Tpp::query()
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

            $tpps = (clone $baseQuery)
                ->with(['pegawai' => function ($relation) {
                    $relation->select('id', 'nama_lengkap', 'nip', 'status_asn', 'skpd_id');
                }])
                ->orderBy('pegawai_id')
                ->paginate($perPage)
                ->withQueryString();

            $monetaryFieldKeys = array_keys($this->monetaryFields());
            $monetaryTotals = $this->aggregateMonetaryTotals($baseQuery, $monetaryFieldKeys);

            $allowanceTotal = $this->sumTotalsByKey($monetaryTotals, $totalTppFields);
            $deductionTotal = $this->sumTotalsByKey($monetaryTotals, $totalPotonganFields);

            $viewData['tpps'] = $tpps;
            $viewData['monetaryTotals'] = $monetaryTotals;
            $viewData['summaryTotals'] = [
                'allowance' => $allowanceTotal,
                'deduction' => $deductionTotal,
                'transfer' => $allowanceTotal - $deductionTotal,
            ];
        } else {
            $viewData['tpps'] = null;
            $viewData['monetaryTotals'] = [];
            $viewData['summaryTotals'] = [
                'allowance' => 0.0,
                'deduction' => 0.0,
                'transfer' => 0.0,
            ];
        }

        return view('tpps.index', $viewData);
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
            ->where('source', 'tpp')
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

        return view('tpps.ebupot.index', [
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

        return view('tpps.ebupot.create', [
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
                'entries' => 'Data TPP untuk periode ini tidak tersedia.',
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
            'tpp'
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
                'ebupot-tpp-%s-%d-%s.xml',
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
            'ebupot-tpp-%s-%d-%s.xlsx',
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
        abort_if($report->source !== 'tpp', 404);

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
                'ebupot-tpp-%s-%d-%s.xml',
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
            'ebupot-tpp-%s-%d-%s.xlsx',
            $report->jenis_asn,
            $report->tahun,
            str_pad((string) $report->bulan, 2, '0', STR_PAD_LEFT)
        );

        return $this->xlsxService->download(array_merge([$headings], $rows), $filename);
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

        return view('tpps.create', [
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

        $duplicateExists = Tpp::where('pegawai_id', $pegawai->id)
            ->where('tahun', $validated['tahun'])
            ->where('bulan', $validated['bulan'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data TPP untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        $data = array_merge([
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $this->resolveJenisAsnForPegawai($selectedType, $pegawai),
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
        ], $this->extractMonetaryValues($request));

        try {
            Tpp::create($data);
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data TPP untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        return redirect()->route('tpps.index', [
            'type' => $selectedType,
            'tahun' => $data['tahun'],
            'bulan' => $data['bulan'],
        ])->with('status', 'Data TPP berhasil ditambahkan.');
    }

    public function edit(Request $request, Tpp $tpp): View
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $tpp->load('pegawai');
        $this->ensureCanManageTpp($tpp, $currentUser);

        $selectedType = $this->resolveType($request->query('type', $tpp->jenis_asn));
        $monthOptions = $this->monthOptions();

        return view('tpps.edit', [
            'tpp' => $tpp,
            'selectedType' => $selectedType,
            'typeLabels' => $this->typeLabels(),
            'monthOptions' => $monthOptions,
            'defaultYear' => $tpp->tahun,
            'defaultMonth' => $tpp->bulan,
            'pegawaiOptions' => $this->pegawaiOptionsForType($selectedType, $currentUser, $tpp->pegawai),
            'monetaryFields' => $this->monetaryFields(),
        ]);
    }

    public function update(Request $request, Tpp $tpp): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $tpp->load('pegawai');
        $this->ensureCanManageTpp($tpp, $currentUser);

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

        $duplicate = Tpp::where('pegawai_id', $pegawai->id)
            ->where('tahun', $validated['tahun'])
            ->where('bulan', $validated['bulan'])
            ->where('id', '!=', $tpp->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Data TPP untuk pegawai dan periode ini sudah ada.',
            ]);
        }

        $updateData = array_merge([
            'pegawai_id' => $pegawai->id,
            'jenis_asn' => $this->resolveJenisAsnForPegawai($selectedType, $pegawai),
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
        ], $this->extractMonetaryValues($request));

        $tpp->update($updateData);

        return redirect()->route('tpps.index', [
            'type' => $selectedType,
            'tahun' => $updateData['tahun'],
            'bulan' => $updateData['bulan'],
        ])->with('status', 'Data TPP berhasil diperbarui.');
    }

    public function destroy(Request $request, Tpp $tpp): RedirectResponse
    {
        $currentUser = $request->user();
        abort_unless($currentUser->isSuperAdmin() || $currentUser->isAdminUnit(), 403);

        $tpp->load('pegawai');
        $this->ensureCanManageTpp($tpp, $currentUser);

        $tpp->delete();

        $redirectParams = array_filter([
            'type' => $this->resolveType($request->input('type', $tpp->jenis_asn)),
            'tahun' => $request->input('tahun', $tpp->tahun),
            'bulan' => $request->input('bulan', $tpp->bulan),
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->route('tpps.index', $redirectParams)->with('status', 'Data TPP berhasil dihapus.');
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

            $query = Tpp::query()
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
                return redirect()->route('tpps.index', $redirectParams)->with('status', 'Tidak ada data TPP yang dihapus.');
            }

            $deleted = $query->delete();

            return redirect()->route('tpps.index', $redirectParams)->with('status', "Berhasil menghapus {$deleted} data TPP.");
        }

        $redirectParams = array_filter([
            'type' => $request->filled('type') ? $this->resolveType($request->input('type')) : null,
            'tahun' => $request->input('tahun'),
            'bulan' => $request->input('bulan'),
            'per_page' => $request->input('per_page'),
        ], fn ($value) => $value !== null && $value !== '');

        $rawIds = $request->input('ids');
        if (! is_array($rawIds) || empty($rawIds)) {
            return redirect()->route('tpps.index', $redirectParams)->with('status', 'Pilih data TPP yang ingin dihapus.');
        }

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:tpps,id'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('tpps.index', $redirectParams)
                ->withErrors($validator)
                ->with('status', 'Tidak dapat menghapus data TPP terpilih.');
        }

        $ids = array_values(array_unique(array_map('intval', $validator->validated()['ids'])));

        $query = Tpp::whereIn('id', $ids);

        if (! $currentUser->isSuperAdmin()) {
            $query->whereHas('pegawai', function ($sub) use ($currentUser) {
                $sub->where('skpd_id', $currentUser->skpd_id);
            });
        }

        $deleted = $query->delete();
        $notDeleted = count($ids) - $deleted;

        if ($deleted === 0) {
            return redirect()->route('tpps.index', $redirectParams)->with('status', 'Tidak ada data TPP yang dihapus.');
        }

        $message = "Berhasil menghapus {$deleted} data TPP terpilih.";
        if ($notDeleted > 0) {
            $message .= " {$notDeleted} data tidak dapat dihapus.";
        }

        return redirect()->route('tpps.index', $redirectParams)->with('status', $message);
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
        $totalTppFields = $this->totalTppFields();
        $totalPotonganFields = $this->totalPotonganFields();

        $export = new TppExport(
            $request->user(),
            $selectedType,
            $selectedYear,
            $selectedMonth,
            $this->exportHeadings($allowanceFields, $deductionFields),
            $monetaryLabels,
            $this->jenisAsnScope($selectedType),
            $allowanceFields,
            $deductionFields,
            $totalTppFields,
            $totalPotonganFields,
            $this->tipeJabatanOptions(),
            $this->statusAsnOptions(),
            $this->statusPerkawinanOptions()
        );

        $rows = array_merge([
            $export->headings(),
        ], $export->rows());

        $filename = sprintf(
            'tpp-%s-%d-%s.xlsx',
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

        $template = new TppTemplateExport(
            $this->exportHeadings($allowanceFields, $deductionFields),
            [$this->templateSampleRow($allowanceFields, $deductionFields)]
        );

        $rows = array_merge([
            $template->headings(),
        ], $template->rows());

        $filename = sprintf('template-tpp-%s.xlsx', $selectedType);

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
            $importer = new TppImport(
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'file' => $e->getMessage(),
            ]);
        }

        return redirect()->route('tpps.index', [
            'type' => $selectedType,
            'tahun' => $selectedYear,
            'bulan' => $selectedMonth,
            'skpd_id' => $currentUser->isSuperAdmin() ? $selectedSkpdId : null,
        ])->with('status', 'Data TPP berhasil diimpor.');
    }

    private function tppTypes(): array
    {
        return config('tpp.types', []);
    }

    private function typeLabels(): array
    {
        $labels = [];
        foreach ($this->tppTypes() as $key => $value) {
            $labels[$key] = $value['label'] ?? strtoupper($key);
        }

        return $labels;
    }

    private function asnTypeMap(): array
    {
        $map = [];
        foreach ($this->tppTypes() as $key => $config) {
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
        $months = config('tpp.months', []);

        if (empty($months) || ! is_array($months)) {
            $months = config('gaji.months', []);
        }

        return $months;
    }
    private function perPageOptions(): array
    {
        return [25, 50, 100];
    }

    private function monetaryConfig(): array
    {
        return config('tpp.monetary_fields', []);
    }

    private function monetaryFields(): array
    {
        $fields = [];
        foreach ($this->monetaryConfig() as $field => $meta) {
            $fields[$field] = $meta['label'] ?? ucwords(str_replace('_', ' ', $field));
        }

        return $fields;
    }

    private function totalTppFields(): array
    {
        return [
            'tpp_beban_kerja',
            'tpp_tempat_bertugas',
            'tpp_kondisi_kerja',
            'tpp_kelangkaan_profesi',
            'tpp_prestasi_kerja',
            'tunjangan_pph',
            'iuran_jaminan_kesehatan',
            'iuran_jaminan_kecelakaan_kerja',
            'iuran_jaminan_kematian',
            'iuran_simpanan_tapera',
            'iuran_pensiun',
            'tunjangan_jaminan_hari_tua',
        ];
    }

    private function totalPotonganFields(): array
    {
        return [
            'iuran_jaminan_kesehatan',
            'iuran_jaminan_kecelakaan_kerja',
            'iuran_jaminan_kematian',
            'iuran_simpanan_tapera',
            'iuran_pensiun',
            'tunjangan_jaminan_hari_tua',
            'potongan_iwp',
            'potongan_pph_21',
            'zakat',
            'bulog',
        ];
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

    private function ensureCanManageTpp(Tpp $tpp, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) optional($tpp->pegawai)->skpd_id !== (int) $user->skpd_id) {
            abort(403, 'Anda tidak dapat mengelola data TPP dari SKPD lain.');
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
    private function prepareEbupotEntries(
        User $currentUser,
        string $selectedType,
        int $selectedYear,
        int $selectedMonth,
        string $defaultTin,
        string $defaultIdTku,
        string $defaultKodeObjek,
        string $defaultCutOffDate
    ): Collection {
        $allowanceFields = array_keys($this->allowanceFields());
        $adjustmentFields = [
            'potongan_pph_21',
            'iuran_jaminan_kecelakaan_kerja',
            'iuran_jaminan_kematian',
        ];

        $tppQuery = Tpp::query()
            ->with(['pegawai' => function ($relation) {
                $relation->select('id', 'nama_lengkap', 'nik', 'status_perkawinan', 'jumlah_istri_suami', 'jumlah_anak', 'skpd_id');
            }])
            ->whereIn('jenis_asn', $this->jenisAsnScope($selectedType))
            ->where('tahun', $selectedYear)
            ->where('bulan', $selectedMonth);

        if (! $currentUser->isSuperAdmin()) {
            $tppQuery->whereHas('pegawai', function ($query) use ($currentUser) {
                $query->where('skpd_id', $currentUser->skpd_id);
            });
        }

        $tpps = $tppQuery->orderBy('pegawai_id')->get();

        return $tpps->map(function (Tpp $tpp) use (
            $allowanceFields,
            $adjustmentFields,
            $defaultTin,
            $defaultIdTku,
            $defaultKodeObjek,
            $defaultCutOffDate,
            $selectedMonth,
            $selectedYear
        ) {
            $pegawai = $tpp->pegawai;
            $statusCode = ($pegawai !== null && (int) ($pegawai->status_perkawinan ?? 0) === 1) ? 'K' : 'TK';
            $dependants = ($pegawai !== null ? (int) ($pegawai->jumlah_istri_suami ?? 0) : 0)
                + ($pegawai !== null ? (int) ($pegawai->jumlah_anak ?? 0) : 0);

            $allowanceTotal = 0.0;
            foreach ($allowanceFields as $field) {
                $allowanceTotal += (float) ($tpp->{$field} ?? 0.0);
            }

            $gross = $allowanceTotal;
            foreach ($adjustmentFields as $field) {
                $gross -= (float) ($tpp->{$field} ?? 0.0);
            }

            if ($gross < 0) {
                $gross = 0.0;
            }

            $terA = $this->calculateTerRate($gross, self::TER_A_BANDS);
            $terB = $this->calculateTerRate($gross, self::TER_B_BANDS);
            $terC = $this->calculateTerRate($gross, self::TER_C_BANDS);

            $status = sprintf('%s/%d', $statusCode, $dependants);
            $tarif = $this->determineTarif($status, $terA, $terB, $terC);

            $masaPajak = (int) ($tpp->bulan ?? $selectedMonth);
            if ($masaPajak < 1 || $masaPajak > 12) {
                $masaPajak = $selectedMonth;
            }

            $tahunPajak = (int) ($tpp->tahun ?? $selectedYear);
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
                'gross' => $gross,
                'gross_formatted' => number_format($gross, 2, '.', ''),
                'tarif' => $tarif,
                'tarif_formatted' => number_format($tarif, 4, '.', ''),
                'id_tku' => $this->digitsOnly($defaultIdTku),
                'tgl_pemotongan' => $defaultCutOffDate,
            ];
        })->values();
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
            'Kode Bank',
            'Nama Bank',
            'Nomor Rekening Bank Pegawai',
        ], array_values($allowanceFields), array_values($deductionFields), [
            'Jumlah TPP',
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









