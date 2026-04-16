@extends('layouts.admin')

@section('title', 'Laporan Absensi')

@push('styles')
<!-- Styles simplified: TomSelect removed -->
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Laporan Absensi</h1>
        <p class="text-secondary small">Lihat dan filter data kehadiran karyawan.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <button type="button" class="btn btn-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal">
            <i class="ti ti-plus me-1"></i> Absen Manual
        </button>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-4 rounded-3">
    <div class="card-body p-4">
        <form id="attendanceFilterForm" class="row g-3">
            <!-- Row 1: Period & Dept -->
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 10px;">Tanggal Mulai</label>
                <input type="date" name="start_date" id="startDateInput" class="form-control shadow-none" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 10px;">Tanggal Selesai</label>
                <input type="date" name="end_date" id="endDateInput" class="form-control shadow-none" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 10px;">Departemen</label>
                <select name="department" id="departmentFilterSelect" class="form-select shadow-none">
                    <option value="">Semua Dept</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 10px;">Status</label>
                <select name="status" class="form-select shadow-none">
                    <option value="">Semua Status</option>
                    <option value="present">Tepat Waktu</option>
                    <option value="late">Terlambat</option>
                    <option value="incomplete">Tidak Selesai</option>
                </select>
            </div>

            <!-- Row 2: Employee & Buttons -->
            <div class="col-md-6">
                <label class="form-label small fw-bold text-uppercase text-secondary" style="font-size: 10px;">Cari Karyawan</label>
                <select name="employee_id" id="employeeFilterSelect" class="form-select shadow-none">
                    <option value="">Semua Karyawan</option>
                </select>
            </div>
            <div class="col-md-3 pt-4">
                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm mt-1">
                    <i class="ti ti-filter me-1"></i> Terapkan Filter
                </button>
            </div>
            <div class="col-md-3 pt-4">
                <button type="button" id="exportExcelBtn" class="btn btn-success w-100 fw-bold shadow-sm mt-1">
                    <i class="ti ti-file-spreadsheet me-1"></i> Export Excel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4" id="attendanceSummary">
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-2">
            <small class="text-secondary d-block mb-1 small text-uppercase fw-bold" style="font-size: 10px;">Total Record</small>
            <h4 class="mb-0" id="summaryTotal">0</h4>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-2">
            <small class="text-secondary d-block mb-1 small text-uppercase fw-bold" style="font-size: 10px;">Tepat Waktu</small>
            <h4 class="mb-0 text-success" id="summaryPresent">0</h4>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-2">
            <small class="text-secondary d-block mb-1 small text-uppercase fw-bold" style="font-size: 10px;">Terlambat</small>
            <h4 class="mb-0 text-danger" id="summaryLate">0</h4>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm p-3 bg-light rounded-2">
            <small class="text-secondary d-block mb-1 small text-uppercase fw-bold" style="font-size: 10px;">Total Jam Kerja</small>
            <h4 class="mb-0 text-primary" id="summaryHours">0h</h4>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Tanggal</th>
                        <th>Karyawan</th>
                        <th>Kode</th>
                        <th>Shift</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Durasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="attendanceTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <p class="text-muted">Gunakan filter untuk menampilkan data.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manual Attendance Modal -->
<div class="modal modal-blur fade" id="manualAttendanceModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0 shadow-none">
                <h5 class="modal-title fw-bold">Input Absen Manual</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="manualAttendanceForm">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Karyawan</label>
                        <select id="manualEmployeeSelect" name="employee_id" class="form-select" required>
                            <option value=""></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tanggal</label>
                        <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Masuk</label>
                            <input type="time" name="check_in" class="form-control" value="08:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Jam Pulang</label>
                            <input type="time" name="check_out" class="form-control" value="17:00" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Status Kehadiran</label>
                        <select name="status" class="form-select">
                            <option value="present">Hadir (Tepat Waktu)</option>
                            <option value="late">Terlambat</option>
                            <option value="incomplete">Incomplete</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4 shadow-none">
                    <button type="button" class="btn btn-link link-secondary me-auto text-decoration-none" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Simpan Absensi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-assets/js/attendance.js') }}" type="module"></script>
@endpush
