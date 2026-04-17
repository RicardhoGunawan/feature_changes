import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.querySelector('#employeeTableBody');
    const employeeForm = document.querySelector('#addEmployeeForm');
    const employeeIdInput = document.getElementById('employeeId');
    const modalTitle = document.getElementById('employeeModalTitle');
    const searchInput = document.querySelector('#employeeSearchInput');
    
    const modalEl = document.getElementById('addEmployeeModal');
    if (!modalEl) return;
    
    const bsModal = new bootstrap.Modal(modalEl);

    let allEmployees = [];

    // Initialize Select2
    function initSelects() {
        $('#shiftSelect').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addEmployeeModal'),
            width: '100%',
            placeholder: 'Pilih Shift...'
        });

        $('#locationSelect').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addEmployeeModal'),
            width: '100%',
            placeholder: 'Pilih Lokasi...'
        });

        $('#positionSelect').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#addEmployeeModal'),
            width: '100%',
            placeholder: 'Pilih Jabatan...'
        });
    }

    function setSelect2Value(selector, value) {
        $(selector).val(value).trigger('change');
    }

    async function loadDropdownData() {
        try {
            const resPos = await api.request('/admin/positions');
            if (resPos.success) {
                const $pos = $('#positionSelect');
                $pos.empty().append('<option value="">Pilih Jabatan...</option>');
                resPos.data.forEach(p => { $pos.append(new Option(p.name, p.id)); });
            }

            const resShift = await api.request('/admin/schedules');
            if (resShift.success) {
                const $shift = $('#shiftSelect');
                $shift.empty().append('<option value="">Pilih Shift...</option>');
                resShift.data.forEach(s => { $shift.append(new Option(`${s.shift_name} (${s.start_time} - ${s.end_time})`, s.id)); });
            }

            const resLoc = await api.request('/admin/locations');
            if (resLoc.success) {
                const $loc = $('#locationSelect');
                $loc.empty().append('<option value="">Pilih Lokasi...</option>');
                resLoc.data.forEach(l => { $loc.append(new Option(`${l.location_name} (${l.radius}m)`, l.id)); });
            }
        } catch (e) { console.error('Failed to load dropdown data', e); }
    }

    async function loadEmployees() {
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const response = await api.getEmployees();
            if (response.success) {
                allEmployees = response.data;
                renderEmployees(allEmployees);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-danger">${error.message}</td></tr>`;
        }
    }

    function renderEmployees(employees) {
        if (!employees || employees.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="7" class="text-center py-5">Tidak ada data karyawan.</td></tr>`;
            return;
        }

        const filteredEmployees = employees.filter(emp => emp.username !== 'admin');

        tableBody.innerHTML = filteredEmployees.map(emp => {
            let badgeHtml = emp.role === 'administrator' 
                ? `<span class="badge bg-primary text-white rounded-pill" style="font-size: 9px; padding: 2px 8px;">ADMINISTRATOR</span>`
                : `<span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" style="font-size: 9px; padding: 2px 8px;">EMPLOYEE</span>`;

            const typeLabels = { 'permanent': 'Tetap', 'contract': 'Kontrak', 'probation': 'Probation' };
            const typeLabel = typeLabels[emp.employee_type] || emp.employee_type || '-';

            return `
                <tr>
                    <td class="px-4">
                        <div class="d-flex align-items-center">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(emp.name)}&background=random" class="avatar avatar-sm rounded-circle me-3 shadow-sm">
                            <div>
                                <div class="fw-bold text-dark">${emp.name}</div>
                                ${badgeHtml}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-medium">${emp.employee_code || '-'}</div>
                        <div class="text-muted small">@${emp.username}</div>
                    </td>
                    <td>
                        <div class="small text-dark fw-medium">${emp.position_name || emp.position || '-'}</div>
                        <div class="text-muted small">${emp.department_name || emp.department || '-'}</div>
                    </td>
                    <td>
                        <div class="small text-dark fw-medium">${emp.join_date || '-'}</div>
                        <div class="text-muted small">${typeLabel}</div>
                    </td>
                    <td>
                        <div class="small fw-bold text-primary">${emp.remaining_leave || 0} Hari</div>
                        <div class="text-muted x-small">Sisa Tahunan</div>
                    </td>
                    <td>
                        <span class="badge ${emp.is_active != 0 ? 'bg-success' : 'bg-secondary'} bg-opacity-10 ${emp.is_active != 0 ? 'text-success' : 'text-secondary'} rounded-pill px-3 py-2">
                            ${emp.is_active != 0 ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </td>
                    <td class="text-end px-4">
                        <div class="d-flex justify-content-end gap-2">
                            <button class="btn btn-light btn-sm btn-icon adjust-quota-btn" data-id="${emp.id}" title="Sesuaikan Kuota Cuti">
                                <i class="ti ti-wallet text-warning"></i>
                            </button>
                            <button class="btn btn-light btn-sm btn-icon edit-btn" data-id="${emp.id}" title="Ubah">
                                <i class="ti ti-edit text-primary"></i>
                            </button>
                            <button class="btn btn-light btn-sm btn-icon delete-btn" data-id="${emp.id}" title="Hapus">
                                <i class="ti ti-trash text-danger"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Listeners for actions
        tableBody.querySelectorAll('.adjust-quota-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('adjust_user_id').value = btn.dataset.id;
                new bootstrap.Modal(document.getElementById('adjustQuotaModal')).show();
            });
        });

        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const emp = allEmployees.find(item => item.id == btn.dataset.id);
                if (emp) {
                    employeeIdInput.value = emp.id;
                    modalTitle.innerText = 'Ubah Data Karyawan';
                    employeeForm.querySelector('[name="username"]').value = emp.username;
                    employeeForm.querySelector('[name="name"]').value = emp.name;
                    employeeForm.querySelector('[name="email"]').value = emp.email || '';
                    employeeForm.querySelector('[name="phone"]').value = emp.phone || '';
                    employeeForm.querySelector('[name="role"]').value = emp.role;
                    employeeForm.querySelector('[name="employee_type"]').value = emp.employee_type || 'permanent';
                    employeeForm.querySelector('[name="join_date"]').value = emp.join_date || '';
                    employeeForm.querySelector('[name="annual_leave_quota"]').value = emp.remaining_leave || 12;
                    
                    employeeForm.querySelector('[name="password"]').required = false; 
                    employeeForm.querySelector('[name="password"]').placeholder = '(Kosongkan jika tidak diubah)';
                    setSelect2Value('#shiftSelect', emp.shift_id || '');
                    setSelect2Value('#locationSelect', emp.location_id || '');
                    setSelect2Value('#positionSelect', emp.position_id || '');
                    bsModal.show();
                }
            });
        });

        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                if (await api.confirm({ title: 'Hapus Karyawan?', message: 'Data histori akan dihapus permanen.', type: 'danger' })) {
                    try {
                        const res = await api.request('/admin/employees', { method: 'DELETE', body: JSON.stringify({ id: btn.dataset.id }) });
                        if (res.success) { api.notify('Berhasil dihapus'); loadEmployees(); }
                    } catch (e) { api.notify(e.message, 'danger'); }
                }
            });
        });
    }

    // Modal forms
    const adjustQuotaForm = document.getElementById('adjustQuotaForm');
    if (adjustQuotaForm) {
        adjustQuotaForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = document.getElementById('adjust_user_id').value;
            try {
                const res = await api.request(`/admin/employees/${id}/adjust-quota`, {
                    method: 'PATCH',
                    body: JSON.stringify(Object.fromEntries(new FormData(adjustQuotaForm)))
                });
                if (res.success) {
                    api.notify('Kuota disesuaikan');
                    bootstrap.Modal.getInstance(document.getElementById('adjustQuotaModal')).hide();
                    adjustQuotaForm.reset();
                    loadEmployees();
                }
            } catch (e) { api.notify(e.message, 'danger'); }
        });
    }

    if (employeeForm) {
        employeeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                if (await api.saveEmployee(Object.fromEntries(new FormData(employeeForm)))) {
                    api.notify('Data disimpan'); bsModal.hide(); loadEmployees();
                }
            } catch (e) { api.notify(e.message, 'danger'); }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            renderEmployees(allEmployees.filter(emp => emp.name.toLowerCase().includes(term) || (emp.employee_id && emp.employee_id.toLowerCase().includes(term))));
        });
    }

    modalEl.addEventListener('hidden.bs.modal', () => {
        employeeForm.reset(); employeeIdInput.value = ''; modalTitle.innerText = 'Tambah Karyawan';
        employeeForm.querySelector('[name="password"]').required = true;
        setSelect2Value('#shiftSelect', ''); setSelect2Value('#locationSelect', ''); setSelect2Value('#positionSelect', '');
    });

    initSelects();
    loadDropdownData();
    loadEmployees();
});
