const RESTAURANT_SLUG = 'grill_house';
const API_BASE        = '/api';
const TAX_RATE        = 0.10;
const CART_KEY        = 'cart_' + RESTAURANT_SLUG;

let cart = [];
try { cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); } catch { cart = []; }

function saveCart() { localStorage.setItem(CART_KEY, JSON.stringify(cart)); renderCartBadge(); renderCartDrawer(); }
function addItem(id, name, price, maxStock) {
    const ex = cart.find(i => i.id === id);
    if (ex) { if (ex.qty >= (maxStock||999)) { toast('Not enough stock'); return; } ex.qty++; }
    else cart.push({ id, name, price: parseFloat(price), qty: 1, maxStock: parseInt(maxStock)||999 });
    saveCart(); toast(name + ' added to cart');
}
function removeItem(id)       { cart = cart.filter(i => i.id !== id); saveCart(); }
function changeQty(id, delta) { const i = cart.find(x => x.id===id); if(i){ i.qty=Math.max(1,i.qty+delta); if(i.qty>i.maxStock)i.qty=i.maxStock; } saveCart(); }
function clearCart()          { cart=[]; saveCart(); }
function totalItems()         { return cart.reduce((s,i)=>s+i.qty,0); }
function subtotal()           { return cart.reduce((s,i)=>s+i.price*i.qty,0); }
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function fmt(n)  { return '$'+parseFloat(n).toFixed(2); }
function toast(msg) { const el=document.getElementById('toast'); el.textContent=msg; el.classList.add('show'); clearTimeout(el._t); el._t=setTimeout(()=>el.classList.remove('show'),2500); }
function renderCartBadge() { const n=totalItems(); document.getElementById('cart-count').textContent=n; document.getElementById('cart-count').style.display=n?'inline-flex':'none'; }
function openCart()  { document.getElementById('cart-drawer').classList.add('open'); document.getElementById('drawer-overlay').classList.add('open'); renderCartDrawer(); }
function closeCart() { document.getElementById('cart-drawer').classList.remove('open'); document.getElementById('drawer-overlay').classList.remove('open'); }

function renderCartDrawer() {
    const body=document.getElementById('cart-body'), footer=document.getElementById('cart-footer');
    if (!cart.length) { body.innerHTML='<div class="drawer-empty">&#128717;<br>Your cart is empty</div>'; footer.innerHTML=''; return; }
    body.innerHTML=cart.map(i=>`<div class="cart-item"><div class="ci-info"><div class="ci-name">${esc(i.name)}</div><div class="ci-price">${fmt(i.price)} each</div></div><div class="ci-controls"><button class="qty-btn" onclick="changeQty('${i.id}',-1)">&#8722;</button><span class="qty-val">${i.qty}</span><button class="qty-btn" onclick="changeQty('${i.id}',1)">&#43;</button><button class="rm-btn" onclick="removeItem('${i.id}')">&times;</button></div></div>`).join('');
    const sub=subtotal(),tax=sub*TAX_RATE,tot=sub+tax;
    footer.innerHTML=`<div class="summary"><div class="sum-row"><span>Subtotal</span><span>${fmt(sub)}</span></div><div class="sum-row"><span>Tax (${(TAX_RATE*100).toFixed(0)}%)</span><span>${fmt(tax)}</span></div><div class="sum-row sum-total"><span>Total</span><span>${fmt(tot)}</span></div></div><div class="form-group"><label>Your Name *</label><input id="cust-name" type="text" placeholder="Full name"></div><div class="form-group"><label>Phone (optional)</label><input id="cust-phone" type="tel" placeholder="Phone number"></div><div class="form-group"><label>Order Notes</label><textarea id="cust-notes" rows="2" placeholder="Special instructions…"></textarea></div><button class="place-btn" id="place-order-btn" onclick="placeOrder()">Place Order &mdash; ${fmt(tot)}</button>`;
}

