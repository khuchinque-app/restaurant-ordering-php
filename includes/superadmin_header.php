<?php
require_once dirname(__DIR__) . '/auth.php';
session_init();
$current_user = get_auth_user();
if (!$current_user || $current_user['role'] !== 'SUPERADMIN') {
    header('Location: /admin_login.php');
    exit;
}
$current_path = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Superadmin') ?> &mdash; Superadmin Panel</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/superadmin.css">
</head>
<body class="sa-body">
<div class="sa-layout">

<aside class="sa-sidebar">
    <div class="sa-logo">&#9733; Superadmin</div>
    <nav class="sa-nav">
        <a href="<?= APP_URL ?>/superadmin/index.php"         class="<?= strpos($current_path, 'superadmin/index')         !== false ? 'active' : '' ?>">&#128202; Dashboard</a>
        <a href="<?= APP_URL ?>/superadmin/restaurants.php"   class="<?= strpos($current_path, 'superadmin/restaurants')   !== false ? 'active' : '' ?>">&#127974; Restaurants</a>
        <a href="<?= APP_URL ?>/superadmin/menu.php"          class="<?= strpos($current_path, 'superadmin/menu')          !== false ? 'active' : '' ?>">&#128196; Menu Items</a>
        <a href="<?= APP_URL ?>/superadmin/users.php"         class="<?= strpos($current_path, 'superadmin/users')         !== false ? 'active' : '' ?>">&#128100; Users</a>
        <a href="<?= APP_URL ?>/superadmin/reports.php"       class="<?= strpos($current_path, 'superadmin/reports')       !== false ? 'active' : '' ?>">&#128196; Reports</a>
        <a href="<?= APP_URL ?>/superadmin/stock.php"         class="<?= strpos($current_path, 'superadmin/stock')         !== false ? 'active' : '' ?>">&#128230; Stock</a>
        <a href="<?= APP_URL ?>/superadmin/chat.php"          class="<?= strpos($current_path, 'superadmin/chat')          !== false ? 'active' : '' ?>" id="chatNavLink">&#128172; Chat <span id="chatUnreadBadge" style="display:none;background:#ef4444;color:#fff;border-radius:10px;font-size:.7rem;padding:.05rem .4rem;margin-left:.2rem">0</span></a>
        <a href="<?= APP_URL ?>/superadmin/activity.php"      class="<?= strpos($current_path, 'superadmin/activity')      !== false ? 'active' : '' ?>">&#128203; Activity</a>
        <hr>
        <?php require_once dirname(__DIR__) . '/includes/storefronts.php'; render_storefront_nav(); ?>
        <hr>
        <a href="<?= APP_URL ?>/logout.php">&#128682; Logout</a>
    </nav>
    <div class="sa-user">
        <?= htmlspecialchars($current_user['name']) ?><br>
        <small>SUPERADMIN</small>
    </div>
</aside>

<div class="sa-main">
    <header class="sa-topbar">
        <h1><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
        <div class="sa-topbar-right">
            <span>&#128100; <?= htmlspecialchars($current_user['email']) ?></span>
        </div>
    </header>
    <div class="sa-content">
<script>
(function pollUnread() {
    fetch('<?= APP_URL ?>/api/staff/chat.php?limit=1', {credentials:'include'})
        .then(r => r.json()).then(d => {
            if (d.success) {
                const n = d.data.unread || 0;
                const b = document.getElementById('chatUnreadBadge');
                if (b) { b.textContent = n; b.style.display = n > 0 ? 'inline' : 'none'; }
            }
        }).catch(()=>{});
    setTimeout(pollUnread, 15000);
})();
</script>
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
