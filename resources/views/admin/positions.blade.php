@extends('layouts.admin')

@section('title', 'Struktur Jabatan')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Struktur Jabatan</h1>
        <p class="text-secondary small">Kelola hirarki organisasi dan alur approval berdasarkan jabatan.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addPositionModal">
            <i class="ti ti-plus me-1"></i> Tambah Jabatan
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Nama Jabatan</th>
                        <th>Departemen</th>
                        <th>Atasan (Hirarki)</th>
                        <th>Level</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="positionTableBody">
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Jabatan -->
<div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="positionModalTitle">Tambah Jabatan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addPositionForm" class="row g-3">
                    <input type="hidden" name="id" id="positionId">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Nama Jabatan</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Manager IT" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Atasan Jabatan (Superior)</label>
                        <select id="parentSelect" name="parent_id" class="form-select">
                            <option value="">Pilih Atasan...</option>
                        </select>
                        <div class="form-text xsmall mt-1 text-muted">Jabatan di atas posisi ini yang akan menyetujui izin/lembur.</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Departemen</label>
                        <input type="text" name="department" class="form-control" placeholder="Contoh: Engineering">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Tingkatan (Level)</label>
                        <input type="number" name="level" class="form-control" value="1" min="1" required>
                        <div class="form-text xsmall mt-1 text-muted">Makin besar angka, makin tinggi jabatan.</div>
                    </div>
                    <div class="col-md-12 text-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan Jabatan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ asset('admin-assets/js/positions.js') }}" type="module"></script>
@endpush
