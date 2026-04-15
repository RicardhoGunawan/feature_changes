import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.querySelector('#positionTableBody');
    const positionForm = document.querySelector('#addPositionForm');
    const positionIdInput = document.getElementById('positionId');
    const modalTitle = document.getElementById('positionModalTitle');
    
    const modalEl = document.getElementById('addPositionModal');
    if (!modalEl) return;
    
    const bsModal = new bootstrap.Modal(modalEl);
    let allPositions = [];
    let parentSelect;

    function initParentSelect() {
        if (parentSelect) parentSelect.destroy();
        parentSelect = new TomSelect('#parentSelect', {
            create: false,
            sortField: { field: 'text', order: 'asc' },
            placeholder: 'Pilih Atasan (Superior)...',
            allowEmptyOption: true
        });
    }

    async function fetchPositions() {
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        
        try {
            const res = await api.request('/admin/positions');
            if (res.success) {
                allPositions = res.data;
                renderPositions(allPositions);
                updateParentOptions(allPositions);
            }
        } catch (e) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-danger">${e.message}</td></tr>`;
        }
    }

    function renderPositions(positions) {
        if (positions.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-5">Belum ada data jabatan.</td></tr>`;
            return;
        }

        tableBody.innerHTML = positions.map(p => `
            <tr>
                <td class="px-4 fw-bold text-dark">${p.name}</td>
                <td><span class="badge bg-light text-dark">${p.department || '-'}</span></td>
                <td>
                    ${p.parent ? `<span class="fw-medium text-primary"><i class="ti ti-arrow-up-right me-1"></i>${p.parent.name}</span>` : '<span class="text-muted">Top Level</span>'}
                </td>
                <td><div class="small fw-semibold">Level ${p.level}</div></td>
                <td class="text-end px-4">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-light btn-sm btn-icon edit-btn" data-id="${p.id}">
                            <i class="ti ti-edit text-primary"></i>
                        </button>
                        <button class="btn btn-light btn-sm btn-icon delete-btn" data-id="${p.id}">
                            <i class="ti ti-trash text-danger"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        // Edit
        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const p = allPositions.find(item => item.id == btn.dataset.id);
                if (p) {
                    positionIdInput.value = p.id;
                    modalTitle.innerText = 'Ubah Jabatan';
                    positionForm.querySelector('[name="name"]').value = p.name;
                    positionForm.querySelector('[name="department"]').value = p.department || '';
                    positionForm.querySelector('[name="level"]').value = p.level;
                    if (parentSelect) parentSelect.setValue(p.parent_id || '');
                    bsModal.show();
                }
            });
        });

        // Delete
        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const confirmed = await api.confirm({
                    title: 'Hapus Jabatan?',
                    message: 'Pastikan tidak ada karyawan yang sedang menjabat di posisi ini sebelum menghapus.',
                    type: 'danger'
                });

                if (confirmed) {
                    try {
                        const res = await api.request('/admin/positions', {
                            method: 'DELETE',
                            body: JSON.stringify({ id: btn.dataset.id })
                        });
                        if (res.success) {
                            api.showToast('Jabatan berhasil dihapus');
                            fetchPositions();
                        }
                    } catch (e) {
                        api.showToast(e.message, 'danger');
                    }
                }
            });
        });
    }

    function updateParentOptions(positions) {
        if (!parentSelect) return;
        parentSelect.clearOptions();
        parentSelect.addOption({ value: '', text: 'Tidak Ada (Top Level)' });
        positions.forEach(p => {
            parentSelect.addOption({ value: p.id, text: p.name });
        });
    }

    positionForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(positionForm).entries());
        
        try {
            const res = await api.request('/admin/positions', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            if (res.success) {
                api.showToast(data.id ? 'Jabatan diperbarui' : 'Jabatan ditambahkan');
                bsModal.hide();
                fetchPositions();
            }
        } catch (e) {
            api.showToast(e.message, 'danger');
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        positionForm.reset();
        positionIdInput.value = '';
        modalTitle.innerText = 'Tambah Jabatan Baru';
        if (parentSelect) parentSelect.clear();
    });

    initParentSelect();
    fetchPositions();
});
