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
    let supervisorSelect;
    let shiftSelect;
    let locationSelect;
    let positionSelect;

    // Initialize Tom Selects
    function initSelects() {
        if (supervisorSelect) supervisorSelect.destroy();
        if (shiftSelect) shiftSelect.destroy();
        if (locationSelect) locationSelect.destroy();
        if (positionSelect) positionSelect.destroy();

        const supEl = document.querySelector('#supervisorSelect');
        if (supEl) {
            supervisorSelect = new TomSelect('#supervisorSelect', {
                create: false,
                sortField: { field: 'text', order: 'asc' },
                placeholder: 'Pilih Atasan...',
                allowEmptyOption: true
            });
        }

        const shiftEl = document.querySelector('#shiftSelect');
        if (shiftEl) {
            shiftSelect = new TomSelect('#shiftSelect', {
                create: false,
                sortField: { field: 'text', order: 'asc' },
                placeholder: 'Pilih Shift...',
                allowEmptyOption: true
            });
        }

        const locEl = document.querySelector('#locationSelect');
        if (locEl) {
            locationSelect = new TomSelect('#locationSelect', {
                create: false,
                sortField: { field: 'text', order: 'asc' },
                placeholder: 'Pilih Lokasi...',
                allowEmptyOption: true
            });
        }

        const posEl = document.querySelector('#positionSelect');
        if (posEl) {
            positionSelect = new TomSelect('#positionSelect', {
                create: false,
                sortField: { field: 'text', order: 'asc' },
                placeholder: 'Pilih Jabatan...',
                allowEmptyOption: true
            });
        }
    }

    async function loadDropdownData() {
        try {
            // Load Supervisors
            if (supervisorSelect) {
                const resSup = await api.request('/admin/employees?mode=supervisors');
                if (resSup.success) {
                    supervisorSelect.clearOptions();
                    supervisorSelect.addOption({ value: '', text: 'Tanpa Atasan' });
                    resSup.data.forEach(s => {
                        supervisorSelect.addOption({
                            value: s.id,
                            text: `${s.name} (${s.employee_id}) - ${s.role.toUpperCase()}`
                        });
                    });
                }
            }

            // Load Positions
            if (positionSelect) {
                const resPos = await api.request('/admin/positions');
                if (resPos.success) {
                    positionSelect.clearOptions();
                    positionSelect.addOption({ value: '', text: 'Pilih Jabatan...' });
                    resPos.data.forEach(p => {
                        positionSelect.addOption({
                            value: p.id,
                            text: p.name
                        });
                    });
                }
            }

            // Load Shifts
            if (shiftSelect) {
                const resShift = await api.request('/admin/schedules');
                if (resShift.success) {
                    shiftSelect.clearOptions();
                    shiftSelect.addOption({ value: '', text: 'Pilih Shift...' });
                    resShift.data.forEach(s => {
                        shiftSelect.addOption({
                            value: s.id,
                            text: `${s.shift_name} (${s.start_time} - ${s.end_time})`
                        });
                    });
                }
            }

            // Load Locations
            if (locationSelect) {
                const resLoc = await api.request('/admin/locations');
                if (resLoc.success) {
                    locationSelect.clearOptions();
                    locationSelect.addOption({ value: '', text: 'Pilih Lokasi...' });
                    resLoc.data.forEach(l => {
                        locationSelect.addOption({
                            value: l.id,
                            text: `${l.location_name} (${l.radius}m)`
                        });
                    });
                }
            }
        } catch (e) {
            console.error('Failed to load dropdown data', e);
        }
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

        // Filter: Jangan tampilkan Administrator di daftar karyawan
        const filteredEmployees = employees.filter(emp => emp.role !== 'admin' || emp.username !== 'admin');

        tableBody.innerHTML = filteredEmployees.map(emp => {
            let badgeHtml = '';
            if (emp.role === 'spv') {
                badgeHtml = `<span class="badge bg-info text-white rounded-pill" style="font-size: 9px; padding: 2px 8px;">SPV</span>`;
            } else if (emp.role === 'hr') {
                badgeHtml = `<span class="badge bg-warning text-white rounded-pill" style="font-size: 9px; padding: 2px 8px;">HR</span>`;
            } else {
                badgeHtml = `<span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill" style="font-size: 9px; padding: 2px 8px;">EMPLOYEE</span>`;
            }

            return `
                <tr>
                    <td class="px-4">
                        <div class="d-flex align-items-center">
                            <img src="${window.ADMIN_ASSETS_PATH || ''}images/avatar/avatar-default.jpg" class="avatar avatar-sm rounded-circle me-3 shadow-sm" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(emp.name)}&background=random'">
                            <div>
                                <div class="fw-bold text-dark">${emp.name}</div>
                                ${badgeHtml}
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="small fw-medium">${emp.employee_id || '-'}</div>
                        <div class="text-muted small">@${emp.username}</div>
                    </td>
                    <td>
                        <div class="small text-dark fw-medium">${emp.position || '-'}</div>
                        <div class="text-muted small">${emp.department || '-'}</div>
                    </td>
                    <td>
                        <div class="small fw-semibold text-primary">${emp.supervisor_name || '<span class="text-muted">-</span>'}</div>
                    </td>
                    <td>
                        <div class="small text-dark fw-medium">${emp.shift_name || '-'}</div>
                        <div class="text-muted small">${emp.work_hours || '-'}</div>
                    </td>
                    <td>
                        <span class="badge ${emp.is_active != 0 ? 'bg-success' : 'bg-secondary'} bg-opacity-10 ${emp.is_active != 0 ? 'text-success' : 'text-secondary'} rounded-pill px-3 py-2">
                            ${emp.is_active != 0 ? 'Aktif' : 'Nonaktif'}
                        </span>
                    </td>
                    <td class="text-end px-4">
                        <div class="d-flex justify-content-end gap-2">
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

        // Edit
        tableBody.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = btn.dataset.id;
                const emp = allEmployees.find(item => item.id == id);
                if (emp) {
                    employeeIdInput.value = emp.id;
                    modalTitle.innerText = 'Ubah Data Karyawan';
                    
                    // Fill Form
                    employeeForm.querySelector('[name="username"]').value = emp.username;
                    employeeForm.querySelector('[name="name"]').value = emp.name;
                    employeeForm.querySelector('[name="email"]').value = emp.email || '';
                    employeeForm.querySelector('[name="phone"]').value = emp.phone || '';
                    employeeForm.querySelector('[name="department"]').value = emp.department || '';
                    employeeForm.querySelector('[name="role"]').value = emp.role;
                    
                    employeeForm.querySelector('[name="password"]').required = false; 
                    employeeForm.querySelector('[name="password"]').placeholder = '(Kosongkan jika tidak diubah)';
                    
                    if (supervisorSelect) {
                        supervisorSelect.setValue(emp.supervisor_id || '');
                    }
                    if (shiftSelect) {
                        shiftSelect.setValue(emp.shift_id || '');
                    }
                    if (locationSelect) {
                        locationSelect.setValue(emp.location_id || '');
                    }
                    if (positionSelect) {
                        positionSelect.setValue(emp.position_id || '');
                    }
                    
                    bsModal.show();
                }
            });
        });

        // Delete
        tableBody.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const confirmed = await api.confirm({
                    title: 'Hapus Karyawan?',
                    message: 'Seluruh data profil, histori absensi, dan akses login karyawan ini akan dihapus secara permanen.',
                    confirmText: 'Hapus Sekarang',
                    type: 'danger'
                });

                if (confirmed) {
                    try {
                        const res = await api.request('/admin/employees', {
                            method: 'DELETE',
                            body: JSON.stringify({ id: btn.dataset.id })
                        });
                        if (res.success) {
                            api.notify('Karyawan berhasil dihapus');
                            loadEmployees();
                            loadDropdownData(); 
                        }
                    } catch (error) {
                        api.notify(error.message, 'danger');
                    }
                }
            });
        });
    }

    // Reset form on close
    modalEl.addEventListener('hidden.bs.modal', () => {
        employeeForm.reset();
        employeeIdInput.value = '';
        modalTitle.innerText = 'Tambah Karyawan Baru';
        employeeForm.querySelector('[name="password"]').required = true;
        employeeForm.querySelector('[name="password"]').placeholder = '******';
        if (supervisorSelect) supervisorSelect.clear();
        if (shiftSelect) shiftSelect.clear();
        if (locationSelect) locationSelect.clear();
        if (positionSelect) positionSelect.clear();
    });

    // Search
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const filtered = allEmployees.filter(emp => 
                emp.name.toLowerCase().includes(term) || 
                (emp.employee_id && emp.employee_id.toLowerCase().includes(term)) || 
                (emp.department && emp.department.toLowerCase().includes(term)) ||
                (emp.username && emp.username.toLowerCase().includes(term))
            );
            renderEmployees(filtered);
        });
    }

    // Submit Form
    if (employeeForm) {
        employeeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(employeeForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await api.saveEmployee(data);
                if (response.success) {
                    api.showToast(data.id ? 'Data karyawan berhasil diperbarui' : 'Karyawan berhasil ditambahkan');
                    bsModal.hide();
                    loadEmployees();
                    loadDropdownData();
                }
            } catch (error) {
                api.showToast(error.message, 'danger');
            }
        });
    }

    initSelects();
    loadDropdownData();
    loadEmployees();
});
