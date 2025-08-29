<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'AZTEAM CRM') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="/azteamcrm/assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="d-md-block sidebar-fixed expanded" id="sidebar" data-bs-toggle="sidebar">
                <div class="position-sticky pt-3">
                    <div class="sidebar-logo-container">
                        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar" aria-label="Toggle Sidebar">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <div class="logo-content">
                            <img src="/azteamcrm/assets/images/logo-icon.svg" alt="AZTEAM" class="logo-sidebar">
                            <span class="logo-text nav-text">AZTEAM</span>
                        </div>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/dashboard" data-bs-toggle="tooltip" data-bs-placement="right" title="Dashboard">
                                <i class="bi bi-speedometer2"></i> <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/users" data-bs-toggle="tooltip" data-bs-placement="right" title="Users">
                                <i class="bi bi-people"></i> <span class="nav-text">Users</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/profile" data-bs-toggle="tooltip" data-bs-placement="right" title="My Profile">
                                <i class="bi bi-person-circle"></i> <span class="nav-text">My Profile</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/customers" data-bs-toggle="tooltip" data-bs-placement="right" title="Customers">
                                <i class="bi bi-person-badge"></i> <span class="nav-text">Customers</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/orders" data-bs-toggle="tooltip" data-bs-placement="right" title="Orders">
                                <i class="bi bi-cart3"></i> <span class="nav-text">Orders</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/production" data-bs-toggle="tooltip" data-bs-placement="right" title="Production">
                                <i class="bi bi-gear"></i> <span class="nav-text">Production</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/production/supplier-tracking" data-bs-toggle="tooltip" data-bs-placement="right" title="Supplier Tracking">
                                <i class="bi bi-truck"></i> <span class="nav-text">Supplier Tracking</span>
                            </a>
                        </li>
                        
                        <!-- Administrator-only sections -->
                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/settings" data-bs-toggle="tooltip" data-bs-placement="right" title="System Settings">
                                <i class="bi bi-gear"></i> <span class="nav-text">Settings</span>
                            </a>
                        </li>
                        <?php /* Commented out - Controllers/Views not implemented yet
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/reports" data-bs-toggle="tooltip" data-bs-placement="right" title="Reports">
                                <i class="bi bi-graph-up"></i> <span class="nav-text">Reports</span>
                            </a>
                        </li>
                        */ ?>
                        <?php endif; ?>
                        
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="/azteamcrm/logout" data-bs-toggle="tooltip" data-bs-placement="right" title="Logout">
                                <i class="bi bi-box-arrow-right"></i> <span class="nav-text">Logout</span>
                            </a>
                        </li>
                    </ul>
                    <div class="px-3 mt-5 text-white-50 nav-text">
                        <small>Logged in as:<br><?= htmlspecialchars($_SESSION['full_name']) ?></small>
                    </div>
                </div>
            </nav>

            <!-- Mobile Header - Visible only on mobile devices -->
            <div class="mobile-header d-block d-md-none" id="mobileHeader">
                <button class="mobile-menu-button" id="mobileMenuButton" type="button" title="Open Menu" aria-label="Open Navigation Menu">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
                <div class="mobile-logo">
                    <img src="/azteamcrm/assets/images/logo-icon.svg" alt="AZTEAM" class="mobile-logo-img">
                    <span class="mobile-logo-text">AZTEAM</span>
                </div>
                <div class="mobile-user-info">
                    <span class="mobile-user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></span>
                </div>
            </div>

            <!-- Mobile Backdrop - For closing sidebar when clicking outside -->
            <div class="mobile-backdrop d-none" id="mobileBackdrop"></div>

            <!-- Main content -->
            <main class="main-content px-md-4" id="mainContent">
                <div class="pt-3 pb-2 mb-3">
    <?php endif; ?>