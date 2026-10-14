<?php
// Public order-status lookup — no session/auth, reachable by anyone, same
// deliberate exception as shop.php (see its header comment). Talks only
// to api/public_order_status.php (read-only, phone+order_no scoped) and
// api/settings.php's public GET (business name/address/phone for the
// header — the same call shop.php already makes).
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>Track Your Order</title>
<meta name="description" content="Check the status of your order — picking, packing, or dispatched.">
<style>
:root{
  --purple1:#c2185b; --purple2:#6a1b9a;
  --gold:#f5b942; --orange:#f4743b;
  --header-bg:#160a12; --header-bg2:#241228;
  --paper:#ffffff; --paper2:#f7f5f8; --line:#e8e3ea;
  --ink:#221420; --ink2:#5a4d57; --ink3:#8d8092;
  --green:#2e7d32; --green-d:#256428; --red:#d32f2f; --amber:#b3521f;
  --radius:12px;
}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',system-ui,-apple-system,sans-serif;background:var(--paper2);color:var(--ink);line-height:1.5}
a{color:inherit;text-decoration:none}
.container{max-width:640px;margin:0 auto;padding:0 18px}

.topbar{background:linear-gradient(90deg,var(--purple1),var(--purple2));color:#fff;font-size:.78rem}
.topbar-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:9px 0;max-width:1180px;margin:0 auto;padding-left:18px;padding-right:18px}
.topbar-addr,.topbar-phone{display:flex;align-items:center;gap:7px;white-space:nowrap;opacity:.95}

