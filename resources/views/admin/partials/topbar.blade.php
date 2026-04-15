<nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center">
        <button id="toggleBtn" class="d-none d-lg-inline-flex btn btn-light btn-icon btn-sm">
            <i class="ti ti-layout-sidebar-left-expand"></i>
        </button>
        <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none me-2">
            <i class="ti ti-layout-sidebar-left-expand"></i>
        </button>
    </div>

    <ul class="list-unstyled d-flex align-items-center mb-0 gap-3">
        <!-- Notifications -->
        <li class="dropdown">
            <a class="position-relative btn btn-light btn-sm rounded-circle p-2" data-bs-toggle="dropdown" href="#" role="button">
                <i class="ti ti-bell fs-5"></i>
                <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="margin-top: 5px; margin-left: -5px;">0</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-2" style="width: 280px;">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold small">Notifications</span>
                    <span class="badge bg-primary-lt text-primary small" id="pendingCountText">0 New</span>
                </div>
                <div class="list-group list-group-flush" id="notificationList" style="max-height: 300px; overflow-y: auto;">
                    <div class="p-4 text-center text-muted small">Memuat...</div>
                </div>
                <div class="p-2 text-center border-top">
                    <a href="{{ url('/admin/leave') }}" class="small text-primary text-decoration-none fw-bold">Lihat semua pengajuan</a>
                </div>
            </div>
        </li>

        <!-- User Profile -->
        <li class="dropdown">
            <a href="#" class="d-flex align-items-center border-0 bg-transparent p-0" data-bs-toggle="dropdown">
                <img src="{{ asset('admin-assets/images/avatar/avatar-1.jpg') }}" alt="User" class="rounded-circle user-avatar shadow-sm" style="width: 35px; height: 35px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=random'">
            </a>
            <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-2" style="min-width: 220px;">
                <div class="p-3 border-bottom d-flex align-items-center gap-3">
                    <img src="{{ asset('admin-assets/images/avatar/avatar-1.jpg') }}" class="rounded-circle user-avatar" style="width: 45px; height: 45px; object-fit: cover;" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=random'">
                    <div>
                        <h6 class="mb-0 small fw-bold user-name text-truncate" style="max-width: 120px;">User</h6>
                        <p class="mb-0 text-muted user-role" style="font-size: 11px;">Administrator</p>
                    </div>
                </div>
                <div class="p-2">
                    <a class="dropdown-item rounded-2 small" href="{{ url('/admin/dashboard') }}">Dashboard</a>
                    <hr class="dropdown-divider my-1">
                    <a class="dropdown-item rounded-2 small text-danger" href="#" id="logoutBtn">Logout</a>
                </div>
            </div>
        </li>
    </ul>
</nav>
