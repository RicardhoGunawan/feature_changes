import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
    const rolesContainer = document.getElementById('rolesContainer');
    const loadingState = document.getElementById('loadingState');
    const confirmSaveBtn = document.getElementById('confirmSaveBtn');
    
    if (!rolesContainer) return;

    let currentConfigs = null;
    let pendingRole = null;
    let pendingPermissions = [];

    // Load Data
    async function loadConfigs() {
        try {
            const res = await api.request('/admin/roles');
            if (res.success) {
                currentConfigs = res.data;
                renderRoles();
            }
        } catch (error) {
            api.notify(error.message, 'danger');
        } finally {
            if (loadingState) loadingState.style.display = 'none';
        }
    }

    function renderRoles() {
        if (!currentConfigs) return;
        const { master_permissions, roles_config } = currentConfigs;

        rolesContainer.innerHTML = roles_config.filter(r => r.role !== 'admin').map(config => {
            let icon = 'ti-users-group';
            let colorClass = 'primary';
            
            if (config.role === 'spv') { icon = 'ti-user-check'; colorClass = 'warning'; }
            if (config.role === 'hr') { icon = 'ti-briefcase'; colorClass = 'info'; }
            if (config.role === 'employee') { icon = 'ti-user'; colorClass = 'secondary'; }

            return `
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="row g-0">
                            <!-- Left Side: Role Info -->
                            <div class="col-lg-4 bg-light bg-opacity-50 border-end border-light-subtle d-flex flex-column p-4 p-xl-5">
                                <div class="mb-4">
                                    <div class="bg-white shadow-sm d-inline-flex p-3 rounded-4 mb-3">
                                        <i class="ti ${icon} text-${colorClass} fs-1"></i>
                                    </div>
                                    <h3 class="fw-bold text-dark mb-1">${config.role_label}</h3>
                                    <p class="text-secondary small">Pengaturan tingkat akses dan wewenang untuk role ini.</p>
                                </div>
                                <div class="mt-auto">
                                    <div class="alert alert-${colorClass} border-0 bg-${colorClass} bg-opacity-10 rounded-3 p-3 text-decoration-none shadow-none">
                                        <h6 class="fw-bold mb-2"><i class="ti ti-info-circle me-1"></i> Informasi</h6>
                                        <p class="mb-0 small text-dark-emphasis opacity-75">Perubahan izin akan langsung berdampak pada seluruh pengguna dengan role ini tanpa perlu logout.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side: Permissions List -->
                            <div class="col-lg-8 p-4 p-xl-5">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <h5 class="fw-bold mb-0">Konfigurasi Hak Akses</h5>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill small">
                                        <i class="ti ti-circle-check me-1"></i> Aktif
                                    </span>
                                </div>

                                <div class="row g-3">
                                    ${master_permissions.map(perm => {
                                        const isChecked = config.permissions.includes(perm.id);
                                        const inputId = `chk-${config.role}-${perm.id}`;
                                        return `
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center justify-content-between p-3 border border-light-subtle rounded-3 hover-shadow-sm transition-all bg-white">
                                                    <label class="fw-semibold text-dark mb-0 fs-13 cursor-pointer" for="${inputId}">
                                                        ${perm.permission_name}
                                                    </label>
                                                    <div class="form-check form-switch m-0">
                                                        <input class="form-check-input custom-switch cursor-pointer" type="checkbox" 
                                                               value="${perm.id}" 
                                                               id="${inputId}" 
                                                               ${isChecked ? 'checked' : ''}>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                                
                                <div class="mt-5 pt-3 border-top border-light-subtle d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary px-5 py-2 fw-bold save-btn rounded-3 shadow-sm" 
                                            data-role="${config.role}">
                                        <i class="ti ti-device-floppy me-2"></i> Simpan Perubahan [${config.role_label}]
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Attach listeners
        rolesContainer.querySelectorAll('.save-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const role = btn.dataset.role;
                const checkboxesArr = Array.from(rolesContainer.querySelectorAll(`input[id^="chk-${role}-"]:checked`));
                const permissions = checkboxesArr.map(c => c.value);
                
                pendingRole = role;
                pendingPermissions = permissions;
                
                const modal = new bootstrap.Modal(document.getElementById('saveConfirmModal'));
                modal.show();
            });
        });
    }

    // Confirmation Handler
    if (confirmSaveBtn) {
        confirmSaveBtn.addEventListener('click', async () => {
            const modalEl = document.getElementById('saveConfirmModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            
            try {
                confirmSaveBtn.disabled = true;
                confirmSaveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                
                const res = await api.request('/admin/roles', {
                    method: 'POST',
                    body: JSON.stringify({
                        role: pendingRole,
                        permissions: pendingPermissions
                    })
                });

                if (res.success) {
                    api.notify(res.message, 'success');
                    modal.hide();
                    loadConfigs(); // Refresh
                }
            } catch (error) {
                api.notify(error.message, 'danger');
            } finally {
                confirmSaveBtn.disabled = false;
                confirmSaveBtn.innerText = 'Ya, Simpan';
            }
        });
    }

    loadConfigs();
});
