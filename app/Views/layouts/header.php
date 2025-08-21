<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'AZTEAM CRM' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/azteamcrm/assets/css/app.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <h5 class="text-white px-3 mb-3">AZTEAM CRM</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/dashboard">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <?php /* Commented out - Controllers/Views not implemented yet
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/orders">
                                <i class="bi bi-cart3"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/production">
                                <i class="bi bi-gear"></i> Production
                            </a>
                        </li>
                        <?php if ($_SESSION['user_role'] === 'administrator'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/reports">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/azteamcrm/users">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                        <?php endif; ?>
                        */ ?>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-danger" href="/azteamcrm/logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                    <div class="px-3 mt-5 text-white-50">
                        <small>Logged in as:<br><?= htmlspecialchars($_SESSION['full_name']) ?></small>
                    </div>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="pt-3 pb-2 mb-3">
    <?php endif; ?>