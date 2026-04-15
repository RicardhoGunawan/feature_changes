import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.querySelector('#holidayTableBody');
    const holidayForm = document.querySelector('#addHolidayForm');
    const holidayIdInput = document.getElementById('holidayId');
    const modalTitle = document.getElementById('holidayModalTitle');
    
    const modalEl = document.getElementById('addHolidayModal');
    if (!modalEl) return;
    
    const bsModal = new bootstrap.Modal(modalEl);
    let allHolidays = [];

    async function fetchHolidays() {
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        
        try {
            const res = await api.request('/admin/holidays');
            if (res.success) {
                allHolidays = res.data;
                renderHolidays(allHolidays);
            }
        } catch (e) {
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-5 text-danger">${e.message}</td></tr>`;
        }
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function renderHolidays(holidays) {
        if (holidays.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-5">Belum ada data hari libur.</td></tr>`;
            return;
        }

        tableBody.innerHTML = holidays.map(h => `
            <tr>
                <td class="px-4 fw-medium text-dark">${formatDate(h.date)}</td>
                <td class="fw-bold">${h.name}</td>
                <td>
                    <span class="badge ${h.type === 'national' ? 'bg-danger-subtle text-danger' : 'bg-info-subtle text-info'}">
                        ${h.type === 'national' ? 'Nasional' : 'Perusahaan'}
                    </span>
                </td>
                <td class="text-end px-4">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-light btn-sm btn-icon edit-btn" data-id="${h.id}">
                            <i class="ti ti-edit text-primary"></i>
                        </button>
                        <button class="btn btn-light btn-sm btn-icon delete-btn" data-id="${h.id}">
                            <i class="ti ti-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Edit
        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const h = allHolidays.find(item => item.id == btn.dataset.id);
                if (h) {
                    holidayIdInput.value = h.id;
                    modalTitle.innerText = 'Ubah Hari Libur';
                    holidayForm.querySelector('[name="date"]').value = h.date;
                    holidayForm.querySelector('[name="name"]').value = h.name;
                    holidayForm.querySelector('[name="type"]').value = h.type;
                    bsModal.show();
                }
            });
        });

        // Delete
        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const confirmed = await api.confirm({
                    title: 'Hapus Hari Libur?',
                    message: 'Tanggal ini akan kembali dianggap sebagai hari kerja biasa untuk perhitungan cuti.',
                    type: 'danger'
                });

                if (confirmed) {
                    try {
                        const res = await api.request('/admin/holidays', {
                            method: 'DELETE',
                            body: JSON.stringify({ id: btn.dataset.id })
                        });
                        if (res.success) {
                            api.showToast('Hari libur berhasil dihapus');
                            fetchHolidays();
                        }
                    } catch (e) {
                        api.showToast(e.message, 'danger');
                    }
                }
            });
        });
    }

    holidayForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(holidayForm).entries());
        
        try {
            const res = await api.request('/admin/holidays', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            if (res.success) {
                api.showToast(data.id ? 'Hari libur diperbarui' : 'Hari libur ditambahkan');
                bsModal.hide();
                fetchHolidays();
            }
        } catch (e) {
            api.showToast(e.message, 'danger');
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        holidayForm.reset();
        holidayIdInput.value = '';
        modalTitle.innerText = 'Tambah Hari Libur';
    });

    fetchHolidays();
});
