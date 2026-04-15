<aside class="sidebar position-fixed top-0 start-0 h-100 bg-white border-end shadow-sm" style="width: 260px; z-index: 1050;">
    <div class="logo-area d-flex align-items-center px-4 py-4 border-bottom">
        <svg width="34" height="34" viewBox="0 0 62 67" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2">
            <path d="M30.604 66.378L0.00805664 48.1582V35.7825L30.604 54.0023V66.378Z" fill="#302C4D"/>
            <path d="M61.1996 48.1582L30.604 66.378V54.0023L61.1996 35.7825V48.1582Z" fill="#E66239"/>
            <path d="M30.5955 0L0 18.2198V30.5955L30.5955 12.3757V0Z" fill="#657E92"/>
            <path d="M61.191 18.2198L30.5955 0V12.3757L61.191 30.5955V18.2198Z" fill="#A3B2BE"/>
            <path d="M30.604 48.8457L0.00805664 30.6259V18.2498L30.604 36.47V48.8457Z" fill="#302C4D"/>
            <path d="M61.1996 30.6259L30.604 48.8457V36.47L61.1996 18.2498V30.6259Z" fill="#E66239"/>
        </svg>
        <div class="logo-text">
            <div class="fw-bold fs-4 text-dark line-height-1">AbsenKan</div>
            <div class="text-orange small fw-bold" style="font-size: 10px; letter-spacing: 1px;">MANAGEMENT</div>
        </div>
    </div>

    <div class="nav-area py-3 px-3">
        <div class="sidebar-label text-uppercase text-muted x-small fw-bold mb-3 px-3" style="font-size: 10px; letter-spacing: 1px;">Menu Utama</div>
        
        <ul class="nav flex-column gap-1">
            <li class="nav-item" data-role="admin,spv,hr">
                <a href="/admin/dashboard" class="nav-link py-2 px-3 rounded-3 d-flex align-items-center {{ Request::is('admin/dashboard') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}">
                    <i class="ti ti-dashboard fs-4 me-3"></i>
                    <span class="nav-text fw-medium">Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item" data-role="admin,hr">
                <a href="/admin/employees" class="nav-link py-2 px-3 rounded-3 d-flex align-items-center {{ Request::is('admin/employees') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}">
                    <i class="ti ti-users fs-4 me-3"></i>
                    <span class="nav-text fw-medium">Karyawan</span>
                </a>
            </li>

            <li class="nav-item" data-role="admin,spv,hr">
                <a href="/admin/attendance" class="nav-link py-2 px-3 rounded-3 d-flex align-items-center {{ Request::is('admin/attendance') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}">
                    <i class="ti ti-calendar-check fs-4 me-3"></i>
                    <span class="nav-text fw-medium">Presensi</span>
                </a>
            </li>

            <li class="nav-item" data-role="admin,spv,hr">
                <a href="/admin/leave" class="nav-link py-2 px-3 rounded-3 d-flex align-items-center {{ Request::is('admin/leave') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}">
                    <i class="ti ti-mailbox fs-4 me-3"></i>
                    <span class="nav-text fw-medium">Izin & Cuti</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-label text-uppercase text-muted x-small fw-bold mt-5 mb-3 px-3" style="font-size: 10px; letter-spacing: 1px;" data-role="admin">Pengaturan</div>
        
        <ul class="nav flex-column gap-1" data-role="admin">
            <li class="nav-item">
                <a href="/admin/locations" class="nav-link py-2 px-3 rounded-3 d-flex align-items-center {{ Request::is('admin/locations') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}">
                    <i class="ti ti-map-pin fs-4 me-3"></i>
                    <span class="nav-text fw-medium">Lokasi Kantor</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="/admin/schedules" class="nav-link py-2 px-3 rounded-3 d-flex align-items-center {{ Request::is('admin/schedules') ? 'active bg-primary text-white shadow-sm' : 'text-secondary' }}">
                    <i class="ti ti-clock fs-4 me-3"></i>
                    <span class="nav-text fw-medium">Jadwal Shift</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer position-absolute bottom-0 start-0 w-100 p-4 border-top bg-light-subtle">
        <a href="#" id="logoutBtn" class="btn btn-outline-danger w-100 rounded-pill d-flex align-items-center justify-content-center">
            <i class="ti ti-logout me-2"></i> Keluar
        </a>
    </div>
</aside>
