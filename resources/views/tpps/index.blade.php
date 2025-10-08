

<?php $__env->startSection('title', 'Data TPP'); ?>
<?php $__env->startSection('page-title', 'Data TPP Pegawai'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $typeLabels = $typeLabels ?? ['pns' => 'PNS', 'pppk' => 'PPPK'];
    $monthOptions = $monthOptions ?? [];
    $selectedType = $selectedType ?? 'pns';
    $filtersReady = $filtersReady ?? false;
    $selectedYear = $selectedYear ?? null;
    $selectedMonth = $selectedMonth ?? null;
    $perPage = $perPage ?? 25;
    $perPageOptions = $perPageOptions ?? [25, 50, 100];
    $searchTerm = $searchTerm ?? null;
    $allowanceFields = $allowanceFields ?? [];
    $deductionFields = $deductionFields ?? [];
    $currentUser = auth()->user();
    $canManageTpp = $currentUser->isSuperAdmin() || $currentUser->isAdminUnit();
    $formatCurrency = fn (float $value) => number_format($value, 2, ',', '.');
    $tppsPaginator = $tpps ?? null;
    $tppTotal = $tppsPaginator ? $tppsPaginator->total() : 0;
    $tppCurrentCount = $tppsPaginator ? $tppsPaginator->count() : 0;
    $monetaryTotals = $monetaryTotals ?? [];
    $summaryTotals = $summaryTotals ?? ['allowance' => 0, 'deduction' => 0, 'transfer' => 0];
    $baseColumnCount = 2 + count($allowanceFields) + count($deductionFields) + 3;
    $columnCount = $baseColumnCount + ($canManageTpp ? 2 : 0);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <ul class="nav nav-pills mb-2 mb-md-0">
        <?php $__currentLoopData = $typeLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $typeKey => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="nav-item">
                <a class="nav-link <?php echo e($typeKey === $selectedType ? 'active' : ''); ?>" href="<?php echo e(route('tpps.index', array_filter([
                    'type' => $typeKey,
                    'tahun' => $selectedYear,
                    'bulan' => $selectedMonth,
                    'per_page' => $perPage === 25 ? null : $perPage,
                    'search' => $searchTerm,
                ]))); ?>"><?php echo e($label); ?></a>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <div class="d-flex flex-wrap gap-2 justify-content-end">
        <?php if($filtersReady): ?>
            <a href="<?php echo e(route('tpps.export', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth])); ?>" class="btn btn-success mb-2"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
            <?php if($canManageTpp): ?>
                <a href="<?php echo e(route('tpps.ebupot.index', array_filter(['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth]))); ?>" class="btn btn-outline-info mb-2"><i class="fas fa-clipboard-list"></i> Arsip E-Bupot</a>
                <a href="<?php echo e(route('tpps.ebupot.create', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth])); ?>" class="btn btn-info mb-2"><i class="fas fa-file-export"></i> Buat E-Bupot</a>
                <button type="submit" class="btn btn-danger mb-2" id="tpp-bulk-delete-button" form="tpp-bulk-delete-form" formaction="<?php echo e(route('tpps.bulk-destroy')); ?>" formmethod="POST" formnovalidate name="delete_all" value="0" <?php echo e($tppCurrentCount === 0 ? 'disabled' : ''); ?>>
                    <i class="fas fa-trash"></i> Hapus Terpilih
                </button>
                <button type="submit" class="btn btn-danger mb-2" id="tpp-bulk-delete-all-button" form="tpp-bulk-delete-form" formaction="<?php echo e(route('tpps.bulk-destroy')); ?>" formmethod="POST" formnovalidate name="delete_all" value="1" <?php echo e($tppTotal === 0 ? 'disabled' : ''); ?>>
                    <i class="fas fa-trash-alt"></i> Hapus Semua
                </button>
                <a href="<?php echo e(route('tpps.template', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth])); ?>" class="btn btn-outline-secondary mb-2"><i class="fas fa-download"></i> Template</a>
                <form action="<?php echo e(route('tpps.import')); ?>" method="POST" enctype="multipart/form-data" class="form-inline mb-2">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="type" value="<?php echo e($selectedType); ?>">
                    <input type="hidden" name="tahun" value="<?php echo e($selectedYear); ?>">
                    <input type="hidden" name="bulan" value="<?php echo e($selectedMonth); ?>">
                    <div class="input-group">
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="tpp-import-file" accept=".xlsx" required>
                            <label class="custom-file-label" for="tpp-import-file">Pilih file...</label>
                        </div>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-upload"></i> Import</button>
                        </div>
                    </div>
                </form>
                <a href="<?php echo e(route('tpps.create', ['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth])); ?>" class="btn btn-primary mb-2"><i class="fas fa-plus"></i> Tambah Data TPP <?php echo e($typeLabels[$selectedType] ?? strtoupper($selectedType)); ?></a>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-muted small mb-2">Pilih tahun dan bulan untuk mengakses ekspor, template, dan impor.</div>
        <?php endif; ?>
    </div>
