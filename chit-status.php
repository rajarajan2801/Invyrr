<?php
// Public chit-scheme status lookup — no session/auth, same deliberate
// exception as shop.php/track-order.php (see shop.php's header comment).
// Talks only to api/public_chit_status.php (read-only, phone-scoped) and
// api/settings.php's public GET (business name/address/phone for the
// header — the same call track-order.php already makes).
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title>My Chit Scheme</title>
<meta name="description" content="Check how much you've paid toward your RR Crackers chit scheme and print your payment receipts.">
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
.container{max-width:680px;margin:0 auto;padding:0 18px}

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
.hp-field{position:absolute;left:-9999px;top:-9999px}
.submit-btn{width:100%;background:linear-gradient(90deg,var(--purple1),var(--purple2));color:#fff;border:none;padding:13px;border-radius:10px;font-weight:800;font-size:.92rem;cursor:pointer}
.submit-btn:disabled{opacity:.6;cursor:default}
.err-banner{background:rgba(211,47,47,.08);border:1px solid rgba(211,47,47,.3);color:var(--red);padding:9px 12px;border-radius:8px;font-size:.82rem;margin-bottom:14px;display:none}

#results{max-width:680px;margin:18px auto 0;padding:0 18px}
.chit-card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;margin-bottom:14px}
.chit-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-bottom:6px}
.chit-plan{font-weight:800;color:var(--purple1);font-size:1rem}
.chit-sub{font-size:.78rem;color:var(--ink3);margin-bottom:12px}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-blue{background:rgba(106,27,154,.1);color:var(--purple2)}
.badge-green{background:rgba(46,125,50,.12);color:var(--green-d)}
.badge-red{background:rgba(211,47,47,.1);color:var(--red)}
.badge-yellow{background:rgba(179,82,31,.1);color:var(--amber)}
.badge-gray{background:rgba(0,0,0,.06);color:var(--ink3)}

.progress-wrap{margin:10px 0 14px}
.progress-bar{height:9px;border-radius:6px;background:var(--paper2);overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--purple1),var(--purple2));border-radius:6px}
.progress-label{font-size:.76rem;color:var(--ink2);margin-top:5px;display:flex;justify-content:space-between}

table.pay-table{width:100%;border-collapse:collapse;font-size:.8rem;margin-top:6px}
table.pay-table th{text-align:left;color:var(--ink3);font-weight:700;font-size:.68rem;text-transform:uppercase;letter-spacing:.3px;padding:6px 6px;border-bottom:1px solid var(--line)}
table.pay-table td{padding:7px 6px;border-bottom:1px solid var(--line)}
table.pay-table .print-btn{background:none;border:1px solid var(--line);border-radius:6px;padding:3px 9px;font-size:.78rem;cursor:pointer;color:var(--ink2)}
table.pay-table .print-btn:hover{background:var(--paper2)}

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
      <h1>🐷 My Chit Scheme</h1>
      <p class="sub">Enter the mobile number you enrolled with to see how much you've paid, how many months are covered, and whether your bonus is on track.</p>
      <div class="err-banner" id="err-banner"></div>
      <form id="lookup-form" onsubmit="return onLookupSubmit(event)">
        <div class="field">
          <label for="f-phone">Mobile Number</label>
          <input type="tel" id="f-phone" inputmode="numeric" placeholder="10-digit mobile number" maxlength="13" required>
        </div>
        <!-- Honeypot — hidden from real visitors; a bot that fills every field will fill this one too. -->
        <div class="field hp-field"><label for="f-website">Website</label><input type="text" id="f-website" tabindex="-1" autocomplete="off"></div>
        <button type="submit" class="submit-btn" id="submit-btn">🔎 Check My Chit</button>
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
const API_CHIT_STATUS='api/public_chit_status.php';

