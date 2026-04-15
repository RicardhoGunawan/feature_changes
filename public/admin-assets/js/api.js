const BASE_URL = window.location.origin + '/api';

export const api = {
    async request(endpoint, options = {}) {
        const token = localStorage.getItem('session_token');
        const headers = { 'Content-Type': 'application/json', ...options.headers };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        try {
            const response = await fetch(`${BASE_URL}${endpoint}`, { ...options, headers });
            const resData = await response.json();
            
            if (!response.ok) {
                if (response.status === 401) {
                    localStorage.removeItem('session_token');
                    window.location.href = '/admin/login';
                }
                throw new Error(resData.message || 'Something went wrong');
            }
            return resData;
        } catch (error) { throw error; }
    },

    login(username, password) { return this.request('/auth/login', { method: 'POST', body: JSON.stringify({ username, password }) }); },
    getDashboardData() { return this.request('/admin/dashboard'); },
    getEmployees() { return this.request('/admin/employees'); },
    getAttendance() { return this.request('/admin/attendance'); },
    getLeaveRequests() { return this.request('/admin/leave'); },
    getLocations() { return this.request('/admin/locations'); },
    saveLocation(data) {
        return this.request(data.id ? `/admin/locations/${data.id}` : '/admin/locations', {
            method: data.id ? 'PATCH' : 'POST',
            body: JSON.stringify(data)
        });
    },
    
    getSchedules() { return this.request('/admin/schedules'); },
    saveSchedule(data) {
        return this.request(data.id ? `/admin/schedules/${data.id}` : '/admin/schedules', {
            method: data.id ? 'PATCH' : 'POST',
            body: JSON.stringify(data)
        });
    },
    deleteSchedule(id) {
        return this.request(`/admin/schedules/${id}`, { method: 'DELETE' });
    },
    deleteLocation(id) {
        return this.request(`/admin/locations/${id}`, { method: 'DELETE' });
    },

    saveEmployee(data) {
        return this.request('/admin/employees', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    updateLeaveStatus(id, action, note = '') {
        return this.request(action === 'approve' || action === 'pending_hr' ? `/admin/leave/${id}/approve` : `/admin/leave/${id}/approve`, {
            method: 'PATCH',
            body: JSON.stringify({ status: action, note: note })
        });
    },

    notify(message, type = 'success') {
        const container = this._getToastContainer();
        const toastId = 'toast-' + Date.now();
        let icon = 'ti-check'; 
        let color = 'success';
        
        if (type === 'error' || type === 'danger') { icon = 'ti-x'; color = 'danger'; }
        else if (type === 'warning') { icon = 'ti-alert-triangle'; color = 'warning'; }
        else if (type === 'info') { icon = 'ti-info-circle'; color = 'info'; }

        const html = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${color} border-0 shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center gap-3 py-3 px-4">
                        <i class="ti ${icon} fs-2"></i>
                        <div class="fw-bold">${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(toastId);
        const toast = new bootstrap.Toast(el, { delay: 3500 });
        toast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    },

    confirm({ title, message, confirmText = 'Ya, Lanjutkan', cancelText = 'Batal', type = 'danger' }) {
        return new Promise((resolve) => {
            const modalId = 'confirmModal-' + Date.now();
            let icon = 'ti-alert-triangle';
            let color = 'danger';
            
            if (type === 'warning') { icon = 'ti-alert-circle'; color = 'warning'; }
            else if (type === 'info') { icon = 'ti-info-circle'; color = 'info'; }
            else if (type === 'success') { icon = 'ti-circle-check'; color = 'success'; }

            const html = `
                <div class="modal modal-blur fade" id="${modalId}" tabindex="-1" role="dialog" aria-hidden="true" style="backdrop-filter: blur(4px);">
                    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                        <div class="modal-content shadow-lg border-0">
                            <div class="modal-status bg-${color}" style="height: 4px; position: absolute; top: 0; left: 0; right: 0; border-radius: 4px 4px 0 0;"></div>
                            <div class="modal-body text-center py-4">
                                <div class="mb-3">
                                    <div class="avatar avatar-xl rounded-circle bg-${color}-lt" style="background: rgba(var(--bs-${color}-rgb), 0.1); color: var(--bs-${color}); width: 64px; height: 64px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="ti ${icon}" style="font-size: 2.5rem;"></i>
                                    </div>
                                </div>
                                <h2 class="mb-2 fw-bold text-dark">${title}</h2>
                                <div class="text-secondary px-3" style="font-size: 0.95rem; line-height: 1.5;">${message}</div>
                            </div>
                            <div class="modal-footer bg-light-subtle border-0">
                                <div class="w-100">
                                    <div class="row g-2">
                                        <div class="col">
                                            <button type="button" class="btn btn-link link-secondary w-100 text-decoration-none fw-medium" data-bs-dismiss="modal">
                                                ${cancelText}
                                            </button>
                                        </div>
                                        <div class="col">
                                            <button type="button" class="btn btn-${color} w-100 fw-bold shadow-sm confirm-btn" style="border-radius: 8px;">
                                                ${confirmText}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', html);
            const modalEl = document.getElementById(modalId);
            const bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });
            
            modalEl.querySelector('.confirm-btn').addEventListener('click', (e) => {
                bsModal.hide();
                resolve(true);
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
                modalEl.remove();
                resolve(false);
            });

            bsModal.show();
        });
    },

    _getToastContainer() {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '99999';
            document.body.appendChild(container);
        }
        return container;
    },

    showToast(msg, type) { this.notify(msg, type); }
};
