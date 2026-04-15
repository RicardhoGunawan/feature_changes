import { api } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#leaveTableBody');
    const filterForm = document.querySelector('#leaveFilterForm');
    
    if (!tableBody) return;

    // Set default month
    const currentMonth = new Date().toISOString().slice(0, 7);
    const monthInput = document.querySelector('#monthInput');
    if (monthInput) monthInput.value = currentMonth;

    // Initial load
    loadEmployees();
    fetchLeaves(currentMonth);

    async function loadEmployees() {
        try {
            const res = await api.getEmployees();
            if (res.success) {
                const employeesOnly = res.data;
                const select = document.querySelector('#employeeFilterSelect');
                if (select) {
                    employeesOnly.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = `${emp.name} (${emp.employee_id || '-'})`;
                        select.appendChild(option);
                    });

                    new TomSelect("#employeeFilterSelect", {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Semua Karyawan",
                        allowEmptyOption: true,
                    });
                }
            }
        } catch (error) { console.error('Error loading employees:', error); }
    }

    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(filterForm);
            fetchLeaves(
                formData.get('month'),
                formData.get('status'),
                formData.get('type'),
                formData.get('employee_id')
            );
        });

        const exportBtn = document.querySelector('#exportLeaveBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                const formData = new FormData(filterForm);
                const token = localStorage.getItem('session_token');
                const params = new URLSearchParams({
                    month: formData.get('month'),
                    status: formData.get('status') || 'all',
                    type: formData.get('type') || 'all',
                    user_id: formData.get('employee_id') || '',
                    token: token || ''
                });
                window.location.href = `/api/admin/leave/export?${params.toString()}`;
            });
        }
    }

    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
    const userRole = userData.role || 'employee';

    async function fetchLeaves(month, status = 'all', type = 'all', userId = '') {
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        
        try {
            const response = await api.request(`/admin/leave?month=${month}&status=${status}&type=${type}&user_id=${userId}`);

            if (response.success) {
                renderLeaves(response.data);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">${error.message}</td></tr>`;
        }
    }

    function renderLeaves(records) {
        if (!records || records.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5">Tidak ada pengajuan izin untuk periode ini.</td></tr>`;
            return;
        }

        tableBody.innerHTML = records.map(reg => {
            const canProcess = (userRole === 'spv' && reg.status === 'pending_spv') || 
                               (['admin', 'hr'].includes(userRole) && reg.status === 'pending_hr');

            return `
                <tr>
                    <td class="px-4">
                        <div class="d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(reg.employee.name)}&background=random&color=fff" 
                                 class="avatar avatar-sm rounded-circle shadow-sm" style="width:32px; height:32px;">
                            <div>
                                <div class="fw-bold text-dark">${reg.employee.name}</div>
                                <div class="text-muted" style="font-size: 11px;">${reg.employee.position || 'Employee'}</div>
                                <div class="text-primary mt-1" style="font-size: 10px; font-weight: 500;">
                                    <i class="ti ti-clock-share"></i> Diajukan: ${reg.created_at}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-medium">${reg.employee.employee_id || '-'}</div>
                    </td>
                    <td>
                        <span class="badge bg-primary-lt text-primary fw-bold" style="font-size: 11px;">${reg.employee.remaining_leave || 0} Hari</span>
                    </td>
                    <td>
                        <div class="fw-bold text-capitalize small border-start border-primary border-3 ps-2 mb-1">${reg.type}</div>
                        <div class="text-muted" style="font-size: 11px;">
                            <i class="ti ti-calendar-event"></i> ${reg.start_date_formatted}
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${parseFloat(reg.total_days || 0)} Hari</div>
                        ${reg.leave_duration_type === 'half_day' ? `
                            <div class="badge bg-info-subtle text-info x-small" style="font-size: 9px;">
                                <i class="ti ti-clock"></i> Setengah Hari (${reg.half_day_session === 'morning' ? 'Pagi' : 'Siang'})
                            </div>
                        ` : ''}
                    </td>
                    <td>
                        <div class="small text-truncate" style="max-width: 150px;" title="${reg.reason}">${reg.reason}</div>
                        ${reg.attachment ? `<span class="badge bg-blue-lt small"><i class="ti ti-paperclip"></i> Lampiran</span>` : ''}
                    </td>
                    <td>
                        <span class="badge ${getStatusBadgeClass(reg.status)}">
                            ${getStatusLabel(reg.status)}
                        </span>
                    </td>
                    <td class="text-end px-4">
                        <div class="dropdown">
                            <button class="btn btn-light btn-icon btn-sm" data-bs-toggle="dropdown" data-bs-boundary="viewport">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0">
                                ${canProcess ? `
                                    <a class="dropdown-item py-2 detail-btn fw-bold text-primary" href="#" data-id='${JSON.stringify(reg)}'>
                                        <i class="ti ti-checklist me-2"></i> Review Tahap Ini
                                    </a>
                                ` : `
                                    <a class="dropdown-item py-2 detail-btn text-secondary" href="#" data-id='${JSON.stringify(reg)}'>
                                        <i class="ti ti-eye me-2"></i> Detail Penjenjangan
                                    </a>
                                `}
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Detail listener
        tableBody.querySelectorAll('.detail-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                showDetail(JSON.parse(btn.dataset.id));
            });
        });
    }

    function showDetail(reg) {
        const canProcess = (userRole === 'spv' && reg.status === 'pending_spv') || 
                           (['admin', 'hr'].includes(userRole) && reg.status === 'pending_hr');

        const content = document.getElementById('detailContent');
        content.innerHTML = `
            <div class="p-4 border-bottom bg-light-subtle d-flex align-items-center gap-3">
                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(reg.employee.name)}&background=random&color=fff&size=128" 
                     class="avatar avatar-lg rounded-circle shadow" style="width:64px; height:64px;">
                <div>
                    <h4 class="mb-0 fw-bold">${reg.employee.name}</h4>
                    <p class="text-muted mb-0 small">${reg.employee.position || '-'} • ${reg.employee.department || '-'}</p>
                </div>
            </div>
            <div class="p-4">
                <div class="row g-4 mb-4">
                    <div class="col-6">
                        <label class="text-muted x-small text-uppercase fw-bold mb-1" style="font-size: 10px;">Tipe Pengajuan</label>
                        <div class="fw-bold text-capitalize text-primary h5 mb-0">${reg.type}</div>
                        ${reg.leave_duration_type === 'half_day' ? `
                            <div class="small text-info fw-bold mt-1">
                                <i class="ti ti-clock"></i> Setengah Hari (${reg.half_day_session === 'morning' ? 'Pagi' : 'Siang'})
                            </div>
                        ` : ''}
                    </div>
                    <div class="col-6">
                        <label class="text-muted x-small text-uppercase fw-bold mb-1" style="font-size: 10px;">Status Penjenjangan</label>
                        <div class="mb-0"><span class="badge ${getStatusBadgeClass(reg.status)}">${getStatusLabel(reg.status)}</span></div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted x-small text-uppercase fw-bold mb-1" style="font-size: 10px;">Mulai</label>
                        <div class="fw-bold small">${reg.start_date_formatted}</div>
                    </div>
                    <div class="col-6">
                        <label class="text-muted x-small text-uppercase fw-bold mb-1" style="font-size: 10px;">Selesai</label>
                        <div class="fw-bold small">${reg.end_date_formatted}</div>
                    </div>
                    <div class="col-12">
                        <div class="mt-2 text-muted x-small">
                            <i class="ti ti-history me-1"></i> Pengajuan ini dibuat pada: <strong>${reg.created_at}</strong>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4 p-3 bg-light rounded-2 border">
                    <label class="text-muted x-small text-uppercase fw-bold mb-2 d-block" style="font-size: 10px;">Alasan / Keterangan</label>
                    <p class="mb-0 small text-dark" style="line-height: 1.6;">${reg.reason || '-'}</p>
                </div>

                ${reg.attachment ? `
                    <div class="mb-4">
                        <label class="text-muted x-small text-uppercase fw-bold mb-2 d-block" style="font-size: 10px;">Lampiran Dokumen</label>
                        <a href="${reg.attachment}" target="_blank" class="btn btn-outline-primary btn-sm w-100 rounded-pill">
                            <i class="ti ti-download me-1"></i> Buka Lampiran
                        </a>
                    </div>
                ` : ''}

                <!-- Audit Trail: SPV Review -->
                ${reg.spv_reviewed_at ? `
                    <div class="mt-4 p-3 border-start border-4 border-warning bg-warning-subtle rounded-end-2 mb-3">
                        <label class="text-warning-emphasis x-small text-uppercase fw-bold mb-1 d-block" style="font-size: 10px;">Lvl 1: Review Supervisor</label>
                        <p class="mb-0 small text-dark">"${reg.spv_review_note || 'Tidak ada catatan'}"</p>
                        <div class="text-muted mt-2" style="font-size: 10px;">Direview oleh <strong>${reg.spv_reviewer_name}</strong> pada ${reg.spv_reviewed_at}</div>
                    </div>
                ` : ''}

                <!-- Audit Trail: HR Review -->
                ${reg.reviewed_at ? `
                    <div class="mt-4 p-3 border-start border-4 border-success bg-success-subtle rounded-end-2 mb-3">
                        <label class="text-success x-small text-uppercase fw-bold mb-1 d-block" style="font-size: 10px;">Lvl 2: Review HR (Final)</label>
                        <p class="mb-0 small text-dark">"${reg.review_note || 'Tidak ada catatan'}"</p>
                        <div class="text-muted mt-2" style="font-size: 10px;">Direview oleh <strong>${reg.reviewer_name}</strong> pada ${reg.reviewed_at}</div>
                    </div>
                ` : ''}

                ${canProcess ? `
                    <div class="mt-4 border-top pt-4">
                        <label class="form-label fw-bold text-dark small">Tulis Catatan / Alasan</label>
                        <textarea id="modalReviewNote" class="form-control mb-3" rows="2" placeholder="Tulis catatan di sini..."></textarea>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success flex-grow-1 py-2 fw-bold" onclick="processFromModal('${reg.id}', 'approve')">
                                <i class="ti ti-check me-1"></i> ${userRole === 'spv' ? 'Teruskan ke HR' : 'Setujui (Final)'}
                            </button>
                            <button class="btn btn-danger flex-grow-1 py-2 fw-bold" onclick="processFromModal('${reg.id}', 'reject')">
                                <i class="ti ti-x me-1"></i> Tolak Pengajuan
                            </button>
                        </div>
                    </div>
                ` : `
                    <div class="alert alert-secondary d-flex align-items-center py-2" style="font-size: 11px;">
                        <i class="ti ti-info-circle me-2 fs-6"></i>
                        <span>${reg.status.includes('rejected') || reg.status === 'approved' ? 'Pengajuan ini sudah selesai diproses.' : 'Menunggu antrian verifikasi role selanjutnya.'}</span>
                    </div>
                `}
            </div>
        `;
        const modal = new bootstrap.Modal(document.getElementById('detailLeaveModal'));
        modal.show();
    }

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'approved': return 'bg-success text-white';
            case 'rejected': return 'bg-danger text-white';
            case 'rejected_spv': return 'bg-secondary text-white';
            case 'pending_spv': return 'bg-warning text-dark';
            case 'pending_hr': return 'bg-info text-white';
            default: return 'bg-light text-dark shadow-sm border';
        }
    }

    function getStatusLabel(status) {
        switch (status) {
            case 'approved': return 'Disetujui Final';
            case 'rejected': return 'Ditolak HR';
            case 'rejected_spv': return 'Ditolak SPV';
            case 'pending_spv': return 'Menunggu SPV';
            case 'pending_hr': return 'Menunggu HR';
            default: return status;
        }
    }

    // New integrated processor
    window.processFromModal = async (id, action) => {
        const note = document.getElementById('modalReviewNote').value;
        
        if (action === 'reject' && !note.trim()) {
            api.notify('Mohon isi alasan penolakan pada catatan.', 'warning');
            return;
        }

        try {
            const response = await api.request('/admin/leave/approve', {
                method: 'PATCH',
                body: JSON.stringify({
                    id: id,
                    status: action,
                    note: note
                })
            });

            if (response.success) {
                api.notify(action === 'approve' ? 'Berhasil diproses' : 'Pengajuan ditolak');
                const modalEl = document.getElementById('detailLeaveModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                
                setTimeout(() => fetchLeaves(monthInput.value), 500);
            }
        } catch (error) {
            api.notify(error.message, 'danger');
        }
    };
});
