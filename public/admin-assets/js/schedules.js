import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.getElementById('scheduleTableBody');
    const scheduleForm = document.getElementById('scheduleForm');
    const scheduleIdInput = document.getElementById('scheduleId');
    const modalTitle = document.getElementById('scheduleModalTitle');
    
    if (!tableBody) return;

    const modalEl = document.getElementById('addScheduleModal');
    if (!modalEl) return;
    
    const bsModal = new bootstrap.Modal(modalEl);

    let scheduleData = [];

    async function loadSchedules() {
        try {
            const response = await api.getSchedules();
            if (response.success) {
                scheduleData = response.data;
                renderSchedules(scheduleData);
            }
        } catch (error) {
            api.showToast('Gagal memuat data jadwal', 'danger');
        }
    }

    function renderSchedules(schedules) {
        if (!schedules || schedules.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted">Belum ada data jadwal.</td></tr>`;
            return;
        }

        tableBody.innerHTML = schedules.map(sch => `
            <tr>
                <td class="px-4 py-3 fw-semibold text-dark">${sch.shift_name}</td>
                <td class="px-4 py-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-medium px-3 py-2">
                        <i class="ti ti-login me-1"></i>${sch.start_time}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary fw-medium px-3 py-2">
                        <i class="ti ti-logout me-1"></i>${sch.end_time}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="text-muted small">${sch.late_tolerance_minutes} Menit</span>
                </td>
                <td class="px-4 py-3 text-end">
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm btn-icon" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item small py-2 edit-btn" href="#" data-id="${sch.id}"><i class="ti ti-edit me-2 text-primary"></i>Ubah</a></li>
                            <li><a class="dropdown-item small py-2 text-danger delete-btn" href="#" data-id="${sch.id}"><i class="ti ti-trash me-2"></i>Hapus</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('');

        // Edit
        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.dataset.id;
                const sch = scheduleData.find(s => s.id == id);
                if (sch) {
                    scheduleIdInput.value = sch.id;
                    modalTitle.innerText = 'Ubah Jadwal';
                    scheduleForm.querySelector('[name="shift_name"]').value = sch.shift_name;
                    scheduleForm.querySelector('[name="start_time"]').value = sch.start_time;
                    scheduleForm.querySelector('[name="end_time"]').value = sch.end_time;
                    scheduleForm.querySelector('[name="late_tolerance_minutes"]').value = sch.late_tolerance_minutes;
                    bsModal.show();
                }
            });
        });

        // Delete
        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const confirmed = await api.confirm({
                    title: 'Hapus Jadwal?',
                    message: 'Jadwal kerja ini akan dihapus. Perubahan mungkin berdampak pada kalkulasi absensi.',
                    confirmText: 'Ya, Hapus',
                    type: 'danger'
                });

                if (confirmed) {
                    try {
                        const res = await api.deleteSchedule(btn.dataset.id);
                        if (res.success) {
                            api.notify('Jadwal dihapus');
                            loadSchedules();
                        }
                    } catch (error) {
                        api.notify(error.message, 'danger');
                    }
                }
            });
        });
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
        scheduleForm.reset();
        scheduleIdInput.value = '';
        modalTitle.innerText = 'Tambah Jadwal Baru';
    });

    if (scheduleForm) {
        scheduleForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(scheduleForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await api.saveSchedule(data);
                if (response.success) {
                    api.showToast('Jadwal berhasil disimpan');
                    bsModal.hide();
                    loadSchedules();
                }
            } catch (error) {
                api.showToast(error.message, 'danger');
            }
        });
    }

    loadSchedules();
});
