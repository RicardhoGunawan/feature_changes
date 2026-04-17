import { api } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    fetchLogs();
});

async function fetchLogs(page = 1) {
    try {
        const response = await api.request(`/admin/audit-logs?page=${page}`);
        if (response.success) {
            renderTable(response.data.data);
            renderPagination(response.data);
        }
    } catch (error) {
        console.error('Error fetching audit logs:', error);
    }
}

function renderTable(data) {
    const tbody = document.getElementById('auditLogTableBody');
    if (!tbody) return;

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-secondary">Belum ada riwayat aktivitas.</td></tr>';
        return;
    }

    tbody.innerHTML = data.map(log => {
        const formattedDate = new Date(log.created_at).toLocaleString('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short'
        });

        return `
            <tr>
                <td class="ps-4 small text-secondary">${formattedDate}</td>
                <td>
                    <div class="fw-bold">${log.causer ? log.causer.name : 'System'}</div>
                    <div class="small text-secondary">${log.causer ? log.causer.username : ''}</div>
                </td>
                <td>
                    <span class="badge ${getEventBadgeClass(log.event)}">${formatEvent(log.event)}</span>
                </td>
                <td>${log.target_user ? log.target_user.name : '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-detail" data-log='${JSON.stringify(log)}'>
                        <i class="ti ti-eye"></i> Lihat Detail
                    </button>
                </td>
                <td class="pe-4 small text-secondary">${log.ip_address || '-'}</td>
            </tr>
        `;
    }).join('');

    // Attach detail events
    document.querySelectorAll('.btn-detail').forEach(btn => {
        btn.addEventListener('click', () => {
            const log = JSON.parse(btn.getAttribute('data-log'));
            showLogDetail(log);
        });
    });
}

function getEventBadgeClass(event) {
    if (event.includes('approval')) return 'bg-success-subtle text-success border-success';
    if (event.includes('rejection')) return 'bg-danger-subtle text-danger border-danger';
    if (event.includes('adjustment')) return 'bg-primary-subtle text-primary border-primary';
    return 'bg-light text-secondary border';
}

function formatEvent(event) {
    return event.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
}

function showLogDetail(log) {
    const content = document.getElementById('logDetailContent');
    content.innerHTML = `
        <div class="bg-light p-3 rounded mb-3 small">
            <div class="row">
                <div class="col-6"><strong>Waktu:</strong> ${new Date(log.created_at).toLocaleString()}</div>
                <div class="col-6"><strong>Device:</strong> ${log.user_agent ? log.user_agent.substring(0, 50) + '...' : '-'}</div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold small text-danger">Data Sebelum:</label>
                <pre class="bg-danger-subtle p-2 rounded small" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(log.old_values, null, 2)}</pre>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small text-success">Data Sesudah:</label>
                <pre class="bg-success-subtle p-2 rounded small" style="max-height: 200px; overflow-y: auto;">${JSON.stringify(log.new_values, null, 2)}</pre>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    modal.show();
}

function renderPagination(meta) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="small text-secondary">Menampilkan ${meta.from || 0} sampai ${meta.to || 0} dari ${meta.total} entri</div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${meta.current_page - 1}"><i class="ti ti-chevron-left"></i></a>
                </li>
                <li class="page-item active"><a class="page-link">${meta.current_page}</a></li>
                <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${meta.current_page + 1}"><i class="ti ti-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    `;

    container.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = link.getAttribute('data-page');
            if (page) fetchLogs(page);
        });
    });
}
