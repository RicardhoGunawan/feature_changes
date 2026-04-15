<header class="topbar sticky-top bg-white border-bottom" style="margin-left: 260px; height: 75px; z-index: 1000;">
    <div class="container-fluid h-100 px-4">
        <div class="d-flex align-items-center justify-content-between h-100">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="btn btn-light btn-icon border-0 shadow-none me-3">
                    <i class="ti ti-menu-2 fs-3"></i>
                </button>
                <div class="search-box d-none d-md-block">
                    <div class="input-group input-group-flat border rounded-pill px-2 bg-light" style="width: 300px;">
                        <span class="input-group-text bg-transparent border-0 pe-2">
                            <i class="ti ti-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control bg-transparent border-0 small py-2" placeholder="Cari data atau fitur...">
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Notifications -->
                <div class="dropdown">
                    <a href="#" class="btn btn-light btn-icon border-0 rounded-circle position-relative" data-bs-toggle="dropdown">
                        <i class="ti ti-bell fs-3"></i>
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-white rounded-circle"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0 py-0" style="width: 320px;">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold">Notifikasi Terakhir</h6>
                            <a href="#" class="small text-decoration-none">Tandai baca</a>
                        </div>
                        <div class="overflow-auto" style="max-height: 300px;">
                            <div class="p-3 text-center text-muted small">Tidak ada notifikasi baru</div>
                        </div>
                        <div class="p-2 border-top text-center">
                            <a href="#" class="small text-decoration-none fw-bold">Lihat Semua</a>
                        </div>
                    </div>
                </div>

                <!-- User Profile -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none gap-3 ps-3 border-start" data-bs-toggle="dropdown">
                        <div class="text-end d-none d-lg-block">
                            <div class="fw-bold text-dark user-name" style="font-size: 14px; line-height: 1.2;">Admin</div>
                            <div class="text-muted user-role" style="font-size: 11px;">SUPER USER</div>
                        </div>
                        <div class="position-relative">
                            <img src="{{ asset('admin-assets/images/avatar/avatar-default.jpg') }}" 
                                 class="avatar avatar-md rounded-circle border shadow-sm user-avatar" 
                                 style="width: 42px; height: 42px; object-fit: cover;"
                                 onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=random'">
                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-white rounded-circle" title="Online"></span>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow border-0" style="min-width: 200px;">
                        <div class="dropdown-header text-uppercase text-muted fw-bold small" style="font-size: 10px;">Pengaturan Akun</div>
                        <a class="dropdown-item py-2 d-flex align-items-center" href="#">
                            <i class="ti ti-user-circle me-3 fs-3 text-secondary"></i>
                            <span>Profil Saya</span>
                        </a>
                        <a class="dropdown-item py-2 d-flex align-items-center" href="#">
                            <i class="ti ti-settings me-3 fs-3 text-secondary"></i>
                            <span>Keamanan</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item py-2 d-flex align-items-center text-danger logout-trigger" href="#" id="topbarLogoutBtn">
                            <i class="ti ti-logout me-3 fs-3"></i>
                            <span class="fw-bold">Keluar Sekarang</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
