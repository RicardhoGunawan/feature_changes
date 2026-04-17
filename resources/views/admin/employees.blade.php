@extends('layouts.admin')

@section('title', 'Daftar Karyawan')

@push('styles')
<!-- TomSelect removed -->
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Daftar Karyawan</h1>
        <p class="text-secondary small">Kelola data karyawan Anda di sini.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="ti ti-plus me-1"></i> Tambah Karyawan
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rounded-3">
    <div class="card-body p-3">
        <div class="input-group border-0">
            <span class="input-group-text bg-white border-end-0">
                <i class="ti ti-search text-muted"></i>
            </span>
            <input type="text" id="employeeSearchInput" class="form-control border-start-0" placeholder="Cari nama, ID, atau departemen...">
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Karyawan</th>
                        <th>ID / Username</th>
                        <th>Posisi / Dept</th>
                        <th>Bergabung</th>
                        <th>Kuota Cuti</th>
                        <th>Status</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="employeeTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Karyawan -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="employeeModalTitle">Tambah Karyawan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addEmployeeForm" class="row g-3">
                    <input type="hidden" name="id" id="employeeId">
                    
                    <div class="col-md-12">
                        <label class="form-label small fw-bold text-primary text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Informasi Dasar</label>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="ID login" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="******" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" placeholder="Nama Karyawan" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@perusahaan.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Telepon</label>
                        <input type="text" name="phone" class="form-control" placeholder="08xxxx">
                    </div>

                    <div class="col-md-12 mt-4">
                        <label class="form-label small fw-bold text-primary text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Data Kepegawaian & HR Engine</label>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Jabatan (Struktural)</label>
                        <select name="position_id" id="positionSelect" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Role Sistem</label>
                        <select name="role" class="form-select" required>
                            <option value="employee">Employee / Staff</option>
                            <option value="administrator">Administrator</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tipe Karyawan</label>
                        <select name="employee_type" class="form-select" required>
                            <option value="permanent">Tetap (Permanent)</option>
                            <option value="contract">Kontrak (Contract)</option>
                            <option value="probation">Percobaan (Probation)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Tanggal Bergabung</label>
                        <input type="date" name="join_date" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Kuota Cuti Awal</label>
                        <input type="number" name="annual_leave_quota" class="form-control" placeholder="Default: 12" value="12">
                    </div>
                    
                    <div class="col-md-12 mt-4">
                        <label class="form-label small fw-bold text-primary text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Operasional</label>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Shift Kerja</label>
                        <select name="shift_id" id="shiftSelect" class="form-select" required></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Lokasi Kantor</label>
                        <select name="location_id" id="locationSelect" class="form-select" required></select>
                    </div>

                    <div class="col-md-12 text-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Simpan Karyawan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Penyesuaian Kuota -->
<div class="modal fade" id="adjustQuotaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom shadow-none">
                <h5 class="modal-title fw-bold">Penyesuaian Jatah Cuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="adjustQuotaForm">
                    <input type="hidden" name="user_id" id="adjust_user_id">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jenis Jatah</label>
                        <select name="type" class="form-select" required>
                            <option value="annual">Cuti Tahunan (Annual Leave)</option>
                            <option value="sick">Cuti Sakit (Sick Leave)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Jumlah Perubahan (Hari)</label>
                        <input type="number" name="amount" class="form-control" placeholder="Contoh: 2 atau -1" required>
                        <div class="form-text x-small text-muted">Contoh: Isi <strong>2</strong> untuk menambah, atau <strong>-1</strong> untuk mengurangi.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Alasan Penyesuaian</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Contoh: Koreksi data masa lalu / Bonus performa" required></textarea>
                    </div>
                    
                    <div class="text-end border-top pt-3 mt-4">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning px-4 fw-bold">Update Kuota</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-assets/js/employees.js') }}" type="module"></script>
@endpush
