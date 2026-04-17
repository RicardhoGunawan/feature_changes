import { api } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    fetchPolicies();

    const editForm = document.getElementById('editPolicyForm');
    if (editForm) {
        editForm.addEventListener('submit', handleUpdatePolicy);
    }
});

async function fetchPolicies() {
    try {
        const response = await api.request('/admin/leave-policies');
        if (response.success) {
            renderTable(response.data);
        }
    } catch (error) {
        console.error('Error fetching policies:', error);
    }
}

function renderTable(data) {
    const tbody = document.getElementById('policyTableBody');
    if (!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">Belum ada kebijakan yang diatur.</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(item => {
        const p = item.policy || {};
        return `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold">${item.name}</div>
                    <div class="text-secondary small text-truncate" style="max-width: 200px;">${p.description || '-'}</div>
                </td>
                <td><span class="badge bg-light text-dark border">${item.code}</span></td>
                <td class="fw-bold">${p.default_quota || 0} Hari</td>
                <td>${p.min_service_months || 0} Bln</td>
                <td>
                    ${p.allow_half_day 
                        ? '<span class="text-success"><i class="ti ti-check me-1"></i>Ya</span>' 
                        : '<span class="text-secondary"><i class="ti ti-x me-1"></i>Tidak</span>'}
                </td>
                <td>
                    ${p.requires_attachment 
                        ? '<span class="badge bg-warning-subtle text-warning border-warning">Wajib</span>' 
                        : '<span class="badge bg-light text-secondary border">Opsional</span>'}
                </td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm btn-icon btn-light btn-edit" data-item='${JSON.stringify(item)}'>
                        <i class="ti ti-edit"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // Attach edit events
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const item = JSON.parse(btn.getAttribute('data-item'));
            showEditModal(item);
        });
    });
}

function showEditModal(item) {
    const p = item.policy || {};
    document.getElementById('edit_leave_type_id').value = item.id;
    document.getElementById('edit_description').value = p.description || '';
    document.getElementById('edit_default_quota').value = p.default_quota || 12;
    document.getElementById('edit_min_service_months').value = p.min_service_months || 0;
    document.getElementById('edit_allow_half_day').checked = !!p.allow_half_day;
    document.getElementById('edit_requires_attachment').checked = !!p.requires_attachment;

    const modal = new bootstrap.Modal(document.getElementById('editPolicyModal'));
    modal.show();
}

async function handleUpdatePolicy(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {
        leave_type_id: formData.get('leave_type_id'),
        description: formData.get('description'),
        default_quota: parseInt(formData.get('default_quota')),
        min_service_months: parseInt(formData.get('min_service_months')),
        allow_half_day: document.getElementById('edit_allow_half_day').checked,
        requires_attachment: document.getElementById('edit_requires_attachment').checked
    };

    try {
        const response = await api.request('/admin/leave-policies', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        if (response.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editPolicyModal'));
            modal.hide();
            fetchPolicies(); // Reload
        }
    } catch (error) {
        alert('Gagal menyimpan kebijakan: ' + error.message);
    }
}
