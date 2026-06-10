<?php
require_once __DIR__ . '/db.php';
$page_title = 'Menu';
$restaurant = get_restaurant();
$categories = $restaurant
    ? db_query('SELECT * FROM Category WHERE restaurantId = ? AND isActive = 1 ORDER BY sortOrder ASC', [$restaurant['id']])
    : [];
include __DIR__ . '/includes/header.php';
?>

<section class="menu-page">
    <div class="menu-hero">
        <h1><?= htmlspecialchars($restaurant['name'] ?? 'Our Menu') ?></h1>
        <?php if (!empty($restaurant['description'])): ?>
            <p><?= htmlspecialchars($restaurant['description']) ?></p>
        <?php endif; ?>
    </div>

    <?php if (!$restaurant): ?>
        <div class="alert alert-danger">Restaurant not found. Please check configuration.</div>
    <?php else: ?>

    <!-- Category Filter -->
    <div class="category-tabs" id="category-tabs">
        <button class="tab-btn active" data-category="all">All Items</button>
        <?php foreach ($categories as $cat): ?>
            <button class="tab-btn" data-category="<?= htmlspecialchars($cat['id']) ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="search-bar">
        <input type="search" id="menu-search" placeholder="Search menu..." autocomplete="off">
    </div>

    <!-- Menu Items Grid -->
    <div class="menu-grid" id="menu-grid">
        <?php
        $items = db_query(
            'SELECT mi.*, c.name AS categoryName, c.id AS catId
             FROM MenuItem mi
             JOIN Category c ON c.id = mi.categoryId
             WHERE mi.restaurantId = ? AND mi.isAvailable = 1 AND c.isActive = 1
             ORDER BY c.sortOrder ASC, mi.name ASC',
            [$restaurant['id']]
        );
        foreach ($items as $item):
        ?>
        <article class="menu-card" data-category="<?= htmlspecialchars($item['catId']) ?>"
                 data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>">
            <?php if (!empty($item['image'])): ?>
                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="menu-card-img">
            <?php else: ?>
                <div class="menu-card-img placeholder-img">&#127860;</div>
            <?php endif; ?>
            <div class="menu-card-body">
                <div class="menu-card-category"><?= htmlspecialchars($item['categoryName']) ?></div>
                <h3 class="menu-card-title"><?= htmlspecialchars($item['name']) ?></h3>
                <?php if (!empty($item['description'])): ?>
                    <p class="menu-card-desc"><?= htmlspecialchars($item['description']) ?></p>
                <?php endif; ?>
                <?php if ($item['stockQuantity'] !== null && $item['stockQuantity'] <= 5): ?>
                    <span class="badge badge-warn">Only <?= (int)$item['stockQuantity'] ?> left</span>
                <?php endif; ?>
                <div class="menu-card-footer">
                    <span class="price">$<?= number_format((float)$item['price'], 2) ?></span>
                    <button class="btn btn-primary btn-sm add-to-cart"
                            data-id="<?= htmlspecialchars($item['id']) ?>"
                            data-name="<?= htmlspecialchars($item['name']) ?>"
                            data-price="<?= htmlspecialchars($item['price']) ?>"
                            data-stock="<?= $item['stockQuantity'] ?? 999 ?>">
                        + Add
                    </button>
                </div>
            </div>
        </article>
        <?php endforeach; ?>

        <?php if (empty($items)): ?>
            <p class="empty-state">No menu items available right now.</p>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</section>

<!-- Add to cart toast -->
<div class="toast" id="cart-toast" aria-live="polite"></div>

<?php include __DIR__ . '/includes/footer.php'; ?>
