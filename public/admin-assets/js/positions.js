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

    function initSelects() {
        $('#parentSelect').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addPositionModal'),
            width: '100%',
            placeholder: 'Pilih Atasan...'
        });
        
        $('#departmentSelect').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addPositionModal'),
            width: '100%',
            placeholder: 'Pilih Departemen...'
        });
    }

    function setSelect2Value(selector, value) {
        $(selector).val(value).trigger('change');
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

    async function loadDepartments() {
        try {
            const res = await api.request('/admin/departments');
            if (res.success) {
                const $dept = $('#departmentSelect');
                $dept.empty().append('<option value="">Pilih Departemen...</option>');
                res.data.forEach(d => {
                    $dept.append(new Option(d.name, d.id));
                });
            }
        } catch (e) {
            console.error('Failed to load departments', e);
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
                <td>
                    <span class="badge bg-light text-dark border">${p.department ? p.department.name : '-'}</span>
                </td>
                <td class="text-primary fw-medium">
                    ${p.parent ? `<span class="fw-medium text-primary"><i class="ti ti-arrow-up-right me-1"></i>${p.parent.name}</span>` : '<span class="text-muted small">Top Level (CEO/Director)</span>'}
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
                    positionForm.querySelector('[name="level"]').value = p.level;
                    
                    setSelect2Value('#departmentSelect', p.department || '');
                    setSelect2Value('#parentSelect', p.parent_id || '');
                    
                    bsModal.show();
                }
            });
        });

        // Delete (Simplified)
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
        const $parent = $('#parentSelect');
        $parent.empty().append('<option value="">Tidak Ada (Top Level)</option>');
        positions.forEach(p => {
            $parent.append(new Option(p.name, p.id));
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
        setSelect2Value('#departmentSelect', '');
        setSelect2Value('#parentSelect', '');
    });

    initSelects();
    loadDepartments();
    fetchPositions();
});
