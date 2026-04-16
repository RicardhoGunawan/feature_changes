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
    let shiftSelect;
    let locationSelect;
    let positionSelect;

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

    // Shortcut for Select2 change trigger
    function setSelect2Value(selector, value) {
        $(selector).val(value).trigger('change');
    }

    async function loadDropdownData() {
        try {
            // Load Positions
            const resPos = await api.request('/admin/positions');
            if (resPos.success) {
                const $pos = $('#positionSelect');
                $pos.empty().append('<option value="">Pilih Jabatan...</option>');
                resPos.data.forEach(p => { $pos.append(new Option(p.name, p.id)); });
            }

            // Load Shifts
            const resShift = await api.request('/admin/schedules');
            if (resShift.success) {
                const $shift = $('#shiftSelect');
                $shift.empty().append('<option value="">Pilih Shift...</option>');
                resShift.data.forEach(s => { $shift.append(new Option(`${s.shift_name} (${s.start_time} - ${s.end_time})`, s.id)); });
            }

            // Load Locations
            const resLoc = await api.request('/admin/locations');
            if (resLoc.success) {
                const $loc = $('#locationSelect');
                $loc.empty().append('<option value="">Pilih Lokasi...</option>');
                resLoc.data.forEach(l => { $loc.append(new Option(`${l.location_name} (${l.radius}m)`, l.id)); });
            }

        } catch (e) {
            console.error('Failed to load dropdown data', e);
        }
    }

    async function loadEmployees() {
        if (!tableBody) return;
        tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        try {
            const response = await api.getEmployees();
            if (response.success) {
                allEmployees = response.data;
                renderEmployees(allEmployees);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger">${error.message}</td></tr>`;
        }
    }

    function renderEmployees(employees) {
        if (!employees || employees.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-5">Tidak ada data karyawan.</td></tr>`;
            return;
        }

        // Filter: Jangan tampilkan Administrator di daftar karyawan
        const filteredEmployees = employees.filter(emp => emp.role !== 'admin' || emp.username !== 'admin');

        tableBody.innerHTML = filteredEmployees.map(emp => {
            let badgeHtml = '';
            if (emp.role === 'administrator') {
                badgeHtml = `<span class="badge bg-primary text-white rounded-pill" style="font-size: 9px; padding: 2px 8px;">ADMINISTRATOR</span>`;
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
                    
                    employeeForm.querySelector('[name="role"]').value = emp.role;
                    
                    employeeForm.querySelector('[name="password"]').required = false; 
                    employeeForm.querySelector('[name="password"]').placeholder = '(Kosongkan jika tidak diubah)';
                    
                    setSelect2Value('#shiftSelect', emp.shift_id || '');
                    setSelect2Value('#locationSelect', emp.location_id || '');
                    setSelect2Value('#positionSelect', emp.position_id || '');
                    
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
        
        setSelect2Value('#shiftSelect', '');
        setSelect2Value('#locationSelect', '');
        setSelect2Value('#positionSelect', '');
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
