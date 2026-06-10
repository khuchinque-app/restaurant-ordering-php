<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
session_init();

$page_title = 'My Orders';

// Look up orders by session customer_id or by order_ids stored in session
$current_user = get_auth_user();
$orders = [];

if ($current_user) {
    $orders = db_query(
        'SELECT o.*, r.name AS restaurantName
         FROM "Order" o
         JOIN Restaurant r ON r.id = o.restaurantId
         WHERE o.customerId = ?
         ORDER BY o.createdAt DESC
         LIMIT 50',
        [$current_user['id']]
    );
} elseif (!empty($_SESSION['order_ids'])) {
    $ids    = array_slice($_SESSION['order_ids'], 0, 20);
    $marks  = implode(',', array_fill(0, count($ids), '?'));
    $orders = db_query(
        "SELECT o.*, r.name AS restaurantName
         FROM \"Order\" o
         JOIN Restaurant r ON r.id = o.restaurantId
         WHERE o.id IN ($marks)
         ORDER BY o.createdAt DESC",
        $ids
    );
}

// Get order items for each order
$order_items = [];
foreach ($orders as $order) {
    $order_items[$order['id']] = db_query(
        'SELECT oi.*, mi.name AS itemName
         FROM OrderItem oi
         JOIN MenuItem mi ON mi.id = oi.menuItemId
         WHERE oi.orderId = ?',
        [$order['id']]
    );
}

include __DIR__ . '/includes/header.php';
?>

<section class="orders-page">
    <h1>My Orders</h1>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#128230;</div>
            <h2>No orders yet</h2>
            <p>Place your first order from our menu!</p>
            <a href="menu.php" class="btn btn-primary">Browse Menu</a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
            <article class="order-card" id="order-<?= htmlspecialchars($order['id']) ?>">
                <div class="order-header">
                    <div>
                        <strong>#<?= htmlspecialchars($order['orderNumber']) ?></strong>
                        <span class="order-restaurant"><?= htmlspecialchars($order['restaurantName']) ?></span>
                    </div>
                    <span class="status-badge status-<?= strtolower(htmlspecialchars($order['status'])) ?>">
                        <?= htmlspecialchars($order['status']) ?>
                    </span>
                </div>

                <div class="order-items">
                    <?php foreach ($order_items[$order['id']] as $item): ?>
                        <div class="order-item-row">
                            <span><?= (int)$item['quantity'] ?>&times; <?= htmlspecialchars($item['itemName']) ?></span>
                            <span>$<?= number_format((float)$item['totalPrice'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-footer">
                    <span class="order-date"><?= date('M d, Y H:i', strtotime($order['createdAt'])) ?></span>
                    <strong class="order-total">$<?= number_format((float)$order['totalAmount'], 2) ?></strong>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<script>
// Auto-refresh pending orders every 15 seconds
(function() {
    const hasPending = <?= json_encode(in_array(true, array_map(fn($o) => in_array($o['status'], ['PENDING','CONFIRMED','PREPARING']), $orders))) ?>;
    if (hasPending) {
        setTimeout(() => location.reload(), 15000);
    }
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
