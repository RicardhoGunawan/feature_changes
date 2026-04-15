@extends('layouts.admin')

@section('title', 'Role & Access Control')

@push('styles')
<style>
    .fs-13 { font-size: 13px; }
    .last-border-none:last-child { border-bottom: none !important; }
    .cursor-pointer { cursor: pointer; }
    .hover-bg-light:hover { background-color: #f8f9fa; }
    .transition-all { transition: all 0.2s ease; }
    .custom-switch-sm { width: 2.2em !important; height: 1.1em !important; cursor: pointer; }
    .permission-list { max-height: 350px; overflow-y: auto; scrollbar-width: thin; padding-right: 5px; }
    .permission-list::-webkit-scrollbar { width: 4px; }
    .permission-list::-webkit-scrollbar-thumb { background: #eee; border-radius: 10px; }
    .card { transition: border-color 0.2s; }
    .card:hover { border-color: var(--bs-primary) !important; }
</style>
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-sm-6">
        <h1 class="fs-3 fw-bold mb-1">Role & Access Control</h1>
        <p class="text-secondary small">Review dan kelola tingkat perizinan akses pengguna.</p>
    </div>
    <div class="col-sm-6 text-sm-end">
        <div class="bg-primary bg-opacity-10 text-primary d-inline-block px-3 py-2 rounded-3 border border-primary border-opacity-25 small fw-bold">
            <i class="ti ti-shield-check me-1"></i> Admin Area
        </div>
    </div>
</div>

<div class="row g-4" id="rolesContainer">
    <!-- Loading State -->
    <div class="col-12 text-center py-5" id="loadingState">
        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
        <p class="mt-3 text-secondary">Sinkronisasi data...</p>
    </div>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="saveConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-5">
                <div class="mb-4">
                    <i class="ti ti-alert-circle text-warning border border-warning rounded-circle p-3" style="font-size: 50px;"></i>
                </div>
                <h4 class="fw-bold mb-2">Simpan Perubahan?</h4>
                <p class="text-secondary mb-4">Perubahan hak akses akan langsung berdampak pada seluruh pengguna dengan role ini.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light px-4 text-decoration-none" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary px-4" id="confirmSaveBtn">Ya, Simpan</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-assets/js/roles.js') }}" type="module"></script>
@endpush
