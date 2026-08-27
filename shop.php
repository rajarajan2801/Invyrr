<?php
// Public storefront homepage — no session/auth, reachable by anyone.
// Talks only to api/public_catalog.php (read) and api/public_checkout.php
// (write) — both deliberately narrow, unauthenticated endpoints; every
// other api/*.php file in this app still requires a login. See those two
// files' header comments for the reasoning.
//
// Visual style is deliberately modeled on geminicrackers.com/products.php
// (a reference the shop owner asked to match): gradient contact strip,
// dark header with a circular logo, a flat product-row list grouped by
// category with divider bars, a sticky search/filter bar, and a sticky
// bottom cart pill — rather than the card-grid look this file started
// with.
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
  --purple1:#c2185b; --purple2:#6a1b9a;
  --gold:#f5b942; --orange:#f4743b;
  --header-bg:#160a12; --header-bg2:#241228;
  --paper:#ffffff; --paper2:#f7f5f8; --line:#e8e3ea;
  --ink:#221420; --ink2:#5a4d57; --ink3:#8d8092;
  --green:#2e7d32; --green-d:#256428; --red:#d32f2f;
  --radius:12px;
}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--paper);color:var(--ink);line-height:1.5}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
button{font-family:inherit;cursor:pointer}
.container{max-width:1180px;margin:0 auto;padding:0 18px}

