<?php
echo 
'<header class="app-header admin-header">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <a href="admin_dashboard.php" class="logo admin-logo">
                <i class="bi bi-person-workspace"></i>
                <span class="logo-text">Admin Panel</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <a href="admin_dashboard.php" class="dashboard-btn">
                    <i class="bi bi-house-door-fill"></i>
                    <span>Dashboard</span>
                </a>
                <div class="dropdown">
                    <div class="admin-profile dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" role="button" tabindex="0">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
                        <li class="dropdown-header">
                            <i class="bi bi-shield-check"></i>
                            <div class="fw-bold">Administrator</div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="admin_users.php"><i class="bi bi-people-fill"></i>Manage Users</a></li>
                        <li><a class="dropdown-item" href="admin_staff.php"><i class="bi bi-person-workspace"></i>Manage Staff</a></li>
                        <li><a class="dropdown-item" href="admin_claim.php"><i class="bi bi-clipboard-check"></i>Claims</a></li>
                        <li><a class="dropdown-item" href="admin_report.php"><i class="bi bi-file-earmark-text"></i>Reports</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger logout-item" href="../logout.php"><i class="bi bi-box-arrow-right"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
.admin-header {
    background: linear-gradient(135deg, #3a0ca3 0%, #7209b7 100%);
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.admin-logo {
    color: #fff !important;
    text-decoration: none;
    font-size: 1.3rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    transition: all 0.3s ease;
}

.admin-logo:hover {
    opacity: 0.9;
    transform: translateX(3px);
}

.admin-logo i {
    font-size: 1.5rem;
}

.dashboard-btn {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 0.9rem;
}

.dashboard-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
    transform: translateY(-1px);
}

.admin-profile {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.15) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.admin-profile i {
    font-size: 1.3rem;
    color: #fff;
}

.admin-profile:hover {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.35) 0%, rgba(255, 255, 255, 0.25) 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    border-color: rgba(255, 255, 255, 0.6);
}

.dropdown-menu {
    border: none;
    border-radius: 12px;
    padding: 0.5rem 0;
    min-width: 220px;
    margin-top: 0.5rem;
}

.dropdown-header {
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #3a0ca3 0%, #7209b7 100%);
    color: white;
    margin: -0.5rem 0 0 0;
    border-radius: 12px 12px 0 0;
    text-align: center;
}

.dropdown-header i {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 0.3rem;
}

.dropdown-item {
    padding: 0.6rem 1.5rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f8f9fa;
    padding-left: 1.8rem;
}

.dropdown-item i {
    font-size: 1rem;
    width: 18px;
}

.logout-item {
    font-weight: 500;
}

.logout-item:hover {
    background: #fff5f5 !important;
    color: #dc3545 !important;
}
</style>';
?>