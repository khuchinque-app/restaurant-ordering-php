<?php
require_once dirname(__DIR__) . '/db.php';
$page_title = 'Menu Management';
include dirname(__DIR__) . '/includes/admin_header.php';

$restaurant = get_restaurant();
$rid = $restaurant['id'] ?? null;

$categories = $rid ? db_query('SELECT * FROM Category WHERE restaurantId = ? ORDER BY sortOrder ASC', [$rid]) : [];
$items = $rid ? db_query(
    'SELECT mi.*, c.name AS categoryName FROM MenuItem mi JOIN Category c ON c.id = mi.categoryId WHERE mi.restaurantId = ? ORDER BY c.sortOrder ASC, mi.name ASC',
    [$rid]
) : [];
?>

<div class="menu-mgmt-layout">
<!-- Categories Panel -->
<div class="card">
    <div class="card-header">
        <h2>Categories</h2>
        <button class="btn btn-primary btn-sm" id="add-category-btn">+ Add</button>
    </div>

    <!-- Add Category Form (hidden by default) -->
    <div id="add-category-form" class="inline-form" style="display:none">
        <form id="category-form">
            <div class="form-row">
                <input type="text" name="name" class="form-control" placeholder="Category name" required>
                <input type="number" name="sortOrder" class="form-control" placeholder="Sort order" value="0" min="0" style="width:90px">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
                <button type="button" class="btn btn-ghost btn-sm" id="cancel-category-btn">Cancel</button>
            </div>
        </form>
    </div>

    <table class="table" id="categories-table">
        <thead><tr><th>Name</th><th>Items</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $cat):
            $item_count = (int)(db_fetch('SELECT COUNT(*) AS n FROM MenuItem WHERE categoryId = ?', [$cat['id']])['n'] ?? 0);
        ?>
        <tr id="cat-row-<?= htmlspecialchars($cat['id']) ?>">
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td><?= $item_count ?></td>
            <td><span class="badge <?= $cat['isActive'] ? 'badge-success' : 'badge-muted' ?>"><?= $cat['isActive'] ? 'Yes' : 'No' ?></span></td>
            <td>
                <button class="btn btn-sm btn-outline toggle-cat-btn"
                        data-id="<?= htmlspecialchars($cat['id']) ?>"
                        data-active="<?= $cat['isActive'] ?>">
                    <?= $cat['isActive'] ? 'Disable' : 'Enable' ?>
                </button>
                <?php if ($item_count === 0): ?>
                <button class="btn btn-sm btn-danger delete-cat-btn" data-id="<?= htmlspecialchars($cat['id']) ?>">Delete</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Menu Items Panel -->
<div class="card">
    <div class="card-header">
        <h2>Menu Items</h2>
        <button class="btn btn-primary btn-sm" id="add-item-btn">+ Add Item</button>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
        <select id="cat-filter" class="form-control" style="width:auto">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" id="item-search" class="form-control" placeholder="Search items..." style="max-width:200px">
    </div>

    <table class="table" id="items-table">
        <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Available</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr id="item-row-<?= htmlspecialchars($item['id']) ?>"
            data-category="<?= htmlspecialchars($item['categoryId']) ?>"
            data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>">
            <td>
                <strong><?= htmlspecialchars($item['name']) ?></strong>
                <?php if ($item['description']): ?>
                    <br><small class="text-muted"><?= htmlspecialchars(substr($item['description'], 0, 60)) ?></small>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($item['categoryName']) ?></td>
            <td>$<?= number_format((float)$item['price'], 2) ?></td>
            <td class="<?= $item['stockQuantity'] !== null && $item['stockQuantity'] <= $item['lowStockThreshold'] ? 'text-warn' : '' ?>">
                <?= $item['stockQuantity'] !== null ? (int)$item['stockQuantity'] : '&infin;' ?>
            </td>
            <td>
                <button class="btn btn-sm btn-outline toggle-item-btn"
                        data-id="<?= htmlspecialchars($item['id']) ?>"
                        data-available="<?= $item['isAvailable'] ?>">
                    <?= $item['isAvailable'] ? 'Disable' : 'Enable' ?>
                </button>
            </td>
            <td>
                <button class="btn btn-sm btn-outline edit-item-btn"
                        data-item='<?= htmlspecialchars(json_encode($item)) ?>'>Edit</button>
                <button class="btn btn-sm btn-danger delete-item-btn" data-id="<?= htmlspecialchars($item['id']) ?>">Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal" id="item-modal" style="display:none">
    <div class="modal-backdrop" id="modal-backdrop"></div>
    <div class="modal-dialog card">
        <div class="card-header">
            <h2 id="modal-title">Add Menu Item</h2>
            <button class="btn btn-ghost btn-sm" id="close-modal">&times;</button>
        </div>
        <form id="item-form">
            <input type="hidden" id="item-id" name="id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" id="item-name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select name="categoryId" id="item-category" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price *</label>
                    <input type="number" name="price" id="item-price" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stockQuantity" id="item-stock" class="form-control" min="0" placeholder="Leave empty for unlimited">
                </div>
                <div class="form-group">
                    <label>Low Stock Threshold</label>
                    <input type="number" name="lowStockThreshold" id="item-threshold" class="form-control" min="0" value="5">
                </div>
                <div class="form-group">
                    <label>Preparation Time (min)</label>
                    <input type="number" name="preparationTime" id="item-prep" class="form-control" min="0" value="15">
                </div>
                <div class="form-group form-full">
                    <label>Description</label>
                    <textarea name="description" id="item-desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group form-full">
                    <label>Image URL</label>
                    <input type="url" name="image" id="item-image" class="form-control" placeholder="https://...">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-ghost" id="cancel-item-btn">Cancel</button>
                <button type="submit" class="btn btn-primary" id="save-item-btn">Save Item</button>
            </div>
        </form>
    </div>
</div>

<script>
const RESTAURANT_ID = <?= json_encode($rid) ?>;
const RESTAURANT_SLUG = <?= json_encode(DEFAULT_RESTAURANT_SLUG) ?>;
</script>

<?php include dirname(__DIR__) . '/includes/admin_footer.php'; ?>
