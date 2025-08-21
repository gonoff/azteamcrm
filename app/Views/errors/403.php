<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Forbidden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #000000 0%, #dc3545 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .error-container {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
        .error-description {
            font-size: 1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        .btn-back {
            background-color: white;
            color: #dc3545;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
        }
        .btn-back:hover {
            transform: scale(1.05);
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <div class="error-message">Access Forbidden</div>
        <div class="error-description">
            You don't have permission to access this resource.<br>
            Please contact your administrator if you believe this is an error.
        </div>
        <a href="/azteamcrm/dashboard" class="btn-back">Go to Dashboard</a>
    </div>
</body>
</html>