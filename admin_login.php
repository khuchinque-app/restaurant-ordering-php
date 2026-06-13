<?php
require_once __DIR__ . '/auth.php';
session_init();

$existing = get_auth_user();
if ($existing) {
    if ($existing['role'] === 'SUPERADMIN') {
        header('Location: /superadmin/index.php'); exit;
    }
    if (in_array($existing['role'], ['ADMIN', 'MANAGER'])) {
        header('Location: /admin/index.php'); exit;
    }
    auth_logout();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $result = auth_login($email, $password);
        if ($result === false) {
            $error = 'Invalid email or password.';
        } else {
            $role = $result['user']['role'];
            if ($role === 'SUPERADMIN') {
                header('Location: /superadmin/index.php'); exit;
            } elseif (in_array($role, ['ADMIN', 'MANAGER'])) {
                header('Location: /admin/index.php'); exit;
            } else {
                auth_logout();
                $error = 'This portal is for staff only.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 32px rgba(0,0,0,.10);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
        }
        .login-logo {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: .4rem;
        }
        h1 {
            text-align: center;
            font-size: 1.35rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.6rem;
        }
        .alert {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            border-radius: 7px;
            padding: .75rem 1rem;
            margin-bottom: 1.1rem;
            font-size: .875rem;
        }
        .form-group { margin-bottom: 1rem; }
        label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: .3rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        input[type=email], input[type=password] {
            width: 100%;
            padding: .65rem .9rem;
            border: 1.5px solid #cbd5e1;
            border-radius: 7px;
            font-size: .95rem;
            color: #1e293b;
            background: #f8fafc;
            transition: border-color .15s;
        }
        input:focus {
            outline: none;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99,102,241,.12);
        }
        .btn-login {
            width: 100%;
            padding: .75rem;
            background: #4f46e5;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: .6rem;
            transition: background .15s;
        }
        .btn-login:hover { background: #4338ca; }
        .divider {
            text-align: center;
            color: #94a3b8;
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            margin: 1.6rem 0 1rem;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 30%;
            height: 1px;
            background: #e2e8f0;
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }
        .storefront-links { display: flex; gap: .75rem; }
        .storefront-btn {
            flex: 1;
            padding: .65rem .5rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            font-size: .9rem;
            color: #374151;
            background: #f8fafc;
            transition: background .15s, border-color .15s;
            display: block;
        }
        .storefront-btn:hover { background: #f1f5f9; }
        .storefront-btn.aseng  { color: #0f766e; border-color: #99f6e4; }
        .storefront-btn.aseng:hover  { background: #f0fdf4; }
        .storefront-btn.tittil { color: #7e22ce; border-color: #e9d5ff; }
        .storefront-btn.tittil:hover { background: #faf5ff; }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-logo">&#127974;</div>
    <h1>Staff Login</h1>

    <?php if ($error): ?>
        <div class="alert"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="divider">Customer Storefronts</div>
    <div class="storefront-links">
        <a href="#" id="btn-aseng"  class="storefront-btn aseng">&#127870; Aseng</a>
        <a href="#" id="btn-tittil" class="storefront-btn tittil">&#127858; Tittil</a>
    </div>
</div>
<script>
    const h = window.location.hostname;
    const p = window.location.protocol;
    document.getElementById('btn-aseng').href  = p + '//' + h + ':7504/aseng/';
    document.getElementById('btn-tittil').href = p + '//' + h + ':7505/tittil/';
</script>
</body>
</html>
