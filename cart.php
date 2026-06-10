<?php
require_once __DIR__ . '/db.php';
$page_title = 'Your Cart';
$restaurant = get_restaurant();
include __DIR__ . '/includes/header.php';
?>

<section class="cart-page">
    <div class="page-header">
        <a href="menu.php" class="btn btn-ghost">&larr; Back to Menu</a>
        <h1>Your Cart</h1>
    </div>

    <div id="cart-empty" class="empty-cart" style="display:none">
        <div class="empty-icon">&#128717;</div>
        <h2>Your cart is empty</h2>
        <p>Add items from the menu to get started.</p>
        <a href="menu.php" class="btn btn-primary">Browse Menu</a>
    </div>

    <div id="cart-content">
        <div class="cart-layout">
            <!-- Cart Items -->
            <div class="cart-items" id="cart-items-list">
                <!-- Populated by JS -->
            </div>

            <!-- Order Summary + Form -->
            <div class="cart-sidebar">
                <div class="card">
                    <h3>Your Information</h3>
                    <div class="form-group">
                        <label for="customer-name">Name *</label>
                        <input type="text" id="customer-name" class="form-control" placeholder="Your full name" required>
                    </div>
                    <div class="form-group">
                        <label for="customer-phone">Phone</label>
                        <input type="tel" id="customer-phone" class="form-control" placeholder="Your phone number">
                    </div>
                    <div class="form-group">
                        <label for="order-notes">Order Notes</label>
                        <textarea id="order-notes" class="form-control" rows="3" placeholder="Any special instructions..."></textarea>
                    </div>
                </div>

                <div class="card order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (<?= number_format(TAX_RATE * 100) ?>%)</span>
                        <span id="summary-tax">$0.00</span>
                    </div>
                    <hr>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span id="summary-total">$0.00</span>
                    </div>

                    <div class="cart-actions">
                        <button class="btn btn-outline" id="clear-cart-btn">Clear Cart</button>
                        <button class="btn btn-primary" id="place-order-btn"
                                data-restaurant-id="<?= htmlspecialchars($restaurant['id'] ?? '') ?>">
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