/* ── Contact strip ── */
.topbar{background:linear-gradient(90deg,var(--purple1),var(--purple2));color:#fff;font-size:.78rem}
.topbar-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 0;flex-wrap:wrap}
.topbar-addr,.topbar-phone{display:flex;align-items:center;gap:7px;white-space:nowrap;opacity:.95}
.topbar-mid{display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:center;font-weight:700}
.topbar-mid .cta{background:var(--gold);color:#3a2400;font-weight:800;padding:5px 14px;border-radius:16px;font-size:.76rem;border:none}

/* ── Header ── */
header.site{position:sticky;top:0;z-index:40;background:var(--header-bg);border-bottom:1px solid rgba(255,255,255,.08)}
.header-row{display:flex;align-items:center;gap:18px;padding:10px 0}
.brand{display:flex;align-items:center;gap:10px;white-space:nowrap}
.brand img{width:52px;height:52px;border-radius:50%;object-fit:cover;background:#fff;border:2px solid var(--gold)}
.brand-name{font-weight:800;font-size:1.15rem;color:#fff;letter-spacing:.2px}
.header-search{flex:1;max-width:420px;position:relative}
.header-search input{width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:#fff;padding:9px 14px 9px 34px;border-radius:20px;font-size:.86rem;outline:none}
.header-search input::placeholder{color:rgba(255,255,255,.55)}
.header-search input:focus{border-color:var(--gold)}
.header-search svg{position:absolute;left:11px;top:50%;transform:translateY(-50%);opacity:.6;color:#fff;pointer-events:none}
.cart-btn{position:relative;display:flex;align-items:center;gap:8px;background:var(--gold);border:none;color:#3a2400;padding:9px 16px;border-radius:20px;font-weight:800;font-size:.85rem;white-space:nowrap}
.cart-badge{position:absolute;top:-6px;right:-6px;background:var(--purple1);color:#fff;font-size:.66rem;font-weight:800;min-width:18px;height:18px;border-radius:9px;display:flex;align-items:center;justify-content:center;padding:0 4px;border:2px solid var(--header-bg)}

/* ── Hero ── */
.hero{background:radial-gradient(circle at 15% 25%,rgba(244,116,59,.35),transparent 55%),radial-gradient(circle at 85% 65%,rgba(245,185,66,.28),transparent 50%),linear-gradient(180deg,#2b1408,#160a06);padding:46px 0 36px;text-align:center;border-bottom:1px solid rgba(255,255,255,.08)}
.hero h1{font-size:clamp(1.6rem,3.6vw,2.4rem);margin:0 0 10px;font-weight:800;letter-spacing:-.02em;color:#fff}
.hero h1 span{background:linear-gradient(90deg,var(--gold),var(--orange));-webkit-background-clip:text;background-clip:text;color:transparent}
.hero p{color:#e7dccf;max-width:520px;margin:0 auto 20px;font-size:.92rem}
.hero-btn{display:inline-block;background:linear-gradient(90deg,var(--purple1),var(--purple2));color:#fff;font-weight:700;padding:11px 26px;border-radius:22px;font-size:.9rem;border:none}
.hero-btn:hover{opacity:.92}

/* ── Sticky search / filter bar ── */
.tools-bar{position:sticky;top:73px;z-index:35;background:var(--paper);border-bottom:1px solid var(--line);box-shadow:0 2px 6px rgba(0,0,0,.04)}
.tools-row{display:flex;align-items:stretch}
.tools-search{flex:1;position:relative;display:flex;align-items:center}
.tools-search svg{position:absolute;left:16px;opacity:.45}
.tools-search input{width:100%;border:none;padding:14px 14px 14px 42px;font-size:.88rem;outline:none;background:transparent;color:var(--ink)}
.tools-filter{position:relative;border-left:1px solid var(--line)}
.tools-filter>button{height:100%;border:none;background:var(--paper2);color:var(--ink2);font-weight:700;font-size:.85rem;padding:14px 20px;display:flex;align-items:center;gap:6px;white-space:nowrap}
.filter-menu{position:absolute;top:100%;right:0;background:#fff;border:1px solid var(--line);border-radius:0 0 10px 10px;box-shadow:0 12px 24px rgba(0,0,0,.12);max-height:320px;overflow-y:auto;min-width:240px;display:none;z-index:36}
.filter-menu.open{display:block}
.filter-menu button{display:block;width:100%;text-align:left;background:none;border:none;padding:10px 16px;font-size:.85rem;color:var(--ink);border-bottom:1px solid var(--paper2)}
.filter-menu button:hover{background:var(--paper2)}
.filter-menu button.active{color:var(--purple1);font-weight:800}

/* ── Category divider ── */
.cat-divider{background:var(--orange);color:#fff;text-align:center;font-weight:800;letter-spacing:.4px;padding:10px 14px;font-size:.86rem;text-transform:uppercase}

/* ── Product rows ── */
.section-title{max-width:1180px;margin:22px auto 6px;padding:0 18px;font-size:.8rem;color:var(--ink3);display:flex;justify-content:space-between}
.list{max-width:1180px;margin:0 auto 90px;border-top:1px solid var(--line)}
.row{display:flex;align-items:center;gap:14px;padding:12px 18px;border-bottom:1px solid var(--line)}
.row:hover{background:var(--paper2)}
.row-img{width:56px;height:56px;border-radius:8px;background:var(--paper2);flex:0 0 auto;overflow:hidden;display:flex;align-items:center;justify-content:center}
.row-img img{width:100%;height:100%;object-fit:cover}
.row-img .ph{font-size:1.4rem;opacity:.35}
.row-info{flex:1;min-width:0}
.row-name{font-weight:800;font-size:.86rem;text-transform:uppercase;letter-spacing:.2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.row-sub{font-size:.74rem;color:var(--ink3);margin-top:2px;display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.stock-pill{font-size:.66rem;font-weight:700;padding:1px 7px;border-radius:8px;background:rgba(46,125,50,.12);color:var(--green-d)}
.stock-pill.low{background:rgba(244,116,59,.14);color:#b3521f}
.row-price{text-align:right;flex:0 0 auto;min-width:88px}
.price-now{font-weight:800;font-size:.92rem;color:var(--ink)}
.price-mrp{font-size:.72rem;color:var(--ink3);text-decoration:line-through;display:block}
.row-action{flex:0 0 auto;min-width:118px;display:flex;justify-content:center}
.add-btn{background:var(--green);color:#fff;border:none;font-weight:800;font-size:.78rem;padding:9px 20px;border-radius:8px}
.add-btn:hover{background:var(--green-d)}
.qty-box{display:flex;align-items:center;gap:6px}
.qty-box button{width:32px;height:32px;border-radius:8px;border:none;font-weight:800;font-size:1rem;display:flex;align-items:center;justify-content:center}
.qty-box .btn-minus{background:var(--red);color:#fff}
.qty-box .btn-plus{background:var(--green);color:#fff}
.qty-box input{width:38px;text-align:center;border:1px solid var(--line);border-radius:6px;font-weight:800;font-size:.85rem;padding:6px 2px}
.row-total{flex:0 0 auto;min-width:80px;text-align:right;font-size:.7rem;color:var(--ink3);text-transform:uppercase;font-weight:700}
.row-total b{display:block;color:var(--red);font-size:.88rem;font-weight:800;margin-top:2px}
.empty-msg{text-align:center;padding:50px 10px;color:var(--ink3)}

/* ── Sticky bottom cart pill ── */
.cart-pill{position:fixed;left:50%;bottom:18px;transform:translateX(-50%);background:var(--gold);color:#3a2400;border-radius:26px;padding:11px 22px;display:none;align-items:center;gap:10px;font-weight:800;font-size:.85rem;box-shadow:0 10px 24px rgba(0,0,0,.25);z-index:50;border:none}
.cart-pill.show{display:flex}
.cart-pill .badge{background:var(--purple1);color:#fff;border-radius:10px;min-width:20px;height:20px;padding:0 5px;display:flex;align-items:center;justify-content:center;font-size:.72rem}

/* ── Cart drawer ── */
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:60;display:none}
.overlay.open{display:block}
.drawer{position:fixed;top:0;right:0;height:100%;width:min(400px,92vw);background:#fff;border-left:1px solid var(--line);z-index:61;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .25s ease}
.drawer.open{transform:translateX(0)}
.drawer-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--line);background:var(--header-bg);color:#fff}
.drawer-head b{font-size:1rem}
.close-x{background:none;border:none;color:inherit;font-size:1.3rem;line-height:1}
.drawer-items{flex:1;overflow-y:auto;padding:10px 14px}
.cart-row{display:flex;gap:10px;padding:10px 0;border-bottom:1px solid var(--line)}
.cart-row img{width:52px;height:52px;border-radius:8px;object-fit:cover;background:var(--paper2)}
.cart-row .ph{width:52px;height:52px;border-radius:8px;background:var(--paper2);display:flex;align-items:center;justify-content:center;font-size:1.3rem;opacity:.4}
.cart-row-info{flex:1;min-width:0}
.cart-row-name{font-size:.83rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cart-row-price{font-size:.76rem;color:var(--ink3);margin-top:2px}
.cart-row-actions{display:flex;align-items:center;gap:6px;margin-top:6px}
.rm-btn{background:none;border:none;color:var(--red);font-size:.72rem;font-weight:700;margin-left:auto}
.drawer-foot{padding:14px 18px;border-top:1px solid var(--line)}
.subtotal-row{display:flex;justify-content:space-between;font-size:.9rem;font-weight:700;margin-bottom:12px}
.checkout-btn{width:100%;background:linear-gradient(90deg,var(--purple1),var(--purple2));color:#fff;border:none;padding:13px;border-radius:10px;font-weight:800;font-size:.9rem}
.checkout-btn:disabled{opacity:.5}

/* ── Checkout modal ── */
.modal-back{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:70;display:none;align-items:center;justify-content:center;padding:16px}
.modal-back.open{display:flex}
.modal-box{background:#fff;border-radius:var(--radius);max-width:440px;width:100%;max-height:90vh;overflow-y:auto}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid var(--line);background:var(--header-bg);color:#fff;border-radius:var(--radius) var(--radius) 0 0}
.modal-body{padding:18px}
.field{margin-bottom:13px}
.field label{display:block;font-size:.74rem;color:var(--ink3);font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.field input,.field textarea{width:100%;background:var(--paper2);border:1px solid var(--line);color:var(--ink);padding:10px 12px;border-radius:8px;font-size:.88rem;font-family:inherit;outline:none}
.field input:focus,.field textarea:focus{border-color:var(--purple1)}
.hp-field{position:absolute;left:-9999px;top:-9999px}
.err-banner{background:rgba(211,47,47,.08);border:1px solid rgba(211,47,47,.3);color:var(--red);padding:9px 12px;border-radius:8px;font-size:.8rem;margin-bottom:12px;display:none}
.success-box{text-align:center;padding:20px 6px}
.success-box .ic{font-size:2.6rem;margin-bottom:10px}
.success-box h3{margin:0 0 8px}
.success-box p{color:var(--ink2);font-size:.88rem}
.order-no{display:inline-block;background:var(--paper2);border:1px dashed var(--purple1);color:var(--purple1);font-weight:800;padding:8px 16px;border-radius:8px;margin:10px 0;letter-spacing:.5px}

/* ── Footer ── */
footer{background:var(--header-bg);color:#c9bcc6;padding:30px 0;font-size:.82rem}
footer .foot-row{display:flex;flex-wrap:wrap;gap:20px;justify-content:space-between}
footer b{color:#fff}

@media (max-width:640px){
  .header-search{display:none}
  .hero{padding:34px 0 26px}
  .row-sub .stock-pill{display:none}
}
</style>
</head>
<body>

<div class="topbar">
  <div class="container topbar-row">
    <div class="topbar-addr" id="topbar-addr"></div>
    <div class="topbar-mid"><span>Welcome to RR Crackers! Something you love is now on sale!</span><a href="#catalog" class="cta">Shop Now!</a></div>
    <div class="topbar-phone" id="topbar-phone"></div>
  </div>
</div>

<header class="site">
  <div class="container header-row">
    <div class="brand">
      <img src="assets/images/branding/rr-crackers-logo.png" alt="RR Crackers">
      <span class="brand-name" id="brand-name">RR Crackers</span>
    </div>
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

<div class="tools-bar" id="catalog">
  <div class="container tools-row">
    <div class="tools-search">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="search-input-2" placeholder="Search an item" oninput="onSearch2()">
    </div>
    <div class="tools-filter">
      <button onclick="toggleFilterMenu()">Filters <span id="filter-current"></span> ▾</button>
      <div class="filter-menu" id="filter-menu"></div>
    </div>
  </div>
</div>

<div class="section-title"><span id="cat-heading">All Products</span><span id="result-count"></span></div>
<div id="product-list"><div class="empty-msg">Loading products…</div></div>

<footer>
  <div class="container foot-row">
    <div>
      <b id="foot-name">RR Crackers</b><br>
      <span id="foot-addr"></span>
    </div>
    <div id="foot-contact"></div>
  </div>
</footer>

<!-- Sticky bottom cart pill -->
<button class="cart-pill" id="cart-pill" onclick="openCart()">
  <span id="pill-count">0 items</span> · <span id="pill-subtotal">₹0</span>
  <span>|</span> 🛒 View Cart <span class="badge" id="pill-badge">0</span>
</button>

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
let CATEGORIES=[];
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
    document.getElementById('topbar-addr').innerHTML=d.business_address?('📍 '+esc(d.business_address)):'';
    document.getElementById('topbar-phone').innerHTML=d.business_phone?('📞 '+esc(d.business_phone)):'';
    const bits=[];
    if(d.business_phone) bits.push('📞 '+esc(d.business_phone));
    if(d.business_email) bits.push('✉️ '+esc(d.business_email));
    document.getElementById('foot-contact').innerHTML=bits.join(' &nbsp;·&nbsp; ');
  }catch(e){ /* branding is cosmetic only -- page still works without it */ }
}

async function loadMeta(){
  try{
    const r=await fetch(API_CATALOG+'?meta=1'); const j=await r.json();
    CATEGORIES=(j.data&&j.data.categories)||[];
    renderFilterMenu();
  }catch(e){}
}

function renderFilterMenu(){
  const menu=document.getElementById('filter-menu');
  menu.innerHTML='<button class="'+(CATEGORY===''?'active':'')+'" onclick="selectCategory(\'\')">All Products</button>'
    +CATEGORIES.map(function(c){
      return '<button class="'+(CATEGORY===c.name?'active':'')+'" onclick="selectCategory(\''+esc(c.name).replace(/'/g,"\\'")+'\')">'+esc(c.name)+' ('+c.product_count+')</button>';
    }).join('');
  document.getElementById('filter-current').textContent=CATEGORY?': '+CATEGORY:'';
  document.getElementById('cat-heading').textContent=CATEGORY||'All Products';
}
function toggleFilterMenu(){
  document.getElementById('filter-menu').classList.toggle('open');
}
document.addEventListener('click', function(e){
  const wrap=document.querySelector('.tools-filter');
  if(wrap && !wrap.contains(e.target)) document.getElementById('filter-menu').classList.remove('open');
});

async function loadProducts(){
  const list=document.getElementById('product-list');
  list.innerHTML='<div class="empty-msg">Loading products…</div>';
  try{
    const params=new URLSearchParams();
    if(CATEGORY) params.set('category',CATEGORY);
    if(SEARCH) params.set('q',SEARCH);
    const r=await fetch(API_CATALOG+'?'+params.toString());
    const j=await r.json();
    PRODUCTS=Array.isArray(j.data)?j.data:[];
    renderProducts();
  }catch(e){
    list.innerHTML='<div class="empty-msg">Could not load products right now. Please refresh.</div>';
  }
}

function productRowHtml(p){
  const inCart=CART[p.id]?CART[p.id].qty:0;
  const low=p.stock>0&&p.stock<=5;
  const mrp=(p.list_price&&p.list_price>p.sell)?'<span class="price-mrp">'+fmtMoney(p.list_price)+'</span>':'';
  const img=p.image_url?'<img src="'+esc(p.image_url)+'" alt="'+esc(p.name)+'" loading="lazy">':'<span class="ph">🎆</span>';
  const lineTotal=inCart>0?fmtMoney(p.sell*inCart):'₹0';
  return '<div class="row" data-pid="'+p.id+'">'
    +'<div class="row-img">'+img+'</div>'
    +'<div class="row-info">'
      +'<div class="row-name">'+esc(p.name)+'</div>'
      +'<div class="row-sub">'+esc(p.unit||'pcs')+(p.brand?' · '+esc(p.brand):'')+' <span class="stock-pill'+(low?' low':'')+'">'+(low?'Only '+p.stock+' left':'In Stock')+'</span></div>'
    +'</div>'
    +'<div class="row-price"><span class="price-now">'+fmtMoney(p.sell)+'</span>'+mrp+'</div>'
    +'<div class="row-action">'+(inCart>0
        ?'<div class="qty-box"><button class="btn-minus" onclick="changeQty('+p.id+',-1)">'+(inCart===1?'&#128465;':'&minus;')+'</button><input type="text" readonly value="'+inCart+'"><button class="btn-plus" onclick="changeQty('+p.id+',1)">+</button></div>'
        :'<button class="add-btn" onclick="changeQty('+p.id+',1)">Add</button>')
    +'</div>'
    +'<div class="row-total">Total<b>'+lineTotal+'</b></div>'
  +'</div>';
}

function renderProducts(){
  const list=document.getElementById('product-list');
  const countEl=document.getElementById('result-count');
  countEl.textContent=PRODUCTS.length?PRODUCTS.length+' item'+(PRODUCTS.length===1?'':'s'):'';
  if(!PRODUCTS.length){
    list.innerHTML='<div class="empty-msg">No products found. Try a different search or category.</div>';
    return;
  }
  if(CATEGORY||SEARCH){
    // Filtered/searched view -- flat list, no divider needed since it's
    // already narrowed to one thing the visitor asked for.
    list.innerHTML='<div class="list">'+PRODUCTS.map(productRowHtml).join('')+'</div>';
    return;
  }
  // Browsing everything -- group into an inline list with a divider bar
  // whenever the category changes, same as the reference site's flow
  // (products are already ORDER BY category, name from the API).
  let html='<div class="list">';
  let lastCat=null;
  PRODUCTS.forEach(function(p){
    const cat=p.category||'Other';
    if(cat!==lastCat){
      html+='<div class="cat-divider">'+esc(cat)+'</div>';
      lastCat=cat;
    }
    html+=productRowHtml(p);
  });
  html+='</div>';
  list.innerHTML=html;
}

function selectCategory(cat){
  CATEGORY=cat;
  renderFilterMenu();
  document.getElementById('filter-menu').classList.remove('open');
  loadProducts();
}

let searchTimer=null;
function onSearch(){
  SEARCH=document.getElementById('search-input').value.trim();
  document.getElementById('search-input-2').value=SEARCH;
  clearTimeout(searchTimer);
  searchTimer=setTimeout(loadProducts,300);
}
function onSearch2(){
  SEARCH=document.getElementById('search-input-2').value.trim();
  document.getElementById('search-input').value=SEARCH;
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
  const sub=cartSubtotal();
  const badge=document.getElementById('cart-badge');
  badge.style.display=n>0?'flex':'none';
  badge.textContent=n;
  document.getElementById('cart-total-mini').textContent=n>0?fmtMoney(sub):'';

  const pill=document.getElementById('cart-pill');
  pill.classList.toggle('show', n>0);
  document.getElementById('pill-count').textContent=n+' item'+(n===1?'':'s');
  document.getElementById('pill-subtotal').textContent=fmtMoney(sub);
  document.getElementById('pill-badge').textContent=n;
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
            +'<div class="qty-box"><button class="btn-minus" onclick="changeQty('+p.id+',-1)">'+(c.qty===1?'&#128465;':'&minus;')+'</button><input type="text" readonly value="'+c.qty+'"><button class="btn-plus" onclick="changeQty('+p.id+',1)">+</button></div>'
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
