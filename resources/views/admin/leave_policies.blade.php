@extends('layouts.admin')

@section('title', 'Manajemen Kebijakan Cuti')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0">Manajemen Kebijakan Cuti</h4>
        <p class="text-secondary small mb-0">Atur jatah, syarat, dan aturan untuk setiap jenis cuti.</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
        <i class="ti ti-plus me-1"></i> Tambah Jenis Cuti
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Jenis Cuti</th>
                        <th>Kode</th>
                        <th>Jatah Dasar</th>
                        <th>Probasi (Bln)</th>
                        <th>Setengah Hari</th>
                        <th>Wajib Lampiran</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody id="policyTableBody">
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data kebijakan...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Policy Modal -->
<div class="modal fade" id="editPolicyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title">Edit Kebijakan Cuti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPolicyForm">
                <div class="modal-body">
                    <input type="hidden" name="leave_type_id" id="edit_leave_type_id">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Deskripsi Kebijakan</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Jatah Dasar (Hari)</label>
                            <input type="number" class="form-control" name="default_quota" id="edit_default_quota" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Min. Masa Kerja (Bulan)</label>
                            <input type="number" class="form-control" name="min_service_months" id="edit_min_service_months" required>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" name="allow_half_day" id="edit_allow_half_day" value="1">
                                <label class="form-check-label ms-2">Boleh Ambil Setengah Hari</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" name="requires_attachment" id="edit_requires_attachment" value="1">
                                <label class="form-check-label ms-2">Wajib Unggah Lampiran</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('admin-assets/js/leave_policies.js') }}"></script>
@endpush
