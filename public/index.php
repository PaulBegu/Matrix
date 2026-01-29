<?php
/**
 * Matrix Application - Main Entry Point
 * Login system using sys_users (similar to docai)
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/common.php';
require_once __DIR__ . '/../src/auth.php';

// Initialize directories
ensureDirs();

// Initialize user session (capture user_id from GET)
initUserSession();

// Get active user info
$activeUserId = getActiveUserId();
$userDisplayName = $activeUserId ? getUserDisplayName($activeUserId) : '';

// Prepare display values
$isLoggedIn = $activeUserId !== null;
$displayLabel = $userDisplayName ?: ($activeUserId ? "UserID: {$activeUserId}" : 'Neautentificat');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matrix</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #d6d6dbff 0%, #f2f3f5ff 50%, #eaedf0ff 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .user-badge {
            position: fixed;
            top: 16px;
            left: 16px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .user-badge.logged-in {
            background: rgba(34, 197, 94, 0.2);
            border-color: rgba(34, 197, 94, 0.4);
        }
        
        .user-badge.not-logged {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.4);
        }
        
        .user-icon {
            font-size: 1.2rem;
        }
        
        .main-container {
            text-align: center;
            padding: 40px;
        }
        
        .test-title {
            font-size: 8rem;
            font-weight: 900;
            letter-spacing: 20px;
            text-transform: uppercase;
            background: linear-gradient(90deg, #00d9ff, #00ff88, #ff00aa, #00d9ff);
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s ease infinite;
            text-shadow: 0 0 40px rgba(0, 217, 255, 0.3);
            margin-bottom: 30px;
        }
        
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .user-display {
            font-size: 1.8rem;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 20px;
            padding: 20px 40px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .user-display strong {
            color: #00d9ff;
        }
        
        .info-text {
            margin-top: 40px;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.5);
        }
        
        .info-text code {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 4px;
            font-family: 'Consolas', monospace;
        }
    </style>
</head>
<body>

    
    <!-- Main Content -->
    <div class="main-container">
        <h1 class="test-title">Skill Matrix</h1>
        
        <div class="user-display">
            <?php if ($isLoggedIn): ?>
                Utilizator logat: <strong><?php echo h($displayLabel); ?></strong>
            <?php else: ?>
                <span style="color: #ef4444;">Nu ești autentificat</span>
            <?php endif; ?>
        </div>
        
        <!-- <p class="info-text">
            Pentru autentificare, accesează cu parametrul: <code>?user_id=XXX</code>
        </p> -->
    </div>
</body>
</html>
