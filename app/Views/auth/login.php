<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AZTEAM CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/azteamcrm/assets/css/login.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <!-- Logo Section - Outside and above the card -->
        <div class="logo-section">
            <img src="/azteamcrm/assets/images/logo-full.svg" alt="AZTEAM" class="logo-main">
            <p class="tagline">Enterprise Resource Planning System</p>
        </div>
        
        <!-- Login Card -->
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form action="/azteamcrm/login/submit" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                </div>
            </form>
            
            <div class="text-center mt-4 copyright">
                <small>&copy; <?= date('Y') ?> AZTEAM. All rights reserved.</small>
            </div>
        </div>
    </div>
</body>
</html>