header.site{background:var(--header-bg);border-bottom:1px solid rgba(255,255,255,.08)}
.header-row{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:14px 0;max-width:1180px;margin:0 auto;padding-left:18px;padding-right:18px}
.brand{display:flex;align-items:center;gap:10px;white-space:nowrap}
.brand img{width:44px;height:44px;border-radius:50%;object-fit:cover;background:#fff;border:2px solid var(--gold)}
.brand-name{font-weight:800;font-size:1.05rem;color:#fff;letter-spacing:.2px}
.back-link{color:#e7dccf;font-size:.82rem;font-weight:600;opacity:.9}
.back-link:hover{opacity:1}

main{padding:32px 0 60px}
.card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:0 4px 16px rgba(0,0,0,.05)}
.lookup-card{padding:26px 24px}
.lookup-card h1{margin:0 0 6px;font-size:1.3rem;font-weight:800}
.lookup-card p.sub{margin:0 0 20px;color:var(--ink3);font-size:.88rem}
.field{margin-bottom:14px}
.field label{display:block;font-size:.74rem;color:var(--ink3);font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px}
.field input{width:100%;background:var(--paper2);border:1px solid var(--line);color:var(--ink);padding:11px 13px;border-radius:8px;font-size:.92rem;font-family:inherit;outline:none}
.field input:focus{border-color:var(--purple1)}
.field .hint{font-size:.72rem;color:var(--ink3);margin-top:4px}
.hp-field{position:absolute;left:-9999px;top:-9999px}
.submit-btn{width:100%;background:linear-gradient(90deg,var(--purple1),var(--purple2));color:#fff;border:none;padding:13px;border-radius:10px;font-weight:800;font-size:.92rem;cursor:pointer}
.submit-btn:disabled{opacity:.6;cursor:default}
.err-banner{background:rgba(211,47,47,.08);border:1px solid rgba(211,47,47,.3);color:var(--red);padding:9px 12px;border-radius:8px;font-size:.82rem;margin-bottom:14px;display:none}

#results{max-width:640px;margin:18px auto 0;padding:0 18px}
.order-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;margin-bottom:14px}
.order-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.order-no{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:800;color:var(--purple1);font-size:.95rem}
.order-date{font-size:.78rem;color:var(--ink3)}

.steps{display:flex;align-items:center;margin:8px 0 4px}
.step{flex:1;display:flex;flex-direction:column;align-items:center;position:relative}
.step-dot{width:28px;height:28px;border-radius:50%;background:var(--paper2);border:2px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:800;color:var(--ink3);z-index:1}
.step-label{font-size:.68rem;color:var(--ink3);margin-top:6px;text-align:center;font-weight:600}
.step-line{position:absolute;top:13px;left:-50%;width:100%;height:3px;background:var(--line);z-index:0}
.step:first-child .step-line{display:none}
.step.done .step-dot{background:var(--green);border-color:var(--green);color:#fff}
.step.done .step-line{background:var(--green)}
.step.current .step-dot{background:var(--purple1);border-color:var(--purple1);color:#fff;box-shadow:0 0 0 4px rgba(194,24,91,.15)}
.step.current .step-label{color:var(--purple1);font-weight:800}
.step.done .step-label{color:var(--green-d)}

.hold-banner{margin-top:14px;background:rgba(244,116,59,.1);border:1px solid rgba(244,116,59,.35);color:var(--amber);padding:9px 12px;border-radius:8px;font-size:.8rem;font-weight:600}
.dispatch-box{margin-top:14px;background:var(--paper2);border-radius:8px;padding:10px 14px;font-size:.8rem;color:var(--ink2)}
.dispatch-box div{margin-bottom:3px}
.dispatch-box b{color:var(--ink)}

.empty-msg{text-align:center;padding:30px 10px;color:var(--ink3)}
.empty-msg .ic{font-size:2rem;margin-bottom:8px;display:block}
.spinner{display:inline-block;width:20px;height:20px;border:3px solid rgba(194,24,91,.2);border-top-color:var(--purple1);border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading{text-align:center;padding:30px 10px}

footer{background:var(--header-bg);color:#c9bcc6;padding:22px 0;font-size:.8rem;text-align:center;margin-top:40px}
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-row">
    <div class="topbar-addr" id="topbar-addr"></div>
    <div class="topbar-phone" id="topbar-phone"></div>
  </div>
</div>

<header class="site">
  <div class="header-row">
    <div class="brand">
      <img src="assets/images/branding/rr-crackers-logo.png" alt="" onerror="this.style.display='none'">
      <span class="brand-name" id="brand-name">RR Crackers</span>
    </div>
    <a class="back-link" href="shop.php">← Back to Shop</a>
  </div>
</header>

<main>
  <div class="container">
    <div class="card lookup-card">
      <h1>📦 Track Your Order</h1>
      <p class="sub">Enter the mobile number used to place the order. Adding the order number narrows it down if you have it handy.</p>
      <div class="err-banner" id="err-banner"></div>
      <form id="lookup-form" onsubmit="return onLookupSubmit(event)">
        <div class="field">
          <label for="f-phone">Mobile Number</label>
          <input type="tel" id="f-phone" inputmode="numeric" placeholder="10-digit mobile number" maxlength="13" required>
        </div>
        <div class="field">
          <label for="f-order">Order Number <span style="font-weight:400;text-transform:none">(optional)</span></label>
          <input type="text" id="f-order" placeholder="e.g. WEB251014-A1B2">
          <div class="hint">Leave this blank to see all your recent orders.</div>
        </div>
        <!-- Honeypot — hidden from real visitors; a bot that fills every field will fill this one too. -->
        <div class="field hp-field"><label for="f-website">Website</label><input type="text" id="f-website" tabindex="-1" autocomplete="off"></div>
        <button type="submit" class="submit-btn" id="submit-btn">🔎 Check Status</button>
      </form>
    </div>
  </div>
  <div id="results"></div>
</main>

<footer>
  <span id="foot-name">RR Crackers</span> &nbsp;·&nbsp; <span id="foot-contact"></span>
</footer>

<script>
const API_SETTINGS='api/settings.php';
const API_STATUS='api/public_order_status.php';

function esc(s){ return (s==null?'':String(s)).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function fmtDate(d){
  if(!d) return '—';
  const p=String(d).split('-'); if(p.length<3) return d;
  const m=['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
  return p[2].padStart(2,'0')+'-'+(m[parseInt(p[1],10)-1]||p[1])+'-'+p[0].slice(-2);
}

let BUSINESS_PHONE='';
async function loadBranding(){
  try{
    const r=await fetch(API_SETTINGS); const s=await r.json();
    const d=s.data||{};
    const name=d.business_name||'RR Crackers';
    document.getElementById('brand-name').textContent=name;
    document.getElementById('foot-name').textContent=name;
    document.title='Track Your Order — '+name;
    document.getElementById('topbar-addr').innerHTML=d.business_address?('📍 '+esc(d.business_address)):'';
    document.getElementById('topbar-phone').innerHTML=d.business_phone?('📞 '+esc(d.business_phone)):'';
    BUSINESS_PHONE=d.business_phone||'';
    const bits=[];
    if(d.business_phone) bits.push('📞 '+esc(d.business_phone));
    if(d.business_email) bits.push('✉️ '+esc(d.business_email));
    document.getElementById('foot-contact').innerHTML=bits.join(' &nbsp;·&nbsp; ');
  }catch(e){ /* branding is cosmetic only -- page still works without it */ }
}
loadBranding();

const STEP_LABELS=['Received','Picking','Packing','Dispatched'];

function buildSteps(step){
  let html='<div class="steps">';
  STEP_LABELS.forEach(function(label,i){
    const n=i+1;
    const cls = n<step ? 'done' : (n===step ? 'current' : '');
    html+='<div class="step '+cls+'">'
      +'<div class="step-line"></div>'
      +'<div class="step-dot">'+(n<step?'✓':n)+'</div>'
      +'<div class="step-label">'+label+'</div>'
      +'</div>';
  });
  html+='</div>';
  return html;
}

function buildOrderCard(o){
  let html='<div class="order-card">'
    +'<div class="order-head"><span class="order-no">'+esc(o.order_no||'—')+'</span><span class="order-date">'+fmtDate(o.date)+'</span></div>'
    +buildSteps(o.step||1);
  if(o.on_hold){
    html+='<div class="hold-banner">⏸ There\'s a note on this order — our team will reach out to you shortly.</div>';
  }
  if(o.stage==='dispatched' && o.dispatch){
    const d=o.dispatch;
    html+='<div class="dispatch-box">'
      +(d.transport_name?'<div><b>Transport:</b> '+esc(d.transport_name)+'</div>':'')
      +(d.lr_number?'<div><b>LR Number:</b> '+esc(d.lr_number)+'</div>':'')
      +(d.ship_date?'<div><b>Shipped:</b> '+fmtDate(d.ship_date)+'</div>':'')
      +(d.box_count?'<div><b>Boxes:</b> '+esc(d.box_count)+'</div>':'')
      +'</div>';
  }
  html+='</div>';
  return html;
}

function showError(msg){
  const el=document.getElementById('err-banner');
  el.textContent=msg; el.style.display='block';
}
function clearError(){
  const el=document.getElementById('err-banner');
  el.textContent=''; el.style.display='none';
}

async function onLookupSubmit(ev){
  ev.preventDefault();
  clearError();
  const results=document.getElementById('results');
  const btn=document.getElementById('submit-btn');

  // Honeypot filled -> a bot, not a visitor. Pretend to look, find nothing.
  if(document.getElementById('f-website').value){
    results.innerHTML='<div class="card"><div class="empty-msg"><span class="ic">📭</span>No orders found for that number.</div></div>';
    return false;
  }

  const phoneRaw=document.getElementById('f-phone').value||'';
  const phone=phoneRaw.replace(/[^0-9]/g,'');
  if(phone.length<10){
    showError('Please enter a valid 10-digit mobile number.');
    return false;
  }
  const orderNo=(document.getElementById('f-order').value||'').trim();

  btn.disabled=true; btn.textContent='Checking…';
  results.innerHTML='<div class="loading"><span class="spinner"></span></div>';
  try{
    const params=new URLSearchParams({phone});
    if(orderNo) params.set('order_no',orderNo);
    const r=await fetch(API_STATUS+'?'+params);
    const j=await r.json();
    if(!j.success) throw new Error(j.message||'Something went wrong');
    const orders=(j.data&&j.data.orders)||[];
    if(!orders.length){
      const contact=BUSINESS_PHONE?(' or call us at '+esc(BUSINESS_PHONE)):'';
      results.innerHTML='<div class="card"><div class="empty-msg"><span class="ic">📭</span>We couldn\'t find an order with that number'+(orderNo?' and order number':'')+'. Please double-check and try again'+contact+'.</div></div>';
    }else{
      results.innerHTML=orders.map(buildOrderCard).join('');
    }
  }catch(e){
    results.innerHTML='';
    showError(e.message||'Something went wrong — please try again.');
  }finally{
    btn.disabled=false; btn.textContent='🔎 Check Status';
  }
  return false;
}
</script>
</body>
</html>
