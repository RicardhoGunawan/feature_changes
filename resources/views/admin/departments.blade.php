@extends('layouts.admin')

@section('title', 'Master Data Departemen')

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Master Departemen</h1>
        <p class="text-secondary small">Kelola daftar departemen perusahaan Anda.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#departmentModal">
            <i class="ti ti-plus me-1"></i> Tambah Departemen
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4">Nama Departemen</th>
                        <th>Kode</th>
                        <th>Karyawan</th>
                        <th>Deskripsi</th>
                        <th class="text-end px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="departmentTableBody">
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

<!-- Modal Departemen -->
<div class="modal fade" id="departmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="departmentModalTitle">Tambah Departemen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form id="departmentForm">
                    <input type="hidden" name="id" id="departmentId">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Departemen</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: IT Support" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Kode Departemen (Opsional)</label>
                        <input type="text" name="code" class="form-control" placeholder="Contoh: IT">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Penjelasan singkat tentang departemen..."></textarea>
                    </div>
                    <div class="text-end mt-4 pt-3 border-top">
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
<script src="{{ asset('admin-assets/js/departments.js') }}" type="module"></script>
@endpush