async function placeOrder() {
    const name=document.getElementById('cust-name')?.value.trim();
    if (!name) { alert('Please enter your name'); document.getElementById('cust-name')?.focus(); return; }
    const btn=document.getElementById('place-order-btn'); btn.disabled=true; btn.textContent='Placing order…';
    try {
        const res=await fetch(`${API_BASE}/orders/index.php`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({restaurantId:window.RESTAURANT_ID,customerName:name,customerPhone:document.getElementById('cust-phone')?.value.trim()||undefined,notes:document.getElementById('cust-notes')?.value.trim()||undefined,items:cart.map(i=>({menuItemId:i.id,quantity:i.qty}))})});
        const data=await res.json();
        if(data.success){clearCart();closeCart();fetch(`${API_BASE}/orders/track.php`,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({orderId:data.data.id})}).catch(()=>{});showOrderConfirm(data.data.orderNumber);}
        else{alert(data.error||'Failed to place order');btn.disabled=false;btn.textContent='Place Order';}
    } catch{alert('Network error');btn.disabled=false;btn.textContent='Place Order';}
}
function showOrderConfirm(n){document.getElementById('confirm-order-num').textContent='#'+n;document.getElementById('order-confirm').classList.add('open');}

let allItems=[],activeCategory='all';
async function loadMenu(){
    const cRes=await fetch(`${API_BASE}/menu/categories.php?restaurant=${RESTAURANT_SLUG}`);
    const cData=await cRes.json();
    if(!cData.success){document.getElementById('menu-grid').innerHTML='<p style="color:red">Failed to load menu.</p>';return;}
    const iRes=await fetch(`${API_BASE}/menu/items.php?restaurant=${RESTAURANT_SLUG}&available=1&limit=100`);
    const iData=await iRes.json();
    allItems=iData.success?iData.data.items:[];
    const infoRes=await fetch(`/api/menu/restaurant_info.php?restaurant=${RESTAURANT_SLUG}`);
    if(infoRes.ok){const info=await infoRes.json();if(info.success)window.RESTAURANT_ID=info.data.id;}
    renderCategories(cData.data);
    renderItems(allItems);
}
function renderCategories(cats){const wrap=document.getElementById('cat-tabs');wrap.innerHTML='<button class="cat-tab active" onclick="filterCat(\'all\',this)">All</button>'+cats.map(c=>`<button class="cat-tab" onclick="filterCat('${esc(c.id)}',this)">${esc(c.name)}</button>`).join('');}
function filterCat(id,btn){activeCategory=id;document.querySelectorAll('.cat-tab').forEach(b=>b.classList.remove('active'));btn.classList.add('active');applyFilter();}
function applyFilter(){const q=(document.getElementById('search-input')?.value||'').toLowerCase().trim();renderItems(allItems.filter(i=>(activeCategory==='all'||i.categoryId===activeCategory)&&(!q||i.name.toLowerCase().includes(q))));}
function renderItems(items){const g=document.getElementById('menu-grid');if(!items.length){g.innerHTML='<p style="padding:2rem;color:#6b7280;text-align:center">No items found.</p>';return;}g.innerHTML=items.map(i=>`<div class="menu-card">${i.image?`<img src="${esc(i.image)}" alt="${esc(i.name)}">` :'<div class="img-placeholder">&#127860;</div>'}<div class="card-body"><div class="card-cat">${esc(i.categoryName||'')}</div><div class="card-name">${esc(i.name)}</div>${i.description?`<div class="card-desc">${esc(i.description)}</div>`:''}<div class="card-footer"><span class="price">${fmt(i.price)}</span><button class="add-btn" onclick="addItem('${esc(i.id)}','${esc(i.name)}','${esc(i.price)}','${esc(i.stockQuantity??999)}')">+ Add</button></div></div></div>`).join('');}

document.addEventListener('DOMContentLoaded',()=>{renderCartBadge();loadMenu();document.getElementById('search-input')?.addEventListener('input',applyFilter);document.getElementById('drawer-overlay')?.addEventListener('click',closeCart);});