function esc(s){ return (s==null?'':String(s)).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];}); }
function fmtMoney(n){ return '₹'+(+n||0).toLocaleString('en-IN',{maximumFractionDigits:0}); }
function fmtDate(d){
  if(!d) return '—';
  const p=String(d).split('-'); if(p.length<3) return d;
  const m=['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
  return p[2].padStart(2,'0')+'-'+(m[parseInt(p[1],10)-1]||p[1])+'-'+p[0].slice(-2);
}

let BRANDING={};
async function loadBranding(){
  try{
    const r=await fetch(API_SETTINGS); const s=await r.json();
    const d=s.data||{};
    BRANDING=d;
    const name=d.business_name||'RR Crackers';
    document.getElementById('brand-name').textContent=name;
    document.getElementById('foot-name').textContent=name;
    document.title='My Chit Scheme — '+name;
    document.getElementById('topbar-addr').innerHTML=d.business_address?('📍 '+esc(d.business_address)):'';
    document.getElementById('topbar-phone').innerHTML=d.business_phone?('📞 '+esc(d.business_phone)):'';
    const bits=[];
    if(d.business_phone) bits.push('📞 '+esc(d.business_phone));
    if(d.business_email) bits.push('✉️ '+esc(d.business_email));
    document.getElementById('foot-contact').innerHTML=bits.join(' &nbsp;·&nbsp; ');
  }catch(e){ /* branding is cosmetic only -- page still works without it */ }
}
loadBranding();

const BONUS_BADGE={on_track:['badge-yellow','Bonus On Track'],earned:['badge-green','Bonus Earned'],forfeited:['badge-red','Bonus Forfeited — a payment missed the 10th']};
const STATUS_BADGE={active:'badge-blue',completed:'badge-green',cancelled:'badge-gray'};

// Keyed by payment id (prefixed with the enrollment id, since ids reset
// per-call) rather than embedding JSON into an onclick attribute -- a
// reference number containing a quote character could otherwise break
// the HTML when the card's innerHTML is assigned.
let PAYMENTS_BY_KEY={};

function buildChitCard(e){
  PAYMENTS_BY_KEY=PAYMENTS_BY_KEY||{};
  const pct=Math.max(0,Math.min(100, (e.months_covered/e.duration_months)*100));
  const bonus=BONUS_BADGE[e.bonus_status]||['badge-gray','—'];
  const statusCls=STATUS_BADGE[e.status]||'badge-gray';
  let html='<div class="chit-card">'
    +'<div class="chit-head"><span class="chit-plan">'+esc(e.plan_label)+'</span>'
    +'<span class="badge '+statusCls+'">'+esc(e.status)+'</span></div>'
    +'<div class="chit-sub">Started '+fmtDate(e.start_date)+' · '+fmtMoney(e.monthly_amount)+'/month for '+e.duration_months+' months</div>'
    +'<div class="progress-wrap"><div class="progress-bar"><div class="progress-fill" style="width:'+pct+'%"></div></div>'
    +'<div class="progress-label"><span>'+e.months_covered+' of '+e.duration_months+' months paid</span><span>'+fmtMoney(e.total_paid)+' of '+fmtMoney(e.monthly_amount*e.duration_months)+'</span></div></div>'
    +'<div><span class="badge '+bonus[0]+'">'+bonus[1]+'</span> <span style="font-size:.78rem;color:var(--ink3)">— bonus of '+fmtMoney(e.bonus_amount)+' if every month lands by the 10th</span></div>';
  if(e.payments && e.payments.length){
    html+='<table class="pay-table"><thead><tr><th>#</th><th>Date</th><th>Amount</th><th>Mode</th><th>On Time</th><th></th></tr></thead><tbody>';
    e.payments.forEach(function(p){
      const key=e.id+'-'+p.installment_no;
      PAYMENTS_BY_KEY[key]={payment:p, enr:e};
      html+='<tr><td>'+p.installment_no+'</td><td>'+fmtDate(p.payment_date)+'</td><td>'+fmtMoney(p.amount)+'</td><td>'+esc(p.mode)+'</td>'
        +'<td><span class="badge '+(p.on_time?'badge-green':'badge-red')+'">'+(p.on_time?'On Time':'Late')+'</span></td>'
        +'<td><button type="button" class="print-btn" onclick="printChitReceipt(\''+key+'\')">🖨️ Receipt</button></td></tr>';
    });
    html+='</tbody></table>';
  }else{
    html+='<div style="font-size:.8rem;color:var(--ink3);margin-top:10px">No payments recorded yet.</div>';
  }
  html+='</div>';
  return html;
}

function printChitReceipt(key){
  const entry=PAYMENTS_BY_KEY[key];
  if(!entry) return;
  const p=entry.payment, e=entry.enr;
  // window.open() must happen synchronously inside the click handler --
  // otherwise popup blockers can swallow it. Branding is already loaded
  // (loadBranding() ran on page load), so no await is needed here at all.
  const w=window.open('','_blank','width=420,height=640');
  // Absolute, not relative -- this document was written via
  // document.write() into a window opened with an empty URL, so a plain
  // relative "assets/..." path has no reliable base to resolve against.
  const logoUrl=new URL('assets/images/branding/rr-crackers-logo.png', location.href).href;
  w.document.write(`<!DOCTYPE html><html><head><title>Chit Payment Receipt</title><style>
    body{font-family:Arial,sans-serif;padding:20px;color:#222;max-width:360px;margin:0 auto}
    .logo-row{display:flex;align-items:center;gap:10px;margin-bottom:4px}
    .logo-row img{width:44px;height:44px;border-radius:50%;object-fit:cover}
    h2{margin:0;font-size:16px} .sub{font-size:11px;color:#777;margin-bottom:10px}
    table{width:100%;border-collapse:collapse;margin:10px 0;font-size:12px}
    td{padding:4px 0;border-bottom:1px dashed #ddd} td:last-child{text-align:right;font-weight:600}
    .total{font-size:14px;font-weight:700;border-top:2px solid #333;margin-top:6px;padding-top:6px}
    @media print{button{display:none}}
  </style></head><body>
    <div class="logo-row"><img src="${logoUrl}" alt="" onerror="this.style.display='none'"><h2>${esc(BRANDING.business_name||'RR Crackers')} — Chit Payment Receipt</h2></div>
    <div class="sub">${BRANDING.business_address?esc(BRANDING.business_address)+' · ':''}${BRANDING.business_phone?esc(BRANDING.business_phone):''}</div>
    <div class="sub">Plan: ${esc(e.plan_label)} · Installment #${p.installment_no}</div>
    <table>
      <tr><td>Customer</td><td>${esc(e.customer_name)}</td></tr>
      <tr><td>Payment Date</td><td>${esc(p.payment_date)}</td></tr>
      <tr><td>Mode</td><td>${esc(p.mode)}</td></tr>
      <tr><td>Reference</td><td>${esc(p.reference_no||'—')}</td></tr>
      <tr><td class="total">Amount Paid</td><td class="total">${fmtMoney(p.amount)}</td></tr>
    </table>
    <div style="font-size:11px;color:#777;margin-top:10px">On time: ${p.on_time?'Yes':'No — bonus eligibility affected'}</div>
    <button onclick="window.print()" style="margin-top:14px;padding:8px 20px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨️ Print</button>
  </body></html>`);
  w.document.close();
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
    results.innerHTML='<div class="card"><div class="empty-msg"><span class="ic">📭</span>No chit enrollment found for that number.</div></div>';
    return false;
  }

  const phoneRaw=document.getElementById('f-phone').value||'';
  const phone=phoneRaw.replace(/[^0-9]/g,'');
  if(phone.length<10){
    showError('Please enter a valid 10-digit mobile number.');
    return false;
  }

  btn.disabled=true; btn.textContent='Checking…';
  results.innerHTML='<div class="loading"><span class="spinner"></span></div>';
  PAYMENTS_BY_KEY={};
  try{
    const r=await fetch(API_CHIT_STATUS+'?phone='+encodeURIComponent(phone));
    const j=await r.json();
    if(!j.success) throw new Error(j.message||'Something went wrong');
    const enrollments=(j.data&&j.data.enrollments)||[];
    if(!enrollments.length){
      results.innerHTML='<div class="card"><div class="empty-msg"><span class="ic">📭</span>We couldn\'t find a chit enrollment with that number. Please double-check and try again, or ask at the counter.</div></div>';
    }else{
      results.innerHTML=enrollments.map(buildChitCard).join('');
    }
  }catch(e){
    results.innerHTML='';
    showError(e.message||'Something went wrong — please try again.');
  }finally{
    btn.disabled=false; btn.textContent='🔎 Check My Chit';
  }
  return false;
}
</script>
</body>
</html>
