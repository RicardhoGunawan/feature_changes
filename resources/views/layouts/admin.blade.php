<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin Dashboard') - AbsenKan</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('admin-assets/images/favicon_io/favicon-32x32.png') }}">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('admin-assets/css/style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        .sidebar, .topbar, .content { transition: all 0.3s ease; }
        .sidebar-collapsed .nav-text, .sidebar-collapsed .sidebar-label, .sidebar-collapsed .logo-text { display: none !important; }
        .sidebar-collapsed .sidebar { width: 70px !important; }
        .sidebar-collapsed main.content, .sidebar-collapsed .topbar { margin-left: 70px !important; width: calc(100% - 70px) !important; }
        .sidebar-collapsed .nav-link { justify-content: center; padding-left: 0 !important; }
        .sidebar-collapsed .logo-area { justify-content: center !important; padding-left: 0 !important; }

        /* Mobile Styles */
        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                left: -250px;
                top: 0;
                bottom: 0;
                width: 250px;
                z-index: 1050;
                background: #fff;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            .sidebar.mobile-show {
                left: 0;
            }
            main.content, .topbar {
                margin-left: 0 !important;
                width: 100% !important;
            }
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            .overlay.show {
                display: block;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-light">

    @include('admin.partials.sidebar')
    @include('admin.partials.topbar')

    <main id="content" class="content pt-5 mt-5">
        <div class="container-fluid py-4">
            @yield('content')
            
            <footer class="text-center py-4 mt-auto text-secondary small">
                <p class="mb-0">Copyright © 2026 Admin Dashboard Absensi. Developed with ❤️</p>
            </footer>
        </div>
    </main>

    <div id="overlay" class="overlay"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleBtn');
            const mobileBtn = document.getElementById('mobileBtn');
            const overlay = document.getElementById('overlay');

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    document.body.classList.toggle('sidebar-collapsed');
                });
            }

            if (mobileBtn) {
                mobileBtn.addEventListener('click', () => {
                    sidebar.classList.add('mobile-show');
                    overlay.classList.add('show');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('mobile-show');
                    overlay.classList.remove('show');
                });
            }
        });
    </script>

    <script src="{{ asset('admin-assets/js/api.js') }}" type="module"></script>
    <script src="{{ asset('admin-assets/js/auth.js') }}" type="module"></script>
    @stack('scripts')
</body>
</html>
