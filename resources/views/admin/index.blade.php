@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <h1 class="fs-3 fw-bold mb-1">Dashboard Absensi</h1>
            <p class="text-secondary small">Selamat datang di sistem manajemen kehadiran.</p>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <div class="card p-4 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-2 shadow-sm h-100">
            <div class="d-flex gap-3">
                <div class="icon-shape icon-md bg-primary text-white rounded-2 shadow-sm" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-users fs-4"></i>
                </div>
                <div>
                    <h2 class="mb-2 fs-6 text-secondary fw-semibold">Total Karyawan</h2>
                    <h3 class="fw-bold mb-0" id="totalEmployees">0</h3>
                    <p class="text-primary mb-0 small mt-1">Aktif</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card p-4 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-2 shadow-sm h-100">
            <div class="d-flex gap-3">
                <div class="icon-shape icon-md bg-success text-white rounded-2 shadow-sm" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-check fs-4"></i>
                </div>
                <div>
                    <h2 class="mb-2 fs-6 text-secondary fw-semibold">Hadir Hari Ini</h2>
                    <h3 class="fw-bold mb-0" id="checkedInToday">0</h3>
                    <p class="text-success mb-0 small mt-1" id="attendanceRate">0%</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card p-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-2 shadow-sm h-100">
            <div class="d-flex gap-3">
                <div class="icon-shape icon-md bg-danger text-white rounded-2 shadow-sm" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-clock-pause fs-4"></i>
                </div>
                <div>
                    <h2 class="mb-2 fs-6 text-secondary fw-semibold">Terlambat</h2>
                    <h3 class="fw-bold mb-0" id="lateToday">0</h3>
                    <p class="text-danger mb-0 small mt-1">Hari ini</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="card p-4 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-2 shadow-sm h-100">
            <div class="d-flex gap-3">
                <div class="icon-shape icon-md bg-warning text-white rounded-2 shadow-sm" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                    <i class="ti ti-calendar-event fs-4"></i>
                </div>
                <div>
                    <h2 class="mb-2 fs-6 text-secondary fw-semibold">Izin/Cuti</h2>
                    <h3 class="fw-bold mb-0" id="pendingLeave">0</h3>
                    <p class="text-warning mb-0 small mt-1">Pending SPV</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-white px-4 py-3">
                <h3 class="h5 mb-0 fw-bold">Statistik Kehadiran (7 Hari Terakhir)</h3>
                <span class="badge bg-danger rounded-pill px-3">Real-time</span>
            </div>
            <div class="card-body p-4">
                <div id="salesPurchaseChart"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header d-flex justify-content-between align-items-center bg-white px-4 py-3">
                <h3 class="h5 mb-0 fw-bold">Statistik Bulan Ini</h3>
            </div>
            <div class="card-body p-4 text-center">
                <h3 class="h6 text-secondary mb-4">Tingkat Kehadiran Tepat Waktu</h3>
                <div id="customerChart"></div>
                
                <div class="row text-center mt-4 border-top pt-4">
                    <div class="col-4 border-end">
                        <h3 class="fw-bold mb-1 text-success fs-4" id="monthOnTime">0</h3>
                        <small class="text-muted small">Tepat Waktu</small>
                    </div>
                    <div class="col-4 border-end">
                        <h3 class="fw-bold mb-1 text-danger fs-4" id="monthLate">0</h3>
                        <small class="text-muted small">Terlambat</small>
                    </div>
                    <div class="col-4">
                        <h3 class="fw-bold mb-1 text-primary fs-4" id="monthTotal">0</h3>
                        <small class="text-muted small">Total Hadir</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row g-3">
    <div class="col-lg-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center px-4 py-3">
                <h4 class="mb-0 h5 fw-bold">Aktivitas Absensi Terbaru</h4>
                <a href="{{ url('/admin/attendance') }}" class="btn btn-sm btn-outline-primary px-3 rounded-pill">Lihat Semua</a>
            </div>
            <ul class="list-group list-group-flush border-top" id="recentActivitiesList">
                <li class="list-group-item text-center py-5 border-0">
                    <div class="spinner-border text-primary"></div>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-assets/js/dashboard.js') }}" type="module"></script>
@endpush
