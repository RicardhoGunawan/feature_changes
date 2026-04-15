@extends('layouts.admin')

@section('title', 'Manajemen Hari Libur')

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Manajemen Hari Libur</h1>
        <p class="text-secondary small">Atur hari libur nasional dan perusahaan untuk perhitungan cuti.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
            <i class="ti ti-plus me-1"></i> Tambah Hari Libur
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Tanggal</th>
                        <th>Nama Hari Libur</th>
                        <th>Tipe</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="holidayTableBody">
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Hari Libur -->
<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="holidayModalTitle">Tambah Hari Libur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addHolidayForm" class="row g-3">
                    <input type="hidden" name="id" id="holidayId">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Tanggal</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Nama Hari Libur</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Idul Fitri" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Tipe Libur</label>
                        <select name="type" class="form-select" required>
                            <option value="national">Libur Nasional</option>
                            <option value="company">Libur Perusahaan (Cuti Bersama)</option>
                        </select>
                    </div>
                    <div class="col-md-12 text-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-light px-4 me-2" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-assets/js/holidays.js') }}" type="module"></script>
@endpush
