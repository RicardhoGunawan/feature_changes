@extends('layouts.admin')

@section('title', 'Jadwal Kerja')

@push('styles')
<style>
    .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border-radius: 8px; }
    .table-responsive { min-height: 120px; overflow: visible !important; }
    .dropdown-menu { z-index: 1060; }
</style>
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-md-6">
        <h1 class="fs-3 fw-bold mb-1">Jadwal Kerja</h1>
        <p class="text-secondary small">Atur jam operasional, jam masuk, dan jam pulang karyawan.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="ti ti-plus me-1"></i> Tambah Jadwal
        </button>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 border-0">Nama Jadwal</th>
                                <th class="px-4 py-3 border-0">Jam Masuk</th>
                                <th class="px-4 py-3 border-0">Jam Pulang</th>
                                <th class="px-4 py-3 border-0">Toleransi</th>
                                <th class="px-4 py-3 border-0 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="scheduleTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted small">Memuat data jadwal...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Jadwal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 px-4 pt-4 shadow-none">
                <h5 class="modal-title fw-bold" id="scheduleModalTitle">Tambah Jadwal Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleForm">
                <input type="hidden" name="id" id="scheduleId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Jadwal</label>
                        <input type="text" class="form-control rounded-2" name="shift_name" placeholder="Contoh: Shift Pagi" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Masuk</label>
                            <input type="time" class="form-control rounded-2" name="start_time" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Pulang</label>
                            <input type="time" class="form-control rounded-2" name="end_time" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Toleransi Terlambat (Menit)</label>
                        <input type="number" class="form-control rounded-2" name="late_tolerance_minutes" value="15" required>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 shadow-none">
                    <button type="button" class="btn btn-light rounded-pill px-4 text-decoration-none" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-assets/js/schedules.js') }}" type="module"></script>
@endpush
