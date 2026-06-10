// ===== Cart Management (localStorage) =====
const CART_KEY = 'restaurant_cart';

function getCart() {
    try { return JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch { return []; }
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartUI();
}

function addToCart(id, name, price, maxStock) {
    const cart = getCart();
    const existing = cart.find(i => i.id === id);
    if (existing) {
        if (maxStock && existing.qty >= maxStock) { showToast('Not enough stock'); return; }
        existing.qty += 1;
    } else {
        cart.push({ id, name, price: parseFloat(price), qty: 1, maxStock: parseInt(maxStock) || 999 });
    }
    saveCart(cart);
    showToast(`${name} added to cart`);
}

function removeFromCart(id) {
    saveCart(getCart().filter(i => i.id !== id));
}

function updateQty(id, delta) {
    const cart = getCart();
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty = Math.max(1, item.qty + delta);
    if (item.maxStock && item.qty > item.maxStock) item.qty = item.maxStock;
    saveCart(cart);
}

function clearCart() {
    saveCart([]);
}

function getTotalItems() {
    return getCart().reduce((s, i) => s + i.qty, 0);
}

function getTotalPrice() {
    return getCart().reduce((s, i) => s + i.price * i.qty, 0);
}

function updateCartUI() {
    const count = getTotalItems();
    document.querySelectorAll('#cart-count').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'inline' : 'none';
    });
}

// ===== Toast Notification =====
function showToast(message) {
    const toast = document.getElementById('cart-toast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 2500);
}

// ===== Menu Page Behaviour =====
function initMenuPage() {
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', () => {
            addToCart(btn.dataset.id, btn.dataset.name, btn.dataset.price, btn.dataset.stock);
            btn.textContent = '✓ Added';
            setTimeout(() => { btn.textContent = '+ Add'; }, 1200);
        });
    });

    // Category tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const category = btn.dataset.category;
            filterMenu(category, document.getElementById('menu-search')?.value || '');
        });
    });

    // Search
    const searchInput = document.getElementById('menu-search');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const active = document.querySelector('.tab-btn.active');
            filterMenu(active?.dataset.category || 'all', searchInput.value);
        });
    }
}

function filterMenu(category, search) {
    const q = search.toLowerCase().trim();
    document.querySelectorAll('.menu-card').forEach(card => {
        const matchCat  = category === 'all' || card.dataset.category === category;
        const matchName = !q || card.dataset.name.includes(q);
        card.style.display = matchCat && matchName ? '' : 'none';
    });
}

// ===== Cart Page Behaviour =====
function initCartPage() {
    renderCart();

    document.getElementById('clear-cart-btn')?.addEventListener('click', () => {
        if (confirm('Clear your cart?')) { clearCart(); renderCart(); }
    });

    document.getElementById('place-order-btn')?.addEventListener('click', placeOrder);
}

function renderCart() {
    const cart = getCart();
    const emptyEl   = document.getElementById('cart-empty');
    const contentEl = document.getElementById('cart-content');
    const listEl    = document.getElementById('cart-items-list');

    if (!listEl) return;

    if (cart.length === 0) {
        emptyEl && (emptyEl.style.display = '');
        contentEl && (contentEl.style.display = 'none');
        return;
    }

    emptyEl && (emptyEl.style.display = 'none');
    contentEl && (contentEl.style.display = '');

    listEl.innerHTML = cart.map(item => `
        <div class="cart-item-card">
            <div class="cart-item-info">
                <div class="cart-item-name">${escHtml(item.name)}</div>
                <div class="cart-item-price">$${item.price.toFixed(2)} each</div>
            </div>
            <div class="cart-item-controls">
                <button class="qty-btn" onclick="updateQty('${item.id}', -1); renderCart()">&#8722;</button>
                <span class="qty-display">${item.qty}</span>
                <button class="qty-btn" onclick="updateQty('${item.id}', 1); renderCart()">&#43;</button>
                <button class="remove-btn" onclick="removeFromCart('${item.id}'); renderCart()" title="Remove">&times;</button>
            </div>
        </div>
    `).join('');

    const sub = getTotalPrice();
    const tax = sub * (window.TAX_RATE || 0.1);
    const tot = sub + tax;
    document.getElementById('summary-subtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('summary-tax').textContent      = '$' + tax.toFixed(2);
    document.getElementById('summary-total').textContent    = '$' + tot.toFixed(2);
}

async function placeOrder() {
    const cart   = getCart();
    if (!cart.length) { alert('Your cart is empty'); return; }

    const name  = document.getElementById('customer-name')?.value.trim();
    const phone = document.getElementById('customer-phone')?.value.trim();
    const notes = document.getElementById('order-notes')?.value.trim();
    const rid   = document.getElementById('place-order-btn')?.dataset.restaurantId;

    if (!name) { alert('Please enter your name'); document.getElementById('customer-name')?.focus(); return; }
    if (!rid)  { alert('Restaurant not configured'); return; }

    const btn = document.getElementById('place-order-btn');
    btn.disabled = true;
    btn.textContent = 'Placing Order...';

    try {
        const res = await fetch('/api/orders/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                restaurantId: rid,
                customerName: name,
                customerPhone: phone || undefined,
                notes: notes || undefined,
                items: cart.map(i => ({ menuItemId: i.id, quantity: i.qty })),
            })
        });

        const data = await res.json();
        if (data.success) {
            clearCart();
            // Track order ID in session
            await fetch('/api/orders/track.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orderId: data.data.id })
            }).catch(() => {});
            window.location.href = '/orders.php';
        } else {
            alert(data.error || 'Failed to place order. Please try again.');
            btn.disabled = false;
            btn.textContent = 'Place Order';
        }
    } catch (e) {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Place Order';
    }
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ===== Init =====
document.addEventListener('DOMContentLoaded', () => {
    updateCartUI();
    if (document.querySelector('.menu-page'))  initMenuPage();
    if (document.querySelector('.cart-page'))  initCartPage();
});
