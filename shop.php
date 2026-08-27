<?php
// Public storefront homepage — no session/auth, reachable by anyone.
// Talks only to api/public_catalog.php (read) and api/public_checkout.php
// (write) — both deliberately narrow, unauthenticated endpoints; every
// other api/*.php file in this app still requires a login. See those two
// files' header comments for the reasoning.
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>RR Crackers — Shop Online</title>
<meta name="description" content="Shop Sivakasi crackers online. Browse our range of sparklers, flower pots, sound crackers and more.">
<style>
:root{
  --accent:#e0392b; --accent2:#ff7a1a; --gold:#f5b942;
  --bg:#120b09; --surface:#1c1210; --surface2:#241713; --surface3:#2c1b16;
  --border:#3a2620; --text:#f3ece7; --text2:#c9b8ae; --text3:#8f7a70;
  --green:#22c55e; --red:#ef4444;
  --radius:14px; --radius-sm:9px;
}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--bg);color:var(--text);line-height:1.5}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
button{font-family:inherit;cursor:pointer}
.container{max-width:1180px;margin:0 auto;padding:0 18px}

/* ── Header ── */
header.site{position:sticky;top:0;z-index:40;background:rgba(18,11,9,.92);backdrop-filter:blur(8px);border-bottom:1px solid var(--border)}
.header-row{display:flex;align-items:center;gap:16px;padding:12px 0}
.brand{display:flex;align-items:center;gap:8px;font-weight:800;font-size:1.15rem;white-space:nowrap}
.brand .dot{width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--gold))}
.header-search{flex:1;max-width:420px;position:relative}
.header-search input{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:9px 14px 9px 34px;border-radius:20px;font-size:.88rem;outline:none}
.header-search input:focus{border-color:var(--accent)}
.header-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);opacity:.55;pointer-events:none}
.cart-btn{position:relative;display:flex;align-items:center;gap:8px;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:9px 16px;border-radius:20px;font-weight:700;font-size:.85rem;white-space:nowrap}
.cart-btn:hover{border-color:var(--accent)}
.cart-badge{position:absolute;top:-6px;right:-6px;background:var(--accent);color:#fff;font-size:.66rem;font-weight:800;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px}

/* ── Hero ── */
.hero{background:radial-gradient(circle at 20% 20%,rgba(224,57,43,.35),transparent 55%),radial-gradient(circle at 80% 60%,rgba(245,185,66,.22),transparent 50%),linear-gradient(180deg,#1a0f0c,#120b09);padding:52px 0 40px;text-align:center;border-bottom:1px solid var(--border)}
.hero h1{font-size:clamp(1.7rem,4vw,2.6rem);margin:0 0 10px;font-weight:800;letter-spacing:-.02em}
.hero h1 span{background:linear-gradient(90deg,var(--gold),var(--accent2));-webkit-background-clip:text;background-clip:text;color:transparent}
.hero p{color:var(--text2);max-width:520px;margin:0 auto 22px;font-size:.95rem}
.hero-btn{display:inline-block;background:linear-gradient(90deg,var(--accent),var(--accent2));color:#fff;font-weight:700;padding:12px 28px;border-radius:24px;font-size:.92rem;border:none}
.hero-btn:hover{opacity:.92}

/* ── Category chips ── */
.cats-wrap{padding:18px 0;border-bottom:1px solid var(--border)}
.cats{display:flex;gap:8px;overflow-x:auto;padding-bottom:2px;scrollbar-width:thin}
.cat-chip{flex:0 0 auto;display:flex;align-items:center;gap:7px;background:var(--surface2);border:1.5px solid var(--border);color:var(--text2);padding:7px 15px;border-radius:20px;font-size:.82rem;font-weight:600;white-space:nowrap}
.cat-chip .sw{width:9px;height:9px;border-radius:50%;background:var(--accent2)}
.cat-chip.active{border-color:var(--accent);color:#fff;background:rgba(224,57,43,.18)}

/* ── Product grid ── */
.section-title{font-size:1.05rem;font-weight:800;margin:26px 0 14px;display:flex;align-items:center;justify-content:space-between}
.section-title small{font-weight:400;color:var(--text3);font-size:.78rem}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:14px;padding-bottom:50px}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column}
.card-img{aspect-ratio:1/1;background:var(--surface3);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.card-img img{width:100%;height:100%;object-fit:cover}
.card-img .ph{font-size:2.4rem;opacity:.35}
.stock-badge{position:absolute;top:8px;left:8px;background:rgba(18,11,9,.75);color:var(--green);font-size:.66rem;font-weight:700;padding:3px 8px;border-radius:10px;border:1px solid rgba(34,197,94,.35)}
.stock-badge.low{color:var(--gold);border-color:rgba(245,185,66,.35)}
.card-body{padding:11px 12px 12px;display:flex;flex-direction:column;gap:6px;flex:1}
.card-cat{font-size:.66rem;color:var(--accent2);font-weight:700;text-transform:uppercase;letter-spacing:.4px}
.card-name{font-size:.87rem;font-weight:700;line-height:1.3;min-height:2.3em}
.card-brand{font-size:.72rem;color:var(--text3)}
.card-price{display:flex;align-items:baseline;gap:7px;margin-top:auto}
.price-now{font-size:1.02rem;font-weight:800;color:var(--gold)}
.price-mrp{font-size:.76rem;color:var(--text3);text-decoration:line-through}
.card-actions{display:flex;align-items:center;gap:6px;margin-top:4px}
.qty-box{display:flex;align-items:center;border:1px solid var(--border);border-radius:8px;overflow:hidden}
.qty-box button{background:var(--surface2);color:var(--text);border:none;width:26px;height:30px;font-size:.95rem}
.qty-box span{width:28px;text-align:center;font-size:.82rem;font-weight:700}
.add-btn{flex:1;background:var(--surface2);border:1px solid var(--accent);color:var(--accent2);font-weight:700;font-size:.78rem;padding:7px 8px;border-radius:8px}
.add-btn.in-cart{background:var(--accent);color:#fff;border-color:var(--accent)}
.empty-msg{text-align:center;padding:50px 10px;color:var(--text3)}

/* ── Cart drawer ── */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:60;display:none}
.overlay.open{display:block}
.drawer{position:fixed;top:0;right:0;height:100%;width:min(400px,92vw);background:var(--surface);border-left:1px solid var(--border);z-index:61;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .25s ease}
.drawer.open{transform:translateX(0)}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--border)}
.drawer-head b{font-size:1rem}
.close-x{background:none;border:none;color:var(--text2);font-size:1.3rem;line-height:1}
.drawer-items{flex:1;overflow-y:auto;padding:10px 14px}
.cart-row{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)}
.cart-row img{width:52px;height:52px;border-radius:8px;object-fit:cover;background:var(--surface3)}
.cart-row .ph{width:52px;height:52px;border-radius:8px;background:var(--surface3);display:flex;align-items:center;justify-content:center;font-size:1.3rem;opacity:.4}
.cart-row-info{flex:1;min-width:0}
.cart-row-name{font-size:.83rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cart-row-price{font-size:.76rem;color:var(--text3);margin-top:2px}
.cart-row-actions{display:flex;align-items:center;gap:6px;margin-top:6px}
.rm-btn{background:none;border:none;color:var(--red);font-size:.72rem;font-weight:700;margin-left:auto}
.drawer-foot{padding:14px 18px;border-top:1px solid var(--border)}
.subtotal-row{display:flex;justify-content:space-between;font-size:.9rem;font-weight:700;margin-bottom:12px}
.checkout-btn{width:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));color:#fff;border:none;padding:13px;border-radius:10px;font-weight:800;font-size:.9rem}
.checkout-btn:disabled{opacity:.5}

/* ── Checkout modal ── */
.modal-back{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:70;display:none;align-items:center;justify-content:center;padding:16px}
.modal-back.open{display:flex}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);max-width:440px;width:100%;max-height:90vh;overflow-y:auto}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--border)}
.modal-body{padding:18px}
.field{margin-bottom:13px}
.field label{display:block;font-size:.74rem;color:var(--text3);font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.field input,.field textarea{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:10px 12px;border-radius:8px;font-size:.88rem;font-family:inherit;outline:none}
.field input:focus,.field textarea:focus{border-color:var(--accent)}
.hp-field{position:absolute;left:-9999px;top:-9999px}
.err-banner{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.4);color:#fca5a5;padding:9px 12px;border-radius:8px;font-size:.8rem;margin-bottom:12px;display:none}
.success-box{text-align:center;padding:20px 6px}
.success-box .ic{font-size:2.6rem;margin-bottom:10px}
.success-box h3{margin:0 0 8px}
.success-box p{color:var(--text2);font-size:.88rem}
.order-no{display:inline-block;background:var(--surface2);border:1px dashed var(--accent2);color:var(--gold);font-weight:800;padding:8px 16px;border-radius:8px;margin:10px 0;letter-spacing:.5px}

/* ── Footer ── */
footer{border-top:1px solid var(--border);padding:30px 0;margin-top:20px;color:var(--text3);font-size:.82rem}
footer .foot-row{display:flex;flex-wrap:wrap;gap:20px;justify-content:space-between}
footer b{color:var(--text2)}

@media (max-width:640px){
  .header-search{display:none}
  .hero{padding:36px 0 28px}
}
</style>
</head>
<body>

<header class="site">
  <div class="container header-row">
    <div class="brand"><span class="dot"></span><span id="brand-name">RR Crackers</span></div>
    <div class="header-search">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="search-input" placeholder="Search sparklers, flower pots, rockets…" oninput="onSearch()">
    </div>
    <button class="cart-btn" onclick="openCart()">
      🛒 Cart <span id="cart-total-mini"></span>
      <span class="cart-badge" id="cart-badge" style="display:none">0</span>
    </button>
  </div>
</header>

<section class="hero">
  <div class="container">
    <h1>Light up your celebration with <span>RR Crackers</span></h1>
    <p>Browse our range of sparklers, flower pots, sound crackers and more — pick what you need and place your order online. Our team will reach out to confirm payment and delivery.</p>
    <a href="#catalog" class="hero-btn">Shop Now</a>
  </div>
</section>

<div class="cats-wrap">
  <div class="container">
    <div class="cats" id="cats-row">
      <button class="cat-chip active" data-cat="" onclick="selectCategory('')">All Products</button>
    </div>
  </div>
</div>

<div class="container" id="catalog">
  <div class="section-title"><span>Our Products</span> <small id="result-count"></small></div>
  <div class="grid" id="product-grid"><div class="empty-msg">Loading products…</div></div>
</div>

<footer>
  <div class="container foot-row">
    <div>
      <b id="foot-name">RR Crackers</b><br>
      <span id="foot-addr"></span>
    </div>
    <div id="foot-contact"></div>
  </div>
</footer>

<!-- Cart drawer -->
<div class="overlay" id="overlay" onclick="closeAllOverlays()"></div>
<div class="drawer" id="cart-drawer">
  <div class="drawer-head"><b>Your Cart</b><button class="close-x" onclick="closeCart()">✕</button></div>
  <div class="drawer-items" id="cart-items"></div>
  <div class="drawer-foot">
    <div class="subtotal-row"><span>Subtotal</span><span id="cart-subtotal">₹0</span></div>
    <button class="checkout-btn" id="checkout-open-btn" onclick="openCheckout()" disabled>Proceed to Checkout</button>
  </div>
</div>

<!-- Checkout modal -->
<div class="modal-back" id="checkout-modal">
  <div class="modal-box">
    <div class="modal-head"><b id="checkout-title">Checkout</b><button class="close-x" onclick="closeCheckout()">✕</button></div>
    <div class="modal-body" id="checkout-body">
      <div class="err-banner" id="checkout-err"></div>
      <div class="field"><label>Full Name</label><input type="text" id="ck-name" placeholder="Your name"></div>
      <div class="field"><label>Phone Number</label><input type="tel" id="ck-phone" placeholder="10-digit mobile number"></div>
      <div class="field"><label>Delivery Address</label><textarea id="ck-address" rows="3" placeholder="House/street, city, pincode"></textarea></div>
      <input type="text" id="ck-website" class="hp-field" tabindex="-1" autocomplete="off">
      <button class="checkout-btn" id="place-order-btn" onclick="placeOrder()">Place Order — <span id="ck-total">₹0</span></button>
    </div>
  </div>
</div>

<script>
const API_CATALOG='api/public_catalog.php';
const API_CHECKOUT='api/public_checkout.php';
const API_SETTINGS='api/settings.php';

let PRODUCTS=[];
let CATEGORY='';
let SEARCH='';
let CART={}; // {product_id: {product, qty}}

function fmtMoney(n){ return '₹'+(+n||0).toLocaleString('en-IN',{maximumFractionDigits:0}); }
function esc(s){ return (s==null?'':String(s)).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }

function loadCart(){
  try{ CART=JSON.parse(localStorage.getItem('rr_shop_cart')||'{}'); }catch(e){ CART={}; }
}
function saveCart(){
  try{ localStorage.setItem('rr_shop_cart', JSON.stringify(CART)); }catch(e){}
}

async function loadBranding(){
  try{
    const r=await fetch(API_SETTINGS); const s=await r.json();
    const d=s.data||{};
    const name=d.business_name||'RR Crackers';
    document.getElementById('brand-name').textContent=name;
    document.getElementById('foot-name').textContent=name;
    document.title=name+' — Shop Online';
    document.getElementById('foot-addr').textContent=d.business_address||'';
    const bits=[];
    if(d.business_phone) bits.push('📞 '+esc(d.business_phone));
    if(d.business_email) bits.push('✉️ '+esc(d.business_email));
    document.getElementById('foot-contact').innerHTML=bits.join(' &nbsp;·&nbsp; ');
  }catch(e){ /* branding is cosmetic only -- page still works without it */ }
}

async function loadMeta(){
  try{
    const r=await fetch(API_CATALOG+'?meta=1'); const j=await r.json();
    const cats=(j.data&&j.data.categories)||[];
    const row=document.getElementById('cats-row');
    row.innerHTML='<button class="cat-chip'+(CATEGORY===''?' active':'')+'" data-cat="" onclick="selectCategory(\'\')">All Products</button>'
      +cats.map(function(c){
        return '<button class="cat-chip'+(CATEGORY===c.name?' active':'')+'" data-cat="'+esc(c.name)+'" onclick="selectCategory(\''+esc(c.name).replace(/'/g,"\\'")+'\')">'
          +'<span class="sw" style="background:'+(c.color||'#e0392b')+'"></span>'+esc(c.name)+' <span style="opacity:.55">('+c.product_count+')</span></button>';
      }).join('');
  }catch(e){}
}

async function loadProducts(){
  const grid=document.getElementById('product-grid');
  grid.innerHTML='<div class="empty-msg">Loading products…</div>';
  try{
    const params=new URLSearchParams();
    if(CATEGORY) params.set('category',CATEGORY);
    if(SEARCH) params.set('q',SEARCH);
    const r=await fetch(API_CATALOG+'?'+params.toString());
    const j=await r.json();
    PRODUCTS=Array.isArray(j.data)?j.data:[];
    renderProducts();
  }catch(e){
    grid.innerHTML='<div class="empty-msg">Could not load products right now. Please refresh.</div>';
  }
}

function renderProducts(){
  const grid=document.getElementById('product-grid');
  const countEl=document.getElementById('result-count');
  countEl.textContent=PRODUCTS.length?PRODUCTS.length+' item'+(PRODUCTS.length===1?'':'s'):'';
  if(!PRODUCTS.length){
    grid.innerHTML='<div class="empty-msg">No products found. Try a different search or category.</div>';
    return;
  }
  grid.innerHTML=PRODUCTS.map(function(p){
    const inCart=CART[p.id]?CART[p.id].qty:0;
    const low=p.stock>0&&p.stock<=5;
    const mrp=(p.list_price&&p.list_price>p.sell)?'<span class="price-mrp">'+fmtMoney(p.list_price)+'</span>':'';
    const img=p.image_url?'<img src="'+esc(p.image_url)+'" alt="'+esc(p.name)+'" loading="lazy">':'<span class="ph">🎆</span>';
    return '<div class="card">'
      +'<div class="card-img">'+img+'<span class="stock-badge'+(low?' low':'')+'">'+(low?'Only '+p.stock+' left':'In Stock')+'</span></div>'
      +'<div class="card-body">'
        +'<div class="card-cat">'+esc(p.category||'')+'</div>'
        +'<div class="card-name">'+esc(p.name)+'</div>'
        +(p.brand?'<div class="card-brand">'+esc(p.brand)+'</div>':'')
        +'<div class="card-price"><span class="price-now">'+fmtMoney(p.sell)+'</span>'+mrp+'</div>'
        +'<div class="card-actions">'
          +(inCart>0
            ?'<div class="qty-box"><button onclick="changeQty('+p.id+',-1)">−</button><span>'+inCart+'</span><button onclick="changeQty('+p.id+',1)">+</button></div><button class="add-btn in-cart" onclick="openCart()">In Cart</button>'
            :'<button class="add-btn" style="flex:none;width:100%" onclick="changeQty('+p.id+',1)">+ Add to Cart</button>')
        +'</div>'
      +'</div>'
    +'</div>';
  }).join('');
}

function selectCategory(cat){
  CATEGORY=cat;
  document.querySelectorAll('.cat-chip').forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-cat')===cat); });
  loadProducts();
}

let searchTimer=null;
function onSearch(){
  SEARCH=document.getElementById('search-input').value.trim();
  clearTimeout(searchTimer);
  searchTimer=setTimeout(loadProducts,300);
}

function changeQty(productId, delta){
  const product=PRODUCTS.find(function(p){return p.id===productId;});
  if(!product)return;
  const cur=CART[productId]?CART[productId].qty:0;
  let next=cur+delta;
  if(next<0)next=0;
  if(next>product.stock)next=product.stock;
  if(next<=0){ delete CART[productId]; }
  else{ CART[productId]={product:product, qty:next}; }
  saveCart();
  renderProducts();
  renderCart();
}

function cartCount(){ return Object.values(CART).reduce(function(s,c){return s+c.qty;},0); }
function cartSubtotal(){ return Object.values(CART).reduce(function(s,c){return s+c.qty*(+c.product.sell||0);},0); }

function renderCartBadge(){
  const n=cartCount();
  const badge=document.getElementById('cart-badge');
  badge.style.display=n>0?'flex':'none';
  badge.textContent=n;
  document.getElementById('cart-total-mini').textContent=n>0?fmtMoney(cartSubtotal()):'';
}

function renderCart(){
  renderCartBadge();
  const wrap=document.getElementById('cart-items');
  const entries=Object.values(CART);
  if(!entries.length){
    wrap.innerHTML='<div class="empty-msg">Your cart is empty. Add some products to get started!</div>';
  }else{
    wrap.innerHTML=entries.map(function(c){
      const p=c.product;
      const img=p.image_url?'<img src="'+esc(p.image_url)+'" alt="">':'<div class="ph">🎆</div>';
      return '<div class="cart-row">'+img
        +'<div class="cart-row-info">'
          +'<div class="cart-row-name">'+esc(p.name)+'</div>'
          +'<div class="cart-row-price">'+fmtMoney(p.sell)+' × '+c.qty+' = '+fmtMoney(p.sell*c.qty)+'</div>'
          +'<div class="cart-row-actions">'
            +'<div class="qty-box"><button onclick="changeQty('+p.id+',-1)">−</button><span>'+c.qty+'</span><button onclick="changeQty('+p.id+',1)">+</button></div>'
            +'<button class="rm-btn" onclick="removeFromCart('+p.id+')">Remove</button>'
          +'</div>'
        +'</div>'
      +'</div>';
    }).join('');
  }
  document.getElementById('cart-subtotal').textContent=fmtMoney(cartSubtotal());
  document.getElementById('checkout-open-btn').disabled=!entries.length;
}

function removeFromCart(productId){
  delete CART[productId];
  saveCart();
  renderProducts();
  renderCart();
}

function openCart(){
  document.getElementById('overlay').classList.add('open');
  document.getElementById('cart-drawer').classList.add('open');
}
function closeCart(){
  document.getElementById('cart-drawer').classList.remove('open');
  if(!document.getElementById('checkout-modal').classList.contains('open')) document.getElementById('overlay').classList.remove('open');
}
function closeAllOverlays(){
  closeCart();
  closeCheckout();
}

function openCheckout(){
  if(!cartCount())return;
  document.getElementById('overlay').classList.add('open');
  document.getElementById('checkout-modal').classList.add('open');
  document.getElementById('ck-total').textContent=fmtMoney(cartSubtotal());
  document.getElementById('checkout-err').style.display='none';
}
function closeCheckout(){
  document.getElementById('checkout-modal').classList.remove('open');
  if(!document.getElementById('cart-drawer').classList.contains('open')) document.getElementById('overlay').classList.remove('open');
}

function showCheckoutError(msg){
  const el=document.getElementById('checkout-err');
  el.textContent=msg;
  el.style.display='block';
}

async function placeOrder(){
  const name=document.getElementById('ck-name').value.trim();
  const phone=document.getElementById('ck-phone').value.trim();
  const address=document.getElementById('ck-address').value.trim();
  const honeypot=document.getElementById('ck-website').value;
  if(!name){ showCheckoutError('Please enter your name'); return; }
  if(!/^\d{10,13}$/.test(phone.replace(/\D/g,''))){ showCheckoutError('Please enter a valid phone number'); return; }
  if(!address){ showCheckoutError('Please enter a delivery address'); return; }
  const items=Object.values(CART).map(function(c){ return {product_id:c.product.id, qty:c.qty}; });
  if(!items.length){ showCheckoutError('Your cart is empty'); return; }

  const btn=document.getElementById('place-order-btn');
  btn.disabled=true; btn.textContent='Placing order…';
  try{
    const r=await fetch(API_CHECKOUT,{
      method:'POST', headers:{'Content-Type':'application/json'},
      body:JSON.stringify({customer:name, phone:phone, address:address, items:items, website:honeypot})
    });
    const j=await r.json();
    if(!j.success){ showCheckoutError(j.message||'Something went wrong. Please try again.'); btn.disabled=false; btn.innerHTML='Place Order — <span id="ck-total"></span>'; return; }
    CART={}; saveCart(); renderProducts(); renderCart();
    document.getElementById('checkout-body').innerHTML=
      '<div class="success-box">'
        +'<div class="ic">🎉</div>'
        +'<h3>Order Received!</h3>'
        +'<p>Thank you'+(j.data.order_no?', your order number is':'')+'</p>'
        +(j.data.order_no?'<div class="order-no">'+esc(j.data.order_no)+'</div>':'')
        +'<p>Our team will contact you shortly on your phone number to confirm payment and delivery details.</p>'
        +'<button class="checkout-btn" onclick="closeCheckout()">Continue Shopping</button>'
      +'</div>';
  }catch(e){
    showCheckoutError('Could not reach the server. Please check your connection and try again.');
    btn.disabled=false; btn.textContent='Place Order';
  }
}

document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeAllOverlays(); });

loadCart();
renderCart();
loadBranding();
loadMeta();
loadProducts();
</script>
</body>
</html>
