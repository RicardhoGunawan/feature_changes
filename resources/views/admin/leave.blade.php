@extends('layouts.admin')

@section('title', 'Izin & Cuti')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>
    .dropdown-menu { z-index: 1060; }
</style>
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Persetujuan Izin & Cuti</h1>
        <p class="text-secondary small">Review dan kelola pengajuan izin karyawan.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <button type="button" id="exportLeaveBtn" class="btn btn-success shadow-sm">
            <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-3">
    <div class="card-body p-4">
        <form id="leaveFilterForm" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Periode Bulan</label>
                <input type="month" name="month" id="monthInput" class="form-control" value="{{ date('Y-m') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Karyawan</label>
                <select name="employee_id" id="employeeFilterSelect" class="form-select">
                    <option value=""></option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="all">Semua Status</option>
                    <option value="pending" selected>Menunggu Review</option>
                    <option value="approved">Disetujui</option>
                    <option value="rejected">Ditolak</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Tipe</label>
                <select name="type" class="form-select">
                    <option value="all">Semua Tipe</option>
                    <option value="izin">Izin</option>
                    <option value="sakit">Sakit</option>
                    <option value="cuti">Cuti</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                    <i class="ti ti-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Karyawan</th>
                        <th>Kode</th>
                        <th>Sisa Cuti</th>
                        <th>Tipe / Tanggal</th>
                        <th>Durasi</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="leaveTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="spinner-border text-primary spinner-border-sm"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Izin -->
<div class="modal fade" id="detailLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom bg-light shadow-none">
                <h5 class="modal-title fw-bold">Detail Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="detailContent">
                <!-- Content will be loaded via JS -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Review Izin -->
<div class="modal fade" id="processLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom shadow-none">
                <h5 class="modal-title fw-bold" id="processTitle">Proses Pengajuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="processLeaveForm">
                    <input type="hidden" name="id" id="leaveIdInput">
                    <input type="hidden" name="action" id="leaveActionInput">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan Review (Opsional)</label>
                        <textarea name="review_note" id="reviewNoteInput" class="form-control" rows="3" placeholder="Alasan disetujui atau ditolak..."></textarea>
                    </div>
                    
                    <div class="text-end border-top pt-3 mt-4">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" id="submitProcessBtn" class="btn btn-primary px-4">Konfirmasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script src="{{ asset('admin-assets/js/leave.js') }}" type="module"></script>
@endpush
