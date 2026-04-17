<aside id="sidebar" class="sidebar">

    <div class="logo-area">
        <a href="{{ url('/admin') }}" class="d-inline-flex align-items-center text-decoration-none text-dark">
            <svg width="24" height="24" viewBox="0 0 62 67" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M30.604 66.378L0.00805664 48.1582V35.7825L30.604 54.0023V66.378Z" fill="#302C4D" />
                <path d="M61.1996 48.1582L30.604 66.378V54.0023L61.1996 35.7825V48.1582Z" fill="#E66239" />
                <path d="M30.5955 0L0 18.2198V30.5955L30.5955 12.3757V0Z" fill="#657E92" />
                <path d="M61.191 18.2198L30.5955 0V12.3757L61.191 30.5955V18.2198Z" fill="#A3B2BE" />
                <path d="M30.604 48.8457L0.00805664 30.6259V18.2498L30.604 36.47V48.8457Z" fill="#302C4D" />
                <path d="M61.1996 30.6259L30.604 48.8457V36.47L61.1996 18.2498V30.6259Z" fill="#E66239" />
            </svg>
            <span class="logo-text ms-2">
                <img src="{{ asset('admin-assets/images/logo.svg') }}" alt="Logo" style="height: 20px;">
            </span>
        </a>
    </div>
    <ul class="nav flex-column mt-3">
        <li class="px-4 py-2 sidebar-label" data-role="administrator,employee">
            <small class="text-secondary text-uppercase fw-bold"
                style="font-size: 10px; letter-spacing: 1px;">Main</small>
        </li>

        <li class="nav-item" data-role="administrator,employee">
            <a class="nav-link {{ Request::is('admin/dashboard') ? 'active' : '' }}"
                href="{{ url('/admin/dashboard') }}">
                <i class="ti ti-home"></i>
                <span class="nav-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item" data-role="administrator">
            <a class="nav-link {{ Request::is('admin/employees*') ? 'active' : '' }}"
                href="{{ url('/admin/employees') }}">
                <i class="ti ti-users"></i>
                <span class="nav-text">Karyawan</span>
            </a>
        </li>

        <li class="nav-item" data-role="administrator,employee">
            <a class="nav-link {{ Request::is('admin/attendance*') ? 'active' : '' }}"
                href="{{ url('/admin/attendance') }}">
                <i class="ti ti-calendar-check"></i>
                <span class="nav-text">Absensi</span>
            </a>
        </li>

        <li class="nav-item" data-role="administrator,employee">
            <a class="nav-link {{ Request::is('admin/leave') ? 'active' : '' }}" href="{{ url('/admin/leave') }}">
                <i class="ti ti-mail"></i>
                <span class="nav-text">Izin & Cuti</span>
            </a>
        </li>

        <li class="px-4 py-2 mt-2 sidebar-label" data-role="administrator">
            <small class="text-secondary text-uppercase fw-bold"
                style="font-size: 10px; letter-spacing: 1px;">HR Engine</small>
        </li>

        <li class="nav-item" data-role="administrator">
            <a class="nav-link {{ Request::is('admin/leave-policies*') ? 'active' : '' }}"
                href="{{ url('/admin/leave-policies') }}">
                <i class="ti ti-settings-automation"></i>
                <span class="nav-text">Kebijakan Cuti</span>
            </a>
        </li>

        <li class="nav-item" data-role="administrator">
            <a class="nav-link {{ Request::is('admin/approval-workflows*') ? 'active' : '' }}"
                href="{{ url('/admin/approval-workflows') }}">
                <i class="ti ti-git-merge"></i>
                <span class="nav-text">Alur Persetujuan</span>
            </a>
        </li>

        <li class="nav-item" data-role="administrator">
            <a class="nav-link {{ Request::is('admin/audit-logs*') ? 'active' : '' }}"
                href="{{ url('/admin/audit-logs') }}">
                <i class="ti ti-history"></i>
                <span class="nav-text">Riwayat Audit</span>
            </a>
        </li>

        <li class="px-4 py-2 mt-2 sidebar-label" data-role="administrator">
            <small class="text-secondary text-uppercase fw-bold"
                style="font-size: 10px; letter-spacing: 1px;">Master Data</small>
        </li>

        <li class="nav-item" data-role="administrator">
            <a class="nav-link {{ Request::is('admin/departments*') ? 'active' : '' }}"
                href="{{ url('/admin/departments') }}">
                <i class="ti ti-building-community"></i>
                <span class="nav-text">Departemen</span>
            </a>
        </li>

        <li class="nav-item" data-role="administrator">
            <a class="nav-link {{ Request::is('admin/positions*') ? 'active' : '' }}"
                href="{{ url('/admin/positions') }}">
                <i class="ti ti-hierarchy-2"></i>
                <span class="nav-text">Struktur Jabatan</span>
            </a>
        </li>

        <li class="px-4 py-2 mt-2 sidebar-label">
            <small class="text-secondary text-uppercase fw-bold"
                style="font-size: 10px; letter-spacing: 1px;">Settings</small>
        </li>

        <li data-role="administrator">
            <a class="nav-link {{ Request::is('admin/locations*') ? 'active' : '' }}"
                href="{{ url('/admin/locations') }}">
                <i class="ti ti-map-pin"></i>
                <span class="nav-text">Atur Lokasi</span>
            </a>
        </li>

        <li data-role="administrator">
            <a class="nav-link {{ Request::is('admin/schedules*') ? 'active' : '' }}"
                href="{{ url('/admin/schedules') }}">
                <i class="ti ti-clock"></i>
                <span class="nav-text">Jadwal Kerja</span>
            </a>
        </li>

        <li data-role="administrator">
            <a class="nav-link {{ Request::is('admin/holidays*') ? 'active' : '' }}"
                href="{{ url('/admin/holidays') }}">
                <i class="ti ti-calendar-event"></i>
                <span class="nav-text">Hari Libur</span>
            </a>
        </li>


    </ul>
</aside>