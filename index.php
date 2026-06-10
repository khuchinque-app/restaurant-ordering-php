<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/includes/activity.php';
session_init();

// If already logged in, redirect to the right place
$user = get_auth_user();
if ($user) {
    if ($user['role'] === 'SUPERADMIN') { header('Location: superadmin/index.php'); exit; }
    if (in_array($user['role'], ['ADMIN', 'MANAGER'])) { header('Location: admin/index.php'); exit; }
    header('Location: menu.php'); exit;
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
            log_activity($result['user'], 'LOGIN', 'User', $result['user']['id'], "Signed in from " . ($_SERVER['REMOTE_ADDR'] ?? ''));
            if ($role === 'SUPERADMIN')                        { header('Location: superadmin/index.php'); exit; }
            if (in_array($role, ['ADMIN', 'MANAGER']))         { header('Location: admin/index.php'); exit; }
            header('Location: menu.php'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; Restaurant System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f1f5f9; }
        .login-wrap { width: 100%; max-width: 400px; padding: 1rem; }
        .login-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 2.5rem 2rem; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .login-logo { text-align: center; font-size: 2.5rem; margin-bottom: .5rem; }
        .login-title { text-align: center; font-size: 1.4rem; font-weight: 700; margin-bottom: .25rem; }
        .login-sub { text-align: center; font-size: .875rem; color: #6b7280; margin-bottom: 2rem; }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">&#127860;</div>
        <div class="login-title">Restaurant System</div>
        <div class="login-sub">Sign in to continue</div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block" style="margin-top:.5rem">Sign In</button>
        </form>
    </div>
</div>
</body>
</html>
