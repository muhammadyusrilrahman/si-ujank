<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\TppCalculation;
use App\Models\User;
use App\Services\TppCalculationService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TppCalculationController extends Controller
{
    private TppCalculationService $calculationService;

    public function __construct(TppCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function index(Request $request)
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        abort_unless($currentUser !== null, 401);

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

        $items = [];
        $summary = null;
        $pagination = null;

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
                ->appends($request->query());

            $summary = $this->calculationService->summarize($calculations->getCollection());

            $items = $calculations->map(function (TppCalculation $calculation) {
                $row = $this->calculationService->formatForView($calculation);

                return [
                    'id' => $calculation->id,
                    'pegawai' => $row['pegawai'] ?? [],
                    'kelas_jabatan' => $row['kelas_jabatan'] ?? '-',
                    'golongan' => $row['golongan'] ?? '-',
                    'beban_kerja' => (float) ($row['beban_kerja'] ?? 0),
                    'kondisi_kerja' => (float) ($row['kondisi_kerja'] ?? 0),
                    'extras' => $row['extras'] ?? [],
                    'jumlah_tpp' => (float) ($row['jumlah_tpp'] ?? 0),
                    'presensi' => $row['presensi'] ?? [],
                    'kinerja' => $row['kinerja'] ?? [],
                    'bruto' => (float) ($row['bruto'] ?? 0),
                    'pfk' => $row['pfk'] ?? [],
                    'netto' => (float) ($row['netto'] ?? 0),
                    'tanda_terima' => $row['tanda_terima'] ?? '',
                    'routes' => [
                        'update' => route('tpps.perhitungan.update', array_merge(['calculation' => $calculation->id], array_filter([
                            'type' => $calculation->jenis_asn,
                            'tahun' => $calculation->tahun,
                            'bulan' => $calculation->bulan,
                        ], static fn ($value) => $value !== null && $value !== ''))),
                        'edit' => route('tpps.perhitungan.edit', array_merge(['calculation' => $calculation->id], array_filter([
                            'type' => $calculation->jenis_asn,
                            'tahun' => $calculation->tahun,
                            'bulan' => $calculation->bulan,
                        ], static fn ($value) => $value !== null && $value !== ''))),
                        'destroy' => route('tpps.perhitungan.destroy', array_merge(['calculation' => $calculation->id], array_filter([
                            'type' => $calculation->jenis_asn,
                            'tahun' => $calculation->tahun,
                            'bulan' => $calculation->bulan,
                        ], static fn ($value) => $value !== null && $value !== ''))),
                    ],
                ];
            })->values()->all();

            $paginatorArray = $calculations->toArray();
            $pagination = [
                'current_page' => $calculations->currentPage(),
                'per_page' => $calculations->perPage(),
                'total' => $calculations->total(),
                'from' => $calculations->firstItem(),
                'to' => $calculations->lastItem(),
                'links' => $paginatorArray['links'] ?? [],
            ];
        }

        $extraMap = $this->calculationService->extraFieldMap();
        $extraOrder = array_keys($extraMap);
        $extraLabels = collect($extraOrder)->mapWithKeys(function ($key) {
            $fallback = strtoupper(str_replace('_', ' ', $key));
            return [$key => $this->extraLabels()[$key] ?? $fallback];
        })->all();

        return response()->json([
            'data' => $items,
            'summary' => $summary,
            'filtersReady' => $filtersReady,
            'filters' => [
                'type' => $selectedType,
                'year' => $selectedYear,
                'month' => $selectedMonth,
                'per_page' => $perPage,
                'search' => $searchTerm ?? '',
            ],
            'options' => [
                'types' => collect($typeLabels)->map(function ($label, $key) {
                    return [
                        'value' => (string) $key,
                        'label' => strtoupper($key) . ' - ' . $label,
                    ];
                })->values()->all(),
                'months' => collect($monthOptions)->map(function ($label, $value) {
                    return [
                        'value' => (string) $value,
                        'label' => $label,
                    ];
                })->values()->all(),
                'per_page' => $perPageOptions,
                'yearBounds' => [
                    'min' => 2000,
                    'max' => (int) date('Y') + 5,
                ],
            ],
            'extras' => [
                'order' => $extraOrder,
                'labels' => $extraLabels,
            ],
            'permissions' => [
                'can_manage' => $currentUser->isSuperAdmin() || $currentUser->isAdminUnit(),
            ],
            'context' => [
                'hidden_fields' => array_filter([
                    'jenis_asn' => $selectedType,
                    'tahun' => $selectedYear,
                    'bulan' => $selectedMonth,
                ], static fn ($value) => $value !== null && $value !== ''),
            ],
            'meta' => [
                'pagination' => $pagination,
            ],
        ]);
    }

    /**
     * @param Builder<TppCalculation> $query
     */
    private function restrictToUserScope(Builder $query, Authenticatable $user): Builder
    {
        if ($user instanceof User && $user->isSuperAdmin()) {
            return $query;
        }

        /** @var User $user */
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

    private function extraLabels(): array
    {
        return [
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
    }
}
