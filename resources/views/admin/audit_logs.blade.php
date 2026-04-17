@extends('layouts.admin')

@section('title', 'Riwayat Audit & Transparansi')

@section('content')
<div class="mb-4">
    <h4 class="mb-0">Riwayat Audit</h4>
    <p class="text-secondary small mb-0">Catatan sejarah seluruh aktivitas administratif di sistem.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Waktu</th>
                        <th>Pelaku (Admin)</th>
                        <th>Aksi / Kejadian</th>
                        <th>Target Karyawan</th>
                        <th>Detail Perubahan</th>
                        <th class="pe-4">IP Address</th>
                    </tr>
                </thead>
                <tbody id="auditLogTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">Memuat data log...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white border-top-0 py-3">
        <div id="paginationContainer" class="d-flex justify-content-between align-items-center"></div>
    </div>
</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title">Detail Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div id="logDetailContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module" src="{{ asset('admin-assets/js/audit_logs.js') }}"></script>
@endpush