<?php if($errors->has('file')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php $__currentLoopData = $errors->get('file'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div><?php echo e($message); ?></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>
<?php if(session('status')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('status')); ?>

        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>
<?php if($filtersReady && $canManageTpp && $tppsPaginator): ?>
    <form id="tpp-bulk-delete-form" method="POST" action="<?php echo e(route('tpps.bulk-destroy')); ?>" class="d-none">
        <?php echo csrf_field(); ?>
        <?php echo method_field('DELETE'); ?>
        <input type="hidden" name="type" value="<?php echo e($selectedType); ?>">
        <input type="hidden" name="tahun" value="<?php echo e($selectedYear); ?>">
        <input type="hidden" name="bulan" value="<?php echo e($selectedMonth); ?>">
        <input type="hidden" name="per_page" value="<?php echo e($perPage); ?>">
        <input type="hidden" name="search" value="<?php echo e($searchTerm); ?>">
        <?php $__currentLoopData = request()->except(['ids', 'page', '_token', '_method', 'delete_all', 'type', 'tahun', 'bulan', 'per_page', 'search']); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($value); ?>">
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </form>
<?php endif; ?>
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('tpps.index')); ?>" class="form-inline flex-wrap gap-2">
            <input type="hidden" name="type" value="<?php echo e($selectedType); ?>">
            <div class="form-group mr-2 mb-2">
                <label for="filter-tahun" class="mr-2">Tahun</label>
                <input type="number" name="tahun" id="filter-tahun" class="form-control <?php $__errorArgs = ['tahun'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($selectedYear ?? ''); ?>" min="2000" max="<?php echo e((int) date('Y') + 5); ?>" required>
                <?php $__errorArgs = ['tahun'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="form-group mr-2 mb-2">
                <label for="filter-bulan" class="mr-2">Bulan</label>
                <select name="bulan" id="filter-bulan" class="form-control <?php $__errorArgs = ['bulan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                    <option value="" disabled <?php echo e($selectedMonth === null ? 'selected' : ''); ?>>Pilih bulan</option>
                    <?php $__currentLoopData = $monthOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e($selectedMonth !== null && (int) $selectedMonth === (int) $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['bulan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="form-group mr-2 mb-2">
                <label for="filter-search" class="mr-2">Cari</label>
                <input type="text" name="search" id="filter-search" class="form-control <?php $__errorArgs = ['search'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e($searchTerm ?? ''); ?>" placeholder="Nama atau NIP">
                <?php $__errorArgs = ['search'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="form-group mr-2 mb-2">
                <label for="filter-per-page" class="mr-2">Per halaman</label>
                <select name="per_page" id="filter-per-page" class="form-control">
                    <?php $__currentLoopData = $perPageOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($option); ?>" <?php echo e((int) $perPage === (int) $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <button type="submit" class="btn btn-outline-secondary mb-2"><i class="fas fa-filter"></i> Terapkan</button>
        </form>
    </div>
</div>
<?php if($errors->has('file')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php $__currentLoopData = $errors->get('file'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div><?php echo e($message); ?></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if(session('status')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo e(session('status')); ?>

        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if($filtersReady && $tppsPaginator): ?>
<div class="card">

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <?php if($canManageTpp): ?>
                            <th class="text-center" style="width: 60px;">
                                <input type="checkbox" id="select-all-tpps" aria-label="Pilih semua Data TPP">
                            </th>
                        <?php endif; ?>
                        <th>Pegawai</th>
                        <th>Periode</th>
                        <?php $__currentLoopData = $allowanceFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th><?php echo e($label); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php $__currentLoopData = $deductionFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th><?php echo e($label); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <th>Jumlah TPP</th>
                        <th>Jumlah Potongan</th>
                        <th>Jumlah Ditransfer</th>
                        <?php if($canManageTpp): ?>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if($tppTotal > 0): ?>
                        <tr class="font-weight-bold bg-light">
                            <?php if($canManageTpp): ?>
                                <td class="text-center align-middle">-</td>
                            <?php endif; ?>
                            <td>Total (<?php echo e(number_format($tppTotal, 0, ',', '.')); ?> data)</td>
                            <td><?php echo e($selectedMonth !== null && $selectedYear !== null ? (($monthOptions[$selectedMonth] ?? $selectedMonth) . '/' . $selectedYear) : '-'); ?></td>
                            <?php $__currentLoopData = $allowanceFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td><?php echo e($formatCurrency((float) ($monetaryTotals[$field] ?? 0))); ?></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $deductionFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td><?php echo e($formatCurrency((float) ($monetaryTotals[$field] ?? 0))); ?></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <td><?php echo e($formatCurrency((float) ($summaryTotals['allowance'] ?? 0))); ?></td>
                            <td><?php echo e($formatCurrency((float) ($summaryTotals['deduction'] ?? 0))); ?></td>
                            <td><?php echo e($formatCurrency((float) ($summaryTotals['transfer'] ?? 0))); ?></td>
                            <?php if($canManageTpp): ?>
                                <td class="text-center align-middle">-</td>
                            <?php endif; ?>
                        </tr>
                    <?php endif; ?>
                    <?php $__empty_1 = true; $__currentLoopData = $tpps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tpp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <?php if($canManageTpp): ?>
                                <td class="text-center align-middle">
                                    <input type="checkbox" name="ids[]" value="<?php echo e($tpp->id); ?>" class="tpp-select-checkbox" form="tpp-bulk-delete-form">
                                </td>
                            <?php endif; ?>
                            <?php
                                $totalAllowance = 0;
                                foreach ($allowanceFields as $field => $label) {
                                    $totalAllowance += (float) $tpp->$field;
                                }
                                $totalDeduction = 0;
                                foreach ($deductionFields as $field => $label) {
                                    $totalDeduction += (float) $tpp->$field;
                                }
                                $transfer = $totalAllowance - $totalDeduction;
                            ?>
                            <td>
                                <div class="font-weight-bold"><?php echo e(optional($tpp->pegawai)->nama_lengkap); ?></div>
                                <div class="text-muted small"><?php echo e(optional($tpp->pegawai)->nip ?: '-'); ?></div>
                            </td>
                            <td><?php echo e($monthOptions[$tpp->bulan] ?? $tpp->bulan); ?>/<?php echo e($tpp->tahun); ?></td>
                            <?php $__currentLoopData = $allowanceFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td><?php echo e($formatCurrency((float) $tpp->$field)); ?></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $deductionFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td><?php echo e($formatCurrency((float) $tpp->$field)); ?></td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <td><?php echo e($formatCurrency($totalAllowance)); ?></td>
                            <td><?php echo e($formatCurrency($totalDeduction)); ?></td>
                            <td><?php echo e($formatCurrency($transfer)); ?></td>
                            <?php if($canManageTpp): ?>
                                <td class="text-center">
                                    <a href="<?php echo e(route('tpps.edit', ['tpp' => $tpp, 'type' => $selectedType])); ?>" class="btn btn-sm btn-warning mb-1"><i class="fas fa-edit"></i></a>
                                    <form action="<?php echo e(route('tpps.destroy', ['tpp' => $tpp, 'type' => $selectedType])); ?>" method="POST" class="d-inline" onsubmit="return confirm('Hapus data TPP ini?');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <input type="hidden" name="type" value="<?php echo e($selectedType); ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="<?php echo e($columnCount); ?>" class="text-center text-muted py-4">Belum ada Data TPP untuk kriteria ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if($tpps->hasPages()): ?>
        <div class="card-footer bg-white">
            <?php echo e($tpps->appends(['type' => $selectedType, 'tahun' => $selectedYear, 'bulan' => $selectedMonth, 'per_page' => $perPage === 25 ? null : $perPage, 'search' => $searchTerm])->onEachSide(1)->links('pagination::bootstrap-4')); ?>

        </div>
    <?php endif; ?>
</div>
<?php elseif(! $filtersReady): ?>
    <div class="alert alert-info">Pilih tahun dan bulan untuk menampilkan Data TPP.</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const importInput = document.getElementById('tpp-import-file');
        if (importInput) {
            importInput.addEventListener('change', function () {
                const label = this.nextElementSibling;
                if (label && this.files.length > 0) {
                    label.textContent = this.files[0].name;
                }
            });
        }

        const bulkForm = document.getElementById('tpp-bulk-delete-form');
        const bulkSelectedButton = document.getElementById('tpp-bulk-delete-button');
        const bulkAllButton = document.getElementById('tpp-bulk-delete-all-button');

        if (!bulkForm || !bulkSelectedButton) {
            return;
        }

        const selectAll = document.getElementById('select-all-tpps');
        const itemCheckboxes = Array.from(document.querySelectorAll('.tpp-select-checkbox'));

        const updateButtonState = () => {
            const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
            bulkSelectedButton.disabled = checkedCount === 0;

            if (selectAll) {
                selectAll.checked = checkedCount > 0 && checkedCount === itemCheckboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < itemCheckboxes.length;
            }
        };

        if (selectAll) {
            selectAll.addEventListener('change', (event) => {
                itemCheckboxes.forEach((checkbox) => {
                    checkbox.checked = event.target.checked;
                });
                updateButtonState();
            });
        }

        itemCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', updateButtonState);
        });

        bulkForm.addEventListener('submit', (event) => {
            const submitter = event.submitter;
            const isDeleteAll = submitter && submitter.id === 'tpp-bulk-delete-all-button';

            if (isDeleteAll) {
                if (!confirm('Hapus semua data TPP pada periode ini?')) {
                    event.preventDefault();
                }

                return;
            }

            const checkedCount = itemCheckboxes.filter((checkbox) => checkbox.checked).length;
            if (checkedCount === 0 || !confirm(`Hapus ${checkedCount} data TPP terpilih?`)) {
                event.preventDefault();
            }
        });

        updateButtonState();
    });
</script>
<?php $__env->stopPush(); ?>


















<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\si-ujank\resources\views/tpps/index.blade.php ENDPATH**/ ?>
