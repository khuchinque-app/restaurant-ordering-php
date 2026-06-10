<?php
require_once dirname(__DIR__) . '/auth.php';
$current_user = require_admin(true);
$current_path = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'Admin') ?> &mdash; Admin Panel</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
<div class="admin-layout">
<aside class="admin-sidebar">
    <div class="sidebar-logo">&#127860; Admin</div>
    <nav class="sidebar-nav">
        <a href="<?= APP_URL ?>/admin/index.php"   class="<?= strpos($current_path, 'admin/index')   !== false ? 'active' : '' ?>">&#128202; Dashboard</a>
        <a href="<?= APP_URL ?>/admin/orders.php"  class="<?= strpos($current_path, 'admin/orders')  !== false ? 'active' : '' ?>">&#128230; Orders</a>
        <a href="<?= APP_URL ?>/admin/menu.php"    class="<?= strpos($current_path, 'admin/menu')    !== false ? 'active' : '' ?>">&#128196; Menu</a>
        <a href="<?= APP_URL ?>/admin/stock.php"   class="<?= strpos($current_path, 'admin/stock')   !== false ? 'active' : '' ?>">&#128230; Stock</a>
        <a href="<?= APP_URL ?>/admin/chat.php"    class="<?= strpos($current_path, 'admin/chat')    !== false ? 'active' : '' ?>" id="chatNavLink">&#128172; Chat <span id="chatUnreadBadge" style="display:none;background:#ef4444;color:#fff;border-radius:10px;font-size:.7rem;padding:.05rem .4rem;margin-left:.2rem">0</span></a>
        <hr>
        <a href="<?= APP_URL ?>/menu.php">&#127760; View Site</a>
        <a href="<?= APP_URL ?>/logout.php">&#128682; Logout</a>
    </nav>
    <div class="sidebar-user">
        <?= htmlspecialchars($current_user['name']) ?><br>
        <small><?= htmlspecialchars($current_user['role']) ?></small>
    </div>
</aside>
<div class="admin-main">
<header class="admin-topbar">
    <h1><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h1>
</header>
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
<div class="admin-content">
<?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-<?= htmlspecialchars($_SESSION['flash']['type']) ?>">
        <?= htmlspecialchars($_SESSION['flash']['message']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
