@extends('layouts.admin')

@section('title', 'Atur Lokasi')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border-radius: 8px; }
    .table-responsive { min-height: 120px; overflow: visible !important; }
    .dropdown-menu { z-index: 1060; }
</style>
@endpush

@section('content')
<div class="row align-items-center mb-6 mt-2">
    <div class="col-md-6">
        <h1 class="fs-3 fw-bold mb-1">Pengaturan Lokasi</h1>
        <p class="text-secondary small">Kelola lokasi kantor dan radius absensi karyawan.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addLocationModal">
            <i class="ti ti-plus me-1"></i> Tambah Lokasi
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
                                <th class="px-4 py-3 border-0">Nama Lokasi</th>
                                <th class="px-4 py-3 border-0">Alamat</th>
                                <th class="px-4 py-3 border-0">Koordinat (Lat, Long)</th>
                                <th class="px-4 py-3 border-0">Radius</th>
                                <th class="px-4 py-3 border-0">Status</th>
                                <th class="px-4 py-3 border-0 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="locationTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 text-muted small">Memuat data lokasi...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Lokasi -->
<div class="modal fade" id="addLocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 px-4 pt-4 shadow-none">
                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Lokasi Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="locationForm">
                <input type="hidden" name="id" id="locationId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nama Lokasi</label>
                        <input type="text" class="form-control rounded-2" name="location_name" placeholder="Contoh: Kantor Pusat" required>
                    </div>
                    
                    <!-- Map Container -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Titik di Peta (Klik untuk geser penanda)</label>
                        <div id="map" class="rounded-3 border" style="height: 250px; background: #eee;"></div>
                        <small class="text-muted d-block mt-1">Anda juga dapat menggeser penanda di atas untuk menentukan titik.</small>
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Latitude</label>
                            <input type="text" class="form-control rounded-2" id="latInput" name="latitude" placeholder="-6.2088..." required readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Longitude</label>
                            <input type="text" class="form-control rounded-2" id="lngInput" name="longitude" placeholder="106.8456..." required readonly>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">Radius Absensi (Meter)</label>
                        <div class="input-group">
                            <input type="number" class="form-control rounded-2" id="radiusInput" name="radius" value="100" required>
                            <span class="input-group-text bg-light border-start-0 rounded-end-2 small">Meter</span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">Alamat (Opsional)</label>
                        <textarea class="form-control rounded-2" name="address" rows="2" placeholder="Alamat lengkap..."></textarea>
                    </div>
                    <div class="mt-3">
                        <label class="form-label small fw-bold">Status Lokasi</label>
                        <select class="form-select rounded-2" name="is_active">
                            <option value="1">Aktif (Bisa digunakan absensi)</option>
                            <option value="0">Tidak Aktif (Lokasi dinonaktifkan)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 shadow-none">
                    <button type="button" class="btn btn-light rounded-pill px-4 text-decoration-none" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan Lokasi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('admin-assets/js/locations.js') }}" type="module"></script>
@endpush
