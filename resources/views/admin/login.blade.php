<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in - AbsenKan</title>
    
    <link rel="icon" type="image/png" href="{{ asset('admin-assets/images/favicon_io/favicon-32x32.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { background-color: #fafbfc; font-family: 'Public Sans', sans-serif; color: #444; }
        .page-center { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 450px; background: #fff; border: 1px solid #e1e4e8; border-radius: 8px; padding: 40px; }
        .brand-logo-area { display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 25px; }
        .brand-text-content { text-align: left; }
        .brand-name { font-size: 20px; font-weight: 700; color: #1e293b; line-height: 1.1; }
        .brand-subtitle { font-size: 11px; color: #e66239; font-weight: 600; }
        .form-title { font-size: 16px; font-weight: 600; color: #1e293b; margin-bottom: 25px; text-align: center; }
        .form-label { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 6px; }
        .form-control { border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; font-size: 14px; }
        .form-control:focus { border-color: #e66239; box-shadow: none; }
        .btn-primary { background-color: #e66239; border: none; border-radius: 6px; padding: 10px; font-weight: 600; font-size: 14px; margin-top: 5px; }
        .btn-primary:hover { background-color: #d15632; }
        .link-orange { color: #e66239; font-weight: 600; text-decoration: none; font-size: 12px; }
        .link-orange:hover { text-decoration: underline; }
        .footer-text { text-align: center; margin-top: 20px; font-size: 13px; color: #666; }
    </style>
</head>
<body>
    <div class="page-center">
        <div class="login-card">
            <div class="brand-logo-area">
                <svg width="34" height="34" viewBox="0 0 62 67" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M30.604 66.378L0.00805664 48.1582V35.7825L30.604 54.0023V66.378Z" fill="#302C4D"/>
                    <path d="M61.1996 48.1582L30.604 66.378V54.0023L61.1996 35.7825V48.1582Z" fill="#E66239"/>
                    <path d="M30.5955 0L0 18.2198V30.5955L30.5955 12.3757V0Z" fill="#657E92"/>
                    <path d="M61.191 18.2198L30.5955 0V12.3757L61.191 30.5955V18.2198Z" fill="#A3B2BE"/>
                    <path d="M30.604 48.8457L0.00805664 30.6259V18.2498L30.604 36.47V48.8457Z" fill="#302C4D"/>
                    <path d="M61.1996 30.6259L30.604 48.8457V36.47L61.1996 18.2498V30.6259Z" fill="#E66239"/>
                </svg>
                <div class="brand-text-content">
                    <div class="brand-name">AbsenKan</div>
                    <div class="brand-subtitle">Admin Dashboard</div>
                </div>
            </div>
            
            <h1 class="form-title">Masuk ke Dashboard</h1>
            
            <form id="loginForm" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Username / Email</label>
                    <input type="text" name="username" class="form-control" placeholder="Masukkan ID Anda" required>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">Password</label>
                    </div>
                    <input type="password" name="password" class="form-control" placeholder="******" required>
                </div>
                
                <button type="submit" id="loginBtn" class="btn btn-primary w-100 shadow-sm mt-2">
                    Masuk Sekarang
                </button>
            </form>
            
            <div class="footer-text mt-4 pt-3 border-top small">
                Masalah akses? <a href="mailto:admin@absenkan.com" class="link-orange">Hubungi IT Support</a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- We inject the dashboard assets path here for JS -->
    <script>
        window.ADMIN_ASSETS_PATH = "{{ asset('admin-assets/') }}";
    </script>
    <script src="{{ asset('admin-assets/js/auth.js') }}" type="module"></script>
</body>
</html>
