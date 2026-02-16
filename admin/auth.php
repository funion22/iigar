<?php

//$adminPassword = $_SERVER['ADMIN_PASSWORD'] ?? '';
$adminPassword = $_SERVER['ADMIN_PASSWORD'] ?? '@llysas888';
$cookieName = 'pagifier_auth';
$cookieToken = 'pgf_' . hash('sha256', 'pagifier_secret_key_2024');

// ── PROCESAR LOGIN ──
if (isset($_POST['admin_password'])) {
    if ($adminPassword !== '' && $_POST['admin_password'] === $adminPassword) {
        setcookie($cookieName, $cookieToken, time() + 86400, '/');
        $_COOKIE[$cookieName] = $cookieToken;
    } else {
        $login_error = true;
    }
}

// ── PROCESAR LOGOUT ──
if (isset($_GET['logout'])) {
    setcookie($cookieName, '', time() - 3600, '/');
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ── VERIFICAR ACCESO ──
$isLogged = isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === $cookieToken;

if (!$isLogged) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Pagifier</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', -apple-system, sans-serif;
                background: #0f1117;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                color: #e1e1e6;
            }
            .login-box {
                background: #1a1b23;
                border: 1px solid #2a2b35;
                border-radius: 12px;
                padding: 40px;
                width: 100%;
                max-width: 380px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            }
            .login-box h1 {
                font-size: 22px;
                font-weight: 600;
                margin-bottom: 8px;
                text-align: center;
            }
            .login-box p {
                font-size: 14px;
                color: #6c6c7a;
                text-align: center;
                margin-bottom: 30px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group label {
                display: block;
                font-size: 13px;
                font-weight: 500;
                margin-bottom: 6px;
                color: #9d9daa;
            }
            .form-group input {
                width: 100%;
                padding: 12px 14px;
                background: #0f1117;
                border: 1px solid #2a2b35;
                border-radius: 8px;
                color: #e1e1e6;
                font-size: 15px;
                outline: none;
                transition: border-color 0.2s;
            }
            .form-group input:focus {
                border-color: #5a5af0;
            }
            .btn-login {
                width: 100%;
                padding: 12px;
                background: #5a5af0;
                color: #fff;
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            .btn-login:hover {
                background: #4a4ae0;
            }
            .error {
                background: #2d1518;
                border: 1px solid #5c2b2e;
                color: #f87171;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 13px;
                margin-bottom: 20px;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>🔒 Pagifier Admin</h1>
            <p>Introduce la contraseña para acceder</p>
            <?php if (!empty($login_error)): ?>
            <div class="error">Contraseña incorrecta</div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="admin_password" autofocus required>
                </div>
                <button type="submit" class="btn-login">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
