import { api } from "./api.js";

document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");

    // Check if we are on the login page in Laravel way
    const IS_LOGIN_PAGE = window.location.pathname.includes("/admin/login");

    // Path protection map: URL Path -> Required Permission(s)
    const PATH_PROTECTION = {
        "/admin/dashboard": "view_dashboard",
        "/admin/employees": "view_employee",
        "/admin/locations": "manage_location",
        "/admin/schedules": "manage_schedule",
        "/admin/roles": "view_roles",
        "/admin/leave": "view_leave",
        "/admin/attendance": "view_attendance",
    };

    if (loginForm && IS_LOGIN_PAGE) {
        loginForm.addEventListener("submit", async (e) => {
            e.preventDefault();

            const username = loginForm.querySelector('[name="username"]').value;
            const password = loginForm.querySelector('[name="password"]').value;
            const submitBtn = document.getElementById("loginBtn");

            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML =
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Masuk...';

            try {
                const response = await api.login(username, password);
                if (response.success) {
                    api.notify("Login berhasil! Mengalihkan...");

                    localStorage.setItem("session_token", response.data.access_token);
                    localStorage.setItem(
                        "user_data",
                        JSON.stringify(response.data.user),
                    );

                    setTimeout(() => {
                        window.location.href = "/admin/dashboard";
                    }, 800);
                }
            } catch (error) {
                api.notify(error.message, "error");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    // Check if logged in on other pages
    if (!IS_LOGIN_PAGE) {
        const token = localStorage.getItem("session_token");
        if (!token) {
            window.location.href = "/admin/login";
        } else {
            // Function to sync latest user data and refresh UI
            const syncUserData = async () => {
                try {
                    const response = await api.request("/employee/profile");
                    if (response.success) {
                        localStorage.setItem(
                            "user_data",
                            JSON.stringify(response.data),
                        );
                        applyVisibility(response.data);
                    }
                } catch (error) {
                    console.error("Failed to sync profile:", error);
                }
            };

            const applyVisibility = (userData) => {
                if (!userData) return;

                // SECURITY: Block regular employees from Admin Dashboard
                if (userData.role === "employee") {
                    localStorage.removeItem("session_token");
                    localStorage.removeItem("user_data");
                    window.location.href = "/admin/login?error=unauthorized";
                    return;
                }

                const nameElements = document.querySelectorAll(".user-name");
                const roleElements = document.querySelectorAll(".user-role");
                const avatarElements =
                    document.querySelectorAll(".user-avatar");

                nameElements.forEach((el) => (el.textContent = userData.name));
                roleElements.forEach((el) => {
                    const role = userData.role || "Administrator";
                    el.textContent = role.toUpperCase();
                });

                if (userData.profile_photo) {
                    avatarElements.forEach(
                        (el) => (el.src = userData.profile_photo),
                    );
                }

                // Hybrid Sidebar Visibility Logic
                const userPermissions = userData.permissions || [];
                const userRole = userData.role;

                console.log("Active Role:", userRole);
                console.log("Active Permissions:", userPermissions);

                document
                    .querySelectorAll(
                        ".sidebar li[data-role], .sidebar li[data-permission]",
                    )
                    .forEach((el) => {
                        if (userRole === "administrator") {
                            el.style.display = "";
                            return;
                        }

                        const requiredRoles = el.dataset.role
                            ? el.dataset.role.split(",").map(r => r.trim())
                            : [];
                        const requiredPermission = el.dataset.permission;

                        const hasRoleMatch = requiredRoles.length === 0 || requiredRoles.includes(userRole);
                        
                        let hasPermissionMatch = true;
                        if (requiredPermission) {
                            if (userPermissions.includes('all')) {
                                hasPermissionMatch = true;
                            } else {
                                const permsArr = requiredPermission.split(",").map(p => p.trim());
                                hasPermissionMatch = permsArr.some((p) =>
                                    userPermissions.includes(p)
                                );
                            }
                        }

                        // Decision: Satisfy BOTH if both are provided
                        if (hasRoleMatch && hasPermissionMatch) {
                            el.style.display = "";
                        } else {
                            el.style.display = "none";
                        }
                    });

                // Signal that auth logic is finished to show allowed items (removes flicker protection)
                const sidebar = document.getElementById("sidebar");
                if (sidebar) sidebar.classList.add("auth-loaded");

                // Path protection: Redirect to 404 if accessing a URL without permissions
                const currentPath = window.location.pathname;

                // SPECIAL FIX: If on dashboard but unauthorized, find first available page
                if (currentPath === '/admin/dashboard' && userRole !== 'administrator' && !userPermissions.includes('view_dashboard') && !userPermissions.includes('all')) {
                    const firstSafePath = Object.entries(PATH_PROTECTION).find(([path, perm]) => 
                        userPermissions.includes(perm) || userPermissions.includes('all')
                    );
                    
                    if (firstSafePath) {
                        window.location.replace(firstSafePath[0]);
                        return;
                    }
                }

                for (const [path, permission] of Object.entries(
                    PATH_PROTECTION,
                )) {
                    // Check if current page is exactly this path or a sub-page
                    if (
                        currentPath === path ||
                        currentPath.startsWith(path + "/")
                    ) {
                        if (
                            userRole !== "administrator" &&
                            !userPermissions.includes(permission) &&
                            !userPermissions.includes('all')
                        ) {
                            console.warn(
                                "Unauthorized Path Access:",
                                currentPath,
                            );
                            window.location.replace("/admin/access-denied-404");
                        }
                    }
                }
            };

            // Initial view from cache for speed
            const cachedData = JSON.parse(localStorage.getItem("user_data"));
            if (cachedData) applyVisibility(cachedData);

            // Fetch latest data from server to sync permissions
            syncUserData();
        }
    }

    // Logout functionality
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", async (e) => {
            e.preventDefault();

            const confirmed = await api.confirm({
                title: "Konfirmasi Keluar",
                message:
                    "Apakah Anda yakin ingin mengakhiri sesi dashboard ini?",
                confirmText: "Ya, Keluar",
                cancelText: "Tetap di Sini",
                type: "warning",
            });

            if (confirmed) {
                localStorage.removeItem("session_token");
                localStorage.removeItem("user_data");
                api.notify("Sesi telah diakhiri", "info");
                setTimeout(() => {
                    window.location.href = "/admin/login";
                }, 500);
            }
        });
    }
});
