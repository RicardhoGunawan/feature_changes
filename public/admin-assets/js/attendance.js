import { api } from './api.js';

document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#attendanceTableBody');
    const filterForm = document.querySelector('#attendanceFilterForm');
    
    if (!tableBody) return;

    // Set default dates (today and 7 days ago)
    const today = new Date().toISOString().split('T')[0];
    const lastWeek = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    const startInput = document.querySelector('#startDateInput');
    const endInput = document.querySelector('#endDateInput');
    
    if (startInput) startInput.value = lastWeek;
    if (endInput) endInput.value = today;

    // Initial load
    loadEmployees();
    fetchAttendance(lastWeek, today);

    async function loadEmployees() {
        try {
            const res = await api.getEmployees();
            if (res.success) {
                const employeesOnly = res.data; // Show all for admin
                
                // Populate Filter Select
                const filterSelect = document.querySelector('#employeeFilterSelect');
                if (filterSelect) {
                    employeesOnly.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = `${emp.name} (${emp.employee_id || '-'})`;
                        filterSelect.appendChild(option);
                    });
                    new TomSelect("#employeeFilterSelect", {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Semua Karyawan",
                        allowEmptyOption: true,
                    });
                }

                // Populate Manual Select
                const manualSelect = document.querySelector('#manualEmployeeSelect');
                if (manualSelect) {
                    employeesOnly.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = `${emp.name} (${emp.employee_id || '-'})`;
                        manualSelect.appendChild(option);
                    });
                    window.manualTomSelect = new TomSelect("#manualEmployeeSelect", {
                        create: false,
                        sortField: { field: "text", direction: "asc" },
                        placeholder: "Pilih Karyawan",
                        allowEmptyOption: true,
                    });
                }
            }
        } catch (error) { console.error('Error loading employees:', error); }
    }

    // Handle Manual Attendance Submission
    const manualForm = document.querySelector('#manualAttendanceForm');
    if (manualForm) {
        manualForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(manualForm);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await api.request('/admin/manual-attendance', {
                    method: 'POST',
                    body: JSON.stringify(data)
                });

                if (response.success) {
                    api.showToast('Data absensi manual berhasil disimpan!', 'success');
                    
                    // Close Modal
                    const modalEl = document.getElementById('manualAttendanceModal');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    // Reset Form
                    manualForm.reset();
                    if (window.manualTomSelect) window.manualTomSelect.clear();

                    // Refresh Table
                    const startInput = document.querySelector('#startDateInput');
                    const endInput = document.querySelector('#endDateInput');
                    fetchAttendance(startInput.value, endInput.value);
                }
            } catch (error) {
                api.showToast(error.message, 'error');
            }
        });
    }

    if (filterForm) {
        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(filterForm);
            fetchAttendance(
                formData.get('start_date'),
                formData.get('end_date'),
                formData.get('status'),
                formData.get('employee_id')
            );
        });

        const exportBtn = document.querySelector('#exportExcelBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                const formData = new FormData(filterForm);
                const token = localStorage.getItem('session_token');
                const params = new URLSearchParams({
                    start_date: formData.get('start_date'),
                    end_date: formData.get('end_date'),
                    status: formData.get('status') || '',
                    employee_id: formData.get('employee_id') || '',
                    token: token || ''
                });
                // POINT TO LARAVEL EXPORT ROUTE
                window.location.href = `/api/admin/attendance/export?${params.toString()}`;
            });
        }
    }

    async function fetchAttendance(startDate, endDate, status = '', employeeId = '') {
        tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5"><div class="spinner-border text-primary"></div></td></tr>`;
        
        try {
            const response = await api.request('/admin/attendance', {
                method: 'POST',
                body: JSON.stringify({
                    start_date: startDate,
                    end_date: endDate,
                    status: status,
                    employee_id: employeeId
                })
            });

            if (response.success) {
                renderAttendance(response.data);
                updateSummary(response.summary);
            }
        } catch (error) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-danger">${error.message}</td></tr>`;
        }
    }

    function renderAttendance(records) {
        if (records.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="8" class="text-center py-5">Tidak ada data absensi untuk periode ini.</td></tr>`;
            return;
        }

        tableBody.innerHTML = records.map(reg => `
            <tr>
                <td class="px-4 fw-medium">${reg.formatted_date}</td>
                <td>
                    <div class="fw-bold">${reg.employee_name}</div>
                    <small class="text-muted">${reg.position || 'Employee'}</small>
                </td>
                <td>
                    <div class="small fw-medium">${reg.employee_id || '-'}</div>
                </td>
                <td>
                    <div>${reg.shift_name || '-'}</div>
                    <small class="text-muted">${reg.shift_hours || '-'}</small>
                </td>
                <td>${reg.check_in_formatted}</td>
                <td>${reg.check_out_formatted}</td>
                <td>${reg.duration_formatted}</td>
                <td>
                    <span class="badge ${getStatusBadgeClass(reg.status)}">
                        ${getStatusLabel(reg.status)}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    function updateSummary(summary) {
        document.querySelector('#summaryTotal').textContent = summary.total_records || 0;
        document.querySelector('#summaryPresent').textContent = summary.present_count || 0;
        document.querySelector('#summaryLate').textContent = summary.late_count || 0;
        document.querySelector('#summaryHours').textContent = (summary.total_hours || 0) + 'h';
    }

    function getStatusBadgeClass(status) {
        switch (status) {
            case 'present': return 'bg-success-subtle text-success';
            case 'late': return 'bg-danger-subtle text-danger';
            case 'incomplete': return 'bg-warning-subtle text-warning';
            default: return 'bg-secondary-subtle text-secondary';
        }
    }

    function getStatusLabel(status) {
        switch (status) {
            case 'present': return 'Tepat Waktu';
            case 'late': return 'Terlambat';
            case 'incomplete': return 'Incomplete';
            default: return status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
        }
    }
});
