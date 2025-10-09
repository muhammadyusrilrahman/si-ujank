@extends('tpps.layout')

@section('title', $isUpdate ? 'Ubah Perhitungan TPP' : 'Buat Perhitungan TPP')

@section('card-tools')
    <a href="{{ route('tpps.perhitungan', $filterParams) }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Kembali ke Perhitungan
    </a>
@endsection

@section('card-body')
    @php
        $payload = collect($payload ?? []);
        $extrasLabels = [
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
        $actionRoute = $isUpdate
            ? route('tpps.perhitungan.update', ['calculation' => $calculation->id] + $filterParams)
            : route('tpps.perhitungan.store', $filterParams);
    @endphp

    <form method="POST" id="perhitungan-form" action="{{ $actionRoute }}">
        @csrf
        @if ($isUpdate)
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label" for="jenis_asn">Jenis ASN</label>
                <select name="jenis_asn" id="jenis_asn" class="form-control @error('jenis_asn') is-invalid @enderror" {{ $isUpdate ? 'readonly' : '' }}>
                    @foreach ($typeLabels as $key => $label)
                        <option value="{{ $key }}" {{ old('jenis_asn', $selectedType) === $key ? 'selected' : '' }}>
                            {{ strtoupper($key) }} - {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('jenis_asn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="tahun">Tahun</label>
                <input type="number" min="2000" max="{{ date('Y') + 5 }}" class="form-control @error('tahun') is-invalid @enderror" id="tahun" name="tahun" value="{{ old('tahun', $defaultYear) }}" required>
                @error('tahun')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="bulan">Bulan</label>
                <select name="bulan" id="bulan" class="form-control @error('bulan') is-invalid @enderror" required>
                    <option value="" disabled {{ $defaultMonth === null ? 'selected' : '' }}>Pilih Bulan</option>
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}" {{ (int) old('bulan', $defaultMonth) === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('bulan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" for="pegawai_id">Pegawai</label>
                @if ($isUpdate)
                    <select class="form-control" id="pegawai_id" name="pegawai_id" disabled>
                        <option value="{{ optional($pegawai)->id }}">{{ optional($pegawai)->nama_lengkap }} @if (optional($pegawai)->nip) - {{ optional($pegawai)->nip }} @endif</option>
                    </select>
                    <input type="hidden" name="pegawai_id" value="{{ optional($pegawai)->id }}">
                @else
                    <select class="form-control @error('pegawai_id') is-invalid @enderror" id="pegawai_id" name="pegawai_id" required>
                        <option value="" disabled {{ old('pegawai_id') ? '' : 'selected' }}>Pilih Pegawai</option>
                        @foreach ($pegawaiOptions as $option)
                            <option value="{{ $option->id }}" {{ (int) old('pegawai_id') === (int) $option->id ? 'selected' : '' }}>
                                {{ $option->nama_lengkap }} @if ($option->nip) - {{ $option->nip }} @endif
                            </option>
                        @endforeach
                    </select>
                    @error('pegawai_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" for="kelas_jabatan">Kelas Jabatan</label>
                <input type="text" class="form-control @error('kelas_jabatan') is-invalid @enderror" id="kelas_jabatan" name="kelas_jabatan" value="{{ old('kelas_jabatan', $payload->get('kelas_jabatan')) }}">
                @error('kelas_jabatan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="golongan">Golongan / Ruang</label>
                <input type="text" class="form-control @error('golongan') is-invalid @enderror" id="golongan" name="golongan" value="{{ old('golongan', $payload->get('golongan')) }}">
                @error('golongan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="tanda_terima">Nomor Rekening (Tanda Terima)</label>
                <input type="text" class="form-control @error('tanda_terima') is-invalid @enderror" id="tanda_terima" name="tanda_terima" value="{{ old('tanda_terima', $payload->get('tanda_terima')) }}" placeholder="Otomatis diisi dari data pegawai">
                @error('tanda_terima')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @php
            $rawBeban = old('beban_kerja', $payload->get('beban_kerja', 0));
            $displayBeban = ($rawBeban === null || $rawBeban === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawBeban, 2, false);
            $rawKondisi = old('kondisi_kerja', $payload->get('kondisi_kerja', 0));
            $displayKondisi = ($rawKondisi === null || $rawKondisi === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawKondisi, 2, false);
        @endphp
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label" for="beban_kerja_display">Beban Kerja (Rp)</label>
                <input type="hidden" name="beban_kerja" id="beban_kerja" value="{{ $rawBeban === null || $rawBeban === '' ? '' : $rawBeban }}">
                <div class="input-group currency-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input
                        type="text"
                        id="beban_kerja_display"
                        class="form-control currency-input {{ $errors->has('beban_kerja') ? 'is-invalid' : '' }}"
                        data-target="beban_kerja"
                        value="{{ $displayBeban }}"
                        placeholder="0,00"
                        required
                        inputmode="decimal"
                        autocomplete="off">
                </div>
                @error('beban_kerja')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label" for="kondisi_kerja_display">Kondisi Kerja (Rp)</label>
                <input type="hidden" name="kondisi_kerja" id="kondisi_kerja" value="{{ $rawKondisi === null || $rawKondisi === '' ? '' : $rawKondisi }}">
                <div class="input-group currency-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input
                        type="text"
                        id="kondisi_kerja_display"
                        class="form-control currency-input {{ $errors->has('kondisi_kerja') ? 'is-invalid' : '' }}"
                        data-target="kondisi_kerja"
                        value="{{ $displayKondisi }}"
                        placeholder="0,00"
                        required
                        inputmode="decimal"
                        autocomplete="off">
                </div>
                @error('kondisi_kerja')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-4">
            <h5 class="mb-3">Tambahan TPP</h5>
            <div class="row">
                @foreach ($extrasLabels as $key => $label)
                    @php
                        $rawExtra = old($key, $payload->get($key, 0));
                        $displayExtra = ($rawExtra === null || $rawExtra === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawExtra, 2, false);
                        $hiddenId = "extra-{$key}";
                        $displayId = "extra-{$key}-display";
                    @endphp
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="{{ $displayId }}">{{ $label }}</label>
                        <input
                            type="hidden"
                            id="{{ $hiddenId }}"
                            name="{{ $key }}"
                            class="perhitungan-extra-input"
                            value="{{ $rawExtra === null || $rawExtra === '' ? '' : $rawExtra }}">
                        <div class="input-group currency-input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input
                                type="text"
                                id="{{ $displayId }}"
                                class="form-control currency-input {{ $errors->has($key) ? 'is-invalid' : '' }}"
                                data-target="{{ $hiddenId }}"
                                value="{{ $displayExtra }}"
                                placeholder="0,00"
                                inputmode="decimal"
                                autocomplete="off">
                        </div>
                        @error($key)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <h5 class="mb-3">Indeks Presensi</h5>
                <div class="mb-3">
                    <label class="form-label" for="presensi-ketidakhadiran">Jumlah Ketidakhadiran</label>
                    <input type="number" min="0" step="0.01" class="form-control @error('presensi_ketidakhadiran') is-invalid @enderror" id="presensi-ketidakhadiran" name="presensi_ketidakhadiran" value="{{ old('presensi_ketidakhadiran', $payload->get('presensi_ketidakhadiran', 0)) }}">
                    @error('presensi_ketidakhadiran')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="presensi-persentase-ketidakhadiran">% Ketidakhadiran</label>
                    <input type="number" min="0" max="40" step="0.01" class="form-control @error('presensi_persen_ketidakhadiran') is-invalid @enderror" id="presensi-persentase-ketidakhadiran" name="presensi_persen_ketidakhadiran" value="{{ old('presensi_persen_ketidakhadiran', $payload->get('presensi_persen_ketidakhadiran', 0)) }}" readonly>
                    @error('presensi_persen_ketidakhadiran')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="presensi-persentase-kehadiran">% Kehadiran (otomatis)</label>
                    <input type="number" min="0" max="40" step="0.01" class="form-control @error('presensi_persen_kehadiran') is-invalid @enderror" id="presensi-persentase-kehadiran" name="presensi_persen_kehadiran" value="{{ old('presensi_persen_kehadiran', $payload->get('presensi_persen_kehadiran', 40)) }}" readonly>
                    @error('presensi_persen_kehadiran')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="presensi-nilai">Nilai Presensi (Rp)</label>
                    <input type="number" min="0" step="0.01" class="form-control @error('presensi_nilai') is-invalid @enderror" id="presensi-nilai" name="presensi_nilai" value="{{ old('presensi_nilai', $payload->get('presensi_nilai', 0)) }}" readonly>
                    @error('presensi_nilai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <h5 class="mb-3">Indeks Kinerja</h5>
                <div class="mb-3">
                    <label class="form-label" for="kinerja-persentase">Persentase Kinerja (%)</label>
                    <input type="number" min="0" max="60" step="0.01" class="form-control @error('kinerja_persen') is-invalid @enderror" id="kinerja-persentase" name="kinerja_persen" value="{{ old('kinerja_persen', $payload->get('kinerja_persen', 60)) }}">
                    @error('kinerja_persen')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="kinerja-nilai">Nilai Kinerja (Rp)</label>
                    <input type="number" min="0" step="0.01" class="form-control @error('kinerja_nilai') is-invalid @enderror" id="kinerja-nilai" name="kinerja_nilai" value="{{ old('kinerja_nilai', $payload->get('kinerja_nilai', 0)) }}" readonly>
                    @error('kinerja_nilai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        @php
            $rawPfkPph = old('pfk_pph21', $payload->get('pfk_pph21', 0));
            $displayPfkPph = ($rawPfkPph === null || $rawPfkPph === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawPfkPph, 2, false);
            $rawPfkBpjs4 = old('pfk_bpjs4', $payload->get('pfk_bpjs4', 0));
            $displayPfkBpjs4 = ($rawPfkBpjs4 === null || $rawPfkBpjs4 === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawPfkBpjs4, 2, false);
            $rawPfkBpjs1 = old('pfk_bpjs1', $payload->get('pfk_bpjs1', 0));
            $displayPfkBpjs1 = ($rawPfkBpjs1 === null || $rawPfkBpjs1 === '') ? '' : \App\Support\MoneyFormatter::rupiah($rawPfkBpjs1, 2, false);
        @endphp
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label" for="pfk-pph21-display">PFK PPh Pasal 21 (Rp)</label>
                <input type="hidden" name="pfk_pph21" id="pfk_pph21" value="{{ $rawPfkPph === null || $rawPfkPph === '' ? '' : $rawPfkPph }}">
                <div class="input-group currency-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input
                        type="text"
                        id="pfk-pph21-display"
                        class="form-control currency-input {{ $errors->has('pfk_pph21') ? 'is-invalid' : '' }}"
                        data-target="pfk_pph21"
                        value="{{ $displayPfkPph }}"
                        placeholder="0,00"
                        inputmode="decimal"
                        autocomplete="off">
                </div>
                @error('pfk_pph21')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="pfk-bpjs4-display">PFK BPJS 4% (Rp)</label>
                <input type="hidden" name="pfk_bpjs4" id="pfk_bpjs4" value="{{ $rawPfkBpjs4 === null || $rawPfkBpjs4 === '' ? '' : $rawPfkBpjs4 }}">
                <div class="input-group currency-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input
                        type="text"
                        id="pfk-bpjs4-display"
                        class="form-control currency-input {{ $errors->has('pfk_bpjs4') ? 'is-invalid' : '' }}"
                        data-target="pfk_bpjs4"
                        value="{{ $displayPfkBpjs4 }}"
                        placeholder="0,00"
                        inputmode="decimal"
                        autocomplete="off">
                </div>
                @error('pfk_bpjs4')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="pfk-bpjs1-display">PFK BPJS 1% (Rp)</label>
                <input type="hidden" name="pfk_bpjs1" id="pfk_bpjs1" value="{{ $rawPfkBpjs1 === null || $rawPfkBpjs1 === '' ? '' : $rawPfkBpjs1 }}">
                <div class="input-group currency-input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Rp</span>
                    </div>
                    <input
                        type="text"
                        id="pfk-bpjs1-display"
                        class="form-control currency-input {{ $errors->has('pfk_bpjs1') ? 'is-invalid' : '' }}"
                        data-target="pfk_bpjs1"
                        value="{{ $displayPfkBpjs1 }}"
                        placeholder="0,00"
                        inputmode="decimal"
                        autocomplete="off">
                </div>
                @error('pfk_bpjs1')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card border shadow-sm mb-4">
            <div class="card-body">
                <div class="row text-center text-md-left">
                    <div class="col-md-2 mb-3 mb-md-0">
                        <div class="text-muted text-uppercase small">Jumlah TPP</div>
                        <div class="h5 mb-0" data-summary="jumlah_tpp">{{ \App\Support\MoneyFormatter::rupiah($payload->get('jumlah_tpp', 0)) }}</div>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <div class="text-muted text-uppercase small">Bruto</div>
                        <div class="h5 mb-0" data-summary="bruto">{{ \App\Support\MoneyFormatter::rupiah($payload->get('bruto', 0)) }}</div>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <div class="text-muted text-uppercase small">PFK BPJS 4%</div>
                        <div class="h5 mb-0" data-summary="pfk_bpjs4">{{ \App\Support\MoneyFormatter::rupiah($payload->get('pfk_bpjs4', 0)) }}</div>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <div class="text-muted text-uppercase small">PFK BPJS 1%</div>
                        <div class="h5 mb-0" data-summary="pfk_bpjs1">{{ \App\Support\MoneyFormatter::rupiah($payload->get('pfk_bpjs1', 0)) }}</div>
                    </div>
                    <div class="col-md-2 mb-3 mb-md-0">
                        <div class="text-muted text-uppercase small">PFK PPh 21</div>
                        <div class="h5 mb-0" data-summary="pfk_pph21">{{ \App\Support\MoneyFormatter::rupiah($payload->get('pfk_pph21', 0)) }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-muted text-uppercase small">Netto</div>
                        <div class="h5 mb-0" data-summary="netto">{{ \App\Support\MoneyFormatter::rupiah($payload->get('netto', 0)) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ $isUpdate ? 'Simpan Perubahan' : 'Simpan Perhitungan' }}
            </button>
            <a href="{{ route('tpps.perhitungan', $filterParams) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('perhitungan-form');
        if (!form) {
            return;
        }

        const extrasInputs = Array.from(form.querySelectorAll('.perhitungan-extra-input'));
        const bebanInput = form.querySelector('[name="beban_kerja"]');
        const kondisiInput = form.querySelector('[name="kondisi_kerja"]');
        const pfkPphInput = form.querySelector('[name="pfk_pph21"]');
        const presensiCountInput = form.querySelector('[name="presensi_ketidakhadiran"]');
        const presensiPercentAbsentInput = form.querySelector('[name="presensi_persen_ketidakhadiran"]');
        const presensiPercentPresenceInput = form.querySelector('[name="presensi_persen_kehadiran"]');
        const presensiValueInput = form.querySelector('[name="presensi_nilai"]');
        const kinerjaPercentInput = form.querySelector('[name="kinerja_persen"]');
        const kinerjaValueInput = form.querySelector('[name="kinerja_nilai"]');
        const pfkBpjs4Input = form.querySelector('[name="pfk_bpjs4"]');
        const pfkBpjs1Input = form.querySelector('[name="pfk_bpjs1"]');

        const summaries = {
            jumlah: form.querySelector('[data-summary="jumlah_tpp"]'),
            bruto: form.querySelector('[data-summary="bruto"]'),
            pfkBpjs4: form.querySelector('[data-summary="pfk_bpjs4"]'),
            pfkBpjs1: form.querySelector('[data-summary="pfk_bpjs1"]'),
            pfkPph: form.querySelector('[data-summary="pfk_pph21"]'),
            netto: form.querySelector('[data-summary="netto"]'),
        };

        const formatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formatRupiah = (value) => `Rp ${formatter.format(value)}`;
        const formatNumber = (value) => formatter.format(value);

        const parseCurrency = (value) => {
            if (typeof value !== 'string') {
                return null;
            }
            const normalized = value.replace(/\./g, '').replace(',', '.');
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : null;
        };

        const sanitizeCurrencyInput = (value) => {
            const cleaned = value.replace(/[^\d,]/g, '');
            const [integerPart, decimalPart] = cleaned.split(',', 2);
            if (decimalPart !== undefined) {
                return `${integerPart},${decimalPart.slice(0, 2)}`;
            }
            return integerPart;
        };

        const toNumber = (value) => {
            const parsed = Number.parseFloat(value);
            return Number.isFinite(parsed) ? parsed : 0;
        };

        const setSummary = (el, value) => {
            if (el) {
                el.textContent = formatRupiah(value);
            }
        };

        const updateSummary = () => {
            const beban = toNumber(bebanInput?.value ?? 0);
            const kondisi = toNumber(kondisiInput?.value ?? 0);
            const extrasTotal = extrasInputs.reduce((carry, input) => carry + toNumber(input.value), 0);
            const jumlah = beban + kondisi + extrasTotal;

            let absentCount = toNumber(presensiCountInput?.value ?? 0);
            if (absentCount < 0) {
                absentCount = 0;
                if (presensiCountInput) {
                    presensiCountInput.value = absentCount.toFixed(2);
                }
            }

            const absentPercent = Math.min(40, absentCount * 3);
            const presencePercent = Math.max(0, 40 - absentPercent);
            const presenceValue = jumlah * (presencePercent / 100);

            if (presensiPercentAbsentInput) {
                presensiPercentAbsentInput.value = absentPercent.toFixed(2);
            }
            if (presensiPercentPresenceInput) {
                presensiPercentPresenceInput.value = presencePercent.toFixed(2);
            }
            if (presensiValueInput) {
                presensiValueInput.value = presenceValue.toFixed(2);
            }

            let kinerjaPercent = toNumber(kinerjaPercentInput?.value ?? 60);
            if (kinerjaPercent < 0) {
                kinerjaPercent = 0;
            } else if (kinerjaPercent > 60) {
                kinerjaPercent = 60;
            }
            if (kinerjaPercentInput) {
                kinerjaPercentInput.value = kinerjaPercent.toFixed(2);
            }
            const kinerjaValue = jumlah * (kinerjaPercent / 100);
            if (kinerjaValueInput) {
                kinerjaValueInput.value = kinerjaValue.toFixed(2);
            }

            const pfkPph = toNumber(pfkPphInput?.value ?? 0);
            const pfkBpjs4 = toNumber(pfkBpjs4Input?.value ?? 0);
            const pfkBpjs1 = toNumber(pfkBpjs1Input?.value ?? 0);
            const bruto = presenceValue + kinerjaValue + pfkPph + pfkBpjs4;
            const netto = bruto - (pfkPph + pfkBpjs4 + pfkBpjs1);

            setSummary(summaries.jumlah, jumlah);
            setSummary(summaries.bruto, bruto);
            setSummary(summaries.pfkBpjs4, pfkBpjs4);
            setSummary(summaries.pfkBpjs1, pfkBpjs1);
            setSummary(summaries.pfkPph, pfkPph);
            setSummary(summaries.netto, netto);
        };

        const currencyInputs = Array.from(form.querySelectorAll('.currency-input'));
        currencyInputs.forEach((displayInput) => {
            const targetId = displayInput.dataset.target;
            if (!targetId) {
                return;
            }

            const hiddenInput = document.getElementById(targetId);
            if (!hiddenInput) {
                return;
            }

            const syncHidden = (numericValue) => {
                if (numericValue === null) {
                    hiddenInput.value = '';
                } else {
                    hiddenInput.value = numericValue.toFixed(2);
                }
                updateSummary();
            };

            displayInput.addEventListener('focus', () => {
                const numericValue = parseCurrency(displayInput.value);
                displayInput.value = numericValue === null ? '' : numericValue.toFixed(2).replace('.', ',');
                displayInput.select();
            });

            displayInput.addEventListener('input', () => {
                const sanitized = sanitizeCurrencyInput(displayInput.value);
                if (sanitized !== displayInput.value) {
                    displayInput.value = sanitized;
                }
                const numericValue = parseCurrency(displayInput.value);
                syncHidden(numericValue);
            });

            displayInput.addEventListener('blur', () => {
                const numericValue = parseCurrency(displayInput.value);
                displayInput.value = numericValue === null ? '' : formatNumber(numericValue);
                syncHidden(numericValue);
            });

            const initialNumeric = parseCurrency(displayInput.value);
            displayInput.value = initialNumeric === null ? '' : formatNumber(initialNumeric);
            syncHidden(initialNumeric);
        });

        const watch = (input, listener) => {
            if (!input) {
                return;
            }
            input.addEventListener('input', listener);
            input.addEventListener('change', listener);
        };

        watch(bebanInput, updateSummary);
        watch(kondisiInput, updateSummary);
        watch(pfkPphInput, updateSummary);
        watch(kinerjaPercentInput, updateSummary);
        watch(presensiCountInput, updateSummary);
        watch(pfkBpjs4Input, updateSummary);
        watch(pfkBpjs1Input, updateSummary);

        extrasInputs.forEach((input) => watch(input, updateSummary));

        updateSummary();
    });
</script>
@endpush
