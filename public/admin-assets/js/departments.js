import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.querySelector('#departmentTableBody');
    const departmentForm = document.querySelector('#departmentForm');
    const departmentIdInput = document.getElementById('departmentId');
    const modalTitle = document.getElementById('departmentModalTitle');
    
    const modalEl = document.getElementById('departmentModal');
    if (!modalEl) return;
    const bsModal = new bootstrap.Modal(modalEl);

    let allDepartments = [];

    async function loadDepartments() {
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const response = await api.request('/admin/departments');
            if (response.success) {
                allDepartments = response.data;
                renderDepartments(allDepartments);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger">${error.message}</td></tr>`;
        }
    }

    function renderDepartments(items) {
        if (!items || items.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5">Belum ada data departemen.</td></tr>`;
            return;
        }

        tableBody.innerHTML = items.map(item => `
            <tr>
                <td class="px-4 fw-bold text-dark">${item.name}</td>
                <td>
                    <span class="badge bg-light text-dark border">${item.code || '-'}</span>
                </td>
                <td>
                    <span class="badge bg-soft-primary text-primary small fw-bold">${item.users_count} Karyawan</span>
                </td>
                <td>
                    <div class="text-muted small text-truncate" style="max-width: 250px;">${item.description || '-'}</div>
                </td>
                <td class="text-end px-4">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-light btn-sm btn-icon edit-btn" data-id="${item.id}">
                            <i class="ti ti-edit text-primary"></i>
                        </button>
                        <button class="btn btn-light btn-sm btn-icon delete-btn" data-id="${item.id}">
                            <i class="ti ti-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Action Buttons
        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const item = allDepartments.find(d => d.id == btn.dataset.id);
                if (item) {
                    departmentIdInput.value = item.id;
                    modalTitle.innerText = 'Ubah Departemen';
                    departmentForm.querySelector('[name="name"]').value = item.name;
                    departmentForm.querySelector('[name="code"]').value = item.code || '';
                    departmentForm.querySelector('[name="description"]').value = item.description || '';
                    bsModal.show();
                }
            });
        });

        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const confirmed = await api.confirm({
                    title: 'Hapus Departemen?',
                    message: 'Apakah Anda yakin ingin menghapus departemen ini?',
                    type: 'danger'
                });

                if (confirmed) {
                    try {
                        const res = await api.request('/admin/departments', {
                            method: 'DELETE',
                            body: JSON.stringify({ id: btn.dataset.id })
                        });
                        if (res.success) {
                            api.showToast('Departemen berhasil dihapus');
                            loadDepartments();
                        }
                    } catch (error) {
                        api.showToast(error.message, 'danger');
                    }
                }
            });
        });
    }

    departmentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(departmentForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await api.request('/admin/departments', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            if (response.success) {
                api.showToast(data.id ? 'Departemen diperbarui' : 'Departemen ditambahkan');
                bsModal.hide();
                loadDepartments();
            }
        } catch (error) {
            api.showToast(error.message, 'danger');
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        departmentForm.reset();
        departmentIdInput.value = '';
        modalTitle.innerText = 'Tambah Departemen';
    });

    loadDepartments();
});
