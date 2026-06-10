<?php
require_once __DIR__ . '/auth.php';
session_init();

if (get_auth_user()) {
    header('Location: menu.php');
    exit;
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
            $redirect = $_GET['redirect'] ?? 'menu.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}

$page_title = 'Login';
include __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
    <div class="auth-card card">
        <h1>Sign In</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '' ?>">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </form>

    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
