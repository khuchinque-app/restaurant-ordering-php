<?php
$page_title = 'Stock (Read Only)';
include dirname(__DIR__) . '/includes/admin_header.php';
include dirname(__DIR__) . '/db.php';

$restaurant = get_restaurant();
$rid = $restaurant['id'] ?? null;

$filter = $_GET['filter'] ?? '';
$where  = $rid ? ['mi.restaurantId = ?'] : [];
$params = $rid ? [$rid] : [];
if ($filter === 'low') { $where[] = 'mi.stockQuantity IS NOT NULL AND mi.stockQuantity > 0 AND mi.stockQuantity <= mi.lowStockThreshold'; }
if ($filter === 'out') { $where[] = 'mi.stockQuantity IS NOT NULL AND mi.stockQuantity = 0'; }

$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$items = $rid ? db_query(
    "SELECT mi.id, mi.name, mi.stockQuantity, mi.lowStockThreshold, mi.isAvailable, c.name AS categoryName
     FROM MenuItem mi JOIN Category c ON c.id = mi.categoryId $w ORDER BY mi.stockQuantity ASC NULLS LAST",
    $params
) : [];
?>

<div class="alert alert-info" style="display:flex;align-items:center;gap:.5rem;margin-bottom:1rem">
    &#128274; <strong>View Only</strong> &mdash; Stock adjustments are restricted to Superadmin.
</div>

<div class="filter-bar">
    <a href="stock.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline' ?>">All Items</a>
    <a href="stock.php?filter=low" class="btn btn-sm <?= $filter === 'low' ? 'btn-primary' : 'btn-outline' ?>">&#9888; Low Stock</a>
    <a href="stock.php?filter=out" class="btn btn-sm <?= $filter === 'out' ? 'btn-primary' : 'btn-outline' ?>">&#10005; Out of Stock</a>
</div>

<div class="card">
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Threshold</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):
            $is_low = $item['stockQuantity'] !== null && (int)$item['stockQuantity'] <= (int)$item['lowStockThreshold'];
            $is_out = $item['stockQuantity'] !== null && (int)$item['stockQuantity'] === 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= htmlspecialchars($item['categoryName']) ?></td>
            <td class="<?= $is_out ? 'text-danger' : ($is_low ? 'text-warn' : '') ?>">
                <strong><?= $item['stockQuantity'] !== null ? (int)$item['stockQuantity'] : '&infin;' ?></strong>
            </td>
            <td><?= $item['stockQuantity'] !== null ? (int)$item['lowStockThreshold'] : '&mdash;' ?></td>
            <td>
                <?php if ($is_out): ?>
                    <span class="badge badge-danger">Out of Stock</span>
                <?php elseif ($is_low): ?>
                    <span class="badge badge-warn">Low Stock</span>
                <?php else: ?>
                    <span class="badge badge-success">OK</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <tr><td colspan="5" class="empty-state">No items found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>
