import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.getElementById('locationTableBody');
    const locationForm = document.getElementById('locationForm');
    const locationIdInput = document.getElementById('locationId');
    const modalTitle = document.getElementById('modalTitle');
    const latInput = document.getElementById('latInput');
    const lngInput = document.getElementById('lngInput');
    const radiusInput = document.getElementById('radiusInput');
    
    if (!tableBody) return;

    let map, marker, radiusCircle;
    let locationData = []; 
    let pendingLocation = null; // Store location to set after map init

    const defaultLat = -6.200000;
    const defaultLng = 106.816666;

    function initMap(lat = defaultLat, lng = defaultLng) {
        if (map) return;
        
        const mapEl = document.getElementById('map');
        if (!mapEl) return;

        map = L.map('map').setView([lat, lng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        radiusCircle = L.circle([lat, lng], {
            radius: parseInt(radiusInput.value) || 100,
            color: '#E66239',
            fillColor: '#E66239',
            fillOpacity: 0.2
        }).addTo(map);

        marker.on('dragend', updateInputsFromMarker);
        map.on('click', (e) => {
            marker.setLatLng(e.latlng);
            updateInputsFromMarker();
        });
        updateInputsFromMarker();
    }

    function updateInputsFromMarker() {
        if (!marker) return;
        const pos = marker.getLatLng();
        latInput.value = pos.lat.toFixed(6);
        lngInput.value = pos.lng.toFixed(6);
        if (radiusCircle) radiusCircle.setLatLng(pos);
    }

    function updateMapPosition(lat, lng, radius = 100) {
        const pos = [parseFloat(lat), parseFloat(lng)];
        if (map && marker) {
            map.setView(pos, 16);
            marker.setLatLng(pos);
            if (radiusCircle) {
                radiusCircle.setLatLng(pos);
                radiusCircle.setRadius(radius);
            }
            updateInputsFromMarker();
        } else {
            pendingLocation = { lat, lng, radius };
        }
    }

    const modalEl = document.getElementById('addLocationModal');
    if (modalEl) {
        const bsModal = new bootstrap.Modal(modalEl);

        modalEl.addEventListener('shown.bs.modal', () => {
            if (!map) {
                initMap();
            }
            if (map) {
                map.invalidateSize();
                // If there's a pending location (from Edit or Geolocation), apply it now
                if (pendingLocation) {
                    updateMapPosition(pendingLocation.lat, pendingLocation.lng, pendingLocation.radius);
                    pendingLocation = null;
                }
            }
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            locationForm.reset();
            locationIdInput.value = '';
            modalTitle.innerText = 'Tambah Lokasi Baru';
            pendingLocation = null;
        });

        // Detect Current Location on Add
        const addBtn = document.querySelector('[data-bs-target="#addLocationModal"]');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition((position) => {
                        pendingLocation = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                            radius: 100
                        };
                        if (map) {
                            updateMapPosition(pendingLocation.lat, pendingLocation.lng, 100);
                            pendingLocation = null;
                        }
                    }, null, { enableHighAccuracy: true });
                }
            });
        }
    }

    if (radiusInput) {
        radiusInput.addEventListener('input', () => {
            if (radiusCircle) radiusCircle.setRadius(parseInt(radiusInput.value) || 0);
        });
    }

    async function loadLocations() {
        try {
            const response = await api.getLocations();
            if (response.success) {
                locationData = response.data;
                renderLocations(locationData);
            }
        } catch (error) {
            api.showToast('Gagal memuat data lokasi', 'danger');
        }
    }

    function renderLocations(locations) {
        if (!locations || locations.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data lokasi.</td></tr>`;
            return;
        }

        tableBody.innerHTML = locations.map(loc => `
            <tr>
                <td class="px-4 py-3 fw-semibold text-dark">${loc.location_name}</td>
                <td class="px-4 py-3 small text-secondary">${loc.address || '-'}</td>
                <td class="px-4 py-3 small font-monospace">${loc.latitude}, ${loc.longitude}</td>
                <td class="px-4 py-3 text-primary fw-medium">${loc.radius}m</td>
                <td class="px-4 py-3">
                    <span class="badge ${loc.is_active == 1 || loc.is_active == true ? 'bg-success' : 'bg-secondary'} bg-opacity-10 ${loc.is_active == 1 || loc.is_active == true ? 'text-success' : 'text-secondary'} rounded-pill px-3 py-2">
                        ${loc.is_active == 1 || loc.is_active == true ? 'Aktif' : 'Non-Aktif'}
                    </span>
                </td>
                <td class="px-4 py-3 text-end">
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm btn-icon" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item small py-2 edit-btn" href="#" data-id="${loc.id}"><i class="ti ti-edit me-2 text-primary"></i>Ubah</a></li>
                            <li><a class="dropdown-item small py-2 text-danger delete-btn" href="#" data-id="${loc.id}"><i class="ti ti-trash me-2"></i>Hapus</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');

        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                const loc = locationData.find(l => l.id == id);
                if (loc) {
                    locationIdInput.value = loc.id;
                    modalTitle.innerText = 'Ubah Lokasi';
                    locationForm.querySelector('[name="location_name"]').value = loc.location_name;
                    locationForm.querySelector('[name="address"]').value = loc.address || '';
                    locationForm.querySelector('[name="is_active"]').value = loc.is_active ? '1' : '0';
                    latInput.value = loc.latitude;
                    lngInput.value = loc.longitude;
                    radiusInput.value = loc.radius;
                    
                    // Set pending location for Edit
                    pendingLocation = { lat: loc.latitude, lng: loc.longitude, radius: loc.radius };
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.show();
                }
            });
        });

        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                
                const confirmed = await api.confirm({
                    title: 'Hapus Lokasi?',
                    message: 'Data lokasi ini akan dihapus permanen dan tidak bisa dikembalikan.',
                    confirmText: 'Ya, Hapus',
                    type: 'danger'
                });

                if (confirmed) {
                    try {
                        const res = await api.deleteLocation(id);
                        if (res.success) {
                            api.notify('Lokasi berhasil dihapus');
                            loadLocations();
                        }
                    } catch (error) {
                        api.notify(error.message, 'danger');
                    }
                }
            });
        });
    }

    if (locationForm) {
        locationForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(locationForm);
            const data = Object.fromEntries(formData.entries());
            try {
                const response = await api.saveLocation(data);
                if (response.success) {
                    api.showToast('Lokasi berhasil disimpan');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    loadLocations();
                }
            } catch (error) {
                api.showToast(error.message, 'danger');
            }
        });
    }

    loadLocations();
});
