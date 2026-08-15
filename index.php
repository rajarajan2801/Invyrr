<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
// Invyrr v2.0 build:20260627_033315 — Built 2026-06-26 15:40
// Gate: require login session before serving any HTML
session_name('SM_SESSION');
session_start();
if (empty($_SESSION['user'])) {
    // Serve login page
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invyrr — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Outfit',sans-serif;background:#0f1117;color:#e8eaf0;min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-wrap{width:100%;max-width:400px;padding:20px}
.login-logo{text-align:center;margin-bottom:32px}
.login-logo .icon{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#4f8eff,#7c3aed);display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin:0 auto 12px}
.login-logo h1{font-size:1.5rem;font-weight:800}
.login-logo p{color:#8892b0;font-size:.85rem;margin-top:4px}
.card{background:#181c26;border:1px solid #2a3050;border-radius:16px;padding:32px}
.form-group{margin-bottom:18px}
label{display:block;font-size:.75rem;color:#8892b0;text-transform:uppercase;letter-spacing:.8px;font-weight:600;margin-bottom:7px}
input{width:100%;background:#1e2333;border:1.5px solid #2a3050;color:#e8eaf0;padding:11px 14px;border-radius:10px;font-family:inherit;font-size:.9rem;outline:none;transition:border .2s}
input:focus{border-color:#4f8eff;box-shadow:0 0 0 3px rgba(79,142,255,.1)}
.btn{width:100%;padding:12px;background:#4f8eff;color:#fff;border:none;border-radius:25px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;transition:all .2s;margin-top:6px}
.btn:hover{background:#3a7ae0;transform:translateY(-1px)}
.err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;border-radius:8px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;display:none}
.spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* SKU autocomplete dropdown */
.sku-ac-dropdown {
  position:absolute; top:100%; left:0; right:0; z-index:2000;
  background:var(--surface2); border:1px solid var(--border);
  border-radius:6px; box-shadow:0 4px 16px rgba(0,0,0,.18);
  max-height:220px; overflow-y:auto; margin-top:2px;
}
.sku-ac-item {
  padding:7px 12px; cursor:pointer; font-size:.82rem;
  border-bottom:1px solid var(--border); display:flex; gap:8px; align-items:center;
}
.sku-ac-item:last-child { border-bottom:none; }
.sku-ac-item:hover { background:var(--surface3); }
.sku-ac-item .ac-sku { font-family:var(--mono); color:var(--accent); font-weight:600; min-width:90px; }
.sku-ac-item .ac-name { color:var(--text2); }
.sku-ac-item .ac-brand { color:var(--text3); font-size:.75rem; }
.sku-ac-item .ac-sku-label { font-family:var(--mono); font-size:.82rem; color:var(--text1); letter-spacing:.01em; }
.sku-ac-item .ac-sku-label strong { color:var(--accent); }

/* Searchable select */
.ss-wrapper { position:relative; }
.ss-wrapper select { display:none !important; }
select[data-ss-init] { display:none !important; }
.ss-display {
  display:flex; align-items:center; justify-content:space-between;
  padding:6px 10px; border:1px solid var(--border); border-radius:6px;
  background:var(--surface2); cursor:pointer; min-height:34px;
  font-size:.85rem; color:var(--text1); user-select:none;
  gap:6px;
}
.ss-display:hover { border-color:var(--accent); }
.ss-display .ss-val { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ss-display .ss-arrow { color:var(--text3); font-size:.7rem; flex-shrink:0; }
.ss-dropdown {
  position:absolute; top:calc(100% + 3px); left:0; right:0; z-index:3000;
  background:var(--surface2); border:1px solid var(--border); border-radius:8px;
  box-shadow:0 6px 24px rgba(0,0,0,.2); overflow:hidden;
}
.ss-search {
  display:block; width:100%; padding:7px 10px; border:none; border-bottom:1px solid var(--border);
  background:var(--surface3); color:var(--text1); font-size:.83rem; outline:none; box-sizing:border-box;
}
.ss-search::placeholder { color:var(--text3); }
.ss-list { max-height:220px; overflow-y:auto; }
.ss-opt {
  padding:7px 12px; cursor:pointer; font-size:.83rem; color:var(--text1);
  border-bottom:1px solid rgba(0,0,0,.04); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.ss-opt:last-child { border-bottom:none; }
.ss-opt:hover, .ss-opt.ss-focused { background:var(--surface3); }
.ss-opt.ss-selected { color:var(--accent); font-weight:600; }
.ss-opt.ss-none { color:var(--text3); font-style:italic; cursor:default; }
.ss-empty { padding:10px 12px; color:var(--text3); font-size:.8rem; text-align:center; }

/* Column resize handle — scoped to products table only */
#products-table th { position: relative; overflow: visible; min-width: 40px; white-space: normal; word-break: break-word; vertical-align: bottom; text-align: center; padding-bottom: 6px; line-height: 1.2; }
#products-table th:first-child, #products-table th:last-child { text-align: left; }
#products-table th .th-resizer {
  position: absolute; right: 0; top: 0; bottom: 0; width: 6px;
  cursor: col-resize; z-index: 2;
  background: linear-gradient(to right, transparent 0px, rgba(255,255,255,.15) 2px, rgba(255,255,255,.15) 4px, transparent 4px);
}
#products-table th .th-resizer:hover,
#products-table th .th-resizer.active { background: var(--accent); opacity:.7; }

/* ── THEME SWATCHES ── */
.theme-swatch{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:var(--radius-sm);cursor:pointer;border:1.5px solid var(--border);transition:all .15s}
.theme-swatch:hover{border-color:var(--border2);background:var(--surface2)}
.theme-swatch.active{border-color:var(--accent);background:var(--surface2)}
.theme-swatch span{font-size:.85rem;color:var(--text)}
.theme-swatch-dot{width:24px;height:24px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 2px var(--surface3)}
.font-swatch-preview{width:24px;height:24px;border-radius:6px;flex-shrink:0;background:var(--surface3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:var(--text)}
#theme-quick-menu .font-swatch-preview{width:18px;height:18px;font-size:.68rem;border-radius:4px}
.theme-swatches-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px}
#theme-quick-menu{position:absolute;top:calc(100% + 8px);right:0;background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:var(--shadow);padding:8px;z-index:200;min-width:180px;display:none}
#theme-quick-menu .theme-swatch{padding:8px 10px}
#theme-quick-menu .theme-swatch-dot{width:18px;height:18px}
#theme-quick-menu .theme-swatch span{font-size:.78rem}
.theme-quick-wrap{position:relative}
</style>
</head>
<body>

<div class="login-wrap">
  <div class="login-logo">
    <div class="icon">📦</div>
    <h1>Invyrr</h1>
    <p>Inventory Management System</p>
  </div>
  <div class="card">
    <div class="err" id="login-err"></div>
    <div class="form-group"><label>Username</label><input type="text" id="lemail" placeholder="e.g. admin" autocomplete="username"></div>
    <div class="form-group"><label>Password</label><input type="password" id="lpass" placeholder="••••••••" autocomplete="current-password" onkeydown="if(event.key==='Enter')doLogin()"></div>
    <button class="btn" id="lbtn" onclick="doLogin()">Sign In</button>
  </div>
</div>
<script>
async function doLogin(){
  const email=document.getElementById('lemail').value.trim();
  const pass=document.getElementById('lpass').value;
  const err=document.getElementById('err')||document.getElementById('login-err');
  const btn=document.getElementById('lbtn');
  err.style.display='none';
  if(!email||!pass){err.textContent='Please enter username and password';err.style.display='block';return;}
  btn.innerHTML='<span class="spinner"></span> Signing in…';btn.disabled=true;
  try{
    const r=await fetch('api/auth.php?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:email,password:pass})});
    const j=await r.json();
    if(j.success){location.reload();}
    else{err.textContent=j.message||'Invalid credentials';err.style.display='block';}
  }catch(e){err.textContent='Server error. Check your connection.';err.style.display='block';}
  finally{btn.innerHTML='Sign In';btn.disabled=false;}
}
</script>

</body></html>
<?php exit; }
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($user['theme'] ?? 'midnight', ENT_QUOTES); ?>" data-font="<?php echo htmlspecialchars($user['font'] ?? 'inter', ENT_QUOTES); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Invyrr [20260627_033315]</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&family=Manrope:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&family=Lexend:wght@300;400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.383.0/dist/umd/lucide.min.js"></script>
<style>
:root{
  --bg:#0f1117;--surface:#181c26;--surface2:#1e2333;--surface3:#252b3d;
  --border:#2a3050;--border2:#333d5c;
  --accent:#4f8eff;--accent2:#7c3aed;
  --green:#22c55e;--red:#ef4444;--orange:#f97316;--yellow:#eab308;--purple:#a855f7;
  --text:#e8eaf0;--text2:#8892b0;--text3:#4a5578;
  --mono:'JetBrains Mono',monospace;--sans:'Inter',sans-serif;
  --radius:12px;--radius-sm:8px;--shadow:0 4px 24px rgba(0,0,0,.4);
  --sidebar-w:230px;
}
/* ── THEME COLOR SCHEMES ── */
/* "midnight" is the default, defined in :root above. The rest override accent
   colors and give the dark surfaces a subtle matching tint. Status colors
   (green/red/orange/yellow/purple) and text colors stay constant across
   themes since they carry semantic meaning (success/error/etc). */
html[data-theme="emerald"]{
  --bg:#0d1512;--surface:#16201a;--surface2:#1c2a22;--surface3:#24352b;
  --border:#2a3f33;--border2:#34503f;
  --accent:#22c55e;--accent2:#10b981;
}
html[data-theme="crimson"]{
  --bg:#160f10;--surface:#221715;--surface2:#2b1e1c;--surface3:#362723;
  --border:#3d2a26;--border2:#4d352f;
  --accent:#f43f5e;--accent2:#fb7185;
}
html[data-theme="amber"]{
  --bg:#15110a;--surface:#221b12;--surface2:#2c2317;--surface3:#382d1e;
  --border:#3d3120;--border2:#4d3f2a;
  --accent:#f59e0b;--accent2:#fb923c;
}
html[data-theme="violet"]{
  --bg:#120f1a;--surface:#1c1730;--surface2:#251e3d;--surface3:#2f264c;
  --border:#332a52;--border2:#413563;
  --accent:#a855f7;--accent2:#d946ef;
}
html[data-theme="teal"]{
  --bg:#0a1416;--surface:#112023;--surface2:#16292d;--surface3:#1d343a;
  --border:#1f3a40;--border2:#294a51;
  --accent:#06b6d4;--accent2:#22d3ee;
}
/* ── FONT SCHEMES ── */
/* "inter" is the default, defined in :root above. */
html[data-font="outfit"]{ --sans:'Outfit',sans-serif; --mono:'DM Mono',monospace; }
html[data-font="jakarta"]{ --sans:'Plus Jakarta Sans',sans-serif; --mono:'IBM Plex Mono',monospace; }
html[data-font="manrope"]{ --sans:'Manrope',sans-serif; --mono:'Space Mono',monospace; }
html[data-font="lexend"]{ --sans:'Lexend',sans-serif; --mono:'Roboto Mono',monospace; }
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:15px}
body{font-family:var(--sans);background:var(--bg);color:var(--text);min-height:100vh;display:flex;overflow-x:auto}

/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:width .25s ease,transform .3s;font-family:var(--sans)}
.sidebar-logo{padding:20px 18px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;overflow:hidden;min-height:69px}
.logo-mark{display:flex;align-items:center;gap:10px;flex:1;min-width:0}
.logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
.logo-text{font-size:.95rem;font-weight:700;letter-spacing:-.3px;white-space:nowrap;overflow:hidden;transition:opacity .2s,width .2s}
.logo-sub{font-size:.65rem;color:var(--text3);font-family:var(--sans);margin-top:2px;white-space:nowrap;overflow:hidden;transition:opacity .2s}
.sidebar-collapse-btn{background:none;border:none;color:var(--text3);cursor:pointer;padding:4px 6px;border-radius:6px;font-size:.9rem;flex-shrink:0;transition:color .15s,transform .25s;line-height:1}
.sidebar-collapse-btn:hover{color:var(--text);background:var(--surface2)}
.nav{flex:1;padding:10px 8px;display:flex;flex-direction:column;gap:1px;overflow-y:auto;overflow-x:hidden;scrollbar-width:thin;scrollbar-color:var(--border2) transparent}
.nav::-webkit-scrollbar{width:4px}
.nav::-webkit-scrollbar-track{background:transparent}
.nav::-webkit-scrollbar-thumb{background:var(--border2);border-radius:4px}
.nav::-webkit-scrollbar-thumb:hover{background:var(--text3)}
.nav-section-label{font-size:.62rem;color:var(--text3);text-transform:uppercase;letter-spacing:1.5px;padding:10px 10px 10px;font-weight:600;white-space:nowrap;overflow:hidden;transition:opacity .2s,height .2s}
.nav-item{display:flex;align-items:center;gap:9px;padding:8px 10px;border-radius:var(--radius-sm);color:var(--text2);font-size:.84rem;font-weight:500;cursor:pointer;transition:all .15s;border:none;background:none;width:100%;text-align:left;white-space:nowrap;overflow:hidden;position:relative}
.nav-item:hover{background:var(--surface2);color:var(--text)}
.nav-item.active{background:rgba(79,142,255,.12);color:var(--accent)}
.nav-icon{width:18px;height:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.nav-icon svg{width:16px;height:16px;stroke-width:1.8}
.nav-item-label{transition:opacity .2s,width .2s;overflow:hidden}
.nav-badge{margin-left:auto;background:var(--red);color:#fff;border-radius:20px;font-size:.62rem;padding:1px 6px;font-weight:700;flex-shrink:0;transition:opacity .2s}
.nav-user{padding:12px 14px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px;overflow:hidden;min-height:57px}
.nav-user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;flex-shrink:0}
.nav-user-info{flex:1;min-width:0;overflow:hidden;transition:opacity .2s,width .2s}
.nav-user-name{font-size:.82rem;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.nav-user-role{font-size:.68rem;color:var(--text3);text-transform:capitalize}
.nav-logout{background:none;border:none;color:var(--text3);cursor:pointer;padding:4px;border-radius:6px;font-size:.85rem;flex-shrink:0;transition:opacity .2s}
.nav-logout:hover{color:var(--red)}

/* collapsed state */
.sidebar.collapsed{width:54px}
.sidebar.collapsed .logo-text,.sidebar.collapsed .logo-sub{opacity:0;width:0;pointer-events:none}
.sidebar.collapsed .sidebar-collapse-btn{transform:rotate(180deg)}
.sidebar.collapsed .nav-section-label{opacity:0;height:0;padding:0;pointer-events:none}
.sidebar.collapsed .nav-item-label{opacity:0;width:0;pointer-events:none}
.sidebar.collapsed .nav-badge{opacity:0;width:0;pointer-events:none}
.sidebar.collapsed .nav-user-info,.sidebar.collapsed .nav-logout{opacity:0;width:0;pointer-events:none}
.sidebar.collapsed .nav-item{justify-content:center;padding:9px 0}
.sidebar.collapsed .nav-user{justify-content:center;padding:12px 0}
/* tooltip on hover when collapsed */
.sidebar.collapsed .nav-item[title]:hover::after{content:attr(title);position:absolute;left:58px;background:var(--surface3);color:var(--text);padding:5px 10px;border-radius:6px;font-size:.78rem;white-space:nowrap;border:1px solid var(--border2);z-index:200;pointer-events:none;box-shadow:var(--shadow)}

/* ── HAMBURGER ── */
.hamburger{display:none;background:none;border:none;color:var(--text);cursor:pointer;padding:6px;font-size:1.3rem}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;min-height:100vh;display:flex;flex-direction:column;transition:margin-left .25s ease;min-width:0}
.sidebar.collapsed~.main,.sidebar.collapsed+.sidebar-overlay+.main{margin-left:54px}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:12px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px;position:fixed;top:0;left:var(--sidebar-w);right:0;z-index:50;min-width:0;transition:left .25s ease}
.topbar-left{display:flex;align-items:center;gap:12px;flex-shrink:0}
.topbar-title{font-size:1.05rem;font-weight:700}
.topbar-actions{display:flex;gap:8px;align-items:center;flex-wrap:nowrap;flex-shrink:0;overflow:hidden}
.sidebar.collapsed~.main .topbar,.sidebar.collapsed+.sidebar-overlay+.main .topbar{left:54px}
.sidebar.collapsed~.main #settings-subnav,.sidebar.collapsed+.sidebar-overlay+.main #settings-subnav{left:54px}
.sidebar.collapsed~.main #exp-entity-tabs-bar,.sidebar.collapsed+.sidebar-overlay+.main #exp-entity-tabs-bar{left:54px}
.content{padding:24px;padding-top:81px;flex:1}
/* Extra padding when expense tabs bar is showing */
.exp-tabs-visible .content,.settings-subnav-visible .content{padding-top:128px}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:25px;border:none;font-family:var(--sans);font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;letter-spacing:.2px;white-space:nowrap;text-decoration:none}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 2px 10px rgba(79,142,255,.3)}
.btn-primary:hover{background:#3a7ae0;transform:translateY(-1px)}
.btn-success{background:var(--green);color:#fff}
.btn-success:hover{background:#16a34a}
.btn-danger{background:var(--red);color:#fff}
.btn-danger:hover{background:#dc2626}
.btn-warning{background:var(--orange);color:#fff}
.btn-outline{background:transparent;border:1.5px solid var(--border2);color:var(--text2)}
.btn-outline:hover{border-color:var(--accent);color:var(--accent)}
.btn-ghost{background:var(--surface2);color:var(--text2)}
.btn-ghost:hover{background:var(--surface3);color:var(--text)}
.btn-purple{background:var(--purple);color:#fff}
.btn-sm{padding:6px 12px;font-size:.76rem}
.btn-xs{padding:3px 9px;font-size:.71rem;border-radius:14px}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}

/* ── STATS ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent-color,var(--accent))}
.stat-icon{font-size:1.4rem;margin-bottom:8px;display:block}
.stat-num{font-size:1.7rem;font-weight:800;font-family:var(--mono);display:block;line-height:1}
.stat-label{font-size:.72rem;color:var(--text2);margin-top:4px;text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.stat-sub{font-size:.72rem;color:var(--text3);margin-top:5px}

/* ── CARD ── */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:18px}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.card-title{font-size:.9rem;font-weight:700}
.card-body{padding:18px}

/* ── TABLE ── */
.tbl-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:var(--border2) transparent}
.tbl-wrap table thead th{position:sticky;top:0;z-index:10;background:var(--surface);box-shadow:0 1px 0 var(--border2)}
#oor-table thead tr:first-child th{position:sticky;top:0;z-index:11;background:var(--surface);box-shadow:0 1px 0 var(--border2)}
#oor-table thead tr:last-child th{position:sticky;top:27px;z-index:11;background:var(--surface);box-shadow:0 1px 0 var(--border2)}
.combo-drag-row{transition:opacity .15s}
.combo-drag-row[draggable=true]:active{cursor:grabbing}
.tbl-wrap::-webkit-scrollbar{height:6px}
.tbl-wrap::-webkit-scrollbar-track{background:transparent}
.tbl-wrap::-webkit-scrollbar-thumb{background:var(--border2);border-radius:6px}
.tbl-wrap::-webkit-scrollbar-thumb:hover{background:var(--text3)}
table{width:100%;border-collapse:collapse;font-size:.82rem}
th{padding:9px 13px;text-align:left;font-size:.67rem;text-transform:uppercase;letter-spacing:1px;color:var(--text3);border-bottom:1px solid var(--border);font-weight:600;white-space:nowrap}
td{padding:10px 13px;border-bottom:1px solid rgba(42,48,80,.4);vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:rgba(79,142,255,.02)}
.checkbox-col{width:36px}
.checkbox-col input[type=checkbox]{width:15px;height:15px;accent-color:var(--accent);cursor:pointer}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:20px;font-size:.7rem;font-weight:700;letter-spacing:.3px}
.badge-green {background:rgba(34,197,94,.12); color:var(--green)}
.badge-red   {background:rgba(239,68,68,.12);  color:var(--red)}
.badge-yellow{background:rgba(234,179,8,.12);  color:var(--yellow)}
.badge-blue  {background:rgba(79,142,255,.12); color:var(--accent)}
.badge-orange{background:rgba(249,115,22,.12); color:var(--orange)}
.badge-purple{background:rgba(168,85,247,.12); color:var(--purple)}
.badge-gray  {background:rgba(255,255,255,.06);color:var(--text2)}

/* ── FORMS ── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.form-grid-4{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px}
.form-group{display:flex;flex-direction:column;gap:5px}
.form-group.full{grid-column:1/-1}
.form-label{font-size:.72rem;color:var(--text2);text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.form-control{background:var(--surface2);border:1.5px solid var(--border);color:var(--text);padding:8px 12px;border-radius:var(--radius-sm);font-family:var(--sans);font-size:.88rem;outline:none;transition:border .2s;width:100%}
.form-control:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(79,142,255,.1)}
select.form-control{cursor:pointer}
textarea.form-control{resize:vertical;min-height:70px}

/* ── MODAL ── */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:1000;display:flex;align-items:center;justify-content:center;padding:16px;backdrop-filter:blur(4px);opacity:0;pointer-events:none;transition:opacity .2s}
.modal-backdrop.open{opacity:1;pointer-events:all}
.modal{background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);width:100%;max-width:600px;max-height:92vh;overflow-y:auto;transform:translateY(20px);transition:transform .25s;box-shadow:var(--shadow)}
.modal-backdrop.open .modal{transform:translateY(0)}
.modal-lg{max-width:960px}
.modal-xl{max-width:1000px}
.modal-header{padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--surface);z-index:1}
.modal-title{font-weight:700;font-size:.95rem}
.modal-close{background:none;border:none;color:var(--text2);font-size:1.2rem;cursor:pointer;padding:3px 7px;border-radius:6px}
.modal-close:hover{background:var(--surface2)}
.modal-body{padding:20px 22px}
.modal-footer{padding:12px 22px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px;position:sticky;bottom:0;background:var(--surface)}

/* ── FILTER BAR ── */
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;align-items:center}
.search-input{flex:1;min-width:160px;background:var(--surface2);border:1.5px solid var(--border);color:var(--text);padding:7px 13px 7px 34px;border-radius:25px;font-family:var(--sans);font-size:.82rem;outline:none;transition:border .2s;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='13' height='13' viewBox='0 0 24 24' fill='none' stroke='%234a5578' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:11px center}
.search-input:focus{border-color:var(--accent)}
.filter-select{background:var(--surface2);border:1.5px solid var(--border);color:var(--text2);padding:7px 13px;border-radius:25px;font-family:var(--sans);font-size:.79rem;outline:none;cursor:pointer}
.date-input{background:var(--surface2);border:1.5px solid var(--border);color:var(--text2);padding:7px 12px;border-radius:25px;font-family:var(--sans);font-size:.79rem;outline:none;cursor:pointer}
.date-input:focus{border-color:var(--accent);color:var(--text)}

/* ── PAGES ── */
.page{display:none}.page.active{display:block}

/* ── LOCATION SELECTOR ── */
.loc-selector{display:flex;align-items:center;gap:7px;background:var(--surface2);border:1.5px solid var(--border);border-radius:25px;padding:5px 13px;font-size:.79rem;color:var(--text2)}
.loc-selector select{background:none;border:none;color:var(--text);font-family:var(--sans);font-size:.79rem;font-weight:600;outline:none;cursor:pointer;max-width:150px}

/* ── CHARTS ── */
.chart-wrap{position:relative;height:220px;padding:10px 0}

/* ── TOAST ── */
.toast-container{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:7px}
.toast{background:var(--surface2);border:1px solid var(--border2);border-radius:var(--radius-sm);padding:11px 16px;min-width:250px;max-width:380px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:9px;font-size:.85rem;animation:toastIn .3s ease;border-left:3px solid var(--accent)}
.toast.success{border-left-color:var(--green)}.toast.error{border-left-color:var(--red)}.toast.warn{border-left-color:var(--yellow)}
@keyframes toastIn{from{transform:translateX(110%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes toastOut{to{transform:translateX(120%);opacity:0}}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:50px 20px;color:var(--text3)}
.empty-state .empty-icon{font-size:2.6rem;margin-bottom:10px;display:block;opacity:.4}
.empty-state p{font-size:.85rem;margin-top:5px}

/* ── SPINNER ── */
.spinner{display:inline-block;width:15px;height:15px;border:2px solid rgba(255,255,255,.25);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── BULK ACTION BAR ── */
.bulk-bar{display:none;background:rgba(79,142,255,.1);border:1px solid rgba(79,142,255,.3);border-radius:var(--radius-sm);padding:10px 16px;align-items:center;gap:12px;margin-bottom:12px;font-size:.84rem}
.bulk-bar.visible{display:flex}

/* ── INVOICE BUILDER ── */
.inv-items-table{width:100%;border-collapse:collapse;font-size:.82rem}
.inv-items-table th{padding:8px 10px;background:var(--surface2);font-size:.68rem;text-transform:uppercase;letter-spacing:.8px;color:var(--text3)}
.inv-items-table td{padding:6px 8px;border-bottom:1px solid var(--border)}
.inv-items-table input,
.inv-items-table select{background:var(--surface3);border:1px solid var(--border);color:var(--text);padding:5px 8px;border-radius:6px;font-size:.82rem;width:100%;font-family:var(--sans)}

/* ── PRODUCT IMAGE ── */
.product-img{width:36px;height:36px;border-radius:8px;object-fit:cover;background:var(--surface2)}
.product-img-placeholder{width:36px;height:36px;border-radius:8px;background:var(--surface2);display:inline-flex;align-items:center;justify-content:center;font-size:.9rem;color:var(--text3)}

/* ── PO STATUS ── */
.status-draft  {color:var(--text2)}
.status-sent   {color:var(--accent)}
.status-partial{color:var(--yellow)}
.status-received{color:var(--green)}
.status-cancelled{color:var(--red);text-decoration:line-through}

/* ── KEYBOARD SHORTCUT HINT ── */
.kbd{background:var(--surface2);border:1px solid var(--border);border-radius:4px;padding:1px 6px;font-family:var(--mono);font-size:.7rem;color:var(--text3)}

/* ── SCANNER ── */
#scanner-container{position:relative;width:100%;max-width:400px;margin:0 auto;border-radius:var(--radius);overflow:hidden;background:#000}
#scanner-container video{width:100%;display:block}
#scanner-container canvas{display:none}

.ie-cell{cursor:pointer;position:relative;white-space:nowrap}
.ie-cell:hover{background:rgba(79,142,255,.06)}
.ie-cell .price-hint{opacity:0;font-size:.65rem;color:var(--accent);margin-left:3px;transition:opacity .15s;vertical-align:middle}
.ie-cell:hover .price-hint{opacity:1}
.ie-input{font-family:var(--mono);font-size:.82rem;background:var(--surface2);border:1.5px solid var(--accent);color:var(--text);border-radius:5px;padding:3px 7px;min-width:70px;max-width:160px;outline:none;box-shadow:0 0 0 3px rgba(79,142,255,.12)}
.ie-select{font-family:var(--sans);font-size:.82rem;background:var(--surface2);border:1.5px solid var(--accent);color:var(--text);border-radius:5px;padding:3px 7px;min-width:100px;max-width:180px;outline:none;box-shadow:0 0 0 3px rgba(79,142,255,.12);cursor:pointer}
/* keep old price-cell working */
.price-cell{cursor:pointer}.price-cell:hover{background:rgba(79,142,255,.06)}.price-cell .price-hint{opacity:0;font-size:.68rem;color:var(--accent);margin-left:4px;transition:opacity .15s}.price-cell:hover .price-hint{opacity:1}.price-cell input.price-input{font-family:var(--mono);font-size:.83rem;background:var(--surface2);border:1.5px solid var(--accent);color:var(--text);border-radius:5px;padding:3px 7px;width:90px;outline:none;box-shadow:0 0 0 3px rgba(79,142,255,.15)}
.settings-tab{background:none;border:none;border-bottom:2px solid transparent;color:var(--text2);font-family:var(--sans);font-size:.88rem;font-weight:500;padding:10px 18px;cursor:pointer;transition:all .15s;margin-bottom:-1px}
.settings-tab:hover{color:var(--text)}
.settings-tab.active{color:var(--accent);border-bottom-color:var(--accent)}
.settings-tab-pane{}
.text-green{color:var(--green)}.text-red{color:var(--red)}.text-accent{color:var(--accent)}.text-muted{color:var(--text2)}.text-orange{color:var(--orange)}
.mono{font-family:var(--mono)}.profit-cell{font-family:var(--mono);font-weight:600}
hr{border:none;border-top:1px solid var(--border);margin:14px 0}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}
.sticky-form-col{display:grid;grid-template-columns:360px 1fr;gap:18px;align-items:start}

/* Compact procurement dashboard table */
#oor-table th{padding:5px 7px;font-size:.68rem;white-space:nowrap}
#oor-table td{padding:4px 7px;font-size:.78rem}
#oor-table td:first-child,#oor-table th:first-child{padding-left:12px}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .stats-row{grid-template-columns:1fr 1fr}
  .sticky-form-col{grid-template-columns:1fr}
  .sticky-form-col > .card{position:static !important}
  .two-col,.three-col,.report-grid{grid-template-columns:1fr}
  .form-grid-4{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
  :root{--sidebar-w:0px}
  .sidebar{transform:translateX(-230px);width:230px}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.open{display:block}
  .main{margin-left:0}
  .hamburger{display:block}
  .topbar{padding:10px 14px}
  .content{padding:14px;padding-top:71px}
  .exp-tabs-visible .content,.settings-subnav-visible .content{padding-top:118px}
  .stats-row{grid-template-columns:1fr 1fr}
  .form-grid,.form-grid-3,.form-grid-4{grid-template-columns:1fr}
  .topbar-actions .btn span:not(.spinner){display:none}
  .loc-selector select{max-width:100px}
  /* Prevent iOS auto-zoom on input focus (requires >=16px font) */
  .form-control,.filter-select,.date-input,.search-input{font-size:16px}
  /* Larger touch targets for row action buttons */
  .btn-xs{padding:7px 10px;min-width:36px;min-height:36px}
}
</style>
</head>
<body>


<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">
      <div class="logo-icon"><i data-lucide="package" style="width:20px;height:20px;stroke:#fff"></i></div>
      <div>
        <div class="logo-text">Invyrr</div>
        <div class="logo-sub">v2.0 · Multi-location</div>
      </div>
    </div>
    <button class="sidebar-collapse-btn" onclick="toggleCollapse()" title="Collapse sidebar">‹</button>
  </div>
  <nav class="nav">
    <div class="nav-section-label">Overview</div>
    <button class="nav-item active" data-page="dashboard" title="Dashboard"><span class="nav-icon"><i data-lucide="layout-dashboard"></i></span><span class="nav-item-label"> Dashboard</span></button>

    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <div class="nav-section-label">Inventory</div>
    <button class="nav-item" data-page="products" title="Products"><span class="nav-icon"><i data-lucide="package"></i></span><span class="nav-item-label"> Products</span></button>
    <button class="nav-item" data-page="categories" title="Categories"><span class="nav-icon"><i data-lucide="tag"></i></span><span class="nav-item-label"> Categories</span></button>
    <button class="nav-item" data-page="combos" title="Combo Builder"><span class="nav-icon"><i data-lucide="gift"></i></span><span class="nav-item-label"> Combos</span></button>
    <?php endif; ?>

    <div class="nav-section-label">Parties</div>
    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <button class="nav-item" data-page="vendors" title="Vendors"><span class="nav-icon"><i data-lucide="factory"></i></span><span class="nav-item-label"> Vendors</span></button>
    <?php endif; ?>
    <button class="nav-item" data-page="customers" title="Customers"><span class="nav-icon"><i data-lucide="users"></i></span><span class="nav-item-label"> Customers</span></button>
    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <button class="nav-item" data-page="website-orders" title="Customer Orders"><span class="nav-icon"><i data-lucide="shopping-bag"></i></span><span class="nav-item-label"> Customer Orders</span></button>
    <?php endif; ?>

    <div class="nav-section-label">Sales</div>
    <button class="nav-item" data-page="invoices" title="Estimates / Sales"><span class="nav-icon"><i data-lucide="receipt"></i></span><span class="nav-item-label"> Estimates / Sales</span></button>
    <button class="nav-item" data-page="picking" title="Fulfillment"><span class="nav-icon"><i data-lucide="check-square"></i></span><span class="nav-item-label"> Fulfillment</span></button>

    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <div class="nav-section-label">Purchases</div>
    <button class="nav-item" data-page="stock-in" title="Stock In"><span class="nav-icon"><i data-lucide="package-plus"></i></span><span class="nav-item-label"> Stock In</span></button>
    <button class="nav-item" data-page="purchase-orders" title="Purchase Orders"><span class="nav-icon"><i data-lucide="clipboard-list"></i></span><span class="nav-item-label"> Purchase Orders</span></button>
    <button class="nav-item" data-page="transfers" title="Transfers"><span class="nav-icon"><i data-lucide="arrow-left-right"></i></span><span class="nav-item-label"> Transfers</span></button>
    <button class="nav-item" data-page="adjustments" title="Adjustments"><span class="nav-icon"><i data-lucide="sliders-horizontal"></i></span><span class="nav-item-label"> Adjustments</span></button>
    <?php endif; ?>

    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <div class="nav-section-label">Accounting</div>
    <button class="nav-item" data-page="vendor-payments" title="Vendor Payments"><span class="nav-icon"><i data-lucide="indian-rupee"></i></span><span class="nav-item-label"> Vendor Payments</span></button>
    <button class="nav-item" data-page="expenses" title="Expenses"><span class="nav-icon"><i data-lucide="wallet"></i></span><span class="nav-item-label"> Expenses</span></button>
    <button class="nav-item" data-page="payees" title="Payees"><span class="nav-icon"><i data-lucide="credit-card"></i></span><span class="nav-item-label"> Payees</span></button>
    <?php endif; ?>

    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <div class="nav-section-label">Reports</div>
    <button class="nav-item" data-page="reports" title="Reports"><span class="nav-icon"><i data-lucide="bar-chart-2"></i></span><span class="nav-item-label"> Reports</span><span class="nav-badge" id="alert-badge" style="display:none">0</span></button>
    <button class="nav-item" data-page="on-order-report" title="Procurement Dashboard"><span class="nav-icon"><i data-lucide="shopping-cart"></i></span><span class="nav-item-label"> Procurement</span></button>
    <?php endif; ?>

    <?php if(($user['role'] ?? '')!=='Picker'): ?>
    <div class="nav-section-label">System</div>
    <button class="nav-item" data-page="settings" title="Settings"><span class="nav-icon"><i data-lucide="settings"></i></span><span class="nav-item-label"> Settings</span></button>
    <?php if($user['role']==='admin'): ?>
    <button class="nav-item" data-page="audit" title="Audit Log"><span class="nav-icon"><i data-lucide="scroll-text"></i></span><span class="nav-item-label"> Audit Log</span></button>
    <?php endif; ?>
    <button class="nav-item" data-page="import" title="Import"><span class="nav-icon"><i data-lucide="file-up"></i></span><span class="nav-item-label"> Import</span></button>
    <?php endif; ?>
  </nav>
  <div class="nav-user">
    <div class="nav-user-avatar"><?= strtoupper(substr($user['name'],0,1)) ?></div>
    <div class="nav-user-info">
      <div class="nav-user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="nav-user-role"><?= $user['role'] ?></div>
    </div>
    <button class="nav-logout" onclick="doLogout()" title="Sign out"><i data-lucide="log-out" style="width:15px;height:15px"></i></button>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- ══ MAIN ══ -->
<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="toggleSidebar()">☰</button>
      <div class="topbar-title" id="page-title">Dashboard</div>
    </div>
    <div class="topbar-actions">
      <div class="theme-quick-wrap">
        <button class="btn btn-ghost btn-sm" id="theme-quick-btn" onclick="toggleThemeMenu()" title="Theme">🎨</button>
        <div id="theme-quick-menu"></div>
      </div>
      <div class="loc-selector">
        <span>🏪</span>
        <select id="global-location" onchange="onLocationChange()">
          <option value="">All Locations</option>
        </select>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="showPage('invoices');openInvoiceModal()" title="New Estimate (I)">🧾 <span>Estimate</span></button>
      <button class="btn btn-outline btn-sm" onclick="exportExcel('all')" title="Export (E)">📊 <span>Export</span></button>
      <button class="btn btn-primary btn-sm" onclick="openProductModal()" title="Quick Add (N)">+ <span>Product</span></button>
    </div>
  </div>
  <!-- Settings sub-nav — shown only when settings page is active -->
  <div id="settings-subnav" style="display:none;background:var(--surface);border-bottom:1px solid var(--border);padding:0 24px;position:fixed;top:57px;left:var(--sidebar-w);right:0;z-index:45;transition:left .25s ease">
    <div style="display:flex;gap:4px;">
      <button class="settings-tab active" data-tab="general"   onclick="switchSettingsTab('general')"  >⚙️ General</button>
      <button class="settings-tab"        data-tab="locations" onclick="switchSettingsTab('locations')">🏪 Locations</button>
      <?php if($user['role']==='admin'): ?>
      <button class="settings-tab"        data-tab="users"     onclick="switchSettingsTab('users')"    >👥 Users</button>
      <?php endif; ?>
      <button class="settings-tab"        data-tab="appearance" onclick="switchSettingsTab('appearance')">🎨 Appearance</button>
      <button class="settings-tab"        data-tab="backup"    onclick="switchSettingsTab('backup')"   >☁️ Backup</button>
    </div>
  </div>
  <!-- Expense entity tabs — shown only when expenses page is active -->
  <div id="exp-entity-tabs-bar" style="display:none;background:var(--surface);border-bottom:1px solid var(--border);padding:8px 24px;position:fixed;top:57px;left:var(--sidebar-w);right:0;z-index:45;transition:left .25s ease">
    <div style="display:flex;gap:8px;flex-wrap:wrap" id="exp-entity-tabs"></div>
  </div>

  <div class="content">

<!-- ══════════ DASHBOARD ══════════ -->
<div class="page active" id="page-dashboard">
  <div class="stats-row" id="dash-stats">
    <div class="stat-card"><span class="stat-icon">📦</span><span class="stat-num">—</span><span class="stat-label">Loading…</span></div>
    <div class="stat-card"><span class="stat-icon">💰</span><span class="stat-num">—</span><span class="stat-label">Loading…</span></div>
    <div class="stat-card"><span class="stat-icon">📈</span><span class="stat-num">—</span><span class="stat-label">Loading…</span></div>
    <div class="stat-card"><span class="stat-icon">🔔</span><span class="stat-num">—</span><span class="stat-label">Loading…</span></div>
  </div>
  <div class="two-col">
    <?php if(in_array($user['role'],['admin','partner'])): ?>
    <div class="card"><div class="card-header"><span class="card-title">💳 Payments by Payee</span><span style="font-size:.72rem;color:var(--text3)" id="dash-payee-period">YTD</span></div><div class="card-body" style="padding:0"><div id="dash-payee-list" style="max-height:260px;overflow-y:auto"></div></div></div>
    <?php endif; ?>
    <div class="card"><div class="card-header"><span class="card-title">🏷️ Stock Value by Category</span></div><div class="card-body"><div class="chart-wrap"><canvas id="chart-category"></canvas></div></div></div>
  </div>
  <div class="two-col">
    <div class="card">
      <div class="card-header"><span class="card-title">📉 Low Stock Alerts</span><button class="btn btn-ghost btn-sm" onclick="showPage('alerts')">View All</button></div>
      <div class="card-body" style="padding:0 18px" id="dash-alerts"></div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">🕐 Recent Transactions</span></div>
      <div class="card-body" style="padding:0 18px" id="dash-recent"></div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">🏆 Top Products by Profit Margin</span></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Product</th><th>Brand</th><th>Vendor</th><th>Cost ₹</th><th>Sell ₹</th><th>Margin</th><th>Stock</th><th>Value</th></tr></thead>
      <tbody id="dash-top-body"><tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text3)">Loading…</td></tr></tbody>
    </table></div>
  </div>
</div>

<!-- ══════════ PRODUCTS ══════════ -->
<div class="page" id="page-products">
  <div class="card">
    <div class="card-header">
      <span class="card-title" id="products-page-title">📦 Products</span>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <div style="position:relative">
          <button class="btn btn-ghost btn-sm" onclick="toggleColChooser()" id="col-chooser-btn">⚙️ Columns</button>
          <div id="col-chooser" style="display:none;position:absolute;right:0;top:calc(100% + 6px);background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);padding:12px 14px;min-width:190px;box-shadow:var(--shadow);z-index:200">
            <div style="font-size:.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:.8px;font-weight:600;margin-bottom:8px">Show / Hide Columns</div>
            <div id="col-toggle-list" style="display:flex;flex-direction:column;gap:6px"></div>
            <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);display:flex;gap:6px">
              <button class="btn btn-ghost btn-xs" onclick="resetColPrefs()">Reset</button>
              <button class="btn btn-primary btn-xs" style="margin-left:auto" onclick="toggleColChooser()">Done</button>
            </div>
          </div>
        </div>
        <button class="btn btn-ghost btn-sm" onclick="openBarcodeModal()">📷 Scan</button>
        <button class="btn btn-ghost btn-sm" onclick="findDuplicates()">🔍 Duplicates</button>
        <button class="btn btn-outline btn-sm" onclick="showBulkBar()">☑️ Bulk</button>
        <button class="btn btn-outline btn-sm" onclick="recalcAllProductCosts()" title="Recalculate Cost Price from List Price using each vendor's pricing formula">🧮 Recalc Costs</button>
        <button class="btn btn-outline btn-sm" onclick="recalcAllLandingCosts()" title="Recalculate Landing Cost = (Cost × Case Content + Case Margin) ÷ Case Content">🧮 Recalc Landing ₹</button>
        <button class="btn btn-primary btn-sm" onclick="openProductModal()">+ Add Product</button>
      </div>
    </div>
    <div class="card-body" style="padding:14px 18px 0">
      <div class="bulk-bar" id="bulk-bar">
        <span id="bulk-count">0 selected</span>
        <button class="btn btn-ghost btn-sm" onclick="bulkAction('category')">Change Category</button>
        <button class="btn btn-ghost btn-sm" onclick="bulkAction('brand')">Change Brand</button>
        <button class="btn btn-ghost btn-sm" onclick="bulkAction('vendor')">Change Vendor</button>
        <?php if(in_array($user['role'] ?? '', ['admin','partner'])): ?><button class="btn btn-danger btn-sm" onclick="bulkAction('delete')">🗑️ Delete</button><?php endif; ?>
        <button class="btn btn-ghost btn-sm" style="margin-left:auto" onclick="clearBulk()">✕ Cancel</button>
      </div>
      <div class="filter-bar">
        <input type="text" class="search-input" id="product-search" placeholder="Search name, SKU, brand…" oninput="loadProducts()">
        <select class="filter-select" id="product-brand-filter" onchange="loadProducts()"><option value="">All Brands</option></select>
        <select class="filter-select" id="product-cat-filter" onchange="loadProducts()"><option value="">All Categories</option></select>
        <select class="filter-select" id="product-vendor-filter" onchange="loadProducts()"><option value="">All Vendors</option></select>
        <select class="filter-select" id="product-stock-filter" onchange="loadProducts()">
          <option value="">All Stock</option><option value="low">Low Stock</option><option value="out">Out of Stock</option><option value="ok">In Stock</option><option value="on_order">On Order</option><option value="no_sku">Missing SKU</option>
        </select>
        <!-- Active/Inactive toggle -->
        <div style="display:inline-flex;border:1px solid var(--border2);border-radius:6px;overflow:hidden;font-size:.78rem">
          <button id="paf-all"      onclick="setPAFilter('')"  class="paf-btn" style="padding:4px 10px;background:var(--surface2);color:var(--text2);border:none;cursor:pointer">All</button>
          <button id="paf-active"   onclick="setPAFilter('1')" class="paf-btn" style="padding:4px 10px;background:var(--accent);color:#fff;border:none;border-left:1px solid var(--border2);cursor:pointer">Active</button>
          <button id="paf-inactive" onclick="setPAFilter('0')" class="paf-btn" style="padding:4px 10px;background:var(--surface2);color:var(--text2);border:none;border-left:1px solid var(--border2);cursor:pointer">Inactive</button>
        </div>
        <!-- Combo toggle -->
        <div style="display:inline-flex;border:1px solid var(--border2);border-radius:6px;overflow:hidden;font-size:.78rem">
          <button id="cbf-all"     onclick="setComboFilter('')"  class="cbf-btn" style="padding:4px 10px;background:var(--accent);color:#fff;border:none;cursor:pointer">All</button>
          <button id="cbf-combo"   onclick="setComboFilter('1')" class="cbf-btn" style="padding:4px 10px;background:var(--surface2);color:var(--text2);border:none;border-left:1px solid var(--border2);cursor:pointer">Combo</button>
          <button id="cbf-regular" onclick="setComboFilter('0')" class="cbf-btn" style="padding:4px 10px;background:var(--surface2);color:var(--text2);border:none;border-left:1px solid var(--border2);cursor:pointer">Regular</button>
        </div>
        <!-- Web publish toggle -->
        <div style="display:inline-flex;border:1px solid var(--border2);border-radius:6px;overflow:hidden;font-size:.78rem">
          <button id="wbf-all" onclick="setWebFilter('')"  class="wbf-btn" style="padding:4px 10px;background:var(--accent);color:#fff;border:none;cursor:pointer">All</button>
          <button id="wbf-web" onclick="setWebFilter('1')" class="wbf-btn" style="padding:4px 10px;background:var(--surface2);color:var(--text2);border:none;border-left:1px solid var(--border2);cursor:pointer">🌐 On Web</button>
          <button id="wbf-noweb" onclick="setWebFilter('0')" class="wbf-btn" style="padding:4px 10px;background:var(--surface2);color:var(--text2);border:none;border-left:1px solid var(--border2);cursor:pointer">Off Web</button>
        </div>
        <input type="hidden" id="product-web-filter" value="">
        <!-- Cost filter -->
        <div style="display:inline-flex;align-items:center;gap:4px;border:1px solid var(--border2);border-radius:6px;padding:2px 8px;font-size:.78rem;background:var(--surface2)">
          <span style="color:var(--text3);white-space:nowrap">Cost ≤ ₹</span>
          <input type="number" id="product-cost-filter" min="0" placeholder="any" style="width:70px;background:transparent;border:none;outline:none;color:var(--text);font-size:.78rem" oninput="loadProducts()">
        </div>
        <input type="hidden" id="product-procurement-filter" value="1">
        <input type="hidden" id="product-combo-filter" value="">
      </div>
    </div>
    <div class="tbl-wrap" id="products-table-wrap" style="max-height:calc(100vh - 220px);overflow-y:auto"><table id="products-table">
      <thead id="products-thead"></thead>
      <tbody id="products-body"></tbody>
    </table></div>
    <div id="products-pagination" style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;gap:8px;flex-wrap:wrap"></div>
    <div id="products-empty" class="empty-state" style="display:none"><span class="empty-icon">📦</span><strong>No products yet</strong><p>Click "Add Product" to get started</p></div>
  </div>
</div>


<div class="modal-backdrop" id="modal-quick-transfer">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><span class="modal-title">&#x1F504; Transfer Stock</span><button class="modal-close" onclick="closeModal('modal-quick-transfer')">&#x2715;</button></div>
    <div class="modal-body">
      <div id="qt-product-name" style="font-weight:700;font-size:.95rem;margin-bottom:14px;color:var(--accent)"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0"><label class="form-label">From *</label><select class="form-control" id="qt-from" onchange="loadQTStock()"></select><div id="qt-from-stock" style="font-size:.75rem;margin-top:3px"></div></div>
        <div class="form-group" style="margin:0"><label class="form-label">To *</label><select class="form-control" id="qt-to" onchange="loadQTStock()"></select><div id="qt-to-stock" style="font-size:.75rem;margin-top:3px"></div></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px">
        <div class="form-group" style="margin:0"><label class="form-label">Qty *</label><input type="number" class="form-control" id="qt-qty" min="1" placeholder="0" oninput="validateQTQty()"><div id="qt-qty-warn" style="font-size:.72rem;color:var(--red);margin-top:3px;display:none">Exceeds available stock</div></div>
        <div class="form-group" style="margin:0"><label class="form-label">Date</label><input type="date" class="form-control" id="qt-date"></div>
      </div>
      <div class="form-group" style="margin:0"><label class="form-label">Note</label><input type="text" class="form-control" id="qt-note" placeholder="Reason for transfer"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-quick-transfer')">Cancel</button>
      <button class="btn btn-primary" id="qt-submit" onclick="submitQuickTransfer()">&#x1F504; Transfer</button>
    </div>
  </div>
</div>
<!-- ══════════ VENDORS ══════════ -->
<div class="page" id="page-vendors">
  <div class="two-col" style="align-items:start">
    <div class="card">
      <div class="card-header"><span class="card-title" id="vendor-form-title">🏭 Add Vendor</span></div>
      <div class="card-body">
        <input type="hidden" id="v-edit-id">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Vendor Name *</label><input class="form-control" id="v-name" placeholder="e.g. Raj Crackers Co."></div>
          <div class="form-group"><label class="form-label">Type</label>
            <select class="form-control" id="v-type">
              <option value="">— Select Type —</option>
              <option value="Fireworks">Fireworks</option>
              <option value="Agent">Agent</option>
              <option value="Both">Both</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Contact Person</label><input class="form-control" id="v-contact" placeholder="Name"></div>
          <div class="form-group"><label class="form-label">City</label><input class="form-control" id="v-city" placeholder="e.g. Sivakasi"></div>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" id="v-phone"></div>
          <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" id="v-email"></div>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">GST Number</label><input class="form-control" id="v-gst"></div>
          <div class="form-group"><label class="form-label">Address</label><input class="form-control" id="v-address"></div>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">💰 Pricing Formula <span style="font-size:.68rem;color:var(--text3);font-weight:400">— steps applied in order to vendor's list price to get your cost</span></label>
          <div id="v-formula-steps"></div>
          <button type="button" class="btn btn-ghost btn-sm" onclick="addFormulaStep('discount_pct',0)" style="margin-top:4px">+ Add Step</button>
          <div style="display:flex;gap:8px;align-items:center;margin-top:10px">
            <input type="number" class="form-control" id="v-test-list-price" placeholder="Test list price ₹ (optional)" step="0.01" style="flex:1;font-size:.8rem" oninput="updateFormulaPreview()">
          </div>
          <div id="v-formula-preview" style="margin-top:8px;font-size:.76rem;color:var(--text3)"></div>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Case Margin ₹ <span style="font-size:.68rem;color:var(--text3);font-weight:400">— per case, added to Landing Cost for this vendor's products. Leave blank to use the default from Settings</span></label>
          <input type="number" class="form-control" id="v-case-margin" step="0.01" min="0" placeholder="Default (from Settings)" onfocus="clearIfZero(this)">
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" id="v-save-btn" style="flex:1;justify-content:center" onclick="saveVendor()">Save Vendor</button>
          <button class="btn btn-ghost" id="v-cancel-btn" style="display:none" onclick="cancelVendorEdit()">Cancel</button>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <span class="card-title" id="vendors-page-title">🏭 Vendors</span>
        <div class="filter-bar" style="margin:0;gap:8px">
          <button class="btn btn-ghost btn-sm" onclick="findCatalogDuplicates('vendors')">🔍 Duplicates</button>
          <input type="text" class="search-input" id="vendor-search" placeholder="Search vendors…" oninput="loadVendors()" style="min-width:130px">
          <select class="filter-select" id="vendor-type-filter" onchange="loadVendors()">
            <option value="">All Types</option>
            <option value="Fireworks">Fireworks</option>
            <option value="Agent">Agent</option>
            <option value="Both">Both</option>
            <option value="Other">Other</option>
          </select>
        </div>
      </div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Vendor</th><th>Type</th><th>Contact</th><th>Phone</th><th>Email</th><th>City</th><th>Products</th><th>Actions</th></tr></thead>
        <tbody id="vendors-body"></tbody>
      </table></div>
      <div id="vendors-empty" class="empty-state" style="display:none"><span class="empty-icon">🏭</span><strong>No vendors yet</strong></div>
    </div>
  </div>
</div>

<!-- ══════════ CATEGORIES ══════════ -->
<div class="page" id="page-categories">
  <div class="two-col" style="align-items:start">
    <div class="card">
      <div class="card-header"><span class="card-title" id="cat-form-title">🏷️ Add Category</span></div>
      <div class="card-body">
        <input type="hidden" id="cat-edit-id">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group">
            <label class="form-label">Category Name *</label>
            <input class="form-control" id="cat-name" placeholder="e.g. Sparklers">
          </div>
          <div class="form-group">
            <label class="form-label">SKU Prefix <span style="font-size:.68rem;color:var(--text3)">(e.g. 11, 15)</span></label>
            <input class="form-control" id="cat-sku-prefix" placeholder="e.g. 11" maxlength="10">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:12px">
          <label class="form-label">Description</label>
          <input class="form-control" id="cat-desc" placeholder="Optional description">
        </div>
        <div class="form-group" style="margin-bottom:18px">
          <label class="form-label">Color Label</label>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px" id="cat-color-swatches">
            <button type="button" onclick="selectCatColor('')"     class="cat-swatch" data-color=""       style="background:var(--surface3);border:2px solid var(--text);width:28px;height:28px;border-radius:50%;cursor:pointer" title="None"></button>
            <button type="button" onclick="selectCatColor('blue')"   class="cat-swatch" data-color="blue"   style="background:#4f8eff;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
            <button type="button" onclick="selectCatColor('green')"  class="cat-swatch" data-color="green"  style="background:#22c55e;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
            <button type="button" onclick="selectCatColor('orange')" class="cat-swatch" data-color="orange" style="background:#f97316;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
            <button type="button" onclick="selectCatColor('red')"    class="cat-swatch" data-color="red"    style="background:#ef4444;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
            <button type="button" onclick="selectCatColor('purple')" class="cat-swatch" data-color="purple" style="background:#a855f7;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
            <button type="button" onclick="selectCatColor('yellow')" class="cat-swatch" data-color="yellow" style="background:#eab308;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
          </div>
          <input type="hidden" id="cat-color">
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" id="cat-save-btn" style="flex:1;justify-content:center" onclick="saveCategory()">Save Category</button>
          <button class="btn btn-ghost" id="cat-cancel-btn" style="display:none" onclick="cancelCategoryEdit()">Cancel</button>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <span class="card-title" id="categories-page-title">🏷️ Categories</span>
        <div class="filter-bar" style="margin:0;gap:8px">
          <button class="btn btn-ghost btn-sm" onclick="findCatalogDuplicates('categories')">🔍 Duplicates</button>
          <input type="text" class="search-input" id="category-search" placeholder="Search categories…" oninput="loadCategoriesPage()" style="min-width:150px">
        </div>
      </div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Category</th><th>SKU Prefix</th><th>Description</th><th>Color</th><th>Products</th><th>Actions</th></tr></thead>
        <tbody id="categories-body"></tbody>
      </table></div>
      <div id="categories-empty" class="empty-state" style="display:none">
        <span class="empty-icon">🏷️</span><strong>No categories yet</strong><p>Add a category to get started</p>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ CUSTOMERS ══════════ -->
<div class="page" id="page-customers">
  <div class="two-col" style="align-items:start">
    <div class="card">
      <div class="card-header"><span class="card-title" id="cust-form-title">👤 Add Customer</span></div>
      <div class="card-body">
        <input type="hidden" id="cust-edit-id">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Name *</label><input class="form-control" id="cust-name" placeholder="Customer name"></div>
          <div class="form-group"><label class="form-label">Phone</label><input class="form-control" id="cust-phone" placeholder="Mobile number"></div>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" id="cust-email" placeholder="Optional"></div>
          <div class="form-group"><label class="form-label">GST Number</label><input class="form-control" id="cust-gst" placeholder="Optional"></div>
        </div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Address</label><textarea class="form-control" id="cust-address" placeholder="Optional" rows="2"></textarea></div>
        <div class="form-group" style="margin-bottom:16px"><label class="form-label">Notes</label><input class="form-control" id="cust-notes" placeholder="Optional notes"></div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" id="cust-save-btn" style="flex:1;justify-content:center" onclick="saveCustomer()">Save Customer</button>
          <button class="btn btn-ghost" id="cust-cancel-btn" style="display:none" onclick="cancelCustomerEdit()">Cancel</button>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header">
        <span class="card-title">👤 Customers</span>
        <div class="filter-bar" style="margin:0"><input type="text" class="search-input" id="customer-search" placeholder="Search…" oninput="loadCustomers()" style="min-width:140px"></div>
      </div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>GST</th><th>Purchases</th><th>Actions</th></tr></thead>
        <tbody id="customers-body"></tbody>
      </table></div>
      <div id="customers-empty" class="empty-state" style="display:none"><span class="empty-icon">👤</span><strong>No customers yet</strong></div>
    </div>
  </div>
  <!-- Customer purchase history panel -->
  <div class="card" id="cust-history-card" style="display:none">
    <div class="card-header">
      <span class="card-title" id="cust-history-title">Purchase History</span>
      <button class="btn btn-ghost btn-sm" onclick="document.getElementById('cust-history-card').style.display='none'">✕</button>
    </div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Invoice</th><th>Date</th><th>Items</th><th>Total ₹</th><th>Payment</th><th>Action</th></tr></thead>
      <tbody id="cust-history-body"></tbody>
    </table></div>
  </div>
</div>

<!-- ══════════ INVOICES ══════════ -->
<div class="page" id="page-invoices">
  <div class="card">
    <div class="card-header">
      <span class="card-title">🧾 Estimates</span>
      <button class="btn btn-primary btn-sm" onclick="openInvoiceModal()">+ New Estimate</button>
    </div>
    <div class="card-body" style="padding:14px 18px 0">
      <div class="filter-bar">
        <input type="text" class="search-input" id="inv-search" placeholder="Search invoice # or customer…" oninput="loadInvoices()">
        <input type="date" class="date-input" id="inv-from" onchange="loadInvoices()" placeholder="From">
        <input type="date" class="date-input" id="inv-to" onchange="loadInvoices()" placeholder="To">
        <select class="filter-select" id="inv-status" onchange="loadInvoices()">
          <option value="">All Status</option><option value="paid">Paid</option><option value="draft">Draft</option><option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Estimate #</th><th>Date</th><th>Customer</th><th>Location</th><th>Items</th><th>Total ₹</th><th>Received ₹</th><th>Balance ₹</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody id="invoices-body"></tbody>
    </table></div>
    <div id="invoices-empty" class="empty-state" style="display:none"><span class="empty-icon">🧾</span><strong>No invoices yet</strong></div>
  </div>
</div>

<!-- ══════════ STOCK IN ══════════ -->
<div class="page" id="page-stock-in">
  <div class="sticky-form-col">
    <div class="card">
      <div class="card-header"><span class="card-title">📥 Record Stock In</span></div>
      <div class="card-body">
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Location</label><select class="form-control" id="si-location" onchange="populateProductSelect('si-product', this.value)"></select></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Vendor</label><select class="form-control" id="si-vendor"></select></div>
        <div class="form-group" style="margin-bottom:12px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
            <label class="form-label" style="margin:0">Product *</label>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm" onclick="openBarcodeModal('si-product')" title="Scan barcode">📷 Scan</button>
              <button class="btn btn-sm" onclick="openQuickAddProduct('si-product')" title="Quick add product" style="background:rgba(79,142,255,.15);color:var(--accent);border:1px solid rgba(79,142,255,.3)">＋ Add Product</button>
            </div>
          </div>
          <select class="form-control" id="si-product" onchange="autofillSICost(this)" style="width:100%;min-height:38px;font-size:.9rem"></select>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Quantity *</label><input type="number" class="form-control" id="si-qty" min="1" placeholder="0"></div>
          <?php if($user['role']!=='manager'): ?><div class="form-group"><label class="form-label">Cost Price ₹</label><input type="number" class="form-control" id="si-cost" step="0.01" placeholder="0.00" onfocus="clearIfZero(this)"></div><?php else: ?><input type="hidden" id="si-cost" value=""><?php endif; ?>
        </div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Purchase Order (optional)</label><select class="form-control" id="si-po"><option value="">— None —</option></select></div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" id="si-date"></div>
          <div class="form-group"><label class="form-label">Invoice / Note</label><input type="text" class="form-control" id="si-note" placeholder="Invoice #…"></div>
        </div>
        <button class="btn btn-success" id="si-submit" style="width:100%;justify-content:center" onclick="recordStockIn()">📥 Record Stock In</button>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">📥 Stock In History</span>
        <div style="display:flex;gap:8px;align-items:center">
          <input type="date" class="date-input" id="si-filter-from" onchange="loadStockIn()">
          <input type="date" class="date-input" id="si-filter-to" onchange="loadStockIn()">
          <button class="btn btn-outline btn-sm" onclick="exportExcel('stock_in')">📊</button>
        </div>
      </div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Date</th><th>Product</th><th>Location</th><th>Vendor</th><th>Qty</th><th>Cost ₹</th><th>Total ₹</th><th>Note</th><th></th></tr></thead>
        <tbody id="si-history"></tbody>
      </table></div>
      <div id="si-empty" class="empty-state" style="display:none"><span class="empty-icon">📥</span><strong>No stock-in recorded</strong></div>
    </div>
  </div>
</div>

<!-- ══════════ PURCHASE ORDERS ══════════ -->
<div class="page" id="page-purchase-orders">
  <div class="card">
    <div class="card-header">
      <span class="card-title">📋 Purchase Orders</span>
      <div style="display:flex;gap:8px;align-items:center">
        <a href="api/import.php?template=purchase_orders" class="btn btn-outline btn-sm">📥 PO Template</a>
        <button class="btn btn-ghost btn-sm" onclick="switchImportToPO()">📂 Import POs</button>
        <button class="btn btn-outline btn-sm" onclick="exportPOs()">📊 Export</button>
        <button class="btn btn-primary btn-sm" onclick="openPOModal()">+ New PO</button>
      </div>
    </div>
    <div class="card-body" style="padding:14px 18px 0">
      <div class="filter-bar">
        <select class="filter-select" id="po-filter-vendor" onchange="loadPOs()"><option value="">All Vendors</option></select>
      </div>
      <div id="po-status-capsules" style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0 4px"></div>
    </div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>PO #</th><th>Vendor</th><th>Location</th><th>Cases</th><th>Total ₹</th><th>Expected</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody id="po-body"></tbody>
    </table></div>
    <div id="po-empty" class="empty-state" style="display:none"><span class="empty-icon">📋</span><strong>No purchase orders yet</strong></div>
  </div>
</div>

<!-- ══════════ TRANSFERS ══════════ -->
<div class="page" id="page-transfers">
  <div class="sticky-form-col">
    <div class="card">
      <div class="card-header"><span class="card-title">🔄 New Transfer</span></div>
      <div class="card-body">
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">From Location *</label><select class="form-control" id="tr-from" onchange="loadTransferStock();populateProductSelect('tr-product', this.value)"></select></div>
          <div class="form-group"><label class="form-label">To Location *</label><select class="form-control" id="tr-to" onchange="loadTransferStock()"></select></div>
        </div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Product *</label><select class="form-control" id="tr-product" onchange="loadTransferStock()"></select></div>
        <div id="tr-stock-info" style="display:none;background:var(--surface3);border-radius:var(--radius-sm);padding:8px 12px;margin-bottom:12px;font-size:.82rem;color:var(--text2)"></div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Quantity *</label><input type="number" class="form-control" id="tr-qty" min="1" placeholder="0"></div>
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" id="tr-date"></div>
        </div>
        <div class="form-group" style="margin-bottom:16px"><label class="form-label">Note</label><input type="text" class="form-control" id="tr-note" placeholder="Reason for transfer"></div>
        <button class="btn btn-primary" id="tr-submit" style="width:100%;justify-content:center" onclick="recordTransfer()">🔄 Record Transfer</button>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">🔄 Transfer History</span></div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Date</th><th>Product</th><th>From</th><th>To</th><th>Qty</th><th>Note</th><th></th></tr></thead>
        <tbody id="tr-history"></tbody>
      </table></div>
      <div id="tr-empty" class="empty-state" style="display:none"><span class="empty-icon">🔄</span><strong>No transfers yet</strong></div>
    </div>
  </div>
</div>

<!-- ══════════ ADJUSTMENTS ══════════ -->
<div class="page" id="page-adjustments">
  <div class="sticky-form-col">
    <div class="card">
      <div class="card-header"><span class="card-title">⚖️ Stock Adjustment</span></div>
      <div class="card-body">
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Location</label><select class="form-control" id="adj-location" onchange="populateProductSelect('adj-product', this.value)"></select></div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Product *</label><select class="form-control" id="adj-product"></select></div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group">
            <label class="form-label">Quantity Change *</label>
            <input type="number" class="form-control" id="adj-qty" placeholder="-5 or +10">
            <div style="font-size:.72rem;color:var(--text3);margin-top:3px">Negative = reduce stock</div>
          </div>
          <div class="form-group"><label class="form-label">Reason *</label>
            <select class="form-control" id="adj-reason">
              <option value="damage">Damaged</option><option value="theft">Theft / Lost</option><option value="correction">Count Correction</option><option value="recount">Recount</option><option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" id="adj-date"></div>
          <div class="form-group"><label class="form-label">Note</label><input type="text" class="form-control" id="adj-note" placeholder="Details…"></div>
        </div>
        <button class="btn btn-warning" id="adj-submit" style="width:100%;justify-content:center" onclick="recordAdjustment()">⚖️ Record Adjustment</button>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">⚖️ Adjustment History</span></div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Date</th><th>Product</th><th>Location</th><th>Change</th><th>Reason</th><th>Note</th><th></th></tr></thead>
        <tbody id="adj-history"></tbody>
      </table></div>
      <div id="adj-empty" class="empty-state" style="display:none"><span class="empty-icon">⚖️</span><strong>No adjustments yet</strong></div>
    </div>
  </div>
</div>

<!-- ══════════ VENDOR PAYMENTS ══════════ -->
<div class="page" id="page-vendor-payments">

  <!-- Top: Vendor balance summary table -->
  <div class="card" style="margin-bottom:18px">
    <div class="card-header">
      <span class="card-title">💰 Vendor Accounts</span>
      <div class="filter-bar" style="margin:0;gap:8px">
        <input type="text" class="search-input" id="vp-vendor-search" placeholder="Search vendor…" oninput="loadVendorPaymentsSummary()" style="min-width:160px">
        <select class="filter-select" id="vp-balance-filter" onchange="loadVendorPaymentsSummary()">
          <option value="">All Vendors</option>
          <option value="outstanding">Outstanding Only</option>
          <option value="clear">Fully Paid</option>
        </select>
        <button class="btn btn-ghost btn-sm" onclick="showPage('payees')">👤 Manage Payees</button>
      </div>
    </div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Vendor</th><th>Opening ₹</th><th>Purchases ₹</th><th>Paid ₹</th><th>Credits ₹</th><th>Balance ₹</th><th>Last Payment</th><th></th></tr></thead>
      <tbody id="vp-summary-body"></tbody>
    </table></div>
    <div id="vp-summary-empty" class="empty-state" style="display:none"><span class="empty-icon">💰</span><strong>No vendor data</strong></div>
  </div>


  <!-- Ledger section (opens when a vendor is selected) -->
  <div id="vp-ledger-section" style="display:none">
    <div class="sticky-form-col">

      <!-- Record transaction form -->
      <div class="card" style="position:sticky;top:72px">
        <div class="card-header">
          <span class="card-title" id="vp-form-vendor-name">Record Transaction</span>
          <button class="btn btn-ghost btn-xs" onclick="closeVendorLedger()">✕</button>
        </div>
        <div class="card-body">
          <input type="hidden" id="vp-vendor-id">
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">Type</label>
            <select class="form-control" id="vp-type" onchange="onVPTypeChange()">
              <option value="payment">💳 Payment to Vendor</option>
              <option value="manual_purchase">🛒 Purchase / Expense (no PO)</option>
              <option value="opening_balance">📂 Opening Balance</option>
              <option value="credit_note">🔄 Credit Note</option>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">Amount ₹ *</label>
            <input type="number" class="form-control" id="vp-amount" step="0.01" min="0.01" placeholder="0.00" oninput="updateAmountWords('vp-amount','vp-amount-words')">
            <div id="vp-amount-words" style="font-size:.7rem;color:var(--text3);font-style:italic;margin-top:4px"></div>
          </div>
          <div class="form-group" style="margin-bottom:12px" id="vp-payee-group">
            <label class="form-label" id="vp-payee-label">Paid By *
              <button type="button" onclick="togglePayeePanel();loadPayees()" style="background:none;border:none;color:var(--accent);font-size:.72rem;cursor:pointer;margin-left:4px">+ Add Payee</button>
            </label>
            <select class="form-control" id="vp-payee"></select>
          </div>
          <div class="form-group" style="margin-bottom:12px" id="vp-desc-group">
            <label class="form-label" id="vp-desc-label">Description</label>
            <input class="form-control" id="vp-desc" placeholder="e.g. Transport charges, Labour">
          </div>
          <div class="form-grid" style="margin-bottom:12px">
            <div class="form-group"><label class="form-label">Date *</label>
              <input type="date" class="form-control" id="vp-date">
            </div>
            <div class="form-group"><label class="form-label">Ref No.</label>
              <input class="form-control" id="vp-ref" placeholder="UTR / Cheque no.">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:16px">
            <label class="form-label">Notes</label>
            <input class="form-control" id="vp-notes" placeholder="Optional">
          </div>
          <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="saveVendorPayment()">💰 Record Transaction</button>
        </div>
        <!-- Live balance box -->
        <div style="margin:0 16px 16px;background:var(--surface3);border-radius:var(--radius-sm);padding:14px 16px">
          <div style="font-size:.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:.6px;font-weight:600;margin-bottom:10px">Account Summary</div>
          <div id="vp-balance-summary" style="display:grid;grid-template-columns:1fr 1fr;gap:6px 10px;font-size:.82rem"></div>
        </div>
      </div>

      <!-- Ledger card -->
      <div class="card">
        <div class="card-header" style="flex-direction:column;align-items:stretch;gap:10px">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap">
            <div>
              <span class="card-title" id="vp-ledger-title">📒 Vendor Ledger</span>
              <div id="vp-ledger-vendor-info" style="font-size:.75rem;color:var(--text3);margin-top:3px"></div>
            </div>
            <div style="display:flex;gap:6px">
              <button class="btn btn-outline btn-sm" onclick="printVendorLedger()">🖨️ Print</button>
              <button class="btn btn-outline btn-sm" onclick="exportVendorLedger()">📊 Export</button>
            </div>
          </div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <span style="font-size:.78rem;color:var(--text3)">From</span>
            <input type="date" class="date-input" id="vp-ledger-from" onchange="refreshVendorLedger(document.getElementById('vp-vendor-id').value)">
            <span style="font-size:.78rem;color:var(--text3)">To</span>
            <input type="date" class="date-input" id="vp-ledger-to" onchange="refreshVendorLedger(document.getElementById('vp-vendor-id').value)">
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('vp-ledger-from').value='';document.getElementById('vp-ledger-to').value='';refreshVendorLedger(document.getElementById('vp-vendor-id').value)">All Time</button>
          </div>
        </div>
        <div class="tbl-wrap">
          <table id="vp-ledger-table">
            <thead><tr>
              <th>Date</th><th>Type</th><th>Description</th>
              <th>Payee</th><th>Ref No.</th>
              <th style="text-align:right">Debit ₹</th>
              <th style="text-align:right">Credit ₹</th>
              <th style="text-align:right">Balance ₹</th>
              <th></th>
            </tr></thead>
            <tbody id="vp-ledger-body"></tbody>
            <tfoot id="vp-ledger-foot"></tfoot>
          </table>
        </div>
        <div id="vp-ledger-empty" class="empty-state" style="display:none">
          <span class="empty-icon">📒</span><strong>No transactions yet</strong>
          <p>Record a payment or purchase above to get started</p>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ══════════ COMBOS ══════════ -->
<div class="page" id="page-combos">
  <div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:8px">
      <span class="card-title">🎁 Combo Builder</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" class="search-input" id="combo-search" placeholder="Search combos…" oninput="renderComboList()" style="min-width:160px">
        <button class="btn btn-primary btn-sm" onclick="openNewComboModal(); return false;">➕ New Combo</button>
      </div>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr>
          <th>Combo</th><th>Target ₹</th><th>Items</th><th>Units</th><th>Sell Total ₹</th>
          <th class="combo-cost-col">Cost ₹</th><th class="combo-cost-col">Margin</th>
          <th>vs Target</th><th></th>
        </tr></thead>
        <tbody id="combo-body"></tbody>
      </table>
    </div>
    <div id="combo-empty" class="empty-state" style="display:none">
      <span class="empty-icon">🎁</span>
      <strong>No combos yet</strong>
      <p>Build assorted product combos (e.g. ₹3000 gift box) — pick products, set quantities, and Invyrr totals everything live.</p>
    </div>
  </div>
</div>

<!-- Combo Builder Modal -->
<div class="modal-backdrop" id="modal-combo">
  <div class="modal" style="max-width:860px">
    <div class="modal-header">
      <span class="modal-title" id="combo-modal-title">🎁 New Combo</span>
      <button class="modal-close" onclick="closeModal('modal-combo')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="combo-edit-id">
      <div class="form-grid-3" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Combo Name *</label>
          <input type="text" class="form-control" id="combo-name" placeholder="e.g. Combo 3000">
        </div>
        <div class="form-group"><label class="form-label">Target Price ₹</label>
          <input type="number" class="form-control" id="combo-target" placeholder="3000" min="0" onfocus="if(this.value==='0')this.value=''" oninput="updateComboTotals()">
        </div>
        <div class="form-group"><label class="form-label">Selling Price ₹ <span style="color:var(--text3);font-weight:400;font-size:.7rem">(optional)</span></label>
          <input type="number" class="form-control" id="combo-sell-price" placeholder="Same as target" min="0" onfocus="if(this.value==='0')this.value=''">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">Notes</label>
        <input type="text" class="form-control" id="combo-notes" placeholder="Optional">
      </div>

      <!-- Add product row -->
      <div style="display:flex;gap:8px;margin-bottom:10px;align-items:center">
        <input type="text" class="form-control" id="combo-prod-search" placeholder="🔍 Type to search products…" style="flex:1" oninput="filterComboProductPicker()">
      </div>
      <div id="combo-picker-results" style="display:none;max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);margin-bottom:12px"></div>

      <!-- Selected items -->
      <div class="tbl-wrap" style="max-height:320px;overflow-y:auto">
        <table>
          <thead><tr><th style="width:20px"></th><th>Product</th><th style="width:64px">Qty</th><th style="width:80px">Price ₹</th><th style="width:80px;text-align:right">Total ₹</th><th style="width:34px"></th></tr></thead>
          <tbody id="combo-items-body"></tbody>
        </table>
      </div>

      <!-- Live totals -->
      <div id="combo-totals" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:14px"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-combo')">Cancel</button>
      <button class="btn btn-primary" id="combo-save-btn" onclick="saveCombo()">💾 Save Combo</button>
    </div>
  </div>
</div>

<!-- ══════════ REPORTS ══════════ -->

<!-- PICKING PAGE -->
<div class="page" id="page-picking">
  <div>

    <!-- Dashboard -->
    <div id="pick-dashboard">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap">
        <div style="flex:1"><div style="font-weight:700;display:flex;align-items:center;gap:8px">Fulfillment
          <span id="pick-sync-status" style="display:none;font-size:.68rem;padding:2px 8px;border-radius:10px;background:rgba(34,197,94,.1);color:var(--green)">&#9679; Live</span></div>
          <div id="pick-dash-date" style="font-size:.75rem;color:var(--text3)"></div></div>
        <select id="pick-dash-location-filter" class="form-control" style="width:150px;font-size:.8rem;padding:4px 8px" onchange="renderPickDashboard()"><option value="">🏪 All Locations</option></select>
        <input type="date" id="pick-dash-date-select" class="form-control" style="width:150px;font-size:.8rem;padding:4px 8px" onchange="loadPickingDate(this.value)">
        <button class="btn btn-ghost btn-sm" onclick="showAllPickingDates()" title="Clear date filter — show every order on record">All dates</button>
        <button class="btn btn-ghost btn-sm" onclick="refreshPickDashboard()" title="Refresh">&#8635;</button>
        <?php if(in_array($user['role'] ?? '', ['admin','partner'])): ?><button class="btn btn-ghost btn-sm" style="color:var(--red);opacity:.7" onclick="clearAllEstimates()">&#128465; Clear</button><?php endif; ?>
        <button class="btn btn-primary btn-sm" onclick="showPickingUpload()">&#43; New Order</button>
      </div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px" id="pick-dash-stats"></div>
      <div>
        <table style="width:100%;table-layout:fixed;border-collapse:collapse;font-size:.82rem">
          <colgroup>
            <col style="width:11%"><col style="width:13%"><col style="width:10%"><col style="width:13%">
            <col style="width:12%"><col style="width:9%"><col style="width:9%"><col style="width:6%"><col style="width:17%">
          </colgroup>
          <thead><tr style="background:var(--surface2)">
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:left;white-space:nowrap">Estimate #</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Customer</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:left;white-space:nowrap">Phone</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Address</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Status</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Picked by</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:left;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Verified by</th>
            <th style="padding:15px 12px;font-size:.72rem;color:var(--text3);text-transform:uppercase;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Items</th>
            <th style="padding:15px 12px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--surface2);z-index:5"></th>
          </tr></thead>
          <tbody id="pick-dash-tbody">
            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3)">
              <div style="font-size:1.5rem;margin-bottom:8px">&#128203;</div>
              <div style="font-weight:600;margin-bottom:8px">No orders today</div>
              <button class="btn btn-primary btn-sm" onclick="showPickingUpload()">+ Add First Order</button>
            </td></tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card" id="pick-upload-card" style="display:none;max-width:960px;margin:0 auto">
      <div class="card-header"><span class="card-title">&#128203; New Order</span><button class="btn btn-ghost btn-sm" onclick="showPickDashboard()">&#8592; Dashboard</button></div>
      <div class="card-body" style="padding:0">
        <div style="display:grid;grid-template-columns:280px 1fr;min-height:400px">
          <!-- Left: estimate list -->
          <div style="border-right:1px solid var(--border);padding:14px;display:flex;flex-direction:column;gap:8px">
            <div style="font-size:.7rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Today's Estimates</div>
            <div id="pick-estimate-list" style="flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:6px">
              <div style="color:var(--text3);font-size:.8rem;text-align:center;padding:20px">No estimates loaded yet</div>
            </div>
            <?php if(in_array($user['role'] ?? '', ['admin','partner'])): ?><button class="btn btn-outline btn-sm" onclick="clearAllEstimates()" style="margin-top:4px">🗑️ Clear All</button><?php endif; ?>
          </div>
          <!-- Right: upload new -->
          <div style="padding:18px;display:flex;flex-direction:column;gap:12px">
            <div id="pick-drop-zone" onclick="document.getElementById('pick-file-input').click()"
              style="border:2px dashed var(--border2);border-radius:var(--radius);padding:24px;text-align:center;cursor:pointer"
              ondragover="event.preventDefault();this.style.borderColor='var(--accent)';this.style.background='rgba(79,142,255,.04)'"
              ondragleave="this.style.borderColor='var(--border2)';this.style.background=''"
              ondrop="event.preventDefault();this.style.borderColor='var(--border2)';this.style.background='';handlePickDrop(event)">
              <div style="font-size:1.8rem;margin-bottom:6px">📂</div>
              <div style="font-weight:600;margin-bottom:3px">Drop PDFs here or click to browse</div>
              <div style="font-size:.75rem;color:var(--text3)">Select multiple PDF estimates at once</div>
              <input type="file" id="pick-file-input" accept=".pdf,.txt" multiple style="display:none" onchange="handlePickFile(this)">
            </div>
            <!-- Bulk progress -->
            <div id="pick-bulk-progress" style="display:none;margin-top:10px">
              <div style="font-size:.8rem;font-weight:600;margin-bottom:6px">Importing estimates...</div>
              <div style="background:var(--surface2);border-radius:10px;height:8px;overflow:hidden;margin-bottom:6px">
                <div id="pick-bulk-bar" style="background:var(--accent);height:100%;width:0%;transition:width .3s;border-radius:10px"></div>
              </div>
              <div id="pick-bulk-status" style="font-size:.75rem;color:var(--text3)"></div>
            </div>
            <div id="pick-extracted-info" style="display:none;background:var(--surface2);border-radius:var(--radius-sm);padding:10px 12px">
              <div style="font-weight:600;margin-bottom:6px;color:var(--accent);font-size:.8rem">Extracted from PDF</div>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;font-size:.82rem">
                <div><span style="color:var(--text3)">Order #</span><br><b id="pick-ext-order"></b></div>
                <div><span style="color:var(--text3)">Customer</span><br><b id="pick-ext-customer"></b></div>
                <div><span style="color:var(--text3)">Phone</span><br><b id="pick-ext-phone"></b></div>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:8px">
              <div class="form-group" style="margin:0"><label class="form-label">Order No.</label><input type="text" class="form-control" id="pick-order-no" placeholder="2025RR1415"></div>
              <div class="form-group" style="margin:0"><label class="form-label">Customer</label><input type="text" class="form-control" id="pick-customer" placeholder="Name"></div>
              <div class="form-group" style="margin:0"><label class="form-label">Phone</label><input type="text" class="form-control" id="pick-phone" placeholder="10-digit"></div>
              <div class="form-group" style="margin:0"><label class="form-label">🏪 Location</label><select class="form-control" id="pick-location"></select></div>
            </div>
            <details><summary style="cursor:pointer;font-size:.82rem;color:var(--text3)">📋 Or paste PDF text manually</summary>
              <textarea class="form-control" id="pick-paste-area" rows="5" placeholder="Paste full PDF text here..." style="font-size:.75rem;resize:vertical;margin-top:8px"></textarea>
            </details>
            <div style="display:flex;gap:8px">
              <button class="btn btn-primary" onclick="parsePicking()">📋 Add to List</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div id="pick-list-area" style="display:none;max-width:960px;margin:0 auto">
      <div class="card" style="margin-bottom:12px">
        <div class="card-body" style="padding:14px 18px">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
            <span style="font-weight:700" id="pick-progress-text">0 / 0 picked</span>
            <div style="display:flex;gap:8px">
              <button class="btn btn-ghost btn-sm" onclick="showPickDashboard()">&#8592; Dashboard</button>
              <button class="btn btn-outline btn-sm" id="pick-print-btn" onclick="printPickSheet('picking')" title="Print picking sheet">&#128424; Print</button>
              <button class="btn btn-success btn-sm" id="pick-complete-btn" onclick="completePicking()">Complete</button>
            </div>
          </div>
          <div style="background:var(--surface2);border-radius:20px;height:10px;overflow:hidden">
            <div id="pick-progress-bar" style="background:var(--accent);height:100%;width:0%;transition:width .3s;border-radius:20px"></div>
          </div>
          <div id="pick-order-summary" style="font-size:.88rem;color:var(--text2);margin-top:10px"></div>
        </div>
      </div>
      <div style="display:flex;gap:6px;margin-bottom:10px;align-items:center;flex-wrap:wrap" id="pick-toolbar-row">
      <div id="pick-status-bar" style="display:flex;align-items:center;gap:5px;margin-bottom:8px;padding:6px 10px;background:var(--surface2);border-radius:var(--radius-sm);flex-wrap:wrap"><span style="font-size:.68rem;color:var(--text3);font-weight:700">STAGE:</span><button onclick="setPickStatus('pending')" id="pst-pending" class="pst-btn" style="padding:2px 8px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-size:.72rem;cursor:pointer">💰 Payment Due</button><button onclick="setPickStatus('picking')" id="pst-picking" class="pst-btn" style="padding:2px 8px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-size:.72rem;cursor:pointer">📦 Picking</button><button onclick="setPickStatus('verification')" id="pst-verification" class="pst-btn" style="padding:2px 8px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-size:.72rem;cursor:pointer">🔍 Verification</button><button onclick="setPickStatus('packing')" id="pst-packing" class="pst-btn" style="padding:2px 8px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-size:.72rem;cursor:pointer">📦 Packing</button><button onclick="openDispatchModal(_pickActiveId)" id="pst-dispatched" class="pst-btn" style="padding:2px 8px;border-radius:20px;border:1px solid var(--border2);background:var(--surface);font-size:.72rem;cursor:pointer">🚚 Dispatched</button><?php if (in_array($user['role'] ?? '', ['admin','manager','partner'])): ?><button onclick="openEstimatePayment(_pickActiveId)" style="padding:2px 8px;border-radius:20px;border:1px solid rgba(34,197,94,.4);background:rgba(34,197,94,.1);color:var(--green);font-size:.72rem;cursor:pointer;margin-left:4px">💰 Payment</button><?php endif; ?></div>
      <div id="pick-ship-info" style="display:none;font-size:.72rem;color:var(--text3);margin:-4px 0 8px 2px"></div>
        <!-- Filter tabs -->
        <button class="btn btn-sm btn-primary" id="pf-all" onclick="filterPickList('all')">All</button>
        <button class="btn btn-sm btn-outline" id="pf-pending" onclick="filterPickList('pending')">Pending</button>
        <button class="btn btn-sm btn-outline" id="pf-done" onclick="filterPickList('done')">Picked</button>
        <div style="flex:1"></div>
        <!-- Select All -->
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:.8rem;cursor:pointer;padding:4px 10px;border:1px solid var(--border2);border-radius:6px;background:var(--surface2)">
          <input type="checkbox" id="pick-select-all" onchange="pickSelectAll(this.checked)" style="width:16px;height:16px;accent-color:var(--green);cursor:pointer">
          Select All
        </label>
        <!-- Verify mode toggle -->
        <?php if(in_array($user['role'] ?? '', ['admin','manager','partner'])): ?><button id="pick-verify-btn" class="btn btn-sm btn-outline" onclick="toggleVerifyMode()" title="Switch to verification mode">
          &#10003;&#10003; Verify
        </button><?php endif; ?>
      </div>
      <!-- Verify mode banner -->
      <div id="pick-verify-banner" style="display:none;background:rgba(168,85,247,.1);border:1px solid rgba(168,85,247,.3);border-radius:var(--radius-sm);padding:8px 14px;margin-bottom:10px;font-size:.82rem;display:none">
        <b style="color:#c084fc">&#128275; Verification Mode</b> — Confirm each item was correctly packed. Tap &#10003; to verify.
        <button onclick="toggleVerifyMode()" style="float:right;background:none;border:none;color:#c084fc;cursor:pointer;font-size:.8rem">Exit</button>
        <div style="clear:both;margin-top:8px;padding-top:8px;border-top:1px solid rgba(168,85,247,.2)">
          <div style="font-weight:700;font-size:.78rem;margin-bottom:6px">&#127873; Add Gift / Complimentary Item</div>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px">
            <input type="text" id="pick-gift-search" class="form-control" placeholder="Search product to add as a gift" style="max-width:260px" oninput="searchPickGiftProduct()">
          </div>
          <div id="pick-gift-results" style="display:flex;flex-direction:column;gap:4px"></div>
        </div>
      </div>
      <div id="pick-payment-lock-banner" style="display:none;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.35);border-radius:var(--radius-sm);padding:10px 14px;margin-bottom:10px;font-size:.85rem;align-items:center;justify-content:space-between;gap:10px">
        <span>&#128176; <b style="color:var(--red)">Payment Due</b> — this order is locked until payment is fully recorded.</span>
        <?php if(in_array($user['role'] ?? '', ['admin','manager','partner'])): ?><button class="btn btn-sm btn-primary" onclick="openEstimatePayment(_pickActiveId)">Record Payment</button><?php endif; ?>
      </div>
      <div id="pick-items-grid" style="display:grid;gap:8px"></div>
    </div>

    <div id="pick-complete-screen" style="display:none">
      <div class="card"><div class="card-body" style="text-align:center;padding:32px 20px">
        <div style="font-size:3rem;margin-bottom:10px">&#127881;</div>
        <div style="font-size:1.4rem;font-weight:700;margin-bottom:6px">Picking Complete!</div>
        <div id="pick-complete-summary" style="color:var(--text3);margin-bottom:16px"></div>
        <div id="pick-missed-items" style="margin-bottom:16px"></div>
        <!-- Send for Verification -->
        <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:16px;margin-bottom:16px;text-align:left">
          <div style="font-weight:700;margin-bottom:8px;font-size:.9rem">&#9989; Send for Verification</div>
          <div style="font-size:.8rem;color:var(--text3);margin-bottom:10px">Generate a code and share with the verifier. They enter it on the Picking page.</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <button class="btn btn-primary btn-sm" onclick="generateVerifyCode()">Generate Code</button>
            <div id="pick-verify-code-box" style="display:none;background:var(--surface);border:2px solid var(--accent);border-radius:8px;padding:6px 14px;font-family:var(--mono);font-size:1.4rem;font-weight:700;color:var(--accent);letter-spacing:4px"><span id="pick-verify-code-display"></span></div>
            <button id="pick-copy-code-btn" class="btn btn-outline btn-sm" style="display:none" onclick="copyVerifyCode()">&#128203; Copy Code</button>
          </div>
        </div>
        <!-- Verifier entry box (for the person verifying) -->
        <?php if(in_array($user['role'] ?? '', ['admin','manager','partner'])): ?><div style="background:var(--surface2);border-radius:var(--radius-sm);padding:16px;margin-bottom:16px;text-align:left">
          <div style="font-weight:700;margin-bottom:8px;font-size:.9rem">&#128275; Verify an Order</div>
          <div style="font-size:.8rem;color:var(--text3);margin-bottom:10px">Enter the code shared by the picker to verify their order.</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <input type="text" id="pick-enter-code" class="form-control" placeholder="Enter code e.g. AB12C" style="max-width:160px;font-family:var(--mono);font-size:1rem;text-transform:uppercase;letter-spacing:2px">
            <button class="btn btn-success btn-sm" onclick="openVerifyByCode()">Open Order</button>
          </div>
        </div><?php endif; ?>
        <!-- Verification status -->
        <div id="pick-verified-badge" style="display:none;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:var(--radius-sm);padding:10px;margin-bottom:16px;color:var(--green);font-weight:700">
          &#9989; Verified by <span id="pick-verified-by"></span>
        </div>
        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
          <button class="btn btn-outline" onclick="printPickSheet('checking')">&#128424; Print Check Sheet</button>
          <button class="btn btn-outline" style="border-color:#25d366;color:#25d366" onclick="sendWhatsApp()">&#128172; WhatsApp</button>
          <button class="btn btn-primary" onclick="newPickingOrder()">&#43; New Order</button>
          <button class="btn btn-outline" onclick="resumePickingList()">&#8592; Back to List</button>
          <button class="btn btn-ghost btn-sm" onclick="showPickDashboard()">&#127968; Dashboard</button>
        </div>
      </div></div>
    </div>
    <!-- Verification view (shown when opened via verify link) -->
    <div id="pick-verify-screen" style="display:none">
      <div class="card">
        <div class="card-header"><span class="card-title">&#9989; Verify Packed Order</span><button class="btn btn-outline btn-sm" onclick="printPickSheet('checking')">&#128424; Print</button></div>
        <div class="card-body">
          <div id="pick-verify-summary" style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px;margin-bottom:14px;font-size:.85rem"></div>
          <div id="pick-verify-items" style="display:grid;gap:6px;margin-bottom:16px"></div>
          <!-- Verifier can add extra items as a gift/compliment — not part of the original estimate -->
          <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px;margin-bottom:16px">
            <div style="font-weight:700;font-size:.8rem;margin-bottom:6px">&#127873; Add Gift / Complimentary Item</div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px">
              <input type="text" id="verify-gift-search" class="form-control" placeholder="Search product to add as a gift" style="max-width:260px" oninput="searchVerifyGiftProduct()">
            </div>
            <div id="verify-gift-results" style="display:flex;flex-direction:column;gap:4px"></div>
          </div>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <input type="text" id="pick-verifier-name" class="form-control" placeholder="Your name" style="max-width:200px">
            <button class="btn btn-success" onclick="confirmVerification()">&#9989; Confirm Verified</button>
            <button class="btn btn-ghost" onclick="showPickingList()">Back</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Substitute Modal -->
<div class="modal-backdrop" id="modal-pick-substitute">
  <div class="modal" style="max-width:500px">
    <div class="modal-header"><span class="modal-title">Substitute Item</span><button class="modal-close" onclick="closePickSubModal()">&#x2715;</button></div>
    <div class="modal-body">
      <div id="pick-sub-original" style="background:var(--surface2);border-radius:var(--radius-sm);padding:10px 12px;margin-bottom:12px;font-size:.85rem"></div>
      <div class="form-group">
        <label class="form-label">Search substitute <span style="color:var(--text3);font-weight:400">(same category / similar price shown first)</span></label>
        <input type="text" class="form-control" id="pick-sub-search" placeholder="Name, SKU or category..." oninput="searchSubstitutes()">
      </div>
      <div id="pick-sub-results" style="max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm)">
        <div style="padding:20px;text-align:center;color:var(--text3)">Loading suggestions...</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="markUnavailableOnly()">No substitute (mark unavailable)</button>
      <button class="btn btn-outline" onclick="closePickSubModal()">Cancel</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-dispatch">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><span class="modal-title">&#x1F69A; Dispatch Order</span><button class="modal-close" onclick="closeDispatchModal()">&#x2715;</button></div>
    <div class="modal-body">
      <div id="dispatch-order-name" style="font-weight:700;font-size:.95rem;margin-bottom:14px;color:var(--accent)"></div>
      <div class="form-group"><label class="form-label">Ship Date *</label><input type="date" class="form-control" id="dispatch-ship-date"></div>
      <div class="form-group"><label class="form-label">Transport Name *</label><input type="text" class="form-control" id="dispatch-transport-name" placeholder="e.g. VRL Logistics"></div>
      <div class="form-group" style="margin-bottom:0"><label class="form-label">No. of Boxes *</label><input type="number" class="form-control" id="dispatch-box-count" min="1" placeholder="0"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeDispatchModal()">Cancel</button>
      <button class="btn btn-primary" id="dispatch-submit-btn" onclick="confirmDispatch()">&#x1F69A; Confirm Dispatch</button>
    </div>
  </div>
</div>

<!-- Change Pick Location Modal -->
<div class="modal-backdrop" id="modal-pick-location">
  <div class="modal" style="max-width:380px">
    <div class="modal-header"><span class="modal-title">&#127978; Change Pick Location</span><button class="modal-close" onclick="closeModal('modal-pick-location')">&#x2715;</button></div>
    <div class="modal-body">
      <div style="font-size:.8rem;color:var(--text3);margin-bottom:12px">Some items for this order may be stocked at a different location — switch where it's being picked from.</div>
      <div class="form-group" style="margin-bottom:0"><label class="form-label">Location</label><select class="form-control" id="pick-location-change"></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-pick-location')">Cancel</button>
      <button class="btn btn-primary" onclick="savePickLocationChange()">&#128190; Save</button>
    </div>
  </div>
</div>

<!-- Edit Stock Adjustment Modal -->
<div class="modal-backdrop" id="modal-adj-edit">
  <div class="modal" style="max-width:420px">
    <div class="modal-header"><span class="modal-title">&#9878;&#65039; Edit Adjustment</span><button class="modal-close" onclick="closeModal('modal-adj-edit')">&#x2715;</button></div>
    <div class="modal-body">
      <input type="hidden" id="adj-edit-id">
      <div id="adj-edit-product" style="font-weight:700;font-size:.95rem;margin-bottom:14px;color:var(--accent)"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="form-label">Location</label><select class="form-control" id="adj-edit-location"></select></div>
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group">
          <label class="form-label">Quantity Change *</label>
          <input type="number" class="form-control" id="adj-edit-qty" placeholder="-5 or +10">
        </div>
        <div class="form-group"><label class="form-label">Reason *</label>
          <select class="form-control" id="adj-edit-reason">
            <option value="damage">Damaged</option><option value="theft">Theft / Lost</option><option value="correction">Count Correction</option><option value="recount">Recount</option><option value="other">Other</option>
          </select>
        </div>
      </div>
      <div class="form-grid" style="margin-bottom:0">
        <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" id="adj-edit-date"></div>
        <div class="form-group"><label class="form-label">Note</label><input type="text" class="form-control" id="adj-edit-note" placeholder="Details…"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-adj-edit')">Cancel</button>
      <button class="btn btn-primary" id="adj-edit-save-btn" onclick="saveAdjustmentEdit()">&#128190; Save</button>
    </div>
  </div>
</div>

<div class="page" id="page-reports">
  <!-- Tab nav + date bar -->
  <div class="card-body" style="padding:0 0 0">
    <div style="display:flex;gap:4px;padding:12px 16px 0;border-bottom:1px solid var(--border);flex-wrap:wrap">
      <button class="btn btn-sm rpt-tab btn-primary" data-tab="overview" onclick="switchRptTab('overview')">📊 Overview</button>
      <button class="btn btn-sm rpt-tab btn-outline" data-tab="vp" onclick="switchRptTab('vp')">💰 Vendor Payments</button>
      <button class="btn btn-sm rpt-tab btn-outline" data-tab="paidto" onclick="switchRptTab('paidto')">👤 Paid To</button>
      <button class="btn btn-sm rpt-tab btn-outline" data-tab="paidby" onclick="switchRptTab('paidby')">💳 Paid By</button>
      <button class="btn btn-sm rpt-tab btn-outline" data-tab="lowstock" onclick="switchRptTab('lowstock')">⚠️ Low Stock</button>
      <button class="btn btn-sm rpt-tab btn-outline" data-tab="picking" onclick="switchRptTab('picking')">📦 Order Picking</button>
    </div>
    <div class="filter-bar" style="padding-top:10px">
      <input type="date" class="date-input" id="rpt-from" onchange="onRptDateChange()">
      <span style="color:var(--text3);font-size:.8rem">to</span>
      <input type="date" class="date-input" id="rpt-to" onchange="onRptDateChange()">
      <button class="btn btn-ghost btn-sm" onclick="setReportRange('month')">This Month</button>
      <button class="btn btn-ghost btn-sm" onclick="setReportRange('quarter')">Quarter</button>
      <button class="btn btn-ghost btn-sm" onclick="setReportRange('year')">Year</button>
      <button class="btn btn-ghost btn-sm" onclick="setReportRange('all')">All Time</button>
      <button class="btn btn-outline btn-sm" id="rpt-export-btn" style="margin-left:auto" onclick="onRptExport()">📊 Export</button>
    </div>
  </div>

  <!-- ── Overview tab ── -->
  <div id="rpt-tab-overview">
    <div class="stats-row" id="report-stats"></div>
    <div class="two-col">
      <div class="card"><div class="card-header"><span class="card-title">📊 Top Selling Products</span></div><div class="card-body"><div class="chart-wrap"><canvas id="chart-topsell"></canvas></div></div></div>
      <div class="card"><div class="card-header"><span class="card-title">💰 Profit by Product</span></div><div class="card-body"><div class="chart-wrap"><canvas id="chart-profit"></canvas></div></div></div>
    </div>
    <div class="two-col">
      <div class="card">
        <div class="card-header"><span class="card-title">💰 Profit & Loss</span><button class="btn btn-outline btn-sm" onclick="exportExcel('pnl')">📊</button></div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Product</th><th>Sold</th><th>Revenue ₹</th><th>COGS ₹</th><th>Profit ₹</th><th>Margin%</th></tr></thead>
          <tbody id="report-pnl"></tbody>
        </table></div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">📦 Stock Value</span></div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Product</th><th>Brand</th><th>Stock</th><th>Cost Value ₹</th><th>Sell Value ₹</th></tr></thead>
          <tbody id="report-value"></tbody>
        </table></div>
      </div>
    </div>
    <div class="two-col">
      <div class="card">
        <div class="card-header"><span class="card-title">🏭 Vendor Purchases</span></div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Vendor</th><th>POs</th><th>Total Qty</th><th>Amount ₹</th><th>Last Purchase</th><th></th></tr></thead>
          <tbody id="report-vendor"></tbody>
        </table></div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">🏪 Stock by Location</span></div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Location</th><th>Products</th><th>Units</th><th>Value ₹</th><th>Low Stock</th></tr></thead>
          <tbody id="report-locations"></tbody>
        </table></div>
      </div>
    </div>
  </div>

  <!-- ── Vendor Payments tab ── -->
  <div id="rpt-tab-vp" style="display:none">
    <div id="rpt-vpr-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px"></div>
    <div class="card">
      <div class="card-header" style="flex-wrap:wrap;gap:8px">
        <span class="card-title">💰 Vendor Payments</span>
        <div style="display:flex;gap:8px;align-items:center">
          <select class="filter-select" id="rpt-vpr-type" onchange="loadRptVP()">
            <option value="">All Types</option>
            <option value="payment">Payments</option>
            <option value="credit_note">Credit Notes</option>
            <option value="opening_balance">Opening Balance</option>
          </select>
          <select class="filter-select" id="rpt-vpr-group" onchange="loadRptVP()">
            <option value="vendor">Group by Vendor</option>
            <option value="payee">Group by Paid Via</option>
            <option value="type">Group by Type</option>
            <option value="month">Group by Month</option>
          </select>
        </div>
      </div>
      <div class="tbl-wrap">
        <table><thead id="rpt-vpr-thead"></thead><tbody id="rpt-vpr-body"></tbody><tfoot id="rpt-vpr-foot"></tfoot></table>
      </div>
      <div id="rpt-vpr-empty" class="empty-state" style="display:none"><span class="empty-icon">💰</span><strong>No vendor payments found</strong></div>
    </div>
  </div>

  <!-- ── Paid To tab ── -->
  <div id="rpt-tab-paidto" style="display:none">
    <div id="rpt-ptr-stats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px"></div>
    <div class="card">
      <div class="card-header" style="flex-wrap:wrap;gap:8px">
        <span class="card-title">👤 Paid To</span>
        <div style="display:flex;gap:8px;align-items:center">
          <select class="filter-select" id="rpt-ptr-group" onchange="loadRptPaidTo()">
            <option value="payee">Group by Person</option>
            <option value="category">Group by Category</option>
            <option value="month">Group by Month</option>
          </select>
        </div>
      </div>
      <div class="tbl-wrap">
        <table><thead id="rpt-ptr-thead"></thead><tbody id="rpt-ptr-body"></tbody><tfoot id="rpt-ptr-foot"></tfoot></table>
      </div>
      <div id="rpt-ptr-empty" class="empty-state" style="display:none"><span class="empty-icon">👤</span><strong>No Paid To records found</strong></div>
    </div>
  </div>

  <!-- ── Paid By tab ── -->
  <div id="rpt-tab-paidby" style="display:none">
    <div id="rpt-pbr-stats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px"></div>
    <div class="card">
      <div class="card-header" style="flex-wrap:wrap;gap:8px">
        <span class="card-title">💳 Paid By (Source of Funds)</span>
        <div style="display:flex;gap:8px;align-items:center">
          <select class="filter-select" id="rpt-pbr-group" onchange="loadRptPaidBy()">
            <option value="payee">Group by Payer</option>
            <option value="category">Group by Category</option>
            <option value="business">Group by Business</option>
            <option value="month">Group by Month</option>
          </select>
        </div>
      </div>
      <div class="tbl-wrap">
        <table><thead id="rpt-pbr-thead"></thead><tbody id="rpt-pbr-body"></tbody><tfoot id="rpt-pbr-foot"></tfoot></table>
      </div>
      <div id="rpt-pbr-empty" class="empty-state" style="display:none"><span class="empty-icon">💳</span><strong>No expense records found</strong></div>
    </div>
  </div>

  <!-- ── Low Stock tab ── -->
  <div id="rpt-tab-lowstock" style="display:none">
    <div class="card">
      <div class="card-header">
        <span class="card-title">⚠️ Low Stock Alerts</span>
        <span id="rpt-alert-count" style="font-size:.8rem;color:var(--text3)"></span>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Stock</th><th>Min Stock</th><th>Deficit</th><th>Vendor</th></tr></thead>
          <tbody id="rpt-alert-body"></tbody>
        </table>
      </div>
      <div id="rpt-alert-empty" class="empty-state" style="display:none"><span class="empty-icon">✅</span><strong>All products are adequately stocked</strong></div>
    </div>
  </div>

  <!-- ── Order Picking tab ── -->
  <div id="rpt-tab-picking" style="display:none">
    <div class="stats-row" id="rpt-picking-stats" style="margin-bottom:12px"></div>
    <div class="card">
      <div class="card-header" style="flex-wrap:wrap;gap:8px">
        <span class="card-title">📦 Order Picking Status</span>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
          <input type="text" class="search-input" id="rpt-picking-search" placeholder="Search order #, customer, owner…" oninput="renderRptPicking()" style="min-width:200px">
          <select class="filter-select" id="rpt-picking-status" onchange="renderRptPicking()">
            <option value="">All Status</option>
            <option value="pending">⏸ Pending</option>
            <option value="picking">📦 Picking</option>
            <option value="verification">🔍 Verification</option>
            <option value="packing">📦 Packing</option>
            <option value="dispatched">🚚 Dispatched</option>
          </select>
          <select class="filter-select" id="rpt-picking-completed-filter" onchange="renderRptPicking()" title="Filter by whether picking has been completed">
            <option value="">Picking: All</option>
            <option value="yes">✅ Picking Completed</option>
            <option value="no">⏳ Picking Not Completed</option>
          </select>
          <span style="font-size:.78rem;color:var(--text3)">Completed:</span>
          <input type="date" class="date-input" id="rpt-picking-completed-from" onchange="renderRptPicking()" title="Picking completed from">
          <span style="color:var(--text3);font-size:.8rem">to</span>
          <input type="date" class="date-input" id="rpt-picking-completed-to" onchange="renderRptPicking()" title="Picking completed to">
        </div>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead><tr>
            <th>Estimate #</th><th>Customer</th><th>Phone</th><th>Status</th>
            <th>Owner (Picker)</th><th>Picking Completed</th>
            <th>Verified By</th><th>Verified At</th>
            <th>Dispatch</th><th>Items Ordered</th><th>Items Picked</th><th>Over/Short</th>
          </tr></thead>
          <tbody id="rpt-picking-body"></tbody>
        </table>
      </div>
      <div id="rpt-picking-empty" class="empty-state" style="display:none"><span class="empty-icon">📋</span><strong>No estimates found</strong></div>
    </div>
  </div>
</div>

<!-- ══════════ ON ORDER REPORT ══════════ -->
<div class="page" id="page-on-order-report">
  <!-- Summary cards -->
  <div id="oor-summary" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px"></div>

  <div class="card">
    <!-- Filter bar -->
    <div class="card-header" style="flex-wrap:wrap;gap:8px">
      <span class="card-title">🛒 Procurement Dashboard</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="text" class="search-input" id="oor-search" placeholder="Search product, SKU, brand…" oninput="loadOnOrderReport()" style="min-width:180px">
        <input type="text" class="filter-select" id="oor-item-code" placeholder="Item Code (e.g. 11)" oninput="loadOnOrderReport()" style="width:140px" title="Prefix match — type 11 to see all item codes starting with 11">
        <!-- Multi-select category dropdown -->
        <div style="position:relative;display:inline-block" id="oor-cat-wrap">
          <button class="filter-select" id="oor-cat-btn" onclick="toggleOORCatPanel()" style="text-align:left;min-width:160px;cursor:pointer" type="button">
            <span id="oor-cat-label">All Categories</span> ▾
          </button>
          <div id="oor-cat-panel" style="display:none;position:absolute;top:100%;left:0;z-index:200;background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius-sm);min-width:220px;max-height:280px;overflow-y:auto;box-shadow:0 8px 24px rgba(0,0,0,.4)">
            <div style="padding:8px 10px;border-bottom:1px solid var(--border)">
              <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;cursor:pointer">
                <input type="checkbox" id="oor-cat-all" checked onchange="onOORCatAllChange(this)"> All Categories
              </label>
            </div>
            <div id="oor-cat-list" style="padding:6px 0"></div>
          </div>
        </div>
        <select class="filter-select" id="oor-vendor" onchange="loadOnOrderReport()"><option value="">All Vendors</option></select>
        <select class="filter-select" id="oor-brand" onchange="loadOnOrderReport()"><option value="">All Brands</option></select>
        <select class="filter-select" id="oor-filter" onchange="loadOnOrderReport()">
          <option value="">All Products</option>
          <option value="out">Out of Stock</option>
          <option value="low">Low Stock</option>
          <option value="on_order">Has On Order</option>
          <option value="no_order">Needs Reorder (no PO)</option>
        </select>
        <select class="filter-select" id="oor-group" onchange="loadOnOrderReport()">
          <option value="">No Grouping</option>
          <option value="category">Group by Category</option>
          <option value="vendor">Group by Vendor</option>
          <option value="item_code" selected>Group by Item Code</option>
          <option value="brand">Group by Brand</option>
          <option value="status">Group by Status</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="exportOnOrderReport()">📊 Export</button>
        <button class="btn btn-ghost btn-sm" onclick="toggleOORColChooser()" title="Choose columns">⚙️ Columns</button>
        <button class="btn btn-ghost btn-sm" onclick="clearOORInputs()" title="Clear all To Be Ordered values" style="color:var(--red);border-color:var(--red)">🗑️ Clear</button>
      </div>
    </div>
    <!-- Column chooser panel -->
    <div id="oor-col-chooser" style="display:none;padding:10px 16px;background:var(--surface2);border-bottom:1px solid var(--border)">
      <div id="oor-col-toggle-list" style="display:flex;flex-wrap:wrap;gap:10px 20px"></div>
      <button class="btn btn-ghost btn-xs" onclick="resetOORColPrefs()" style="margin-top:8px;font-size:.72rem">↺ Reset to default</button>
    </div>

    <div id="oor-tbo-total" style="padding:6px 16px;font-size:.82rem;font-weight:700;color:#f97316;min-height:24px"></div>
    <!-- Category reference pills -->
    <div id="oor-cat-ref" style="display:none;padding:6px 16px 8px;background:var(--surface2);border-bottom:1px solid var(--border)">
      <div id="oor-cat-ref-body" style="display:flex;flex-wrap:wrap;gap:5px 8px;align-items:center"></div>
    </div>
    <div class="tbl-wrap" id="oor-table-wrap" style="max-height:calc(100vh - 280px);overflow-y:auto">
      <table id="oor-table">
        <thead id="oor-thead"></thead>
        <tbody id="oor-tbody"></tbody>
      </table>
    </div>
    <div id="oor-empty" class="empty-state" style="display:none">
      <span class="empty-icon">🛒</span>
      <strong>No products found</strong>
      <p>Try adjusting your filters.</p>
    </div>
  </div>
</div>

<!-- ══════════ PRODUCT LEDGER ══════════ -->
<div class="page" id="page-product-ledger">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="btn btn-ghost btn-sm" onclick="showPage('products')">← Back</button>
        <div>
          <span class="card-title" id="pl-product-name">Product Ledger</span>
          <div style="font-size:.75rem;color:var(--text3);margin-top:2px" id="pl-product-meta"></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="date" class="date-input" id="pl-from" onchange="loadProductLedger()">
        <span style="color:var(--text3);font-size:.8rem">to</span>
        <input type="date" class="date-input" id="pl-to" onchange="loadProductLedger()">
        <button class="btn btn-outline btn-sm" onclick="exportProductLedger()">📊 Export</button>
      </div>
    </div>
    <!-- Stat cards -->
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)" id="pl-stats"></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    <!-- Stock by Location -->
    <div class="card">
      <div class="card-header"><span class="card-title">📍 Stock by Location</span></div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Location</th><th>Stock</th><th>Min Stock</th><th>Status</th></tr></thead>
        <tbody id="pl-locations"></tbody>
      </table></div>
    </div>
    <!-- Open POs -->
    <div class="card">
      <div class="card-header"><span class="card-title">📋 Open Purchase Orders</span></div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>PO #</th><th>Vendor</th><th>Ordered</th><th>Received</th><th>Pending</th><th>Status</th></tr></thead>
        <tbody id="pl-open-pos"></tbody>
      </table></div>
      <div id="pl-no-pos" class="empty-state" style="display:none;padding:20px"><span style="color:var(--text3);font-size:.85rem">No open purchase orders</span></div>
    </div>
  </div>

  <!-- Unified Transaction Ledger -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📒 Transaction History</span>
      <span id="pl-txn-count" style="font-size:.8rem;color:var(--text3)"></span>
    </div>
    <div class="tbl-wrap">
      <table id="pl-ledger-table">
        <thead><tr>
          <th>Date</th><th>Type</th><th>Description</th>
          <th>Vendor / Customer</th><th>Location</th><th>Ref</th>
          <th style="text-align:right">In</th>
          <th style="text-align:right">Out</th>
          <th style="text-align:right">Balance</th>
        </tr></thead>
        <tbody id="pl-ledger-body"></tbody>
        <tfoot id="pl-ledger-foot"></tfoot>
      </table>
    </div>
    <div id="pl-ledger-empty" class="empty-state" style="display:none"><span class="empty-icon">📒</span><strong>No transactions in this period</strong></div>
  </div>
</div>

<!-- ══════════ PAYEE LEDGER ══════════ -->
<div class="page" id="page-payee-ledger">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="btn btn-ghost btn-sm" id="payee-ledger-back-btn" onclick="showPage('payees')">← Back to Payees</button>
        <div>
          <span class="card-title" id="payl-name">Payee Ledger</span>
          <div style="font-size:.75rem;color:var(--text3);margin-top:2px" id="payl-meta"></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="date" class="date-input" id="payl-from" onchange="loadPayeeLedger()">
        <span style="color:var(--text3);font-size:.8rem">to</span>
        <input type="date" class="date-input" id="payl-to" onchange="loadPayeeLedger()">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('payl-from').value='';document.getElementById('payl-to').value='';loadPayeeLedger()">All Time</button>
        <button class="btn btn-outline btn-sm" onclick="exportPayeeLedger()">📊 Export</button>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)" id="payl-stats"></div>
  </div>
  <div class="card">
    <div class="card-header">
      <span class="card-title">💳 Transaction History</span>
      <span id="payl-txn-count" style="font-size:.8rem;color:var(--text3)"></span>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Date</th><th>Type</th><th>Vendor</th><th>Reference</th><th>Description</th><th style="text-align:right">Amount ₹</th><th style="text-align:right">Running Total ₹</th></tr></thead>
        <tbody id="payl-body"></tbody>
        <tfoot id="payl-foot"></tfoot>
      </table>
    </div>
    <div id="payl-empty" class="empty-state" style="display:none"><span class="empty-icon">💳</span><strong>No transactions in this period</strong></div>
  </div>
</div>

<!-- ══════════ VENDOR LEDGER REPORT ══════════ -->
<div class="page" id="page-vendor-ledger">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="btn btn-ghost btn-sm" onclick="showPage('vendor-payments')">← Back</button>
        <div>
          <span class="card-title" id="vlr-vendor-name">Vendor Ledger</span>
          <div style="font-size:.75rem;color:var(--text3);margin-top:2px" id="vlr-vendor-meta"></div>
        </div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="date" class="date-input" id="vlr-from" onchange="loadVendorLedgerReport()">
        <span style="color:var(--text3);font-size:.8rem">to</span>
        <input type="date" class="date-input" id="vlr-to" onchange="loadVendorLedgerReport()">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('vlr-from').value='';document.getElementById('vlr-to').value='';loadVendorLedgerReport()">All Time</button>
        <button class="btn btn-outline btn-sm" onclick="exportVendorLedger()">📊 Export</button>
      </div>
    </div>
    <!-- Summary stat cards -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px 18px;border-bottom:1px solid var(--border)" id="vlr-stats"></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">📒 Transaction Ledger</span>
      <span id="vlr-balance-badge" style="font-family:var(--mono);font-size:.9rem;font-weight:700"></span>
    </div>
    <div class="tbl-wrap">
      <table id="vlr-table">
        <thead><tr>
          <th>Date</th><th>Type</th><th>Description</th><th>Payee</th>
          <th>Ref No.</th><th style="text-align:right">Debit ₹</th>
          <th style="text-align:right">Credit ₹</th>
          <th style="text-align:right">Balance ₹</th>
          <th></th>
        </tr></thead>
        <tbody id="vlr-body"></tbody>
        <tfoot id="vlr-foot"></tfoot>
      </table>
    </div>
    <div id="vlr-empty" class="empty-state" style="display:none">
      <span class="empty-icon">📒</span><strong>No transactions in this period</strong>
    </div>
  </div>
</div>

<!-- ══════════ PAID TO REPORT ══════════ -->
<div class="page" id="page-paid-to-report">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header" style="flex-wrap:wrap;gap:8px">
      <span class="card-title">👤 Paid To Report</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="date" class="date-input" id="ptr-from" onchange="loadPaidToReport()">
        <span style="color:var(--text3);font-size:.8rem">to</span>
        <input type="date" class="date-input" id="ptr-to" onchange="loadPaidToReport()">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('ptr-from').value='';document.getElementById('ptr-to').value='';loadPaidToReport()">All Time</button>
        <select class="filter-select" id="ptr-group" onchange="loadPaidToReport()">
          <option value="payee">Group by Person</option>
          <option value="category">Group by Category</option>
          <option value="month">Group by Month</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="exportPaidToReport()">📊 Export</button>
      </div>
    </div>
    <div id="ptr-stats" style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)"></div>
  </div>
  <div class="card">
    <div class="card-header">
      <span class="card-title">💳 Payment Details</span>
      <span id="ptr-count" style="font-size:.8rem;color:var(--text3)"></span>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead id="ptr-thead"></thead>
        <tbody id="ptr-body"></tbody>
        <tfoot id="ptr-foot"></tfoot>
      </table>
    </div>
    <div id="ptr-empty" class="empty-state" style="display:none">
      <span class="empty-icon">👤</span>
      <strong>No Paid To records found</strong>
      <p>Record expenses with a Paid To field to see them here.</p>
    </div>
  </div>
</div>

<!-- ══════════ VENDOR PAYMENTS REPORT ══════════ -->
<div class="page" id="page-vp-report">
  <div class="card" style="margin-bottom:16px">
    <div class="card-header" style="flex-wrap:wrap;gap:8px">
      <span class="card-title">💰 Vendor Payments Report</span>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="date" class="date-input" id="vpr-from" onchange="loadVPReport()">
        <span style="color:var(--text3);font-size:.8rem">to</span>
        <input type="date" class="date-input" id="vpr-to" onchange="loadVPReport()">
        <button class="btn btn-ghost btn-sm" onclick="document.getElementById('vpr-from').value='';document.getElementById('vpr-to').value='';loadVPReport()">All Time</button>
        <select class="filter-select" id="vpr-type" onchange="loadVPReport()">
          <option value="">All Types</option>
          <option value="payment">Payments</option>
          <option value="credit_note">Credit Notes</option>
          <option value="opening_balance">Opening Balance</option>
        </select>
        <select class="filter-select" id="vpr-group" onchange="loadVPReport()">
          <option value="vendor">Group by Vendor</option>
          <option value="payee">Group by Paid Via</option>
          <option value="type">Group by Type</option>
          <option value="month">Group by Month</option>
        </select>
        <button class="btn btn-outline btn-sm" onclick="exportVPReport()">📊 Export</button>
      </div>
    </div>
    <div id="vpr-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)"></div>
  </div>
  <div class="card">
    <div class="card-header">
      <span class="card-title">📋 Transaction Details</span>
      <span id="vpr-count" style="font-size:.8rem;color:var(--text3)"></span>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead id="vpr-thead"></thead>
        <tbody id="vpr-body"></tbody>
        <tfoot id="vpr-foot"></tfoot>
      </table>
    </div>
    <div id="vpr-empty" class="empty-state" style="display:none">
      <span class="empty-icon">💰</span>
      <strong>No vendor payments found</strong>
    </div>
  </div>
</div>

<!-- ══════════ LOW STOCK ALERTS ══════════ -->
<div class="page" id="page-alerts">
  <div class="card">
    <div class="card-header"><span class="card-title">🔔 Low Stock Alerts</span><button class="btn btn-warning btn-sm" onclick="sendAlertEmail()" id="send-alert-btn">📧 Email Alert Now</button></div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Stock</th><th>Min</th><th>Shortage</th><th>Vendor</th><th>Action</th></tr></thead>
      <tbody id="alerts-body"></tbody>
    </table></div>
    <div id="alerts-empty" class="empty-state" style="display:none"><span class="empty-icon">✅</span><strong>All stocks healthy!</strong></div>
  </div>
</div>

<!-- ══════════ LOCATIONS ══════════ -->
<!-- ══════════ AUDIT LOG ══════════ -->
<div class="page" id="page-audit">
  <div class="card">
    <div class="card-header">
      <span class="card-title">📜 Audit Log</span>
      <span id="audit-total-label" style="font-size:.78rem;color:var(--text3)"></span>
    </div>
    <div class="card-body" style="padding:14px 18px 0">
      <div class="filter-bar">
        <input type="date" class="date-input" id="audit-from" onchange="loadAudit()">
        <input type="date" class="date-input" id="audit-to" onchange="loadAudit()">
        <select class="filter-select" id="audit-action-filter" onchange="loadAudit()">
          <option value="">All Actions</option>
          <option value="create">➕ Created</option>
          <option value="update">✏️ Updated</option>
          <option value="delete">🗑️ Deleted</option>
          <option value="stock_in">📥 Stock In</option>
          <option value="stock_out">📤 Stock Out</option>
          <option value="import">📂 Import</option>
          <option value="import_skipped">⚠️ Import Skipped</option>
          <option value="login">🔐 Login</option>
        </select>
        <input type="text" class="search-input" id="audit-search" placeholder="Search user, action, detail…" oninput="loadAudit()" style="min-width:160px">
        <select class="filter-select" id="audit-limit" onchange="loadAudit()">
          <option value="200">200 rows</option>
          <option value="500">500 rows</option>
          <option value="1000">1000 rows</option>
          <option value="5000">All (5000)</option>
        </select>
      </div>
    </div>
    <div class="tbl-wrap"><table>
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>What</th><th>Detail</th><th>IP</th></tr></thead>
      <tbody id="audit-body"></tbody>
    </table></div>
    <div id="audit-empty" class="empty-state" style="display:none"><span class="empty-icon">📜</span><strong>No audit entries</strong></div>
  </div>
</div>

<!-- ══════════ SETTINGS ══════════ -->

<!-- ══════════ PAYEES ══════════ -->
<div class="page" id="page-payees">
<div class="two-col" style="align-items:start">
      <div class="card">
        <div class="card-header">
          <span class="card-title" id="payee-form-title">👤 Add Payee</span>
          <button class="btn btn-ghost btn-xs" onclick="togglePayeePanel()">✕</button>
        </div>
        <div class="card-body">
          <input type="hidden" id="payee-edit-id">
          <div class="form-group" style="margin-bottom:12px">
            <label class="form-label">Name * <span style="font-size:.68rem;color:var(--text3)">(person or account)</span></label>
            <input class="form-control" id="payee-name" placeholder="e.g. Raj, SR Traders">
          </div>
          <div class="form-grid" style="margin-bottom:12px">
            <div class="form-group"><label class="form-label">Type</label>
              <div style="display:flex;gap:6px">
                <select class="form-control" id="payee-type"></select>
                <button class="btn btn-ghost btn-sm" type="button" onclick="openPayeeTypeModal()" title="Manage payee types">⚙️</button>
              </div>
            </div>
            <div class="form-group"><label class="form-label">Phone</label>
              <input class="form-control" id="payee-phone" placeholder="Optional">
            </div>
          </div>
          <div class="form-grid" style="margin-bottom:12px">
            <div class="form-group"><label class="form-label">Bank Name</label>
              <input class="form-control" id="payee-bank-name" placeholder="e.g. SBI">
            </div>
            <div class="form-group"><label class="form-label">Account No.</label>
              <input class="form-control" id="payee-account-no" placeholder="Optional">
            </div>
          </div>
          <div class="form-grid" style="margin-bottom:12px">
            <div class="form-group"><label class="form-label">IFSC</label>
              <input class="form-control" id="payee-ifsc" placeholder="Optional">
            </div>
            <div class="form-group"><label class="form-label">UPI ID</label>
              <input class="form-control" id="payee-upi-id" placeholder="Optional">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label class="form-label">Notes</label>
            <input class="form-control" id="payee-notes" placeholder="Optional">
          </div>
          <label style="display:flex;align-items:center;gap:8px;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" id="payee-active" checked style="width:15px;height:15px;accent-color:var(--accent)">
            <span style="font-size:.84rem;color:var(--text2)">Active</span>
          </label>
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary" id="payee-save-btn" style="flex:1;justify-content:center" onclick="savePayee()">Save Payee</button>
            <button class="btn btn-ghost" id="payee-cancel-btn" style="display:none" onclick="cancelPayeeEdit()">Cancel</button>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:8px">
          <span class="card-title">👤 Payees</span>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
            <a href="api/import.php?template=payees" class="btn btn-outline btn-sm" title="Download CSV template">📥 Template</a>
            <button class="btn btn-ghost btn-sm" onclick="switchImportToPayees()" title="Import payees from CSV">📂 Import</button>
            <button class="btn btn-outline btn-sm" onclick="exportPayeesList()" title="Export all payees as CSV">📊 Export</button>
            <button class="btn btn-outline btn-sm" onclick="exportAllPayeeLedgers()" title="Export all payee ledgers as one CSV">📊 Export All Ledgers</button>
            <input type="text" class="search-input" id="payee-search" placeholder="Search…" oninput="loadPayees()" style="min-width:140px">
          </div>
        </div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Name</th><th>Type</th><th>Bank / UPI</th><th>Phone</th><th>Payments</th><th>Total Paid</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="payees-body"></tbody>
        </table></div>
        <div id="payees-empty" class="empty-state" style="display:none"><span class="empty-icon">👤</span><strong>No payees yet</strong></div>
      </div>
    </div>
  </div>
  </div>


<div class="page" id="page-settings">

  <!-- Tab nav moved to sub-nav bar above content -->
  <div style="display:none">
    <button class="settings-tab active" data-tab="general"    onclick="switchSettingsTab('general')"   >⚙️ General</button>
    <button class="settings-tab"        data-tab="locations"  onclick="switchSettingsTab('locations')" >🏪 Locations</button>
    <?php if($user['role']==='admin'): ?>
    <button class="settings-tab"        data-tab="users"      onclick="switchSettingsTab('users')"     >👥 Users</button>
    <?php endif; ?>
    <button class="settings-tab"        data-tab="appearance" onclick="switchSettingsTab('appearance')">🎨 Appearance</button>
    <button class="settings-tab"        data-tab="backup"     onclick="switchSettingsTab('backup')"    >☁️ Backup</button>
  </div>

  <!-- ── General ── -->
  <div id="stab-general" class="settings-tab-pane">
    <div class="two-col" style="align-items:start">
      <div>
        <div class="card">
          <div class="card-header"><span class="card-title">🏢 Business Info</span></div>
          <div class="card-body">
            <div class="form-group" style="margin-bottom:12px"><label class="form-label">Business Name</label><input class="form-control" id="s-biz-name"></div>
            <div class="form-group" style="margin-bottom:12px"><label class="form-label">Address</label><textarea class="form-control" id="s-biz-addr" rows="2"></textarea></div>
            <div class="form-grid" style="margin-bottom:12px">
              <div class="form-group"><label class="form-label">Phone</label><input class="form-control" id="s-biz-phone"></div>
              <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-control" id="s-biz-email"></div>
            </div>
            <div class="form-group"><label class="form-label">GST Number</label><input class="form-control" id="s-biz-gst"></div>
            <div class="form-group" style="margin-top:12px"><label class="form-label">Sidebar Tagline <span style="font-size:.68rem;color:var(--text3);font-weight:400">— shown under "Invyrr" in the sidebar (e.g. your org name)</span></label><input class="form-control" id="s-sidebar-tagline" placeholder="v2.0 · Multi-location"></div>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><span class="card-title">💰 Invoice &amp; Tax</span></div>
          <div class="card-body">
            <div class="form-grid" style="margin-bottom:12px">
              <div class="form-group"><label class="form-label">Invoice Prefix</label><input class="form-control" id="s-inv-prefix" placeholder="INV"></div>
              <div class="form-group"><label class="form-label">PO Prefix</label><input class="form-control" id="s-po-prefix" placeholder="PO"></div>
            </div>
            <div class="form-grid">
              <div class="form-group"><label class="form-label">Currency Symbol</label><input class="form-control" id="s-currency" placeholder="₹"></div>
              <div class="form-group"><label class="form-label">Default Tax Rate %</label><input type="number" class="form-control" id="s-tax" step="0.01" placeholder="0"></div>
            </div>
            <div class="form-grid" style="margin-top:12px">
              <div class="form-group"><label class="form-label">Case Margin ₹ <span style="font-size:.68rem;color:var(--text3);font-weight:400">(per case, added to Landing Cost)</span></label><input type="number" class="form-control" id="s-case-margin" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)"></div>
            </div>
          </div>
        </div>
      </div>
      <div>
        <div class="card">
          <div class="card-header"><span class="card-title">📧 Email Alerts</span></div>
          <div class="card-body">
            <p style="font-size:.82rem;color:var(--text3);margin-bottom:14px">Send email when any product falls below its minimum stock level.</p>
            <div class="form-group" style="margin-bottom:12px"><label class="form-label">Alert Email Address</label><input type="email" class="form-control" id="s-alert-email" placeholder="manager@yourbusiness.com"></div>
            <hr>
            <div style="font-size:.78rem;color:var(--text2);font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:.6px">SMTP Configuration</div>
            <div class="form-grid" style="margin-bottom:12px">
              <div class="form-group"><label class="form-label">SMTP Host</label><input class="form-control" id="s-smtp-host" placeholder="smtp.gmail.com"></div>
              <div class="form-group"><label class="form-label">Port</label><input type="number" class="form-control" id="s-smtp-port" placeholder="587"></div>
            </div>
            <div class="form-grid" style="margin-bottom:12px">
              <div class="form-group"><label class="form-label">Username</label><input class="form-control" id="s-smtp-user" placeholder="your@email.com"></div>
              <div class="form-group"><label class="form-label">Password</label><input type="password" class="form-control" id="s-smtp-pass" placeholder="App password"></div>
            </div>
            <button class="btn btn-ghost btn-sm" onclick="testEmail()">✉️ Send Test Email</button>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="card-title">⌨️ Keyboard Shortcuts</span></div>
          <div class="card-body" style="font-size:.82rem">
            <div style="display:grid;grid-template-columns:auto 1fr;gap:6px 16px;align-items:center">
              <kbd class="kbd">N</kbd><span>New Product</span>
              <kbd class="kbd">I</kbd><span>New Invoice</span>
              <kbd class="kbd">S</kbd><span>Stock In</span>
              <kbd class="kbd">R</kbd><span>Reports</span>
              <kbd class="kbd">D</kbd><span>Dashboard</span>
              <kbd class="kbd">?</kbd><span>Shortcuts help</span>
              <kbd class="kbd">Esc</kbd><span>Close modal</span>
            </div>
          </div>
        </div>
        <button class="btn btn-primary" id="settings-save-btn" style="width:100%;justify-content:center;border-radius:var(--radius-sm)" onclick="saveSettings()">💾 Save All Settings</button>
      </div>
    </div>
  </div>

  <!-- ── Locations ── -->
  <div id="stab-locations" class="settings-tab-pane" style="display:none">
    <div class="sticky-form-col">
      <div class="card">
        <div class="card-header"><span class="card-title" id="loc-form-title">🏪 Add Location</span></div>
        <div class="card-body">
          <input type="hidden" id="loc-edit-id">
          <div class="form-group" style="margin-bottom:12px"><label class="form-label">Name *</label><input class="form-control" id="loc-name" placeholder="e.g. Main Store, Warehouse"></div>
          <div class="form-group" style="margin-bottom:12px"><label class="form-label">Phone</label><input class="form-control" id="loc-phone" placeholder="Optional"></div>
          <div class="form-group" style="margin-bottom:12px"><label class="form-label">Address</label><input class="form-control" id="loc-address" placeholder="Street, city…"></div>
          <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px;cursor:pointer">
            <input type="checkbox" id="loc-default" style="width:15px;height:15px;accent-color:var(--accent)">
            <span style="font-size:.84rem;color:var(--text2)">Set as default location</span>
          </label>
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary" id="loc-save-btn" style="flex:1;justify-content:center" onclick="saveLocation()">Save Location</button>
            <button class="btn btn-ghost" id="loc-cancel-btn" style="display:none" onclick="cancelLocationEdit()">Cancel</button>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">🏪 All Locations</span></div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Location</th><th>Address</th><th>Phone</th><th>Products</th><th>Units</th><th>Value ₹</th><th>Low Stock</th><th>Actions</th></tr></thead>
          <tbody id="locations-body"></tbody>
        </table></div>
      </div>
    </div>
    <div class="card" style="margin-top:18px">
      <div class="card-header">
        <span class="card-title">📦 Stock by Location</span>
        <select class="filter-select" id="loc-stock-location-filter" onchange="loadLocationStockTable()"><option value="">Select location…</option></select>
      </div>
      <div class="tbl-wrap"><table>
        <thead><tr><th>Product</th><th>Brand</th><th>Category</th><th>Stock</th><th>Min</th><th>Status</th><th>Cost Value</th><th>Sell Value</th></tr></thead>
        <tbody id="loc-stock-body"><tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text3)">Select a location above</td></tr></tbody>
      </table></div>
    </div>
  </div>

  <!-- ── Users ── -->
  <div id="stab-users" class="settings-tab-pane" style="display:none">
    <div class="two-col" style="align-items:start">
      <div class="card">
        <div class="card-header"><span class="card-title" id="user-form-title">👥 Add User</span></div>
        <div class="card-body">
          <input type="hidden" id="usr-edit-id">
          <div class="form-group" style="margin-bottom:12px"><label class="form-label">Full Name * <span style="font-size:.68rem;color:var(--text3);font-weight:400">(used to login)</span></label><input class="form-control" id="usr-name" placeholder="e.g. Rajan"></div>
          <div class="form-group" style="margin-bottom:12px"><label class="form-label">Email <span style="font-size:.68rem;color:var(--text3);font-weight:400">(optional)</span></label><input type="email" class="form-control" id="usr-email" placeholder="Optional"></div>
          <div class="form-group" style="margin-bottom:12px"><label class="form-label">Password <span id="usr-pass-hint" style="font-size:.7rem;color:var(--text3)">(required for new user)</span></label><input type="password" class="form-control" id="usr-pass" placeholder="Leave blank to keep existing"></div>
          <div class="form-grid" style="margin-bottom:14px">
            <div class="form-group"><label class="form-label">Role</label>
              <select class="form-control" id="usr-role">
                <option value="Picker">Picker</option>
              <option value="partner">Partner</option><option value="manager">Manager</option><option value="admin">Admin</option>
              </select>
            </div>
            <div class="form-group"><label class="form-label">Status</label>
              <select class="form-control" id="usr-active"><option value="1">Active</option><option value="0">Inactive</option></select>
            </div>
          </div>
          <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px;font-size:.78rem;color:var(--text3);margin-bottom:14px">
            <strong style="color:var(--text2)">Roles:</strong> Admin = full access · Manager = no delete, no cost/margin · Picker = Pick/Pack/Dispatch only, no cost/margin/vendor info
          </div>
          <div style="display:flex;gap:8px">
            <button class="btn btn-primary" id="usr-save-btn" style="flex:1;justify-content:center" onclick="saveUser()">Save User</button>
            <button class="btn btn-ghost" id="usr-cancel-btn" style="display:none" onclick="cancelUserEdit()">Cancel</button>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><span class="card-title">👥 System Users</span></div>
        <div class="tbl-wrap"><table>
          <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last Login</th><th>Actions</th></tr></thead>
          <tbody id="users-body"></tbody>
        </table></div>
      </div>
    </div>
  </div>

  <!-- ── Payees ── -->
  <div id="stab-payees" class="settings-tab-pane" style="display:none">
    <div class="empty-state" style="padding:48px">
      <span class="empty-icon">💳</span>
      <strong>Payees</strong>
      <p style="margin-top:10px">
        <button class="btn btn-primary" onclick="showPage('payees')">
          👤 Go to Payees
        </button>
      </p>
    </div>
  </div>
  </div>

  <!-- ── Appearance ── -->
  <div id="stab-appearance" class="settings-tab-pane" style="display:none">
    <div class="card" style="margin-bottom:18px">
      <div class="card-header"><span class="card-title">🎨 Theme</span></div>
      <div class="card-body">
        <p style="font-size:.84rem;color:var(--text2);margin-bottom:14px">Pick a color scheme. Your choice is saved to your account and follows you on any device.</p>
        <div class="theme-swatches-grid" id="appearance-theme-grid"></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span class="card-title">🔤 Font</span></div>
      <div class="card-body">
        <p style="font-size:.84rem;color:var(--text2);margin-bottom:14px">Pick a typeface for the interface. Your choice is saved to your account and follows you on any device.</p>
        <div class="theme-swatches-grid" id="appearance-font-grid"></div>
      </div>
    </div>
  </div>

  <!-- ── Backup ── -->
  <div id="stab-backup" class="settings-tab-pane" style="display:none">
  <div style="display:grid;grid-template-columns:340px 1fr;gap:20px;align-items:start">

    <!-- Left: Actions -->
    <div style="display:flex;flex-direction:column;gap:16px">

      <!-- SQL Backup -->
      <div class="card">
        <div class="card-header"><span class="card-title">🗄️ SQL Backup</span></div>
        <div class="card-body">
          <p style="font-size:.84rem;color:var(--text2);margin-bottom:16px">Full database dump — restores everything including structure and data.</p>
          <button class="btn btn-primary" style="width:100%;justify-content:center" onclick="downloadSQL()">
            📥 Download .sql File
          </button>
        </div>
      </div>

      <!-- Excel Backup -->
      <div class="card">
        <div class="card-header"><span class="card-title">📊 CSV Export</span></div>
        <div class="card-body">
          <p style="font-size:.84rem;color:var(--text2);margin-bottom:16px">All data exported as a multi-sheet Excel file — products, vendors, transactions, P&amp;L.</p>
          <button class="btn btn-success" style="width:100%;justify-content:center" onclick="downloadExcel()">
            📥 Download CSV Export
          </button>
        </div>
      </div>

      <!-- Google Drive -->
      <div class="card" style="border-color:rgba(79,142,255,.3)">
        <div class="card-header"><span class="card-title">☁️ Google Drive Backup</span></div>
        <div class="card-body">
          <p style="font-size:.84rem;color:var(--text2);margin-bottom:6px">Backs up your full database as a <strong style="color:var(--text)">.sql file</strong> directly to an <strong style="color:var(--text)">Invyrr_db_backup</strong> folder in your Google Drive.</p>
          <div style="font-size:.76rem;color:var(--text3);margin-bottom:12px">Signs in with Google in a popup — no software needed. Folder is created automatically.</div>
          <div id="drive-auth-row" style="margin-bottom:10px;display:none">
            <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;background:rgba(66,133,244,.08);border:1px solid rgba(66,133,244,.2);border-radius:var(--radius-sm);font-size:.8rem">
              <span style="color:var(--green)">●</span>
              <span id="drive-signed-in-label" style="color:var(--text2)">Signed in</span>
              <button class="btn btn-ghost btn-xs" style="margin-left:auto" onclick="driveSignOut()">Sign out</button>
            </div>
          </div>
          <button class="btn btn-primary" style="width:100%;justify-content:center;background:linear-gradient(135deg,#4285f4,#34a853)" onclick="backupToDrive()" id="drive-backup-btn">
            ☁️ Backup to Google Drive
          </button>
          <div id="drive-status" style="display:none;margin-top:12px;padding:10px 12px;border-radius:var(--radius-sm);font-size:.82rem"></div>
        </div>
      </div>

      <!-- Restore -->
      <div class="card" style="border-color:rgba(239,68,68,.2)">
        <div class="card-header"><span class="card-title">♻️ Restore from SQL</span></div>
        <div class="card-body">
          <p style="font-size:.84rem;color:var(--text2);margin-bottom:12px">Upload a <code>.sql</code> backup file to restore. <strong style="color:var(--red)">This will overwrite all current data.</strong></p>
          <div id="restore-drop" style="border:2px dashed var(--border2);border-radius:var(--radius-sm);padding:20px;text-align:center;cursor:pointer;transition:border .2s"
               ondragover="event.preventDefault();this.style.borderColor='var(--red)'"
               ondragleave="this.style.borderColor='var(--border2)'"
               ondrop="handleRestoreDrop(event)"
               onclick="document.getElementById('restore-file').click()">
            <div style="font-size:1.5rem;margin-bottom:4px">📄</div>
            <div style="font-size:.82rem;color:var(--text2)">Drop .sql file here or click to browse</div>
          </div>
          <input type="file" id="restore-file" accept=".sql" style="display:none" onchange="restoreFromSQL(this.files[0])">
          <div id="restore-status" style="display:none;margin-top:10px;padding:10px 12px;border-radius:var(--radius-sm);font-size:.82rem"></div>
        </div>
      </div>

    </div>

    <!-- Right: Backup History -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">🕐 Backup History</span>
        <button class="btn btn-ghost btn-sm" onclick="loadBackupHistory()">↻ Refresh</button>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead><tr><th>Date &amp; Time</th><th>Type</th><th>File</th><th>Size</th><th>Created By</th><th>Actions</th></tr></thead>
          <tbody id="backup-history-body"></tbody>
        </table>
      </div>
      <div id="backup-history-empty" class="empty-state" style="display:none">
        <span class="empty-icon">☁️</span>
        <strong>No backups yet</strong>
        <p>Your backup history will appear here</p>
      </div>
    </div>

  </div>
  </div>

</div><!-- /page-settings -->

<!-- ══════════ EXPENSES ══════════ -->
<div class="page" id="page-website-orders">
  <div class="card" style="margin-top:120px">
    <div class="card-header">
      <span class="card-title">🛒 Customer Orders &amp; Payments</span>
      <span id="wo-total-label" style="font-size:.8rem;color:var(--text3)"></span>
    </div>
    <div class="card-body" style="padding-bottom:0">
      <div class="filter-bar">
        <input type="text" class="form-control" id="wo-search" placeholder="🔍 Order #, customer, mobile, city…" style="max-width:240px" oninput="loadWebsiteOrders()">
        <input type="date" class="date-input" id="wo-from" onchange="loadWebsiteOrders()">
        <input type="date" class="date-input" id="wo-to" onchange="loadWebsiteOrders()">
        <select class="filter-select" id="wo-filter-dispatch" onchange="loadWebsiteOrders()">
          <option value="">All Dispatch Status</option>
          <option value="Pending">Pending</option>
          <option value="Packed">Packed</option>
          <option value="Dispatched">Dispatched</option>
          <option value="Delivered">Delivered</option>
        </select>
        <a href="api/import.php?template=website_orders" class="btn btn-outline btn-sm" title="Download CSV template">📥 Template</a>
        <button class="btn btn-ghost btn-sm" onclick="switchImportToWebsiteOrders()" title="Import orders from CSV">📂 Import</button>
        <button class="btn btn-outline btn-sm" onclick="exportWebsiteOrders()" title="Export report as CSV">📊 Export</button>
        <button class="btn btn-primary btn-sm" onclick="openWebsiteOrderModal()" style="margin-left:auto">＋ New Order</button>
      </div>
      <div id="wo-status-capsules" style="display:flex;gap:8px;flex-wrap:wrap;padding:12px 14px 4px"></div>
    </div>
    <div class="tbl-wrap">
      <table>
        <thead><tr>
          <th>Order #</th><th>Date</th><th>Customer</th><th>City / Mobile</th>
          <th>Amount ₹</th><th>Paid ₹</th><th>Balance ₹</th><th>Status</th><th>Dispatch</th><th></th>
        </tr></thead>
        <tbody id="wo-body"></tbody>
      </table>
    </div>
    <div id="wo-empty" class="empty-state" style="display:none">
      <span class="empty-icon">🛒</span><strong>No orders yet</strong>
      <p>Add an order above, or import your existing order sheet</p>
    </div>
  </div>
</div>

<!-- New / Edit Website Order Modal -->
<div class="modal-backdrop" id="modal-website-order">
  <div class="modal" style="max-width:640px">
    <div class="modal-header">
      <span class="modal-title" id="wo-modal-title">🛒 New Order</span>
      <button class="modal-close" onclick="closeModal('modal-website-order')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="wo-edit-id">
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Order Number *</label>
          <input type="text" class="form-control" id="wo-order-number" placeholder="2025RR1130">
        </div>
        <div class="form-group"><label class="form-label">Order Date *</label>
          <input type="date" class="form-control" id="wo-order-date">
        </div>
      </div>
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Customer Name</label>
          <input type="text" class="form-control" id="wo-customer-name">
        </div>
        <div class="form-group"><label class="form-label">Mobile Number</label>
          <input type="text" class="form-control" id="wo-mobile">
        </div>
      </div>
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">City</label>
          <input type="text" class="form-control" id="wo-city">
        </div>
        <div class="form-group"><label class="form-label">Order Amount ₹ *</label>
          <input type="number" class="form-control" id="wo-amount" step="0.01" min="0">
        </div>
      </div>
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Order Status</label>
          <select class="form-control" id="wo-status">
            <option value="Pending">Pending</option>
            <option value="Partial">Partial</option>
            <option value="Paid">Paid</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Gift</label>
          <input type="text" class="form-control" id="wo-gift" placeholder="Optional">
        </div>
      </div>
      <div style="border-top:1px solid var(--border);margin:16px 0;padding-top:12px">
        <div style="font-size:.78rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Dispatch</div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Dispatch Status</label>
            <select class="form-control" id="wo-dispatch-status">
              <option value="">—</option>
              <option value="Pending">Pending</option>
              <option value="Packed">Packed</option>
              <option value="Dispatched">Dispatched</option>
              <option value="Delivered">Delivered</option>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Dispatch Date</label>
            <input type="date" class="form-control" id="wo-dispatch-date">
          </div>
        </div>
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Transport</label>
            <input type="text" class="form-control" id="wo-transport">
          </div>
          <div class="form-group"><label class="form-label"># of Boxes</label>
            <input type="number" class="form-control" id="wo-num-boxes" min="0">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:12px"><label class="form-label">Comments</label>
          <textarea class="form-control" id="wo-comments" rows="2"></textarea>
        </div>
      </div>
      <button class="btn btn-primary" onclick="saveWebsiteOrder()" style="width:100%;justify-content:center">💾 Save Order</button>
    </div>
  </div>
</div>

<!-- Record Payment / Ledger Modal -->
<div class="modal-backdrop" id="modal-wo-payments">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <span class="modal-title" id="wop-title">💰 Payments</span>
      <button class="modal-close" onclick="closeModal('modal-wo-payments')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="wop-order-id">
      <div id="wop-summary" style="display:flex;gap:16px;padding:10px 14px;background:var(--surface2);border-radius:var(--radius-sm);margin-bottom:14px;font-size:.82rem"></div>

      <div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center">
        <input type="number" class="form-control" id="wop-amount" placeholder="Amount ₹" step="0.01" min="0" style="max-width:110px">
        <label style="display:flex;align-items:center;gap:4px;font-size:.74rem;color:var(--text2);cursor:pointer;white-space:nowrap" title="Fill in the full balance due">
          <input type="checkbox" id="wop-full-tick" onchange="toggleWopFullAmount(this.checked)" style="width:15px;height:15px;accent-color:var(--green);cursor:pointer">
          Full amount
        </label>
        <input type="date" class="form-control" id="wop-date" style="max-width:150px">
        <select class="form-control" id="wop-payee" style="max-width:150px"></select>
        <select class="form-control" id="wop-mode" style="max-width:110px">
          <option value="account">Account</option>
          <option value="cash">Cash</option>
        </select>
        <input type="text" class="form-control" id="wop-note" placeholder="Note (optional)" style="max-width:150px">
        <button class="btn btn-primary btn-sm" onclick="recordCustomerPayment()">＋ Add</button>
      </div>

      <div class="tbl-wrap" style="max-height:260px">
        <table>
          <thead><tr><th>Date</th><th>Amount</th><th>Via</th><th>Mode</th><th>Note</th><th></th></tr></thead>
          <tbody id="wop-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="page" id="page-expenses">
  <div class="sticky-form-col">
    <!-- Form card (sticky left) -->
    <div class="card" style="margin-top:120px">
      <div class="card-header"><span class="card-title" id="exp-form-title">💸 Record Expense</span></div>
      <div class="card-body">
        <input type="hidden" id="exp-edit-id">
        <!-- Row 1: Date + Category -->
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Date *</label>
            <input type="date" class="form-control" id="exp-date">
          </div>
          <div class="form-group"><label class="form-label">Category *</label>
            <div style="display:flex;gap:6px">
              <select class="form-control" id="exp-category"></select>
              <button class="btn btn-ghost btn-sm" onclick="openExpenseCatModal()" title="Manage categories">⚙️</button>
            </div>
          </div>
        </div>
        <!-- Row 2: Amount + Paid Via -->
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Amount ₹ *</label>
            <input type="number" class="form-control" id="exp-amount" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)" oninput="updateAmountWords('exp-amount','exp-amount-words')">
            <div id="exp-amount-words" style="font-size:.7rem;color:var(--yellow);font-weight:700;font-style:italic;margin-top:4px"></div>
          </div>
          <div class="form-group"><label class="form-label">Paid Via <span style="color:var(--red)">*</span> <span style="color:var(--text3);font-weight:400;font-size:.7rem">(source of funds)</span></label>
            <select class="form-control" id="exp-payee"></select>
          </div>
        </div>
        <!-- Row 3: Paid To + Vendor -->
        <div class="form-grid" style="margin-bottom:12px">
          <div class="form-group"><label class="form-label">Paid To <span style="color:var(--text3);font-weight:400;font-size:.7rem">(optional — employee)</span></label>
            <select class="form-control" id="exp-paid-to"><option value="">— Same as Paid Via —</option></select>
          </div>
          <div class="form-group"><label class="form-label">Vendor <span style="color:var(--text3);font-weight:400;font-size:.7rem">(optional)</span></label>
            <select class="form-control" id="exp-vendor"></select>
          </div>
        </div>
        <!-- Row 3b: Business context — dropdown in edit mode, locked label in add mode -->
        <div id="exp-entity-context-row" class="form-grid" style="margin-bottom:12px;display:none">
          <div class="form-group">
            <label class="form-label">Business</label>
            <select class="form-control" id="exp-entity-select" style="display:none" onchange="document.getElementById('exp-entity').value=this.value"></select>
            <div style="padding:8px 12px;background:var(--surface2);border:1.5px solid var(--accent);border-radius:var(--radius-sm);font-size:.85rem;font-weight:600;color:var(--accent)" id="exp-entity-context-label"></div>
            <input type="hidden" id="exp-entity" value="">
          </div>
          <div></div>
        </div>
        <!-- Row 4: Notes + Ref No. -->
        <div class="form-grid" style="margin-bottom:16px">
          <div class="form-group"><label class="form-label">Notes</label>
            <input type="text" class="form-control" id="exp-notes" placeholder="Optional description">
          </div>
          <div class="form-group"><label class="form-label">Reference No.</label>
            <input type="text" class="form-control" id="exp-ref" placeholder="Bill / Receipt #">
          </div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn btn-primary" onclick="recordExpense()" id="exp-submit-btn" style="flex:1;justify-content:center">💸 Record Expense</button>
          <button class="btn btn-outline" onclick="cancelExpenseEdit()" id="exp-cancel-btn" style="display:none">✕ Cancel</button>
        </div>
      </div>
    </div>

    <!-- History card -->
    <div class="card" style="margin-top:120px">
      <div class="card-header">
        
        <span class="card-title">📋 Expense History</span>
        <span id="exp-total-label" style="font-size:.8rem;color:var(--yellow);font-weight:700"></span>
      </div>
      <div class="card-body" style="padding-bottom:0">
        <div class="filter-bar">
          <input type="date" class="date-input" id="exp-from" onchange="loadExpenses()">
          <input type="date" class="date-input" id="exp-to" onchange="loadExpenses()">
          <select class="filter-select" id="exp-filter-cat" onchange="loadExpenses()"><option value="">All Categories</option></select>
          <select class="filter-select" id="exp-filter-vendor" onchange="loadExpenses()"><option value="">All Vendors</option></select>
          <select class="filter-select" id="exp-filter-payee" onchange="loadExpenses()"><option value="">All Paid Via</option></select>
          <select class="filter-select" id="exp-filter-paid-to" onchange="loadExpenses()"><option value="">All Paid To</option></select>
          <a href="api/import.php?template=expenses" class="btn btn-outline btn-sm" title="Download CSV template">📥 Template</a>
          <button class="btn btn-ghost btn-sm" onclick="switchImportToExpenses()" title="Import expenses from CSV">📂 Import</button>
          <button class="btn btn-outline btn-sm" onclick="exportExpenses()">📊 Export</button>
          <button class="btn btn-ghost btn-sm" onclick="toggleExpColChooser()" title="Choose columns">⚙️ Columns</button>
        </div>
        <!-- Column chooser panel -->
        <div id="exp-col-chooser" style="display:none;padding:10px 14px;background:var(--surface2);border-bottom:1px solid var(--border);display:none">
          <div id="exp-col-toggle-list" style="display:flex;flex-wrap:wrap;gap:10px 20px"></div>
          <button class="btn btn-ghost btn-xs" onclick="resetExpColPrefs()" style="margin-top:8px;font-size:.72rem">↺ Reset to default</button>
        </div>
      </div>
      <div class="tbl-wrap">
        <table>
          <thead id="exp-thead"><tr>
            <th style="white-space:nowrap">Date</th><th>Category</th><th>Amount ₹</th>
            <th>Vendor</th><th>Paid Via</th><th>Paid To</th><th>Business</th><th>Ref No.</th><th>Notes</th><th>Audited</th><th></th>
          </tr></thead>
          <tbody id="exp-body"></tbody>
        </table>
      </div>
      <div id="exp-empty" class="empty-state" style="display:none">
        <span class="empty-icon">💸</span><strong>No expenses recorded</strong>
        <p>Record your first expense above</p>
      </div>
    </div>
  </div>
</div>

<!-- Expense Categories Modal -->
<div class="modal-backdrop" id="modal-expense-entities">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <span class="modal-title">⚙️ Manage Businesses</span>
      <button class="modal-close" onclick="closeModal('modal-expense-entities')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:.78rem;color:var(--text3);margin-bottom:12px">Track expenses separately for other businesses you run (e.g. SVT, RRA) — independent of your stock locations.</p>
      <div style="display:flex;gap:8px;margin-bottom:14px">
        <input type="text" class="form-control" id="new-expense-entity-input" placeholder="Business name (e.g. SVT)…" style="flex:1" onkeydown="if(event.key==='Enter')saveNewExpenseEntity()">
        <button class="btn btn-primary btn-sm" onclick="saveNewExpenseEntity()">＋ Add</button>
      </div>
      <div id="expense-entity-list" style="max-height:320px;overflow-y:auto"></div>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="modal-payee-types">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <span class="modal-title">⚙️ Payee Types</span>
      <button class="modal-close" onclick="closeModal('modal-payee-types')">✕</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:8px;margin-bottom:14px">
        <input type="text" class="form-control" id="new-payee-type-input" placeholder="New payee type…" style="flex:1" onkeydown="if(event.key==='Enter')saveNewPayeeType()">
        <button class="btn btn-primary btn-sm" onclick="saveNewPayeeType()">＋ Add</button>
      </div>
      <div id="payee-type-list" style="max-height:320px;overflow-y:auto"></div>
      <div style="text-align:right;margin-top:10px">
        <button type="button" onclick="restoreDefaultPayeeTypes()" style="background:none;border:none;color:var(--text3);font-size:.72rem;cursor:pointer">↺ Restore default types</button>
      </div>
    </div>
  </div>
</div>
<div class="modal-backdrop" id="modal-exp-cats">
  <div class="modal" style="max-width:460px">
    <div class="modal-header">
      <span class="modal-title">⚙️ Expense Categories</span>
      <button class="modal-close" onclick="closeModal('modal-exp-cats')">✕</button>
    </div>
    <div class="modal-body">
      <div style="display:flex;gap:8px;margin-bottom:14px">
        <input type="text" class="form-control" id="new-exp-cat-input" placeholder="New category name…" style="flex:1">
        <button class="btn btn-primary btn-sm" onclick="saveNewExpCat()">＋ Add</button>
      </div>
      <div id="exp-cat-list" style="max-height:320px;overflow-y:auto"></div>
    </div>
  </div>
</div>
<!-- ══════════ IMPORT ══════════ -->
<div class="page" id="page-import">
  <div class="sticky-form-col">
    <div class="card">
      <div class="card-header"><span class="card-title">📂 Import Settings</span></div>
      <div class="card-body">
        <div class="form-group" style="margin-bottom:14px">
          <label class="form-label">What are you importing?</label>
          <select class="form-control" id="import-type" onchange="onImportTypeChange()">
            <option value="products">📦 Products</option>
            <option value="vendors">🏭 Vendors</option>
            <option value="expenses">💸 Expenses</option>
            <option value="payees">👤 Payees</option>
            <option value="website_orders">🛒 Customer Orders</option>
            <option value="purchase_orders">📋 Purchase Orders</option>
            <option value="stock_in">📥 Stock In</option>
            <option value="stock_out">📤 Stock Out</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:14px" id="import-mode-group">
          <label class="form-label">If record already exists…</label>
          <select class="form-control" id="import-mode"><option value="insert">Skip existing</option><option value="upsert">Update existing</option></select>
        </div>
        <div class="form-group" style="margin-bottom:18px">
          <label class="form-label">File (.xlsx or .csv)</label>
          <div id="drop-zone" style="border:2px dashed var(--border2);border-radius:var(--radius);padding:24px 16px;text-align:center;cursor:pointer;transition:all .2s;background:var(--surface2)"
               ondragover="event.preventDefault();this.style.borderColor='var(--accent)'"
               ondragleave="this.style.borderColor='var(--border2)'"
               ondrop="handleDrop(event)" onclick="document.getElementById('import-file').click()">
            <div style="font-size:1.8rem;margin-bottom:6px">📄</div>
            <div style="font-weight:600;font-size:.88rem">Drop file here or click to browse</div>
            <div style="font-size:.72rem;color:var(--text3);margin-top:4px">.csv · .xlsx · max 10MB</div>
          </div>
          <input type="file" id="import-file" accept=".csv,.xlsx,.xls" style="display:none" onchange="onFileSelect(this)">
        </div>
        <button class="btn btn-primary" id="import-btn" style="width:100%;justify-content:center" onclick="runImport()">📂 Import File</button>
      </div>
    </div>
    <div style="display:flex;flex-direction:column;gap:18px">
      <!-- Export All -->
      <div class="card" style="border-color:rgba(34,197,94,.3)">
        <div class="card-header"><span class="card-title">📊 Export All Data</span></div>
        <div class="card-body">
          <p style="font-size:.82rem;color:var(--text2);margin-bottom:12px">Downloads a ZIP with all 15 sheets — Products, Vendors, Stock In/Out, Invoices, Expenses, Payees, Vendor Payments, Transfers, Adjustments, PnL, POs, Categories, Locations.</p>
          <a href="api/export.php?sheet=all" class="btn btn-success" style="width:100%;justify-content:center">📦 Download Full Export ZIP</a>
        </div>
      </div>
      <!-- Download Templates -->
      <div class="card">
        <div class="card-header"><span class="card-title">📋 Import Templates</span></div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
          <a href="api/import.php?template=products"        class="btn btn-outline btn-sm" style="justify-content:center">📦 Products</a>
          <a href="api/import.php?template=vendors"          class="btn btn-outline btn-sm" style="justify-content:center">🏭 Vendors</a>
          <a href="api/import.php?template=expenses"         class="btn btn-outline btn-sm" style="justify-content:center">💸 Expenses</a>
          <a href="api/import.php?template=payees"           class="btn btn-outline btn-sm" style="justify-content:center">👤 Payees</a>
          <a href="api/import.php?template=purchase_orders"  class="btn btn-outline btn-sm" style="justify-content:center">📋 Purchase Orders</a>
          <a href="api/import.php?template=stock_in"         class="btn btn-outline btn-sm" style="justify-content:center">📥 Stock In</a>
          <a href="api/import.php?template=stock_out"        class="btn btn-outline btn-sm" style="justify-content:center">📤 Stock Out</a>
        </div>
      </div>
      <div class="card" id="import-results-card" style="display:none">
        <div class="card-header"><span class="card-title">✅ Import Results</span><span id="import-result-badge" class="badge badge-green"></span></div>
        <div class="card-body">
          <div id="import-result-stats" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:12px"></div>
          <div id="import-errors-box" style="display:none">
            <div style="font-size:.75rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px">⚠️ Issues</div>
            <div id="import-errors-list" style="background:var(--surface2);border-radius:var(--radius-sm);padding:10px;max-height:180px;overflow-y:auto;font-size:.77rem;font-family:var(--mono);color:var(--red);line-height:1.7"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</div><!-- /content -->
</div><!-- /main -->

<!-- ══════════════ MODALS ══════════════ -->

<!-- Product Modal -->
<div class="modal-backdrop" id="modal-product">
  <div class="modal modal-lg">
    <div class="modal-header"><span class="modal-title" id="product-modal-title">Add Product</span><button class="modal-close" onclick="closeModal('modal-product')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="p-edit-id">
      <!-- Row 1: Category | SKU | Product Name | Brand -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Category</label>
          <div style="display:flex;gap:6px">
            <select class="form-control" id="p-category"></select>
            <button type="button" class="btn btn-ghost btn-sm" onclick="openCategoryModal(true)" title="Add new category" style="flex-shrink:0">＋</button>
          </div>
        </div>
        <div class="form-group"><label class="form-label">SKU</label><div style="position:relative"><input class="form-control" id="p-sku" placeholder="e.g. SPK-001" oninput="autoExtractItemCode(this.value);skuLiveCheck(this,'p-sku-feedback')" autocomplete="off" style="padding-right:28px"><div id="p-sku-ac" class="sku-ac-dropdown" style="display:none"></div></div><div id="p-sku-feedback" style="font-size:.72rem;margin-top:3px"></div></div>
        <div class="form-group"><label class="form-label">Product Name *</label><input class="form-control" id="p-name" placeholder="e.g. Sparklers 10cm"></div>
        <div class="form-group"><label class="form-label">Brand</label><input class="form-control" id="p-brand" list="brand-list" placeholder="e.g. Star Brand"><datalist id="brand-list"></datalist></div>
      </div>
      <!-- Row 2: Vendor | Case Content | Box Content | Unit -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Vendor</label><select class="form-control" id="p-vendor" onchange="autoCalcCostFromList('p-vendor','p-list-price','p-cost')"></select></div>
        <div class="form-group"><label class="form-label">Case Content <span style="font-size:.68rem;color:var(--text3)">(per carton)</span></label><input type="number" class="form-control" id="p-case-content" min="0" step="1" pattern="[0-9]*" placeholder="e.g. 12" oninput="this.value=this.value.replace(/[^0-9]/g,'');autoCalcLandingCost('p-cost','p-case-content','p-landing-cost','p-vendor')"></div>
        <div class="form-group"><label class="form-label">Box Content <span style="font-size:.68rem;color:var(--text3)">(per box)</span></label><input type="text" class="form-control" id="p-box-content" placeholder="e.g. 6 / 6x10"></div>
        <div class="form-group"><label class="form-label">Unit</label><input class="form-control" id="p-unit" value="Box" placeholder="Box, pcs, kg…"></div>
      </div>
      <!-- Row 3: Cost Price | Landing Cost | Sell Price | Wholesale Cost -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Cost Price ₹ *</label><input type="number" class="form-control" id="p-list-price" step="0.01" placeholder="List ₹ (vendor rate)" style="margin-bottom:4px;font-size:.76rem;padding:5px 8px" onfocus="clearIfZero(this)" oninput="autoCalcCostFromList('p-vendor','p-list-price','p-cost')"><input type="number" class="form-control" id="p-cost" step="0.01" placeholder="0.00" onfocus="clearIfZero(this)" oninput="autoCalcLandingCost('p-cost','p-case-content','p-landing-cost','p-vendor')"></div>
        <div class="form-group"><label class="form-label">Landing Cost ₹</label><input type="number" class="form-control" id="p-landing-cost" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)"></div>
        <div class="form-group"><label class="form-label">Sell Price ₹</label><input type="number" class="form-control" id="p-sell" step="0.01" placeholder="0.00" onfocus="clearIfZero(this)"></div>
        <div class="form-group"><label class="form-label">Wholesale Price ₹</label><input type="number" class="form-control" id="p-wholesale-price" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)"></div>
      </div>
      <!-- Row 4: Opening Stock | Min Stock | Combo | Product Image -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Opening Stock</label><input type="number" class="form-control" id="p-stock" min="0" placeholder="0"></div>
        <div class="form-group"><label class="form-label">Min Stock</label><input type="number" class="form-control" id="p-min-stock" min="0" placeholder="10"></div>
        <div class="form-group"><label class="form-label">Combo</label>
          <select class="form-control" id="p-combo">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Product Image</label>
          <input type="file" id="p-image-file" accept="image/*" class="form-control" style="padding:5px" onchange="previewProductImage(this)">
        </div>
      </div>
      <!-- Item Code (auto) + Description -->
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Item Code <span style="color:var(--text3);font-weight:400;font-size:.68rem">auto</span></label><input type="number" class="form-control" id="p-item-code" placeholder="—" readonly style="background:var(--surface3);color:var(--text2);cursor:default;opacity:.8"></div>
        <div class="form-group"><label class="form-label">Description</label><input class="form-control" id="p-desc" placeholder="Optional notes"></div>
      </div>
      <div id="p-image-preview" style="display:none;margin-bottom:12px"><img id="p-img-preview-el" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--border)"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-product')">Cancel</button>
      <button class="btn btn-primary" id="p-save-btn" onclick="saveProduct()">Save Product</button>
    </div>
  </div>
</div>

<!-- Vendor Modal -->

<!-- Category Modal -->
<div class="modal-backdrop" id="modal-category" style="z-index:1200">
  <div class="modal" style="max-width:440px">
    <div class="modal-header">
      <span class="modal-title" id="category-modal-title">Add Category</span>
      <button class="modal-close" onclick="closeModal('modal-category')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="catm-edit-id">
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group">
          <label class="form-label">Category Name *</label>
          <input class="form-control" id="catm-name" placeholder="e.g. Sparklers">
        </div>
        <div class="form-group">
          <label class="form-label">SKU Prefix <span style="font-size:.68rem;color:var(--text3)">(e.g. 11, 15)</span></label>
          <input class="form-control" id="catm-sku-prefix" placeholder="e.g. 11" maxlength="10">
        </div>
      </div>
      <div class="form-group" style="margin-bottom:12px">
        <label class="form-label">Description</label>
        <input class="form-control" id="catm-desc" placeholder="Optional description">
      </div>
      <div class="form-group">
        <label class="form-label">Color Label</label>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px" id="catm-color-swatches">
          <button type="button" onclick="selectCatColor('','catm')"     class="cat-swatch" data-color=""       style="background:var(--surface3);border:2px solid var(--border2);width:28px;height:28px;border-radius:50%;cursor:pointer" title="None"></button>
          <button type="button" onclick="selectCatColor('blue','catm')"   class="cat-swatch" data-color="blue"   style="background:#4f8eff;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
          <button type="button" onclick="selectCatColor('green','catm')"  class="cat-swatch" data-color="green"  style="background:#22c55e;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
          <button type="button" onclick="selectCatColor('orange','catm')" class="cat-swatch" data-color="orange" style="background:#f97316;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
          <button type="button" onclick="selectCatColor('red','catm')"    class="cat-swatch" data-color="red"    style="background:#ef4444;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
          <button type="button" onclick="selectCatColor('purple','catm')" class="cat-swatch" data-color="purple" style="background:#a855f7;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
          <button type="button" onclick="selectCatColor('yellow','catm')" class="cat-swatch" data-color="yellow" style="background:#eab308;border:2px solid transparent;width:28px;height:28px;border-radius:50%;cursor:pointer"></button>
        </div>
        <input type="hidden" id="catm-color">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-category')">Cancel</button>
      <button class="btn btn-primary" id="catm-save-btn" onclick="saveCategory()">Save Category</button>
    </div>
  </div>
</div>

<!-- Invoice Modal -->
<div class="modal-backdrop" id="modal-invoice">
  <div class="modal modal-xl">
    <div class="modal-header"><span class="modal-title" id="inv-modal-title">🧾 New Estimate</span><button class="modal-close" onclick="closeModal('modal-invoice')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="inv-edit-id">
      <div class="form-grid" style="margin-bottom:14px">
        <div class="form-group"><label class="form-label">Customer</label>
          <input class="form-control" id="inv-customer-search" placeholder="Type to search or enter name…" oninput="searchCustomerInline(this.value)" list="inv-customer-list">
          <datalist id="inv-customer-list"></datalist>
          <input type="hidden" id="inv-customer-id">
        </div>
        <div class="form-group"><label class="form-label">Location</label><select class="form-control" id="inv-location" onchange="renderInvoiceItems()"></select></div>
        <div class="form-group"><label class="form-label">Date</label><input type="date" class="form-control" id="inv-date"></div>
      </div>

      <div style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:.78rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.6px">Items</span>
        <button class="btn btn-ghost btn-sm" onclick="addInvoiceItem()">+ Add Item</button>
      </div>
      <table class="inv-items-table" style="margin-bottom:14px">
        <thead><tr><th style="width:40%">Product</th><th>Qty</th><th>Unit Price ₹</th><th>Total ₹</th><th></th></tr></thead>
        <tbody id="inv-items-body"></tbody>
      </table>

      <!-- Totals + editable charges -->
      <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
        <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px 18px;min-width:290px">
          <div style="display:grid;grid-template-columns:1fr auto;gap:6px 16px;align-items:center">
            <span style="color:var(--text2);font-size:.85rem">Subtotal</span>
            <span class="mono" id="inv-subtotal">₹0.00</span>

            <span style="color:var(--text2);font-size:.85rem">Discount ₹</span>
            <input type="number" id="inv-discount" step="0.01" onfocus="clearIfZero(this)" min="0" placeholder="0.00"
              style="background:var(--surface3);border:1px solid var(--border);color:var(--text);padding:4px 8px;border-radius:6px;width:110px;font-family:var(--mono);text-align:right"
              oninput="recalcInvoice()">

            <span style="color:var(--text2);font-size:.85rem">Packing ₹</span>
            <input type="number" id="inv-packing" step="0.01" onfocus="clearIfZero(this)" min="0" placeholder="0.00"
              style="background:var(--surface3);border:1px solid var(--border);color:var(--text);padding:4px 8px;border-radius:6px;width:110px;font-family:var(--mono);text-align:right"
              oninput="recalcInvoice()">

            <span style="color:var(--text2);font-size:.85rem">Misc. Charges ₹</span>
            <input type="number" id="inv-misc" step="0.01" onfocus="clearIfZero(this)" min="0" placeholder="0.00"
              style="background:var(--surface3);border:1px solid var(--border);color:var(--text);padding:4px 8px;border-radius:6px;width:110px;font-family:var(--mono);text-align:right"
              oninput="recalcInvoice()">

            <span style="font-weight:700;font-size:1rem;border-top:1px solid var(--border);padding-top:8px;margin-top:2px">TOTAL</span>
            <span class="mono" style="font-weight:800;font-size:1.1rem;color:var(--green);border-top:1px solid var(--border);padding-top:8px;margin-top:2px" id="inv-total">₹0.00</span>
            <span></span>
            <span id="inv-total-words" style="font-size:.7rem;color:var(--text3);font-style:italic;text-align:right"></span>
          </div>
        </div>
      </div>

      <!-- Payment section at bottom -->
      <div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:12px">
        <div style="font-size:.72rem;color:var(--text3);text-transform:uppercase;letter-spacing:.6px;font-weight:600;margin-bottom:10px">Payment Details</div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select class="form-control" id="inv-payment" onchange="onPaymentMethodChange()">
              <option value="">— Select —</option>
              <option value="cash">Cash</option>
              <option value="upi">UPI</option>
              <option value="card">Card</option>
              <option value="credit">Credit</option>
              <option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="form-group" id="inv-upi-group" style="display:none">
            <label class="form-label">UPI / Account</label>
            <select class="form-control" id="inv-upi-payee"></select>
          </div>
          <div class="form-group">
            <label class="form-label">Amount Received ₹</label>
            <input type="number" class="form-control" id="inv-amount-received" step="0.01" onfocus="clearIfZero(this)" min="0" placeholder="0.00" oninput="recalcInvoice()">
          </div>
          <div class="form-group" id="inv-balance-group">
            <label class="form-label">Balance ₹</label>
            <div id="inv-balance-display" class="form-control" style="background:var(--surface3);font-family:var(--mono);font-weight:700;color:var(--red)">—</div>
          </div>
        </div>
      </div>

      <input type="hidden" id="inv-tax" value="0">
      <div class="form-group"><label class="form-label">Notes</label><input class="form-control" id="inv-notes" placeholder="Optional notes on estimate"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-invoice')">Cancel</button>
      <button class="btn btn-primary" id="inv-save-btn" onclick="saveInvoice()">💾 Save Estimate</button>
    </div>
  </div>
</div>

<!-- Purchase Order Modal -->
<div class="modal-backdrop" id="modal-po">
  <div class="modal modal-lg">
    <div class="modal-header"><span class="modal-title" id="po-modal-title">📋 New Purchase Order</span><button class="modal-close" onclick="closeModal('modal-po')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="po-edit-id">
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Vendor *</label><select class="form-control" id="po-vendor" onchange="recalcAllPOItemCosts()"></select></div>
        <div class="form-group"><label class="form-label">Deliver To Location</label><select class="form-control" id="po-location"></select></div>
      </div>
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Expected Date</label><input type="date" class="form-control" id="po-expected"></div>
        <div class="form-group"><label class="form-label">Status</label>
          <select class="form-control" id="po-status"><option value="draft">Draft</option><option value="sent">Sent to Vendor</option><option value="partial">Partially Received</option><option value="received">Fully Received</option><option value="cancelled">Cancelled</option></select>
        </div>
      </div>
      <div style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:.78rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.6px">Items</span>
        <div style="display:flex;gap:6px">
          <button class="btn btn-primary btn-sm" onclick="openQuickAddProduct(null,'po')" style="background:rgba(79,142,255,.15);color:var(--accent);border:1px solid rgba(79,142,255,.3)">＋ Quick Add Product</button>
          <button class="btn btn-ghost btn-sm" onclick="addPOItem()">+ Add Item</button>
        </div>
      </div>
      <table class="inv-items-table" style="margin-bottom:6px">
        <thead><tr><th style="width:40%;min-width:220px">Product</th><th style="width:60px">Qty (Cases)</th><?php if($user['role']!=='manager'): ?><th style="width:75px">List Rate ₹</th><th style="width:75px">Cost Price ₹</th><?php endif; ?><th style="width:60px">Case Content</th><th style="width:65px">Qty Ordered</th><th style="width:65px">Qty Received</th><?php if($user['role']!=='manager'): ?><th style="width:75px">Line Total ₹</th><?php endif; ?><th style="width:90px"></th></tr></thead>
        <tbody id="po-items-body"></tbody>
      </table>
      <div style="text-align:right;font-size:.8rem;color:var(--text2);margin-bottom:12px">Total Cases: <b id="po-total-cases" style="font-family:var(--mono)">0</b></div>
      <div class="form-group"><label class="form-label">Notes</label><textarea class="form-control" id="po-notes" rows="2" placeholder="Special instructions, terms…"></textarea></div>
      <?php if($user['role']!=='manager'): ?>
      <div class="form-grid" style="margin-top:10px">
        <div class="form-group">
          <label class="form-label">Misc. Charges ₹ <span style="font-size:.68rem;color:var(--text3)">(transport, loading, etc.)</span></label>
          <input type="number" class="form-control" id="po-misc" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)" oninput="updatePOTotal()">
        </div>
        <div class="form-group" style="justify-content:flex-end">
          <label class="form-label">Order Total ₹</label>
          <div id="po-total-display" style="font-size:1.2rem;font-weight:800;font-family:var(--mono);color:var(--green);padding:8px 0">₹0.00</div>
          <div id="po-total-words" style="font-size:.7rem;color:var(--text3);font-style:italic;text-align:right"></div>
        </div>
      </div>
      <?php else: ?>
      <input type="hidden" id="po-misc" value="0">
      <?php endif; ?>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-po')">Cancel</button>
      <button class="btn btn-ghost" onclick="refreshPOPrices()" title="Update all line item costs from current product prices">🔄 Refresh Prices</button>
      <button class="btn btn-success" id="po-receive-btn" style="display:none" onclick="receivePO()">📥 Receive & Create Stock-In</button>
      <button class="btn btn-primary" id="po-save-btn" onclick="savePO()">Save PO</button>
    </div>
  </div>
</div>

<!-- PO Receive Items Modal (partial receiving) -->
<div class="modal-backdrop" id="modal-po-receive">
  <div class="modal modal-lg">
    <div class="modal-header"><span class="modal-title" id="po-receive-title">📥 Receive Items</span><button class="modal-close" onclick="closeModal('modal-po-receive')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="po-receive-id">
      <p style="font-size:.8rem;color:var(--text3);margin:0 0 10px">Enter the quantity actually received for each item. You can receive fewer than ordered — stock and PO status update to reflect only what's received.</p>
      <table class="inv-items-table" style="margin-bottom:6px">
        <thead><tr><th style="width:40%;min-width:180px">Product</th><th style="width:70px">Ordered</th><th style="width:80px">Already Recv.</th><th style="width:70px">Pending</th><th style="width:100px">Receive Now</th></tr></thead>
        <tbody id="po-receive-body"></tbody>
      </table>
      <div id="po-receive-empty" style="display:none;padding:14px 0;text-align:center;color:var(--text3);font-size:.85rem">All items already fully received.</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-po-receive')">Cancel</button>
      <button class="btn btn-ghost" onclick="fillPOReceiveAllPending()">Fill All Pending</button>
      <button class="btn btn-success" id="po-receive-confirm-btn" onclick="confirmReceivePOItems()">📥 Receive & Update Stock</button>
    </div>
  </div>
</div>

<!-- Barcode Scanner Modal -->
<div class="modal-backdrop" id="modal-barcode">
  <div class="modal" style="max-width:440px">
    <div class="modal-header"><span class="modal-title">📷 Barcode / QR Scanner</span><button class="modal-close" onclick="closeBarcodeModal()">✕</button></div>
    <div class="modal-body">
      <div id="scanner-container"><video id="scanner-video" playsinline></video></div>
      <div style="margin-top:14px">
        <label class="form-label" style="margin-bottom:6px;display:block">Or enter barcode manually</label>
        <div style="display:flex;gap:8px">
          <input class="form-control" id="barcode-manual" placeholder="Scan or type barcode…" onkeydown="if(event.key==='Enter')applyBarcode()">
          <button class="btn btn-primary" onclick="applyBarcode()">Apply</button>
        </div>
      </div>
      <div id="barcode-result" style="margin-top:12px;font-size:.85rem;color:var(--text3)"></div>
    </div>
  </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal-backdrop" id="modal-bulk">
  <div class="modal" style="max-width:380px">
    <div class="modal-header"><span class="modal-title" id="bulk-modal-title">Bulk Action</span><button class="modal-close" onclick="closeModal('modal-bulk')">✕</button></div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label" id="bulk-modal-label">New Value</label>
        <input class="form-control" id="bulk-modal-value" placeholder="Enter value…">
        <select class="form-control" id="bulk-modal-vendor" style="display:none">
          <option value="">— Select Vendor —</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-bulk')">Cancel</button>
      <button class="btn btn-primary" onclick="executeBulkAction()">Apply to Selected</button>
    </div>
  </div>
</div>

<!-- Keyboard Shortcuts Help Modal -->
<!-- Quick Add Product Modal -->
<div class="modal-backdrop" id="modal-quick-product" style="z-index:1100">
  <div class="modal" style="max-width:620px">
    <div class="modal-header">
      <span class="modal-title">⚡ Quick Add Product</span>
      <button class="modal-close" onclick="closeModal('modal-quick-product')">✕</button>
    </div>
    <div class="modal-body" style="max-height:75vh;overflow-y:auto">
      <input type="hidden" id="qp-target-select">
      <!-- Row 1: Category | SKU | Product Name | Brand (matches Products page field order) -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Category</label>
          <div style="display:flex;gap:6px">
            <select class="form-control" id="qp-category"></select>
            <button type="button" class="btn btn-ghost btn-sm" onclick="openCategoryModal(true)" title="Add new category" style="flex-shrink:0">＋</button>
          </div>
        </div>
        <div class="form-group"><label class="form-label">SKU</label><div style="position:relative"><input class="form-control" id="qp-sku" placeholder="e.g. SPK-001" oninput="qpAutoItemCode(this.value);skuLiveCheck(this,'qp-sku-feedback')" autocomplete="off"><div id="qp-sku-ac" class="sku-ac-dropdown" style="display:none"></div></div><div id="qp-sku-feedback" style="font-size:.72rem;margin-top:3px"></div></div>
        <div class="form-group"><label class="form-label">Product Name *</label><input class="form-control" id="qp-name" placeholder="e.g. Sparklers 10cm"></div>
        <div class="form-group"><label class="form-label">Brand</label><input class="form-control" id="qp-brand" list="brand-list" placeholder="e.g. Star Brand"></div>
      </div>
      <!-- Row 2: Vendor | Case Content | Box Content | Unit -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Vendor</label><select class="form-control" id="qp-vendor" onchange="autoCalcCostFromList('qp-vendor','qp-list-price','qp-cost')"></select></div>
        <div class="form-group"><label class="form-label">Case Content <span style="font-size:.68rem;color:var(--text3)">(per carton)</span></label><input type="number" class="form-control" id="qp-case-content" min="0" step="1" pattern="[0-9]*" placeholder="e.g. 12" oninput="this.value=this.value.replace(/[^0-9]/g,'');autoCalcLandingCost('qp-cost','qp-case-content','qp-landing-cost','qp-vendor')"></div>
        <div class="form-group"><label class="form-label">Box Content <span style="font-size:.68rem;color:var(--text3)">(per box)</span></label><input type="text" class="form-control" id="qp-box-content" placeholder="e.g. 6 / 6x10"></div>
        <div class="form-group"><label class="form-label">Unit</label><input class="form-control" id="qp-unit" placeholder="Box, pcs, kg…"></div>
      </div>
      <!-- Row 3: Cost + Landing Cost + Sell + Wholesale -->
      <div class="form-grid-4" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Cost Price ₹ *</label><input type="number" class="form-control" id="qp-list-price" step="0.01" placeholder="List ₹ (vendor rate)" style="margin-bottom:4px;font-size:.76rem;padding:5px 8px" onfocus="clearIfZero(this)" oninput="autoCalcCostFromList('qp-vendor','qp-list-price','qp-cost')"><input type="number" class="form-control" id="qp-cost" step="0.01" placeholder="0.00" onfocus="clearIfZero(this)" oninput="autoCalcLandingCost('qp-cost','qp-case-content','qp-landing-cost','qp-vendor')"></div>
        <div class="form-group"><label class="form-label">Landing Cost ₹</label><input type="number" class="form-control" id="qp-landing-cost" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)"></div>
        <div class="form-group"><label class="form-label">Sell Price ₹ <span style="font-size:.68rem;color:var(--text3)">(optional)</span></label><input type="number" class="form-control" id="qp-sell" step="0.01" placeholder="0.00" onfocus="clearIfZero(this)"></div>
        <div class="form-group"><label class="form-label">Wholesale Price ₹</label><input type="number" class="form-control" id="qp-wholesale-price" step="0.01" min="0" placeholder="0.00" onfocus="clearIfZero(this)"></div>
      </div>
      <!-- Row 4: Opening Stock + Min Stock + Combo -->
      <div class="form-grid-3" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Opening Stock</label><input type="number" class="form-control" id="qp-stock" min="0" placeholder="0"></div>
        <div class="form-group"><label class="form-label">Min Stock</label><input type="number" class="form-control" id="qp-min-stock" min="0" placeholder="0"></div>
        <div class="form-group"><label class="form-label">Combo</label>
          <select class="form-control" id="qp-combo">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
      </div>
      <!-- Row 5: Item Code (auto) + Description -->
      <div class="form-grid" style="margin-bottom:12px">
        <div class="form-group"><label class="form-label">Item Code <span style="color:var(--text3);font-weight:400;font-size:.68rem">auto</span></label><input type="number" class="form-control" id="qp-item-code" placeholder="—" readonly style="background:var(--surface3);color:var(--text2);cursor:default;opacity:.8"></div>
        <div class="form-group"><label class="form-label">Description</label><input class="form-control" id="qp-desc" placeholder="Optional notes"></div>
      </div>
      <div style="font-size:.75rem;color:var(--text3);margin-top:4px">💡 Opening stock is set directly here or record via Stock In after saving.</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-quick-product')">Cancel</button>
      <button class="btn btn-primary" id="qp-save-btn" onclick="saveQuickProduct()">💾 Save &amp; Select</button>
    </div>
  </div>
</div>

<!-- Catalog Duplicates Modal (Vendors / Categories) -->
<div class="modal-backdrop" id="modal-catalog-duplicates">
  <div class="modal" style="max-width:680px">
    <div class="modal-header">
      <div>
        <span class="modal-title" id="catdup-title">🔍 Duplicate Vendors</span>
        <div style="font-size:.75rem;color:var(--text3);margin-top:3px" id="catdup-sub">Items with similar or identical names</div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-catalog-duplicates')">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <div style="padding:10px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <span id="catdup-count" style="font-size:.82rem;color:var(--text3)"></span>
      </div>
      <div id="catdup-body" style="max-height:480px;overflow-y:auto;padding:16px 20px">
        <div style="text-align:center;padding:40px;color:var(--text3)"><span class="spinner"></span> Scanning…</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-catalog-duplicates')">Close</button>
    </div>
  </div>
</div>

<!-- Duplicates Modal -->
<div class="modal-backdrop" id="modal-duplicates">
  <div class="modal" style="max-width:760px">
    <div class="modal-header">
      <div>
        <span class="modal-title">🔍 Duplicate Products</span>
        <div style="font-size:.75rem;color:var(--text3);margin-top:3px">Products sharing the same SKU — rows are highlighted <span style="color:var(--orange);font-weight:600">orange</span> in the table</div>
      </div>
      <button class="modal-close" onclick="closeModal('modal-duplicates')">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <div style="display:flex;gap:6px;padding:14px 20px;border-bottom:1px solid var(--border);flex-wrap:wrap;align-items:center">
        <button class="btn btn-ghost btn-sm" id="dup-tab-name" onclick="showDupTab('name')" style="border-radius:20px">By Name</button>
        <button class="btn btn-sm" id="dup-tab-sku" onclick="showDupTab('sku')" style="background:var(--accent);color:#fff;border-radius:20px">By SKU</button>
        <button class="btn btn-ghost btn-sm" id="dup-tab-all" onclick="showDupTab('all')" style="border-radius:20px">All</button>
        <span id="dup-count" style="margin-left:auto;font-size:.8rem;color:var(--text3)"></span>
      </div>
      <div id="dup-body" style="max-height:480px;overflow-y:auto;padding:16px 20px">
        <div style="text-align:center;padding:40px;color:var(--text3)"><span class="spinner"></span> Scanning…</div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('modal-duplicates')">Close</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-shortcuts">
  <div class="modal" style="max-width:360px">
    <div class="modal-header"><span class="modal-title">⌨️ Keyboard Shortcuts</span><button class="modal-close" onclick="closeModal('modal-shortcuts')">✕</button></div>
    <div class="modal-body" style="display:grid;grid-template-columns:auto 1fr;gap:8px 20px;align-items:center;font-size:.85rem">
      <kbd class="kbd">D</kbd><span>Dashboard</span>
      <kbd class="kbd">N</kbd><span>New Product</span>
      <kbd class="kbd">I</kbd><span>New Invoice</span>
      <kbd class="kbd">S</kbd><span>Go to Stock In</span>
      <kbd class="kbd">R</kbd><span>Reports</span>
      <kbd class="kbd">A</kbd><span>Low Stock Alerts</span>
      <kbd class="kbd">Esc</kbd><span>Close modal</span>
      <kbd class="kbd">?</kbd><span>Show this help</span>
    </div>
  </div>
</div>

<div class="toast-container" id="toast-container"></div>

<script>
// ══════════════════════════════════════════════════════════
// CONSTANTS & HELPERS
// ══════════════════════════════════════════════════════════
const API = {
  auth:'api/auth.php', products:'api/products.php', vendors:'api/vendors.php',
  customers:'api/customers.php', locations:'api/locations.php',
  stockIn:'api/stock_in.php', stockOut:'api/stock_out.php',
  invoices:'api/invoices.php', purchaseOrders:'api/purchase_orders.php',
  transfers:'api/transfers.php', adjustments:'api/adjustments.php',
  dashboard:'api/dashboard.php', settings:'api/settings.php',
  users:'api/users.php', audit:'api/audit_log.php', export:'api/export.php', import:'api/import.php', categories:'api/categories.php',
  vendorPayments:'api/vendor_payments.php', payees:'api/payees.php', expenses:'api/expenses.php', productDetail:'api/product_detail.php', payeeLedger:'api/payee_ledger.php', expenseEntities:'api/expense_entities.php', combos:'api/combos.php', pickingSessions:'api/picking_sessions.php', websiteOrders:'api/website_orders.php', customerPayments:'api/customer_payments.php',
};
const CUR = { sym:'₹' }; // updated from settings
const ROLE = "<?= $user['role'] ?>";

// Hide nav items restricted from manager role
(function(){
  const MANAGER_HIDDEN = ['vendor-payments','import','on-order-report','payees','vendors','settings','vp-report'];
  if(ROLE === 'manager'){
    MANAGER_HIDDEN.forEach(function(page){
      var btn = document.querySelector('.nav-item[data-page="'+page+'"]');
      if(btn) btn.style.display='none';
    });
  }
  // Partner: same as admin but delete buttons hidden
  if(ROLE !== 'admin' && ROLE !== 'partner'){
    // Picker/manager: hide delete buttons handled per-page
  }
})();
window._GOOGLE_CLIENT_ID = '';
const CURRENT_USER = <?= json_encode($user['name'] ?? 'Unknown') ?>;
// Picker sees the same cost/landing-cost/stock-value masking as manager,
// plus Vendor names and Margin % (see the dashboard's Top Products table
// and the vendor-count stat, both gated on this below) -- Picker's job is
// fulfillment, not pricing/sourcing, and shouldn't see vendor economics.
const HIDE_COST = (ROLE === 'manager' || ROLE === 'Picker');
const HIDE_STOCK_VALUE = (ROLE === 'manager' || ROLE === 'Picker');
const HIDE_VENDOR_INFO = (ROLE === 'Picker');
const CAN_DELETE = (ROLE === 'admin' || ROLE === 'partner'); // delete is admin/partner only -- see canDelete() in includes/db.php for the server-side half
const CAN_VERIFY = ['admin','manager','partner'].includes(ROLE); // Order Picking: who can verify a picked/packed order
// Pages Picker must not reach at all -- mirrors the PHP-side nav-item
// gating above (Inventory/Purchases/Accounting/System sections, plus
// Vendors/Customer Orders/Reports/Procurement individually), but enforced
// here too so hitting a URL hash or console-calling showPage() directly
// can't route around the hidden nav buttons. Picker's whole job is
// Picking: pick an already-paid order, hand it to verification, then
// (once someone else has verified it) assist with Packing/Dispatch -- see
// setPickStatus() and openDispatchModal() for the stage-level half of
// that restriction.
const PICKER_BLOCKED_PAGES = ['products','categories','combos',
  'stock-in','purchase-orders','transfers','adjustments',
  'vendor-payments','expenses','payees',
  'settings','audit','import',
  'website-orders','reports','on-order-report','vendors'];
function hideCost(val){ return HIDE_COST ? '<span style="color:var(--text3);font-size:.8rem">—</span>' : val; }
function fmtCost(val){ return HIDE_COST ? '—' : (CUR.sym+fmtN(val)); }

async function http(url,method='GET',body=null){
  const opts={method,headers:{'Content-Type':'application/json'},credentials:'same-origin'};
  if(body) opts.body=JSON.stringify(body);
  const res=await fetch(url,opts);
  if(res.status===401){location.reload();return;}
  const raw=await res.text();
  let j;
  try{ j=JSON.parse(raw); }
  catch(e){
    // PHP returned HTML/error — extract the message and log raw response
    console.error('API non-JSON response from '+url+':', raw);
    const preview=raw.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim().slice(0,200);
    throw new Error('Server error: '+preview);
  }
  if(!j.success) throw new Error(j.message||'API error');
  return j;
}
const api={
  get:(url)=>http(url),
  post:(url,b)=>http(url,'POST',b),
  put:(url,b)=>http(url,'PUT',b),
  delete:(url)=>http(url,'DELETE'),
};

function toast(msg,type='success'){
  const el=document.createElement('div');
  el.className='toast '+type;
  const icon = type==='error'?'❌':type==='success'?'✅':'⚠️';
  const safeMsg = typeof msg === 'string' ? msg : String(msg);
  // Strip any HTML tags from the message for display (show plain text only)
  const plainMsg = safeMsg.replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim();
  if(type==='error'){
    el.style.cssText='max-width:520px;cursor:default';
    const msgSpan=document.createElement('span');
    msgSpan.style.cssText='flex:1;word-break:break-word;font-size:.8rem';
    msgSpan.textContent=plainMsg;
    const copyBtn=document.createElement('button');
    copyBtn.textContent='Copy';
    copyBtn.style.cssText='margin-left:8px;padding:2px 8px;font-size:.7rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);border-radius:4px;color:#fff;cursor:pointer;white-space:nowrap;flex-shrink:0';
    copyBtn.onclick=function(){ navigator.clipboard.writeText(plainMsg).then(function(){ copyBtn.textContent='✅ Copied'; }); };
    const closeBtn=document.createElement('button');
    closeBtn.textContent='✕';
    closeBtn.style.cssText='margin-left:6px;font-size:.9rem;background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;padding:0 4px;flex-shrink:0';
    closeBtn.onclick=function(){ el.remove(); };
    const iconSpan=document.createElement('span');
    iconSpan.textContent=icon;
    el.appendChild(iconSpan);
    el.appendChild(msgSpan);
    el.appendChild(copyBtn);
    el.appendChild(closeBtn);
  } else {
    const iconSpan=document.createElement('span');
    iconSpan.textContent=icon;
    const msgSpan=document.createElement('span');
    msgSpan.textContent=plainMsg;
    el.appendChild(iconSpan);
    el.appendChild(msgSpan);
    el.style.cursor='pointer'; el.title='Click to dismiss';
    el.addEventListener('click',function(){ el.style.animation='toastOut .3s ease forwards'; setTimeout(()=>el.remove(),300); });
    const dur = 4000;
    setTimeout(()=>{if(el.parentNode){el.style.animation='toastOut .3s ease forwards';setTimeout(()=>el.remove(),300);}},dur);
  }
  document.getElementById('toast-container').appendChild(el);
}
function openModal(id){document.getElementById(id)?.classList.add('open');}
function closeModal(id){document.getElementById(id)?.classList.remove('open');clearAllSearchableSelects();}
document.querySelectorAll('.modal-backdrop').forEach(b=>b.addEventListener('click',e=>{if(e.target===b)b.classList.remove('open');}));

const fmt=(n)=>Number(n).toLocaleString('en-IN',{maximumFractionDigits:0});
const fmtN=(n)=>String(Math.round(Number(n)||0));
// ── Amount in words (Indian numbering: Crore/Lakh/Thousand) ────────────────
// Used as live helper text under money total fields (Expense amount, Vendor
// Payment amount, PO Order Total, Estimate Total) so staff can sanity-check
// what they typed/see without doing the digit-grouping math themselves.
function numberToWordsIndian(num){
  num=Math.round(Math.abs(+num||0));
  if(num===0) return 'Zero';
  const ones=['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten',
    'Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
  const tens=['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
  function twoDigits(n){
    if(n<20) return ones[n];
    return tens[Math.floor(n/10)]+(n%10?' '+ones[n%10]:'');
  }
  function threeDigits(n){
    let s='';
    if(n>=100){ s+=ones[Math.floor(n/100)]+' Hundred'; n=n%100; if(n) s+=' '; }
    if(n>0) s+=twoDigits(n);
    return s;
  }
  const crore=Math.floor(num/10000000); num%=10000000;
  const lakh=Math.floor(num/100000); num%=100000;
  const thousand=Math.floor(num/1000); num%=1000;
  const hundred=num;
  const parts=[];
  if(crore) parts.push(threeDigits(crore)+' Crore');
  if(lakh) parts.push(threeDigits(lakh)+' Lakh');
  if(thousand) parts.push(threeDigits(thousand)+' Thousand');
  if(hundred) parts.push(threeDigits(hundred));
  return parts.join(' ');
}
function amountInWords(amount){
  const n=Math.abs(+amount||0);
  if(!n) return '';
  const rupees=Math.floor(n);
  const paise=Math.round((n-rupees)*100);
  let words='Rupees '+numberToWordsIndian(rupees);
  if(paise>0) words+=' and '+numberToWordsIndian(paise)+' Paise';
  return words+' Only';
}
// For live input fields (Expense amount, Vendor Payment amount)
function updateAmountWords(inputId,outputId){
  const inp=document.getElementById(inputId);
  const out=document.getElementById(outputId);
  if(!inp||!out)return;
  const v=parseFloat(inp.value)||0;
  out.textContent=v>0?amountInWords(v):'';
}
// For computed total displays (PO Order Total, Estimate Total)
function setAmountWordsDisplay(outputId,amount){
  const out=document.getElementById(outputId);
  if(!out)return;
  const v=+amount||0;
  out.textContent=v>0?amountInWords(v):'';
}
const esc=(s)=>String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const today=()=>new Date().toISOString().split('T')[0];
function setElText(id,val){const el=document.getElementById(id);if(el)el.textContent=val;}
const MONTHS_SHORT=['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
// Format YYYY-MM-DD → 27-JUL-26
function fmtExpDate(d){
  if(!d) return '—';
  const p=String(d).split('-');
  if(p.length<3) return d;
  return p[2].padStart(2,'0')+'-'+(MONTHS_SHORT[parseInt(p[1],10)-1]||p[1])+'-'+p[0].slice(-2);
}

// ══════════════════════════════════════════════════════════
// SIDEBAR COLLAPSE (desktop) + MOBILE TOGGLE
// ══════════════════════════════════════════════════════════
function toggleCollapse(){
  const sb=document.getElementById('sidebar');
  const collapsed=sb.classList.toggle('collapsed');
  localStorage.setItem('sm_sidebar_collapsed', collapsed?'1':'0');
  // update main margin via JS as well (CSS sibling selector covers it, but keep in sync)
  document.querySelector('.main').style.marginLeft = collapsed ? '54px' : 'var(--sidebar-w)';
}
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.remove('open');
}
// Restore collapsed state on load
(function(){
  if(localStorage.getItem('sm_sidebar_collapsed')==='1'){
    document.getElementById('sidebar').classList.add('collapsed');
    document.querySelector('.main').style.marginLeft='54px';
  }
})();

// ══════════════════════════════════════════════════════════
// NAVIGATION
// ══════════════════════════════════════════════════════════
const pageTitles={
  dashboard:'Dashboard',products:'Products',vendors:'Vendors',customers:'Customers',
  invoices:'Estimates / Sales','website-orders':'Customer Orders','stock-in':'Stock In','purchase-orders':'Purchase Orders',
  transfers:'Stock Transfers',adjustments:'Stock Adjustments',
  picking:'Fulfillment',
  expenses:'Expenses',payees:'Payees',categories:'Categories',
  'vendor-payments':'Vendor Payments',
  reports:'Reports & Analytics',alerts:'Low Stock Alerts','on-order-report':'Procurement Dashboard',combos:'Combo Builder','paid-to-report':'Paid To Report','vp-report':'Vendor Payments Report',
  locations:'Store Locations',users:'User Management',audit:'Audit Log',
  settings:'Settings',import:'Import Data',
};
function showPage(id){
  // Hard block, independent of the nav buttons being hidden -- covers a
  // typed URL hash, a restored sessionStorage page from a previous
  // session, or calling showPage() directly from the console.
  if(ROLE==='Picker' && PICKER_BLOCKED_PAGES.includes(id)){
    toast('You do not have access to that page','error');
    if(id!=='dashboard') showPage('dashboard');
    return;
  }
  // Leaving Picking for any other page must stop its 30s background
  // auto-refresh -- otherwise it keeps polling behind the scenes and can
  // land a stale response after we've already navigated back and re-fetched,
  // clobbering the list. See _pickFetchToken below for the other half of this fix.
  if(id!=='picking') clearInterval(window._pickRefreshTimer);
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
  document.getElementById('page-'+id)?.classList.add('active');
  document.querySelector(`.nav-item[data-page="${id}"]`)?.classList.add('active');
  document.getElementById('page-title').textContent=pageTitles[id]||id;
  // Save current page in URL hash for refresh persistence
  history.replaceState(null,'','#'+id);
  sessionStorage.setItem('invyrr_page', id);
  document.getElementById('settings-subnav').style.display = (id==='settings') ? '' : 'none';
  document.querySelector('.main').classList.toggle('settings-subnav-visible', id==='settings');
  const expBar = document.getElementById('exp-entity-tabs-bar');
  if(expBar){
    expBar.style.display = (id==='expenses') ? '' : 'none';
    document.querySelector('.main').classList.toggle('exp-tabs-visible', id==='expenses');
  }
  closeSidebar();
  const loaders={
    dashboard:loadDashboard, products:()=>{loadProducts();loadCategories();},
    vendors:loadVendors, categories:loadCategoriesPage, customers:loadCustomers,
    invoices:loadInvoices, 'website-orders':async()=>{ await populatePayeeSelect('wop-payee'); loadWebsiteOrders(); }, 'stock-in':async()=>{
      // Reset form fields when navigating to the page
      ['si-product','si-vendor','si-qty','si-cost','si-note'].forEach(function(id){
        var el=document.getElementById(id); if(el){ el.value=''; }
      });
      clearAllSearchableSelects();
      await populateLocationSelect('si-location');
      var siLoc=document.getElementById('si-location')?.value||null;
      populateProductSelect('si-product', siLoc);
      populateVendorSelect('si-vendor');
      populatePOSelect();
      loadStockIn();
    },
    'purchase-orders':()=>{populateVendorSelect('po-filter-vendor',null,true,true);loadPOs();},
    'vendor-payments':()=>{ loadVendorPaymentsSummary(); },
    'vendor-ledger':()=>{ /* opened programmatically */ },
    'product-ledger':()=>{ /* opened programmatically */ },
    'payee-ledger':()=>{ /* opened programmatically */ },
    'payees':()=>{ populatePayeeTypeSelect('Person'); loadPayees(); },
    'expenses':async()=>{ await loadExpensesPage(); },
    transfers:async()=>{
      ['tr-product','tr-qty'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; });
      var _trInfo=document.getElementById('tr-stock-info'); if(_trInfo) _trInfo.style.display='none';
      clearAllSearchableSelects();
      await populateLocationSelect('tr-from');await populateLocationSelect('tr-to');
      var trLoc=document.getElementById('tr-from')?.value||null;
      populateProductSelect('tr-product', trLoc);loadTransfers();
    },
    adjustments:async()=>{
      ['adj-product','adj-qty','adj-reason'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; });
      clearAllSearchableSelects();
      await populateLocationSelect('adj-location');
      var adjLoc=document.getElementById('adj-location')?.value||null;
      populateProductSelect('adj-product', adjLoc);loadAdjustments();
    },
    reports:()=>{switchRptTab(_rptActiveTab||'overview');}, alerts:loadAlerts,
    'on-order-report': loadOnOrderReport,
    'picking': initPickingPage,
    combos: loadCombos,
    'paid-to-report':  loadPaidToReport,
    'vp-report':       loadVPReport,
    locations:loadLocations, users:loadUsers, audit:loadAudit,
    settings:()=>{loadSettings();switchSettingsTab('general');},
    locations:()=>{showPage('settings');switchSettingsTab('locations');},
    users:()=>{showPage('settings');switchSettingsTab('users');},
    import:initImportPage,
    backup:()=>{showPage('settings');switchSettingsTab('backup');},
  };
  loaders[id]?.();
}
document.querySelectorAll('.nav-item[data-page]').forEach(btn=>btn.addEventListener('click',()=>showPage(btn.dataset.page)));

function getLocationId(){return document.getElementById('global-location')?.value||'';}
function onLocationChange(){
  const active=document.querySelector('.page.active')?.id?.replace('page-','');
  if(active) showPage(active);
}

async function doLogout(){
  await fetch(API.auth+'?action=logout',{method:'POST',credentials:'same-origin'});
  location.reload();
}

// ══════════════════════════════════════════════════════════
// GLOBAL LOCATION SELECTOR
// ══════════════════════════════════════════════════════════
async function loadGlobalLocationSelector(){
  try{
    const r=await api.get(API.locations);
    const sel=document.getElementById('global-location');
    const cur=sel.value;
    sel.innerHTML='<option value="">All Locations</option>'+
      r.data.map(l=>`<option value="${l.id}" ${cur==l.id?'selected':''}>${esc(l.name)}${+l.is_default?' ★':''}</option>`).join('');
  }catch{}
}
async function populateLocationSelect(selId,selectedId=null){
  try{
    const r=await api.get(API.locations);
    const el=document.getElementById(selId);
    if(!el)return;
    const prefer=selectedId||getLocationId()||(r.data.find(l=>+l.is_default)||r.data[0])?.id||'';
    el.innerHTML=r.data.map(l=>`<option value="${l.id}" ${l.id==prefer?'selected':''}>${esc(l.name)}${+l.is_default?' ★':''}</option>`).join('');
  }catch{}
}

// ══════════════════════════════════════════════════════════
// POPULATE HELPERS
// ══════════════════════════════════════════════════════════
async function populateProductSelect(selId, locationId){
  try{
    const products = await getProductsCache();
    const el=document.getElementById(selId);if(!el)return;
    const cur=el.value;
    populateProductSelectEl(el, products, cur, '— Select Product —', locationId||null);
  }catch(e){}
}
// noSearch=true → plain native select (no searchable wrapper), e.g. PO vendor
async function populateVendorSelect(selId,selectedId=null,addAll=false,noSearch=false){
  try{
    const r=await api.get(API.vendors);
    const el=document.getElementById(selId);if(!el)return;
    const pre=addAll?'<option value="">All Vendors</option>':'<option value="">— No Vendor —</option>';
    var vendors=r.data.slice().sort(function(a,b){return (a.name||'').localeCompare(b.name||'');});
    el.innerHTML=pre+vendors.map(v=>`<option value="${v.id}" ${selectedId==v.id?'selected':''}>${esc(v.name)}${v.type?' ('+esc(v.type)+')':''}</option>`).join('');
    if(!noSearch){
      var phText=addAll?'All Vendors':'— No Vendor —';
      makeSearchableSelect(selId, phText);
      refreshSearchableSelect(selId);
    }
  }catch{}
}
async function populatePOSelect(){
  try{
    const r=await api.get(API.purchaseOrders+'?status=sent&status=partial');
    const el=document.getElementById('si-po');if(!el)return;
    el.innerHTML='<option value="">— None —</option>'+
      r.data.map(p=>`<option value="${p.id}">${esc(p.po_number)} – ${esc(p.vendor_name||'')}</option>`).join('');
  }catch{}
}

// ══════════════════════════════════════════════════════════
// DASHBOARD
// ══════════════════════════════════════════════════════════
let chartCategory=null;
async function loadDashboard(){
  const locId=getLocationId();
  const q=locId?'?location_id='+locId:'';
  const qa=locId?'?location_id='+locId+'&':'?';
  try{
    const[r,top]=await Promise.all([api.get(API.dashboard+q),api.get(API.dashboard+qa+'report=top_margin')]);
    const s=r.data.stats;
    document.getElementById('dash-stats').innerHTML=`
      <div class="stat-card" style="--accent-color:var(--accent)"><span class="stat-icon">📦</span><span class="stat-num">${s.total_products}</span><span class="stat-label">Products</span>${!HIDE_VENDOR_INFO&&ROLE!=='manager'?'<div class="stat-sub">'+s.total_vendors+' vendors</div>':''}</div>
      ${!HIDE_STOCK_VALUE?`<div class="stat-card" style="--accent-color:var(--green)"><span class="stat-icon">💰</span><span class="stat-num">${CUR.sym}${fmt(s.stock_value)}</span><span class="stat-label">Stock Value</span><div class="stat-sub">At cost price</div></div>`:''}
      ${!HIDE_COST?`<div class="stat-card" style="--accent-color:var(--orange)"><span class="stat-icon">📈</span><span class="stat-num" style="color:${+s.total_profit>=0?'var(--green)':'var(--red)'}">${CUR.sym}${fmt(s.total_profit)}</span><span class="stat-label">Total Profit</span><div class="stat-sub">Revenue: ${CUR.sym}${fmt(s.total_revenue)}</div></div>`:''}
      <div class="stat-card" style="--accent-color:var(--red)"><span class="stat-icon">🔔</span><span class="stat-num" style="color:${+s.low_stock_count>0?'var(--red)':'var(--green)'}">${s.low_stock_count}</span><span class="stat-label">Low Stock</span></div>`;
    const _ab=document.getElementById('alert-badge');
    if(_ab){_ab.textContent=s.low_stock_count;_ab.style.display=+s.low_stock_count>0?'':'none';}
    document.getElementById('dash-alerts').innerHTML=r.data.alerts.length
      ?r.data.alerts.map(p=>`<div class="stock-alert-item" style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border)">
          <div><div style="font-weight:600;font-size:.85rem">${esc(p.name)}</div><div style="font-size:.73rem;color:var(--text3)">${catLabel(p)}${p.location_name?' · '+esc(p.location_name):''}</div></div>
          <span class="badge ${+p.stock<=0?'badge-red':'badge-yellow'}">${+p.stock<=0?'OUT':p.stock+' '+esc(p.unit)}</span>
        </div>`).join('')
      :'<div class="empty-state" style="padding:20px"><span class="empty-icon">✅</span><strong>All healthy</strong></div>';
    document.getElementById('dash-recent').innerHTML=r.data.recent.length
      ?r.data.recent.map(t=>`<div style="display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border)">
          <div><div style="font-weight:600;font-size:.84rem">${esc(t.product_name)}</div><div style="font-size:.72rem;color:var(--text3)">${t.date}${t.location_name?' · '+esc(t.location_name):''}</div></div>
          <span class="badge ${t.type==='in'?'badge-green':'badge-orange'}">${t.type==='in'?'+':'-'}${t.qty}</span>
        </div>`).join('')
      :'<div class="empty-state" style="padding:20px"><span>No transactions yet</span></div>';
    document.getElementById('dash-top-body').innerHTML=top.data.map(p=>`<tr>
      <td><strong>${esc(p.name)}</strong>${p.category?` <span class="badge badge-blue" style="font-size:.62rem">${esc(catLabel(p))}</span>`:''}</td>
      <td>${p.brand?`<span class="badge badge-orange" style="font-size:.62rem">${esc(p.brand)}</span>`:'—'}</td>
      <td style="color:var(--text2);font-size:.8rem">${HIDE_VENDOR_INFO?'—':esc(p.vendor_name||'—')}</td>
      <td class="mono">${fmtCost(p.cost)}</td><td class="mono">${CUR.sym}${fmtN(p.sell)}</td>
      <td>${HIDE_COST?'<span style="color:var(--text3);font-size:.8rem">—</span>':`<span class="profit-cell ${+p.margin>20?'text-green':+p.margin>10?'text-accent':'text-red'}">${p.margin}%</span>`}</td>
      <td class="mono">${p.stock} ${esc(p.unit)}</td>
      <td class="mono">${HIDE_STOCK_VALUE?'—':CUR.sym+fmtN(p.stock_value)}</td>
    </tr>`).join('')||'<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text3)">No products yet</td></tr>';
    loadDashboardCharts(locId);
  }catch(e){toast(e.message,'error');}
}
async function loadDashboardCharts(locId){
  try{
    const qa=locId?'?location_id='+locId+'&':'?';
    const[pnl,sv]=await Promise.all([api.get(API.dashboard+qa+'report=pnl'),api.get(API.dashboard+qa+'report=stock_value')]);
    const colors=['#4f8eff','#22c55e','#f97316','#a855f7','#eab308','#ef4444','#06b6d4','#ec4899'];
    // Payee payments widget
    try{
      const payeeR = await api.get(API.payees+'?ytd=1');
      const payeeList = document.getElementById('dash-payee-list');
      if(payeeList && payeeR.data && payeeR.data.length){
        // Sort by total_paid descending, filter those with payments
        const active = payeeR.data.filter(function(p){ return +p.total_paid > 0 || +p.payment_count > 0; })
          .sort(function(a,b){ return (+b.total_paid)-(+a.total_paid); });
        if(!active.length){
          payeeList.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text3);font-size:.83rem">No payments recorded yet</div>';
        } else {
          const maxAmt = +active[0].total_paid || 1;
          payeeList.innerHTML = active.map(function(p){
            const pct = Math.round((+p.total_paid / maxAmt) * 100);
            const typeTag = p.type ? '<span style="font-size:.68rem;color:var(--text3);margin-left:6px">'+esc(p.type)+'</span>' : '';
            return '<div style="padding:10px 16px;border-bottom:1px solid var(--border)">'
              +'<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">'
              +'<span style="font-size:.84rem;font-weight:600;color:var(--accent);cursor:pointer;text-decoration:underline;text-underline-offset:2px" onclick="openPayeeLedger('+p.id+',\''+esc(p.name)+'\')" title="View ledger">'+esc(p.name)+'</span>'+typeTag
              +'<span class="mono" style="font-size:.84rem;color:'+(+p.total_paid<0?'var(--red)':'var(--green)')+';font-weight:700">'+(+p.total_paid<0?'-':'')+CUR.sym+fmtN(Math.abs(+p.total_paid))+'</span>'
              +'</div>'
              +'<div style="background:var(--surface3);border-radius:4px;height:5px">'
              +'<div style="background:var(--accent);width:'+pct+'%;height:5px;border-radius:4px;transition:width .4s"></div>'
              +'</div>'
              +'<div style="font-size:.72rem;color:var(--text3);margin-top:3px">'+p.payment_count+' transaction'+(p.payment_count!=1?'s':'')+'</div>'
              +'</div>';
          }).join('');
        }
      }
    }catch{}

    // Category donut
    const catMap={};
    sv.data.forEach(p=>{const c=p.brand||p.category||'Other';catMap[c]=(catMap[c]||0)+(+p.cost_value);});
    const cats=Object.entries(catMap).sort((a,b)=>b[1]-a[1]).slice(0,8);
    if(chartCategory)chartCategory.destroy();
    chartCategory=new Chart(document.getElementById('chart-category'),{
      type:'doughnut',
      data:{labels:cats.map(c=>c[0]),datasets:[{data:cats.map(c=>c[1]),backgroundColor:colors,borderWidth:0,hoverOffset:4}]},
      options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right',labels:{color:'#8892b0',font:{size:11},boxWidth:12}}}}
    });
  }catch{}
}

// ══════════════════════════════════════════════════════════
// PRODUCTS
// ══════════════════════════════════════════════════════════
let bulkSelected=new Set(); let bulkActionType='';

// ── Column Chooser ─────────────────────────────────────────
const PROD_COLS = [
  { key:'sku',          label:'SKU',          def:true  },
  { key:'item_code',    label:'Item Code',    def:true  },
  { key:'image',        label:'Image',        def:true  },
  { key:'brand',        label:'Brand',        def:true  },
  { key:'category',     label:'Category',     def:true  },
  { key:'vendor',       label:'Vendor',       def:false },
  { key:'list_price',   label:'List ₹',       def:false },
  { key:'cost',         label:'Cost ₹',       def:true  },
  { key:'landing_cost',     label:'Landing ₹',        def:false },
  { key:'sell',             label:'Sell ₹',            def:true  },
  { key:'wholesale_price',  label:'Wholesale ₹',       def:false },
  { key:'margin',       label:'Margin',       def:true  },
  { key:'case_content', label:'Case Content', def:false },
  { key:'box_content',  label:'Box Content',  def:true  },
  { key:'combo',        label:'Combo',        def:false },
  { key:'publish_web',       label:'Push to Web',  def:false },
  { key:'procurement_active', label:'Status', def:true },
  { key:'stock',        label:'Stock',        def:true  },
  { key:'min_stock',    label:'Min Stock',    def:false },
  { key:'status',       label:'Status',       def:true  },
  { key:'open_orders',   label:'Open Orders',  def:true  },
];
function getColPrefs(){
  try{ const s=localStorage.getItem('sm_prod_cols'); if(s) return JSON.parse(s); }catch{}
  const d={};PROD_COLS.forEach(c=>d[c.key]=c.def);return d;
}
function saveColPrefs(prefs){ localStorage.setItem('sm_prod_cols',JSON.stringify(prefs)); }
function resetColPrefs(){ localStorage.removeItem('sm_prod_cols'); buildColToggles(); renderProductTable(); }
function colVisible(key){ return getColPrefs()[key]!==false; }

function buildColToggles(){
  const prefs=getColPrefs();
  const list=document.getElementById('col-toggle-list');
  if(!list)return;
  list.innerHTML=PROD_COLS.map(c=>`
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.83rem;color:var(--text)">
      <input type="checkbox" ${prefs[c.key]!==false?'checked':''} onchange="onColToggle('${c.key}',this.checked)"
        style="width:14px;height:14px;accent-color:var(--accent);cursor:pointer">
      ${c.label}
    </label>`).join('');
}
function onColToggle(key,checked){
  const prefs=getColPrefs(); prefs[key]=checked; saveColPrefs(prefs);
  renderProductTable();
}
function toggleColChooser(){
  const el=document.getElementById('col-chooser');
  const open=el.style.display==='none';
  el.style.display=open?'block':'none';
  if(open) buildColToggles();
}
// Close chooser on outside click
document.addEventListener('click',e=>{
  const chooser=document.getElementById('col-chooser');
  const btn=document.getElementById('col-chooser-btn');
  if(chooser&&chooser.style.display!=='none'&&!chooser.contains(e.target)&&e.target!==btn&&!btn?.contains(e.target))
    chooser.style.display='none';
});

let _productData=[], _productLocs=[];
const PRODUCTS_PER_PAGE = 50;
let _productPage = 1;
async function togglePublishWeb(pid, btn){
  const cur = btn.dataset.active === '1';
  const newVal = cur ? 0 : 1;
  btn.dataset.active = String(newVal);
  btn.style.background = newVal ? '#3b82f6' : 'var(--border2)';
  btn.querySelector('span').style.left = newVal ? '25px' : '3px';
  btn.title = newVal ? 'Pushed to web — click to unpublish' : 'Not on web — click to publish';
  try{
    await api.put(API.products,{id:pid,_bulk:true,publish_web:newVal});
    const p=_productData?.find(x=>String(x.id)===String(pid));
    if(p) p.publish_web=newVal;
    invalidateProductsCache();
  }catch(e){
    btn.dataset.active = cur?'1':'0';
    btn.style.background = cur ? '#3b82f6' : 'var(--border2)';
    btn.querySelector('span').style.left = cur ? '25px' : '3px';
    toast(e.message,'error');
  }
}
async function toggleProcurementActive(pid, btn){
  const cur = btn.dataset.active === '1';
  const newVal = cur ? 0 : 1;
  btn.dataset.active = String(newVal);
  btn.style.background = newVal ? 'var(--green)' : 'var(--border2)';
  btn.querySelector('span').style.left = newVal ? '25px' : '3px';
  btn.title = newVal ? 'Active — click to deactivate' : 'Inactive — click to activate';
  try{
    await api.put(API.products,{id:pid,_bulk:true,procurement_active:newVal});
    const p=_productData?.find(x=>String(x.id)===String(pid));
    if(p) p.procurement_active=newVal;
    invalidateProductsCache();
  }catch(e){
    btn.dataset.active = cur ? '1' : '0';
    btn.style.background = cur ? 'var(--green)' : 'var(--border2)';
    btn.querySelector('span').style.left = cur ? '25px' : '3px';
    toast(e.message,'error');
  }
}
function setPAFilter(val){
  document.getElementById('product-procurement-filter').value=val;
  document.querySelectorAll('.paf-btn').forEach(b=>{b.style.background='var(--surface2)';b.style.color='var(--text2)';});
  const id=val===''?'paf-all':val==='1'?'paf-active':'paf-inactive';
  const btn=document.getElementById(id);
  if(btn){btn.style.background='var(--accent)';btn.style.color='#fff';}
  loadProducts();
}
function setWebFilter(val){
  document.getElementById('product-web-filter').value=val;
  document.querySelectorAll('.wbf-btn').forEach(b=>{b.style.background='var(--surface2)';b.style.color='var(--text2)';});
  const id=val===''?'wbf-all':val==='1'?'wbf-web':'wbf-noweb';
  const btn=document.getElementById(id);
  if(btn){btn.style.background='var(--accent)';btn.style.color='#fff';}
  loadProducts();
}
function setComboFilter(val){
  document.getElementById('product-combo-filter').value=val;
  document.querySelectorAll('.cbf-btn').forEach(b=>{b.style.background='var(--surface2)';b.style.color='var(--text2)';});
  const id=val===''?'cbf-all':val==='1'?'cbf-combo':'cbf-regular';
  const btn=document.getElementById(id);
  if(btn){btn.style.background='var(--accent)';btn.style.color='#fff';}
  loadProducts();
}
async function loadProducts(){
  _productPage=1;
  const q=document.getElementById('product-search')?.value||'';
  const cat=document.getElementById('product-cat-filter')?.value||'';
  const brand=document.getElementById('product-brand-filter')?.value||'';
  const vendorId=document.getElementById('product-vendor-filter')?.value||'';
  const sf=document.getElementById('product-stock-filter')?.value||'';
  const pa=document.getElementById('product-procurement-filter')?.value;
  const cf=document.getElementById('product-combo-filter')?.value;
  const costMax=document.getElementById('product-cost-filter')?.value||'';
  const locId=getLocationId();
  const params=new URLSearchParams();
  if(q)params.set('q',q);if(cat)params.set('category',cat);if(brand)params.set('brand',brand);if(vendorId)params.set('vendor_id',vendorId);if(sf)params.set('stock_filter',sf);
  if(pa!==undefined&&pa!=='') params.set('procurement_active',pa);
  const wf=document.getElementById('product-web-filter')?.value;
  if(cf!==undefined&&cf!=='') params.set('combo_filter',cf);
  if(wf!==undefined&&wf!=='') params.set('web_filter',wf);
  if(costMax) params.set('cost_max',costMax);
  if(locId)params.set('location_id',locId);
  try{
    const [r, poR, dupR] = await Promise.all([
      api.get(API.products+(params.toString()?'?'+params:'')),
      api.get('api/purchase_orders.php?status_filter=open&compact=1').catch(()=>({data:[]})),
      api.get('api/products.php?duplicates=1').catch(()=>({data:{sku:[]}}))
    ]);
    // Build set of product IDs that have a duplicate SKU (same SKU + vendor + brand)
    // Different brand = NOT a duplicate, even with the same SKU and vendor
    _dupSkuIds = new Set();
    ((dupR.data&&dupR.data.sku)||[]).forEach(function(g){
      g.products.forEach(function(p){ _dupSkuIds.add(String(p.id)); });
    });
    _productData=r.data;
    const ptitle=document.getElementById('products-page-title');
    if(ptitle) ptitle.textContent='📦 Products ('+r.total+')';
    // Build per-product PO map
    const poMap={};
    (poR.data||[]).forEach(po=>{
      (po.items||[]).forEach(item=>{
        if(!poMap[item.product_id]) poMap[item.product_id]=[];
        poMap[item.product_id].push({
          po_number:po.po_number, status:po.status,
          pending_qty: Math.max(0, (+item.qty_ordered||0) - (+item.qty_received||0))
        });
      });
    });
    _productData.forEach(p=>{ p.open_orders = poMap[String(p.id)]||[]; });
    if(_productData.length && _productData[0].location_stocks){
      _productLocs=_productData[0].location_stocks.map(l=>({id:l.location_id,name:l.location_name}));
    }
    renderProductTable();
  }catch(e){toast(e.message,'error');}
}
function renderProductTable(){
  const tbody=document.getElementById('products-body');
  const empty=document.getElementById('products-empty');
  const thead=document.getElementById('products-thead');
  const showBulk=document.getElementById('bulk-bar')?.classList.contains('visible');
  const vis=key=>colVisible(key);
  const locId=getLocationId();

  // Build header — stock columns: one per location if multiple, else just "Stock"
  let hcells=`<th class="checkbox-col"><input type="checkbox" id="bulk-all" onchange="toggleBulkAll(this)"></th>`;
  if(vis('image')) hcells+=`<th></th>`;
  if(vis('procurement_active')) hcells+=`<th>Status</th>`;
  if(vis('sku'))   hcells+=`<th>SKU</th>`;
  if(vis('item_code')) hcells+=`<th style="max-width:60px">Item<br>Code</th>`;
  hcells+=`<th>Product</th>`;
  if(vis('brand'))        hcells+=`<th>Brand</th>`;
  if(vis('category'))     hcells+=`<th>Category</th>`;
  if(vis('vendor'))       hcells+=`<th>Vendor</th>`;
  if(vis('list_price')&&!HIDE_COST) hcells+=`<th style="max-width:70px">List<br>₹</th>`;
  if(vis('cost'))         hcells+=`<th>Cost ₹</th>`;
  if(vis('landing_cost')&&!HIDE_COST) hcells+=`<th style="max-width:70px">Landing<br>₹</th>`;
  if(vis('sell'))         hcells+='<th>Sell ₹</th>';
  if(vis('wholesale_price')) hcells+='<th style="max-width:80px">Wholesale<br>₹</th>';
  if(vis('margin'))       hcells+=`<th>Margin</th>`;
  if(vis('case_content')) hcells+='<th style="max-width:70px">Case<br>Content</th>';
  if(vis('box_content'))  hcells+='<th style="max-width:70px">Box<br>Content</th>';
  if(vis('combo'))        hcells+=`<th>Combo</th>`;
  if(vis('publish_web'))  hcells+=`<th title="Push to Website">🌐 Web</th>`;
  if(vis('stock')){
    if(_productLocs.length>1 && !locId){
      // Show one column per location
      _productLocs.forEach(l=>{ hcells+=`<th title="${esc(l.name)}">${esc(l.name)}</th>`; });
    } else {
      hcells+=`<th>${locId&&_productLocs.length?(_productLocs.find(l=>l.id==locId)?.name||'Stock'):'Stock'}</th>`;
    }
  }
  if(vis('min_stock'))    hcells+=`<th style="max-width:50px">Min<br>Stock</th>`;
  if(vis('status'))       hcells+=`<th>Status</th>`;
  if(vis('open_orders')) hcells+=`<th style="max-width:60px">On<br>Order</th>`;
  hcells+=`<th>Actions</th>`;
  if(thead){ thead.innerHTML=`<tr>${hcells}</tr>`; initProductColResize(); }

  if(!_productData.length){if(tbody)tbody.innerHTML='';if(empty)empty.style.display='block';
    document.getElementById('products-pagination').innerHTML=''; return;}
  if(empty) empty.style.display='none';

  // Pagination
  const totalPages = Math.ceil(_productData.length / PRODUCTS_PER_PAGE);
  if(_productPage > totalPages) _productPage = 1;
  const pageStart = (_productPage-1)*PRODUCTS_PER_PAGE;
  const pageData  = _productData.slice(pageStart, pageStart+PRODUCTS_PER_PAGE);

  // Pagination controls
  const pg = document.getElementById('products-pagination');
  if(pg){
    const info = '<span style="font-size:.8rem;color:var(--text3)">Showing '+(pageStart+1)+'–'+Math.min(pageStart+PRODUCTS_PER_PAGE,_productData.length)+' of '+_productData.length+'</span>';
    const prevBtn = '<button class="btn btn-ghost btn-sm" '+(_productPage<=1?'disabled':'onclick="_productPage--;renderProductTable()"')+'>← Prev</button>';
    const nextBtn = '<button class="btn btn-ghost btn-sm" '+(_productPage>=totalPages?'disabled':'onclick="_productPage++;renderProductTable()"')+'>Next →</button>';
    const pageNums = Array.from({length:totalPages},(_,i)=>i+1).filter(n=>Math.abs(n-_productPage)<=2||n===1||n===totalPages)
      .reduce((acc,n,i,arr)=>{if(i>0&&n-arr[i-1]>1)acc.push('…');acc.push(n);return acc;},[]);
    const pageLinks = pageNums.map(n=>n==='…'?'<span style="padding:0 4px;color:var(--text3)">…</span>':
      '<button class="btn btn-sm '+(n===_productPage?'btn-primary':'btn-ghost')+'" onclick="_productPage='+n+';renderProductTable()">'+n+'</button>').join('');
    pg.innerHTML = prevBtn+pageLinks+nextBtn+info;
  }

  tbody.innerHTML=pageData.map(p=>{
    const dispStock = p.display_stock ?? p.stock;
    const dispMin   = p.display_min_stock ?? p.min_stock;
    const sc=+dispStock<=0?['badge-red','Out']:+dispStock<=+dispMin?['badge-yellow','Low']:['badge-green','OK'];
    const img=p.image?`<img src="${esc(p.image)}" class="product-img" loading="lazy">`:`<div class="product-img-placeholder">📦</div>`;
    // ie() — renders a cell that calls inlineEdit when clicked
    // data-pid, data-field, data-type, data-val stored as data attributes to avoid JSON/quote issues
    const ie=(field,display,val,type='text')=>{
      const safeVal = val===null||val===undefined ? '' : String(val);
      return '<td class="ie-cell" data-pid="'+p.id+'" data-field="'+field+'" data-type="'+type+'" data-val="'+esc(safeVal)+'" title="Click to edit"><span class="ie-val">'+display+'</span><span class="price-hint">✎</span></td>';
    };
    let cells='<td class="checkbox-col">'+(showBulk?'<input type="checkbox" '+(bulkSelected.has(p.id)?'checked':'')+' onchange="toggleBulkItem('+p.id+',this.checked)">':'&nbsp;')+'</td>';
    if(vis('image'))        cells+='<td>'+img+'</td>';
    if(vis('procurement_active')){
      const isActive = parseInt(p.procurement_active,10)!==0;
      cells+='<td style="text-align:center;padding:4px 6px">'
        +'<button onclick="toggleProcurementActive('+p.id+',this)" '
        +'data-active="'+(isActive?'1':'0')+'" '
        +'style="position:relative;display:inline-flex;align-items:center;width:44px;height:22px;border-radius:11px;border:none;cursor:pointer;transition:background .2s;outline:none;background:'+(isActive?'var(--green)':'var(--border2)')+'" '
        +'title="'+(isActive?'Active':'Inactive')+'">'
        +'<span style="position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);transition:left .2s;left:'+(isActive?'25px':'3px')+'"></span>'
        +'</button></td>';
    }
    if(vis('sku')){
      const skuDupBadge = (_dupSkuIds.has(String(p.id)) && p.sku) ? ' <span title="Duplicate SKU (same vendor + brand)" style="background:var(--orange);color:#fff;font-size:.6rem;padding:1px 5px;border-radius:4px;font-weight:700;vertical-align:middle">⚠️ DUP</span>' : '';
      cells+=ie('sku','<span class="mono" style="color:var(--accent2);font-size:.75rem;font-weight:600">'+esc(p.sku||'—')+'</span>'+skuDupBadge, p.sku||'');
    }
    if(vis('item_code'))    cells+='<td class="mono" style="color:var(--text2);font-size:.8rem">'+(p.item_code||'—')+'</td>';
    cells+=ie('name','<div style="font-weight:600">'+esc(p.name)+'</div>'+(p.description?'<div style="font-size:.72rem;color:var(--text3)">'+esc(p.description)+'</div>':''),p.name);
    if(vis('brand'))        cells+=ie('brand',p.brand?'<span class="badge badge-orange">'+esc(p.brand)+'</span>':'<span style="color:var(--text3)">—</span>',p.brand||'','brand');
    if(vis('category'))     cells+=ie('category',p.category?'<span class="badge badge-blue">'+esc(catLabel(p))+'</span>':'<span style="color:var(--text3)">—</span>',catLabel(p)||'','category');
    if(vis('vendor'))       cells+=ie('vendor_id','<span style="color:var(--text2);font-size:.82rem">'+esc(p.vendor_name||'—')+'</span>',p.vendor_id||'','vendor');
    if(vis('list_price'))   cells+=HIDE_COST?'':ie('list_price','<span class="mono" style="color:var(--text2)">'+(p.list_price?CUR.sym+fmtN(p.list_price):'—')+'</span>',p.list_price||'','number');
    if(vis('cost'))         cells+=HIDE_COST?'<td>—</td>':ie('cost','<span class="mono">'+CUR.sym+fmtN(p.cost)+'</span>',p.cost,'number');
    if(vis('landing_cost')) cells+=HIDE_COST?'<td>—</td>':ie('landing_cost','<span class="mono" style="color:var(--text2)">'+(p.landing_cost?CUR.sym+fmtN(p.landing_cost):'—')+'</span>',p.landing_cost||'','number');
    if(vis('sell'))         cells+=ie('sell','<span class="mono">'+CUR.sym+fmtN(p.sell)+'</span>',p.sell,'number');
    if(vis('wholesale_price')) cells+=ie('wholesale_price','<span class="mono" style="color:var(--text2)">'+(p.wholesale_price?CUR.sym+fmtN(p.wholesale_price):'—')+'</span>',p.wholesale_price||'','number');
    if(vis('margin'))       cells+='<td><span class="profit-cell '+(+p.margin>20?'text-green':+p.margin>10?'text-accent':'text-red')+'">'+p.margin+'%</span></td>';
    if(vis('case_content')) cells+=ie('case_content','<span class="mono" style="color:var(--text2)">'+(p.case_content&&+p.case_content>0?Math.round(+p.case_content):'—')+'</span>',p.case_content&&+p.case_content>0?Math.round(+p.case_content):'','number');
    if(vis('box_content'))  cells+=ie('box_content','<span class="mono" style="color:var(--text2)">'+(p.box_content||'—')+'</span>',p.box_content||'','text');
    if(vis('combo'))        cells+=ie('combo',+p.combo?'<span class="badge badge-purple">Yes</span>':'<span class="badge badge-gray">No</span>',p.combo,'toggle');
    if(vis('publish_web')){
      const isWeb = parseInt(p.publish_web,10)===1;
      cells+='<td style="text-align:center;padding:4px 6px">';
      cells+='<button onclick="togglePublishWeb('+p.id+',this)" data-active="'+(isWeb?'1':'0')+'"'
        +' style="position:relative;display:inline-flex;align-items:center;width:44px;height:22px;border-radius:11px;border:none;cursor:pointer;outline:none;background:'+(isWeb?'#3b82f6':'var(--border2)')+'"'
        +' title="'+(isWeb?'Pushed to web — click to unpublish':'Not on web — click to publish')+'">'
        +'<span style="position:absolute;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.3);left:'+(isWeb?'25px':'3px')+'"></span>'
        +'</button></td>';
    }
    if(vis('stock')){
      const locs = p.location_stocks||[];
      if(locs.length>1 && !locId){
        locs.forEach(ls=>{
          const lstatus=+ls.stock<=0?'text-red':+ls.stock<=+ls.min_stock?'text-yellow':'';
          cells+='<td class="mono '+lstatus+'" style="font-weight:600">'+ls.stock+' <span style="font-size:.72rem;color:var(--text3)">'+esc(p.unit)+'</span></td>';
        });
      } else {
        const ls = locId ? locs.find(l=>l.location_id==locId) : null;
        const stock = ls ? ls.stock : dispStock;
        cells+='<td class="mono" style="font-weight:700">'+stock+' <span style="font-size:.72rem;color:var(--text3)">'+esc(p.unit)+'</span></td>';
      }
    }
    if(vis('min_stock'))    cells+=ie('min_stock','<span class="mono" style="color:var(--text3)">'+dispMin+'</span>',dispMin,'number');
    if(vis('status'))       cells+='<td><span class="badge '+sc[0]+'">'+sc[1]+'</span></td>';
    if(vis('open_orders')){
      const po=p.open_orders||[];
      const pendingQty=po.reduce(function(s,o){return s+(+o.pending_qty||0);},0);
      if(po.length && pendingQty>0){
        const statusLabels={draft:'Draft',sent:'Sent',partial:'Partial'};
        const tip=po.map(function(o){
          return o.po_number+' ['+( statusLabels[o.status]||o.status)+']: '+o.pending_qty+' units pending';
        }).join('&#10;');
        cells+='<td class="mono" style="font-weight:700;color:var(--orange)" title="'+tip+'">'+pendingQty+'</td>';
      } else {
        cells+='<td style="color:var(--text3)">—</td>';
      }
    }
    cells+='<td style="white-space:nowrap">'
      +'<button class="btn btn-ghost btn-xs" onclick="openProductLedger('+p.id+',\''+p.name.replace(/'/g,"\\'")+'\')" title="View Ledger">📒</button> '
      +'<button class="btn btn-ghost btn-xs" onclick="editProduct('+p.id+')">✏️</button> '
      +'<button class="btn btn-ghost btn-xs" onclick="cloneProduct('+p.id+')" title="Clone product">📋</button> '
      +'<button class="btn btn-purple btn-xs" onclick="printBarcode('+p.id+',\''+esc(p.name)+'\',\''+esc(p.sku||'')+'\')">🏷️</button> '
      +(CAN_DELETE?'<button class="btn btn-danger btn-xs" onclick="deleteProduct('+p.id+',\''+esc(p.name)+'\')">🗑️</button>':'')
      +'<button class="btn btn-ghost btn-xs" onclick="openQuickTransfer('+p.id+',\''+esc(p.name)+'\')">🔄</button>'
      +'</td>';
    const isDupSku = _dupSkuIds.has(String(p.id));
    const rowStyle = isDupSku ? ' style="outline:2px solid var(--orange);outline-offset:-2px;background:rgba(249,115,22,.05)"' : '';
    return '<tr'+rowStyle+'>'+cells+'</tr>';
  }).join('');
}
// ══════════════════════════════════════════════════════════
// CATEGORIES
// ══════════════════════════════════════════════════════════
const CAT_COLORS = {
  blue:'badge-blue', green:'badge-green', orange:'badge-orange',
  red:'badge-red', purple:'badge-purple', yellow:'badge-yellow', '':'badge-gray'
};
function catBadgeClass(color){ return CAT_COLORS[color||'']||'badge-blue'; }

async function loadCategories(){
  // Populate category select dropdowns in product modal + filters
  try{
    const r = await api.get(API.categories);
    // Build name→{sku_prefix,name} map for catLabel in dropdowns
    _catMap = {};
    r.data.forEach(function(c){ _catMap[c.name] = c; });
    // Product modal selects — value stored as name in DB
    const pCat = document.getElementById('p-category');
    const qCat = document.getElementById('qp-category');
    const filterCat = document.getElementById('product-cat-filter');
    const opts = '<option value="">— None —</option>' + r.data.map(c=>`<option value="${esc(c.name)}">${c.sku_prefix?esc(c.sku_prefix)+'-':'' }${esc(c.name)}</option>`).join('');
    if(pCat) { const v=pCat.value; pCat.innerHTML=opts; if(v) pCat.value=v; makeSearchableSelect('p-category','— None —'); refreshSearchableSelect('p-category'); }
    if(qCat) { const v=qCat.value; qCat.innerHTML=opts; if(v) qCat.value=v; makeSearchableSelect('qp-category','— None —'); refreshSearchableSelect('qp-category'); }
    if(filterCat){ const cur=filterCat.value; filterCat.innerHTML='<option value="">All Categories</option>'+r.data.map(c=>`<option value="${esc(c.name)}" ${cur===c.name?'selected':''}>${c.sku_prefix?esc(c.sku_prefix)+'-':''}${esc(c.name)}</option>`).join(''); makeSearchableSelect('product-cat-filter','All Categories'); refreshSearchableSelect('product-cat-filter'); }
    // Also update brand list (unchanged)
    const brandR = await api.get(API.products+'?brands=1');
    const brandSel=document.getElementById('product-brand-filter');
    if(brandSel){const cur=brandSel.value;brandSel.innerHTML='<option value="">All Brands</option>'+brandR.data.map(b=>`<option value="${esc(b)}" ${cur===b?'selected':''}>${esc(b)}</option>`).join('');}
    // Vendor filter
    if(document.getElementById('product-vendor-filter')){
      const curV=document.getElementById('product-vendor-filter').value;
      await populateVendorSelect('product-vendor-filter', curV||null, true, false);
    }
  }catch{}
}

async function loadCategoriesPage(){
  const q = document.getElementById('category-search')?.value||'';
  try{
    const r = await api.get(API.categories+(q?'?q='+encodeURIComponent(q):''));
    const tbody = document.getElementById('categories-body');
    const empty = document.getElementById('categories-empty');
    const ct=document.getElementById('categories-page-title');
    if(!r.data.length){ tbody.innerHTML=''; empty.style.display='block'; if(ct)ct.textContent='🏷️ Categories (0)'; return; }
    empty.style.display='none';
    if(ct)ct.textContent='🏷️ Categories ('+r.total+')';
    tbody.innerHTML = r.data.map(c=>{
      const badge = catBadgeClass(c.color);
      const colorDot = c.color ? `<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--${c.color});margin-right:6px;vertical-align:middle"></span>` : '';
      return `<tr>
        <td><span class="badge ${badge}">${colorDot}${esc(catLabel(c))}</span></td>
        <td><span class="mono" style="font-size:.82rem;color:var(--accent)">${esc(c.sku_prefix||'—')}</span></td>
        <td style="color:var(--text2);font-size:.84rem">${esc(c.description||'—')}</td>
        <td>${c.color?`<span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:var(--${c.color});vertical-align:middle"></span> ${c.color}` : '—'}</td>
        <td><span class="badge badge-blue">${c.product_count||0} products</span></td>
        <td>
          <button class="btn btn-ghost btn-xs" onclick="editCategory(${c.id})">✏️</button>
          ${CAN_DELETE?`<button class="btn btn-danger btn-xs" onclick="deleteCategory(${c.id},'${esc(c.name)}',${c.product_count||0})">🗑️</button>`:""}
        </td>
      </tr>`;
    }).join('');
  }catch(e){toast(e.message,'error');}
}

let _catModalReturnFocus = false;
function openCategoryModal(fromProductModal=false){
  // When called from product modal ＋ button, open the modal version.
  // The modal has its own catm-* ids (separate from the Categories page's
  // own cat-* inline form, which is always present in the DOM too) —
  // they used to share the same ids, so getElementById() always grabbed
  // the page form's fields regardless of which one the user was actually
  // filling in, and Save always saw an empty name from the untouched page
  // form ("Category name required" even with the modal fully filled in).
  _catModalReturnFocus = fromProductModal;
  if(fromProductModal){
    document.getElementById('catm-edit-id').value='';
    document.getElementById('catm-name').value='';
    if(document.getElementById('catm-sku-prefix')) document.getElementById('catm-sku-prefix').value='';
    document.getElementById('catm-desc').value='';
    selectCatColor('','catm');
    setElText('category-modal-title', 'Add Category');
    openModal('modal-category');
    setTimeout(()=>document.getElementById('catm-name').focus(),200);
    return;
  }
  // Otherwise clear the inline form
  cancelCategoryEdit();
  document.getElementById('cat-name').focus();
}
function clearCategoryForm(){
  document.getElementById('cat-edit-id').value='';
  document.getElementById('cat-name').value='';
  document.getElementById('cat-desc').value='';
  selectCatColor('');
  setElText('cat-form-title', '🏷️ Add Category');
  document.getElementById('cat-cancel-btn').style.display='none';
  setElText('cat-save-btn', 'Save Category');
}
function cancelCategoryEdit(){ clearCategoryForm(); }
async function editCategory(id){
  try{
    const r=await api.get(API.categories+'?id='+id);
    const c=r.data;
    document.getElementById('cat-edit-id').value=c.id;
    document.getElementById('cat-name').value=c.name;
    if(document.getElementById('cat-sku-prefix')) document.getElementById('cat-sku-prefix').value=c.sku_prefix||'';
    document.getElementById('cat-desc').value=c.description||'';
    selectCatColor(c.color||'');
    setElText('cat-form-title', '🏷️ Edit Category');
    document.getElementById('cat-cancel-btn').style.display='';
    setElText('cat-save-btn', 'Update Category');
    document.getElementById('cat-name').scrollIntoView({behavior:'smooth',block:'center'});
    document.getElementById('cat-name').focus();
  }catch(e){toast(e.message,'error');}
}
function selectCatColor(color,prefix){
  prefix=prefix||'cat';
  document.getElementById(prefix+'-color').value=color;
  document.querySelectorAll('#'+prefix+'-color-swatches .cat-swatch').forEach(s=>{
    s.style.border=s.dataset.color===color?'2px solid var(--text)':'2px solid transparent';
    if(!s.dataset.color&&!color) s.style.border='2px solid var(--text)';
  });
}
async function saveCategory(){
  // Which form is actually being submitted — the Categories page's own
  // inline cat-* form, or the catm-* quick-add modal opened from a
  // product form's '+' button. These used to share ids, so Save always
  // read the page form regardless of which one was in front.
  const modalOpen=!!document.getElementById('modal-category')?.classList.contains('open');
  const idp=modalOpen?'catm':'cat';
  const name=document.getElementById(idp+'-name').value.trim();
  if(!name){toast('Category name required','error');return;}
  const editId=parseInt(document.getElementById(idp+'-edit-id').value)||0;
  const body={name,sku_prefix:document.getElementById(idp+'-sku-prefix')?.value.trim()||null,description:document.getElementById(idp+'-desc').value.trim(),color:document.getElementById(idp+'-color').value};
  const btn=document.getElementById(modalOpen?'catm-save-btn':'cat-save-btn');
  btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){await api.put(API.categories,{...body,id:editId});toast('Category updated');}
    else{await api.post(API.categories,body);toast('Category added');}
    if(modalOpen){
      closeModal('modal-category');
      if(_catModalReturnFocus){
        await loadCategories();
        const sel=document.getElementById('p-category')||document.getElementById('qp-category');
        if(sel) sel.value=name;
      }
    } else {
      clearCategoryForm();
    }
    await loadCategories();
    loadCategoriesPage();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.textContent=editId?'Update Category':'Save Category';}
}
async function deleteCategory(id,name,productCount){
  const msg=productCount>0?`Delete "${name}"? ${productCount} product(s) will have their category cleared.`:`Delete category "${name}"?`;
  if(!confirm(msg))return;
  try{await api.delete(API.categories+'?id='+id);toast('Category deleted');loadCategories();loadCategoriesPage();}
  catch(e){toast(e.message,'error');}
}


function openProductModal(product=null){
  clearProductForm();
  if(product){
    setElText('product-modal-title', 'Edit Product');
    document.getElementById('p-edit-id').value=product.id;
    document.getElementById('p-name').value=product.name;
    document.getElementById('p-sku').value=product.sku||'';
    document.getElementById('p-item-code').value=product.item_code||'';
    document.getElementById('p-brand').value=product.brand||'';
    const catSel=document.getElementById('p-category');
    if(catSel){ catSel.value=product.category||''; refreshSearchableSelect('p-category'); }
    document.getElementById('p-list-price').value=product.list_price||'';
    document.getElementById('p-cost').value=product.cost;
    document.getElementById('p-sell').value=product.sell||'';
    document.getElementById('p-min-stock').value=product.min_stock||0;
    document.getElementById('p-unit').value=product.unit||'Box';
    document.getElementById('p-case-content').value=product.case_content||'';
    document.getElementById('p-box-content').value=product.box_content||'';
    document.getElementById('p-landing-cost').value=product.landing_cost||'';
    document.getElementById('p-wholesale-price').value=product.wholesale_price||'';
    document.getElementById('p-combo').value=product.combo?'1':'0';
    document.getElementById('p-desc').value=product.description||'';
    if(product.image){document.getElementById('p-image-preview').style.display='block';document.getElementById('p-img-preview-el').src=product.image;}
  }
  populateVendorSelect('p-vendor',product?.vendor_id);
  openModal('modal-product');
  setTimeout(()=>document.getElementById('p-name').focus(),200);
}
function clearProductForm(){
  setElText('product-modal-title', 'Add Product');
  ['p-edit-id','p-name','p-sku','p-item-code','p-brand','p-category','p-cost','p-list-price','p-sell','p-stock','p-min-stock','p-unit','p-case-content','p-box-content','p-landing-cost','p-wholesale-price','p-desc'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  const unitEl=document.getElementById('p-unit');if(unitEl)unitEl.value='Box';
  const combo=document.getElementById('p-combo');if(combo)combo.value='0';
  document.getElementById('p-image-preview').style.display='none';
}
function autoExtractItemCode(sku){
  const el=document.getElementById('p-item-code');
  if(!el) return;
  // Extract leading digits only (e.g. 2405V2 → 2405, not 24052)
  const m=sku.match(/^(\d+)/);
  el.value = m ? m[1] : '';
  // Auto-fill product name from existing product with same SKU
  // Only fill if name field is still empty (don't overwrite user input)
  const nameEl = document.getElementById('p-name');
  if(nameEl && !nameEl.value.trim() && sku.trim()){
    getProductsCache().then(function(products){
      const match = products.find(function(p){ return p.sku && p.sku.toLowerCase()===sku.toLowerCase(); });
      if(match && nameEl && !nameEl.value.trim()) nameEl.value = match.name;
    }).catch(function(){});
  }
}
function previewProductImage(input){
  const f=input.files[0];if(!f)return;
  const r=new FileReader();r.onload=e=>{document.getElementById('p-img-preview-el').src=e.target.result;document.getElementById('p-image-preview').style.display='block';};r.readAsDataURL(f);
}
async function editProduct(id){
  try{const r=await api.get(API.products+'?id='+id);openProductModal(r.data);}catch(e){toast(e.message,'error');}
}
async function cloneProduct(id){
  try{
    const r=await api.get(API.products+'?id='+id);
    const p=r.data;
    // Open Add Product modal pre-filled but with no ID (new product)
    // Clear fields that should not be copied
    p.id=null;
    p.name='Copy of '+p.name;
    // Keep SKU — user will update manually
    p.stock=0;
    p.image=null;
    openProductModal(p);
    // Clear the edit ID so it saves as new
    document.getElementById('p-edit-id').value='';
    // Update modal title
    const title=document.getElementById('modal-product-title');
    if(title) title.textContent='📋 Clone Product';
    toast('Cloned — update SKU and name, then save','info');
  }catch(e){toast(e.message,'error');}
}

async function saveProduct(){
  const editId=parseInt(document.getElementById('p-edit-id').value)||0;
  const body={name:document.getElementById('p-name').value.trim(),sku:document.getElementById('p-sku').value.trim(),item_code:document.getElementById('p-item-code').value||null,brand:document.getElementById('p-brand').value.trim(),category:document.getElementById('p-category').value.trim(),vendor_id:document.getElementById('p-vendor').value||null,cost:document.getElementById('p-cost').value,list_price:document.getElementById('p-list-price').value||null,sell:document.getElementById('p-sell').value,stock:document.getElementById('p-stock').value||0,min_stock:document.getElementById('p-min-stock').value||0,unit:document.getElementById('p-unit').value||'Box',case_content:document.getElementById('p-case-content').value||null,box_content:document.getElementById('p-box-content').value||null,landing_cost:document.getElementById('p-landing-cost').value||null,wholesale_price:document.getElementById('p-wholesale-price').value||null,combo:document.getElementById('p-combo').value==='1'?1:0,description:document.getElementById('p-desc').value.trim()};
  if(!body.name){toast('Product name required','error');return;}
  if(!body.cost){toast('Cost price is required','error');return;}

  // Handle image upload
  const imgFile=document.getElementById('p-image-file')?.files[0];
  if(imgFile){
    const fd=new FormData();fd.append('image',imgFile);fd.append('id',editId||'new');
    try{const ir=await fetch('api/upload_image.php',{method:'POST',body:fd,credentials:'same-origin'});const ij=await ir.json();if(ij.success)body.image=ij.path;}catch{}
  }
  const btn=document.getElementById('p-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){body.id=editId;await api.put(API.products,body);toast('Product updated!');}
    else{await api.post(API.products,body);toast('Product added!');}
    closeModal('modal-product');clearAllSearchableSelects();invalidateProductsCache();loadProducts();loadCategories();updateAlertBadge();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='Save Product';}
}
async function deleteProduct(id,name){
  if(!confirm(`Delete "${name}"?`))return;
  try{await api.delete(API.products+'?id='+id);toast('Product deleted');invalidateProductsCache();loadProducts();updateAlertBadge();}catch(e){toast(e.message,'error');}
}

// Bulk actions
function showBulkBar(){bulkSelected.clear();document.getElementById('bulk-bar').classList.add('visible');loadProducts();}
function clearBulk(){bulkSelected.clear();document.getElementById('bulk-bar').classList.remove('visible');document.getElementById('bulk-all').checked=false;loadProducts();}
function toggleBulkItem(id,checked){if(checked)bulkSelected.add(id);else bulkSelected.delete(id);document.getElementById('bulk-count').textContent=bulkSelected.size+' selected';}
function toggleBulkAll(cb){document.querySelectorAll('#products-body input[type=checkbox]').forEach(el=>{el.checked=cb.checked;const id=parseInt(el.closest('tr').querySelector('[onclick*=editProduct]')?.getAttribute('onclick')?.match(/\d+/)?.[0]);if(id){if(cb.checked)bulkSelected.add(id);else bulkSelected.delete(id);}});document.getElementById('bulk-count').textContent=bulkSelected.size+' selected';}
function bulkAction(type){
  if(!bulkSelected.size){toast('Select at least one product','error');return;}
  if(type==='delete'){if(!confirm(`Delete ${bulkSelected.size} products?`))return;executeBulkAction('delete');return;}
  bulkActionType=type;
  const titles={category:'Set Category',brand:'Set Brand',vendor:'Set Vendor'};
  const labels={category:'New Category',brand:'New Brand',vendor:'New Vendor'};
  setElText('bulk-modal-title', titles[type]||'Bulk Edit');
  setElText('bulk-modal-label', labels[type]||'New Value');
  // Show text input or vendor dropdown
  const inp=document.getElementById('bulk-modal-value');
  const sel=document.getElementById('bulk-modal-vendor');
  if(type==='vendor'){
    inp.style.display='none'; sel.style.display='';
    // Populate vendor dropdown
    api.get(API.vendors).then(r=>{
      sel.innerHTML='<option value="">— No Vendor —</option>'+
        r.data.map(v=>'<option value="'+v.id+'">'+esc(v.name)+(v.type?' ('+esc(v.type)+')':'')+' </option>').join('');
    }).catch(()=>{});
  } else {
    inp.style.display=''; sel.style.display='none'; inp.value='';
  }
  openModal('modal-bulk');
}
async function executeBulkAction(type){
  type=type||bulkActionType;
  let val;
  if(type==='delete') val=null;
  else if(type==='vendor') val=document.getElementById('bulk-modal-vendor').value||null;
  else val=document.getElementById('bulk-modal-value').value.trim();
  const ids=[...bulkSelected];
  try{
    await Promise.all(ids.map(id=>{
      if(type==='delete') return api.delete(API.products+'?id='+id);
      if(type==='vendor') return api.put(API.products,{id,_bulk:true,vendor_id:val});
      return api.put(API.products,{id,_bulk:true,[type]:val});
    }));
    toast(`Updated ${ids.length} products`);
    closeModal('modal-bulk'); clearBulk();
  }catch(e){toast(e.message,'error');}
}

// Barcode label print
function printBarcode(id,name,sku){
  const w=window.open('','_blank','width=400,height=300');
  w.document.write(`<!DOCTYPE html><html><head><title>Label</title><style>body{font-family:Arial;text-align:center;padding:20px}h3{margin:0 0 4px;font-size:14px}.sku{font-size:11px;color:#666;margin-bottom:10px}.barcode{font-family:'Libre Barcode 39',monospace;font-size:40px;letter-spacing:4px;line-height:1}@import url('https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap')</style></head><body><h3>${esc(name)}</h3><div class="sku">${esc(sku)}</div><div class="barcode">*${esc(sku||String(id))}*</div><button onclick="window.print()" style="margin-top:12px;padding:8px 20px;background:#4f8eff;color:#fff;border:none;border-radius:6px;cursor:pointer">Print</button>
</body></html>`);
}

// ══════════════════════════════════════════════════════════
// VENDORS
// ══════════════════════════════════════════════════════════
async function loadVendors(){
  const q=document.getElementById('vendor-search')?.value||'';
  const type=document.getElementById('vendor-type-filter')?.value||'';
  const params=new URLSearchParams();
  if(q)params.set('q',q);if(type)params.set('type',type);
  try{
    const r=await api.get(API.vendors+(params.toString()?'?'+params:''));
    const tbody=document.getElementById('vendors-body');
    const empty=document.getElementById('vendors-empty');
    const vt=document.getElementById('vendors-page-title');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';if(vt)vt.textContent='🏭 Vendors (0)';return;}
    empty.style.display='none';
    if(vt)vt.textContent='🏭 Vendors ('+r.total+')';
    const typeColors={'Fireworks':'badge-orange','Agent':'badge-purple','Both':'badge-blue','Other':'badge-gray'};
    tbody.innerHTML=r.data.map(v=>{
      const typeBadge=v.type?'<span class="badge '+( typeColors[v.type]||'badge-gray')+'">'+esc(v.type)+'</span>':'—';
      const addrDiv=v.address?'<div style="font-size:.73rem;color:var(--text3)">'+esc(v.address)+'</div>':'';
      const phoneLink=v.phone?'<a href="tel:'+esc(v.phone)+'" style="color:var(--accent)">'+esc(v.phone)+'</a>':'—';
      const emailLink=v.email?'<a href="mailto:'+esc(v.email)+'" style="color:var(--accent);font-size:.8rem">'+esc(v.email)+'</a>':'—';
      return `<tr>
      <td><div style="font-weight:600">${esc(v.name)}</div>${addrDiv}</td>
      <td>${typeBadge}</td>
      <td>${esc(v.contact||'—')}</td>
      <td>${phoneLink}</td>
      <td>${emailLink}</td>
      <td>${esc(v.city||'—')}</td>
      <td><span class="badge badge-blue">${v.product_count||0}</span></td>
      <td><button class="btn btn-ghost btn-xs" onclick="editVendor(${v.id})">✏️</button> ${CAN_DELETE?`<button class="btn btn-danger btn-xs" onclick="deleteVendor(${v.id},'${esc(v.name)}')">🗑️</button>`:""}</td>
    </tr>`;
    }).join('');
  }catch(e){toast(e.message,'error');}
}
function clearVendorForm(){
  ['v-edit-id','v-name','v-contact','v-phone','v-email','v-city','v-gst','v-address'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  document.getElementById('v-type').value='';
  setElText('vendor-form-title', '🏭 Add Vendor');
  document.getElementById('v-cancel-btn').style.display='none';
  setElText('v-save-btn', 'Save Vendor');
  setFormulaSteps([]);
  document.getElementById('v-case-margin').value='';
}
function cancelVendorEdit(){ clearVendorForm(); }
async function editVendor(id){
  try{
    const r=await api.get(API.vendors+'?id='+id);
    const v=r.data;
    document.getElementById('v-edit-id').value=v.id;
    ['name','contact','phone','email','city','gst','address'].forEach(f=>{const el=document.getElementById('v-'+f);if(el)el.value=v[f]||'';});
    document.getElementById('v-type').value=v.type||'';
    try{ setFormulaSteps(JSON.parse(v.pricing_formula||'[]')); }catch{ setFormulaSteps([]); }
    document.getElementById('v-case-margin').value = (v.case_margin!==null && v.case_margin!==undefined) ? v.case_margin : '';
    setElText('vendor-form-title', '🏭 Edit Vendor');
    document.getElementById('v-cancel-btn').style.display='';
    setElText('v-save-btn', 'Update Vendor');
    // Scroll form into view
    document.getElementById('v-name').scrollIntoView({behavior:'smooth',block:'center'});
    document.getElementById('v-name').focus();
  }catch(e){toast(e.message,'error');}
}
async function saveVendor(){
  const editId=parseInt(document.getElementById('v-edit-id').value)||0;
  const body={};['name','contact','phone','email','city','gst','address'].forEach(f=>{body[f]=document.getElementById('v-'+f)?.value.trim()||'';});
  body.type=document.getElementById('v-type')?.value||'';
  body.pricing_formula=JSON.stringify(getFormulaSteps('v-formula-steps'));
  body.case_margin=document.getElementById('v-case-margin')?.value||null;
  if(!body.name){toast('Vendor name required','error');return;}
  const btn=document.getElementById('v-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){body.id=editId;await api.put(API.vendors,body);toast('Vendor updated!');}
    else{await api.post(API.vendors,body);toast('Vendor added!');}
    delete _vendorDataCache[editId]; // invalidate cached formula/case-margin so new calculations use the latest
    clearVendorForm();
    loadVendors();
    populateVendorSelect('p-vendor');
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.textContent=document.getElementById('v-edit-id').value?'Update Vendor':'Save Vendor';}
}
async function deleteVendor(id,name){
  if(!confirm(`Delete "${name}"?`))return;
  try{await api.delete(API.vendors+'?id='+id);toast('Vendor deleted');loadVendors();}
  catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// PRODUCT LEDGER
// ══════════════════════════════════════════════════════════
let _plProductId=null;

function openProductLedger(productId,productName){
  _plProductId=productId;
  showPage('product-ledger');
  setElText('pl-product-name', '📦 '+productName+' — Ledger');
  setElText('pl-product-meta', '');
  const now=new Date();
  document.getElementById('pl-from').value=now.getFullYear()+'-01-01';
  document.getElementById('pl-to').value=now.toISOString().split('T')[0];
  loadProductLedger();
}

async function loadProductLedger(){
  if(!_plProductId) return;
  const from=document.getElementById('pl-from').value;
  const to=document.getElementById('pl-to').value;
  const params=new URLSearchParams({id:_plProductId});
  if(from) params.set('from',from);
  if(to)   params.set('to',to);
  try{
    const r=await api.get(API.productDetail+'?'+params);
    const d=r.data;
    const p=d.product;
    const s=d.summary;

    // Meta
    document.getElementById('pl-product-meta').textContent=
      [p.sku?'SKU: '+p.sku:'', p.brand||'', catLabel(p)||'', p.vendor_name||''].filter(Boolean).join(' · ');

    // Stat cards
    document.getElementById('pl-stats').innerHTML=
      statCard('Total In',s.total_in+' '+esc(p.unit||''),'var(--green)')
      +statCard('Total Out',s.total_out+' '+esc(p.unit||''),'var(--red)')
      +statCard('Current Stock',s.current_stock+' '+esc(p.unit||''),(s.current_stock>0?'var(--accent)':'var(--red)'))
      +(!HIDE_COST?statCard('Revenue',CUR.sym+fmtN(s.total_revenue),'var(--green)'):'')
      +(!HIDE_COST?statCard('Profit',CUR.sym+fmtN(s.total_profit),(s.total_profit>=0?'var(--green)':'var(--red)')):'')
      +statCard('Adjustments',(s.total_adj>=0?'+':'')+s.total_adj,'var(--yellow)');

    function statCard(label,val,color){
      return '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px">'
        +'<div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">'+label+'</div>'
        +'<div style="font-size:1.1rem;font-weight:800;font-family:var(--mono);color:'+color+'">'+val+'</div>'
        +'</div>';
    }

    // Stock by location
    document.getElementById('pl-locations').innerHTML=(d.locations||[]).map(function(l){
      const cls=l.stock<=0?'text-red':l.stock<=l.min_stock?'text-yellow':'text-green';
      const status=l.stock<=0?'Out':'Low Stock';
      return '<tr><td>'+esc(l.location_name)+(l.is_default?'<span class="badge badge-blue" style="font-size:.6rem;margin-left:4px">Primary</span>':'')+'</td>'
        +'<td class="mono '+cls+'" style="font-weight:700">'+l.stock+'</td>'
        +'<td class="mono" style="color:var(--text3)">'+l.min_stock+'</td>'
        +'<td>'+(l.stock<=0?'<span class="badge badge-red">Out</span>':l.stock<=l.min_stock?'<span class="badge badge-yellow">'+status+'</span>':'<span class="badge badge-green">OK</span>')+'</td>'
        +'</tr>';
    }).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--text3);padding:16px">No location data</td></tr>';

    // Open POs
    const pos=d.open_orders||[];
    const poEl=document.getElementById('pl-open-pos');
    const noPo=document.getElementById('pl-no-pos');
    if(!pos.length){poEl.parentElement.style.display='none';noPo.style.display='';}
    else{
      poEl.parentElement.style.display='';noPo.style.display='none';
      const statusCls={draft:'badge-gray',sent:'badge-blue',partial:'badge-yellow'};
      poEl.innerHTML=pos.map(function(po){
        return '<tr><td class="mono" style="color:var(--accent)">'+esc(po.po_number)+'</td>'
          +'<td style="font-size:.8rem">'+esc(po.vendor_name||'—')+'</td>'
          +'<td class="mono">'+po.qty_ordered+'</td>'
          +'<td class="mono text-green">'+po.qty_received+'</td>'
          +'<td class="mono text-orange" style="font-weight:700">'+po.pending_qty+'</td>'
          +'<td><span class="badge '+(statusCls[po.status]||'badge-gray')+'">'+po.status+'</span></td></tr>';
      }).join('');
    }

    // Unified ledger
    const TYPE_META={
      stock_in:  {label:'Stock In',   cls:'badge-green',  side:'in'},
      stock_out: {label:'Estimate/Sale',cls:'badge-orange',side:'out'},
      adjustment:{label:'Adjustment', cls:'badge-yellow', side:'adj'},
    };
    const txns=d.transactions||[];
    setElText('pl-txn-count', txns.length+' transaction'+(txns.length!==1?'s':''));
    const tbody=document.getElementById('pl-ledger-body');
    const tfoot=document.getElementById('pl-ledger-foot');
    const empty=document.getElementById('pl-ledger-empty');
    if(!txns.length){tbody.innerHTML='';tfoot.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';

    let runBal=0;
    let totalIn=0,totalOut=0;
    tbody.innerHTML=txns.map(function(t,i){
      const meta=TYPE_META[t.type]||{label:t.type,cls:'badge-gray',side:'in'};
      const qty=+t.qty||0;
      let inQty='',outQty='';
      if(meta.side==='in')       { runBal+=qty; totalIn+=qty; inQty='+'+qty; }
      else if(meta.side==='out') { runBal-=qty; totalOut+=qty; outQty=qty; }
      else { // adjustment
        const chg=+t.qty||0; runBal+=chg;
        if(chg>0) inQty='+'+chg; else outQty=Math.abs(chg);
      }
      const balCls=runBal<0?'text-red':runBal>0?'text-green':'text-muted';
      const bg=i%2===1?'background:rgba(255,255,255,.018)':'';
      return '<tr style="'+bg+'">'
        +'<td class="mono" style="font-size:.78rem;white-space:nowrap">'+esc(t.txn_date||'—')+'</td>'
        +'<td><span class="badge '+meta.cls+'">'+meta.label+'</span></td>'
        +'<td style="font-size:.8rem;max-width:180px">'+esc(t.description||'—')+'</td>'
        +'<td style="font-size:.78rem;color:var(--text2)">'+esc(t.vendor_name||t.customer||'—')+'</td>'
        +'<td style="font-size:.75rem;color:var(--text3)">'+esc(t.location_name||'—')+'</td>'
        +'<td class="mono" style="font-size:.73rem;color:var(--text3)">'+esc(t.po_number||'—')+'</td>'
        +'<td class="mono" style="text-align:right;color:var(--green);font-weight:600">'+inQty+'</td>'
        +'<td class="mono" style="text-align:right;color:var(--red);font-weight:600">'+outQty+'</td>'
        +'<td class="mono '+balCls+'" style="text-align:right;font-weight:700">'+runBal+'</td>'
        +'</tr>';
    }).join('');

    const footCls=runBal<0?'text-red':runBal>0?'text-green':'text-muted';
    tfoot.innerHTML='<tr style="background:var(--surface3);border-top:2px solid var(--border2);font-weight:700">'
      +'<td colspan="6" style="padding:8px 14px;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">Totals</td>'
      +'<td class="mono" style="text-align:right;color:var(--green);padding:8px 14px">+'+totalIn+'</td>'
      +'<td class="mono" style="text-align:right;color:var(--red);padding:8px 14px">'+totalOut+'</td>'
      +'<td class="mono '+footCls+'" style="text-align:right;font-size:.95rem;padding:8px 14px">'+runBal+'</td>'
      +'</tr>';

  }catch(e){toast(e.message,'error');}
}

function exportProductLedger(){
  const name=document.getElementById('pl-product-name').textContent.replace('📦 ','').replace(' — Ledger','');
  const from=document.getElementById('pl-from').value;
  const to=document.getElementById('pl-to').value;
  const headers=['Date','Type','Description','Vendor/Customer','Location','Ref','In','Out','Balance'];
  const rows=[];
  document.querySelectorAll('#pl-ledger-body tr').forEach(function(tr){
    const cells=tr.querySelectorAll('td');
    if(cells.length>=9) rows.push([cells[0].textContent.trim(),cells[1].textContent.trim(),cells[2].textContent.trim(),cells[3].textContent.trim(),cells[4].textContent.trim(),cells[5].textContent.trim(),cells[6].textContent.trim(),cells[7].textContent.trim(),cells[8].textContent.trim()]);
  });
  const csv=rowsToCsv([headers,...rows]);
  downloadCsv(csv, name.replace(/\s+/g,'_')+'_Ledger_'+from+'_'+to+'.csv');
  toast('Ledger exported! 📊');
}

// ══════════════════════════════════════════════════════════
let _vlrVendorId = null;

// ══════════════════════════════════════════════════════════
// PAYEE LEDGER
// ══════════════════════════════════════════════════════════
let _paylPayeeId=null;

function openPayeeLedger(payeeId, payeeName){
  _paylPayeeId=payeeId;
  showPage('payee-ledger');
  setElText('payl-name', '💳 '+payeeName+' — Ledger');
  setElText('payl-meta', '');
  const now=new Date();
  // Default to all-time — no date filter, so full ledger always shows
  document.getElementById('payl-from').value='';
  document.getElementById('payl-to').value='';
  loadPayeeLedger();
}

async function loadPayeeLedger(){
  if(!_paylPayeeId) return;
  const from=document.getElementById('payl-from').value;
  const to=document.getElementById('payl-to').value;
  const params=new URLSearchParams({id:_paylPayeeId});
  if(from) params.set('from',from);
  if(to)   params.set('to',to);
  try{
    const r=await api.get(API.payeeLedger+'?'+params);
    const d=r.data;
    const p=d.payee;
    const s=d.summary;
    const parts=[];
    if(p.type) parts.push(p.type);
    if(p.upi_id) parts.push('UPI: '+p.upi_id);
    if(p.bank_name) parts.push(p.bank_name+(p.account_no?' ****'+String(p.account_no).slice(-4):''));
    if(p.phone) parts.push('📞 '+p.phone);
    setElText('payl-meta', parts.join(' · '));
    const isFiltered = from||to;
    const allTimeNote = isFiltered && s.all_time_paid
      ? '<div style="font-size:.7rem;color:var(--text3);margin-top:2px">All-time: '+CUR.sym+fmtN(s.all_time_paid)+'</div>'
      : '';
    document.getElementById('payl-stats').innerHTML=
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Transactions</div><div style="font-size:1rem;font-weight:800;font-family:var(--mono);color:var(--accent)">'+s.txn_count+'</div>'+(isFiltered?'<div style="font-size:.7rem;color:var(--text3);margin-top:2px">'+(from||'start')+' → '+(to||'now')+'</div>':'')+'</div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Paid Out</div><div style="font-size:1rem;font-weight:800;font-family:var(--mono);color:var(--red)">'+CUR.sym+fmtN(s.total_paid)+'</div><div style="font-size:.7rem;color:var(--text3);margin-top:2px">Pmts: '+CUR.sym+fmtN(s.total_vp_paid||0)+' + Exp: '+CUR.sym+fmtN(s.total_expenses||0)+'</div>'+allTimeNote+'</div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Credit Notes</div><div style="font-size:1rem;font-weight:800;font-family:var(--mono);color:var(--green)">'+CUR.sym+fmtN(s.total_credits)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Last Transaction</div><div style="font-size:1rem;font-weight:800;font-family:var(--mono);color:var(--text2)">'+(s.last_txn_date||'—')+'</div></div>';
    const txns=d.transactions||[];
    setElText('payl-txn-count', txns.length+' transaction'+(txns.length!==1?'s':''));
    const tbody=document.getElementById('payl-body');
    const tfoot=document.getElementById('payl-foot');
    const empty=document.getElementById('payl-empty');
    if(!txns.length){tbody.innerHTML='';tfoot.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    // sign: +1 = paid out (debit), -1 = received (credit)
    const TYPE_META={payment:{label:'Payment',cls:'badge-red',sign:1},credit_note:{label:'Credit Note',cls:'badge-green',sign:-1},manual_purchase:{label:'Purchase',cls:'badge-orange',sign:1},opening_balance:{label:'Opening Bal',cls:'badge-blue',sign:1},expense:{label:'Expense',cls:'badge-orange',sign:1}};
    let running=0;
    tbody.innerHTML=txns.map(function(t,i){
      const meta=TYPE_META[t.type]||{label:t.type,cls:'badge-gray',sign:1};
      const amt=+t.amount||0;running+=meta.sign*amt;
      const runCls=running>0?'text-red':'text-green';
      const amtCls=meta.sign>0?'text-red':'text-green';
      const bg=i%2===1?'background:rgba(255,255,255,.018)':'';
      return '<tr style="'+bg+'">'
        +'<td class="mono" style="font-size:.78rem;white-space:nowrap">'+esc(t.txn_date||'—')+'</td>'
        +'<td><span class="badge '+meta.cls+'">'+meta.label+'</span></td>'
        +'<td style="font-size:.82rem">'+esc(t.vendor_name||'—')+'</td>'
        +'<td class="mono" style="font-size:.75rem;color:var(--text3)">'+esc(t.reference_no||'—')+'</td>'
        +'<td style="font-size:.8rem;color:var(--text2)">'+esc(t.description||'—')+'</td>'
        +'<td class="mono '+amtCls+'" style="text-align:right;font-weight:700">'+(meta.sign<0?'-':'+')+CUR.sym+fmtN(amt)+'</td>'
        +'<td class="mono '+runCls+'" style="text-align:right;font-weight:700">'+CUR.sym+fmtN(running)+'</td>'
        +'</tr>';
    }).join('');
    const footCls=running>0?'text-red':'text-green';
    tfoot.innerHTML='<tr style="background:var(--surface3);border-top:2px solid var(--border2);font-weight:700">'
      +'<td colspan="5" style="padding:8px 14px;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">Total</td>'
      +'<td class="mono text-red" style="text-align:right;padding:8px 14px">'+CUR.sym+fmtN(running)+'</td>'
      +'<td class="mono text-red" style="text-align:right;font-size:.95rem;padding:8px 14px">'+CUR.sym+fmtN(running)+'</td>'
      +'</tr>';
  }catch(e){toast(e.message,'error');}
}

function exportPayeeLedger(){
  const name=document.getElementById('payl-name').textContent.replace('💳 ','').replace(' — Ledger','');
  const from=document.getElementById('payl-from').value;const to=document.getElementById('payl-to').value;
  const headers=['Date','Type','Vendor','Reference','Description','Amount','Running Total'];
  const rows=[];
  document.querySelectorAll('#payl-body tr').forEach(function(tr){
    const cells=tr.querySelectorAll('td');
    if(cells.length>=7) rows.push([cells[0].textContent.trim(),cells[1].textContent.trim(),cells[2].textContent.trim(),cells[3].textContent.trim(),cells[4].textContent.trim(),cells[5].textContent.trim(),cells[6].textContent.trim()]);
  });
  const csv=rowsToCsv([headers,...rows]);
  downloadCsv(csv, name.replace(/\s+/g,'_')+'_Ledger.csv');
  toast('Exported! 📊');
}

function openVendorLedgerReport(vendorId, vendorName){
  _vlrVendorId = vendorId;
  showPage('vendor-ledger');
  setElText('vlr-vendor-name', '📒 ' + vendorName + ' — Ledger');
  setElText('vlr-vendor-meta', '');
  // Default date range: current year
  const now = new Date();
  // Default to all-time — no date filter
  document.getElementById('vlr-from').value = '';
  document.getElementById('vlr-to').value   = '';
  loadVendorLedgerReport();
}

async function loadVendorLedgerReport(){
  if(!_vlrVendorId) return;
  const from = document.getElementById('vlr-from').value;
  const to   = document.getElementById('vlr-to').value;
  try{
    const r = await api.get(API.vendorPayments+'?vendor_id='+_vlrVendorId);
    const d = r.data;
    const s = d.summary;
    const v = d.vendor;

    // Meta
    document.getElementById('vlr-vendor-meta').textContent =
      [v.type, v.city, v.phone].filter(Boolean).join(' · ');

    // Stat cards
    const bal = +s.balance;
    document.getElementById('vlr-stats').innerHTML =
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px">'
        +'<div style="font-size:.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:6px">Total Purchases</div>'
        +'<div style="font-size:1.2rem;font-weight:800;font-family:var(--mono)">'+CUR.sym+fmtN(s.total_purchases)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px">'
        +'<div style="font-size:.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:6px">Total Paid</div>'
        +'<div style="font-size:1.2rem;font-weight:800;font-family:var(--mono);color:var(--green)">'+CUR.sym+fmtN(s.total_paid)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:14px">'
        +'<div style="font-size:.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:6px">Credits</div>'
        +'<div style="font-size:1.2rem;font-weight:800;font-family:var(--mono);color:var(--accent)">'+CUR.sym+fmtN(s.total_credits)+'</div></div>'
      +'<div style="background:'+(bal>0?'rgba(239,68,68,.1)':bal<0?'rgba(34,197,94,.1)':'var(--surface2)')
        +';border-radius:var(--radius-sm);padding:14px;border:1px solid '+(bal>0?'rgba(239,68,68,.3)':bal<0?'rgba(34,197,94,.3)':'var(--border)')+'>">'
        +'<div style="font-size:.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:6px">Balance</div>'
        +'<div style="font-size:1.3rem;font-weight:800;font-family:var(--mono);color:'+(bal>0?'var(--red)':bal<0?'var(--green)':'var(--text3)')+'">'+CUR.sym+fmtN(Math.abs(bal))+(bal<0?' CR':'')+'</div>'
        +'<div style="font-size:.72rem;color:var(--text3);margin-top:3px">'+(bal>0?'Amount owed to vendor':bal<0?'Advance / Overpaid':'Settled')+'</div></div>';

    // Balance badge in header
    document.getElementById('vlr-balance-badge').textContent =
      (bal>0?'Outstanding: ':'Advance: ') + CUR.sym + fmtN(Math.abs(bal)) + (bal<0?' CR':'');
    document.getElementById('vlr-balance-badge').style.color = bal>0?'var(--red)':'var(--green)';

    // Build ledger rows
    const TYPE_META = {
      payment:          {label:'Payment',          cls:'badge-green',  side:'credit'},
      opening_balance:  {label:'Opening Balance',  cls:'badge-blue',   side:'debit'},
      credit_note:      {label:'Credit Note',      cls:'badge-orange', side:'credit'},
      purchase:         {label:'Purchase (PO)',    cls:'badge-gray',   side:'debit'},
      manual_purchase:  {label:'Purchase/Expense', cls:'badge-red',    side:'debit'},
    };

    const allTxns = [
      ...d.purchases.map(function(p){ return Object.assign({},p,{_rowType:'purchase'}); }),
      ...d.payments.map(function(p){ return Object.assign({},p,{_rowType:p.type}); }),
    ].sort(function(a,b){ return (a.txn_date||'').localeCompare(b.txn_date||'')||(+a.id||0)-(+b.id||0); });

    // Apply date filter for display only
    const filtered = allTxns.filter(function(t){
      if(from && t.txn_date < from) return false;
      if(to   && t.txn_date > to)   return false;
      return true;
    });

    // Compute opening running balance (before date range)
    let runBal = 0;
    if(from){
      allTxns.filter(function(t){ return t.txn_date < from; }).forEach(function(t){
        const m = TYPE_META[t._rowType]||TYPE_META.purchase;
        if(m.side==='debit') runBal+=+t.amount; else runBal-=+t.amount;
      });
    }

    const tbody = document.getElementById('vlr-body');
    const tfoot = document.getElementById('vlr-foot');
    const empty = document.getElementById('vlr-empty');

    if(!filtered.length){ tbody.innerHTML=''; tfoot.innerHTML=''; empty.style.display='block'; return; }
    empty.style.display='none';

    let totalDebit=0, totalCredit=0;
    tbody.innerHTML = filtered.map(function(t, i){
      const meta    = TYPE_META[t._rowType]||TYPE_META.purchase;
      const isDebit = meta.side==='debit';
      const amt     = +t.amount;
      if(isDebit){ runBal+=amt; totalDebit+=amt; } else { runBal-=amt; totalCredit+=amt; }
      const debit  = isDebit  ? CUR.sym+fmtN(amt) : '';
      const credit = !isDebit ? CUR.sym+fmtN(amt) : '';
      const balCls = runBal>0?'var(--red)':runBal<0?'var(--green)':'var(--text3)';
      const balStr = CUR.sym+fmtN(Math.abs(runBal))+(runBal<0?' CR':'');
      const bg     = i%2===1?'background:rgba(255,255,255,.018)':'';
      const isAdmin = ROLE==='admin';
      const canDelete = CAN_DELETE;
      const canAct  = t._rowType !== 'purchase';
      const editBtn = (canAct && isAdmin) ? '<button class="btn btn-ghost btn-xs" onclick="editVendorPayment('+t.id+','+_vlrVendorId+')" style="margin-right:4px">✏️</button>' : '';
      const delBtn  = (canAct && canDelete) ? '<button class="btn btn-danger btn-xs" onclick="deleteVendorPaymentFromLedger('+t.id+','+_vlrVendorId+')">🗑️</button>' : '';
      return '<tr style="'+bg+'">'
        +'<td class="mono" style="font-size:.78rem;white-space:nowrap">'+esc(t.txn_date||'—')+'</td>'
        +'<td><span class="badge '+meta.cls+'">'+meta.label+'</span></td>'
        +'<td style="font-size:.82rem">'+esc(t.description||t.notes||'—')+'</td>'
        +(function(t){var pn=t.payee_name||'';if(!pn)return '<td style="font-size:.78rem;color:var(--text2)">—</td>';var pt=t.payee_type||'';var sub=pt==='Cash'?'Cash':t.payee_bank?(esc(t.payee_bank)+(t.payee_account?' ****'+String(t.payee_account).slice(-4):'')):pt==='UPI'?'UPI':'';return '<td style="font-size:.78rem;color:var(--text2)">'+esc(pn)+(sub?'<br><span style="font-size:.7rem;color:var(--text3)">'+sub+'</span>':'')+'</td>';})(t)
        +'<td class="mono" style="font-size:.73rem;color:var(--text3)">'+esc(t.reference_no||'—')+'</td>'
        +'<td class="mono" style="text-align:right;color:var(--red);font-weight:600">'+debit+'</td>'
        +'<td class="mono" style="text-align:right;color:var(--green);font-weight:600">'+credit+'</td>'
        +'<td class="mono" style="text-align:right;font-weight:700;color:'+balCls+'">'+balStr+'</td>'
        +'<td style="white-space:nowrap">'+editBtn+delBtn+'</td>'
        +'</tr>';
    }).join('');

    // Totals footer
    const footBal = runBal;
    const footCls = footBal>0?'var(--red)':footBal<0?'var(--green)':'var(--text3)';
    tfoot.innerHTML = '<tr style="background:var(--surface3);border-top:2px solid var(--border2);font-weight:700">'
      +'<td colspan="5" style="padding:8px 14px;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">Total</td>'
      +'<td class="mono" style="text-align:right;color:var(--red);padding:8px 14px">'+CUR.sym+fmtN(totalDebit)+'</td>'
      +'<td class="mono" style="text-align:right;color:var(--green);padding:8px 14px">'+CUR.sym+fmtN(totalCredit)+'</td>'
      +'<td class="mono" style="text-align:right;font-size:.95rem;padding:8px 14px;color:'+footCls+'">'+CUR.sym+fmtN(Math.abs(footBal))+(footBal<0?' CR':'')+'</td>'
      +'<td></td>'
      +'</tr>';
  }catch(e){ toast(e.message,'error'); }
}



// ── Vendor Payment Edit (admin only) ─────────────────────────────────────────
async function deleteVendorPaymentFromLedger(id, vendorId){
  if(!confirm('Delete this payment entry? This cannot be undone.')) return;
  try{
    await api.delete(API.vendorPayments+'?id='+id);
    toast('Entry deleted');
    refreshVendorLedger(vendorId);
  }catch(e){ toast(e.message,'error'); }
}

function vpeTogglePayee(type){
  var row   = document.getElementById('vpe-payee-row');
  var label = row ? row.querySelector('.form-label') : null;
  if(!row) return;
  var hidePayee    = (type === 'manual_purchase');
  var requirePayee = (type === 'payment' || type === 'opening_balance');
  var payeeLabel   = type === 'payment'          ? 'Paid by'
                   : type === 'credit_note'       ? 'Paid to'
                   : type === 'opening_balance'   ? 'Account'
                   : 'Payee / Account';
  row.style.display = hidePayee ? 'none' : '';
  if(label) label.innerHTML = requirePayee
    ? payeeLabel + ' <span style="color:var(--red)">*</span>'
    : payeeLabel;
  row.dataset.required = requirePayee ? '1' : '';
}

function editVendorPayment(id, vendorId){
  document.getElementById('vpe-id').value = id;
  document.getElementById('vpe-vendor-id').value = vendorId;
  // Load payees and payment data in parallel
  Promise.all([
    api.get(API.vendorPayments + '?id=' + id),
    api.get(API.payees + '?active_only=1').catch(function(){ return {data:[]}; })
  ]).then(function(results){
    var p       = results[0].data;
    var payees  = results[1].data || [];
    // Populate payee dropdown
    var paySel = document.getElementById('vpe-payee');
    paySel.innerHTML = '<option value="">— No Payee —</option>'
      + payees.map(function(py){
          var label = esc(py.name);
          if(py.type) label += ' (' + esc(py.type) + ')';
          if(py.bank_name) label += ' — ' + esc(py.bank_name);
          return '<option value="'+py.id+'"'+(String(py.id)===String(p.payee_id)?' selected':'')+'>'+label+'</option>';
        }).join('');
    document.getElementById('vpe-type').value   = p.type || 'payment';
    document.getElementById('vpe-date').value   = p.payment_date || '';
    document.getElementById('vpe-amount').value = p.amount || '';
    document.getElementById('vpe-ref').value    = p.reference_no || '';
    document.getElementById('vpe-notes').value  = p.notes || '';
    vpeTogglePayee(p.type || 'payment'); // show/hide payee based on type
    openModal('modal-vp-edit');
  }).catch(function(e){ toast(e.message,'error'); });
}

async function saveVendorPaymentEdit(){
  var id       = document.getElementById('vpe-id').value;
  var vendorId = document.getElementById('vpe-vendor-id').value;
  var amount   = document.getElementById('vpe-amount').value;
  var date     = document.getElementById('vpe-date').value;
  var type = document.getElementById('vpe-type').value;
  var payeeRow = document.getElementById('vpe-payee-row');
  var payeeVal = document.getElementById('vpe-payee').value;
  var payeeRequired = payeeRow && payeeRow.dataset.required === '1';
  if(!amount||!date){ toast('Amount and date are required','error'); return; }
  if(payeeRequired && !payeeVal){ toast('Payee is required for '+type.replace('_',' '),'error'); return; }
  try{
    await api.put(API.vendorPayments, {
      id:           +id,
      amount:       +amount,
      txn_date:     date,
      type:         document.getElementById('vpe-type').value,
      reference_no: document.getElementById('vpe-ref').value,
      notes:        document.getElementById('vpe-notes').value,
      payee_id:     document.getElementById('vpe-payee').value || null,
    });
    closeModal('modal-vp-edit');
    toast('Payment updated ✅');
    // Refresh the correct ledger view
    if(_vlrVendorId && String(_vlrVendorId)===String(vendorId)){
      loadVendorLedgerReport(); // re-render Transaction Ledger in-place, heading preserved
    } else {
      // For vendor payments panel — read existing title to preserve vendor name
      var vpTitle = document.getElementById('vp-ledger-title');
      var currentVpName = vpTitle ? vpTitle.textContent.replace('📒 ','').replace(' — Ledger','').trim() : '';
      if(!currentVpName){
        // Fallback: read from vlr heading
        var vlrHead = document.getElementById('vlr-vendor-name');
        currentVpName = vlrHead ? vlrHead.textContent.replace('📒 ','').replace(' — Ledger','').trim() : '';
      }
      openVendorLedger(+vendorId, currentVpName);
    }
  }catch(e){ toast(e.message,'error'); }
}

function exportVendorLedger(){
  if(!_vlrVendorId) return;
  const vendorName = document.getElementById('vlr-vendor-name').textContent.replace('📒 ','').replace(' — Ledger','');
  const from = document.getElementById('vlr-from').value;
  const to   = document.getElementById('vlr-to').value;
  const headers = ['Date','Type','Description','Payee','Ref No.','Debit','Credit','Balance'];
  const rows = [];
  document.querySelectorAll('#vlr-body tr').forEach(function(tr){
    const cells = tr.querySelectorAll('td');
    if(cells.length >= 8){
      rows.push([cells[0].textContent.trim(),cells[1].textContent.trim(),cells[2].textContent.trim(),
        cells[3].textContent.trim(),cells[4].textContent.trim(),cells[5].textContent.trim(),
        cells[6].textContent.trim(),cells[7].textContent.trim()]);
    }
  });
  const csv=rowsToCsv([headers,...rows]);
  downloadCsv(csv, vendorName.replace(/\s+/g,'_')+'_Ledger_'+from+'_'+to+'.csv');
  toast('Ledger exported!');
}

async function loadPayees(){
  const q=document.getElementById('payee-search')?.value||'';
  try{
    const r=await api.get(API.payees+(q?'?q='+encodeURIComponent(q):''));
    const tbody=document.getElementById('payees-body');
    const empty=document.getElementById('payees-empty');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=r.data.map(p=>'<tr>'
      +'<td><div style="font-weight:600">'+esc(p.name)+'</div>'+(p.notes?'<div style="font-size:.73rem;color:var(--text3)">'+esc(p.notes)+'</div>':'')+'</td>'
      +'<td><span class="badge badge-blue">'+esc(p.type||'—')+'</span></td>'
      +'<td style="font-size:.78rem;color:var(--text2)">'+(p.bank_name?esc(p.bank_name)+(p.account_no?' · ****'+String(p.account_no).slice(-4):''):p.upi_id?esc(p.upi_id):'—')+'</td>'
      +'<td style="font-size:.8rem">'+esc(p.phone||'—')+'</td>'
      +'<td class="mono">'+p.payment_count+'</td>'
      +'<td class="mono text-green">'+CUR.sym+fmtN(p.total_paid)+'</td>'
      +'<td><span class="badge '+(+p.is_active?'badge-green':'badge-red')+'">'+( +p.is_active?'Active':'Inactive')+'</span></td>'
      +'<td><button class="btn btn-ghost btn-xs" onclick="openPayeeLedger('+p.id+',\''+p.name.replace(/'/g,"\\'")+'\')" title="View Ledger">📒</button> '
      +'<button class="btn btn-ghost btn-xs" onclick="editPayee('+p.id+')">✏️</button>'
      +(CAN_DELETE&&+p.payment_count===0?'<button class="btn btn-danger btn-xs" onclick="deletePayee('+p.id+',\''+esc(p.name)+'\')">🗑️</button>':'')
      +'</td></tr>'
    ).join('');
  }catch(e){toast(e.message,'error');}
}
// ══════════════════════════════════════════════════════════
// PAYEE TYPES (custom list, stored in localStorage)
// ══════════════════════════════════════════════════════════
const PAYEE_TYPES_KEY = 'invyrr_payee_types';
const PAYEE_TYPES_HIDDEN_KEY = 'invyrr_payee_types_hidden';
const DEFAULT_PAYEE_TYPES = ['Person','Bank Account','UPI','Cash','Cheque','Other'];

function getHiddenDefaultTypes(){
  try{ return JSON.parse(localStorage.getItem(PAYEE_TYPES_HIDDEN_KEY)||'[]'); }
  catch(e){ return []; }
}

function saveHiddenDefaultTypes(list){
  localStorage.setItem(PAYEE_TYPES_HIDDEN_KEY, JSON.stringify(list));
}

function getPayeeTypes(){
  try{
    const hidden = getHiddenDefaultTypes();
    const stored = JSON.parse(localStorage.getItem(PAYEE_TYPES_KEY)||'[]');
    const merged = DEFAULT_PAYEE_TYPES.filter(t=>!hidden.includes(t));
    stored.forEach(t=>{ if(!merged.includes(t)) merged.push(t); });
    return merged;
  }catch(e){ return [...DEFAULT_PAYEE_TYPES]; }
}

function getCustomPayeeTypes(){
  try{ return JSON.parse(localStorage.getItem(PAYEE_TYPES_KEY)||'[]'); }
  catch(e){ return []; }
}

function saveCustomPayeeTypes(list){
  localStorage.setItem(PAYEE_TYPES_KEY, JSON.stringify(list));
}

function populatePayeeTypeSelect(selectedValue){
  const sel = document.getElementById('payee-type');
  if(!sel) return;
  const cur = selectedValue !== undefined ? selectedValue : sel.value;
  sel.innerHTML = getPayeeTypes().map(t=>'<option value="'+esc(t)+'">'+esc(t)+'</option>').join('');
  if(cur) sel.value = cur;
}

// Ensures a (possibly legacy/unrecognized) saved type appears as a selectable option
function ensurePayeeTypeOption(type){
  if(!type) return;
  const types = getPayeeTypes();
  if(!types.includes(type)){
    const custom = getCustomPayeeTypes();
    custom.push(type);
    saveCustomPayeeTypes(custom);
  }
  populatePayeeTypeSelect(type);
}

function openPayeeTypeModal(){
  loadPayeeTypeList();
  document.getElementById('new-payee-type-input').value='';
  openModal('modal-payee-types');
}

function loadPayeeTypeList(){
  const el = document.getElementById('payee-type-list');
  if(!el) return;
  const defaults = DEFAULT_PAYEE_TYPES;
  const custom = getCustomPayeeTypes();
  const all = getPayeeTypes();
  el.innerHTML = all.map(function(t){
    const isDefault = defaults.includes(t);
    return '<div class="payee-type-row" style="display:flex;align-items:center;gap:8px;padding:7px 4px;border-bottom:1px solid var(--border)">'
      +'<span style="flex:1;font-size:.85rem">'+esc(t)+(isDefault?' <span style="color:var(--text3);font-size:.7rem">(default)</span>':'')+'</span>'
      +'<button class="btn btn-ghost btn-xs payee-type-rename" data-type="'+esc(t)+'" title="Rename">✏️</button>'
      +'<button class="btn btn-danger btn-xs payee-type-delete" data-type="'+esc(t)+'" title="Delete">🗑️</button>'
      +'</div>';
  }).join('');
  el.querySelectorAll('.payee-type-rename').forEach(function(btn){
    btn.addEventListener('click', function(){ renamePayeeType(btn.dataset.type); });
  });
  el.querySelectorAll('.payee-type-delete').forEach(function(btn){
    btn.addEventListener('click', function(){ deletePayeeType(btn.dataset.type); });
  });
}

function saveNewPayeeType(){
  const input = document.getElementById('new-payee-type-input');
  const name = input.value.trim();
  if(!name){ toast('Enter a type name','error'); return; }
  const all = getPayeeTypes();
  if(all.some(t=>t.toLowerCase()===name.toLowerCase())){
    toast('That type already exists','error'); return;
  }
  const custom = getCustomPayeeTypes();
  custom.push(name);
  saveCustomPayeeTypes(custom);
  input.value='';
  loadPayeeTypeList();
  populatePayeeTypeSelect(name);
  toast('Payee type added');
}

function renamePayeeType(oldName){
  const isDefault = DEFAULT_PAYEE_TYPES.includes(oldName);
  const newName = prompt('Rename payee type:', oldName);
  if(!newName || !newName.trim() || newName.trim()===oldName) return;
  const trimmed = newName.trim();

  if(isDefault){
    // Hide the default and add the new name as a custom type
    const hidden = getHiddenDefaultTypes();
    if(!hidden.includes(oldName)){ hidden.push(oldName); saveHiddenDefaultTypes(hidden); }
    const custom = getCustomPayeeTypes();
    if(!custom.includes(trimmed)) custom.push(trimmed);
    saveCustomPayeeTypes(custom);
  } else {
    let custom = getCustomPayeeTypes();
    const idx = custom.indexOf(oldName);
    if(idx===-1) return;
    custom[idx] = trimmed;
    saveCustomPayeeTypes(custom);
  }
  loadPayeeTypeList();
  populatePayeeTypeSelect();
  toast('Payee type renamed — note: existing payees keep their old type name until edited');
}

function deletePayeeType(name){
  const isDefault = DEFAULT_PAYEE_TYPES.includes(name);
  if(!confirm('Delete payee type "'+name+'"? Existing payees using this type will keep it until edited.')) return;

  if(isDefault){
    const hidden = getHiddenDefaultTypes();
    if(!hidden.includes(name)){ hidden.push(name); saveHiddenDefaultTypes(hidden); }
  } else {
    let custom = getCustomPayeeTypes();
    custom = custom.filter(t=>t!==name);
    saveCustomPayeeTypes(custom);
  }
  loadPayeeTypeList();
  populatePayeeTypeSelect();
  toast('Payee type deleted');
}

function restoreDefaultPayeeTypes(){
  if(!confirm('Restore all default payee types (Person, Bank Account, UPI, Cash, Cheque, Other)?')) return;
  saveHiddenDefaultTypes([]);
  loadPayeeTypeList();
  populatePayeeTypeSelect();
  toast('Default payee types restored');
}

async function editPayee(id){
  const r=await api.get(API.payees+'?id='+id);
  const p=r.data;
  document.getElementById('payee-edit-id').value=p.id;
  document.getElementById('payee-name').value=p.name||'';
  ensurePayeeTypeOption(p.type||'Person');
  document.getElementById('payee-type').value=p.type||'Person';
  document.getElementById('payee-phone').value=p.phone||'';
  document.getElementById('payee-bank-name').value=p.bank_name||'';
  document.getElementById('payee-account-no').value=p.account_no||'';
  document.getElementById('payee-ifsc').value=p.ifsc||'';
  document.getElementById('payee-upi-id').value=p.upi_id||'';
  document.getElementById('payee-notes').value=p.notes||'';
  document.getElementById('payee-active').checked=!!+p.is_active;
  setElText('payee-form-title', '💳 Edit Payee');
  document.getElementById('payee-cancel-btn').style.display='';
  setElText('payee-save-btn', 'Update Payee');
  document.getElementById('payee-name').scrollIntoView({behavior:'smooth',block:'center'});
  document.getElementById('payee-name').focus();
}
function cancelPayeeEdit(){
  ['payee-edit-id','payee-name','payee-phone','payee-bank-name','payee-account-no','payee-ifsc','payee-upi-id','payee-notes'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  populatePayeeTypeSelect('Person');
  document.getElementById('payee-active').checked=true;
  setElText('payee-form-title', '💳 Add Payee');
  document.getElementById('payee-cancel-btn').style.display='none';
  setElText('payee-save-btn', 'Save Payee');
}
async function savePayee(){
  const name=document.getElementById('payee-name').value.trim();
  if(!name){toast('Name required','error');return;}
  const editId=parseInt(document.getElementById('payee-edit-id').value)||0;
  const body={name,type:document.getElementById('payee-type').value,phone:document.getElementById('payee-phone').value.trim(),bank_name:document.getElementById('payee-bank-name').value.trim(),account_no:document.getElementById('payee-account-no').value.trim(),ifsc:document.getElementById('payee-ifsc').value.trim(),upi_id:document.getElementById('payee-upi-id').value.trim(),notes:document.getElementById('payee-notes').value.trim(),is_active:document.getElementById('payee-active').checked?1:0};
  const btn=document.getElementById('payee-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){await api.put(API.payees,{...body,id:editId});toast('Payee updated!');}
    else{await api.post(API.payees,body);toast('Payee added!');}
    cancelPayeeEdit();loadPayees();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.textContent=document.getElementById('payee-edit-id').value?'Update Payee':'Save Payee';}
}
async function deletePayee(id,name){
  if(!confirm('Delete "'+name+'"?'))return;
  try{await api.delete(API.payees+'?id='+id);toast('Payee deleted');loadPayees();}
  catch(e){toast(e.message,'error');}
}
async function populatePayeeSelect(selId, emptyLabel){
  const r=await api.get(API.payees+'?active_only=1').catch(()=>({data:[]}));
  const sel=document.getElementById(selId);
  if(!sel)return;
  sel.innerHTML='<option value="">'+(emptyLabel||'— Select Payee —')+'</option>'+r.data.map(p=>'<option value="'+p.id+'">'+esc(p.name)+(p.type?' ('+esc(p.type)+')':'')+'</option>').join('');
}

// ══════════════════════════════════════════════════════════
// VENDOR PAYMENTS
// ══════════════════════════════════════════════════════════
async function loadVendorPaymentsSummary(){
  const q=document.getElementById('vp-vendor-search')?.value||'';
  const bf=document.getElementById('vp-balance-filter')?.value||'';
  try{
    const r=await api.get(API.vendorPayments+'?summary=1');
    let rows=r.data;
    if(q) rows=rows.filter(v=>v.name.toLowerCase().includes(q.toLowerCase()));
    if(bf==='outstanding') rows=rows.filter(v=>+v.balance>0);
    if(bf==='clear') rows=rows.filter(v=>+v.balance<=0);
    const tbody=document.getElementById('vp-summary-body');
    const empty=document.getElementById('vp-summary-empty');
    if(!rows.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=rows.map(v=>{
      const bal=+v.balance;
      const balCls=bal>0?'text-red':bal<0?'text-green':'text-muted';
      return '<tr>'
        +'<td><div style="font-weight:600">'+esc(v.name)+'</div></td>'
        +'<td style="font-size:.78rem;color:var(--text2)">'+esc(v.type||'—')+'</td>'
        +'<td class="mono">'+CUR.sym+fmtN(v.opening_balance)+'</td>'
        +'<td class="mono">'+CUR.sym+fmtN(v.total_purchases)+'</td>'
        +'<td class="mono text-green">'+CUR.sym+fmtN(v.total_paid)+'</td>'
        +'<td class="mono text-accent">'+CUR.sym+fmtN(v.total_credits)+'</td>'
        +'<td class="mono '+balCls+'" style="font-weight:700">'+CUR.sym+fmtN(Math.abs(bal))+(bal<0?' <span style="font-size:.7rem">(Advance)</span>':'')+'</td>'
        +'<td style="font-size:.78rem;color:var(--text3)">'+esc(v.last_payment_date||'—')+'</td>'
        +'<td><button class="btn btn-primary btn-xs" onclick="openVendorLedger('+v.id+',\''+esc(v.name)+'\')">Pay</button> <button class="btn btn-ghost btn-xs" onclick="openVendorLedgerReport('+v.id+',\''+esc(v.name)+'\')">📒</button></td>'
        +'</tr>';
    }).join('');
  }catch(e){toast(e.message,'error');}
}
async function openVendorLedger(vendorId,vendorName){
  document.getElementById('vp-ledger-section').style.display='';
  setElText('vp-form-vendor-name', '💳 '+vendorName);
  setElText('vp-ledger-title', '📒 '+vendorName+' — Ledger');
  document.getElementById('vp-vendor-id').value=vendorId;
  document.getElementById('vp-date').value=new Date().toISOString().split('T')[0];
  document.getElementById('vp-amount').value='';document.getElementById('vp-amount-words').textContent='';
  document.getElementById('vp-ref').value='';
  document.getElementById('vp-notes').value='';
  document.getElementById('vp-desc').value='';
  document.getElementById('vp-type').value='payment';
  onVPTypeChange();
  await populatePayeeSelect('vp-payee');
  // Set vendor info line
  try{
    const vr=await api.get(API.vendors+'?id='+vendorId);
    const v=vr.data;
    const infoEl=document.getElementById('vp-ledger-vendor-info');
    if(infoEl) infoEl.textContent=[v.type,v.phone,v.city].filter(Boolean).join(' · ');
  }catch{}
  document.getElementById('vp-ledger-section').scrollIntoView({behavior:'smooth',block:'start'});
  await refreshVendorLedger(vendorId);
}

function printVendorLedger(){
  const vendorName = document.getElementById('vp-ledger-title').textContent.replace('📒 ','');
  const vendorInfo = document.getElementById('vp-ledger-vendor-info').textContent;
  const table      = document.getElementById('vp-ledger-table');
  const summary    = document.getElementById('vp-balance-summary');
  const from       = document.getElementById('vp-ledger-from').value;
  const to         = document.getElementById('vp-ledger-to').value;
  const dateRange  = (from||to) ? ((from||'Start')+' → '+(to||'Today')) : 'All Transactions';
  const w = window.open('','_blank');
  w.document.write(`<!DOCTYPE html><html><head><title>${vendorName}</title>
  <style>
    body{font-family:Arial,sans-serif;font-size:12px;color:#111;margin:24px}
    h2{margin:0 0 4px;font-size:16px}
    .info{color:#555;font-size:11px;margin-bottom:4px}
    .range{color:#555;font-size:11px;margin-bottom:16px}
    table{width:100%;border-collapse:collapse;margin-bottom:20px}
    th{background:#1e3a5f;color:#fff;padding:7px 10px;text-align:left;font-size:11px}
    th.r,td.r{text-align:right}
    td{padding:6px 10px;border-bottom:1px solid #e0e0e0;font-size:11px}
    tr:nth-child(even) td{background:#f7f9fc}
    tfoot td{font-weight:700;background:#eef2f8;border-top:2px solid #1e3a5f}
    .badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600}
    .b-green{background:#d1fae5;color:#065f46}
    .b-gray{background:#f3f4f6;color:#374151}
    .b-blue{background:#dbeafe;color:#1e40af}
    .b-orange{background:#ffedd5;color:#9a3412}
    .b-red{background:#fee2e2;color:#991b1b}
    .summary{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px;padding:12px;background:#f7f9fc;border:1px solid #e0e0e0;border-radius:6px}
    .s-label{font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.5px}
    .s-val{font-size:15px;font-weight:700;margin-top:2px}
    .red{color:#dc2626} .green{color:#16a34a}
    @media print{button{display:none}}
  </style></head><body>

  <h2>${vendorName}</h2>
  <div class="info">${vendorInfo}</div>
  <div class="range">Period: ${dateRange} | Generated: ${new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'})}</div>
  <div class="summary">${summary.innerHTML}</div>
  ${table.outerHTML}
  <button onclick="window.print()" style="padding:8px 20px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨️ Print</button>
  
</body></html>`);
  w.document.close();
  setTimeout(()=>w.print(),600);
}

function closeVendorLedger(){
  document.getElementById('vp-ledger-section').style.display='none';
}
async function refreshVendorLedger(vendorId){
  if(!vendorId) return;
  try{
    const r=await api.get(API.vendorPayments+'?vendor_id='+vendorId);
    const d=r.data;
    const s=d.summary;
    // Balance summary box
    document.getElementById('vp-balance-summary').innerHTML=
      '<div style="color:var(--text3)">Purchases</div><div class="mono" style="font-weight:700">'+CUR.sym+fmtN(s.total_purchases)+'</div>'
      +'<div style="color:var(--text3)">Total Paid</div><div class="mono text-green" style="font-weight:700">'+CUR.sym+fmtN(s.total_paid)+'</div>'
      +'<div style="color:var(--text3)">Credits</div><div class="mono text-accent" style="font-weight:700">'+CUR.sym+fmtN(s.total_credits)+'</div>'
      +'<div style="color:var(--text3);font-weight:600;border-top:1px solid var(--border);padding-top:6px;margin-top:2px">Balance</div>'
      +'<div class="mono '+(+s.balance>0?'text-red':+s.balance<0?'text-green':'text-muted')+'" style="font-weight:800;font-size:1.05rem;border-top:1px solid var(--border);padding-top:6px;margin-top:2px">'+CUR.sym+fmtN(Math.abs(s.balance))+(+s.balance<0?' CR':'')+' </div>';
    // Build unified sorted ledger
    const TYPE_META={
      payment:         {label:'Payment',        cls:'badge-green',  side:'credit'},
      opening_balance: {label:'Opening Bal',    cls:'badge-blue',   side:'debit'},
      credit_note:     {label:'Credit Note',    cls:'badge-orange', side:'credit'},
      purchase:        {label:'Purchase (PO)',  cls:'badge-gray',   side:'debit'},
      manual_purchase: {label:'Purchase/Expense',cls:'badge-red',  side:'debit'},
    };
    const allTxns=[
      ...d.purchases.map(function(p){ return Object.assign({},p,{_rowType:'purchase'}); }),
      ...d.payments.map(function(p){ return Object.assign({},p,{_rowType:p.type}); }),
    ].sort(function(a,b){ return (a.txn_date||'').localeCompare(b.txn_date||'')||(+a.id||0)-(+b.id||0); });
    const from=document.getElementById('vp-ledger-from')?.value||'';
    const to=document.getElementById('vp-ledger-to')?.value||'';
    // Compute running balance before date filter
    let runBal=0;
    if(from){
      allTxns.filter(function(t){return t.txn_date<from;}).forEach(function(t){
        const m=TYPE_META[t._rowType]||TYPE_META.purchase;
        if(m.side==='debit') runBal+=+t.amount; else runBal-=+t.amount;
      });
    }
    const filtered=allTxns.filter(function(t){
      if(from&&t.txn_date<from)return false;
      if(to&&t.txn_date>to)return false;
      return true;
    });
    const tbody=document.getElementById('vp-ledger-body');
    const tfoot=document.getElementById('vp-ledger-foot');
    const empty=document.getElementById('vp-ledger-empty');
    if(!filtered.length){tbody.innerHTML='';if(tfoot)tfoot.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    let totalDebit=0,totalCredit=0;
    tbody.innerHTML=filtered.map(function(t,i){
      const meta=TYPE_META[t._rowType]||TYPE_META.purchase;
      const isDebit=meta.side==='debit';
      const amt=+t.amount;
      if(isDebit){runBal+=amt;totalDebit+=amt;}else{runBal-=amt;totalCredit+=amt;}
      const debit=isDebit?CUR.sym+fmtN(amt):'';
      const credit=!isDebit?CUR.sym+fmtN(amt):'';
      const balCls=runBal>0?'text-red':runBal<0?'text-green':'text-muted';
      const balStr=CUR.sym+fmtN(Math.abs(runBal))+(runBal<0?' CR':'');
      const isAdmin = ROLE==='admin';
      const canDelete = CAN_DELETE;
      const delBtn = (CAN_DELETE && t._rowType!=='purchase') ? '<button class="btn btn-danger btn-xs" onclick="deleteVendorPayment('+t.id+','+vendorId+')">🗑️</button>' : '';
      const editBtn = (t._rowType!=='purchase' && isAdmin) ? '<button class="btn btn-ghost btn-xs" onclick="editVendorPayment('+t.id+','+vendorId+')" style="margin-right:4px">✏️</button>' : '';
      const bg=i%2===1?'background:rgba(255,255,255,.02)':'';
      return '<tr style="'+bg+'">'
        +'<td class="mono" style="font-size:.77rem;white-space:nowrap">'+esc(t.txn_date||'—')+'</td>'
        +'<td><span class="badge '+meta.cls+'">'+meta.label+'</span></td>'
        +'<td style="font-size:.8rem;max-width:180px;font-weight:'+(t._rowType==='manual_purchase'?'600':'400')+'">'+esc(t.description||t.notes||'—')+'</td>'
        +(function(t){var pn=t.payee_name||'';if(!pn)return '<td style="font-size:.78rem;color:var(--text2)">—</td>';var pt=t.payee_type||'';var sub=pt==='Cash'?'Cash':t.payee_bank?(esc(t.payee_bank)+(t.payee_account?' ****'+String(t.payee_account).slice(-4):'')):pt==='UPI'?'UPI':'';return '<td style="font-size:.78rem;color:var(--text2)">'+esc(pn)+(sub?'<br><span style="font-size:.7rem;color:var(--text3)">'+sub+'</span>':'')+'</td>';})(t)
        
        +'<td class="mono" style="font-size:.73rem;color:var(--text3)">'+esc(t.reference_no||'—')+'</td>'
        +'<td class="mono" style="text-align:right;color:var(--red);font-weight:600">'+debit+'</td>'
        +'<td class="mono" style="text-align:right;color:var(--green);font-weight:600">'+credit+'</td>'
        +'<td class="mono '+balCls+'" style="text-align:right;font-weight:700">'+balStr+'</td>'
        +'<td style="white-space:nowrap">'+editBtn+delBtn+'</td>'
        +'</tr>';
    }).join('');
    // Totals footer
    if(tfoot){
      const footBal=runBal;const footCls=footBal>0?'text-red':footBal<0?'text-green':'text-muted';
      tfoot.innerHTML='<tr style="background:var(--surface3);border-top:2px solid var(--border2)">'
        +'<td colspan="5" style="padding:8px 14px;font-weight:700;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;color:var(--text2)">Totals</td>'
        +'<td class="mono" style="text-align:right;font-weight:700;color:var(--red);padding:8px 14px">'+CUR.sym+fmtN(totalDebit)+'</td>'
        +'<td class="mono" style="text-align:right;font-weight:700;color:var(--green);padding:8px 14px">'+CUR.sym+fmtN(totalCredit)+'</td>'
        +'<td class="mono '+footCls+'" style="text-align:right;font-weight:800;font-size:.95rem;padding:8px 14px">'+CUR.sym+fmtN(Math.abs(footBal))+(footBal<0?' CR':'')+' </td>'
        +'<td style="padding:8px 14px"></td></tr>';
    }
  }catch(e){toast(e.message,'error');}
}
function togglePayeePanel(){
  const panel=document.getElementById('vp-payee-panel');
  const show=panel.style.display==='none';
  panel.style.display=show?'':'none';
  if(show){loadPayees();panel.scrollIntoView({behavior:'smooth',block:'start'});}
}
function onVPTypeChange(){
  const type = document.getElementById('vp-type').value;
  const payeeLabel = document.getElementById('vp-payee-label');
  const descGroup  = document.getElementById('vp-desc-group');
  const descLabel  = document.getElementById('vp-desc-label');
  const payeeGroup = document.getElementById('vp-payee-group');
  // purchase/expense: debit side — show description prominently, payee optional
  if(type==='manual_purchase'){
    if(payeeLabel) payeeLabel.firstChild.textContent='Paid By';
    if(descGroup)  descGroup.style.display='';
    if(descLabel)  descLabel.textContent='Description *';
    document.getElementById('vp-desc').placeholder='e.g. Transport charges, Labour, Packaging';
  } else if(type==='credit_note'){
    if(payeeLabel) payeeLabel.firstChild.textContent='Paid to * ';
    if(descGroup)  descGroup.style.display='none';
    document.getElementById('vp-desc').value='';
  } else {
    if(payeeLabel) payeeLabel.firstChild.textContent='Paid By * ';
    if(descGroup)  descGroup.style.display='none';
    document.getElementById('vp-desc').value='';
  }
}
async function saveVendorPayment(){
  const vendorId = document.getElementById('vp-vendor-id').value;
  const amount   = document.getElementById('vp-amount').value;
  const date     = document.getElementById('vp-date').value;
  const type     = document.getElementById('vp-type').value;
  const desc     = document.getElementById('vp-desc')?.value.trim()||'';
  if(!vendorId||!amount||!date){toast('Vendor, amount and date are required','error');return;}
  if(type==='purchase'&&!desc){toast('Description is required for purchases/expenses','error');return;}
  const body={
    vendor_id:vendorId, amount, payment_date:date, type,
    payee_id:document.getElementById('vp-payee').value||null,
    reference_no:document.getElementById('vp-ref').value.trim(),
    notes:document.getElementById('vp-notes').value.trim(),
    description:desc,
  };
  try{
    await api.post(API.vendorPayments,body);
    toast(type==='purchase'?'Purchase/Expense recorded!':'Payment recorded!');
    document.getElementById('vp-amount').value='';document.getElementById('vp-amount-words').textContent='';
    document.getElementById('vp-ref').value='';
    document.getElementById('vp-notes').value='';
    document.getElementById('vp-desc').value='';
    await refreshVendorLedger(vendorId);
    loadVendorPaymentsSummary();
  }catch(e){toast(e.message,'error');}
}
async function deleteVendorPayment(id,vendorId){
  if(!confirm('Delete this transaction?'))return;
  try{
    await api.delete(API.vendorPayments+'?id='+id);
    toast('Deleted');
    await refreshVendorLedger(vendorId);
    loadVendorPaymentsSummary();
  }catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// DUPLICATE FINDER — VENDORS & CATEGORIES
// ══════════════════════════════════════════════════════════
let _catDupType = 'vendors';

async function findCatalogDuplicates(type){
  _catDupType = type;
  const isVendor = type === 'vendors';
  setElText('catdup-title', isVendor ? '🔍 Duplicate Vendors' : '🔍 Duplicate Categories');
  setElText('catdup-sub', isVendor ? 'Vendors with identical or very similar names' : 'Categories with identical or very similar names');
  document.getElementById('catdup-body').innerHTML = '<div style="text-align:center;padding:40px;color:var(--text3)"><span class="spinner"></span> Scanning…</div>';
  setElText('catdup-count', '');
  openModal('modal-catalog-duplicates');

  try{
    const url = isVendor ? API.vendors+'?duplicates=1' : API.categories+'?duplicates=1';
    const r   = await api.get(url);
    const groups = r.data || [];
    setElText('catdup-count', groups.length === 0 ? '✅ No duplicates found' : groups.length+' duplicate group'+(groups.length!==1?'s':'')+' found');

    if(!groups.length){
      document.getElementById('catdup-body').innerHTML =
        '<div style="text-align:center;padding:48px;color:var(--text3)"><div style="font-size:2rem;margin-bottom:10px">✅</div><strong>No duplicates found</strong></div>';
      return;
    }

    document.getElementById('catdup-body').innerHTML = groups.map(function(g){
      const rows = g.items.map(function(item){
        const editFn   = isVendor ? 'editVendor('+item.id+')' : 'editCategory('+item.id+')';
        const deleteFn = 'catalogDupDelete('+item.id+',\''+esc(item.name)+'\',\''+type+'\')';
        const extra = isVendor
          ? '<td style="padding:7px 10px;font-size:.78rem;color:var(--text2)">'+esc(item.type||'—')+'</td>'
            +'<td style="padding:7px 10px;font-size:.78rem;color:var(--text3)">'+esc(item.city||'—')+'</td>'
            +'<td style="padding:7px 10px;font-family:var(--mono);font-size:.78rem">'+esc(item.phone||'—')+'</td>'
          : '<td style="padding:7px 10px;font-size:.78rem;color:var(--text2)">'+esc(item.description||'—')+'</td>'
            +'<td style="padding:7px 10px"><span class="badge '+(item.color?'badge-'+item.color:'badge-gray')+'">'+esc(item.color||'—')+'</span></td>'
            +'<td style="padding:7px 10px;font-family:var(--mono);font-size:.78rem">'+item.product_count+' products</td>';
        return '<tr>'
          +'<td style="padding:7px 10px;font-weight:600;font-size:.83rem">'+esc(item.name)+'</td>'
          +extra
          +'<td style="padding:7px 10px;white-space:nowrap">'
          +'<button class="btn btn-ghost btn-xs" onclick="closeModal(\'modal-catalog-duplicates\');'+editFn+'">✏️ Edit</button> '
          +(CAN_DELETE?'<button class="btn btn-danger btn-xs" onclick="'+deleteFn+'">🗑️</button>':'')
          +'</td></tr>';
      }).join('');

      const cols = isVendor
        ? '<th>Name</th><th>Type</th><th>City</th><th>Phone</th><th></th>'
        : '<th>Name</th><th>Description</th><th>Color</th><th>Products</th><th></th>';
      return '<div style="margin-bottom:14px;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">'
        +'<div style="padding:8px 14px;background:rgba(239,68,68,.08);font-size:.78rem;color:var(--text2);border-bottom:1px solid var(--border)">'
        +'Similar name: <strong>'+esc(g.key)+'</strong> — <span style="color:var(--orange)">'+g.items.length+' entries</span></div>'
        +'<table style="width:100%;border-collapse:collapse"><thead><tr style="background:var(--surface2)">'
        +'<th style="padding:6px 10px;text-align:left;font-size:.67rem;color:var(--text3);text-transform:uppercase">'+cols+'</tr></thead>'
        +'<tbody>'+rows+'</tbody></table></div>';
    }).join('');
  }catch(e){
    document.getElementById('catdup-body').innerHTML='<div style="text-align:center;padding:30px;color:var(--red)">'+esc(e.message)+'</div>';
  }
}

async function catalogDupDelete(id, name, type){
  if(!confirm('Delete "'+name+'"?'))return;
  try{
    const url = type==='vendors' ? API.vendors+'?id='+id : API.categories+'?id='+id;
    await api.delete(url);
    toast(name+' deleted');
    findCatalogDuplicates(type);
    if(type==='vendors') loadVendors();
    else { loadCategoriesPage(); loadCategories(); }
  }catch(e){ toast(e.message,'error'); }
}

// ══════════════════════════════════════════════════════════
// DUPLICATE PRODUCT FINDER
// ══════════════════════════════════════════════════════════
let _dupData = { name:[], sku:[], all:[] };

// ── Category label helper (SKU prefix + category name) ───────────────────────
let _catMap = {}; // name → {sku_prefix, name} — used for dropdown labels

function catLabel(p, catName){
  // p can be:
  //   - a product object  → uses p.category_sku_prefix + p.category
  //   - a category object → uses c.sku_prefix + c.name
  //   - null/undefined    → returns ''
  // catName is optional override for the name part
  if(!p) return catName || '';
  var prefix = null;
  var name   = catName || '';
  if(typeof p === 'object'){
    // Product object
    if(p.category !== undefined){
      name   = catName || p.category || '';
      prefix = p.category_sku_prefix || null;
    }
    // Category object (has sku_prefix field directly)
    if(p.sku_prefix !== undefined && p.name !== undefined){
      name   = catName || p.name || '';
      prefix = p.sku_prefix || null;
    }
  }
  if(!name) return '';
  return prefix ? prefix + '-' + name : name;
}


let _dupSkuIds = new Set(); // product IDs that share a SKU+vendor+brand (true duplicates)

async function findDuplicates(){
  openModal('modal-duplicates');
  document.getElementById('dup-body').innerHTML='<div style="text-align:center;padding:40px;color:var(--text3)"><span class="spinner"></span> Scanning…</div>';
  setElText('dup-count', '');
  try{
    const r = await api.get('api/products.php?duplicates=1');
    _dupData = r.data;
    // Sync inline highlight set too
    _dupSkuIds = new Set();
    // Same SKU+vendor+brand = true duplicate; different brand or different vendor = not flagged
    (_dupData.sku||[]).forEach(function(g){ g.products.forEach(function(p){ _dupSkuIds.add(String(p.id)); }); });
    showDupTab('sku');
  }catch(e){ document.getElementById('dup-body').innerHTML='<div style="text-align:center;padding:30px;color:var(--red)">'+esc(e.message)+'</div>'; }
}

function showDupTab(tab){
  ['name','sku','all'].forEach(t=>{
    const btn=document.getElementById('dup-tab-'+t);
    if(!btn)return;
    btn.style.background=t===tab?'var(--accent)':'';
    btn.style.color=t===tab?'#fff':'';
    btn.className=t===tab?'btn btn-sm':'btn btn-ghost btn-sm';
    btn.style.borderRadius='20px';
  });
  const groups = (_dupData&&_dupData[tab])||[];
  const count  = groups.length;
  setElText('dup-count', count===0?'✅ No duplicates':count+' group'+(count!==1?'s':'')+' found');
  if(!groups.length){
    document.getElementById('dup-body').innerHTML='<div style="text-align:center;padding:48px;color:var(--text3)"><div style="font-size:2rem;margin-bottom:10px">✅</div><strong>No duplicates found</strong></div>';
    return;
  }
  document.getElementById('dup-body').innerHTML=groups.map(function(g){
    const reason=tab==='name'?'Similar name: <strong>'+esc(g.key)+'</strong>':tab==='sku'?'Duplicate SKU: <strong>'+esc(g.key)+'</strong>':'Duplicate: <strong>'+esc(g.key)+'</strong>';
    const rows=g.products.map(function(p){
      return '<tr>'
        +'<td style="padding:7px 10px;font-weight:600;font-size:.83rem">'+esc(p.name)+'</td>'
        +'<td style="padding:7px 10px;font-size:.78rem;color:var(--accent2);font-family:var(--mono);font-weight:600">'+esc(p.sku||'—')+'</td>'
        +'<td style="padding:7px 10px;font-size:.78rem;color:var(--accent);font-weight:600">'+esc(p.brand||'—')+'</td>'
        +'<td style="padding:7px 10px;font-size:.78rem;color:var(--text2)">'+esc(catLabel(p))+'</td>'
        +'<td style="padding:7px 10px;font-family:var(--mono);font-size:.8rem">'+(HIDE_COST?'— / ':fmtCost(p.cost)+' / ')+CUR.sym+fmtN(p.sell)+'</td>'
        +'<td style="padding:7px 10px;font-family:var(--mono);font-size:.8rem;font-weight:700">'+p.stock+'</td>'
        +'<td style="padding:7px 10px;white-space:nowrap">'
        +'<button class="btn btn-ghost btn-xs" onclick="closeModal(\'modal-duplicates\');editProduct('+p.id+')">✏️ Edit</button> '
        +'<button class="btn btn-danger btn-xs" onclick="dupDelete('+p.id+',\''+esc(p.name)+'\',\''+tab+'\')">🗑️</button>'
        +'</td></tr>';
    }).join('');
    return '<div style="margin-bottom:16px;border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">'
      +'<div style="padding:9px 14px;background:rgba(79,142,255,.08);font-size:.78rem;color:var(--text2);border-bottom:1px solid var(--border)">'+reason+' — <span style="color:var(--orange)">'+g.products.length+' products</span></div>'
      +'<table style="width:100%;border-collapse:collapse"><thead><tr style="background:var(--surface2)">'
      +'<th style="padding:6px 10px;text-align:left;font-size:.68rem;color:var(--text3);text-transform:uppercase">Name</th>'
      +'<th style="padding:6px 10px;text-align:left;font-size:.68rem;color:var(--text3);text-transform:uppercase">SKU</th>'
      +'<th style="padding:6px 10px;text-align:left;font-size:.68rem;color:var(--text3);text-transform:uppercase">Brand</th>'
      +'<th style="padding:6px 10px;text-align:left;font-size:.68rem;color:var(--text3);text-transform:uppercase">Category</th>'
      +'<th style="padding:6px 10px;text-align:left;font-size:.68rem;color:var(--text3);text-transform:uppercase">Cost / Sell</th>'
      +'<th style="padding:6px 10px;text-align:left;font-size:.68rem;color:var(--text3);text-transform:uppercase">Stock</th>'
      +'<th style="padding:6px 10px"></th>'
      +'</tr></thead><tbody>'+rows+'</tbody></table></div>';
  }).join('');
}

async function dupDelete(id,name,tab){
  if(!confirm('Delete "'+name+'"? This cannot be undone.'))return;
  try{
    await api.delete(API.products+'?id='+id);
    toast(name+' deleted');
    const r=await api.get('api/products.php?duplicates=1');
    _dupData=r.data; showDupTab(tab); loadProducts();
  }catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// CUSTOMERS
// ══════════════════════════════════════════════════════════
async function loadCustomers(){
  const q=document.getElementById('customer-search')?.value||'';
  try{
    const r=await api.get(API.customers+(q?'?q='+encodeURIComponent(q):''));
    const tbody=document.getElementById('customers-body');
    const empty=document.getElementById('customers-empty');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=r.data.map(c=>`<tr>
      <td><div style="font-weight:600">${esc(c.name)}</div></td>
      <td>${c.phone?`<a href="tel:${esc(c.phone)}" style="color:var(--accent)">${esc(c.phone)}</a>`:'—'}</td>
      <td style="font-size:.8rem">${esc(c.email||'—')}</td>
      <td style="font-size:.75rem;color:var(--text3)">${esc(c.gst||'—')}</td>
      <td><button class="btn btn-ghost btn-xs" onclick="viewCustomerHistory(${c.id},'${esc(c.name)}')">📋 History</button></td>
      <td><button class="btn btn-ghost btn-xs" onclick="editCustomer(${c.id})">✏️</button> ${CAN_DELETE?`<button class="btn btn-danger btn-xs" onclick="deleteCustomer(${c.id},'${esc(c.name)}')">🗑️</button>`:""}</td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
async function saveCustomer(){
  const editId=parseInt(document.getElementById('cust-edit-id').value)||0;
  const body={name:document.getElementById('cust-name').value.trim(),phone:document.getElementById('cust-phone').value.trim(),email:document.getElementById('cust-email').value.trim(),gst:document.getElementById('cust-gst').value.trim(),address:document.getElementById('cust-address').value.trim(),notes:document.getElementById('cust-notes').value.trim()};
  if(!body.name){toast('Customer name required','error');return;}
  const btn=document.getElementById('cust-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){body.id=editId;await api.put(API.customers,body);toast('Customer updated!');}
    else{await api.post(API.customers,body);toast('Customer added!');}
    cancelCustomerEdit();loadCustomers();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='Save Customer';}
}
async function editCustomer(id){
  try{
    const r=await api.get(API.customers+'?id='+id);
    const c=r.data;
    setElText('cust-form-title', '✏️ Edit Customer');
    document.getElementById('cust-edit-id').value=c.id;
    document.getElementById('cust-name').value=c.name;
    document.getElementById('cust-phone').value=c.phone||'';
    document.getElementById('cust-email').value=c.email||'';
    document.getElementById('cust-gst').value=c.gst||'';
    document.getElementById('cust-address').value=c.address||'';
    document.getElementById('cust-notes').value=c.notes||'';
    document.getElementById('cust-cancel-btn').style.display='inline-flex';
  }catch(e){toast(e.message,'error');}
}
function cancelCustomerEdit(){
  setElText('cust-form-title', '👤 Add Customer');
  document.getElementById('cust-edit-id').value='';
  ['cust-name','cust-phone','cust-email','cust-gst','cust-address','cust-notes'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  document.getElementById('cust-cancel-btn').style.display='none';
}
async function deleteCustomer(id,name){if(!confirm(`Delete "${name}"?`))return;try{await api.delete(API.customers+'?id='+id);toast('Deleted');loadCustomers();}catch(e){toast(e.message,'error');}}
async function viewCustomerHistory(id,name){
  try{
    const r=await api.get(API.customers+'?id='+id);
    const card=document.getElementById('cust-history-card');
    setElText('cust-history-title', `📋 ${name} – Purchase History`);
    const tbody=document.getElementById('cust-history-body');
    if(!r.data.invoices?.length){tbody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text3)">No purchases yet</td></tr>';}
    else tbody.innerHTML=r.data.invoices.map(i=>`<tr>
      <td class="mono" style="color:var(--accent)">${esc(i.invoice_number)}</td>
      <td>${i.date}</td><td class="mono">${i.item_count||'—'}</td>
      <td class="mono">${CUR.sym}${fmtN(i.total)}</td>
      <td><span class="badge ${i.status==='paid'?'badge-green':i.status==='cancelled'?'badge-red':'badge-yellow'}">${i.status}</span></td>
      <td><button class="btn btn-ghost btn-xs" onclick="window.open('${API.invoices}?print=${i.id}','_blank')">🖨️</button></td>
    </tr>`).join('');
    card.style.display='block';card.scrollIntoView({behavior:'smooth'});
  }catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// INVOICES
// ══════════════════════════════════════════════════════════
let invItems=[];
async function loadInvoices(){
  const params=new URLSearchParams();
  const q=document.getElementById('inv-search')?.value;const from=document.getElementById('inv-from')?.value;const to=document.getElementById('inv-to')?.value;const status=document.getElementById('inv-status')?.value;const loc=getLocationId();
  if(q)params.set('q',q);if(from)params.set('from',from);if(to)params.set('to',to);if(status)params.set('status',status);if(loc)params.set('location_id',loc);
  try{
    const r=await api.get(API.invoices+'?'+params);
    const tbody=document.getElementById('invoices-body');
    const empty=document.getElementById('invoices-empty');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=r.data.map(i=>`<tr>
      <td class="mono" style="color:var(--accent);font-weight:700">${esc(i.invoice_number)}</td>
      <td>${i.date}</td>
      <td>${esc(i.customer_name||'Walk-in')}</td>
      <td style="font-size:.8rem;color:var(--text2)">${esc(i.location_name||'—')}</td>
      <td class="mono">${i.item_count||'—'}</td>
      <td class="mono text-green" style="font-weight:700">${CUR.sym}${fmtN(i.total)}</td>
      <td class="mono ${+i.amount_received>0?'text-green':'text-muted'}">${+i.amount_received>0?CUR.sym+fmtN(i.amount_received):'—'}</td>
      <td class="mono ${(+i.total-(+i.amount_received||0))>0?'text-red':'text-green'}" style="font-weight:600">${(()=>{const bal=+i.total-(+i.amount_received||0);return bal>0?CUR.sym+fmtN(bal):'✅ Paid';})()}</td>
      <td>${i.payment_method?'<span class="badge badge-gray">'+esc(i.payment_method)+'</span>':'—'}</td>
      <td><span class="badge ${i.status==='paid'?'badge-green':i.status==='cancelled'?'badge-red':'badge-yellow'}">${i.status}</span></td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick="editInvoice(${i.id})" title="Edit">✏️</button>
        <button class="btn btn-ghost btn-xs" onclick="window.open('${API.invoices}?print=${i.id}','_blank')" title="Print">🖨️</button>
        ${(CAN_DELETE && i.status!=='cancelled')?`<button class="btn btn-danger btn-xs" onclick="cancelInvoice(${i.id},'${esc(i.invoice_number)}')" title="Cancel">✕</button>`:''}
      </td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
function addInvoiceItem(){
  const id='ii_'+Date.now();
  invItems.push({id,product_id:'',product_name:'',qty:1,unit_price:0});
  renderInvoiceItems();
}
function removeInvoiceItem(id){invItems=invItems.filter(i=>i.id!==id);renderInvoiceItems();recalcInvoice();}
function onPaymentMethodChange(){
  const method=document.getElementById('inv-payment')?.value;
  const upiGroup=document.getElementById('inv-upi-group');
  if(!upiGroup) return;
  if(method==='upi'){
    upiGroup.style.display='';
    populateUPIPayees();
  } else {
    upiGroup.style.display='none';
  }
}
async function populateUPIPayees(){
  try{
    const r=await api.get(API.payees+'?active_only=1');
    const upiPayees=r.data.filter(function(p){return p.type==='UPI'||p.type==='Cash'||p.type==='Person'||p.type==='Bank Account';});
    const sel=document.getElementById('inv-upi-payee');
    if(!sel) return;
    sel.innerHTML='<option value="">— Select Account —</option>'+upiPayees.map(function(p){
      var sub=p.type==='UPI'?('UPI: '+(p.upi_id||'')):p.type==='Bank Account'?('Bank: '+(p.bank_name||'')+(p.account_no?' ****'+String(p.account_no).slice(-4):'')):'';
      return '<option value="'+p.id+'">'+esc(p.name)+(sub?' — '+esc(sub):'')+'</option>';
    }).join('');
  }catch(e){}
}
async function openInvoiceModal(){
  invItems=[];
  invalidateProductsCache(); // always fetch fresh stock when opening estimate
  document.getElementById('inv-edit-id').value='';
  setElText('inv-modal-title', '🧾 New Estimate');
  document.getElementById('inv-customer-search').value='';
  document.getElementById('inv-customer-id').value='';
  document.getElementById('inv-date').value=today();
  document.getElementById('inv-payment').value='cash';
  document.getElementById('inv-upi-group').style.display='none';
  document.getElementById('inv-discount').value='';
  document.getElementById('inv-packing').value='';
  document.getElementById('inv-misc').value='';
  const s=await getSettings();
  document.getElementById('inv-tax').value=s.tax_rate||0;
  document.getElementById('inv-notes').value='';
  document.getElementById('inv-items-body').innerHTML='';
  populateLocationSelect('inv-location');
  await loadCustomerDatalist();
  addInvoiceItem();
  recalcInvoice();
  openModal('modal-invoice');
}
async function editInvoice(id){
  try{
    const r=await api.get(API.invoices+'?id='+id);
    const inv=r.data;
    invItems=[];
    document.getElementById('inv-edit-id').value=inv.id;
    setElText('inv-modal-title', '🧾 Edit Estimate: '+inv.invoice_number);
    document.getElementById('inv-customer-search').value=inv.customer_name||'';
    document.getElementById('inv-customer-id').value=inv.customer_id||'';
    document.getElementById('inv-date').value=inv.date||today();
    document.getElementById('inv-payment').value=inv.payment_method||'cash';
    onPaymentMethodChange();
    if(inv.upi_payee_id){ document.getElementById('inv-upi-payee').value=inv.upi_payee_id; }
    document.getElementById('inv-discount').value=inv.discount||'';
    document.getElementById('inv-tax').value=inv.tax_rate||'';
    document.getElementById('inv-packing').value=inv.packing_charges||'';
    document.getElementById('inv-amount-received').value=inv.amount_received||'';
    document.getElementById('inv-misc').value=inv.misc_charges||'';
    document.getElementById('inv-notes').value=inv.notes||'';
    populateLocationSelect('inv-location',inv.location_id);
    await loadCustomerDatalist();
    // Load items
    invItems=(inv.items||[]).map(function(it,idx){return {id:'e'+idx,product_id:it.product_id,product_name:it.product_name,qty:it.qty,unit_price:it.unit_price};});
    renderInvoiceItems();recalcInvoice();
    openModal('modal-invoice');
  }catch(e){toast(e.message,'error');}
}
async function loadCustomerDatalist(){
  try{const r=await api.get(API.customers);const dl=document.getElementById('inv-customer-list');if(dl)dl.innerHTML=r.data.map(c=>`<option value="${esc(c.name)}" data-id="${c.id}">`).join('');}catch{}
}
async function searchCustomerInline(name){
  try{
    const r=await api.get(API.customers+'?q='+encodeURIComponent(name));
    const match=r.data.find(c=>c.name.toLowerCase()===name.toLowerCase());
    document.getElementById('inv-customer-id').value=match?match.id:'';
  }catch{}
}
async function onInvoiceProductChange(id,selectEl){
  const pid=selectEl.value;
  const item=invItems.find(i=>i.id===id);
  if(!item) return;
  if(!pid){ item.product_id=''; item.unit_price=0; recalcInvoice(); return; }
  const opt=selectEl.options[selectEl.selectedIndex];
  const sell=parseFloat(opt?.getAttribute('data-sell')||0);
  item.product_id=pid;
  item.product_name=esc((opt?.textContent||'').split(' | ')[0].trim());
  item.unit_price=sell;
  const priceInput=document.getElementById('inv-price-'+id);
  if(priceInput) priceInput.value=fmtN(sell);
  const qty=parseFloat(document.getElementById('inv-qty-'+id)?.value)||1;
  item.qty=qty;
  const totalEl=document.getElementById('inv-item-total-'+id);
  if(totalEl) totalEl.textContent=CUR.sym+fmtN(qty*sell);
  recalcInvoice();
}
function renderInvoiceItems(){
  const tbody=document.getElementById('inv-items-body');
  tbody.innerHTML=invItems.map(function(item){
    return '<tr data-item-id="'+item.id+'">'
      +'<td><select class="form-control" id="inv-sel-'+item.id+'" onchange="onInvoiceProductChange(\''+item.id+'\',this)" style="background:var(--surface3)"><option value="">— Select Product —</option></select></td>'
      +'<td><input type="number" value="'+item.qty+'" min="1" id="inv-qty-'+item.id+'" style="background:var(--surface3);border:1px solid var(--border);color:var(--text);padding:5px 8px;border-radius:6px;width:70px;font-family:var(--mono)" onchange="updateInvItem(\''+item.id+'\',\'qty\',this.value)"></td>'
      +'<td><input type="number" value="'+fmtN(item.unit_price)+'" step="0.01" id="inv-price-'+item.id+'" onfocus="clearIfZero(this)" style="background:var(--surface3);border:1px solid var(--border);color:var(--text);padding:5px 8px;border-radius:6px;width:100px;font-family:var(--mono)" oninput="updateInvItem(\''+item.id+'\',\'unit_price\',this.value)"></td>'
      +'<td class="mono" style="font-weight:600" id="inv-item-total-'+item.id+'">'+CUR.sym+fmtN(item.qty*item.unit_price)+'</td>'
      +'<td><button class="btn btn-danger btn-xs" onclick="removeInvoiceItem(\''+item.id+'\')">✕</button></td>'
      +'</tr>';
  }).join('');
  // Populate product selects using cache — set sell price immediately when product matched
  getProductsCache().then(function(products){
    invItems.forEach(function(item){
      const sel=document.getElementById('inv-sel-'+item.id);
      if(!sel) return;
      var invLoc=document.getElementById('inv-location')?.value||null;
      populateProductSelectEl(sel, products, item.product_id, '— Select Product —', invLoc);
      // Immediately set unit price from sell price if product is selected
      if(item.product_id){
        const matched=products.find(function(p){return p.id==item.product_id;});
        if(matched){
          const priceInput=document.getElementById('inv-price-'+item.id);
          if(priceInput){
            if(item.unit_price>0) priceInput.value=fmtN(item.unit_price);
            else { priceInput.value=fmtN(matched.sell); item.unit_price=+matched.sell; }
          }
          const totalEl=document.getElementById('inv-item-total-'+item.id);
          if(totalEl) totalEl.textContent=CUR.sym+fmtN(item.qty*item.unit_price);
        }
      }
    });
    recalcInvoice();
  }).catch(function(){});
}
function updateInvItem(id,field,value){
  const item=invItems.find(i=>i.id===id);
  if(!item)return;
  const num=parseFloat(value)||0;
  if(field==='qty'&&num<=0){
    toast('Quantity must be greater than 0','error');
    const el=document.getElementById('inv-qty-'+id);
    if(el){el.value=1;item.qty=1;}
    return;
  }
  item[field]=num;
  const totalEl=document.getElementById('inv-item-total-'+id);
  if(totalEl) totalEl.textContent=CUR.sym+fmtN(item.qty*item.unit_price);
  recalcInvoice();
}
function recalcInvoice(){
  const subtotal=invItems.reduce((s,i)=>s+i.qty*i.unit_price,0);
  const discount=parseFloat(document.getElementById('inv-discount')?.value)||0;
  const packing=parseFloat(document.getElementById('inv-packing')?.value)||0;
  const misc=parseFloat(document.getElementById('inv-misc')?.value)||0;
  const received=parseFloat(document.getElementById('inv-amount-received')?.value)||0;
  const total=Math.max(0,subtotal-discount+packing+misc);
  const balance=total-received;
  setElText('inv-subtotal', CUR.sym+fmtN(subtotal));
  setElText('inv-total', CUR.sym+fmtN(total));
  setAmountWordsDisplay('inv-total-words', total);
  const balEl=document.getElementById('inv-balance-display');
  if(balEl){
    if(received<=0){balEl.textContent='—';balEl.style.color='var(--text3)';}
    else if(balance>0){balEl.textContent=CUR.sym+fmtN(balance)+' due';balEl.style.color='var(--red)';}
    else{balEl.textContent='✅ Fully Paid';balEl.style.color='var(--green)';}
  }
}
async function saveInvoice(){
  const items=invItems.filter(i=>i.product_id&&i.qty>0);
  if(!items.length){toast('Add at least one item with quantity > 0','error');return;}
  const badQty=invItems.filter(i=>i.product_id&&i.qty<=0);
  if(badQty.length){toast('Quantity must be greater than 0 for all items','error');return;}
  // Frontend stock check using cached product data
  try{
    const products=await getProductsCache();
    for(const item of items){
      const p=products.find(function(x){return x.id==item.product_id;});
      if(p&&+p.stock<=0){toast('"'+esc(p.name)+'" is out of stock','error');return;}
      if(p&&+p.stock<item.qty){toast('"'+esc(p.name)+'": only '+p.stock+' '+esc(p.unit||'')+'  available','error');return;}
    }
  }catch(e){}
  const editId=document.getElementById('inv-edit-id')?.value;
  const body={
    customer_id:document.getElementById('inv-customer-id').value||null,
    customer_name:document.getElementById('inv-customer-search').value.trim()||'Walk-in',
    location_id:document.getElementById('inv-location').value||null,
    date:document.getElementById('inv-date').value,
    payment_method:document.getElementById('inv-payment').value,
    upi_payee_id:document.getElementById('inv-upi-payee')?.value||null,
    amount_received:document.getElementById('inv-amount-received')?.value||0,
    discount:document.getElementById('inv-discount').value||0,
    tax_rate:document.getElementById('inv-tax').value||0,
    packing_charges:document.getElementById('inv-packing').value||0,
    misc_charges:document.getElementById('inv-misc').value||0,
    notes:document.getElementById('inv-notes').value.trim(),
    items:items.map(i=>({product_id:i.product_id,qty:i.qty,unit_price:i.unit_price})),
  };
  const btn=document.getElementById('inv-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Saving…';
  try{
    if(editId){
      body.id=editId;
      await api.put(API.invoices,body);
      toast('Estimate updated!');
    } else {
      const r=await api.post(API.invoices,body);
      toast('Estimate '+r.data.invoice_number+' created!');
      if(confirm('Open print view?'))window.open(API.invoices+'?print='+r.data.id,'_blank');
    }
    closeModal('modal-invoice');clearAllSearchableSelects();loadInvoices();invalidateProductsCache();updateAlertBadge();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='💾 Save Estimate';}
}
async function cancelInvoice(id,num){
  if(!confirm(`Cancel estimate ${num}? Stock will be restored.`))return;
  try{await api.delete(API.invoices+'?id='+id);toast('Estimate cancelled');loadInvoices();invalidateProductsCache();updateAlertBadge();}catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// STOCK IN
// ══════════════════════════════════════════════════════════
async function recordStockIn(){
  const pid=document.getElementById('si-product').value;const qty=document.getElementById('si-qty').value;
  if(!pid||!qty||+qty<1){toast('Select product and quantity','error');return;}
  const btn=document.getElementById('si-submit');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    const r=await api.post(API.stockIn,{product_id:pid,location_id:document.getElementById('si-location').value||null,vendor_id:document.getElementById('si-vendor').value||null,po_id:document.getElementById('si-po').value||null,qty,cost:document.getElementById('si-cost').value||null,date:document.getElementById('si-date').value,note:document.getElementById('si-note').value.trim()});
    toast(r.message);
    ['si-qty','si-cost','si-note'].forEach(id=>{document.getElementById(id).value='';});
    clearAllSearchableSelects();
    loadStockIn();invalidateProductsCache();populateProductSelect('si-product');updateAlertBadge();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='📥 Record Stock In';}
}
async function loadStockIn(){
  const params=new URLSearchParams();
  const locId=document.getElementById('si-location')?.value||getLocationId();const from=document.getElementById('si-filter-from')?.value;const to=document.getElementById('si-filter-to')?.value;
  if(locId)params.set('location_id',locId);if(from)params.set('from',from);if(to)params.set('to',to);params.set('limit','200');
  try{
    const r=await api.get(API.stockIn+'?'+params);
    const tbody=document.getElementById('si-history');const empty=document.getElementById('si-empty');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=r.data.map(t=>`<tr>
      <td class="mono" style="font-size:.78rem">${t.date}</td>
      <td>${esc(t.product_name)}</td>
      <td><span class="badge badge-gray" style="font-size:.68rem">${esc(t.location_name||'—')}</span></td>
      <td style="color:var(--text2)">${esc(t.vendor_name||'—')}</td>
      <td class="mono text-green">+${t.qty}</td>
      <td class="mono">${CUR.sym}${fmtN(t.cost)}</td>
      <td class="mono">${CUR.sym}${fmtN(t.total)}</td>
      <td style="color:var(--text3);font-size:.79rem">${esc(t.note||'—')}</td>
      <td>${CAN_DELETE?`<button class="btn btn-ghost btn-xs" onclick="reverseStockIn(${t.id})" title="Reverse">↩️</button>`:''}</td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
async function reverseStockIn(id){if(!confirm('Reverse this transaction?'))return;try{const r=await api.delete(API.stockIn+'?id='+id);toast(r.message);loadStockIn();invalidateProductsCache();populateProductSelect('si-product');updateAlertBadge();}catch(e){toast(e.message,'error');}}

// ══════════════════════════════════════════════════════════
// PURCHASE ORDERS
// ══════════════════════════════════════════════════════════
function switchImportToExpenses(){
  showPage('import');
  const sel=document.getElementById('import-type');
  if(sel){ sel.value='expenses'; onImportTypeChange(); }
}

function switchImportToPayees(){
  showPage('import');
  const sel=document.getElementById('import-type');
  if(sel){ sel.value='payees'; onImportTypeChange(); }
}

function switchImportToWebsiteOrders(){
  showPage('import');
  const sel=document.getElementById('import-type');
  if(sel){ sel.value='website_orders'; onImportTypeChange(); }
}

function exportWebsiteOrders(){
  if(!_woAllRows.length){ toast('No orders to export','error'); return; }
  const rows=_woStatusFilter?_woAllRows.filter(function(o){return o.status===_woStatusFilter;}):_woAllRows;
  const headers=['S. No','Order Number','Order Date','Customer Name','Mobile Number','Amount','Paid Date','Account','Order Status','Gift','Dispatch Status','Dispatch Date','Transport Name','# of Boxes','Comments'];
  const body=rows.map(function(o,i){
    return [
      i+1,
      o.order_number||'',
      o.order_date||'',
      o.customer_name||'',
      o.mobile||'',
      Math.round(+o.amount||0),
      o.paid_date||'',
      o.account_names||'',
      o.status||'',
      o.gift||'',
      o.dispatch_status||'',
      o.dispatch_date||'',
      o.transport||'',
      o.num_boxes||0,
      o.comments||'',
    ];
  });
  const csv=rowsToCsv([headers,...body]);
  const from=document.getElementById('wo-from')?.value||'';
  const to=document.getElementById('wo-to')?.value||'';
  const dateLabel=(from||'')+(to?'_to_'+to:'');
  downloadCsv(csv,'Customer_Orders'+(dateLabel?'_'+dateLabel:'')+'.csv');
  toast('Exported '+body.length+' orders 📊');
}
// ══════════════════════════════════════════════════════════
// CUSTOMER ORDERS / PAYMENTS (website order tracking)
// ══════════════════════════════════════════════════════════
let _woAllRows=[];
let _woStatusFilter='';

async function loadWebsiteOrders(){
  const q=document.getElementById('wo-search')?.value||'';
  const from=document.getElementById('wo-from')?.value||'';
  const to=document.getElementById('wo-to')?.value||'';
  const dispatch=document.getElementById('wo-filter-dispatch')?.value||'';
  const params=new URLSearchParams();
  if(q)params.set('q',q); if(from)params.set('from',from); if(to)params.set('to',to);
  if(dispatch)params.set('dispatch_status',dispatch);
  try{
    const r=await api.get(API.websiteOrders+'?'+params);
    _woAllRows=r.data||[];
    if(_woStatusFilter && !_woAllRows.some(o=>o.status===_woStatusFilter)) _woStatusFilter='';
    renderWOStatusCapsules();
    renderWOTable();
    const totalAmt=_woAllRows.reduce((s,o)=>s+(+o.amount||0),0);
    const totalPaid=_woAllRows.reduce((s,o)=>s+(+o.amount_paid||0),0);
    setElText('wo-total-label', _woAllRows.length+' order(s) · '+CUR.sym+fmtN(totalPaid)+' collected of '+CUR.sym+fmtN(totalAmt));
  }catch(e){toast(e.message,'error');}
}
function setWOStatusFilter(status){
  _woStatusFilter=(_woStatusFilter===status)?'':status;
  renderWOStatusCapsules();
  renderWOTable();
}
function renderWOStatusCapsules(){
  const el=document.getElementById('wo-status-capsules');
  if(!el)return;
  const SM={
    Pending:{color:'var(--text3)',bg:'rgba(148,163,184,.15)',icon:'⏳'},
    Partial:{color:'#ca8a04',bg:'rgba(234,179,8,.15)',icon:'◐'},
    Paid:{color:'var(--green)',bg:'rgba(34,197,94,.15)',icon:'✅'},
    Cancelled:{color:'var(--red)',bg:'rgba(239,68,68,.15)',icon:'✕'},
  };
  const counts={};
  _woAllRows.forEach(o=>{counts[o.status]=(counts[o.status]||0)+1;});
  const allOn=!_woStatusFilter;
  el.innerHTML='<button onclick="setWOStatusFilter(\'\')" style="cursor:pointer;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;border:1.5px solid '+(allOn?'var(--accent)':'transparent')+';background:'+(allOn?'var(--accent)':'var(--surface2)')+';color:'+(allOn?'#fff':'var(--text2)')+'">All ('+_woAllRows.length+')</button>'
    +Object.keys(SM).map(function(s){
      if(!counts[s])return '';
      const on=_woStatusFilter===s;
      return '<button onclick="setWOStatusFilter(\''+s+'\')" style="cursor:pointer;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;border:1.5px solid '+(on?SM[s].color:'transparent')+';background:'+SM[s].bg+';color:'+SM[s].color+'">'+SM[s].icon+' '+s+': '+counts[s]+'</button>';
    }).join('');
}
function renderWOTable(){
  const tbody=document.getElementById('wo-body'); const empty=document.getElementById('wo-empty');
  const rows=_woStatusFilter?_woAllRows.filter(o=>o.status===_woStatusFilter):_woAllRows;
  if(!rows.length){tbody.innerHTML='';empty.style.display='block';return;}
  empty.style.display='none';
  const statusBadge={Pending:'badge-gray',Partial:'badge-yellow',Paid:'badge-green',Cancelled:'badge-red'};
  tbody.innerHTML=rows.map(function(o){
    const bal=(+o.amount||0)-(+o.amount_paid||0);
    return '<tr>'
      +'<td style="font-weight:600">'+esc(o.order_number)+'</td>'
      +'<td style="white-space:nowrap;font-size:.8rem">'+esc(o.order_date)+'</td>'
      +'<td>'+esc(o.customer_name||'—')+'</td>'
      +'<td style="font-size:.78rem;color:var(--text2)">'+esc(o.city||'')+(o.mobile?' · '+esc(o.mobile):'')+'</td>'
      +'<td class="mono">'+CUR.sym+fmtN(o.amount)+'</td>'
      +'<td class="mono text-green">'+CUR.sym+fmtN(o.amount_paid)+'</td>'
      +'<td class="mono '+(bal>0?'text-red':'text-muted')+'">'+CUR.sym+fmtN(bal)+'</td>'
      +'<td><span class="badge '+(statusBadge[o.status]||'badge-gray')+'">'+esc(o.status)+'</span></td>'
      +'<td style="font-size:.78rem;color:var(--text2)">'+esc(o.dispatch_status||'—')+'</td>'
      +'<td style="white-space:nowrap">'
        +'<button class="btn btn-ghost btn-xs" onclick="openWOPayments('+o.id+')" title="Payments">💰</button>'
        +'<button class="btn btn-ghost btn-xs" onclick="openWebsiteOrderModal('+o.id+')" title="Edit">✏️</button>'
        +(CAN_DELETE?'<button class="btn btn-ghost btn-xs" onclick="deleteWebsiteOrder('+o.id+',\''+esc(o.order_number)+'\')" title="Delete">🗑️</button>':'')
      +'</td>'
      +'</tr>';
  }).join('');
}

function openWebsiteOrderModal(id){
  document.getElementById('wo-edit-id').value=id||'';
  document.getElementById('wo-modal-title').textContent=id?'✏️ Edit Order':'🛒 New Order';
  if(id){
    const o=_woAllRows.find(r=>String(r.id)===String(id));
    if(o){
      document.getElementById('wo-order-number').value=o.order_number||'';
      document.getElementById('wo-order-date').value=o.order_date||'';
      document.getElementById('wo-customer-name').value=o.customer_name||'';
      document.getElementById('wo-mobile').value=o.mobile||'';
      document.getElementById('wo-city').value=o.city||'';
      document.getElementById('wo-amount').value=o.amount||'';
      document.getElementById('wo-status').value=o.status||'Pending';
      document.getElementById('wo-gift').value=o.gift||'';
      document.getElementById('wo-dispatch-status').value=o.dispatch_status||'';
      document.getElementById('wo-dispatch-date').value=o.dispatch_date||'';
      document.getElementById('wo-transport').value=o.transport||'';
      document.getElementById('wo-num-boxes').value=o.num_boxes||'';
      document.getElementById('wo-comments').value=o.comments||'';
    }
  } else {
    ['wo-order-number','wo-customer-name','wo-mobile','wo-city','wo-amount','wo-gift','wo-dispatch-date','wo-transport','wo-num-boxes','wo-comments'].forEach(function(id2){
      var el=document.getElementById(id2); if(el) el.value='';
    });
    document.getElementById('wo-order-date').value=new Date().toISOString().slice(0,10);
    document.getElementById('wo-status').value='Pending';
    document.getElementById('wo-dispatch-status').value='';
  }
  openModal('modal-website-order');
}
async function saveWebsiteOrder(){
  const id=document.getElementById('wo-edit-id').value;
  const body={
    order_number:document.getElementById('wo-order-number').value.trim(),
    order_date:document.getElementById('wo-order-date').value,
    customer_name:document.getElementById('wo-customer-name').value.trim(),
    mobile:document.getElementById('wo-mobile').value.trim(),
    city:document.getElementById('wo-city').value.trim(),
    amount:document.getElementById('wo-amount').value,
    status:document.getElementById('wo-status').value,
    gift:document.getElementById('wo-gift').value.trim(),
    dispatch_status:document.getElementById('wo-dispatch-status').value,
    dispatch_date:document.getElementById('wo-dispatch-date').value,
    transport:document.getElementById('wo-transport').value.trim(),
    num_boxes:document.getElementById('wo-num-boxes').value,
    comments:document.getElementById('wo-comments').value.trim(),
  };
  if(!body.order_number||!body.order_date||body.amount===''){ toast('Order Number, Date and Amount are required','error'); return; }
  try{
    if(id){ body.id=id; await api.put(API.websiteOrders, body); toast('Order updated'); }
    else{ await api.post(API.websiteOrders, body); toast('Order created'); }
    closeModal('modal-website-order');
    loadWebsiteOrders();
  }catch(e){ toast(e.message,'error'); }
}
async function deleteWebsiteOrder(id, orderNumber){
  if(!confirm('Delete order '+orderNumber+'? This also removes its payment history.'))return;
  try{
    await api.delete(API.websiteOrders+'?id='+id);
    toast('Order deleted');
    loadWebsiteOrders();
  }catch(e){ toast(e.message,'error'); }
}

// ── Payments sub-modal ──────────────────────────────────
let _wopBalance=0;
function toggleWopFullAmount(checked){
  if(!checked)return;
  const amt=document.getElementById('wop-amount');
  if(amt && _wopBalance>0) amt.value=_wopBalance.toFixed(2);
}
async function openWOPayments(orderId){
  document.getElementById('wop-order-id').value=orderId;
  document.getElementById('wop-amount').value='';
  document.getElementById('wop-date').value=new Date().toISOString().slice(0,10);
  document.getElementById('wop-note').value='';
  const tick=document.getElementById('wop-full-tick'); if(tick) tick.checked=false;
  await populatePayeeSelect('wop-payee');
  openModal('modal-wo-payments');
  await loadWOPayments();
}
async function loadWOPayments(){
  const orderId=document.getElementById('wop-order-id').value;
  if(!orderId)return;
  try{
    const r=await api.get(API.customerPayments+'?order_id='+orderId);
    const d=r.data;
    document.getElementById('wop-title').textContent='💰 Payments — '+d.order.order_number;
    _wopBalance=+d.summary.balance||0;
    const isOverpaid=_wopBalance<-0.5;
    const balColor=_wopBalance>0.5?'var(--red)':isOverpaid?'var(--yellow)':'var(--text2)';
    document.getElementById('wop-summary').innerHTML=
      '<div><div style="color:var(--text3);font-size:.72rem">ORDER</div><div class="mono" style="font-weight:700;font-size:1.35rem">'+CUR.sym+fmtN(d.summary.amount)+'</div></div>'
      +'<div><div style="color:var(--text3);font-size:.72rem">PAID</div><div class="mono text-green" style="font-weight:700;font-size:1.35rem">'+CUR.sym+fmtN(d.summary.amount_paid)+'</div></div>'
      +'<div><div style="color:var(--text3);font-size:.72rem">BALANCE</div><div class="mono" style="font-weight:700;font-size:1.35rem;color:'+balColor+'">'+CUR.sym+fmtN(Math.abs(_wopBalance))+(isOverpaid?' extra':'')+'</div></div>';
    const tbody=document.getElementById('wop-body');
    if(!d.payments.length){ tbody.innerHTML='<tr><td colspan="6" style="text-align:center;color:var(--text3);padding:14px">No payments recorded yet</td></tr>'; return; }
    tbody.innerHTML=d.payments.map(function(p){
      return '<tr>'
        +'<td style="font-size:.8rem">'+esc(p.payment_date)+'</td>'
        +'<td class="mono">'+CUR.sym+fmtN(p.amount)+'</td>'
        +'<td style="font-size:.8rem">'+esc(p.payee_name||'—')+'</td>'
        +'<td style="font-size:.78rem;color:var(--text2)">'+(p.mode==='cash'?'💵 Cash':'🏦 Account')+'</td>'
        +'<td style="font-size:.76rem;color:var(--text3)">'+esc(p.note||'')+'</td>'
        +'<td>'+(CAN_DELETE?'<button class="btn btn-ghost btn-xs" onclick="deleteCustomerPayment('+p.id+')" title="Delete">🗑️</button>':'')+'</td>'
        +'</tr>';
    }).join('');
  }catch(e){ toast(e.message,'error'); }
}
async function recordCustomerPayment(){
  const orderId=document.getElementById('wop-order-id').value;
  const amount=document.getElementById('wop-amount').value;
  const date=document.getElementById('wop-date').value;
  if(!amount||+amount<=0){ toast('Enter a valid amount','error'); return; }
  if(!date){ toast('Pick a date','error'); return; }
  try{
    await api.post(API.customerPayments, {
      order_id:orderId, amount:amount, payment_date:date,
      payee_id:document.getElementById('wop-payee').value||null,
      mode:document.getElementById('wop-mode').value,
      note:document.getElementById('wop-note').value.trim(),
    });
    toast('Payment recorded');
    document.getElementById('wop-amount').value='';
    document.getElementById('wop-note').value='';
    const tick=document.getElementById('wop-full-tick'); if(tick) tick.checked=false;
    await loadWOPayments();
    loadWebsiteOrders();
    // Refresh the Picking-side payment cache too, so the overpayment
    // badge/banner (dashboard row + open order summary) reflect this
    // payment immediately, without needing to reopen the order.
    if(typeof refreshWoCacheForPicking==='function'){
      await refreshWoCacheForPicking();
      if(typeof renderPickDashboard==='function') renderPickDashboard();
      if(typeof renderPickOrderSummary==='function') renderPickOrderSummary();
    }
    // If this payment was recorded from within a Pending picking order
    // (opened via the Payment button/banner), retry the pending->picking
    // transition now — setPickStatus() re-checks payment and unlocks the
    // item grid immediately if this payment brought it to fully paid.
    if(typeof _pickStatus!=='undefined' && _pickStatus==='pending' && typeof setPickStatus==='function'){
      setPickStatus('picking');
    }
  }catch(e){ toast(e.message,'error'); }
}
async function deleteCustomerPayment(id){
  if(!confirm('Delete this payment entry?'))return;
  try{
    await api.delete(API.customerPayments+'?id='+id);
    toast('Payment deleted');
    await loadWOPayments();
    loadWebsiteOrders();
  }catch(e){ toast(e.message,'error'); }
}
// Bridge from an in-progress Picking estimate to the Customer Orders/
// Payments ledger — silently syncs a website_orders row (order total
// computed from priced items, excluding gift items; dispatch snapshot if
// already dispatched) keyed by Order Number, then opens the same payment
// modal used on the Customer Orders page. This is the only place payments
// get recorded day-to-day; the Customer Orders page itself stays for
// historical CSV imports and orders that never went through Picking.
// Looks up whether an order (by Order Number) has a fully-paid
// website_orders record — used to gate the Pending->Picking transition.
// No record yet (payment never synced/recorded) counts as unpaid.
async function isOrderFullyPaid(orderNo){
  if(!orderNo) return false;
  try{
    const r=await api.get(API.websiteOrders+'?order_number='+encodeURIComponent(orderNo));
    const rows=r.data||[];
    if(!rows.length) return false;
    const o=rows[0];
    return (+o.amount||0) > 0 && (+o.amount_paid||0) >= (+o.amount||0);
  }catch(e){ return false; }
}
async function openEstimatePayment(estId){
  if(!estId){toast('No active order','error');return;}
  const est=_pickEstimates.find(function(e){return e.id===estId;});
  if(!est){toast('Order not found','error');return;}
  if(!est.orderNo){toast('This order needs an order number before payments can be recorded','error');return;}
  const items=est.items||[];
  const amount=items.filter(function(it){return !it.isGift;}).reduce(function(s,it){return s+(+it.amount||0);},0);
  const giftNames=items.filter(function(it){return it.isGift;}).map(function(it){return it.matched_name||it.name;}).join(', ');
  const orderDate=(function(){var d=est.ts?new Date(est.ts):new Date();return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');})();
  const payload={
    order_number:est.orderNo, order_date:orderDate,
    customer_name:est.customer||'', mobile:est.phone||'', city:'',
    amount:Math.round(amount*100)/100, gift:giftNames,
  };
  if(est.status==='dispatched'){
    payload.dispatch_status='Dispatched';
    payload.dispatch_date=est.shipDate||'';
    payload.transport=est.transportName||'';
    payload.num_boxes=est.boxCount||0;
  }
  try{
    const r=await api.post(API.websiteOrders, payload);
    await openWOPayments(r.data.id);
  }catch(e){ toast(e.message,'error'); }
}



function exportPayeesList(){
  api.get(API.payees).then(r=>{
    const payees = r.data||[];
    if(!payees.length){ toast('No payees to export','error'); return; }
    const headers = ['Name','Type','Bank Name','Account No','IFSC','UPI ID','Phone','Notes','Status'];
    const rows = payees.map(p=>[
      p.name||'', p.type||'', p.bank_name||'', p.account_no||'',
      p.ifsc||'', p.upi_id||'', p.phone||'', p.notes||'',
      (+p.is_active===1?'Active':'Inactive'),
    ]);
    downloadCsv(rowsToCsv([headers,...rows]), 'Payees_'+new Date().toISOString().split('T')[0]+'.csv');
    toast('Exported '+payees.length+' payees 📊');
  }).catch(e=>toast(e.message,'error'));
}

async function exportAllPayeeLedgers(){
  const btn=event?.target;
  if(btn){ btn.disabled=true; btn.innerHTML='<span class="spinner"></span> Exporting…'; }
  try{
    // Fetch all payees
    const r = await api.get(API.payees);
    const payees = r.data||[];
    if(!payees.length){ toast('No payees found','error'); return; }

    const allRows = [];
    const headers = ['Payee','Type','Date','Transaction Type','Vendor','Reference','Description','Amount ₹','Running Total ₹'];
    allRows.push(headers);

    for(const p of payees){
      // Fetch each payee's ledger
      const lr = await api.get(API.payeeLedger+'?id='+p.id).catch(()=>null);
      if(!lr || !lr.data) continue;
      const txns = lr.data.transactions||[];
      if(!txns.length){
        // Include payee with no transactions as a blank row
        allRows.push([p.name, p.type||'', '', '', '', '', '', '', '']);
        continue;
      }
      const TYPE_META={payment:{label:'Payment',sign:1},credit_note:{label:'Credit Note',sign:-1},manual_purchase:{label:'Purchase',sign:1},opening_balance:{label:'Opening Bal',sign:1},expense:{label:'Expense',sign:1}};
      let running=0;
      txns.forEach(function(t){
        const meta=TYPE_META[t.type]||{label:t.type,sign:1};
        running += meta.sign*(+t.amount||0);
        allRows.push([
          p.name,
          p.type||'',
          t.txn_date||'',
          meta.label,
          t.vendor_name||'',
          t.reference_no||'',
          t.description||'',
          (+t.amount||0).toFixed(0),
          running.toFixed(0),
        ]);
      });
      // Blank separator between payees
      allRows.push(['','','','','','','','','']);
    }

    const csv = rowsToCsv(allRows);
    const today_str = new Date().toISOString().split('T')[0];
    downloadCsv(csv, 'All_Payee_Ledgers_'+today_str+'.csv');
    toast('All payee ledgers exported — '+payees.length+' payees 📊');
  }catch(e){ toast(e.message,'error'); }
  finally{ if(btn){ btn.disabled=false; btn.innerHTML='📊 Export All Ledgers'; } }
}


let _poAllRows=[];
let _poStatusFilter='';
function fmtCases(n){
  n=+n||0;
  const r=Math.round(n*10)/10;
  return (r%1===0)?String(r):r.toFixed(1);
}
async function loadPOs(){
  const vendor=document.getElementById('po-filter-vendor')?.value||'';
  const params=new URLSearchParams();if(vendor)params.set('vendor_id',vendor);
  try{
    const r=await api.get(API.purchaseOrders+'?'+params);
    _poAllRows=r.data||[];
    if(_poStatusFilter && !_poAllRows.some(po=>po.status===_poStatusFilter)) _poStatusFilter='';
    renderPOStatusCapsules();
    renderPOTable();
  }catch(e){toast(e.message,'error');}
}
function setPOStatusFilter(status){
  _poStatusFilter=(_poStatusFilter===status)?'':status;
  renderPOStatusCapsules();
  renderPOTable();
}
function renderPOStatusCapsules(){
  const el=document.getElementById('po-status-capsules');
  if(!el)return;
  const SM={
    draft:{label:'Draft',color:'var(--text3)',bg:'rgba(148,163,184,.15)',icon:'📝'},
    sent:{label:'Sent',color:'var(--accent)',bg:'rgba(79,142,255,.15)',icon:'📤'},
    partial:{label:'Partial',color:'#ca8a04',bg:'rgba(234,179,8,.15)',icon:'⏳'},
    received:{label:'Received',color:'var(--green)',bg:'rgba(34,197,94,.15)',icon:'✅'},
    cancelled:{label:'Cancelled',color:'var(--red)',bg:'rgba(239,68,68,.15)',icon:'✕'},
  };
  const counts={};
  _poAllRows.forEach(po=>{counts[po.status]=(counts[po.status]||0)+1;});
  const allOn=!_poStatusFilter;
  el.innerHTML='<button onclick="setPOStatusFilter(\'\')" style="cursor:pointer;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;border:1.5px solid '+(allOn?'var(--accent)':'transparent')+';background:'+(allOn?'var(--accent)':'var(--surface2)')+';color:'+(allOn?'#fff':'var(--text2)')+'">All ('+_poAllRows.length+')</button>'
    +Object.keys(SM).map(function(s){
      if(!counts[s])return '';
      const on=_poStatusFilter===s;
      return '<button onclick="setPOStatusFilter(\''+s+'\')" style="cursor:pointer;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;border:1.5px solid '+(on?SM[s].color:'transparent')+';background:'+SM[s].bg+';color:'+SM[s].color+'">'+SM[s].icon+' '+SM[s].label+': '+counts[s]+'</button>';
    }).join('');
}
function renderPOTable(){
  const tbody=document.getElementById('po-body');const empty=document.getElementById('po-empty');
  const rows=_poStatusFilter?_poAllRows.filter(po=>po.status===_poStatusFilter):_poAllRows;
  if(!rows.length){tbody.innerHTML='';empty.style.display='block';return;}
  empty.style.display='none';
  const statusColors={draft:'badge-gray',sent:'badge-blue',partial:'badge-yellow',received:'badge-green',cancelled:'badge-red'};
  tbody.innerHTML=rows.map(po=>`<tr>
      <td class="mono" style="color:var(--accent);font-weight:700">${esc(po.po_number)}</td>
      <td>${esc(po.vendor_name||'—')}</td>
      <td style="font-size:.8rem;color:var(--text2)">${esc(po.location_name||'—')}</td>
      <td class="mono">${fmtCases(po.cases_received)} / ${fmtCases(po.cases_ordered)}</td>
      <td class="mono">${HIDE_COST?'—':CUR.sym+fmtN(po.total||0)}</td>
      <td style="color:var(--text3)">${po.expected_date||'—'}</td>
      <td><span class="badge ${statusColors[po.status]||'badge-gray'}">${po.status}</span></td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick="editPO(${po.id})">✏️ Edit</button>
        ${po.status!=='received'&&po.status!=='cancelled'?`<button class="btn btn-success btn-xs" onclick="openReceivePO(${po.id})">📥 Receive</button>`:''}
        <button class="btn btn-ghost btn-xs" onclick="clonePO(${po.id})" title="Duplicate this PO into a new draft">📋 Clone</button>
        <button class="btn btn-outline btn-xs" onclick="exportSinglePO(${po.id})" title="Export this PO">📄</button>
        ${CAN_DELETE&&(po.status==='draft'||po.status==='cancelled')?`<button class="btn btn-danger btn-xs" onclick="deletePO(${po.id},'${esc(po.po_number)}')" title="Delete this PO">🗑️</button>`:''}
      </td>
    </tr>`).join('');
}
async function deletePO(id,poNumber){
  if(!confirm(`Delete PO ${poNumber}? This cannot be undone.`))return;
  try{
    await api.delete(API.purchaseOrders+'?id='+id);
    toast('PO deleted');
    loadPOs();
  }catch(e){toast(e.message,'error');}
}
// ── Shared CSV helpers ───────────────────────────────────────────────────────
function rowsToCsv(rows){
  return rows.map(r=>r.map(v=>{
    const s=String(v??'');
    return (s.includes(',')||s.includes('"')||s.includes('\n'))?'"'+s.replace(/"/g,'""')+'"':s;
  }).join(',')).join('\r\n');
}
function downloadCsv(csv, filename){
  // Add UTF-8 BOM so Excel opens the file with correct encoding
  const bom = '\uFEFF';
  const blob=new Blob([bom+csv],{type:'text/csv;charset=utf-8;'});
  const a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download=filename;a.click();
}

// ── PO CSV builders ───────────────────────────────────────────────────────────
function buildPOSummaryCsv(pos){
  const hdr=HIDE_COST
    ?['PO #','Vendor','Location','Status','Expected Date','Items','Notes','Created']
    :['PO #','Vendor','Location','Status','Expected Date','Items','Total','Notes','Created'];
  const rows=pos.map(po=>{
    const r=[po.po_number,po.vendor_name||'',po.location_name||'',po.status,
             po.expected_date||'',po.item_count||0];
    if(!HIDE_COST) r.push(Math.round(parseFloat(po.total||0)));
    r.push(po.notes||'',(po.created_at||'').split(' ')[0]);
    return r;
  });
  return rowsToCsv([hdr,...rows]);
}

function buildPOItemsCsv(pos){
  // Order: SKU, Product, Brand, Ordered Qty(Case), Ordered Qty, Unit, [Cost, Line Total,] Received, Pending, PO #, Vendor, Location, Status, Expected Date
  const costCols = HIDE_COST ? [] : ['Cost','Line Total'];
  const hdr = ['SKU','Product','Brand','Ordered Qty(Case)','Ordered Qty','Unit']
    .concat(costCols)
    .concat(['Received','Pending','PO #','Vendor','Location','Status','Expected Date']);
  const rows=[];
  pos.forEach(po=>{
    (po.items||[]).forEach(item=>{
      const qtyOrdered  = item.qty_ordered||0;
      const received    = item.qty_received||0;
      const pending     = qtyOrdered - received;
      const caseContent = item.case_content ? parseFloat(item.case_content) : null;
      const qtyCase     = caseContent ? (qtyOrdered/caseContent).toFixed(2) : '';
      const r = [item.sku||'', item.product_name||'', item.brand||'',
                 qtyCase, qtyOrdered, item.unit||''];
      if(!HIDE_COST){ r.push(Math.round(parseFloat(item.cost||0))); r.push(Math.round(parseFloat(item.cost||0)*qtyOrdered)); }
      r.push(received, pending, po.po_number, po.vendor_name||'', po.location_name||'', po.status, po.expected_date||'');
      rows.push(r);
    });
  });
  return rowsToCsv([hdr,...rows]);
}

async function exportPOs(){
  const status=_poStatusFilter||'';
  const vendor=document.getElementById('po-filter-vendor')?.value||'';
  const params=new URLSearchParams();
  if(status) params.set('status',status);
  if(vendor) params.set('vendor_id',vendor);
  params.set('compact','1');
  try{
    const r=await api.get(API.purchaseOrders+'?'+params);
    const pos=r.data;
    if(!pos.length){toast('No POs to export','error');return;}
    const today=new Date().toISOString().split('T')[0];
    const sfx=(status?'_'+status:'')+'_'+today;
    downloadCsv(buildPOSummaryCsv(pos),'PO_Summary'+sfx+'.csv');
    setTimeout(()=>downloadCsv(buildPOItemsCsv(pos),'PO_LineItems'+sfx+'.csv'),300);
    toast('Exported '+pos.length+' PO'+(pos.length===1?'':'s')+' — 2 CSV files 📊');
  }catch(e){toast(e.message,'error');}
}

async function exportSinglePO(id){
  try{
    const r=await api.get(API.purchaseOrders+'?id='+id);
    const po=r.data;
    if(!po){toast('PO not found','error');return;}
    const safe=po.po_number.replace(/[^a-zA-Z0-9_-]/g,'_');
    const today=new Date().toISOString().split('T')[0];
    downloadCsv(buildPOSummaryCsv([po]),'PO_'+safe+'_Summary_'+today+'.csv');
    if((po.items||[]).length){
      setTimeout(()=>downloadCsv(buildPOItemsCsv([po]),'PO_'+safe+'_LineItems_'+today+'.csv'),300);
    }
    toast('Exported '+po.po_number+' 📄');
  }catch(e){toast(e.message,'error');}
}
function openPOModal(){
  document.getElementById('po-edit-id').value='';
  setElText('po-modal-title', '📋 New Purchase Order');
  document.getElementById('po-items-body').innerHTML='';
  document.getElementById('po-notes').value='';
  document.getElementById('po-expected').value='';
  document.getElementById('po-status').value='draft';
  document.getElementById('po-misc').value='';
  document.getElementById('po-receive-btn').style.display='none';
  populateVendorSelect('po-vendor',null,false,true);populateLocationSelect('po-location');
  addPOItem();
  updatePOTotal();
  openModal('modal-po');
}
async function editPO(id){
  try{
    const r=await api.get(API.purchaseOrders+'?id='+id);
    const po=r.data;
    document.getElementById('po-edit-id').value=po.id;
    setElText('po-modal-title', '📋 Edit PO: '+po.po_number);
    document.getElementById('po-notes').value=po.notes||'';
    document.getElementById('po-expected').value=po.expected_date||'';
    document.getElementById('po-status').value=po.status;
    document.getElementById('po-misc').value=po.misc_charges||'';
    document.getElementById('po-receive-btn').style.display=po.status==='sent'||po.status==='partial'?'inline-flex':'none';
    populateVendorSelect('po-vendor',po.vendor_id,false,true);
    populateLocationSelect('po-location',po.location_id);
    renderPOItems(po.items||[]);
    // updatePOTotal() must run AFTER renderPOItems() populates the items
    // table — calling it before left the "Order Total" showing whatever
    // rows were still in the DOM from the previously open PO (or 0 for a
    // fresh page load), i.e. the wrong total, until the user touched a
    // field and triggered a recalculation themselves.
    updatePOTotal();
    openModal('modal-po');
  }catch(e){toast(e.message,'error');}
}

async function refreshPOPrices(){
  const rows = document.querySelectorAll('#po-items-body tr');
  if(!rows.length){ toast('No items to refresh','error'); return; }
  const vendorId = document.getElementById('po-vendor')?.value;
  const formula  = vendorId ? await getVendorFormula(vendorId) : null;
  let updated = 0;
  const products = await getProductsCache();
  rows.forEach(function(row){
    const sel = row.querySelector('select[id^=poi-prod]');
    if(!sel||!sel.value) return;
    const prod = products.find(function(p){ return String(p.id)===String(sel.value); });
    if(!prod) return;
    const costInput = row.querySelector('input[id^=poi-cost]');
    const listInput = row.querySelector('input[id^=poi-listprice]');
    const listPrice = parseFloat(prod.list_price||prod.sell||0);
    // Prefer: vendor formula applied to list price; fallback to product cost
    if(formula && formula.length && listPrice){
      const result = computeFormula(listPrice, formula);
      if(costInput){ costInput.value = Math.round(result.final); costInput.dispatchEvent(new Event('input',{bubbles:true})); }
      if(listInput){ listInput.value = fmtN(listPrice); }
    } else {
      const directCost = parseFloat(prod.cost||0);
      if(costInput && directCost){ costInput.value = Math.round(directCost); costInput.dispatchEvent(new Event('input',{bubbles:true})); }
      if(listInput && listPrice)  listInput.value = fmtN(listPrice);
    }
    updated++;
  });
  updatePOTotal();
  toast('Refreshed prices for '+updated+' item'+(updated===1?'':'s')+' ✅');
}
function renderPOItems(items=[]){
  const tbody=document.getElementById('po-items-body');
  tbody.innerHTML=items.map((item,i)=>{
    const lineTotal=(item.qty_ordered||0)*(item.cost||0);
    const caseContent=item.case_content||0;
    // Reverse-derive Qty (Cases) for existing items so the field isn't blank when editing.
    // Round-trips cleanly through calcPOQtyFromCases (Qty Ordered = Qty Cases × Case Content).
    const qtyCases=(caseContent>0 && item.qty_ordered) ? (Math.round((item.qty_ordered/caseContent)*100)/100) : '';
    return `<tr data-item-id="${item.id||''}">
    <td><select class="form-control" id="poi-prod-${i}" style="background:var(--surface3);min-width:200px;width:100%" onchange="autofillPOCost(this)"></select></td>
    <td><input type="number" class="form-control" id="poi-qtycases-${i}" value="${qtyCases}" min="0" step="0.01" style="background:var(--surface3);width:60px" oninput="calcPOQtyFromCases(this)"></td>
    ${HIDE_COST
      ? `<input type="hidden" id="poi-listprice-${i}" value="">`
      : `<td><input type="number" class="form-control" id="poi-listprice-${i}" placeholder="List ₹" step="0.01" style="background:var(--surface3);width:68px;font-family:var(--mono);font-size:.8rem" onfocus="clearIfZero(this)" oninput="autoCalcCostFromList('po-vendor','poi-listprice-${i}','poi-cost-${i}')"></td>`}
    ${HIDE_COST
      ? `<input type="hidden" id="poi-cost-${i}" value="${fmtN(item.cost||0)}">`
      : `<td><input type="number" class="form-control" id="poi-cost-${i}" value="${fmtN(item.cost||0)}" step="0.01" onfocus="clearIfZero(this)" style="background:var(--surface3);width:68px;font-family:var(--mono)" oninput="updatePOTotal()"></td>`}
    <td><input type="text" class="form-control" id="poi-case-${i}" value="${item.case_content||''}" readonly style="background:var(--surface3);width:55px;font-family:var(--mono);color:var(--text3);cursor:default"></td>
    <td><input type="number" class="form-control" id="poi-qty-${i}" value="${item.qty_ordered||1}" min="1" style="background:var(--surface3);width:60px" oninput="updatePOTotal();syncPOReceivedMax(this)"></td>
    <td><input type="number" class="form-control" id="poi-recv-${i}" value="${item.qty_received||0}" min="0" max="${item.qty_ordered||0}" style="width:60px" oninput="clampPOReceivedQty(this)" title="Editable — correcting this adjusts stock and is logged as a stock-in correction"></td>
    ${HIDE_COST ? '' : `<td><span class="mono" id="poi-linetotal-${i}" style="font-size:.85rem;font-weight:600;color:var(--text2)">${CUR.sym}${fmtN(lineTotal)}</span></td>`}
    <td style="white-space:nowrap"><button class="btn btn-ghost btn-xs" onclick="addPOItem()" title="Add item below">+ Add Item</button> <button class="btn btn-danger btn-xs" onclick="this.closest('tr').remove()" title="Remove">✕</button></td>
  </tr>`;
  }).join('');
  getProductsCache().then(function(products){
    items.forEach(function(item,i){
      const sel=document.getElementById('poi-prod-'+i);if(!sel)return;
      populateProductSelectEl(sel, products, item.product_id, '— Select —');
      const listInput=document.getElementById('poi-listprice-'+i);
      if(listInput){
        const p=products.find(function(p){return String(p.id)===String(item.product_id);});
        if(p && p.list_price) listInput.value=fmtN(p.list_price);
      }
    });
  }).catch(function(){});
}
function calcPOQtyFromCases(input){
  const row = input.closest('tr');
  if(!row) return;
  const caseInput = row.querySelector('[id^=poi-case]');
  const qtyInput  = row.querySelector('[id^="poi-qty-"]');
  if(!qtyInput) return;
  const caseContent = caseInput ? parseFloat(caseInput.value)||0 : 0;
  const qtyCases    = parseFloat(input.value)||0;
  if(caseContent > 0){
    qtyInput.value = Math.round(qtyCases * caseContent);
  } else {
    // No case content — treat qty cases as direct qty
    qtyInput.value = Math.round(qtyCases) || 1;
  }
  updatePOTotal();
}

function autofillPOCost(sel){
  const opt=sel.options[sel.selectedIndex];
  if(!opt||!opt.value) return;
  const cost=opt.getAttribute('data-cost');
  const row=sel.closest('tr');
  if(!row) return;
  // Fill cost
  const costInput=row.querySelector('[id^=poi-cost]');
  if(costInput && cost!==null && cost!=='') costInput.value=Math.round(parseFloat(cost));
  // Fill case content and List Rate from product cache
  const prodId = opt.value;
  const caseInput = row.querySelector('[id^=poi-case]');
  const listInput = row.querySelector('[id^=poi-listprice]');
  if(prodId){
    getProductsCache().then(function(products){
      const p = products.find(function(p){ return String(p.id)===String(prodId); });
      if(caseInput) caseInput.value = p && p.case_content ? p.case_content : '';
      if(listInput) listInput.value = p && p.list_price ? fmtN(p.list_price) : '';
    }).catch(function(){});
  } else {
    if(caseInput) caseInput.value='';
    if(listInput) listInput.value='';
  }
  // Clear qty (cases) when product changes — user re-enters
  const qtyCasesInput = row.querySelector('[id^=poi-qtycases]');
  if(qtyCasesInput) qtyCasesInput.value='';
  updatePOTotal();
}
function autofillSICost(sel){
  const opt=sel.options[sel.selectedIndex];
  if(!opt||!opt.value) return;
  const cost=opt.getAttribute('data-cost');
  if(cost===null||cost==='') return;
  const el=document.getElementById('si-cost');
  if(el) el.value=Math.round(parseFloat(cost));
}
let _cachedProducts=null;
async function getProductsCache(){
  if(_cachedProducts) return _cachedProducts;
  const r=await api.get(API.products);
  _cachedProducts=r.data;
  return _cachedProducts;
}
function invalidateProductsCache(){ _cachedProducts=null; }

// Bulk-recalculate Cost Price = f(List Price) for every product that has both
// a list_price and a vendor with a pricing formula. Useful after setting up
// or changing a vendor's formula — applies retroactively to existing products.
async function recalcAllProductCosts(){
  if(!confirm('Recalculate Cost Price from List Price for ALL products that have a List Price and a Vendor set?\n\nVendors with a pricing formula use it; vendors without one get Cost = List Price.\n\nThis will overwrite the current Cost Price for those products.')) return;

  // Operate on _productData directly — this is what renderProductTable() reads from.
  // (getProductsCache() returns a separate copy and updating it alone wouldn't refresh the visible table.)
  const candidates = _productData.filter(p=>p.list_price && +p.list_price>0 && p.vendor_id);
  if(!candidates.length){ toast('No products have both a List Price and a Vendor set','info'); return; }

  let updated=0, unchanged=0;
  for(const p of candidates){
    const formula = await getVendorFormula(p.vendor_id);
    const result = computeFormula(+p.list_price, formula); // empty formula => cost = list price
    const newCost = Math.round(result.final);
    if(Math.abs(newCost - (+p.cost||0)) < 0.005){ unchanged++; continue; }
    try{
      await api.put(API.products,{id:p.id,_bulk:true,cost:newCost});
      p.cost = newCost;
      p.margin = p.sell>0 ? +(((+p.sell-newCost)/+p.sell)*100).toFixed(1) : 0;
      updated++;
    }catch{}
  }
  invalidateProductsCache(); // so getProductsCache() refetches with fresh costs elsewhere
  renderProductTable();
  toast('Recalculated '+updated+' product(s)'+(unchanged?', '+unchanged+' already up to date':''), 'success');
}

function addPOItem(preSelectId){
  const tbody=document.getElementById('po-items-body');
  const i=tbody.rows.length;
  const tr=document.createElement('tr');
  tr.innerHTML=`<td><select class="form-control" id="poi-prod-${i}" style="background:var(--surface3);min-width:200px;width:100%" onchange="autofillPOCost(this)"><option value="">— Select Product —</option></select></td>
    <td><input type="number" class="form-control" id="poi-qtycases-${i}" value="" min="0" step="1" style="background:var(--surface3);width:60px" oninput="calcPOQtyFromCases(this)"></td>
    ${HIDE_COST
      ? `<input type="hidden" id="poi-listprice-${i}" value="">`
      : `<td><input type="number" class="form-control" id="poi-listprice-${i}" placeholder="List ₹" step="0.01" style="background:var(--surface3);width:68px;font-family:var(--mono);font-size:.8rem" onfocus="clearIfZero(this)" oninput="autoCalcCostFromList('po-vendor','poi-listprice-${i}','poi-cost-${i}')"></td>`}
    ${HIDE_COST
      ? `<input type="hidden" id="poi-cost-${i}" value="0">`
      : `<td><input type="number" class="form-control" id="poi-cost-${i}" value="0" step="0.01" onfocus="clearIfZero(this)" style="background:var(--surface3);width:68px;font-family:var(--mono)" oninput="updatePOTotal()"></td>`}
    <td><input type="text" class="form-control" id="poi-case-${i}" value="" readonly style="background:var(--surface3);width:55px;font-family:var(--mono);color:var(--text3);cursor:default"></td>
    <td><input type="number" class="form-control" id="poi-qty-${i}" value="1" min="1" style="background:var(--surface3);width:60px" oninput="updatePOTotal()"></td>
    <td><input type="number" class="form-control" id="poi-recv-${i}" value="0" min="0" style="background:var(--surface3);width:60px" readonly></td>
    ${HIDE_COST ? '' : `<td><span class="mono" id="poi-linetotal-${i}" style="font-size:.85rem;font-weight:600;color:var(--text2)">—</span></td>`}
    <td style="white-space:nowrap"><button class="btn btn-ghost btn-xs" onclick="addPOItem()" title="Add item below">+ Add Item</button> <button class="btn btn-danger btn-xs" onclick="this.closest('tr').remove()" title="Remove">✕</button></td>`;

  tbody.appendChild(tr);
  getProductsCache().then(function(products){
    const sel=document.getElementById('poi-prod-'+i);if(!sel)return;
    populateProductSelectEl(sel, products, preSelectId||null, '— Select Product —');
    if(preSelectId){ sel.value=String(preSelectId); autofillPOCost(sel); }
  }).catch(function(){});
}
// Keeps the (editable, existing-item-only) Qty Received field's max in sync
// when Qty Ordered changes, and clamps its current value down if it now
// exceeds the new max — you can't have received more than was ordered.
function syncPOReceivedMax(qtyInput){
  const row=qtyInput.closest('tr');if(!row)return;
  const recvInput=row.querySelector('[id^="poi-recv-"]');
  if(!recvInput||recvInput.readOnly)return;
  const maxQty=parseInt(qtyInput.value)||0;
  recvInput.max=maxQty;
  if((parseInt(recvInput.value)||0)>maxQty) recvInput.value=maxQty;
}
function clampPOReceivedQty(recvInput){
  const max=parseInt(recvInput.max)||0;
  let v=parseInt(recvInput.value)||0;
  if(v<0)v=0;
  if(v>max)v=max;
  recvInput.value=v;
}
function updatePOTotal(){
  const rows=document.querySelectorAll('#po-items-body tr');
  let itemsTotal=0, totalCases=0;
  rows.forEach(function(row){
    const qty=parseFloat(row.querySelector('[id^="poi-qty-"]')?.value)||0;
    const cost=parseFloat(row.querySelector('[id^="poi-cost-"]')?.value)||0;
    const lineTotal=qty*cost;
    itemsTotal+=lineTotal;
    // Update per-row line total display
    const ltEl=row.querySelector('[id^=poi-linetotal]');
    if(ltEl) ltEl.textContent=CUR.sym+fmtN(lineTotal);
    totalCases+=parseFloat(row.querySelector('[id^="poi-qtycases-"]')?.value)||0;
  });
  const casesEl=document.getElementById('po-total-cases');
  if(casesEl) casesEl.textContent=(Math.round(totalCases*100)/100).toString();
  const misc=parseFloat(document.getElementById('po-misc')?.value)||0;
  const total=itemsTotal+misc;
  const el=document.getElementById('po-total-display');
  if(el) el.textContent=CUR.sym+fmtN(total);
  setAmountWordsDisplay('po-total-words', total);
}
async function savePO(){
  const editId=parseInt(document.getElementById('po-edit-id').value)||0;
  const rows=document.getElementById('po-items-body').rows;
  const items=[];
  for(let i=0;i<rows.length;i++){
    const pid=rows[i].querySelector(`[id^=poi-prod]`)?.value;
    if(!pid)continue;
    const itemId=parseInt(rows[i].dataset.itemId)||0;
    items.push({
      id: itemId||undefined,
      product_id:pid,
      qty_ordered:parseInt(rows[i].querySelector('[id^="poi-qty-"]')?.value)||1,
      qty_received:parseInt(rows[i].querySelector(`[id^=poi-recv]`)?.value)||0,
      cost:parseFloat(rows[i].querySelector(`[id^=poi-cost]`)?.value)||0
    });
  }
  if(!items.length){toast('Add at least one item','error');return;}
  const body={vendor_id:document.getElementById('po-vendor').value,location_id:document.getElementById('po-location').value||null,expected_date:document.getElementById('po-expected').value,status:document.getElementById('po-status').value,notes:document.getElementById('po-notes').value.trim(),misc_charges:parseFloat(document.getElementById('po-misc')?.value)||0,items};
  const btn=document.getElementById('po-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){body.id=editId;await api.put(API.purchaseOrders,body);toast('PO updated!');}
    else{await api.post(API.purchaseOrders,body);toast('Purchase Order created!');}
    closeModal('modal-po');clearAllSearchableSelects();loadPOs();invalidateProductsCache();loadProducts();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='Save PO';}
}
// Opens the partial-receive modal for a PO: shows ordered/received/pending per
// item with an editable "Receive Now" qty, defaulting to the full pending amount
// so the old "receive everything" behavior is still one click (Fill All Pending
// + Receive), while also supporting partial deliveries.
async function openReceivePO(id){
  try{
    const r=await api.get(API.purchaseOrders+'?id='+id);
    const po=r.data;
    document.getElementById('po-receive-id').value=po.id;
    setElText('po-receive-title','📥 Receive Items — '+po.po_number);
    const items=po.items||[];
    const tbody=document.getElementById('po-receive-body');
    const emptyEl=document.getElementById('po-receive-empty');
    const rowsHtml=items.map(it=>{
      const ordered=+it.qty_ordered||0, recvd=+it.qty_received||0;
      const pending=Math.max(0,ordered-recvd);
      return `<tr data-item-id="${it.id}">
        <td>${esc(it.product_name||'')}${it.sku?`<div style="font-size:.72rem;color:var(--text3)">${esc(it.sku)}</div>`:''}</td>
        <td class="mono">${ordered}</td>
        <td class="mono">${recvd}</td>
        <td class="mono" style="font-weight:600;color:${pending>0?'var(--yellow)':'var(--text3)'}">${pending}</td>
        <td><input type="number" class="form-control" id="po-recv-now-${it.id}" data-pending="${pending}" value="${pending}" min="0" max="${pending}" style="width:80px;background:var(--surface3)" ${pending<=0?'disabled':''}></td>
      </tr>`;
    }).join('');
    tbody.innerHTML=rowsHtml;
    const anyPending=items.some(it=>((+it.qty_ordered||0)-(+it.qty_received||0))>0);
    emptyEl.style.display=anyPending?'none':'block';
    document.getElementById('po-receive-confirm-btn').style.display=anyPending?'inline-flex':'none';
    openModal('modal-po-receive');
  }catch(e){toast(e.message,'error');}
}
function fillPOReceiveAllPending(){
  document.querySelectorAll('#po-receive-body input[id^="po-recv-now-"]').forEach(function(input){
    input.value=input.dataset.pending||0;
  });
}
async function confirmReceivePOItems(){
  const id=parseInt(document.getElementById('po-receive-id').value)||0;
  if(!id)return;
  const receive={};
  let any=false;
  document.querySelectorAll('#po-receive-body tr[data-item-id]').forEach(function(row){
    const itemId=row.dataset.itemId;
    const input=document.getElementById('po-recv-now-'+itemId);
    const qty=parseInt(input?.value)||0;
    if(qty>0){receive[itemId]=qty;any=true;}
  });
  if(!any){toast('Enter a quantity to receive for at least one item','error');return;}
  const btn=document.getElementById('po-receive-confirm-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    const r=await api.put(API.purchaseOrders,{id,receive});
    toast(r.message||'Items received and stock updated!');
    closeModal('modal-po-receive');loadPOs();updateAlertBadge();invalidateProductsCache();loadProducts();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='📥 Receive & Update Stock';}
}
// The Edit PO modal's "Receive & Create Stock-In" button — routes to the same
// partial-receive modal as the list's Receive button (previously called an
// undefined function and did nothing when clicked).
function receivePO(){
  const id=parseInt(document.getElementById('po-edit-id').value)||0;
  if(!id){toast('Save the PO first','error');return;}
  closeModal('modal-po');
  openReceivePO(id);
}
// Clone an existing PO into a new draft PO with the same vendor, location,
// items and costs. Received quantities are NOT copied — the clone starts
// fresh so it can be sent to the vendor as a new order.
async function clonePO(id){
  try{
    const r=await api.get(API.purchaseOrders+'?id='+id);
    const po=r.data;
    document.getElementById('po-edit-id').value='';
    setElText('po-modal-title','📋 New Purchase Order (cloned from '+po.po_number+')');
    document.getElementById('po-notes').value=po.notes||'';
    document.getElementById('po-expected').value='';
    document.getElementById('po-status').value='draft';
    document.getElementById('po-misc').value=po.misc_charges||'';
    document.getElementById('po-receive-btn').style.display='none';
    populateVendorSelect('po-vendor',po.vendor_id,false,true);
    populateLocationSelect('po-location',po.location_id);
    const clonedItems=(po.items||[]).map(it=>({
      product_id:it.product_id, qty_ordered:it.qty_ordered, cost:it.cost, case_content:it.case_content
      // id and qty_received intentionally omitted — this is a new, unreceived PO
    }));
    renderPOItems(clonedItems);
    updatePOTotal();
    openModal('modal-po');
    toast('Cloned '+po.po_number+' — review items and Save PO','info');
  }catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// TRANSFERS
// ══════════════════════════════════════════════════════════
async function loadTransferStock(){
  const pid=document.getElementById('tr-product')?.value;
  const fromLocId=document.getElementById('tr-from')?.value;
  const toLocId=document.getElementById('tr-to')?.value;
  const info=document.getElementById('tr-stock-info');
  if(!pid||!fromLocId){if(info)info.style.display='none';return;}
  try{
    const [fromR,toR]=await Promise.all([
      api.get(API.locations+'?id='+fromLocId),
      toLocId?api.get(API.locations+'?id='+toLocId):Promise.resolve(null)
    ]);
    const fromPl=fromR.data.products?.find(p=>p.product_id==pid);
    const fromQty=fromPl?fromPl.stock:0;
    const unit=fromPl?(fromPl.unit||''):'';
    const fromColor=fromQty<=0?'var(--red)':fromQty<5?'var(--orange)':'var(--green)';
    let html='<div style="display:flex;gap:16px;flex-wrap:wrap">';
    html+='<span>📤 <b>'+esc(fromR.data.name)+'</b> (From): <b style="color:'+fromColor+'">'+fromQty+(unit?' '+esc(unit):'')+'</b></span>';
    if(toR){
      const toPl=toR.data.products?.find(p=>p.product_id==pid);
      const toQty=toPl?toPl.stock:0;
      html+='<span>📥 <b>'+esc(toR.data.name)+'</b> (To): <b style="color:'+(toQty<=0?'var(--text3)':'var(--green)')+'">'+toQty+(unit?' '+esc(unit):'')+'</b></span>';
    }
    html+='</div>';
    if(fromQty<=0) html+='<div style="color:var(--red);font-size:.75rem;margin-top:4px">No stock at '+esc(fromR.data.name)+'</div>';
    if(info){info.style.display='block';info.innerHTML=html;}
    const qi=document.getElementById('tr-qty');if(qi)qi.max=fromQty;
  }catch(e){if(info)info.style.display='none';}
}

async function recordTransfer(){
  const pid=document.getElementById('tr-product').value;const from=document.getElementById('tr-from').value;const to=document.getElementById('tr-to').value;const qty=document.getElementById('tr-qty').value;
  if(!pid||!from||!to||!qty||+qty<1){toast('Fill all required fields','error');return;}
  const btn=document.getElementById('tr-submit');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    const r=await api.post(API.transfers,{product_id:pid,from_location:from,to_location:to,qty,date:document.getElementById('tr-date').value,note:document.getElementById('tr-note').value.trim()});
    toast(r.message);['tr-qty','tr-note'].forEach(id=>{document.getElementById(id).value='';});loadTransfers();loadTransferStock();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='🔄 Record Transfer';}
}
// ── Quick Transfer (Products page 🔄 button) ────────────────────────
let _qtProductId=null;
async function openQuickTransfer(productId,productName){
  _qtProductId=productId;
  const nameEl=document.getElementById('qt-product-name');
  if(nameEl)nameEl.textContent=productName||'';
  const dateEl=document.getElementById('qt-date');
  if(dateEl&&!dateEl.value){const n=new Date();dateEl.value=n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');}
  const qtyEl=document.getElementById('qt-qty');if(qtyEl)qtyEl.value='';
  const noteEl=document.getElementById('qt-note');if(noteEl)noteEl.value='';
  const warnEl=document.getElementById('qt-qty-warn');if(warnEl)warnEl.style.display='none';
  await Promise.all([populateLocationSelect('qt-from'),populateLocationSelect('qt-to')]);
  openModal('modal-quick-transfer');
  loadQTStock();
}
async function loadQTStock(){
  const fromLocId=document.getElementById('qt-from')?.value;
  const toLocId=document.getElementById('qt-to')?.value;
  const fromInfo=document.getElementById('qt-from-stock');
  const toInfo=document.getElementById('qt-to-stock');
  if(!_qtProductId||!fromLocId){if(fromInfo)fromInfo.textContent='';if(toInfo)toInfo.textContent='';return;}
  try{
    const [fromR,toR]=await Promise.all([
      api.get(API.locations+'?id='+fromLocId),
      toLocId?api.get(API.locations+'?id='+toLocId):Promise.resolve(null)
    ]);
    const fromPl=fromR.data.products?.find(p=>p.product_id==_qtProductId);
    const fromQty=fromPl?fromPl.stock:0;
    if(fromInfo){fromInfo.style.color=fromQty<=0?'var(--red)':fromQty<5?'var(--orange)':'var(--green)';fromInfo.textContent='Available: '+fromQty;}
    if(toR){
      const toPl=toR.data.products?.find(p=>p.product_id==_qtProductId);
      const toQty=toPl?toPl.stock:0;
      if(toInfo){toInfo.style.color='var(--text3)';toInfo.textContent='Current: '+toQty;}
    }else if(toInfo){toInfo.textContent='';}
    const qtyEl=document.getElementById('qt-qty');if(qtyEl)qtyEl.max=fromQty;
    validateQTQty();
  }catch(e){
    if(fromInfo)fromInfo.textContent='';
    if(toInfo)toInfo.textContent='';
  }
}
function validateQTQty(){
  const qtyEl=document.getElementById('qt-qty');
  const warnEl=document.getElementById('qt-qty-warn');
  if(!qtyEl||!warnEl)return;
  const max=qtyEl.max?+qtyEl.max:null;
  const val=+qtyEl.value||0;
  warnEl.style.display=(max!==null&&val>max)?'':'none';
}
async function submitQuickTransfer(){
  const from=document.getElementById('qt-from')?.value;
  const to=document.getElementById('qt-to')?.value;
  const qty=document.getElementById('qt-qty')?.value;
  const date=document.getElementById('qt-date')?.value;
  const note=document.getElementById('qt-note')?.value.trim()||'';
  if(!_qtProductId){toast('No product selected','error');return;}
  if(!from||!to||!qty||+qty<1){toast('Fill all required fields','error');return;}
  if(from===to){toast('From and To locations must be different','error');return;}
  const btn=document.getElementById('qt-submit');
  if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';}
  try{
    const r=await api.post(API.transfers,{product_id:_qtProductId,from_location:from,to_location:to,qty,date,note});
    toast(r.message||'Transfer recorded');
    closeModal('modal-quick-transfer');
    invalidateProductsCache();loadProducts();
  }catch(e){
    toast(e.message,'error');
  }finally{
    if(btn){btn.disabled=false;btn.innerHTML='&#x1F504; Transfer';}
  }
}

async function loadTransfers(){
  try{
    const r=await api.get(API.transfers);
    const tbody=document.getElementById('tr-history');const empty=document.getElementById('tr-empty');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=r.data.map(t=>`<tr>
      <td class="mono" style="font-size:.78rem">${t.date}</td>
      <td>${esc(t.product_name)}</td>
      <td><span class="badge badge-orange">${esc(t.from_name)}</span></td>
      <td><span class="badge badge-green">${esc(t.to_name)}</span></td>
      <td class="mono text-accent">→${t.qty} ${esc(t.unit)}</td>
      <td style="color:var(--text3);font-size:.79rem">${esc(t.note||'—')}</td>
      <td>${CAN_DELETE?`<button class="btn btn-ghost btn-xs" onclick="reverseTransfer(${t.id})" title="Reverse">↩️</button>`:''}</td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
async function reverseTransfer(id){if(!confirm('Reverse this transfer?'))return;try{const r=await api.delete(API.transfers+'?id='+id);toast(r.message);loadTransfers();}catch(e){toast(e.message,'error');}}

// ══════════════════════════════════════════════════════════
// ADJUSTMENTS
// ══════════════════════════════════════════════════════════
async function recordAdjustment(){
  const pid=document.getElementById('adj-product').value;const qty=document.getElementById('adj-qty').value;const reason=document.getElementById('adj-reason').value;
  if(!pid||!qty||qty==='0'){toast('Select product and enter a non-zero quantity','error');return;}
  const btn=document.getElementById('adj-submit');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    const r=await api.post(API.adjustments,{product_id:pid,location_id:document.getElementById('adj-location').value||null,qty_change:parseInt(qty),reason,date:document.getElementById('adj-date').value,note:document.getElementById('adj-note').value.trim()});
    toast(r.message);['adj-qty','adj-note'].forEach(id=>{document.getElementById(id).value='';});loadAdjustments();updateAlertBadge();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='⚖️ Record Adjustment';}
}
let _adjRows=[];
async function loadAdjustments(){
  try{
    const r=await api.get(API.adjustments);
    _adjRows=r.data||[];
    const tbody=document.getElementById('adj-history');const empty=document.getElementById('adj-empty');
    if(!_adjRows.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=_adjRows.map(a=>`<tr>
      <td class="mono" style="font-size:.78rem">${a.date}</td>
      <td>${esc(a.product_name)}</td>
      <td style="font-size:.8rem;color:var(--text2)">${esc(a.location_name||'—')}</td>
      <td class="mono" style="font-weight:700;color:${+a.qty_change>0?'var(--green)':'var(--red)'}">${+a.qty_change>0?'+':''}${a.qty_change} ${esc(a.unit)}</td>
      <td><span class="badge ${a.reason==='damage'?'badge-red':a.reason==='theft'?'badge-orange':a.reason==='correction'?'badge-blue':'badge-gray'}">${a.reason}</span></td>
      <td style="color:var(--text3);font-size:.79rem">${esc(a.note||'—')}</td>
      <td style="white-space:nowrap"><button class="btn btn-ghost btn-xs" onclick="openAdjustmentEdit(${a.id})" title="Edit">✏️</button> ${CAN_DELETE?`<button class="btn btn-ghost btn-xs" onclick="reverseAdjustment(${a.id})" title="Reverse">↩️</button>`:''}</td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
async function openAdjustmentEdit(id){
  const a=_adjRows.find(function(x){return x.id===id;});
  if(!a){toast('Adjustment not found','error');return;}
  document.getElementById('adj-edit-id').value=a.id;
  document.getElementById('adj-edit-product').textContent=a.product_name+(a.unit?' ('+a.unit+')':'');
  document.getElementById('adj-edit-qty').value=a.qty_change;
  document.getElementById('adj-edit-reason').value=a.reason;
  document.getElementById('adj-edit-date').value=a.date;
  document.getElementById('adj-edit-note').value=a.note||'';
  await populateLocationSelect('adj-edit-location', a.location_id||null);
  openModal('modal-adj-edit');
}
async function saveAdjustmentEdit(){
  const id=parseInt(document.getElementById('adj-edit-id').value)||0;
  const qty=document.getElementById('adj-edit-qty').value;
  const reason=document.getElementById('adj-edit-reason').value;
  if(!id||!qty||qty==='0'){toast('Enter a non-zero quantity','error');return;}
  const btn=document.getElementById('adj-edit-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    const r=await api.put(API.adjustments,{
      id,
      qty_change:parseInt(qty),
      location_id:document.getElementById('adj-edit-location').value||null,
      reason,
      date:document.getElementById('adj-edit-date').value,
      note:document.getElementById('adj-edit-note').value.trim()
    });
    toast(r.message||'Adjustment updated');
    closeModal('modal-adj-edit');
    loadAdjustments();updateAlertBadge();invalidateProductsCache();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='💾 Save';}
}
async function reverseAdjustment(id){if(!confirm('Reverse this adjustment?'))return;try{const r=await api.delete(API.adjustments+'?id='+id);toast(r.message);loadAdjustments();updateAlertBadge();}catch(e){toast(e.message,'error');}}

// ══════════════════════════════════════════════════════════
// REPORTS
// ══════════════════════════════════════════════════════════
let chartTopsell=null,chartProfit=null;
let _rptActiveTab = 'overview';

function switchRptTab(tab){
  _rptActiveTab = tab;
  document.querySelectorAll('.rpt-tab').forEach(function(b){
    b.classList.toggle('btn-primary', b.dataset.tab===tab);
    b.classList.toggle('btn-outline', b.dataset.tab!==tab);
  });
  ['overview','vp','paidto','paidby','lowstock','picking'].forEach(function(t){
    const el=document.getElementById('rpt-tab-'+t); if(el) el.style.display = t===tab?'':'none';
  });
  // Update export button label and reload correct data
  const labels={overview:'📊 Export All',vp:'📊 Export VP',paidto:'📊 Export Paid To',paidby:'📊 Export Paid By',lowstock:'📊 Export',picking:'📊 Export'};
  const exportBtn = document.getElementById('rpt-export-btn');
  if(exportBtn) exportBtn.textContent = labels[tab]||'📊 Export';
  if(tab==='overview') loadReports();
  else if(tab==='vp') loadRptVP();
  else if(tab==='paidto') loadRptPaidTo();
  else if(tab==='paidby') loadRptPaidBy();
  else if(tab==='lowstock') loadRptLowStock();
  else if(tab==='picking') loadRptPicking();
}

function onRptDateChange(){
  if(_rptActiveTab==='overview') loadReports();
  else if(_rptActiveTab==='vp') loadRptVP();
  else if(_rptActiveTab==='paidto') loadRptPaidTo();
  else if(_rptActiveTab==='paidby') loadRptPaidBy();
  else if(_rptActiveTab==='lowstock') loadRptLowStock();
  // 'picking' deliberately not reloaded here — it shows full history
  // (like the Order Picking dashboard), not the overview date range.
}

function onRptExport(){
  if(_rptActiveTab==='overview') exportExcel('all');
  else if(_rptActiveTab==='vp') exportRptVP();
  else if(_rptActiveTab==='paidto') exportRptPaidTo();
  else if(_rptActiveTab==='paidby') exportRptPaidBy();
  else if(_rptActiveTab==='picking') exportRptPicking();
  else if(_rptActiveTab==='lowstock'){
    // Simple CSV export from table
    const rows=[['Product','Brand','Category','Stock','Min Stock','Deficit','Vendor']];
    document.querySelectorAll('#rpt-alert-body tr').forEach(function(tr){
      rows.push(Array.from(tr.querySelectorAll('td')).map(function(td){return td.textContent.trim();}));
    });
    downloadCsv(rowsToCsv(rows),'LowStock_'+new Date().toISOString().split('T')[0]+'.csv');
    toast('Exported 📊');
  }
}

function setReportRange(range){
  const now=new Date();let from,to=today();
  if(range==='month'){from=new Date(now.getFullYear(),now.getMonth(),1).toISOString().split('T')[0];}
  else if(range==='quarter'){const q=Math.floor(now.getMonth()/3);from=new Date(now.getFullYear(),q*3,1).toISOString().split('T')[0];}
  else if(range==='year'){from=new Date(now.getFullYear(),0,1).toISOString().split('T')[0];}
  else{from='';to='';}
  document.getElementById('rpt-from').value=from||'';
  document.getElementById('rpt-to').value=to;
  onRptDateChange();
}
async function loadReports(){
  const locId=getLocationId();
  const from=document.getElementById('rpt-from')?.value;const to=document.getElementById('rpt-to')?.value;
  const buildQ=(extra='')=>{const p=new URLSearchParams();if(locId)p.set('location_id',locId);if(from)p.set('from',from);if(to)p.set('to',to);if(extra){const[k,v]=extra.split('=');p.set(k,v);}return p.toString()?'?'+p:'';}
  try{
    const[dash,pnl,sv,vs,locs]=await Promise.all([
      api.get(API.dashboard+buildQ()),
      api.get(API.dashboard+buildQ('report=pnl')),
      api.get(API.dashboard+buildQ('report=stock_value')),
      api.get(API.dashboard+buildQ('report=vendor_summary')),
      api.get(API.dashboard+'?report=location_summary'),
    ]);
    const s=dash.data.stats;
    document.getElementById('report-stats').innerHTML=`
      <div class="stat-card" style="--accent-color:var(--green)"><span class="stat-icon">💵</span><span class="stat-num">${CUR.sym}${fmt(s.total_revenue)}</span><span class="stat-label">Revenue</span></div>
      <div class="stat-card" style="--accent-color:var(--orange)"><span class="stat-icon">🏭</span><span class="stat-num">${CUR.sym}${fmt(s.total_cogs)}</span><span class="stat-label">COGS</span></div>
      <div class="stat-card" style="--accent-color:${+s.total_profit>=0?'var(--green)':'var(--red)'}"><span class="stat-icon">📊</span><span class="stat-num" style="color:${+s.total_profit>=0?'var(--green)':'var(--red)'}">${CUR.sym}${fmt(s.total_profit)}</span><span class="stat-label">Net Profit</span></div>
      ${HIDE_STOCK_VALUE?'':`<div class="stat-card" style="--accent-color:var(--accent)"><span class="stat-icon">📦</span><span class="stat-num">${CUR.sym}${fmt(s.stock_value)}</span><span class="stat-label">Stock Value</span></div>`}`;

    document.getElementById('report-pnl').innerHTML=pnl.data.length
      ?pnl.data.map(r=>`<tr>
          <td><strong>${esc(r.product)}</strong>${r.brand?` <span class="badge badge-orange" style="font-size:.62rem">${esc(r.brand)}</span>`:''}</td>
          <td class="mono">${r.sold_qty}</td><td class="mono">${CUR.sym}${fmtN(r.revenue)}</td><td class="mono">${CUR.sym}${fmtN(r.cogs)}</td>
          <td class="mono ${+r.profit>=0?'text-green':'text-red'}" style="font-weight:700">${CUR.sym}${fmtN(r.profit)}</td>
          <td><span class="badge ${+r.margin_pct>20?'badge-green':+r.margin_pct>10?'badge-blue':'badge-red'}">${r.margin_pct}%</span></td>
        </tr>`).join('')
      :'<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text3)">No sales yet</td></tr>';

    document.getElementById('report-value').innerHTML=sv.data.length
      ?sv.data.map(r=>`<tr><td>${esc(r.name)}</td><td>${r.brand?`<span class="badge badge-orange" style="font-size:.62rem">${esc(r.brand)}</span>`:'—'}</td><td class="mono">${r.stock} ${esc(r.unit)}</td><td class="mono text-accent">${CUR.sym}${fmtN(r.cost_value)}</td><td class="mono text-green">${CUR.sym}${fmtN(r.sell_value)}</td></tr>`).join('')
      :'<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text3)">No products</td></tr>';

    document.getElementById('report-vendor').innerHTML=vs.data.length
      ?vs.data.map(r=>'<tr><td>'+esc(r.vendor)+'</td><td class="mono">'+r.purchases+'</td><td class="mono">'+r.total_qty+'</td><td class="mono text-accent">'+CUR.sym+fmtN(r.total_amount)+'</td><td class="mono" style="color:var(--text3)">'+(r.last_purchase||'—')+'</td><td>'+(r.vendor_id?'<button class="btn btn-ghost btn-xs" onclick="openVendorLedgerReport('+r.vendor_id+',\''+esc(r.vendor)+'\')">📒 Ledger</button>':'')+'</td></tr>').join('')
      :'<tr><td colspan="6" style="text-align:center;padding:24px;color:var(--text3)">No purchases</td></tr>';

    document.getElementById('report-locations').innerHTML=locs.data.length
      ?locs.data.map(r=>`<tr><td><strong>${esc(r.location)}</strong>${+r.is_default?' <span class="badge badge-blue" style="font-size:.62rem">Default</span>':''}</td><td class="mono">${r.product_count}</td><td class="mono">${r.total_units}</td>${HIDE_STOCK_VALUE?'<td>—</td>':`<td class="mono text-accent">${CUR.sym}${fmtN(r.stock_value)}</td>`}<td>${+r.low_stock_count>0?`<span class="badge badge-red">${r.low_stock_count}</span>`:'<span class="badge badge-green">OK</span>'}</td></tr>`).join('')
      :'<tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text3)">No locations</td></tr>';

    // Charts
    const top8=pnl.data.slice(0,8);const colors=['#4f8eff','#22c55e','#f97316','#a855f7','#eab308','#ef4444','#06b6d4','#ec4899'];
    if(chartTopsell)chartTopsell.destroy();
    chartTopsell=new Chart(document.getElementById('chart-topsell'),{type:'bar',data:{labels:top8.map(p=>p.product.length>14?p.product.slice(0,13)+'…':p.product),datasets:[{label:'Sold Qty',data:top8.map(p=>+p.sold_qty),backgroundColor:'rgba(79,142,255,.7)',borderRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#4a5578',font:{size:10}}},y:{ticks:{color:'#4a5578',font:{size:10}}}}}});
    if(chartProfit)chartProfit.destroy();
    chartProfit=new Chart(document.getElementById('chart-profit'),{type:'bar',data:{labels:top8.map(p=>p.product.length>14?p.product.slice(0,13)+'…':p.product),datasets:[{label:'Profit',data:top8.map(p=>+p.profit),backgroundColor:top8.map(p=>+p.profit>=0?'rgba(34,197,94,.7)':'rgba(239,68,68,.7)'),borderRadius:5}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#4a5578',font:{size:10}}},y:{ticks:{color:'#4a5578',font:{size:10}}}}}});
  }catch(e){toast(e.message,'error');}
}

// ── Reports VP tab ─────────────────────────────────────────
let _rptVPData=[];
async function loadRptVP(){
  const from=document.getElementById('rpt-from')?.value||'';
  const to=document.getElementById('rpt-to')?.value||'';
  const type=document.getElementById('rpt-vpr-type')?.value||'';
  const group=document.getElementById('rpt-vpr-group')?.value||'vendor';
  const tbody=document.getElementById('rpt-vpr-body');
  const thead=document.getElementById('rpt-vpr-thead');
  if(tbody) tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    const params=new URLSearchParams();
    if(from) params.set('from',from);
    if(to) params.set('to',to);
    if(type) params.set('type',type);
    const r=await api.get(API.vendorPayments+'?report=1&'+params);
    _rptVPData=r.data||[];
    const payments=_rptVPData.filter(e=>e.type==='payment').reduce((s,e)=>s+(+e.amount||0),0);
    const credits=_rptVPData.filter(e=>e.type==='credit_note').reduce((s,e)=>s+(+e.amount||0),0);
    document.getElementById('rpt-vpr-stats').innerHTML=
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Transactions</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+_rptVPData.length+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Payments</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(payments)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Credit Notes</div><div style="font-size:1rem;font-weight:800;color:var(--green)">'+CUR.sym+fmtN(credits)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Net Paid</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(payments-credits)+'</div></div>';
    document.getElementById('rpt-vpr-empty').style.display=_rptVPData.length?'none':'block';
    if(!_rptVPData.length){if(tbody)tbody.innerHTML='';return;}
    const getKey=e=>{if(group==='payee')return e.payee_name||'No Payee';if(group==='type')return (e.type||'').replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase());if(group==='month')return e.payment_date?e.payment_date.slice(0,7):'Unknown';return e.vendor_name||'No Vendor';};
    const groups={},order=[];
    _rptVPData.forEach(e=>{const k=getKey(e);if(!groups[k]){groups[k]=[];order.push(k);}groups[k].push(e);});
    order.sort();
    const typeBadge=t=>({payment:'<span class="badge badge-blue" style="font-size:.7rem">Payment</span>',credit_note:'<span class="badge" style="background:rgba(52,211,153,.15);color:var(--green);font-size:.7rem">Credit</span>',opening_balance:'<span class="badge" style="font-size:.7rem">Opening</span>'}[t]||('<span class="badge" style="font-size:.7rem">'+esc(t)+'</span>'));
    if(thead) thead.innerHTML='<tr><th>Date</th><th>Vendor</th><th>Type</th><th>Paid Via</th><th>Reference</th><th>Description</th><th style="text-align:right">Amount ₹</th></tr>';
    let html='',grand=0;
    order.forEach(key=>{
      const grp=groups[key],gt=grp.filter(e=>e.type!=='credit_note').reduce((s,e)=>s+(+e.amount||0),0);
      grand+=gt;
      html+=`<tr style="background:var(--surface2)"><td colspan="6" style="font-weight:700;font-size:.82rem;color:var(--text2);padding:7px 12px">${esc(key)} <span style="color:var(--text3);font-weight:400;font-size:.72rem">(${grp.length})</span></td><td style="text-align:right;font-weight:700;color:var(--accent)">${CUR.sym}${fmtN(gt)}</td></tr>`;
      grp.forEach(e=>{html+=`<tr style="font-size:.83rem"><td style="white-space:nowrap">${esc(e.payment_date||'—')}</td><td>${esc(e.vendor_name||'—')}</td><td>${typeBadge(e.type)}</td><td style="font-size:.78rem">${esc(e.payee_name||'—')}</td><td style="font-size:.75rem;color:var(--text3)">${esc(e.reference_no||'—')}</td><td style="font-size:.78rem;color:var(--text2)">${esc(e.description||e.notes||'—')}</td><td style="text-align:right;color:${e.type==='credit_note'?'var(--green)':'var(--red)'};font-weight:600">${CUR.sym}${fmtN(+e.amount)}</td></tr>`;});
    });
    if(tbody) tbody.innerHTML=html;
    document.getElementById('rpt-vpr-foot').innerHTML=`<tr style="font-weight:700;background:var(--surface2)"><td colspan="6">TOTAL PAID</td><td style="text-align:right;color:var(--red)">${CUR.sym}${fmtN(grand)}</td></tr>`;
  }catch(e){toast(e.message,'error');if(tbody)tbody.innerHTML='';}
}
function exportRptVP(){
  if(!_rptVPData.length){toast('No data','error');return;}
  const h=['Date','Vendor','Type','Paid Via','Reference','Description','Amount'];
  downloadCsv(rowsToCsv([h,..._rptVPData.map(e=>[e.payment_date||'',e.vendor_name||'',e.type||'',e.payee_name||'',e.reference_no||'',e.description||'',Math.round(+e.amount||0)])]),'VendorPayments_'+new Date().toISOString().split('T')[0]+'.csv');
  toast('Exported 📊');
}

// ── Reports Paid To tab ───────────────────────────────────
let _rptPTData=[];
async function loadRptPaidTo(){
  const from=document.getElementById('rpt-from')?.value||'';
  const to=document.getElementById('rpt-to')?.value||'';
  const group=document.getElementById('rpt-ptr-group')?.value||'payee';
  const tbody=document.getElementById('rpt-ptr-body');
  const thead=document.getElementById('rpt-ptr-thead');
  if(tbody) tbody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    const params=new URLSearchParams();
    if(from) params.set('from',from);
    if(to) params.set('to',to);
    params.set('entity_id','all');
    const r=await api.get(API.expenses+'?'+params);
    _rptPTData=(r.data||[]).filter(e=>e.paid_to_id);
    const total=_rptPTData.reduce((s,e)=>s+(+e.amount||0),0);
    const people=new Set(_rptPTData.map(e=>e.paid_to_name)).size;
    document.getElementById('rpt-ptr-stats').innerHTML=
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Paid</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(total)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Transactions</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+_rptPTData.length+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">People Paid</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+people+'</div></div>';
    document.getElementById('rpt-ptr-empty').style.display=_rptPTData.length?'none':'block';
    if(!_rptPTData.length){if(tbody)tbody.innerHTML='';return;}
    const getKey=e=>{if(group==='category')return e.category||'General';if(group==='month')return e.expense_date?e.expense_date.slice(0,7):'Unknown';return e.paid_to_name||'Unknown';};
    const groups={},order=[];
    _rptPTData.forEach(e=>{const k=getKey(e);if(!groups[k]){groups[k]=[];order.push(k);}groups[k].push(e);});
    order.sort();
    if(thead) thead.innerHTML='<tr><th>Date</th><th>Paid To</th><th>Category</th><th>Paid Via</th><th>Business</th><th style="text-align:right">Amount ₹</th></tr>';
    let html='',grand=0;
    order.forEach(key=>{
      const grp=groups[key],gt=grp.reduce((s,e)=>s+(+e.amount||0),0);
      grand+=gt;
      html+=`<tr style="background:var(--surface2)"><td colspan="5" style="font-weight:700;font-size:.82rem;color:var(--text2);padding:7px 12px">${esc(key)} <span style="color:var(--text3);font-weight:400;font-size:.72rem">(${grp.length})</span></td><td style="text-align:right;font-weight:700;color:var(--accent)">${CUR.sym}${fmtN(gt)}</td></tr>`;
      grp.forEach(e=>{html+=`<tr style="font-size:.83rem"><td style="white-space:nowrap">${fmtExpDate(e.expense_date)}</td><td>${esc(e.paid_to_name||'—')}<br><span style="font-size:.7rem;color:var(--text3)">${esc(e.paid_to_type||'')}</span></td><td><span class="badge badge-blue" style="font-size:.7rem">${esc(e.category)}</span></td><td style="font-size:.78rem">${esc(e.payee_name||'—')}</td><td style="font-size:.78rem;color:var(--text3)">${esc(e.entity_name||'—')}</td><td style="text-align:right;color:var(--red);font-weight:600">${CUR.sym}${fmtN(+e.amount)}</td></tr>`;});
    });
    if(tbody) tbody.innerHTML=html;
    document.getElementById('rpt-ptr-foot').innerHTML=`<tr style="font-weight:700;background:var(--surface2)"><td colspan="5">TOTAL</td><td style="text-align:right;color:var(--red)">${CUR.sym}${fmtN(grand)}</td></tr>`;
  }catch(e){toast(e.message,'error');if(tbody)tbody.innerHTML='';}
}
function exportRptPaidTo(){
  if(!_rptPTData.length){toast('No data','error');return;}
  const h=['Date','Paid To','Paid To Type','Category','Paid Via','Business','Amount'];
  downloadCsv(rowsToCsv([h,..._rptPTData.map(e=>[fmtExpDate(e.expense_date),e.paid_to_name||'',e.paid_to_type||'',e.category||'',e.payee_name||'',e.entity_name||'',Math.round(+e.amount||0)])]),'PaidTo_'+new Date().toISOString().split('T')[0]+'.csv');
  toast('Exported 📊');
}

// ── Reports Paid By tab (source of funds) ─────────────────
let _rptPBData=[];
async function loadRptPaidBy(){
  const from=document.getElementById('rpt-from')?.value||'';
  const to=document.getElementById('rpt-to')?.value||'';
  const group=document.getElementById('rpt-pbr-group')?.value||'payee';
  const tbody=document.getElementById('rpt-pbr-body');
  const thead=document.getElementById('rpt-pbr-thead');
  if(tbody) tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    const params=new URLSearchParams();
    if(from) params.set('from',from);
    if(to) params.set('to',to);
    const expParams=new URLSearchParams(params); expParams.set('entity_id','all');
    // Fetch expenses + vendor payments in parallel
    const [expR, vpR] = await Promise.all([
      api.get(API.expenses+'?'+expParams),
      api.get(API.vendorPayments+'?report=1&'+params),
    ]);
    // Normalize both sources into one row shape
    const expRows = (expR.data||[]).filter(e=>e.payee_id).map(function(e){
      return { src:'Expense', date:e.expense_date, payee_name:e.payee_name, payee_type:e.payee_type,
               payee_bank:e.payee_bank, payee_account:e.payee_account, payee_upi:e.payee_upi,
               category:e.category||'General', recipient:e.paid_to_name||'', business:e.entity_name||'RR Expenses',
               amount:+e.amount||0 };
    });
    const vpRows = (vpR.data||[]).filter(v=>v.payee_name && v.type==='payment').map(function(v){
      return { src:'Vendor Payment', date:v.payment_date, payee_name:v.payee_name, payee_type:v.payee_type,
               payee_bank:'', payee_account:'', payee_upi:'',
               category:'Vendor Payment', recipient:v.vendor_name||'', business:'—',
               amount:+v.amount||0 };
    });
    _rptPBData = expRows.concat(vpRows).sort(function(a,b){ return String(b.date||'').localeCompare(String(a.date||'')); });
    const total=_rptPBData.reduce((s,e)=>s+e.amount,0);
    const payers=new Set(_rptPBData.map(e=>e.payee_name)).size;
    const statEl=document.getElementById('rpt-pbr-stats');
    if(statEl) statEl.innerHTML=
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Paid</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(total)+'</div><div style="font-size:.7rem;color:var(--text3);margin-top:2px">Exp: '+CUR.sym+fmtN(expRows.reduce((s,e)=>s+e.amount,0))+' + VP: '+CUR.sym+fmtN(vpRows.reduce((s,e)=>s+e.amount,0))+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Transactions</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+_rptPBData.length+'</div><div style="font-size:.7rem;color:var(--text3);margin-top:2px">'+expRows.length+' expenses · '+vpRows.length+' vendor pmts</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Payers</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+payers+'</div></div>';
    const emptyEl=document.getElementById('rpt-pbr-empty');
    if(emptyEl) emptyEl.style.display=_rptPBData.length?'none':'block';
    if(!_rptPBData.length){if(tbody)tbody.innerHTML='';return;}
    const getKey=e=>{
      if(group==='category') return String(e.category||'General');
      if(group==='business') return String(e.business||'RR Expenses');
      if(group==='month')    return e.date?String(e.date).slice(0,7):'Unknown';
      return String(e.payee_name||'Unknown');
    };
    const groups={},order=[];
    _rptPBData.forEach(e=>{const k=getKey(e);if(!groups[k]){groups[k]=[];order.push(k);}groups[k].push(e);});
    order.sort((a,b)=>String(a).localeCompare(String(b)));
    if(thead) thead.innerHTML='<tr><th>Date</th><th>Paid By</th><th>Type</th><th>Category</th><th>Paid To / Vendor</th><th>Business</th><th style="text-align:right">Amount ₹</th></tr>';
    let html='',grand=0;
    order.forEach(key=>{
      const grp=groups[key],gt=grp.reduce((s,e)=>s+e.amount,0);
      grand+=gt;
      html+=`<tr style="background:var(--surface2)"><td colspan="6" style="font-weight:700;font-size:.82rem;color:var(--text2);padding:7px 12px">${esc(key)} <span style="color:var(--text3);font-weight:400;font-size:.72rem">(${grp.length})</span></td><td style="text-align:right;font-weight:700;color:var(--accent)">${CUR.sym}${fmtN(gt)}</td></tr>`;
      grp.forEach(e=>{
        const sub=e.payee_type==='Cash'?'Cash':e.payee_bank?(esc(e.payee_bank)+(e.payee_account?' ****'+String(e.payee_account).slice(-4):'')):(e.payee_upi?esc(e.payee_upi):esc(e.payee_type||''));
        const srcBadge = e.src==='Vendor Payment'
          ? '<span class="badge" style="background:rgba(251,191,36,.15);color:#f59e0b;font-size:.68rem">VP</span>'
          : '<span class="badge badge-blue" style="font-size:.68rem">Exp</span>';
        html+=`<tr style="font-size:.83rem"><td style="white-space:nowrap">${fmtExpDate(e.date)}</td><td>${esc(e.payee_name||'—')}${sub?'<br><span style="font-size:.7rem;color:var(--text3)">'+sub+'</span>':''}</td><td>${srcBadge}</td><td><span class="badge badge-blue" style="font-size:.7rem">${esc(e.category)}</span></td><td style="font-size:.78rem">${esc(e.recipient||'—')}</td><td style="font-size:.78rem;color:var(--text3)">${esc(e.business||'—')}</td><td style="text-align:right;color:var(--red);font-weight:600">${CUR.sym}${fmtN(e.amount)}</td></tr>`;
      });
    });
    if(tbody) tbody.innerHTML=html;
    const footEl=document.getElementById('rpt-pbr-foot');
    if(footEl) footEl.innerHTML=`<tr style="font-weight:700;background:var(--surface2)"><td colspan="6">TOTAL</td><td style="text-align:right;color:var(--red)">${CUR.sym}${fmtN(grand)}</td></tr>`;
  }catch(e){toast(e.message,'error');if(tbody)tbody.innerHTML='';}
}
function exportRptPaidBy(){
  if(!_rptPBData.length){toast('No data','error');return;}
  const h=['Date','Paid By','Payee Type','Source','Category','Paid To / Vendor','Business','Amount'];
  downloadCsv(rowsToCsv([h,..._rptPBData.map(e=>[fmtExpDate(e.date),e.payee_name||'',e.payee_type||'',e.src,e.category||'',e.recipient||'',e.business||'',Math.round(e.amount)])]),'PaidBy_'+new Date().toISOString().split('T')[0]+'.csv');
  toast('Exported 📊');
}

// ── Reports Low Stock tab ─────────────────────────────────
async function loadRptLowStock(){
  const tbody=document.getElementById('rpt-alert-body');
  if(tbody) tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    const r=await api.get(API.products+'?low_stock=1');
    const rows=r.data||[];
    document.getElementById('rpt-alert-empty').style.display=rows.length?'none':'block';
    setElText('rpt-alert-count', rows.length?rows.length+' item'+(rows.length===1?'':'s')+' below min stock':'');
    if(!rows.length){if(tbody)tbody.innerHTML='';return;}
    if(tbody) tbody.innerHTML=rows.map(p=>`<tr style="font-size:.83rem">
      <td style="font-weight:500">${esc(p.name)}</td>
      <td style="color:var(--accent)">${esc(p.brand||'—')}</td>
      <td style="color:var(--text3)">${esc(p.category||'—')}</td>
      <td style="color:${+p.stock<=0?'var(--red)':'var(--orange)'};font-weight:700">${fmtN(p.stock)}</td>
      <td>${fmtN(p.min_stock)}</td>
      <td style="color:var(--red);font-weight:600">${fmtN(Math.max(0,(p.min_stock||0)-(p.stock||0)))}</td>
      <td style="color:var(--text3)">${esc(p.vendor_name||'—')}</td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');if(tbody)tbody.innerHTML='';}
}

// ══════════════════════════════════════════════════════════
// ORDER PICKING REPORT — status, owner, and timeline for every estimate
// ══════════════════════════════════════════════════════════
let _rptPickingRows=[]; // raw rows from the server (SELECT *-ish shape, snake_case)
const RPT_PICKING_SM={
  pending:{label:'Pending',color:'var(--text3)',bg:'rgba(148,163,184,.15)',icon:'⏸'},
  picking:{label:'Picking',color:'var(--orange)',bg:'rgba(249,115,22,.15)',icon:'📦'},
  verification:{label:'Verification',color:'#ca8a04',bg:'rgba(234,179,8,.15)',icon:'🔍'},
  packing:{label:'Packing',color:'var(--accent)',bg:'rgba(79,142,255,.15)',icon:'📦'},
  dispatched:{label:'Dispatched',color:'var(--green)',bg:'rgba(34,197,94,.15)',icon:'🚚'},
};
async function loadRptPicking(){
  const body=document.getElementById('rpt-picking-body');
  if(body) body.innerHTML='<tr><td colspan="12" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    // No ?date= — matches the Order Picking dashboard's 'full history'
    // default, so this report always covers every estimate on record,
    // not just a recent window.
    const r=await api.get(API.pickingSessions);
    _rptPickingRows=Array.isArray(r.data)?r.data:[];
  }catch(e){
    _rptPickingRows=[];
    toast('Could not load picking report: '+e.message,'error');
  }
  renderRptPicking();
}
// Shared with the item counts / value-matching badge on the Order
// Picking dashboard (renderPickDashboard()) — kept as standalone
// functions here rather than refactoring that already-working code,
// so both places compute 'picked' and 'over/short' the same way.
function pickCalcDoneCount(items){
  return items.filter(function(it){
    if(!it.unavailable) return (+it.picked||0)>=(+it.qty||0);
    const sv=(it.substitutes||[]).reduce(function(a,b){return a+(+b.sell||0)*(+b.picked||0);},0);
    const ov=+it.amount||(+it.rate||0)*(+it.qty||0);
    return ov>0?sv>=ov:(it.substitutes||[]).reduce(function(a,b){return a+(+b.picked||0);},0)>=(+it.qty||0);
  }).length;
}
function pickCalcNetDiff(items){
  let netDiff=0;
  items.forEach(function(it){
    if(!it.unavailable)return;
    const tgt=+it.amount||(+it.rate||0)*(+it.qty||0);
    const subVal=(it.substitutes||[]).reduce(function(a,b){return a+(+b.sell||0)*(+b.picked||0);},0);
    netDiff+=(tgt-subVal);
  });
  return Math.round(netDiff*100)/100;
}
function getFilteredRptPickingRows(){
  const q=(document.getElementById('rpt-picking-search')?.value||'').toLowerCase().trim();
  const statusFilter=document.getElementById('rpt-picking-status')?.value||'';
  const completedFilter=document.getElementById('rpt-picking-completed-filter')?.value||'';
  const completedFrom=document.getElementById('rpt-picking-completed-from')?.value||'';
  const completedTo=document.getElementById('rpt-picking-completed-to')?.value||'';
  let rows=_rptPickingRows;
  if(statusFilter) rows=rows.filter(row=>(row.status||'pending')===statusFilter);
  if(completedFilter==='yes') rows=rows.filter(row=>!!row.picking_completed_at);
  else if(completedFilter==='no') rows=rows.filter(row=>!row.picking_completed_at);
  if(completedFrom||completedTo) rows=rows.filter(row=>{
    if(!row.picking_completed_at)return false;
    const d=(''+row.picking_completed_at).slice(0,10); // 'YYYY-MM-DD...' -> date part
    if(completedFrom&&d<completedFrom)return false;
    if(completedTo&&d>completedTo)return false;
    return true;
  });
  if(q) rows=rows.filter(row=>
    (row.order_no||'').toLowerCase().includes(q)
    ||(row.customer||'').toLowerCase().includes(q)
    ||(row.picker||'').toLowerCase().includes(q)
    ||(row.verified_by||'').toLowerCase().includes(q));
  return rows;
}
function renderRptPicking(){
  const body=document.getElementById('rpt-picking-body');
  const empty=document.getElementById('rpt-picking-empty');
  const statsEl=document.getElementById('rpt-picking-stats');
  if(!body)return;
  const counts={};
  _rptPickingRows.forEach(row=>{const s=row.status||'pending';counts[s]=(counts[s]||0)+1;});
  if(statsEl){
    statsEl.innerHTML='<div class="stat-card" style="--accent-color:var(--accent)"><span class="stat-icon">📋</span><span class="stat-num">'+_rptPickingRows.length+'</span><span class="stat-label">Total Estimates</span></div>'
      +Object.keys(RPT_PICKING_SM).map(s=>{
        if(!counts[s])return '';
        const sm=RPT_PICKING_SM[s];
        return '<div class="stat-card" style="--accent-color:'+sm.color+'"><span class="stat-icon">'+sm.icon+'</span><span class="stat-num" style="color:'+sm.color+'">'+counts[s]+'</span><span class="stat-label">'+sm.label+'</span></div>';
      }).join('');
  }
  const rows=getFilteredRptPickingRows();
  if(!rows.length){
    body.innerHTML='';
    if(empty)empty.style.display='';
    return;
  }
  if(empty)empty.style.display='none';
  body.innerHTML=rows.map(row=>{
    const s=row.status||'pending',sm=RPT_PICKING_SM[s]||RPT_PICKING_SM.pending;
    let items=[];
    try{items=typeof row.data==='string'?(JSON.parse(row.data||'[]')||[]):(row.data||[]);}catch(e){}
    const done=pickCalcDoneCount(items);
    const netDiff=pickCalcNetDiff(items);
    const diffCell=Math.abs(netDiff)>0.01
      ?'<span style="font-weight:700;color:'+(netDiff>0?'var(--orange)':'var(--accent)')+'">'+(netDiff>0?'Short ₹'+netDiff.toFixed(2):'Over ₹'+(-netDiff).toFixed(2))+'</span>'
      :'<span style="color:var(--text3)">—</span>';
    const dispatch=row.ship_date
      ?(esc(row.ship_date)+(row.transport_name?' · '+esc(row.transport_name):'')+(row.box_count?' · '+row.box_count+' box'+(row.box_count==1?'':'es'):''))
      :'—';
    return '<tr style="font-size:.83rem">'
      +'<td style="font-weight:700">'+esc(row.order_no||'—')+'</td>'
      +'<td>'+esc(row.customer||'—')+'</td>'
      +'<td>'+esc(row.phone||'—')+'</td>'
      +'<td><span style="padding:3px 10px;border-radius:20px;font-size:.74rem;font-weight:700;background:'+sm.bg+';color:'+sm.color+';white-space:nowrap">'+sm.icon+' '+sm.label+'</span></td>'
      +'<td>'+esc(row.picker||'—')+'</td>'
      +'<td style="color:var(--text3)">'+esc(formatPickTimestamp(row.picking_completed_at)||'—')+'</td>'
      +'<td>'+esc(row.verified_by||'—')+'</td>'
      +'<td style="color:var(--text3)">'+esc(formatPickTimestamp(row.verified_at)||'—')+'</td>'
      +'<td style="color:var(--text3)">'+dispatch+'</td>'
      +'<td style="text-align:center">'+items.length+'</td>'
      +'<td style="text-align:center">'+done+'</td>'
      +'<td style="text-align:right">'+diffCell+'</td>'
    +'</tr>';
  }).join('');
}
function exportRptPicking(){
  const rows=getFilteredRptPickingRows();
  if(!rows.length){toast('Nothing to export','error');return;}
  const csvRows=[['Estimate #','Customer','Phone','Status','Owner (Picker)','Picking Completed','Verified By','Verified At','Ship Date','Transport','Boxes','Items Ordered','Items Picked','Over/Short (₹)']];
  rows.forEach(row=>{
    const sm=RPT_PICKING_SM[row.status||'pending']||RPT_PICKING_SM.pending;
    let items=[];
    try{items=typeof row.data==='string'?(JSON.parse(row.data||'[]')||[]):(row.data||[]);}catch(e){}
    const done=pickCalcDoneCount(items);
    const netDiff=pickCalcNetDiff(items);
    csvRows.push([
      row.order_no||'',row.customer||'',row.phone||'',sm.label,
      row.picker||'',formatPickTimestamp(row.picking_completed_at)||'',
      row.verified_by||'',formatPickTimestamp(row.verified_at)||'',
      row.ship_date||'',row.transport_name||'',row.box_count||'',
      items.length,done,
      netDiff>0?('Short '+netDiff.toFixed(2)):netDiff<0?('Over '+(-netDiff).toFixed(2)):''
    ]);
  });
  downloadCsv(rowsToCsv(csvRows),'OrderPicking_Report_'+new Date().toISOString().split('T')[0]+'.csv');
  toast('Exported 📊');
}

// ══════════════════════════════════════════════════════════
// COMBO BUILDER
// ══════════════════════════════════════════════════════════
let _combos = [];
let _comboItems = []; // items in the builder modal: {product_id, name, qty, sell, cost, stock, unit}

async function loadCombos(){
  try{
    const r = await api.get(API.combos);
    _combos = r.data||[];
    renderComboList();
  }catch(e){ toast(e.message,'error'); }
}

function renderComboList(){
  const q = (document.getElementById('combo-search')?.value||'').toLowerCase();
  const rows = q ? _combos.filter(c=>String(c.name||'').toLowerCase().includes(q)) : _combos;
  const tbody = document.getElementById('combo-body');
  const empty = document.getElementById('combo-empty');
  if(!tbody) return;
  // Hide cost columns for managers
  document.querySelectorAll('.combo-cost-col').forEach(el=>{ el.style.display = HIDE_COST?'none':''; });
  if(!rows.length){ tbody.innerHTML=''; if(empty) empty.style.display='block'; return; }
  if(empty) empty.style.display='none';
  tbody.innerHTML = rows.map(function(c){
    const sell = +c.sell_total||0, cost = +c.cost_total||0, target = +c.target_price||0;
    const margin = sell>0 ? Math.round((sell-cost)/sell*100) : 0;
    const diff = sell - target;
    const diffCell = target>0
      ? '<span style="color:'+(Math.abs(diff)<=target*0.02?'var(--green)':(diff>0?'var(--orange)':'var(--red)'))+';font-weight:600">'+(diff>=0?'+':'')+CUR.sym+fmtN(diff)+'</span>'
      : '<span style="color:var(--text3)">—</span>';
    const actions = '<button class="btn btn-ghost btn-xs" onclick="editCombo('+c.id+')" title="Edit">✏️</button> '
      +'<button class="btn btn-ghost btn-xs" onclick="duplicateCombo('+c.id+')" title="Duplicate">📋</button> '
      +'<button class="btn btn-ghost btn-xs" onclick="exportCombo('+c.id+')" title="Export packing list">📊</button> '
      +'<button class="btn btn-ghost btn-xs" onclick="printCombo('+c.id+')" title="Print packing list">🖨️</button> '
      +(CAN_DELETE?'<button class="btn btn-danger btn-xs" onclick="deleteCombo('+c.id+',\''+esc(c.name).replace(/'/g,"\\'")+'\')" title="Delete">🗑️</button>':'');
    return '<tr style="font-size:.85rem">'
      +'<td style="font-weight:600">'+esc(c.name)+(c.notes?'<br><span style="font-size:.72rem;color:var(--text3)">'+esc(c.notes)+'</span>':'')+'</td>'
      +'<td class="mono">'+(target?CUR.sym+fmtN(target):'—')+'</td>'
      +'<td>'+c.item_count+'</td>'
      +'<td>'+fmtN(c.total_units)+'</td>'
      +'<td class="mono" style="font-weight:600">'+CUR.sym+fmtN(sell)+'</td>'
      +'<td class="mono combo-cost-col"'+(HIDE_COST?' style="display:none"':'')+'>'+CUR.sym+fmtN(cost)+'</td>'
      +'<td class="combo-cost-col"'+(HIDE_COST?' style="display:none"':'')+'><span style="color:'+(margin>=30?'var(--green)':margin>=15?'var(--orange)':'var(--red)')+';font-weight:600">'+margin+'%</span></td>'
      +'<td>'+diffCell+'</td>'
      +'<td style="white-space:nowrap">'+actions+'</td>'
      +'</tr>';
  }).join('');
}

function openNewComboModal(){
  try{
    var fields=['combo-edit-id','combo-name','combo-target','combo-sell-price','combo-notes','combo-prod-search'];
    fields.forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; });
    var picker=document.getElementById('combo-picker-results');
    if(picker) picker.style.display='none';
    setElText('combo-modal-title','🎁 New Combo');
    _comboItems=[];
    renderComboItems();
    // Force open the modal directly — don't rely on openModal helper
    var modal=document.getElementById('modal-combo');
    if(modal){ modal.classList.add('open'); }
    else { console.error('modal-combo element not found'); }
    // Warm product cache in background
    getProductsCache().catch(function(e){ toast('Could not load products: '+e.message,'error'); });
  }catch(ex){
    toast('Error opening combo builder: '+ex.message,'error');
    console.error('openNewComboModal error:',ex);
  }
}

async function openComboModal(prefill){
  // Reset form
  document.getElementById('combo-edit-id').value = prefill&&prefill.id ? prefill.id : '';
  setElText('combo-modal-title', prefill&&prefill.id ? '✏️ Edit Combo' : '🎁 New Combo');
  document.getElementById('combo-name').value    = prefill&&prefill.name ? prefill.name : '';
  document.getElementById('combo-target').value  = prefill&&+prefill.target_price>0 ? Math.round(+prefill.target_price) : '';
  document.getElementById('combo-sell-price').value = prefill&&+prefill.sell_price>0 ? Math.round(+prefill.sell_price) : '';
  document.getElementById('combo-notes').value   = prefill&&prefill.notes ? prefill.notes : '';
  document.getElementById('combo-prod-search').value = '';
  document.getElementById('combo-picker-results').style.display = 'none';
  _comboItems = prefill&&prefill.items ? prefill.items.map(function(it){
    return { product_id:+it.product_id, name:it.name||'', qty:+it.qty||1, sell:+it.sell_price||0, cost:+it.cost||0, stock:+it.total_stock||0, unit:it.unit||'' };
  }) : [];
  renderComboItems();
  // Open modal immediately — don't wait for cache
  openModal('modal-combo');
  // Warm the product cache in background (needed for the search picker)
  try{ await getProductsCache(); }catch(e){ toast('Could not load product list: '+e.message,'error'); }
}

async function filterComboProductPicker(){
  const q = (document.getElementById('combo-prod-search').value||'').toLowerCase().trim();
  const box = document.getElementById('combo-picker-results');
  if(!q){ box.style.display='none'; return; }
  const products = await getProductsCache();
  const inCombo = new Set(_comboItems.map(i=>String(i.product_id)));
  const matches = products.filter(function(p){
    return !inCombo.has(String(p.id)) && (
      String(p.name||'').toLowerCase().includes(q) ||
      String(p.brand||'').toLowerCase().includes(q) ||
      String(p.sku||'').toLowerCase().includes(q) ||
      String(p.item_code||'').toLowerCase().includes(q));
  }).slice(0,12);
  if(!matches.length){ box.innerHTML='<div style="padding:10px 14px;color:var(--text3);font-size:.8rem">No matching products</div>'; box.style.display='block'; return; }
  box.innerHTML = matches.map(function(p){
    const noPrice = !p.sell || +p.sell===0;
    return '<div onclick="addComboItem('+p.id+')" style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:9px 12px;cursor:pointer;border-bottom:1px solid var(--border);font-size:.82rem" onmouseover="this.style.background=\'var(--surface2)\'" onmouseout="this.style.background=\'\'">'
      +'<span>'+esc(p.name)+(p.brand?' <span style="color:var(--accent);font-size:.72rem">'+esc(p.brand)+'</span>':'')+'</span>'
      +'<span class="mono" style="white-space:nowrap;color:'+(noPrice?'var(--orange)':'var(--text3)')+'">'
        +(noPrice?'⚠️ no price':CUR.sym+fmtN(p.sell))
        +' · stk '+fmtN(p.stock||0)+'</span>'
      +'</div>';
  }).join('');
  box.style.display='block';
}

async function addComboItem(pid){
  const products = await getProductsCache();
  const p = products.find(function(x){ return String(x.id)===String(pid); });
  if(!p) return;
  _comboItems.push({ product_id:+p.id, name:p.name, qty:1, sell:+p.sell||0, cost:+p.cost||0, stock:+p.stock||0, unit:p.unit||'' });
  document.getElementById('combo-prod-search').value='';
  document.getElementById('combo-picker-results').style.display='none';
  renderComboItems();
}

function removeComboItem(idx){ _comboItems.splice(idx,1); renderComboItems(); }

function renderComboItems(){
  const tbody = document.getElementById('combo-items-body');
  if(!tbody) return;
  if(!_comboItems.length){
    tbody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:18px;color:var(--text3);font-size:.8rem">Search above to add products · Click a product to add it to the combo</td></tr>';
    updateComboTotals(); return;
  }
  tbody.innerHTML = _comboItems.map(function(it,i){
    const low  = it.stock < it.qty;
    const noPrice = !it.sell || it.sell===0;
    return '<tr draggable="true" data-idx="'+i+'" class="combo-drag-row" style="font-size:.83rem;cursor:grab" '
      +'ondragstart="comboDragStart(event,'+i+')" ondragover="comboDragOver(event)" ondrop="comboDrop(event,'+i+')" ondragleave="comboDragLeave(event)" ondragend="comboDragEnd(event)">'
      +'<td style="padding-left:6px;color:var(--text3);font-size:.8rem;cursor:grab" title="Drag to reorder">⠿</td>'
      +'<td>'+esc(it.name)
        +(low?' <span style="color:var(--red);font-size:.68rem" title="Stock: '+fmtN(it.stock)+'">⚠️ stk '+fmtN(it.stock)+'</span>':'')
        +(noPrice?' <span style="color:var(--orange);font-size:.68rem">⚠️ no price</span>':'')
      +'</td>'
      +'<td><input type="number" min="1" value="'+it.qty+'" style="width:56px;background:var(--surface2);border:1px solid var(--border2);border-radius:6px;color:var(--text);padding:3px 5px;text-align:center" oninput="setComboItemQty('+i+',this.value)"></td>'
      +'<td><input type="number" min="0" value="'+it.sell+'" style="width:72px;background:var(--surface2);border:1px solid '+(noPrice?'var(--orange)':'var(--border2)')+';border-radius:6px;color:'+(noPrice?'var(--orange)':'var(--text)')+';padding:3px 5px;text-align:right;font-family:var(--mono)" placeholder="0" oninput="setComboItemPrice('+i+',this.value)" onfocus="if(this.value===\'0\')this.value=\'\'"></td>'
      +'<td class="mono" id="combo-row-total-'+i+'" style="text-align:right">'+CUR.sym+fmtN(it.qty*it.sell)+'</td>'
      +'<td><button class="btn btn-ghost btn-xs" onclick="removeComboItem('+i+')" style="padding:4px 6px">✕</button></td>'
      +'</tr>';
  }).join('');
  updateComboTotals();
}

// ── Drag & drop reorder ────────────────────────────────────
let _dragSrcIdx = null;
function comboDragStart(e, idx){
  _dragSrcIdx = idx;
  e.dataTransfer.effectAllowed = 'move';
  e.currentTarget.style.opacity = '0.4';
}
function comboDragOver(e){
  e.preventDefault();
  e.dataTransfer.dropEffect = 'move';
  e.currentTarget.style.background = 'var(--surface2)';
  e.currentTarget.style.outline = '2px solid var(--accent)';
  return false;
}
function comboDragLeave(e){
  e.currentTarget.style.outline = '';
  e.currentTarget.style.background = '';
}
function comboDrop(e, toIdx){
  e.preventDefault();
  e.currentTarget.style.outline = '';
  e.currentTarget.style.background = '';
  if(_dragSrcIdx === null || _dragSrcIdx === toIdx) return;
  const moved = _comboItems.splice(_dragSrcIdx, 1)[0];
  _comboItems.splice(toIdx, 0, moved);
  _dragSrcIdx = null;
  renderComboItems();
}
function comboDragEnd(e){
  e.currentTarget.style.opacity = '';
  _dragSrcIdx = null;
}

function setComboItemQty(idx,val){
  if(!_comboItems[idx]) return;
  _comboItems[idx].qty = Math.max(1, parseInt(val,10)||1);
  const t=document.getElementById('combo-row-total-'+idx);
  if(t) t.textContent = CUR.sym+fmtN(_comboItems[idx].qty*_comboItems[idx].sell);
  updateComboTotals();
}

function setComboItemPrice(idx,val){
  if(!_comboItems[idx]) return;
  _comboItems[idx].sell = parseFloat(val)||0;
  const t=document.getElementById('combo-row-total-'+idx);
  if(t) t.textContent = CUR.sym+fmtN(_comboItems[idx].qty*_comboItems[idx].sell);
  updateComboTotals();
}
function updateComboTotals(){
  const box = document.getElementById('combo-totals');
  if(!box) return;
  const target = parseFloat(document.getElementById('combo-target')?.value)||0;
  const sell = _comboItems.reduce(function(s,it){ return s+it.qty*it.sell; },0);
  const cost = _comboItems.reduce(function(s,it){ return s+it.qty*it.cost; },0);
  const units = _comboItems.reduce(function(s,it){ return s+it.qty; },0);
  const diff = sell-target;
  const margin = sell>0 ? Math.round((sell-cost)/sell*100) : 0;
  const stat = function(label,val,color){
    return '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:10px 12px"><div style="font-size:.65rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:3px">'+label+'</div><div style="font-size:.95rem;font-weight:800;font-family:var(--mono);color:'+color+'">'+val+'</div></div>';
  };
  let html = stat('Items / Units', _comboItems.length+' / '+fmtN(units), 'var(--accent)')
    + stat('Sell Total', CUR.sym+fmtN(sell), 'var(--text)');
  if(!HIDE_COST) html += stat('Cost / Margin', CUR.sym+fmtN(cost)+' · '+margin+'%', margin>=30?'var(--green)':'var(--orange)');
  html += target>0
    ? stat('vs Target '+CUR.sym+fmtN(target), (diff>=0?'+':'')+CUR.sym+fmtN(diff), Math.abs(diff)<=target*0.02?'var(--green)':(diff>0?'var(--orange)':'var(--red)'))
    : stat('vs Target','—','var(--text3)');
  box.innerHTML = html;
}

async function saveCombo(){
  const name = document.getElementById('combo-name').value.trim();
  if(!name){ toast('Combo name is required','error'); return; }
  if(!_comboItems.length){ toast('Add at least one product','error'); return; }
  const body = {
    name:name,
    target_price: parseFloat(document.getElementById('combo-target').value)||0,
    sell_price:   parseFloat(document.getElementById('combo-sell-price').value)||0,
    notes: document.getElementById('combo-notes').value.trim(),
    items: _comboItems.map(function(it){ return {product_id:it.product_id, qty:it.qty}; }),
  };
  const editId = document.getElementById('combo-edit-id').value;
  try{
    if(editId){ body.id=+editId; await api.put(API.combos, body); toast('Combo updated!','success'); }
    else { await api.post(API.combos, body); toast('Combo saved!','success'); }
    closeModal('modal-combo');
    loadCombos();
  }catch(e){ toast(e.message,'error'); }
}

async function editCombo(id){
  try{
    const r = await api.get(API.combos+'?id='+id);
    openComboModal(r.data);
  }catch(e){ toast(e.message,'error'); }
}

async function duplicateCombo(id){
  try{
    const r = await api.get(API.combos+'?id='+id);
    const c = r.data;
    c.id = null;
    c.name = c.name + ' (Copy)';
    openComboModal(c);
    toast('Editing a copy — save to create it','info');
  }catch(e){ toast(e.message,'error'); }
}

async function deleteCombo(id, name){
  if(!confirm('Delete combo "'+name+'"?')) return;
  try{ await api.delete(API.combos+'?id='+id); toast('Combo deleted'); loadCombos(); }
  catch(e){ toast(e.message,'error'); }
}

async function exportCombo(id){
  try{
    const r = await api.get(API.combos+'?id='+id);
    const c = r.data;
    const headers = HIDE_COST
      ? ['#','Product','Brand','Item Code','Qty','Unit','Price','Total']
      : ['#','Product','Brand','Item Code','Qty','Unit','Price','Total','Cost','Cost Total'];
    const rows = c.items.map(function(it,i){
      const base = [i+1, it.name, it.brand||'', it.item_code||'', it.qty, it.unit||'', Math.round(+it.sell_price||0), Math.round(it.qty*(+it.sell_price||0))];
      if(!HIDE_COST) base.push(Math.round(+it.cost||0), Math.round(it.qty*(+it.cost||0)));
      return base;
    });
    const sellTotal = c.items.reduce(function(s,it){return s+it.qty*(+it.sell_price||0);},0);
    const totalRow = ['','TOTAL','','',c.items.reduce(function(s,it){return s+ +it.qty;},0),'','',Math.round(sellTotal)];
    if(!HIDE_COST) totalRow.push('', Math.round(c.items.reduce(function(s,it){return s+it.qty*(+it.cost||0);},0)));
    downloadCsv(rowsToCsv([headers,...rows,totalRow]), esc(c.name).replace(/[^\w]+/g,'_')+'.csv');
    toast('Exported packing list 📊');
  }catch(e){ toast(e.message,'error'); }
}

async function printCombo(id){
  try{
    const r = await api.get(API.combos+'?id='+id);
    const c = r.data;
    const sellTotal = c.items.reduce(function(s,it){return s+it.qty*(+it.sell_price||0);},0);
    const w = window.open('','_blank');
    w.document.write('<html><head><title>'+esc(c.name)+'</title><style>'
      +'body{font-family:Arial,sans-serif;padding:24px;color:#111}'
      +'h2{margin:0 0 4px}p{margin:2px 0;color:#555;font-size:13px}'
      +'table{width:100%;border-collapse:collapse;margin-top:14px;font-size:13px}'
      +'th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}'
      +'th{background:#f2f2f2}td.num,th.num{text-align:right}'
      +'tfoot td{font-weight:bold;background:#fafafa}'
      +'</style></head><body>'
      +'<h2>🎁 '+esc(c.name)+'</h2>'
      +(c.target_price>0?'<p>Target: ₹'+fmtN(c.target_price)+'</p>':'')
      +(c.notes?'<p>'+esc(c.notes)+'</p>':'')
      +'<table><thead><tr><th>#</th><th>Product</th><th>Brand</th><th class="num">Qty</th><th class="num">Price ₹</th><th class="num">Total ₹</th></tr></thead><tbody>'
      +c.items.map(function(it,i){
        return '<tr><td>'+(i+1)+'</td><td>'+esc(it.name)+'</td><td>'+esc(it.brand||'')+'</td><td class="num">'+it.qty+'</td><td class="num">'+fmtN(+it.sell_price||0)+'</td><td class="num">'+fmtN(it.qty*(+it.sell_price||0))+'</td></tr>';
      }).join('')
      +'</tbody><tfoot><tr><td colspan="3">TOTAL</td><td class="num">'+fmtN(c.items.reduce(function(s,it){return s+ +it.qty;},0))+'</td><td></td><td class="num">₹'+fmtN(sellTotal)+'</td></tr></tfoot></table>'
      +'<button onclick="window.print()" style="margin-top:14px;padding:8px 20px;background:#1e3a5f;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">🖨️ Print</button>'
      +'</body></html>');
    w.document.close();
    setTimeout(function(){ w.print(); }, 600);
  }catch(e){ toast(e.message,'error'); }
}

// ══════════════════════════════════════════════════════════
// PROCUREMENT DASHBOARD
// ══════════════════════════════════════════════════════════
let _oorData = null;
let _oorFiltersInit = false;

// ── Procurement column selector ───────────────────────────
const OOR_COLS = [
  { key:'item_code',    label:'Item Code',    def:true  },
  { key:'sku',          label:'SKU',          def:false },
  { key:'brand',        label:'Brand',        def:true  },
  { key:'category',     label:'Category',     def:false },
  { key:'vendor',       label:'Vendor',       def:true  },
  { key:'cost',         label:'Cost ₹',       def:true  },
  { key:'case_content', label:'Case Content', def:true  },
];
function getOORColPrefs(){
  try{ const s=localStorage.getItem('sm_oor_cols'); if(s) return JSON.parse(s); }catch{}
  const d={}; OOR_COLS.forEach(c=>d[c.key]=c.def); return d;
}
function saveOORColPrefs(p){ localStorage.setItem('sm_oor_cols',JSON.stringify(p)); }
function resetOORColPrefs(){ localStorage.removeItem('sm_oor_cols'); buildOORColToggles(); loadOnOrderReport(); }
function oorColVis(k){
  if(k==='cost' && HIDE_COST) return false;
  return getOORColPrefs()[k]!==false;
}
function buildOORColToggles(){
  const prefs=getOORColPrefs();
  const list=document.getElementById('oor-col-toggle-list');
  if(!list) return;
  list.innerHTML=OOR_COLS.filter(c=>!(c.key==='cost'&&HIDE_COST)).map(c=>
    '<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.83rem;white-space:nowrap">'
    +'<input type="checkbox" '+(prefs[c.key]!==false?'checked':'')+' onchange="onOORColToggle(\''+c.key+'\',this.checked)" style="accent-color:var(--accent)">'
    +c.label+'</label>'
  ).join('');
}
function onOORColToggle(key,checked){
  const prefs=getOORColPrefs(); prefs[key]=checked; saveOORColPrefs(prefs);
  loadOnOrderReport();
}
function toggleOORColChooser(){
  const el=document.getElementById('oor-col-chooser');
  if(!el) return;
  const open = el.style.display==='none'||el.style.display==='';
  el.style.display = open ? 'block' : 'none';
  if(open) buildOORColToggles();
}
function oorFixedColCount(){
  let n=1; // Product always visible
  OOR_COLS.forEach(c=>{ if(oorColVis(c.key)) n++; });
  return n;
}

function buildOORRow(r, locs, badge){
  const locCells = locs.map(l=>{
    const stock  = r['loc_'+l.id]||0;
    const onOrd  = r.pos.filter(p=>p.location_id && String(p.location_id)===String(l.id)).reduce((s,p)=>s+(+p.pending_qty||0),0);
    const stockCol = stock<=0 ? `<span style="color:var(--red)">${stock}</span>` : `<span style="color:var(--green)">${fmt(stock)}</span>`;
    const ordCol   = onOrd>0  ? `<span style="color:var(--accent);font-weight:600">${fmt(onOrd)}</span>` : `<span style="color:var(--text3)">—</span>`;
    return `<td style="text-align:center">${stockCol}</td><td style="text-align:center">${ordCol}</td>`;
  }).join('');
  const poTooltip = r.pos.length
    ? r.pos.map(p=>`${p.po_number} [${p.status}] ×${p.pending_qty} ${p.vendor}${p.location_name?' → '+p.location_name:''}`).join('\n')
    : '';
  const onOrderCell = r.on_order>0
    ? `<td style="text-align:center;font-weight:700;color:var(--accent);cursor:help" title="${esc(poTooltip)}">${fmt(r.on_order)}<div style="font-size:.6rem;margin-top:1px">${[...new Set(r.pos.map(p=>p.status))].map(s=>badge(s)).join('')}</div></td>`
    : `<td style="text-align:center;color:var(--text3)">—</td>`;
  const rowBg = r.total_stock<=0 ? 'background:rgba(239,68,68,.04)' :
                (r.min_stock>0 && r.total_stock<=r.min_stock) ? 'background:rgba(249,115,22,.04)' : '';
  let cells = `<tr data-category="${esc(r.category||''  )}" style="${rowBg}">`;
  if(oorColVis('item_code'))    cells += `<td style="font-family:monospace;color:var(--text3)">${esc(r.item_code||'')}</td>`;
  if(oorColVis('sku'))          cells += `<td style="font-family:monospace;color:var(--text2)">${esc(r.sku||'—')}</td>`;
  cells += `<td style="font-weight:500">${esc(r.name)}</td>`;
  if(oorColVis('brand'))        cells += `<td style="color:var(--accent);font-weight:600">${esc(r.brand||'')}</td>`;
  if(oorColVis('category'))     cells += `<td style="color:var(--text3)">${esc(r.category)}</td>`;
  if(oorColVis('vendor'))       cells += `<td style="color:var(--text3)">${esc(r.vendor_name)}</td>`;
  if(oorColVis('cost'))         cells += `<td class="mono" style="text-align:right">${r.cost?CUR.sym+fmtN(r.cost):'—'}</td>`;
  if(oorColVis('case_content')) cells += `<td style="text-align:center;color:var(--text2)">${esc(r.case_content||'—')}</td>`;
  cells += locCells;
  cells += `<td style="text-align:center;font-weight:600">${fmt(r.total_stock)} <span style="font-size:.68rem;color:var(--text3)">${esc(r.unit||'')}</span></td>`;
  cells += onOrderCell;
  cells += `<td style="text-align:center;padding:3px 5px">
      <input type="text" class="oor-tbo-input"
        data-pid="${r.id}" data-name="${esc(r.name).replace(/"/g,'&quot;')}"
        style="width:56px;background:var(--surface2);border:1.5px solid var(--border2);border-radius:6px;color:#f97316;font-weight:700;font-size:.85rem;text-align:center;padding:3px 4px;outline:none"
        placeholder="0"
        onfocus="if(this.value==='0'||this.value==='')this.value=''"
        onblur="if(!this.value)this.value=''"
        oninput="updateOORTotal()">
    </td></tr>`;
  return cells;
}

// ── OOR Category multi-select panel ──────────────────────
function buildOORCatPanel(cats){
  const list = document.getElementById('oor-cat-list');
  if(!list) return;
  list.innerHTML = cats.map(function(c){
    const name   = typeof c === 'string' ? c : (c.category||c);
    const prefix = typeof c === 'object' ? (c.sku_prefix||'') : '';
    const label  = prefix ? '<span style="font-family:monospace;color:var(--accent);min-width:28px;display:inline-block">'+esc(prefix)+'</span> '+esc(name) : esc(name);
    return '<label style="display:flex;align-items:center;gap:8px;font-size:.82rem;padding:5px 10px;cursor:pointer;border-radius:4px" onmouseover="this.style.background=\'var(--surface2)\'" onmouseout="this.style.background=\'\'">'
      +'<input type="checkbox" value="'+esc(name)+'" checked onchange="onOORCatChange()"> '+label
      +'</label>';
  }).join('');
  // Build clickable pills
  const refBody = document.getElementById('oor-cat-ref-body');
  const refWrap = document.getElementById('oor-cat-ref');
  if(refBody && cats.length){
    refBody.innerHTML = '<span style="font-size:.68rem;color:var(--text3);font-weight:600;flex-shrink:0">Jump to:</span>';
    cats.forEach(function(cat){
      const name   = typeof cat === 'string' ? cat : (cat.category||cat);
      const prefix = (typeof cat === 'object' && cat.sku_prefix) ? cat.sku_prefix : '';
      const chip = document.createElement('span');
      chip.title = 'Jump to ' + name;
      chip.style.cssText = 'display:inline-flex;align-items:center;gap:3px;padding:2px 8px 2px 6px;background:var(--surface2);border:1px solid var(--border2);border-radius:20px;font-size:.75rem;cursor:pointer;white-space:nowrap';
      chip.innerHTML = (prefix?'<b style="font-family:monospace;color:var(--accent);font-size:.7rem">'+esc(prefix)+'</b>&nbsp;':'')
        + '<span style="color:var(--accent);text-decoration:underline;text-underline-offset:2px">'+esc(name)+'</span>';
      chip.addEventListener('click', (function(n){ return function(){ jumpToOORCat(n); }; })(name));
      chip.addEventListener('mouseover', function(){ chip.style.borderColor='var(--accent)'; chip.style.background='rgba(79,142,255,.1)'; });
      chip.addEventListener('mouseout',  function(){ chip.style.borderColor='var(--border2)'; chip.style.background=''; });
      refBody.appendChild(chip);
    });
    if(refWrap) refWrap.style.display = '';
  }
}

function jumpToOORCat(name){
  const lname = name.trim().toLowerCase();
  const rows = document.querySelectorAll('#oor-tbody tr');
  let target = null;
  for(let i=0;i<rows.length;i++){
    const hd = rows[i].querySelector('td[colspan]');
    if(hd && hd.textContent.trim().toLowerCase().includes(lname)){ target=rows[i]; break; }
    if(rows[i].dataset.category && rows[i].dataset.category.trim().toLowerCase()===lname){ target=rows[i]; break; }
  }
  if(target){
    const wrap = document.getElementById('oor-table-wrap');
    if(wrap){ wrap.scrollTop += target.getBoundingClientRect().top - wrap.getBoundingClientRect().top - 60; }
    target.style.transition='background .4s';
    target.style.background='rgba(79,142,255,.25)';
    setTimeout(function(){ target.style.background=''; },1800);
  }
}
function toggleOORCatPanel(){
  const panel = document.getElementById('oor-cat-panel');
  if(!panel) return;
  const isOpen = panel.style.display !== 'none';
  panel.style.display = isOpen ? 'none' : 'block';
  if(!isOpen){
    // Close on outside click
    setTimeout(function(){
      document.addEventListener('click', function closePanel(e){
        if(!document.getElementById('oor-cat-wrap')?.contains(e.target)){
          panel.style.display='none';
          document.removeEventListener('click', closePanel);
        }
      });
    }, 10);
  }
}

function onOORCatAllChange(cb){
  document.querySelectorAll('#oor-cat-list input[type=checkbox]').forEach(function(el){
    el.checked = cb.checked;
  });
  updateOORCatLabel();
  _oorFiltersInit = false; // force re-init so categories reload
  loadOnOrderReport();
}

function onOORCatChange(){
  const all    = document.querySelectorAll('#oor-cat-list input[type=checkbox]');
  const checked = document.querySelectorAll('#oor-cat-list input[type=checkbox]:checked');
  const allCb  = document.getElementById('oor-cat-all');
  if(allCb) allCb.checked = all.length === checked.length;
  updateOORCatLabel();
  loadOnOrderReport();
}

function updateOORCatLabel(){
  const all     = document.querySelectorAll('#oor-cat-list input[type=checkbox]');
  const checked = document.querySelectorAll('#oor-cat-list input[type=checkbox]:checked');
  const label   = document.getElementById('oor-cat-label');
  if(!label) return;
  if(!all.length || all.length === checked.length){
    label.textContent = 'All Categories';
  } else if(checked.length === 0){
    label.textContent = 'No Category';
  } else if(checked.length <= 2){
    label.textContent = Array.from(checked).map(function(el){ return el.value; }).join(', ');
  } else {
    label.textContent = checked.length+' categories';
  }
}

async function loadOnOrderReport(){
  const search    = document.getElementById('oor-search')?.value||'';
  const itemCode  = document.getElementById('oor-item-code')?.value.trim()||'';
  const vendor    = document.getElementById('oor-vendor')?.value||'';
  const brand     = document.getElementById('oor-brand')?.value||'';
  const filter    = document.getElementById('oor-filter')?.value||'';
  const groupBy   = document.getElementById('oor-group')?.value||'';
  // Collect checked categories
  const catChecks = document.querySelectorAll('#oor-cat-list input[type=checkbox]:checked');
  const cats      = Array.from(catChecks).map(function(el){ return el.value; });

  const tbody = document.getElementById('oor-tbody');
  const thead = document.getElementById('oor-thead');
  if(tbody) tbody.innerHTML='<tr><td colspan="20" style="text-align:center;padding:30px;color:var(--text3)"><span class="spinner"></span> Loading…</td></tr>';

  try{
    const params = new URLSearchParams({search, item_code:itemCode, vendor, brand, filter});
    cats.forEach(function(c){ params.append('categories[]', c); });
    const r = await api.get('api/on_order_report.php?'+params.toString());
    _oorData = r.data;

    // Populate filter dropdowns once
    if(!_oorFiltersInit){
      _oorFiltersInit = true;
      buildOORCatPanel(r.data.categories||[]);
      const venSel = document.getElementById('oor-vendor');
      (r.data.vendors||[]).forEach(function(v){
        const o=document.createElement('option'); o.value=v; o.textContent=v; venSel?.appendChild(o);
      });
      const brandSel = document.getElementById('oor-brand');
      (r.data.brands||[]).forEach(function(b){
        const o=document.createElement('option'); o.value=b; o.textContent=b; brandSel?.appendChild(o);
      });
    }
    if(vendor) document.getElementById('oor-vendor').value=vendor;

    // Summary cards
    const s = r.data.summary;
    const sumEl = document.getElementById('oor-summary');
    if(sumEl) sumEl.innerHTML = `
      <div class="stat-card" style="--accent-color:var(--accent);cursor:pointer" onclick="document.getElementById('oor-filter').value='';loadOnOrderReport()">
        <span class="stat-icon">📦</span><span class="stat-num">${fmt(s.total)}</span>
        <span class="stat-label">Total Products</span></div>
      <div class="stat-card" style="--accent-color:var(--red);cursor:pointer" onclick="document.getElementById('oor-filter').value='out';loadOnOrderReport()">
        <span class="stat-icon">🚫</span><span class="stat-num">${s.out_of_stock}</span>
        <span class="stat-label">Out of Stock</span></div>
      <div class="stat-card" style="--accent-color:var(--orange);cursor:pointer" onclick="document.getElementById('oor-filter').value='low';loadOnOrderReport()">
        <span class="stat-icon">⚠️</span><span class="stat-num">${s.low_stock}</span>
        <span class="stat-label">Low Stock</span></div>
      <div class="stat-card" style="--accent-color:var(--green);cursor:pointer" onclick="document.getElementById('oor-filter').value='on_order';loadOnOrderReport()">
        <span class="stat-icon">🚚</span><span class="stat-num">${fmt(s.on_order)}</span>
        <span class="stat-label">Units On Order</span></div>
      <div class="stat-card" style="--accent-color:#f97316;cursor:pointer" onclick="document.getElementById('oor-filter').value='no_order';loadOnOrderReport()">
        <span class="stat-icon">🔴</span><span class="stat-num">${s.needs_reorder}</span>
        <span class="stat-label">Needs Reorder</span></div>`;

    const locs = r.data.locations||[];
    const rows = r.data.rows||[];

    if(!rows.length){
      if(tbody) tbody.innerHTML='';
      document.getElementById('oor-empty').style.display='';
      document.getElementById('oor-table-wrap').style.display='none';
      return;
    }
    document.getElementById('oor-empty').style.display='none';
    document.getElementById('oor-table-wrap').style.display='';

    // Header: Item Code | Product | Brand | Category | Vendor | [Loc Stock+OnOrder]... | Total Stock | On Order (tooltip) | To Be Ordered
    let hHtml=`<tr>`;
    if(oorColVis('item_code'))    hHtml+=`<th rowspan="2" style="vertical-align:bottom">Item<br>Code</th>`;
    if(oorColVis('sku'))          hHtml+=`<th rowspan="2" style="vertical-align:bottom">SKU</th>`;
    hHtml+=`<th rowspan="2" style="min-width:130px;vertical-align:bottom">Product</th>`;
    if(oorColVis('brand'))        hHtml+=`<th rowspan="2" style="vertical-align:bottom"><span style="color:var(--accent)">Brand</span></th>`;
    if(oorColVis('category'))     hHtml+=`<th rowspan="2" style="vertical-align:bottom">Category</th>`;
    if(oorColVis('vendor'))       hHtml+=`<th rowspan="2" style="vertical-align:bottom">Vendor</th>`;
    if(oorColVis('cost'))         hHtml+=`<th rowspan="2" style="text-align:right;vertical-align:bottom">Cost<br>₹</th>`;
    if(oorColVis('case_content')) hHtml+=`<th rowspan="2" style="text-align:center;vertical-align:bottom">Case<br>Cont.</th>`;
    locs.forEach(l=>{ hHtml+=`<th colspan="2" style="text-align:center;border-bottom:1px solid var(--border2);color:var(--text2)">${esc(l.name)}</th>`; });
    hHtml+=`<th rowspan="2" style="text-align:center;vertical-align:bottom">Total<br>Stock</th>
      <th rowspan="2" style="text-align:center;color:var(--accent);vertical-align:bottom;cursor:help" title="Hover any value to see active POs">On<br>Order ℹ</th>
      <th rowspan="2" style="text-align:center;color:#f97316;vertical-align:bottom">To Be<br>Ordered</th>
    </tr><tr>`;
    locs.forEach(()=>{ hHtml+=`<th style="text-align:center;font-size:.65rem;color:var(--green);padding:2px 5px">Stk</th><th style="text-align:center;font-size:.65rem;color:var(--accent);padding:2px 5px">Ord</th>`; });
    hHtml+=`</tr>`;
    if(thead) thead.innerHTML=hHtml;

    // Badge helper
    const badge = st=>({
      draft:`<span class="badge" style="background:rgba(100,116,139,.15);color:var(--text2);font-size:.65rem">Draft</span>`,
      sent:`<span class="badge badge-blue" style="font-size:.65rem">Sent</span>`,
      partial:`<span class="badge" style="background:rgba(251,191,36,.15);color:#f59e0b;font-size:.65rem">Partial</span>`,
    }[st]||'');

    let html='';

    // ── Group-by helper ───────────────────────────────────
    const getGroupKey = (r) => {
      if(groupBy==='item_code') return r.item_code ? String(r.item_code) : 'No Item Code';
      if(groupBy==='category') return String(r.category||'Uncategorised');
      if(groupBy==='vendor')   return String(r.vendor_name||'No Vendor');
      if(groupBy==='brand')    return String(r.brand||'No Brand');
      if(groupBy==='status'){
        if(r.total_stock<=0) return '🚫 Out of Stock';
        if(r.min_stock>0 && r.total_stock<=r.min_stock) return '⚠️ Low Stock';
        if(r.on_order>0) return '🚚 On Order';
        return '✅ In Stock';
      }
      return 'Other';
    };

    const colCount = oorFixedColCount() + locs.length*2 + 3; // visible fixed cols + loc pairs + Total/OnOrder/TBO

    if(groupBy){
      // Group rows by key, preserving order of first appearance
      const groups = {};
      const groupOrder = [];
      rows.forEach(r=>{
        const key = getGroupKey(r);
        if(!groups[key]){ groups[key]=[]; groupOrder.push(key); }
        groups[key].push(r);
      });
      groupOrder.sort((a,b)=>String(a||'').localeCompare(String(b||'')));
      groupOrder.forEach(groupKey=>{
        // Group header row
        html+=`<tr style="background:var(--surface2)">
          <td colspan="${colCount}" style="padding:8px 12px;font-weight:700;font-size:.82rem;color:var(--text2);border-top:2px solid var(--border2)">
            ${esc(groupKey)} <span style="font-size:.72rem;color:var(--text3);font-weight:400">(${groups[groupKey].length} item${groups[groupKey].length===1?'':'s'})</span>
          </td></tr>`;
        groups[groupKey].forEach(r=>{ html+=buildOORRow(r,locs,badge); });
      });
    } else {
      rows.forEach(r=>{ html+=buildOORRow(r,locs,badge); });
    }

    if(tbody) tbody.innerHTML=html;
    restoreOORInputs();
    updateOORTotal();

  }catch(e){ toast(e.message,'error'); if(tbody) tbody.innerHTML=''; }
}

const OOR_TBO_KEY = 'invyrr_tbo_values';

function saveTBOValue(pid, value){
  try{
    const store = JSON.parse(localStorage.getItem(OOR_TBO_KEY)||'{}');
    if(value){ store[pid] = value; } else { delete store[pid]; }
    localStorage.setItem(OOR_TBO_KEY, JSON.stringify(store));
  }catch(e){}
}

function restoreOORInputs(){
  try{
    const store = JSON.parse(localStorage.getItem(OOR_TBO_KEY)||'{}');
    document.querySelectorAll('.oor-tbo-input').forEach(inp=>{
      const v = store[inp.dataset.pid];
      if(v !== undefined) inp.value = v;
    });
  }catch(e){}
}

function updateOORTotal(){
  const inputs = document.querySelectorAll('.oor-tbo-input');
  let total = 0;
  inputs.forEach(inp=>{
    const raw = inp.value||'';
    const num = parseInt(raw,10)||0;
    total += num;
    saveTBOValue(inp.dataset.pid, raw);
  });
  const el = document.getElementById('oor-tbo-total');
  if(el) el.textContent = total > 0 ? 'Total numeric qty: ' + fmt(total) : '';
}

function clearOORInputs(){
  if(!confirm('Clear all To Be Ordered values?')) return;
  document.querySelectorAll('.oor-tbo-input').forEach(inp=>inp.value='');
  localStorage.removeItem(OOR_TBO_KEY);
  const el = document.getElementById('oor-tbo-total');
  if(el) el.textContent='';
  toast('To Be Ordered values cleared');
}

// ══════════════════════════════════════════════════════════
// PAID TO REPORT
// ══════════════════════════════════════════════════════════
let _ptrData = [];

async function loadPaidToReport(){
  const from  = document.getElementById('ptr-from')?.value||'';
  const to    = document.getElementById('ptr-to')?.value||'';
  const group = document.getElementById('ptr-group')?.value||'payee';
  const tbody = document.getElementById('ptr-body');
  const thead = document.getElementById('ptr-thead');
  if(tbody) tbody.innerHTML='<tr><td colspan="6" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    const params = new URLSearchParams();
    if(from) params.set('from',from);
    if(to)   params.set('to',to);
    const r = await api.get(API.expenses+'?'+params+'&entity_id=all');
    const rows = (r.data||[]).filter(function(e){ return e.paid_to_id; });
    _ptrData = rows;

    // Stats
    const total = rows.reduce(function(s,e){ return s+(+e.amount||0); },0);
    const uniquePeople = new Set(rows.map(function(e){ return e.paid_to_name; })).size;
    document.getElementById('ptr-stats').innerHTML =
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Paid</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(total)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Transactions</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+rows.length+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">People Paid</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+uniquePeople+'</div></div>';

    document.getElementById('ptr-empty').style.display = rows.length?'none':'block';
    if(!rows.length){ if(tbody) tbody.innerHTML=''; return; }

    // Build grouped view
    const getKey = function(e){
      if(group==='category') return e.category||'General';
      if(group==='month')    return e.expense_date ? e.expense_date.slice(0,7) : 'Unknown';
      return e.paid_to_name||'Unknown';
    };
    const groups={}, order=[];
    rows.forEach(function(e){ const k=getKey(e); if(!groups[k]){groups[k]=[];order.push(k);} groups[k].push(e); });
    order.sort();

    if(thead) thead.innerHTML='<tr><th>Date</th><th>Paid To</th><th>Category</th><th>Paid Via</th><th>Business</th><th style="text-align:right">Amount ₹</th></tr>';
    let html='';
    let grand=0;
    order.forEach(function(key){
      const grp=groups[key];
      const grpTotal=grp.reduce(function(s,e){return s+(+e.amount||0);},0);
      grand+=grpTotal;
      html+='<tr style="background:var(--surface2)"><td colspan="5" style="font-weight:700;font-size:.82rem;color:var(--text2);padding:7px 12px">'+esc(key)+' <span style="color:var(--text3);font-weight:400;font-size:.72rem">('+grp.length+' record'+(grp.length===1?'':'s')+')</span></td><td style="text-align:right;font-weight:700;color:var(--accent)">'+CUR.sym+fmtN(grpTotal)+'</td></tr>';
      grp.forEach(function(e){
        html+='<tr style="font-size:.83rem">'
          +'<td style="white-space:nowrap">'+fmtExpDate(e.expense_date)+'</td>'
          +'<td style="font-weight:500">'+esc(e.paid_to_name||'—')+'<br><span style="font-size:.7rem;color:var(--text3)">'+esc(e.paid_to_type||'')+'</span></td>'
          +'<td><span class="badge badge-blue" style="font-size:.7rem">'+esc(e.category)+'</span></td>'
          +'<td style="font-size:.78rem">'+esc(e.payee_name||'—')+(e.payee_type?'<br><span style="font-size:.68rem;color:var(--text3)">'+esc(e.payee_type)+'</span>':'')+'</td>'
          +'<td style="font-size:.78rem;color:var(--text3)">'+esc(e.entity_name||'—')+'</td>'
          +'<td style="text-align:right;color:var(--red);font-weight:600">'+CUR.sym+fmtN(+e.amount)+'</td>'
          +'</tr>';
      });
    });
    if(tbody) tbody.innerHTML=html;
    document.getElementById('ptr-foot').innerHTML='<tr style="font-weight:700;background:var(--surface2)"><td colspan="5">TOTAL</td><td style="text-align:right;color:var(--red)">'+CUR.sym+fmtN(grand)+'</td></tr>';
    setElText('ptr-count', rows.length+' record'+(rows.length===1?'':'s'));
  }catch(e){ toast(e.message,'error'); if(tbody) tbody.innerHTML=''; }
}

function exportPaidToReport(){
  if(!_ptrData.length){ toast('No data to export','error'); return; }
  const headers=['Date','Paid To','Paid To Type','Category','Paid Via','Business','Amount'];
  const rows=_ptrData.map(function(e){ return [fmtExpDate(e.expense_date),e.paid_to_name||'',e.paid_to_type||'',e.category||'',e.payee_name||'',e.entity_name||'',Math.round(+e.amount||0)]; });
  downloadCsv(rowsToCsv([headers,...rows]),'PaidTo_Report_'+new Date().toISOString().split('T')[0]+'.csv');
  toast('Exported '+rows.length+' records 📊');
}

// ══════════════════════════════════════════════════════════
// VENDOR PAYMENTS REPORT
// ══════════════════════════════════════════════════════════
let _vprData = [];

async function loadVPReport(){
  const from  = document.getElementById('vpr-from')?.value||'';
  const to    = document.getElementById('vpr-to')?.value||'';
  const type  = document.getElementById('vpr-type')?.value||'';
  const group = document.getElementById('vpr-group')?.value||'vendor';
  const tbody = document.getElementById('vpr-body');
  const thead = document.getElementById('vpr-thead');
  if(tbody) tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:30px"><span class="spinner"></span></td></tr>';
  try{
    const params = new URLSearchParams();
    if(from) params.set('from',from);
    if(to)   params.set('to',to);
    if(type) params.set('type',type);
    const r = await api.get(API.vendorPayments+'?report=1&'+params);
    let rows = r.data||[];
    _vprData = rows;

    // Stats
    const payments  = rows.filter(function(e){return e.type==='payment';}).reduce(function(s,e){return s+(+e.amount||0);},0);
    const credits   = rows.filter(function(e){return e.type==='credit_note';}).reduce(function(s,e){return s+(+e.amount||0);},0);
    const net       = payments - credits;
    document.getElementById('vpr-stats').innerHTML=
      '<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Transactions</div><div style="font-size:1rem;font-weight:800;color:var(--accent)">'+rows.length+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Total Payments</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(payments)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Credit Notes</div><div style="font-size:1rem;font-weight:800;color:var(--green)">'+CUR.sym+fmtN(credits)+'</div></div>'
      +'<div style="background:var(--surface2);border-radius:var(--radius-sm);padding:12px 14px"><div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:4px">Net Paid</div><div style="font-size:1rem;font-weight:800;color:var(--red)">'+CUR.sym+fmtN(net)+'</div></div>';

    document.getElementById('vpr-empty').style.display=rows.length?'none':'block';
    if(!rows.length){ if(tbody) tbody.innerHTML=''; return; }

    // Group
    const getKey = function(e){
      if(group==='payee') return e.payee_name||'No Payee';
      if(group==='type')  return (e.type||'payment').replace('_',' ').replace(/\b\w/g,function(c){return c.toUpperCase();});
      if(group==='month') return e.payment_date ? e.payment_date.slice(0,7) : 'Unknown';
      return e.vendor_name||'No Vendor';
    };
    const groups={}, order=[];
    rows.forEach(function(e){ const k=getKey(e); if(!groups[k]){groups[k]=[];order.push(k);} groups[k].push(e); });
    order.sort();

    if(thead) thead.innerHTML='<tr><th>Date</th><th>Vendor</th><th>Type</th><th>Paid Via</th><th>Reference</th><th>Description</th><th style="text-align:right">Amount ₹</th></tr>';
    const typeBadge = function(t){
      const m={'payment':'<span class="badge badge-blue" style="font-size:.7rem">Payment</span>','credit_note':'<span class="badge" style="background:rgba(52,211,153,.15);color:var(--green);font-size:.7rem">Credit</span>','opening_balance':'<span class="badge" style="font-size:.7rem">Opening</span>'};
      return m[t]||'<span class="badge" style="font-size:.7rem">'+esc(t)+'</span>';
    };
    let html=''; let grand=0;
    order.forEach(function(key){
      const grp=groups[key];
      const grpTotal=grp.filter(function(e){return e.type!=='credit_note';}).reduce(function(s,e){return s+(+e.amount||0);},0);
      grand+=grpTotal;
      html+='<tr style="background:var(--surface2)"><td colspan="6" style="font-weight:700;font-size:.82rem;color:var(--text2);padding:7px 12px">'+esc(key)+' <span style="color:var(--text3);font-weight:400;font-size:.72rem">('+grp.length+' record'+(grp.length===1?'':'s')+')</span></td><td style="text-align:right;font-weight:700;color:var(--accent)">'+CUR.sym+fmtN(grpTotal)+'</td></tr>';
      grp.forEach(function(e){
        html+='<tr style="font-size:.83rem">'
          +'<td style="white-space:nowrap">'+esc(e.payment_date||'—')+'</td>'
          +'<td>'+esc(e.vendor_name||'—')+'</td>'
          +'<td>'+typeBadge(e.type)+'</td>'
          +'<td style="font-size:.78rem">'+esc(e.payee_name||'—')+'</td>'
          +'<td style="font-size:.75rem;color:var(--text3)">'+esc(e.reference_no||'—')+'</td>'
          +'<td style="font-size:.78rem;color:var(--text2)">'+esc(e.description||e.notes||'—')+'</td>'
          +'<td style="text-align:right;color:'+(e.type==='credit_note'?'var(--green)':'var(--red)')+';font-weight:600">'+CUR.sym+fmtN(+e.amount)+'</td>'
          +'</tr>';
      });
    });
    if(tbody) tbody.innerHTML=html;
    document.getElementById('vpr-foot').innerHTML='<tr style="font-weight:700;background:var(--surface2)"><td colspan="6">TOTAL PAID</td><td style="text-align:right;color:var(--red)">'+CUR.sym+fmtN(grand)+'</td></tr>';
    setElText('vpr-count', rows.length+' record'+(rows.length===1?'':'s'));
  }catch(e){ toast(e.message,'error'); if(tbody) tbody.innerHTML=''; }
}

function exportVPReport(){
  if(!_vprData.length){ toast('No data to export','error'); return; }
  const headers=['Date','Vendor','Type','Paid Via','Reference','Description','Amount'];
  const rows=_vprData.map(function(e){ return [e.payment_date||'',e.vendor_name||'',e.type||'',e.payee_name||'',e.reference_no||'',e.description||e.notes||'',Math.round(+e.amount||0)]; });
  downloadCsv(rowsToCsv([headers,...rows]),'VendorPayments_Report_'+new Date().toISOString().split('T')[0]+'.csv');
  toast('Exported '+rows.length+' records 📊');
}

function exportOnOrderReport(){
  if(!_oorData) return;
  const locs = _oorData.locations||[];
  const rows = _oorData.rows||[];

  const headers = ['Item Code','SKU','Product','Brand','Category','Vendor'];
  if(!HIDE_COST) headers.push('Cost');
  headers.push('Case Content','Min Stock','Total Stock');
  locs.forEach(l=>{ headers.push(l.name+' Stock'); headers.push(l.name+' On Order'); });
  headers.push('Total On Order','Active POs','To Be Ordered');

  // Collect TBO values from inputs
  const tboMap={};
  document.querySelectorAll('.oor-tbo-input').forEach(inp=>{
    if(inp.value) tboMap[inp.dataset.pid]=parseInt(inp.value,10)||0;
  });

  const csvRows=[headers];
  rows.forEach(r=>{
    const row=[r.item_code||'', r.sku||'', r.name, r.brand||'', r.category, r.vendor_name];
    if(!HIDE_COST) row.push(Math.round(+r.cost||0));
    row.push(r.case_content||'', r.min_stock, r.total_stock);
    locs.forEach(l=>{
      const onOrd = r.pos.filter(p=>p.location_id && String(p.location_id)===String(l.id)).reduce((s,p)=>s+(+p.pending_qty||0),0);
      row.push(r['loc_'+l.id]||0);
      row.push(onOrd||0);
    });
    row.push(r.on_order||0);
    row.push(r.pos.map(p=>`${p.po_number}(${p.status}×${p.pending_qty})`).join('; '));
    row.push(tboMap[String(r.id)]||'');
    csvRows.push(row);
  });

  const today=new Date().toISOString().split('T')[0];
  downloadCsv(rowsToCsv(csvRows), `Procurement_Dashboard_${today}.csv`);
  toast('Procurement dashboard exported 📊');
}

// ══════════════════════════════════════════════════════════
async function loadAlerts(){
  try{
    const r=await api.get(API.products+'?stock_filter=low');
    const out=await api.get(API.products+'?stock_filter=out');
    const all=[...out.data,...r.data.filter(p=>+p.stock>0)];
    const tbody=document.getElementById('alerts-body');const empty=document.getElementById('alerts-empty');
    const btn=document.getElementById('send-alert-btn');
    if(btn)btn.style.display=all.length?'':'none';
    if(!all.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=all.map(p=>`<tr>
      <td><strong>${esc(p.name)}</strong></td>
      <td>${p.brand?`<span class="badge badge-orange">${esc(p.brand)}</span>`:'—'}</td>
      <td>${p.category?`<span class="badge badge-blue">${esc(p.category)}</span>`:'—'}</td>
      <td class="mono" style="font-weight:700;color:${+p.stock<=0?'var(--red)':'var(--yellow)'}">${p.stock} ${esc(p.unit)}</td>
      <td class="mono">${p.min_stock}</td>
      <td class="mono text-red">${Math.max(0,+p.min_stock-+p.stock)}</td>
      <td style="color:var(--text2)">${esc(p.vendor_name||'—')}</td>
      <td><button class="btn btn-success btn-xs" onclick="showPage('stock-in')">+ Restock</button></td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
async function sendAlertEmail(){
  const btn=document.getElementById('send-alert-btn');
  if(btn){btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Sending…';}
  try{const r=await api.post('api/send_alert.php',{});toast(r.message,'success');}
  catch(e){toast(e.message,'error');}
  finally{if(btn){btn.disabled=false;btn.innerHTML='📧 Email Alert Now';}}
}
async function updateAlertBadge(){
  try{
    const r=await api.get(API.dashboard);
    const cnt=r.data.stats.low_stock_count;
    const _ab=document.getElementById('alert-badge');
    if(_ab){_ab.textContent=cnt;_ab.style.display=+cnt>0?'':'none';}
  }catch{}
}

// ══════════════════════════════════════════════════════════
// LOCATIONS
// ══════════════════════════════════════════════════════════
async function loadLocations(){
  try{
    const r=await api.get(API.locations);
    const tbody=document.getElementById('locations-body');
    tbody.innerHTML=r.data.map(l=>`<tr>
      <td><strong>${esc(l.name)}</strong>${+l.is_default?' <span class="badge badge-blue" style="font-size:.62rem">Default</span>':''}</td>
      <td style="font-size:.8rem;color:var(--text2)">${esc(l.address||'—')}</td>
      <td>${esc(l.phone||'—')}</td>
      <td class="mono">${l.product_count}</td>
      <td class="mono">${l.total_units}</td>
      ${HIDE_STOCK_VALUE?'<td>—</td>':`<td class="mono text-accent">${CUR.sym}${fmtN(l.stock_value)}</td>`}
      <td>${+l.low_stock_count>0?`<span class="badge badge-red">${l.low_stock_count}</span>`:'<span class="badge badge-green">OK</span>'}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-ghost btn-xs" onclick="editLocation(${l.id})">✏️</button>
        ${CAN_DELETE&&!+l.is_default?`<button class="btn btn-danger btn-xs" onclick="deleteLocation(${l.id},'${esc(l.name)}')">🗑️</button>`:""}
      </td>
    </tr>`).join('');
    const sel=document.getElementById('loc-stock-location-filter');
    if(sel){const cur=sel.value;sel.innerHTML='<option value="">Select location…</option>'+r.data.map(l=>`<option value="${l.id}" ${cur==l.id?'selected':''}>${esc(l.name)}</option>`).join('');}
  }catch(e){toast(e.message,'error');}
}
async function loadLocationStockTable(){
  const locId=document.getElementById('loc-stock-location-filter')?.value;
  const tbody=document.getElementById('loc-stock-body');
  if(!locId){tbody.innerHTML='<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text3)">Select a location above</td></tr>';return;}
  try{
    const r=await api.get(API.locations+'?id='+locId);
    const ps=r.data.products;
    if(!ps.length){tbody.innerHTML='<tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text3)">No products</td></tr>';return;}
    tbody.innerHTML=ps.map(p=>{
      const sc=+p.stock<=0?['badge-red','Out']:+p.stock<=+p.min_stock?['badge-yellow','Low']:['badge-green','OK'];
      return `<tr><td><strong>${esc(p.name)}</strong></td><td>${p.brand?`<span class="badge badge-orange">${esc(p.brand)}</span>`:'—'}</td><td>${p.category?`<span class="badge badge-blue">${esc(p.category)}</span>`:'—'}</td><td class="mono" style="font-weight:700;color:${+p.stock<=0?'var(--red)':+p.stock<=+p.min_stock?'var(--yellow)':'var(--text)'}">${p.stock} ${esc(p.unit)}</td><td class="mono" style="color:var(--text3)">${p.min_stock}</td><td><span class="badge ${sc[0]}">${sc[1]}</span></td><td class="mono text-accent">${CUR.sym}${fmtN(p.stock*p.cost)}</td><td class="mono text-green">${CUR.sym}${fmtN(p.stock*p.sell)}</td></tr>`;
    }).join('');
  }catch(e){toast(e.message,'error');}
}
async function editLocation(id){
  try{
    const r=await api.get(API.locations+'?id='+id);const l=r.data;
    setElText('loc-form-title', '✏️ Edit Location');
    document.getElementById('loc-edit-id').value=l.id;
    document.getElementById('loc-name').value=l.name;
    document.getElementById('loc-phone').value=l.phone||'';
    document.getElementById('loc-address').value=l.address||'';
    document.getElementById('loc-default').checked=!!+l.is_default;
    document.getElementById('loc-cancel-btn').style.display='inline-flex';
  }catch(e){toast(e.message,'error');}
}
function cancelLocationEdit(){
  setElText('loc-form-title', '🏪 Add Location');
  document.getElementById('loc-edit-id').value='';
  ['loc-name','loc-phone','loc-address'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  document.getElementById('loc-default').checked=false;
  document.getElementById('loc-cancel-btn').style.display='none';
}
async function saveLocation(){
  const name=document.getElementById('loc-name').value.trim();if(!name){toast('Name required','error');return;}
  const editId=parseInt(document.getElementById('loc-edit-id').value)||0;
  const body={name,phone:document.getElementById('loc-phone').value.trim(),address:document.getElementById('loc-address').value.trim(),is_default:document.getElementById('loc-default').checked?1:0};
  const btn=document.getElementById('loc-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){body.id=editId;await api.put(API.locations,body);toast('Updated!');}
    else{await api.post(API.locations,body);toast('Location added!');}
    cancelLocationEdit();loadLocations();loadGlobalLocationSelector();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='Save Location';}
}
async function deleteLocation(id,name){if(!confirm(`Delete "${name}"?`))return;try{await api.delete(API.locations+'?id='+id);toast('Deleted');loadLocations();loadGlobalLocationSelector();}catch(e){toast(e.message,'error');}}

// ══════════════════════════════════════════════════════════
// USERS
// ══════════════════════════════════════════════════════════
async function loadUsers(){
  try{
    const r=await api.get(API.users);
    document.getElementById('users-body').innerHTML=r.data.map(u=>`<tr>
      <td><strong>${esc(u.name)}</strong></td>
      <td><span class="badge ${u.role==='admin'?'badge-purple':u.role==='manager'?'badge-blue':'badge-gray'}">${u.role}</span></td>
      <td><span class="badge ${+u.is_active?'badge-green':'badge-red'}">${+u.is_active?'Active':'Inactive'}</span></td>
      <td style="font-size:.78rem;color:var(--text3)">${u.last_login?u.last_login.slice(0,16):'Never'}</td>
      <td><button class="btn btn-ghost btn-xs" onclick="editUser(${u.id})">✏️</button> ${CAN_DELETE?`<button class="btn btn-danger btn-xs" onclick="deleteUser(${u.id},'${esc(u.name)}')">🗑️</button>`:""}</td>
    </tr>`).join('');
  }catch(e){toast(e.message,'error');}
}
function cancelUserEdit(){
  setElText('user-form-title', '👥 Add User');
  document.getElementById('usr-edit-id').value='';
  ['usr-name','usr-email','usr-pass'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});
  document.getElementById('usr-role').value='Picker';
  document.getElementById('usr-active').value='1';
  document.getElementById('usr-cancel-btn').style.display='none';
}
async function editUser(id){
  try{
    const r=await api.get(API.users);const u=r.data.find(u=>u.id===id);if(!u)return;
    setElText('user-form-title', '✏️ Edit User');
    document.getElementById('usr-edit-id').value=u.id;
    document.getElementById('usr-name').value=u.name;
    document.getElementById('usr-email').value=u.email;
    document.getElementById('usr-pass').value='';
    document.getElementById('usr-role').value=u.role;
    document.getElementById('usr-active').value=String(u.is_active);
    document.getElementById('usr-cancel-btn').style.display='inline-flex';
  }catch(e){toast(e.message,'error');}
}
async function saveUser(){
  const editId=parseInt(document.getElementById('usr-edit-id').value)||0;
  const body={name:document.getElementById('usr-name').value.trim(),email:document.getElementById('usr-email').value.trim(),password:document.getElementById('usr-pass').value,role:document.getElementById('usr-role').value,is_active:document.getElementById('usr-active').value};
  if(!body.name){toast('Name is required','error');return;}
  if(!editId&&!body.password){toast('Password required for new user','error');return;}
  const btn=document.getElementById('usr-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span>';
  try{
    if(editId){body.id=editId;await api.put(API.users,body);toast('User updated!');}
    else{await api.post(API.users,body);toast('User created!');}
    cancelUserEdit();loadUsers();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='Save User';}
}
async function deleteUser(id,name){if(!confirm(`Delete user "${name}"?`))return;try{await api.delete(API.users+'?id='+id);toast('Deleted');loadUsers();}catch(e){toast(e.message,'error');}}

// ══════════════════════════════════════════════════════════
// AUDIT LOG
// ══════════════════════════════════════════════════════════
async function loadAudit(){
  const from   = document.getElementById('audit-from')?.value;
  const to     = document.getElementById('audit-to')?.value;
  const q      = document.getElementById('audit-search')?.value||'';
  const action = document.getElementById('audit-action-filter')?.value||'';
  const limit  = document.getElementById('audit-limit')?.value||'200';
  const params = new URLSearchParams();
  if(from)   params.set('from',from);
  if(to)     params.set('to',to);
  if(q)      params.set('q',q);
  if(action) params.set('action',action);
  params.set('limit', limit);
  try{
    const r=await api.get('api/audit_log.php?'+params);
    const totalLabel = document.getElementById('audit-total-label');
    if(totalLabel){
      const shown = r.data.length;
      const total = r.total ?? shown;
      totalLabel.textContent = shown < total
        ? 'Showing '+shown+' of '+total+' entries'
        : total+' entries total';
    }
    const tbody=document.getElementById('audit-body');
    const empty=document.getElementById('audit-empty');
    if(!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';

    // Human-readable action labels + badge colours
    const ACTION_META = {
      'create_product':    {label:'Created Product',     cls:'badge-green'},
      'update_product':    {label:'Updated Product',     cls:'badge-blue'},
      'delete_product':    {label:'Deleted Product',     cls:'badge-red'},
      'stock_in':          {label:'Stock In',            cls:'badge-green'},
      'stock_in_reversed': {label:'Stock In Reversed',   cls:'badge-orange'},
      'stock_out':         {label:'Stock Out',           cls:'badge-orange'},
      'stock_out_reversed':{label:'Stock Out Reversed',  cls:'badge-yellow'},
      'create_vendor':     {label:'Created Vendor',      cls:'badge-green'},
      'update_vendor':     {label:'Updated Vendor',      cls:'badge-blue'},
      'delete_vendor':     {label:'Deleted Vendor',      cls:'badge-red'},
      'create_category':   {label:'Created Category',    cls:'badge-green'},
      'update_category':   {label:'Updated Category',    cls:'badge-blue'},
      'delete_category':   {label:'Deleted Category',    cls:'badge-red'},
      'create_po':         {label:'Created PO',          cls:'badge-green'},
      'update_po':         {label:'Updated PO',          cls:'badge-blue'},
      'create_invoice':    {label:'Created Invoice',     cls:'badge-green'},
      'cancel_invoice':    {label:'Cancelled Invoice',   cls:'badge-red'},
      'create_user':       {label:'Created User',        cls:'badge-green'},
      'update_user':       {label:'Updated User',        cls:'badge-blue'},
      'delete_user':       {label:'Deleted User',        cls:'badge-red'},
      'adjustment':        {label:'Stock Adjustment',    cls:'badge-yellow'},
      'stock_transfer':    {label:'Stock Transfer',      cls:'badge-blue'},
      'update_settings':   {label:'Settings Updated',    cls:'badge-gray'},
      'login':             {label:'Login',               cls:'badge-gray'},
      'restore_backup':    {label:'Backup Restored',     cls:'badge-orange'},
      'backup_drive':      {label:'Drive Backup',        cls:'badge-blue'},
      'send_alert':        {label:'Alert Sent',          cls:'badge-yellow'},
      'import':            {label:'Import',              cls:'badge-blue'},
      'import_products':   {label:'Import Products',     cls:'badge-blue'},
      'import_vendors':    {label:'Import Vendors',       cls:'badge-blue'},
      'import_purchase_orders':{label:'Import POs',       cls:'badge-blue'},
      'import_stock_in':   {label:'Import Stock In',      cls:'badge-blue'},
      'import_stock_out':  {label:'Import Stock Out',     cls:'badge-blue'},
      'import_skipped':    {label:'Import Skipped',       cls:'badge-orange'},
    };

    const ENTITY_LABELS = {
      product:'Product', vendor:'Vendor', category:'Category',
      purchase_order:'PO', invoice:'Invoice', user:'User',
      transfer:'Transfer', adjustment:'Adjustment', settings:'Settings',
      database:'Database', low_stock:'Low Stock',
    };

    tbody.innerHTML=r.data.map(a=>{
      const meta  = ACTION_META[a.action] || {label:a.action.replace(/_/g,' '), cls:'badge-gray'};
      const entity= ENTITY_LABELS[a.entity] || a.entity || '—';
      const what  = entity + (a.entity_id ? ' #'+a.entity_id : '');
      return '<tr>'
        +'<td class="mono" style="font-size:.75rem;color:var(--text3);white-space:nowrap">'+esc(a.created_at?.replace('T',' ').slice(0,16)||'—')+'</td>'
        +'<td style="font-weight:600;font-size:.83rem">'+esc(a.user_name||'system')+'</td>'
        +'<td><span class="badge '+meta.cls+'">'+esc(meta.label)+'</span></td>'
        +'<td style="color:var(--text2);font-size:.82rem">'+esc(what)+'</td>'
        +'<td style="font-size:.82rem;color:var(--text)">'+esc(a.detail||'—')+'</td>'
        +'<td style="font-size:.73rem;font-family:var(--mono);color:var(--text3)">'+esc(a.ip||'—')+'</td>'
        +'</tr>';
    }).join('');
  }catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// SETTINGS
// ══════════════════════════════════════════════════════════
let _settings={};
async function getSettings(){if(Object.keys(_settings).length)return _settings;try{const r=await api.get(API.settings);_settings=r.data;CUR.sym=r.data.currency_symbol||'₹';return _settings;}catch{return {};}}
function switchSettingsTab(tab){
  // Sync all settings-tab buttons (inline + subnav)
  document.querySelectorAll('.settings-tab').forEach(t=>t.classList.toggle('active', t.dataset.tab===tab));
  document.querySelectorAll('.settings-tab-pane').forEach(p=>p.style.display='none');
  const pane = document.getElementById('stab-'+tab);
  if(pane) pane.style.display='';
  // Scroll content to top so tab bar is always visible
  document.querySelector('.content')?.scrollTo({top:0,behavior:'instant'});
  window.scrollTo({top:0,behavior:'instant'});
  if(tab==='locations') loadLocations();
  if(tab==='users')     loadUsers();
  if(tab==='backup'){
    loadBackupHistory();
    // Pre-fill saved Client ID
    const el=document.getElementById('s-google-client-id');
    if(el && !el.value && window._GOOGLE_CLIENT_ID) el.value=window._GOOGLE_CLIENT_ID;
  }
  if(tab==='payees')    loadPayees();
  if(tab==='appearance'){
    const grid=document.getElementById('appearance-theme-grid');
    if(grid) grid.innerHTML=themeSwatchesHTML();
    const fgrid=document.getElementById('appearance-font-grid');
    if(fgrid) fgrid.innerHTML=fontSwatchesHTML();
  }
}
async function loadSettings(){
  const s=await getSettings();
  const map={'s-biz-name':'business_name','s-biz-addr':'business_address','s-biz-phone':'business_phone','s-biz-email':'business_email','s-biz-gst':'business_gst','s-sidebar-tagline':'sidebar_tagline','s-inv-prefix':'invoice_prefix','s-po-prefix':'po_prefix','s-currency':'currency_symbol','s-tax':'tax_rate','s-case-margin':'case_margin','s-alert-email':'low_stock_email','s-smtp-host':'smtp_host','s-smtp-port':'smtp_port','s-smtp-user':'smtp_user'};
  Object.entries(map).forEach(([id,key])=>{const el=document.getElementById(id);if(el)el.value=s[key]||'';});
  // Load Google Client ID into JS global and prefill field
  if(s['google_client_id']){
    window._GOOGLE_CLIENT_ID = s['google_client_id'];
    const el=document.getElementById('s-google-client-id');
    if(el) el.value=s['google_client_id'];
    initGIS();
  }
}
async function saveSettings(){
  const map={'s-biz-name':'business_name','s-biz-addr':'business_address','s-biz-phone':'business_phone','s-biz-email':'business_email','s-biz-gst':'business_gst','s-sidebar-tagline':'sidebar_tagline','s-inv-prefix':'invoice_prefix','s-po-prefix':'po_prefix','s-currency':'currency_symbol','s-tax':'tax_rate','s-case-margin':'case_margin','s-alert-email':'low_stock_email','s-smtp-host':'smtp_host','s-smtp-port':'smtp_port','s-smtp-user':'smtp_user','s-smtp-pass':'smtp_pass','s-google-client-id':'google_client_id'};
  const body={};Object.entries(map).forEach(([id,key])=>{const el=document.getElementById(id);if(el)body[key]=el.value;});
  const btn=document.getElementById('settings-save-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Saving…';
  try{await api.put(API.settings,body);_settings={};await getSettings();toast('Settings saved!');}catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='💾 Save All Settings';}
}
async function testEmail(){
  toast('Sending test email…','');
  try{const r=await api.post('api/send_alert.php',{test:true});toast(r.message);}catch(e){toast(e.message,'error');}
}

// ══════════════════════════════════════════════════════════
// BARCODE SCANNER
// ══════════════════════════════════════════════════════════
let barcodeTarget=null;let scanStream=null;
function openBarcodeModal(targetSelectId=null){
  barcodeTarget=targetSelectId;
  setElText('barcode-result', '');
  document.getElementById('barcode-manual').value='';
  openModal('modal-barcode');
  startScanner();
}
function startScanner(){
  if(!navigator.mediaDevices?.getUserMedia)return;
  navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(stream=>{
    scanStream=stream;
    const video=document.getElementById('scanner-video');
    video.srcObject=stream;video.play();
    // Use Quagga for barcode detection
    if(typeof Quagga!=='undefined'){
      Quagga.init({inputStream:{type:'LiveStream',target:document.getElementById('scanner-container'),constraints:{facingMode:'environment'}},decoder:{readers:['ean_reader','code_128_reader','code_39_reader','upc_reader']}},err=>{if(!err)Quagga.start();});
      Quagga.onDetected(data=>{const code=data.codeResult.code;document.getElementById('barcode-manual').value=code;applyBarcode();});
    }
  }).catch(()=>{});
}
function closeBarcodeModal(){
  if(scanStream){scanStream.getTracks().forEach(t=>t.stop());scanStream=null;}
  if(typeof Quagga!=='undefined')try{Quagga.stop();}catch{}
  closeModal('modal-barcode');
}
async function applyBarcode(){
  const code=document.getElementById('barcode-manual').value.trim();
  if(!code)return;
  if(scanStream){scanStream.getTracks().forEach(t=>t.stop());scanStream=null;}
  // Find product by SKU
  try{
    const r=await api.get(API.products+'?q='+encodeURIComponent(code));
    const match=r.data.find(p=>p.sku===code)||r.data[0];
    if(match&&barcodeTarget){
      const sel=document.getElementById(barcodeTarget);
      if(sel){
        const opt=sel.querySelector(`option[value="${match.id}"]`);
        if(opt){sel.value=match.id;document.getElementById('barcode-result').innerHTML=`✅ Found: <strong>${esc(match.name)}</strong>`;}
        else{document.getElementById('barcode-result').innerHTML=`⚠️ Product "${esc(match.name)}" found but not in list`;}
      }
    }else{document.getElementById('barcode-result').innerHTML=`❌ No product found for barcode: <strong>${esc(code)}</strong>`;}
    setTimeout(()=>closeBarcodeModal(),1500);
  }catch{}
}

// ══════════════════════════════════════════════════════════
// IMPORT
// ══════════════════════════════════════════════════════════
let importFile=null;
function onImportTypeChange(){
  const type=document.getElementById('import-type')?.value;
  const mg=document.getElementById('import-mode-group');
  if(mg)mg.style.display=(type==='stock_in'||type==='stock_out'||type==='expenses'||type==='payees')?'none':'block';
  document.getElementById('import-results-card').style.display='none';
}
function initImportPage(){
  importFile=null;
  document.getElementById('import-file').value='';
  document.getElementById('drop-zone').innerHTML='<div style="font-size:1.8rem;margin-bottom:6px">📄</div><div style="font-weight:600;font-size:.88rem">Drop file here or click to browse</div><div style="font-size:.72rem;color:var(--text3);margin-top:4px">.csv · .xlsx · max 10MB</div>';
  document.getElementById('import-results-card').style.display='none';
  onImportTypeChange();
}
function handleDrop(e){e.preventDefault();document.getElementById('drop-zone').style.borderColor='var(--border2)';const f=e.dataTransfer.files[0];if(f)setImportFile(f);}
function onFileSelect(input){if(input.files[0])setImportFile(input.files[0]);}
function setImportFile(f){
  const allowed=['csv','xlsx','xls'];const ext=f.name.split('.').pop().toLowerCase();
  if(!allowed.includes(ext)){toast('Please upload .csv or .xlsx','error');return;}
  importFile=f;
  document.getElementById('drop-zone').innerHTML=`<div style="font-size:1.8rem;margin-bottom:6px">✅</div><div style="font-weight:600;color:var(--green);font-size:.88rem">${esc(f.name)}</div><div style="font-size:.72rem;color:var(--text3);margin-top:4px">${(f.size/1024).toFixed(1)} KB · Click to change</div>`;
}
async function runImport(){
  if(!importFile){toast('Select a file first','error');return;}
  const btn=document.getElementById('import-btn');btn.disabled=true;btn.innerHTML='<span class="spinner"></span> Importing…';
  try{
    const fd=new FormData();fd.append('file',importFile);fd.append('type',document.getElementById('import-type').value);fd.append('mode',document.getElementById('import-mode')?.value||'insert');
    const res=await fetch(API.import,{method:'POST',body:fd,credentials:'same-origin'});
    const j=await res.json();if(!j.success)throw new Error(j.message);
    const d=j.data;
    document.getElementById('import-results-card').style.display='block';
    setElText('import-result-badge', d.errors?.length?d.errors.length+' issues':'Success');
    document.getElementById('import-result-badge').className='badge '+(d.errors?.length?'badge-yellow':'badge-green');
    document.getElementById('import-result-stats').innerHTML=`
      <div style="background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:8px;padding:10px;text-align:center"><div style="font-size:1.4rem;font-weight:800;font-family:var(--mono);color:var(--green)">${d.inserted}</div><div style="font-size:.68rem;color:var(--text2);text-transform:uppercase;margin-top:2px">Inserted</div></div>
      <div style="background:rgba(79,142,255,.08);border:1px solid rgba(79,142,255,.2);border-radius:8px;padding:10px;text-align:center"><div style="font-size:1.4rem;font-weight:800;font-family:var(--mono);color:var(--accent)">${d.updated}</div><div style="font-size:.68rem;color:var(--text2);text-transform:uppercase;margin-top:2px">Updated</div></div>
      <div style="background:rgba(148,163,184,.08);border:1px solid rgba(148,163,184,.15);border-radius:8px;padding:10px;text-align:center"><div style="font-size:1.4rem;font-weight:800;font-family:var(--mono);color:var(--text2)">${d.skipped}</div><div style="font-size:.68rem;color:var(--text2);text-transform:uppercase;margin-top:2px">Skipped</div></div>
      <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:10px;text-align:center"><div style="font-size:1.4rem;font-weight:800;font-family:var(--mono);color:var(--red)">${d.errors?.length||0}</div><div style="font-size:.68rem;color:var(--text2);text-transform:uppercase;margin-top:2px">Errors</div></div>`;
    const eb=document.getElementById('import-errors-box');
    if(d.errors?.length){eb.style.display='block';document.getElementById('import-errors-list').innerHTML=d.errors.map(e=>'• '+esc(e)).join('<br>');}
    else eb.style.display='none';
    // Show auto-created items
    if(d.created?.length){
      eb.style.display='block';
      const wl=document.getElementById('import-errors-list');
      const existing=wl.innerHTML;
      const createdHtml='<span style="color:var(--green);font-weight:600">✅ Auto-created:</span><br>'+d.created.map(w=>'<span style="color:var(--green)"> + '+esc(w)+'</span>').join('<br>');
      wl.innerHTML=(existing?existing+'<br>':'')+createdHtml;
    }
    // Show warnings
    if(d.warnings?.length){
      eb.style.display='block';
      const wl=document.getElementById('import-errors-list');
      const existing=wl.innerHTML;
      const warnHtml=d.warnings.map(w=>'<span style="color:var(--yellow)">⚠️ '+esc(w)+'</span>').join('<br>');
      wl.innerHTML=(existing?existing+'<br>':'')+warnHtml;
    }
    toast(j.message);
    if(document.getElementById('import-type').value==='products'){loadCategories();updateAlertBadge();}
    if(document.getElementById('import-type').value==='purchase_orders'){loadPOs();}
    // Clear file selection after successful import
    importFile=null;
    document.getElementById('import-file').value='';
    document.getElementById('drop-zone').innerHTML='<div style="font-size:1.8rem;margin-bottom:6px">📄</div><div style="font-weight:600;font-size:.88rem">Drop file here or click to browse</div><div style="font-size:.72rem;color:var(--text3);margin-top:4px">.csv · .xlsx · max 10MB</div>';
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='📂 Import File';}
}


// ══════════════════════════════════════════════════════════
document.addEventListener('click', e=>{
  const td = e.target.closest('td.ie-cell');
  if(!td) return;
  if(td.querySelector('.ie-input,.ie-select')) return;
  inlineEdit(td, +td.dataset.pid, td.dataset.field, td.dataset.val, td.dataset.type);
});


async function inlineEdit(td, productId, field, currentVal, type){
  if(td.querySelector('.ie-input,.ie-select')) return; // already editing
  const origHTML = td.innerHTML;
  const p = _productData.find(x=>x.id==productId);

  // ── toggle (combo) ──
  if(type==='toggle'){
    const newVal = (currentVal==='1'||currentVal===1||currentVal===true) ? 0 : 1;
    td.querySelector('.ie-val').innerHTML = newVal ? '<span class="badge badge-purple">Yes</span>' : '<span class="badge badge-gray">No</span>';
    td.dataset.val = String(newVal);
    try{
      await api.put(API.products,{id:productId,_bulk:true,[field]:newVal});
      if(p) p[field]=newVal;
    }catch(e){ toast(e.message,'error'); td.innerHTML=origHTML; }
    return;
  }

  // ── select (brand / category / vendor) ──
  if(type==='brand'||type==='category'||type==='vendor'){
    let options=[];
    if(type==='brand'){
      const r=await api.get(API.products+'?brands=1').catch(()=>({data:[]}));
      options=[{v:'',l:'— None —'},...r.data.map(b=>({v:b,l:b}))];
    } else if(type==='category'){
      const r=await api.get(API.categories).catch(()=>({data:[]}));
      options=[{v:'',l:'— None —'},...r.data.map(c=>({v:c.name,l:c.name}))];
    } else {
      const r=await api.get(API.vendors).catch(()=>({data:[]}));
      options=[{v:'',l:'— No Vendor —'},...r.data.map(v=>({v:String(v.id),l:v.name+(v.type?` (${v.type})`:'')}))];
    }
    const selVal = type==='vendor' ? String(currentVal||'') : (currentVal||'');
    td.innerHTML=`<select class="ie-select">
      ${options.map(o=>`<option value="${esc(String(o.v))}" ${String(o.v)===selVal?'selected':''}>${esc(o.l)}</option>`).join('')}
    </select>`;
    const sel=td.querySelector('select');
    sel.focus();
    let committed=false;
    const commit=async()=>{
      if(committed) return; committed=true;
      const val=sel.value;
      td.innerHTML=origHTML;
      try{
        const body={id:productId,_bulk:true};
        if(type==='vendor') body.vendor_id=val||null;
        else body[field]=val;
        await api.put(API.products,body);
        if(p){
          if(type==='vendor'){
            p.vendor_id=val;
            const found=options.find(o=>String(o.v)===val);
            p.vendor_name=found&&val?found.l.replace(/ \(.*\)$/,''):'';
          } else { p[field]=val; }
        }
        td.dataset.val=val;
        renderProductTable();
      }catch(e){ toast(e.message,'error'); td.innerHTML=origHTML; }
    };
    sel.onblur=commit;
    sel.onkeydown=e=>{ if(e.key==='Enter'){e.preventDefault();sel.blur();} if(e.key==='Escape'){committed=true;e.preventDefault();td.innerHTML=origHTML;} };
    return;
  }

  // ── text / number ──
  const isIntField = (field==='case_content'||field==='box_content'||field==='min_stock'||field==='stock');
  const ieStep = type==='number' ? (isIntField?'1':'0.01') : '';
  const iePh   = type==='number' ? (isIntField?'e.g. 12':'0.00') : '';
  td.innerHTML=`<input class="ie-input" type="${type==='number'?'number':'text'}"
    step="${ieStep}" min="${type==='number'?'0':''}"
    value="${esc(String(currentVal??''))}" placeholder="${iePh}">`;
  const inp=td.querySelector('input');
  inp.focus(); inp.select();

  let committed=false;
  const commit=async()=>{
    if(committed) return; committed=true;
    const val=inp.value.trim();
    td.innerHTML=origHTML;
    if(type==='number'){
      const num = val===''?null:parseFloat(val);
      if(num!==null&&isNaN(num)) return;
      try{
        await api.put(API.products,{id:productId,_bulk:true,[field]:num});
        if(p){
          p[field]=num;
          if(field==='cost'||field==='sell')
            p.margin=p.sell>0?+(((+p.sell-+p.cost)/+p.sell)*100).toFixed(1):0;
        }
        td.dataset.val=String(val);
        renderProductTable();
      }catch(e){ toast(e.message,'error'); }
    } else {
      if(!val&&field==='name'){ toast('Name cannot be empty','error'); return; }
      try{
        await api.put(API.products,{id:productId,_bulk:true,[field]:val});
        if(p){
          p[field]=val;
          if(field==='sku'){
            const nums=(val||'').replace(/\D/g,'');
            p.item_code=nums?parseInt(nums):null;
          }
        }
        td.dataset.val=val;
        renderProductTable();
      }catch(e){ toast(e.message,'error'); }
    }
  };
  inp.onblur=commit;
  inp.onkeydown=e=>{ if(e.key==='Enter'){e.preventDefault();inp.blur();} if(e.key==='Escape'){committed=true;e.preventDefault();td.innerHTML=origHTML;} };
}
// stub for old price-edit calls (no longer used but keep to avoid errors)
function startPriceEdit(td,id,field,val){ inlineEdit(td,id,field,String(val??''),'number'); }
function commitPriceEdit(){}
function cancelPriceEdit(){}

// ══════════════════════════════════════════════════════════
function openQuickAddProduct(targetSelectId, context){
  ['qp-name','qp-sku','qp-item-code','qp-brand','qp-cost','qp-sell','qp-wholesale-price',
   'qp-min-stock','qp-stock','qp-unit','qp-case-content','qp-box-content',
   'qp-landing-cost','qp-desc'].forEach(id=>{
    const el=document.getElementById(id); if(el) el.value='';
  });
  const catEl=document.getElementById('qp-category'); if(catEl) catEl.value='';
  const comboEl=document.getElementById('qp-combo'); if(comboEl) comboEl.value='0';
  // Clear SKU feedback and dropdown
  const fb=document.getElementById('qp-sku-feedback'); if(fb) fb.innerHTML='';
  const ac=document.getElementById('qp-sku-ac'); if(ac) ac.style.display='none';
  document.getElementById('qp-unit').value='Box';
  document.getElementById('qp-target-select').value=targetSelectId||'';
  document.getElementById('qp-target-select').dataset.context=context||'';
  populateVendorSelect('qp-vendor');
  loadCategories();
  openModal('modal-quick-product');
  setTimeout(()=>document.getElementById('qp-name').focus(),200);
}
function qpAutoItemCode(sku){
  const m=sku.match(/^(\d+)/);
  document.getElementById('qp-item-code').value = m ? m[1] : '';
  // Auto-fill product name from existing product with same SKU
  const nameEl = document.getElementById('qp-name');
  if(nameEl && !nameEl.value.trim() && sku.trim()){
    getProductsCache().then(function(products){
      const match = products.find(function(p){ return p.sku && p.sku.toLowerCase()===sku.toLowerCase(); });
      if(match && nameEl && !nameEl.value.trim()) nameEl.value = match.name;
    }).catch(function(){});
  }
}
async function saveQuickProduct(){
  const name=document.getElementById('qp-name').value.trim();
  const cost=document.getElementById('qp-cost').value;
  if(!name){toast('Product name is required','error');return;}
  if(!cost){toast('Cost price is required','error');return;}
  const btn=document.getElementById('qp-save-btn');
  btn.disabled=true;btn.textContent='Saving…';
  try{
    const body={
      name,
      sku:         document.getElementById('qp-sku').value.trim()||null,
      item_code:   document.getElementById('qp-item-code').value||null,
      brand:       document.getElementById('qp-brand').value.trim()||null,
      category:    document.getElementById('qp-category').value.trim()||null,
      vendor_id:   document.getElementById('qp-vendor').value||null,
      cost,
      list_price:  document.getElementById('qp-list-price').value||null,
      sell:        document.getElementById('qp-sell').value||null,
      wholesale_price: document.getElementById('qp-wholesale-price').value||null,
      landing_cost:document.getElementById('qp-landing-cost').value||null,
      unit:        document.getElementById('qp-unit').value.trim()||'Box',
      min_stock:   document.getElementById('qp-min-stock').value||0,
      stock:       document.getElementById('qp-stock').value||0,
      case_content:document.getElementById('qp-case-content').value||null,
      box_content: document.getElementById('qp-box-content').value||null,
      combo:       document.getElementById('qp-combo').value==='1'?1:0,
      description: document.getElementById('qp-desc').value.trim()||null,
    };
    const r=await api.post(API.products,body);
    const newId=r.data?.id;
    closeModal('modal-quick-product');
    clearAllSearchableSelects();
    toast(name+' added!','success');
    invalidateProductsCache();
    const targetEl=document.getElementById('qp-target-select');
    const targetId=targetEl.value;
    const context=targetEl.dataset.context||'';
    if(context==='po'){
      // PO context — add a new PO line and pre-select the new product
      addPOItem(newId);
    } else if(targetId){
      // Stock In or other select context
      await populateProductSelect(targetId);
      const sel=document.getElementById(targetId);
      if(sel&&newId) sel.value=newId;
      if(targetId==='si-product'&&cost){
        const costEl=document.getElementById('si-cost');
        if(costEl) costEl.value=parseFloat(cost);
      }
    }
    updateAlertBadge();
  }catch(e){toast(e.message,'error');}
  finally{btn.disabled=false;btn.innerHTML='💾 Save &amp; Select';}
}

function exportExcel(sheet){window.location.href=API.export+'?sheet='+sheet;}


// ── SKU live check: autocomplete + duplicate warning ─────────────────────────
let _skuAcTimer = null;
async function skuLiveCheck(inputEl, feedbackId){
  const val = inputEl.value.trim();
  const feedbackEl = document.getElementById(feedbackId);
  // Determine which dropdown el to use based on input id
  const acId = inputEl.id === 'p-sku' ? 'p-sku-ac' : 'qp-sku-ac';
  const acEl = document.getElementById(acId);

  // Clear state on empty
  if(!val){ 
    if(feedbackEl) feedbackEl.innerHTML='';
    if(acEl) acEl.style.display='none';
    inputEl.style.borderColor='';
    return;
  }

  clearTimeout(_skuAcTimer);
  _skuAcTimer = setTimeout(async function(){
    try{
      const products = await getProductsCache();
      const lower = val.toLowerCase();
      const editId = parseInt(document.getElementById('p-edit-id')?.value)||0;

      // Exact duplicate check (ignore current product when editing)
      // Get current vendor selection from the form (p-vendor for add/edit, qp-vendor for quick add)
      var curVendorId = String(document.getElementById('p-vendor')?.value || document.getElementById('qp-vendor')?.value || '');
      var exact = null;
      for(var _si=0;_si<products.length;_si++){
        var _cp=products[_si];
        if(_cp.sku && _cp.sku.toLowerCase()===lower && _cp.id!==editId){
          // Only flag as duplicate if same vendor, or no vendor selected yet
          var sameVendor = !curVendorId || !_cp.vendor_id || String(_cp.vendor_id)===curVendorId;
          if(sameVendor){ exact=_cp; break; }
        }
      }
      if(exact){
        inputEl.style.borderColor='var(--red)';
        if(feedbackEl) feedbackEl.innerHTML=
          '<span style="color:var(--red)">⛔ SKU already used by <strong>'+esc(exact.name)+'</strong>'
          +(exact.brand?' ('+esc(exact.brand)+')':'')+'</span>';
        if(acEl) acEl.style.display='none';
        return;
      }

      // Clear error state
      inputEl.style.borderColor='';
      if(feedbackEl) feedbackEl.innerHTML='';

      // Autocomplete — match SKUs that START WITH the typed value (positional, not substring)
      var matches = products.filter(function(p){ return p.sku && p.sku.toLowerCase().indexOf(lower)===0 && p.id!==editId; });
      if(!matches.length || !acEl){ if(acEl) acEl.style.display='none'; return; }

      var acHtml='';
      var acSlice=matches.slice(0,12);
      for(var _ai=0;_ai<acSlice.length;_ai++){
        var _p=acSlice[_ai];
        var _evtId = inputEl.id;
        var _sku   = esc(_p.sku||'');
        // Format: SKU - Product Name - Brand  e.g. '3058AJ - Magic Buzz - Ajanta'
        var _label = esc(_p.sku||'');
        if(_p.name)  _label += ' - ' + esc(_p.name);
        if(_p.brand) _label += ' - ' + esc(_p.brand);
        acHtml += '<div class="sku-ac-item" onmousedown="skuAcPick(event,&quot;'+_evtId+'&quot;,&quot;'+_sku+'&quot;)">'
                + '<span class="ac-sku-label">'+_label+'</span>'
                + '</div>';
      }
      acEl.innerHTML=acHtml;
      acEl.style.display='block';

      // Hide dropdown on outside click
      document.addEventListener('click', function hideAc(e){
        if(!acEl.contains(e.target) && e.target!==inputEl){
          acEl.style.display='none';
          document.removeEventListener('click', hideAc);
        }
      });
    }catch(e){}
  }, 200);
}


function skuAcPick(e, inputId, sku){
  e.preventDefault();
  // inputId/sku may have been passed through HTML &quot; encoding — decode
  inputId = String(inputId).replace(/&quot;/g,'"');
  sku     = String(sku).replace(/&quot;/g,'"');
  const inputEl = document.getElementById(inputId);
  if(!inputEl) return;
  inputEl.value = sku;
  // Trigger dependent updates
  if(inputId==='p-sku') autoExtractItemCode(sku);
  if(inputId==='qp-sku') qpAutoItemCode(sku);
  const acId = inputId==='p-sku' ? 'p-sku-ac' : 'qp-sku-ac';
  const acEl = document.getElementById(acId);
  if(acEl) acEl.style.display='none';
  // Warn if they pick an existing SKU (would be duplicate)
  skuLiveCheck(inputEl, inputId==='p-sku' ? 'p-sku-feedback' : 'qp-sku-feedback');
}


// ══════════════════════════════════════════════════════════
// SEARCHABLE SELECT COMPONENT
// Usage: makeSearchableSelect('select-id') — wraps the <select> with search
// The underlying <select> is kept in sync so existing form logic still works
// ══════════════════════════════════════════════════════════
var _ssInstances = {}; // id → {wrapper, display, dropdown, search, list, open}


function clearAllSearchableSelects(){
  Object.keys(_ssInstances).forEach(function(id){
    var inst = _ssInstances[id];
    if(!inst) return;
    if(inst.search) inst.search.value = '';
    if(inst.list)   inst.list.innerHTML = '';
    if(inst.dropdown) inst.dropdown.style.display = 'none';
  });
  // Clear transfer stock info when product is deselected
  var trProd = document.getElementById('tr-product');
  if(!trProd || !trProd.value){
    var info = document.getElementById('tr-stock-info');
    if(info) info.style.display = 'none';
  }
}

function makeSearchableSelect(selId, placeholder){
  var sel = document.getElementById(selId);
  if(!sel) return;
  if(sel.dataset.ssInit) { refreshSearchableSelect(selId); return; }
  sel.dataset.ssInit = '1';
  sel.style.display = 'none';
  placeholder = placeholder || sel.options[0]?.text || '— Select —';

  var wrapper = document.createElement('div');
  wrapper.className = 'ss-wrapper';
  sel.parentNode.insertBefore(wrapper, sel);
  wrapper.appendChild(sel);

  var display = document.createElement('div');
  display.className = 'ss-display';
  display.innerHTML = '<span class="ss-val"></span><span class="ss-arrow">▼</span>';
  wrapper.appendChild(display);

  var dropdown = document.createElement('div');
  dropdown.className = 'ss-dropdown';
  dropdown.style.display = 'none';
  dropdown.innerHTML = '<input class="ss-search" type="text" placeholder="🔍 Search…" autocomplete="off"><div class="ss-list"></div>';
  wrapper.appendChild(dropdown);

  var searchEl = dropdown.querySelector('.ss-search');
  var listEl   = dropdown.querySelector('.ss-list');
  var MAX_SHOW = 60; // only render this many options at a time

  function getOptions(){
    return Array.from(sel.options);
  }

  function renderList(q){
    var opts = getOptions();
    var lower = (q||'').toLowerCase();
    var html = '';
    var count = 0;
    for(var i=0;i<opts.length;i++){
      var o = opts[i];
      var text = o.text;
      var brand = o.dataset ? (o.dataset.brand||'') : '';
      var searchText = text + ' ' + brand;
      if(lower && searchText.toLowerCase().indexOf(lower) === -1) continue;
      count++;
      if(count > MAX_SHOW){ html += '<div class="ss-empty" style="color:var(--text3);font-size:.75rem">…'+(count-MAX_SHOW+1)+' more — type to narrow</div>'; break; }
      var cls = 'ss-opt' + (o.selected ? ' ss-selected' : '');
      var brand = o.dataset ? o.dataset.brand : '';
      var itemHtml = esc(text) + (brand ? ' <span style="color:var(--accent);font-size:.78rem;font-weight:600">'+esc(brand)+'</span>' : '');
      html += '<div class="'+cls+'" data-val="'+o.value.replace(/"/g,'&quot;')+'" data-idx="'+i+'">'+itemHtml+'</div>';
    }
    if(!count) html = '<div class="ss-empty">No results</div>';
    listEl.innerHTML = html;
  }

  function updateDisplay(){
    var idx = sel.selectedIndex;
    var sel_val = idx >= 0 ? sel.options[idx] : null;
    var txt = sel_val && sel_val.value ? sel_val.text : placeholder;
    display.querySelector('.ss-val').textContent = txt;
    display.querySelector('.ss-val').style.color = (sel_val && sel_val.value) ? '' : 'var(--text3)';
  }

  function openDropdown(){
    dropdown.style.display = 'block';
    searchEl.value = '';
    renderList('');
    searchEl.focus();
    setTimeout(function(){
      var sel_el = listEl.querySelector('.ss-selected');
      if(sel_el) sel_el.scrollIntoView({block:'nearest'});
    }, 30);
  }

  function closeDropdown(){
    dropdown.style.display = 'none';
    searchEl.value = '';
    listEl.innerHTML = '';
  }

  display.addEventListener('click', function(e){
    e.stopPropagation();
    dropdown.style.display==='none' ? openDropdown() : closeDropdown();
  });

  searchEl.addEventListener('input', function(){ renderList(this.value); });
  searchEl.addEventListener('keydown', function(e){ if(e.key==='Escape') closeDropdown(); });

  listEl.addEventListener('mousedown', function(e){
    var opt = e.target.closest('.ss-opt');
    if(!opt) return;
    e.preventDefault();
    var val = opt.dataset.val;
    var idx = parseInt(opt.dataset.idx);
    if(!isNaN(idx)) sel.selectedIndex = idx;
    else sel.value = val;
    sel.dispatchEvent(new Event('change', {bubbles:true}));
    updateDisplay();
    closeDropdown();
  });

  document.addEventListener('click', function(e){
    if(!wrapper.contains(e.target)) closeDropdown();
  });

  // Intercept programmatic .value assignments
  var _nativeValueSetter = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value').set;
  Object.defineProperty(sel, 'value', {
    set: function(v){ _nativeValueSetter.call(sel, v); updateDisplay(); },
    get: function(){ return sel.selectedIndex >= 0 ? (sel.options[sel.selectedIndex]?.value||'') : ''; },
    configurable: true
  });

  updateDisplay();
  _ssInstances[selId] = {wrapper:wrapper, display:display, dropdown:dropdown, search:searchEl, list:listEl};
}

function refreshSearchableSelect(selId){
  var inst = _ssInstances[selId];
  if(!inst) return;
  var sel = document.getElementById(selId);
  var display = inst.display;
  var placeholder = sel?.options[0]?.text || '— Select —';
  var sel_val = sel?.options[sel?.selectedIndex];
  var txt = sel_val ? (sel_val.value === '' ? placeholder : sel_val.text) : placeholder;
  display.querySelector('.ss-val').textContent = txt;
  display.querySelector('.ss-val').style.color = sel?.value ? '' : 'var(--text3)';
  // Also clear search if dropdown is closed
  if(inst.dropdown.style.display === 'none'){
    inst.search.value = '';
    inst.list.innerHTML = '';
  }
}


// ── Shared product select builder ─────────────────────────────────────────────
// Builds sorted option HTML and applies searchable select to any <select> element
function buildProductOptions(products, selectedId, placeholder, locationId){
  placeholder = placeholder || '— Select Product —';
  var sorted = products.slice().sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });
  return '<option value="">'+placeholder+'</option>'
    + sorted.map(function(p){
        // Show location-specific stock if locationId provided
        var stockQty = p.stock;
        if(locationId && p.location_stocks){
          var ls = p.location_stocks.find(function(l){ return String(l.location_id)===String(locationId); });
          if(ls) stockQty = ls.stock;
        }
        var brand = p.brand ? p.brand : '';
        var label = (p.sku ? esc(p.sku)+' - ' : '') + esc(p.name) + '  ('+stockQty+' '+esc(p.unit||'')+')';
        return '<option value="'+p.id+'" data-cost="'+p.cost+'" data-sell="'+(p.sell||0)+'" data-brand="'+esc(brand)+'"'+(String(p.id)===String(selectedId)?' selected':'')+'>'+label+'</option>';
      }).join('');
}

function populateProductSelectEl(el, products, selectedId, placeholder, locationId){
  if(!el) return;
  el.innerHTML = buildProductOptions(products, selectedId, placeholder, locationId);
  makeSearchableSelect(el.id, placeholder||'— Select Product —');
  refreshSearchableSelect(el.id);
}


// ══════════════════════════════════════════════════════════
// EXPENSES
// ══════════════════════════════════════════════════════════
// ── Expense column selector ────────────────────────────────
const EXP_COLS = [
  { key:'date',     label:'Date',     def:true  },
  { key:'category', label:'Category', def:true  },
  { key:'amount',   label:'Amount',   def:true  },
  { key:'vendor',   label:'Vendor',   def:true  },
  { key:'paid_via', label:'Paid Via', def:true  },
  { key:'paid_to',  label:'Paid To',  def:true  },
  { key:'business', label:'Business', def:true  },
  { key:'ref_no',   label:'Ref No.',  def:false },
  { key:'notes',    label:'Notes',    def:true  },
];
function getExpColPrefs(){
  try{ const s=localStorage.getItem('sm_exp_cols'); if(s) return JSON.parse(s); }catch{}
  const d={}; EXP_COLS.forEach(c=>d[c.key]=c.def); return d;
}
function saveExpColPrefs(p){ localStorage.setItem('sm_exp_cols',JSON.stringify(p)); }
function resetExpColPrefs(){ localStorage.removeItem('sm_exp_cols'); buildExpColToggles(); loadExpenses(); }
function expColVis(k){ return getExpColPrefs()[k]!==false; }

function buildExpColToggles(){
  const prefs=getExpColPrefs();
  const list=document.getElementById('exp-col-toggle-list');
  if(!list) return;
  list.innerHTML=EXP_COLS.map(c=>
    '<label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.83rem;white-space:nowrap">'
    +'<input type="checkbox" '+(prefs[c.key]!==false?'checked':'')+' onchange="onExpColToggle(\''+c.key+'\',this.checked)" style="accent-color:var(--accent)">'
    +c.label+'</label>'
  ).join('');
}
function onExpColToggle(key,checked){
  const prefs=getExpColPrefs(); prefs[key]=checked; saveExpColPrefs(prefs);
  loadExpenses();
}
function toggleExpColChooser(){
  const el=document.getElementById('exp-col-chooser');
  if(!el) return;
  const open = el.style.display==='none'||el.style.display==='';
  el.style.display = open ? 'block' : 'none';
  if(open) buildExpColToggles();
}

async function loadExpensesPage(){
  // Default dates left empty so ALL expenses show on first load
  // User can filter by date using the date pickers
  const fromEl = document.getElementById('exp-from');
  const toEl   = document.getElementById('exp-to');
  document.getElementById('exp-date').value = new Date().toISOString().split('T')[0];

  // Populate dropdowns in parallel
  await Promise.all([
    populateExpenseCategories(),
    populateVendorSelect('exp-vendor',null,false,true),
    populateVendorSelect('exp-filter-vendor',null,true,true),
    populatePayeeSelect('exp-filter-payee','All Paid Via'),
    populatePayeeSelect('exp-filter-paid-to','All Paid To'),
    populatePayeeSelect('exp-payee'),
    populatePayeeSelect('exp-paid-to','— Same as Paid Via —'),
    populateExpenseEntitySelect(),
  ]);
  await loadExpenseEntityTabs();
  setExpenseEntityTab(_expActiveEntityId); // init form to current tab
  loadExpenses();
}

let _expEntities = [];
let _expActiveEntityId = '';
let _expShowAll = false; // when true on RR tab, shows all businesses

async function populateExpenseEntitySelect(){
  try{
    const r = await api.get(API.expenseEntities);
    _expEntities = r.data||[];
    // No dropdown to populate — entity is set by the active tab via setExpenseEntityTab
  }catch(e){}
}

async function loadExpenseEntityTabs(){
  const wrap = document.getElementById('exp-entity-tabs');
  if(!wrap) return;
  if(!_expEntities.length){
    try{ const r = await api.get(API.expenseEntities); _expEntities = r.data||[]; }catch(e){ /* table may not exist yet */ }
  }
  window._expenseEntities = _expEntities; // cache for edit dropdown
  let html = '<button class="btn btn-sm '+(_expActiveEntityId===''?'btn-primary':'btn-outline')+'" onclick="setExpenseEntityTab(\'\')">RR Expenses</button>';
  _expEntities.forEach(function(en){
    const active = String(_expActiveEntityId)===String(en.id);
    html += '<button class="btn btn-sm '+(active?'btn-primary':'btn-outline')+'" onclick="setExpenseEntityTab(\''+en.id+'\')">Expenses — '+esc(en.name)+'</button>';
  });
  // Show "All Businesses" toggle only when on RR Expenses tab
  if(_expActiveEntityId===''){
    html += '<button class="btn btn-sm '+(_expShowAll?'btn-primary':'btn-outline')+'" onclick="toggleExpShowAll()" title="Show expenses from all businesses">All Businesses</button>';
  }
  html += '<button class="btn btn-ghost btn-sm" onclick="openExpenseEntityModal()" title="Manage businesses">⚙️</button>';
  wrap.innerHTML = html;
}

function setExpenseEntityTab(entId){
  _expActiveEntityId = entId;
  _expShowAll = false; // always reset when switching tabs
  // Sync the form — lock to this business or clear when on home tab
  const hiddenInput = document.getElementById('exp-entity');
  const contextRow  = document.getElementById('exp-entity-context-row');
  const contextLabel= document.getElementById('exp-entity-context-label');
  if(hiddenInput) hiddenInput.value = entId;
  if(entId && contextRow && contextLabel){
    const en = _expEntities.find(function(e){ return String(e.id)===String(entId); });
    contextLabel.textContent = en ? en.name : '';
    contextRow.style.display = '';
  } else if(contextRow){
    contextRow.style.display = 'none';
    if(hiddenInput) hiddenInput.value = '';
  }
  loadExpenseEntityTabs();
  loadExpenses();
}

function toggleExpShowAll(){
  _expShowAll = !_expShowAll;
  loadExpenseEntityTabs();
  loadExpenses();
}

function openExpenseEntityModal(){
  loadExpenseEntityList();
  document.getElementById('new-expense-entity-input').value='';
  openModal('modal-expense-entities');
}

async function loadExpenseEntityList(){
  const el = document.getElementById('expense-entity-list');
  if(!el) return;
  try{
    const r = await api.get(API.expenseEntities);
    _expEntities = r.data||[];
  }catch(e){}
  if(!_expEntities.length){
    el.innerHTML = '<div style="padding:14px;text-align:center;color:var(--text3);font-size:.82rem">No businesses added yet</div>';
    return;
  }
  el.innerHTML = _expEntities.map(function(en){
    return '<div class="expense-entity-row" style="display:flex;align-items:center;gap:8px;padding:7px 4px;border-bottom:1px solid var(--border)">'
      +'<span style="flex:1;font-size:.85rem">'+esc(en.name)+'</span>'
      +'<button class="btn btn-ghost btn-xs expense-entity-rename" data-id="'+en.id+'" data-name="'+esc(en.name)+'" title="Rename">✏️</button>'
      +(CAN_DELETE?'<button class="btn btn-danger btn-xs expense-entity-delete" data-id="'+en.id+'" data-name="'+esc(en.name)+'" title="Delete">🗑️</button>':'')
      +'</div>';
  }).join('');
  el.querySelectorAll('.expense-entity-rename').forEach(function(btn){
    btn.addEventListener('click', function(){ renameExpenseEntity(btn.dataset.id, btn.dataset.name); });
  });
  el.querySelectorAll('.expense-entity-delete').forEach(function(btn){
    btn.addEventListener('click', function(){ deleteExpenseEntity(btn.dataset.id, btn.dataset.name); });
  });
}

async function saveNewExpenseEntity(){
  const input = document.getElementById('new-expense-entity-input');
  const name = input.value.trim();
  if(!name){ toast('Enter a business name','error'); return; }
  try{
    await api.post(API.expenseEntities, {name});
    input.value='';
    await loadExpenseEntityList();
    await populateExpenseEntitySelect();
    await loadExpenseEntityTabs();
    toast('Business added');
  }catch(e){ toast(e.message,'error'); }
}

async function renameExpenseEntity(id, oldName){
  const newName = prompt('Rename business:', oldName);
  if(!newName || !newName.trim() || newName.trim()===oldName) return;
  try{
    await api.put(API.expenseEntities, {id:+id, name:newName.trim()});
    await loadExpenseEntityList();
    await populateExpenseEntitySelect();
    await loadExpenseEntityTabs();
    loadExpenses();
    toast('Business renamed');
  }catch(e){ toast(e.message,'error'); }
}

async function deleteExpenseEntity(id, name){
  if(!confirm('Delete business "'+name+'"? Expenses already tagged with it will keep showing it until reassigned.')) return;
  try{
    await api.delete(API.expenseEntities+'?id='+id);
    await loadExpenseEntityList();
    await populateExpenseEntitySelect();
    await loadExpenseEntityTabs();
    toast('Business deleted');
  }catch(e){ toast(e.message,'error'); }
}

async function populateExpenseCategories(){
  try{
    const r = await api.get(API.expenses+'?categories=1');
    const cats = r.data||[];
    ['exp-category','exp-filter-cat'].forEach(function(id){
      const sel = document.getElementById(id);
      if(!sel) return;
      const isFilter = id.indexOf('filter') > -1;
      const cur = sel.value;
      sel.innerHTML = (isFilter?'<option value="">All Categories</option>':'')
        + cats.map(function(c){ return '<option value="'+esc(c.name)+'">'+esc(c.name)+'</option>'; }).join('');
      if(cur) sel.value=cur;
    });
  }catch{}
}

let _lastExpenses = [];

async function loadExpenses(){
  const from   = document.getElementById('exp-from')?.value||'';
  const to     = document.getElementById('exp-to')?.value||'';
  const cat    = document.getElementById('exp-filter-cat')?.value||'';
  const vendor = document.getElementById('exp-filter-vendor')?.value||'';
  const payee  = document.getElementById('exp-filter-payee')?.value||'';
  const paidTo = document.getElementById('exp-filter-paid-to')?.value||'';
  const params = new URLSearchParams();
  if(from)   params.set('from',from);
  if(to)     params.set('to',to);
  if(cat)    params.set('category',cat);
  if(vendor) params.set('vendor_id',vendor);
  if(payee)  params.set('payee_id',payee);
  if(paidTo) params.set('paid_to_id',paidTo);
  if(_expActiveEntityId){
    params.set('entity_id',_expActiveEntityId);         // specific business
  } else if(_expShowAll){
    params.set('entity_id','all');                       // all businesses
  }
  // else: no entity_id param → API defaults to RR Expenses (entity_id IS NULL)
  try{
    const r = await api.get(API.expenses+'?'+params);
    const rows = r.data||[];
    _lastExpenses = rows;  // cache for export
    const tbody = document.getElementById('exp-body');
    const empty = document.getElementById('exp-empty');
    const totalLabel = document.getElementById('exp-total-label');
    if(!rows.length){
      tbody.innerHTML=''; empty.style.display='block';
      if(totalLabel) totalLabel.textContent='No expenses';
      return;
    }
    empty.style.display='none';
    const total = rows.reduce(function(s,r){ return s+(+r.amount); },0);
    if(totalLabel) totalLabel.textContent = rows.length+' entries — Total: '+CUR.sym+fmtN(total);
    tbody.innerHTML = rows.map(function(e){
      // Audited expenses are locked to admin-only edits/deletes — ticking
      // the checkbox is the point where "review complete" becomes
      // enforced, not just a display flag.
      const isAudited = !!(+e.audited);
      const canEdit = (ROLE==='admin'||ROLE==='partner'||ROLE==='manager') && (!isAudited || ROLE==='admin');
      const actions = canEdit
        ? '<button class="btn btn-ghost btn-xs" onclick="editExpense('+e.id+')">✏️</button> '
          +(CAN_DELETE?'<button class="btn btn-danger btn-xs" onclick="deleteExpense('+e.id+')">🗑️</button>':'')
        : (isAudited ? '<span title="Audited — locked, admin only" style="color:var(--text3)">🔒</span>' : '');
      const canToggleAudit = (ROLE==='admin'||ROLE==='partner'||ROLE==='manager') && (!isAudited || ROLE==='admin');
      const auditTitle = isAudited
        ? ('Audited by '+esc(e.audited_by||'—')+(e.audited_at?' · '+formatPickTimestamp(e.audited_at):''))
        : 'Tick to mark this expense as audited (locks it to admin-only edits)';
      const auditCell = '<td style="text-align:center" title="'+auditTitle+'"><input type="checkbox" '+(isAudited?'checked':'')+' '+(canToggleAudit?'':'disabled')+' onchange="toggleExpenseAudit('+e.id+',this.checked)" style="width:16px;height:16px;accent-color:var(--green);cursor:'+(canToggleAudit?'pointer':'not-allowed')+'"></td>';
      var cells = '<tr>';
      if(expColVis('date'))     cells += '<td class="mono" style="font-size:.8rem;white-space:nowrap">'+fmtExpDate(e.expense_date)+'</td>';
      if(expColVis('category')) cells += '<td><span class="badge badge-blue">'+esc(e.category)+'</span></td>';
      if(expColVis('amount'))   cells += '<td class="mono" style="color:var(--red);font-weight:600">'+CUR.sym+fmtN(+e.amount)+'</td>';
      if(expColVis('vendor'))   cells += '<td style="font-size:.82rem">'+esc(e.vendor_name||'—')+'</td>';
      if(expColVis('paid_via')){
        var pn=e.payee_name||'';
        var pt=e.payee_type||'';
        var sub=pt==='Cash'?'Cash':e.payee_bank?(esc(e.payee_bank)+(e.payee_account?' ****'+String(e.payee_account).slice(-4):'')):(pt==='UPI'&&e.payee_upi?esc(e.payee_upi):pt||'');
        cells += pn ? '<td style="font-size:.82rem">'+esc(pn)+(sub?'<br><span style="font-size:.7rem;color:var(--text3)">'+sub+'</span>':'')+'</td>' : '<td style="font-size:.82rem">—</td>';
      }
      if(expColVis('paid_to')){
        var ptn=e.paid_to_name||'';
        var ptt=e.paid_to_type||'';
        cells += ptn ? '<td style="font-size:.82rem">'+esc(ptn)+(ptt?'<br><span style="font-size:.7rem;color:var(--text3)">'+esc(ptt)+'</span>':'')+'</td>' : '<td style="font-size:.82rem;color:var(--text3)">—</td>';
      }
      if(expColVis('business')) cells += '<td style="font-size:.82rem">'+esc(e.entity_name||'—')+'</td>';
      if(expColVis('ref_no'))   cells += '<td style="font-size:.75rem;color:var(--text3)">'+esc(e.reference_no||'—')+'</td>';
      if(expColVis('notes'))    cells += '<td style="font-size:.78rem;color:var(--text2)">'+esc(e.notes||'—')+'</td>';
      cells += auditCell+'<td style="white-space:nowrap">'+actions+'</td></tr>';
      return cells;
    }).join('');

    // Rebuild header to match visible columns
    var hrow = '<tr>';
    if(expColVis('date'))     hrow += '<th style="white-space:nowrap">Date</th>';
    if(expColVis('category')) hrow += '<th>Category</th>';
    if(expColVis('amount'))   hrow += '<th>Amount ₹</th>';
    if(expColVis('vendor'))   hrow += '<th>Vendor</th>';
    if(expColVis('paid_via')) hrow += '<th>Paid Via</th>';
    if(expColVis('paid_to'))  hrow += '<th>Paid To</th>';
    if(expColVis('business')) hrow += '<th>Business</th>';
    if(expColVis('ref_no'))   hrow += '<th>Ref No.</th>';
    if(expColVis('notes'))    hrow += '<th>Notes</th>';
    hrow += '<th>Audited</th><th></th></tr>';
    var thead = document.getElementById('exp-thead');
    if(thead) thead.innerHTML = hrow;
  }catch(e){ toast(e.message,'error'); }
}

async function recordExpense(){
  const editId = document.getElementById('exp-edit-id').value;
  const date   = document.getElementById('exp-date').value;
  const cat    = document.getElementById('exp-category').value;
  const amount = document.getElementById('exp-amount').value;
  const payee  = document.getElementById('exp-payee').value;
  const paidTo = document.getElementById('exp-paid-to').value;
  const entId  = document.getElementById('exp-entity').value;
  if(!date||!cat||!amount){ toast('Date, category and amount are required','error'); return; }
  if(!payee){ toast('Paid Via is required','error'); return; }
  const body = {
    expense_date: date, category: cat, amount: +amount,
    vendor_id:    document.getElementById('exp-vendor').value||null,
    payee_id:     payee,
    paid_to_id:   paidTo||null,
    entity_id:    entId||null,
    reference_no: document.getElementById('exp-ref').value.trim(),
    notes:        document.getElementById('exp-notes').value.trim(),
  };
  try{
    if(editId){
      body.id = +editId;
      await api.put(API.expenses, body);
      toast('Expense updated!','success');
      cancelExpenseEdit();
    } else {
      await api.post(API.expenses, body);
      toast('Expense recorded!','success');
      ['exp-amount','exp-ref','exp-notes'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; }); var w=document.getElementById('exp-amount-words'); if(w) w.textContent='';
      document.getElementById('exp-payee').value='';
      document.getElementById('exp-paid-to').value='';
      setExpenseEntityTab(_expActiveEntityId); // re-lock to current tab
    }
    loadExpenses();
  }catch(e){ toast(e.message,'error'); }
}

function cancelExpenseEdit(){
  document.getElementById('exp-edit-id').value='';
  // Restore locked label, hide edit dropdown
  const sel=document.getElementById('exp-entity-select'); if(sel) sel.style.display='none';
  const lbl=document.getElementById('exp-entity-context-label'); if(lbl) lbl.style.display='';
  setElText('exp-form-title', '💸 Record Expense');
  setElText('exp-submit-btn', '💸 Record Expense');
  document.getElementById('exp-cancel-btn').style.display='none';
  ['exp-date','exp-amount','exp-ref','exp-notes'].forEach(function(id){ var el=document.getElementById(id); if(el) el.value=''; }); var w2=document.getElementById('exp-amount-words'); if(w2) w2.textContent='';
  document.getElementById('exp-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('exp-payee').value='';
  document.getElementById('exp-paid-to').value='';
  document.getElementById('exp-vendor').value='';
  setExpenseEntityTab(_expActiveEntityId); // re-lock to current tab
}

async function openExpenseCatModal(){
  await loadExpCatList();
  document.getElementById('new-exp-cat-input').value='';
  openModal('modal-exp-cats');
}

async function loadExpCatList(){
  try{
    const r=await api.get(API.expenses+'?categories=1');
    const cats=r.data||[];
    const el=document.getElementById('exp-cat-list');
    if(!cats.length){ el.innerHTML='<div style="color:var(--text3);font-size:.83rem;padding:8px">No categories yet</div>'; return; }
    el.innerHTML=cats.map(function(c){
      return '<div style="display:flex;align-items:center;gap:8px;padding:7px 4px;border-bottom:1px solid var(--border)">'
        +'<span style="flex:1;font-size:.85rem">'+esc(c.name)+'</span>'
        +'<button class="btn btn-ghost btn-xs" onclick="renameExpCat(\''+esc(c.name)+'\')" title="Rename">✏️</button>'
        +(CAN_DELETE?'<button class="btn btn-danger btn-xs" onclick="deleteExpCat(\''+esc(c.name)+'\')" title="Delete">🗑️</button>':'')
        +'</div>';
    }).join('');
  }catch(e){ toast(e.message,'error'); }
}

async function saveNewExpCat(){
  const name=document.getElementById('new-exp-cat-input').value.trim();
  if(!name){ toast('Enter a category name','error'); return; }
  try{
    await api.post(API.expenses+'?add_category=1',{category_name:name});
    document.getElementById('new-exp-cat-input').value='';
    await loadExpCatList();
    await populateExpenseCategories();
    document.getElementById('exp-category').value=name;
    toast('Category added');
  }catch(e){ toast(e.message,'error'); }
}

async function renameExpCat(oldName){
  const newName=prompt('Rename category:',oldName);
  if(!newName||newName.trim()===oldName) return;
  try{
    await api.post(API.expenses+'?rename_category=1',{old_name:oldName, new_name:newName.trim()});
    await loadExpCatList();
    await populateExpenseCategories();
    toast('Category renamed');
  }catch(e){ toast(e.message,'error'); }
}

async function deleteExpCat(name){
  if(!confirm('Delete category "'+name+'"? Existing expenses in this category will keep the name.')) return;
  try{
    await api.delete(API.expenses+'?category='+encodeURIComponent(name));
    await loadExpCatList();
    await populateExpenseCategories();
    toast('Category deleted');
  }catch(e){ toast(e.message,'error'); }
}

async function deleteExpense(id){
  if(!confirm('Delete this expense?')) return;
  try{
    await api.delete(API.expenses+'?id='+id);
    toast('Expense deleted');
    loadExpenses();
  }catch(e){ toast(e.message,'error'); }
}
async function toggleExpenseAudit(id, checked){
  try{
    await api.put(API.expenses,{id,audited:checked});
    toast(checked?'Marked audited':'Audit removed');
    loadExpenses();
  }catch(e){ toast(e.message,'error'); loadExpenses(); }
}

async function editExpense(id){
  try{
    const r = await api.get(API.expenses+'?single='+id);
    const e = r.data;
    document.getElementById('exp-edit-id').value    = e.id;
    document.getElementById('exp-date').value       = e.expense_date;
    document.getElementById('exp-amount').value     = e.amount;
    updateAmountWords('exp-amount','exp-amount-words');
    document.getElementById('exp-ref').value        = e.reference_no||'';
    document.getElementById('exp-notes').value      = e.notes||'';
    // Set category
    await populateExpenseCategories();
    document.getElementById('exp-category').value   = e.category;
    // Set vendor and payee
    await populateVendorSelect('exp-vendor', null, false, true);
    if(e.vendor_id) document.getElementById('exp-vendor').value = e.vendor_id;
    await populatePayeeSelect('exp-payee');
    if(e.payee_id) document.getElementById('exp-payee').value = e.payee_id;
    await populatePayeeSelect('exp-paid-to','— Same as Paid Via —');
    if(e.paid_to_id) document.getElementById('exp-paid-to').value = e.paid_to_id;
    // Populate and show business dropdown for edit mode
    if(!window._expenseEntities || !window._expenseEntities.length){
      try{ const er=await api.get(API.expenseEntities); window._expenseEntities=er.data||[]; }catch(e){}
    }
    const expEntities = window._expenseEntities || [];
    const sel = document.getElementById('exp-entity-select');
    const lbl = document.getElementById('exp-entity-context-label');
    const row = document.getElementById('exp-entity-context-row');
    if(sel){
      sel.innerHTML = '<option value="">🏠 RR Expenses</option>'
        + expEntities.map(function(en){ return '<option value="'+en.id+'">'+esc(en.name)+'</option>'; }).join('');
      sel.value = e.entity_id ? String(e.entity_id) : '';
      document.getElementById('exp-entity').value = e.entity_id ? String(e.entity_id) : '';
      sel.style.display = '';
      if(lbl) lbl.style.display = 'none';
      if(row) row.style.display = '';
    }
    // Update form to edit mode
    setElText('exp-form-title', '✏️ Edit Expense');
    setElText('exp-submit-btn', '💾 Save Changes');
    document.getElementById('exp-cancel-btn').style.display = '';
    // Scroll to form (works on both desktop and mobile stacked layout)
    const formTitle = document.getElementById('exp-form-title');
    if(formTitle) formTitle.scrollIntoView({behavior:'smooth', block:'start'});
    else window.scrollTo({top:0, behavior:'smooth'});
    toast('Editing expense — make changes and save','info');
  }catch(e){ toast(e.message,'error'); }
}

function exportExpenses(){
  const from = document.getElementById('exp-from')?.value||'';
  const to   = document.getElementById('exp-to')?.value||'';

  if(!_lastExpenses.length){ toast('No expenses to export','error'); return; }

  const headers = ['Date','Category','Amount','Vendor','Paid Via','Payee Type','Bank Name','Account No','UPI ID','Paid To','Paid To Type','Business','Ref No.','Notes'];
  const rows = _lastExpenses.map(function(e){
    return [
      fmtExpDate(e.expense_date)||'',
      e.category||'',
      Math.round(+e.amount||0),
      e.vendor_name||'',
      e.payee_name||'',
      e.payee_type||'',
      e.payee_bank||'',
      e.payee_account||'',
      e.payee_upi||'',
      e.paid_to_name||'',
      e.paid_to_type||'',
      e.entity_name||'',
      e.reference_no||'',
      e.notes||'',
    ];
  });

  const csv = rowsToCsv([headers,...rows]);
  const activeEnt = _expEntities.find(function(en){return String(en.id)===String(_expActiveEntityId);});
  const bizName = _expActiveEntityId ? (activeEnt?.name||'Business') : (_expShowAll ? 'All_Businesses' : 'RR_Expenses');
  const dateLabel = (from||'') + (to?'_to_'+to:'');
  downloadCsv(csv, 'Expenses_'+bizName+(dateLabel?'_'+dateLabel:'')+'.csv');
  toast('Exported '+rows.length+' expenses 📊');
}



// ── Product table column resize (runs once per header render) ─────────────────
function initProductColResize(){
  var table = document.getElementById('products-table');
  if(!table) return;
  table.querySelectorAll('th').forEach(function(th){
    // Remove existing resizer if re-rendering headers
    var old = th.querySelector('.th-resizer');
    if(old) old.remove();
    var resizer = document.createElement('div');
    resizer.className = 'th-resizer';
    th.appendChild(resizer);
    resizer.addEventListener('mousedown', function(e){
      e.preventDefault();
      e.stopPropagation();
      var startX = e.pageX;
      var startW = th.getBoundingClientRect().width;
      resizer.classList.add('active');
      document.body.style.cursor = 'col-resize';
      document.body.style.userSelect = 'none';
      function onMove(e){
        var w = Math.max(50, startW + e.pageX - startX);
        th.style.minWidth = w + 'px';
        th.style.maxWidth = w + 'px';
        th.style.width    = w + 'px';
      }
      function onUp(){
        resizer.classList.remove('active');
        document.body.style.cursor = '';
        document.body.style.userSelect = '';
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      }
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    });
  });
}




// ══════════════════════════════════════════════════════════
// CALCULATE COST FROM VENDOR LIST PRICE
// ══════════════════════════════════════════════════════════
let _vendorDataCache = {}; // vendorId -> {formula, case_margin}

async function getVendorData(vendorId){
  if(!vendorId) return {formula:[], case_margin:null};
  if(_vendorDataCache[vendorId]) return _vendorDataCache[vendorId];
  try{
    const r = await api.get(API.vendors+'?id='+vendorId);
    const data = {
      formula: JSON.parse(r.data.pricing_formula || '[]'),
      case_margin: (r.data.case_margin!==null && r.data.case_margin!==undefined && r.data.case_margin!=='') ? parseFloat(r.data.case_margin) : null
    };
    _vendorDataCache[vendorId] = data;
    return data;
  }catch{ return {formula:[], case_margin:null}; }
}

async function getVendorFormula(vendorId){
  const d = await getVendorData(vendorId);
  return d.formula;
}

// Auto-calc Landing Cost = Cost Price + (Case Margin / Case Content)
// i.e. (Cost Price * Case Content + Case Margin) / Case Content
// Case Margin: vendor-specific override if set, else the global default from Settings.
async function autoCalcLandingCost(costFieldId, caseContentFieldId, landingCostFieldId, vendorFieldId){
  const cost        = parseFloat(document.getElementById(costFieldId)?.value) || 0;
  const caseContent = parseFloat(document.getElementById(caseContentFieldId)?.value) || 0;
  if(!cost || !caseContent) return; // need both to distribute the case margin per unit
  const vendorId = vendorFieldId ? document.getElementById(vendorFieldId)?.value : null;
  const caseMargin = await getCaseMargin(vendorId);
  const landingEl = document.getElementById(landingCostFieldId);
  if(landingEl){
    const landing = (cost * caseContent + caseMargin) / caseContent;
    landingEl.value = Math.round(landing);
  }
}

// Resolve the Case Margin to use: the vendor's own override if set, otherwise
// the global default from Settings (fetched fresh, bypassing the _settings
// cache which is only populated at page load / on Save Settings).
async function getCaseMargin(vendorId){
  if(vendorId){
    const d = await getVendorData(vendorId);
    if(d.case_margin !== null && !isNaN(d.case_margin)) return d.case_margin;
  }
  try{
    const r = await api.get(API.settings);
    _settings = r.data; // refresh cache while we're at it
    return parseFloat(r.data.case_margin) || 0;
  }catch{ return parseFloat(_settings.case_margin) || 0; }
}


// Bulk-recalculate Landing Cost = (Cost * Case Content + Case Margin) / Case Content
// for every product that has a Case Content set. Uses the global Case Margin setting.
// Applies retroactively to existing products — run again after changing the Case Margin setting.
async function recalcAllLandingCosts(){
  const defaultMargin = await getCaseMargin();
  if(!confirm('Recalculate Landing Cost for ALL products with a Case Content set?\n\nFormula: (Cost \u00d7 Case Content + Case Margin) \u00f7 Case Content\n\nEach vendor\'s own Case Margin is used if set, otherwise the default ('+CUR.sym+fmtN(defaultMargin)+').\n\nThis will overwrite the current Landing Cost for those products.')) return;

  const candidates = _productData.filter(p=>p.case_content && +p.case_content>0);
  if(!candidates.length){ toast('No products have a Case Content set','info'); return; }

  let updated=0, unchanged=0;
  for(const p of candidates){
    const cost = +p.cost || 0;
    const caseContent = +p.case_content;
    const caseMargin = await getCaseMargin(p.vendor_id);
    const newLanding = Math.round((cost * caseContent + caseMargin) / caseContent);
    if(Math.abs(newLanding - (+p.landing_cost||0)) < 0.005){ unchanged++; continue; }
    try{
      await api.put(API.products,{id:p.id,_bulk:true,landing_cost:newLanding});
      p.landing_cost = newLanding;
      updated++;
    }catch{}
  }
  invalidateProductsCache();
  renderProductTable();

  toast('Recalculated landing cost for '+updated+' product(s)'+(unchanged?', '+unchanged+' already up to date':''), 'success');
}

// Recalculate cost for every PO line item that has a list price entered
function recalcAllPOItemCosts(){
  document.querySelectorAll('[id^="poi-listprice-"]').forEach(function(el){
    const i = el.id.replace('poi-listprice-','');
    if(el.value) autoCalcCostFromList('po-vendor','poi-listprice-'+i,'poi-cost-'+i);
  });
}

// Silent auto-calc — called on every list-price/vendor change, no toasts, no-ops if inputs missing
async function autoCalcCostFromList(vendorFieldId, listFieldId, costFieldId){
  const vendorId  = document.getElementById(vendorFieldId)?.value;
  const listEl    = document.getElementById(listFieldId);
  const listPrice = parseFloat(listEl?.value) || 0;
  if(!vendorId || !listPrice) return; // need both to calculate
  const formula = await getVendorFormula(vendorId);
  const result = computeFormula(listPrice, formula);
  const costEl = document.getElementById(costFieldId);
  if(costEl){
    costEl.value = Math.round(result.final);
    costEl.dispatchEvent(new Event('input', {bubbles:true}));
  }
}

// ══════════════════════════════════════════════════════════
// VENDOR PRICING FORMULA
// ══════════════════════════════════════════════════════════
const FORMULA_OPS = {
  discount_pct: {label:'Discount %',         calc:(v,x)=>v*(1-x/100)},
  add_pct:      {label:'Add % (tax/packing)',calc:(v,x)=>v*(1+x/100)},
  multiply:     {label:'Multiply by',        calc:(v,x)=>v*x},
  add_fixed:    {label:'Add Fixed ₹',        calc:(v,x)=>v+x},
};

function formulaStepRow(op, value){
  op = op || 'discount_pct';
  value = (value===undefined||value===null) ? '' : value;
  const opts = Object.keys(FORMULA_OPS).map(function(k){
    return '<option value="'+k+'"'+(k===op?' selected':'')+'>'+FORMULA_OPS[k].label+'</option>';
  }).join('');
  const row = document.createElement('div');
  row.className = 'formula-step';
  row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:6px';
  row.innerHTML = '<select class="form-control formula-op" style="flex:1.6" onchange="updateFormulaPreview()">'+opts+'</select>'
    + '<input type="number" class="form-control formula-val" step="0.01" placeholder="0" value="'+esc(String(value))+'" style="flex:1" oninput="updateFormulaPreview()">'
    + '<button type="button" class="btn btn-danger btn-xs" onclick="this.closest(\'.formula-step\').remove();updateFormulaPreview()">✕</button>';
  return row;
}

function addFormulaStep(op, value){
  const container = document.getElementById('v-formula-steps');
  if(!container) return;
  container.appendChild(formulaStepRow(op, value));
  updateFormulaPreview();
}

function getFormulaSteps(containerId){
  containerId = containerId || 'v-formula-steps';
  const container = document.getElementById(containerId);
  if(!container) return [];
  return Array.from(container.querySelectorAll('.formula-step')).map(function(row){
    return {
      op: row.querySelector('.formula-op').value,
      value: parseFloat(row.querySelector('.formula-val').value) || 0
    };
  });
}

function setFormulaSteps(steps, containerId){
  containerId = containerId || 'v-formula-steps';
  const container = document.getElementById(containerId);
  if(!container) return;
  container.innerHTML = '';
  (steps||[]).forEach(function(s){ container.appendChild(formulaStepRow(s.op, s.value)); });
  updateFormulaPreview();
}

// computeFormula: applies steps in order to a starting list price
function computeFormula(listPrice, steps){
  let current = parseFloat(listPrice) || 0;
  const breakdown = [];
  (steps||[]).forEach(function(s){
    const before = current;
    const fn = FORMULA_OPS[s.op] ? FORMULA_OPS[s.op].calc : null;
    if(fn) current = fn(current, s.value);
    breakdown.push({op:s.op, value:s.value, before:before, after:current, label:FORMULA_OPS[s.op]?FORMULA_OPS[s.op].label:s.op});
  });
  return {steps:breakdown, final:current};
}

function updateFormulaPreview(){
  const steps = getFormulaSteps('v-formula-steps');
  const preview = document.getElementById('v-formula-preview');
  if(!preview) return;
  if(!steps.length){ preview.innerHTML = ''; return; }
  const testInput = document.getElementById('v-test-list-price');
  const testVal = testInput ? parseFloat(testInput.value) : 0;
  const listPrice = testVal > 0 ? testVal : 100;
  const result = computeFormula(listPrice, steps);
  let html = (testVal>0 ? ('List price '+CUR.sym+fmtN(listPrice)+': ') : 'Example — List price ₹100: ');
  html += result.steps.map(function(s){
    return CUR.sym+fmtN(s.after)+' <span style="opacity:.6">('+s.label+(s.value?' '+s.value:'')+(s.op.indexOf('pct')>-1?'%':'')+')</span>';
  }).join(' → ');
  preview.innerHTML = html + '<br><strong style="color:var(--accent)">Final Cost: '+CUR.sym+fmtN(result.final)+'</strong>';
}


// ══════════════════════════════════════════════════════════
// THEMES
// ══════════════════════════════════════════════════════════
const THEMES = {
  midnight: {label:'Midnight Blue', accent:'#4f8eff', accent2:'#7c3aed'},
  emerald:  {label:'Emerald Night', accent:'#22c55e', accent2:'#10b981'},
  crimson:  {label:'Crimson Dusk',  accent:'#f43f5e', accent2:'#fb7185'},
  amber:    {label:'Amber Glow',    accent:'#f59e0b', accent2:'#fb923c'},
  violet:   {label:'Royal Violet',  accent:'#a855f7', accent2:'#d946ef'},
  teal:     {label:'Ocean Teal',    accent:'#06b6d4', accent2:'#22d3ee'},
};
let _currentTheme = document.documentElement.dataset.theme || 'midnight';

function applyTheme(key){
  if(!THEMES[key]) return;
  document.documentElement.dataset.theme = key;
  _currentTheme = key;
  document.querySelectorAll('.theme-swatch').forEach(function(el){
    el.classList.toggle('active', el.dataset.theme===key);
  });
}

async function saveTheme(key){
  if(!THEMES[key]) return;
  applyTheme(key);
  try{
    await api.put(API.auth+'?action=set_theme', {theme:key});
    toast('Theme set to '+THEMES[key].label);
  }catch(e){ toast(e.message,'error'); }
}

function themeSwatchesHTML(){
  return Object.entries(THEMES).map(function([key,t]){
    const active = key===_currentTheme ? ' active' : '';
    return '<div class="theme-swatch'+active+'" data-theme="'+key+'" onclick="saveTheme(\''+key+'\')" title="'+esc(t.label)+'">'
      + '<div class="theme-swatch-dot" style="background:linear-gradient(135deg,'+t.accent+','+t.accent2+')"></div>'
      + '<span>'+esc(t.label)+'</span>'
      + '</div>';
  }).join('');
}

document.addEventListener('click', function(e){
  const menu = document.getElementById('theme-quick-menu');
  const btn  = document.getElementById('theme-quick-btn');
  if(menu && menu.style.display!=='none' && !menu.contains(e.target) && e.target!==btn && !btn?.contains(e.target)){
    menu.style.display='none';
  }
});

// ══════════════════════════════════════════════════════════
// FONTS
// ══════════════════════════════════════════════════════════
const FONTS = {
  inter:   {label:'Inter',           sans:"'Inter',sans-serif",           mono:"'JetBrains Mono',monospace"},
  outfit:  {label:'Outfit (classic)',sans:"'Outfit',sans-serif",          mono:"'DM Mono',monospace"},
  jakarta: {label:'Plus Jakarta Sans',sans:"'Plus Jakarta Sans',sans-serif",mono:"'IBM Plex Mono',monospace"},
  manrope: {label:'Manrope',         sans:"'Manrope',sans-serif",         mono:"'Space Mono',monospace"},
  lexend:  {label:'Lexend',          sans:"'Lexend',sans-serif",          mono:"'Roboto Mono',monospace"},
};
let _currentFont = document.documentElement.dataset.font || 'inter';

function applyFont(key){
  if(!FONTS[key]) return;
  document.documentElement.dataset.font = key;
  _currentFont = key;
  document.querySelectorAll('.font-swatch').forEach(function(el){
    el.classList.toggle('active', el.dataset.font===key);
  });
}

async function saveFont(key){
  if(!FONTS[key]) return;
  applyFont(key);
  try{
    await api.put(API.auth+'?action=set_font', {font:key});
    toast('Font set to '+FONTS[key].label);
  }catch(e){ toast(e.message,'error'); }
}

function fontSwatchesHTML(){
  return Object.entries(FONTS).map(function([key,f]){
    const active = key===_currentFont ? ' active' : '';
    return '<div class="font-swatch theme-swatch'+active+'" data-font="'+key+'" onclick="saveFont(\''+key+'\')" title="'+esc(f.label)+'">'
      + '<div class="font-swatch-preview" style="font-family:'+f.sans+'">Aa</div>'
      + '<span>'+esc(f.label)+'</span>'
      + '</div>';
  }).join('');
}

// Override toggleThemeMenu to show both theme + font swatches in the quick menu
function toggleThemeMenu(){
  const menu = document.getElementById('theme-quick-menu');
  if(!menu) return;
  const open = menu.style.display!=='none';
  menu.style.display = open?'none':'block';
  if(!open){
    menu.innerHTML = '<div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;padding:4px 10px 2px">Theme</div>'
      + themeSwatchesHTML()
      + '<div style="font-size:.68rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;font-weight:600;padding:8px 10px 2px;border-top:1px solid var(--border);margin-top:6px">Font</div>'
      + fontSwatchesHTML();
  }
}

// ══════════════════════════════════════════════════════════
// KEYBOARD SHORTCUTS
// ══════════════════════════════════════════════════════════
document.addEventListener('keydown',e=>{
  // Skip if typing in an input
  if(e.target.tagName==='INPUT'||e.target.tagName==='TEXTAREA'||e.target.tagName==='SELECT')return;
  if(e.key==='Escape'){document.querySelectorAll('.modal-backdrop.open').forEach(m=>m.classList.remove('open'));return;}
  const map={d:'dashboard',n:()=>openProductModal(),i:()=>{showPage('invoices');openInvoiceModal();},s:'stock-in',r:'reports',a:'alerts','?':()=>openModal('modal-shortcuts')};
  const action=map[e.key.toLowerCase()];
  if(action){e.preventDefault();typeof action==='function'?action():showPage(action);}
});

// ══════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded',async()=>{
  if(typeof lucide!=='undefined') lucide.createIcons();
  const _initSettings = await getSettings();
  const _tagline = document.querySelector('.logo-sub');
  if(_tagline && _initSettings.sidebar_tagline) _tagline.textContent = _initSettings.sidebar_tagline;
  const t=today();
  ['si-date','so-date','tr-date','adj-date'].forEach(id=>{const el=document.getElementById(id);if(el)el.value=t;});
  await loadGlobalLocationSelector();
  // Restore page from URL hash or sessionStorage on refresh
  const _hashPage = (window.location.hash||'').replace('#','').trim();
  const _storedPage = sessionStorage.getItem('invyrr_page')||'';
  const _targetPage = _hashPage || _storedPage;
  const _validPages = Object.keys(pageTitles);
  if(_targetPage && _validPages.includes(_targetPage)){
    showPage(_targetPage);
  } else {
    loadDashboard();
  }
  updateAlertBadge();
});
// ══════════════════════════════════════════════════════════
// BACKUP
// ══════════════════════════════════════════════════════════
function downloadSQL(){
  window.location.href='api/backup.php?action=sql';
}
function downloadExcel(){
  window.location.href='api/export.php?sheet=all';
}
async function loadBackupHistory(){
  try{
    const r=await api.get('api/backup.php?action=history');
    const tbody=document.getElementById('backup-history-body');
    const empty=document.getElementById('backup-history-empty');
    if(!r.data||!r.data.length){tbody.innerHTML='';empty.style.display='block';return;}
    empty.style.display='none';
    tbody.innerHTML=r.data.map(b=>{
      const typeIcon={sql:'🗄️',excel:'📊',drive:'☁️'}[b.type]||'📄';
      const typeBadge={sql:'badge-blue',excel:'badge-green',drive:'badge-purple'}[b.type]||'badge-gray';
      return `<tr>
        <td class="mono" style="font-size:.8rem">${b.created_at}</td>
        <td><span class="badge ${typeBadge}">${typeIcon} ${b.type.toUpperCase()}</span></td>
        <td style="font-size:.8rem;color:var(--text2)">${esc(b.filename)}</td>
        <td class="mono" style="color:var(--text3);font-size:.78rem">${b.size_human}</td>
        <td style="color:var(--text2);font-size:.82rem">${esc(b.created_by||'—')}</td>
        <td>
          ${b.type!=='drive'?`<a href="api/backup.php?action=download&file=${encodeURIComponent(b.filename)}" class="btn btn-ghost btn-xs">📥</a>`:''}
          ${CAN_DELETE?`<button class="btn btn-danger btn-xs" onclick="deleteBackup('${esc(b.filename)}')">🗑️</button>`:""}
        </td>
      </tr>`;
    }).join('');
  }catch(e){toast(e.message,'error');}
}
// ══════════════════════════════════════════════════════════
// GOOGLE DRIVE BACKUP — uses Google Identity Services (GIS)
// No software needed. Signs in via browser popup. Uploads SQL
// dump directly to "Invyrr_db_backup" folder in user's Drive.
// ══════════════════════════════════════════════════════════
let _driveToken = null;
let _driveTokenExpiry = 0;
let _tokenClient = null;
let _driveBackupPending = false;

function getDriveClientId(){
  // Read from global, or fall back to the input field value directly
  return (window._GOOGLE_CLIENT_ID || document.getElementById('s-google-client-id')?.value || '').trim();
}

// Called once when GIS library loads
function initGIS(){
  const clientId = getDriveClientId();
  if(!clientId) return;
  _tokenClient = google.accounts.oauth2.initTokenClient({
    client_id: clientId,
    scope: 'https://www.googleapis.com/auth/drive.file',
    callback: function(resp){
      if(resp.error){ driveSetStatus('❌ Google sign-in failed: '+resp.error, 'error'); return; }
      _driveToken = resp.access_token;
      _driveTokenExpiry = Date.now() + (resp.expires_in - 60) * 1000;
      showDriveSignedIn();
      if(_driveBackupPending === 'full') executeFullBackup();
      else if(_driveBackupPending === 'sql') executeDriveBackup();
      _driveBackupPending = false;
    }
  });
}
// GIS calls this after the script loads
window.onGISLoad = initGIS;

function showDriveSignedIn(){
  const row = document.getElementById('drive-auth-row');
  if(row) row.style.display = 'block';
}
// ── Auto Backup Scheduler ─────────────────────────────────
// Schedule stored in localStorage — persists across sessions in same browser.
// Checks on page load and every hour whether a backup is due.
const AUTO_BACKUP_KEY = 'invyrr_auto_backup';

function saveAutoBackupSchedule(){
  const freq = document.getElementById('auto-backup-freq')?.value || 'off';
  const config = { freq, savedAt: new Date().toISOString(), lastBackup: getAutoBackupConfig().lastBackup || null };
  localStorage.setItem(AUTO_BACKUP_KEY, JSON.stringify(config));
  updateAutoBackupStatus();
  toast(freq === 'off' ? 'Auto backup disabled' : 'Auto backup set to: ' + freq);
}

function getAutoBackupConfig(){
  try{ return JSON.parse(localStorage.getItem(AUTO_BACKUP_KEY) || '{}'); }
  catch(e){ return {}; }
}

function updateAutoBackupStatus(){
  const el  = document.getElementById('auto-backup-status');
  const sel = document.getElementById('auto-backup-freq');
  const cfg = getAutoBackupConfig();
  if(sel && cfg.freq) sel.value = cfg.freq || 'off';
  if(!el) return;
  if(!cfg.freq || cfg.freq === 'off'){ el.innerHTML = ''; return; }
  const now = new Date();
  let next = new Date();
  if(cfg.freq === 'daily')        { next.setDate(now.getDate()+1); next.setHours(2,0,0,0); }
  else if(cfg.freq === 'weekly')  { const d=(7-now.getDay())%7||7; next.setDate(now.getDate()+d); next.setHours(2,0,0,0); }
  else if(cfg.freq === 'monthly') { next = new Date(now.getFullYear(), now.getMonth()+1, 1, 2, 0, 0); }
  const nextStr = next.toLocaleDateString('en-IN',{weekday:'short',day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
  const lastStr = cfg.lastBackup ? 'Last: '+new Date(cfg.lastBackup).toLocaleString('en-IN')+' &nbsp;·&nbsp; ' : '';
  el.innerHTML = lastStr + '<span style="color:var(--accent)">Next: '+nextStr+'</span>';
}

function isBackupDue(cfg){
  if(!cfg.freq || cfg.freq === 'off') return false;
  if(!cfg.lastBackup) return true;
  const hrs = (Date.now() - new Date(cfg.lastBackup)) / 3600000;
  return (cfg.freq==='daily' && hrs>=24) || (cfg.freq==='weekly' && hrs>=168) || (cfg.freq==='monthly' && hrs>=720);
}

async function checkAndRunAutoBackup(){
  const cfg = getAutoBackupConfig();
  if(!isBackupDue(cfg) || !getDriveClientId()) return;
  if(!driveTokenValid()){ console.log('[Invyrr] Auto backup due but not signed in to Drive — skipping'); return; }
  console.log('[Invyrr] Running auto full backup:', cfg.freq);
  try{
    await executeFullBackup();  // Full backup: SQL + all CSVs
    cfg.lastBackup = new Date().toISOString();
    localStorage.setItem(AUTO_BACKUP_KEY, JSON.stringify(cfg));
    updateAutoBackupStatus();
  } catch(e){ console.warn('[Invyrr] Auto backup failed:', e.message); }
}

// Check 5 seconds after login and every hour thereafter
setTimeout(function(){ updateAutoBackupStatus(); checkAndRunAutoBackup(); }, 5000);
setInterval(checkAndRunAutoBackup, 60*60*1000);

function driveSignOut(){
  _driveToken = null; _driveTokenExpiry = 0;
  const row = document.getElementById('drive-auth-row');
  if(row) row.style.display = 'none';
  toast('Signed out of Google Drive');
}
function driveTokenValid(){
  return _driveToken && Date.now() < _driveTokenExpiry;
}

async function backupToDrive(){
  const clientId = getDriveClientId();
  if(!clientId){
    driveSetStatus('❌ Google Client ID not set. Go to Settings → Backup → 🔑 Google Drive Setup first.','error');
    return;
  }
  if(!_tokenClient){ initGIS(); }
  if(!driveTokenValid()){
    _driveBackupPending = 'sql';
    _tokenClient.requestAccessToken({prompt:'consent'});
    return;
  }
  _driveBackupPending = false;
  await executeDriveBackup();
}

// ── Full Backup (SQL + all CSVs as ZIP) ───────────────────
async function fullBackupToDrive(){
  const clientId = getDriveClientId();
  if(!clientId){
    driveSetStatus('❌ Google Client ID not set. Go to Settings → Backup → 🔑 Google Drive Setup first.','error');
    return;
  }
  if(!_tokenClient){ initGIS(); }
  if(!driveTokenValid()){
    _driveBackupPending = 'full';
    _tokenClient.requestAccessToken({prompt:'consent'});
    return;
  }
  _driveBackupPending = false;
  await executeFullBackup();
}

async function executeFullBackup(){
  const btn = document.getElementById('drive-full-btn');
  if(btn){ btn.disabled=true; btn.innerHTML='<span class="spinner"></span> Building backup…'; }
  driveSetStatus('⏳ Generating full backup (DB + CSVs)…','info');
  try{
    // Step 1: get ZIP from server
    const r = await api.post('api/backup.php?action=full_dump', {});
    if(!r.success) throw new Error(r.message || 'Backup generation failed');
    const {zip_b64, filename} = r.data;

    driveSetStatus('⏳ Uploading ZIP to Google Drive…','info');

    // Step 2: decode base64 → binary → Blob
    const binary = atob(zip_b64);
    const bytes  = new Uint8Array(binary.length);
    for(let i=0;i<binary.length;i++) bytes[i]=binary.charCodeAt(i);
    const blob = new Blob([bytes], {type:'application/zip'});

    // Step 3: find or create folder
    const folderId = await driveGetOrCreateFolder('Invyrr_db_backup');

    // Step 4: upload
    const meta = JSON.stringify({name: filename, parents: [folderId]});
    const form = new FormData();
    form.append('metadata', new Blob([meta], {type:'application/json'}));
    form.append('file', blob);

    const up = await fetch('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink',{
      method:'POST',
      headers:{Authorization:'Bearer '+_driveToken},
      body:form
    });
    if(!up.ok) throw new Error('Drive upload failed: '+(await up.text()));
    const file = await up.json();

    driveSetStatus('✅ Full backup saved: <strong>'+esc(file.name)+'</strong> &nbsp;<a href="'+file.webViewLink+'" target="_blank" style="color:var(--accent)">Open in Drive ↗</a>','success');
    showDriveSignedIn();
    loadBackupHistory();
  }catch(e){
    driveSetStatus('❌ '+esc(e.message),'error');
  }finally{
    if(btn){ btn.disabled=false; btn.innerHTML='☁️ Full Backup to Drive'; }
  }
}


async function executeDriveBackup(){
  const btn = document.getElementById('drive-backup-btn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Generating backup…';
  driveSetStatus('⏳ Fetching database dump from server…', 'info');
  try{
    // Step 1: get SQL dump from server
    const r = await api.post('api/backup.php?action=sql_dump', {});
    if(!r.success) throw new Error(r.message || 'Backup failed');
    const sql = r.data.sql;
    const filename = r.data.filename || ('Invyrr_Backup_' + new Date().toISOString().slice(0,19).replace(/:/g,'-') + '.sql');

    driveSetStatus('⏳ Uploading to Google Drive…', 'info');

    // Step 2: find or create "Invyrr Backups" folder
    const folderId = await driveGetOrCreateFolder('Invyrr_db_backup');

    // Step 3: upload SQL file
    const blob = new Blob([sql], {type: 'text/plain'});
    const meta = JSON.stringify({name: filename, parents: [folderId]});
    const form = new FormData();
    form.append('metadata', new Blob([meta], {type:'application/json'}));
    form.append('file', blob);

    const up = await fetch('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink', {
      method: 'POST',
      headers: { Authorization: 'Bearer ' + _driveToken },
      body: form
    });
    if(!up.ok) throw new Error('Drive upload failed: ' + await up.text());
    const file = await up.json();

    driveSetStatus('✅ Backup saved: <strong>' + esc(file.name) + '</strong> &nbsp;<a href="' + file.webViewLink + '" target="_blank" style="color:var(--accent)">Open in Drive ↗</a>', 'success');
    showDriveSignedIn();
    loadBackupHistory();
  } catch(e){
    driveSetStatus('❌ ' + esc(e.message), 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '☁️ Backup to Google Drive';
    _driveBackupPending = false;
  }
}

async function driveGetOrCreateFolder(name){
  // Search for existing folder
  const q = encodeURIComponent(`name='${name}' and mimeType='application/vnd.google-apps.folder' and trashed=false`);
  const res = await fetch(`https://www.googleapis.com/drive/v3/files?q=${q}&fields=files(id)`, {
    headers: { Authorization: 'Bearer ' + _driveToken }
  });
  const data = await res.json();
  if(data.files && data.files.length > 0) return data.files[0].id;
  // Create folder
  const cr = await fetch('https://www.googleapis.com/drive/v3/files', {
    method: 'POST',
    headers: { Authorization: 'Bearer ' + _driveToken, 'Content-Type': 'application/json' },
    body: JSON.stringify({ name: name, mimeType: 'application/vnd.google-apps.folder' })
  });
  const folder = await cr.json();
  return folder.id;
}

function driveSetStatus(html, type){
  const el = document.getElementById('drive-status');
  if(!el) return;
  el.style.display = 'block';
  const styles = {
    success: ['rgba(34,197,94,.1)','var(--green)','1px solid rgba(34,197,94,.2)'],
    error:   ['rgba(239,68,68,.1)','var(--red)',  '1px solid rgba(239,68,68,.2)'],
    info:    ['rgba(79,142,255,.1)','var(--accent)','1px solid rgba(79,142,255,.2)'],
  };
  const [bg, color, border] = styles[type] || styles.info;
  el.style.background = bg; el.style.color = color; el.style.border = border;
  el.innerHTML = html;
}

async function saveDriveClientId(){
  const id = document.getElementById('s-google-client-id')?.value?.trim();
  if(!id){ toast('Please enter a Client ID','error'); return; }
  try{
    await api.put(API.settings, {google_client_id: id});
    window._GOOGLE_CLIENT_ID = id;
    _settings = {}; // clear settings cache so getSettings() re-fetches
    const st = document.getElementById('drive-client-id-status');
    if(st){ st.style.display='block'; setTimeout(()=>st.style.display='none', 3000); }
    initGIS();
    toast('Client ID saved ✅');
  }catch(e){
    toast('Failed to save: '+e.message,'error');
  }
}
async function deleteBackup(filename){
  if(!confirm('Delete this backup file?'))return;
  try{await api.delete('api/backup.php?action=delete&file='+encodeURIComponent(filename));toast('Backup deleted');loadBackupHistory();}
  catch(e){toast(e.message,'error');}
}
function handleRestoreDrop(e){
  e.preventDefault();document.getElementById('restore-drop').style.borderColor='var(--border2)';
  const f=e.dataTransfer.files[0];if(f)restoreFromSQL(f);
}
async function restoreFromSQL(file){
  if(!file||!file.name.endsWith('.sql')){toast('Please select a .sql file','error');return;}
  if(!confirm('⚠️ This will OVERWRITE all current data. Are you sure?'))return;
  const status=document.getElementById('restore-status');
  status.style.display='block';status.style.background='rgba(234,179,8,.1)';status.style.color='var(--yellow)';status.style.border='1px solid rgba(234,179,8,.2)';
  status.innerHTML='⏳ Restoring…';
  try{
    const fd=new FormData();fd.append('sql_file',file);
    const resp=await fetch('api/backup.php?action=restore',{method:'POST',body:fd,credentials:'same-origin'});
    const j=await resp.json();
    if(!j.success)throw new Error(j.message);
    status.style.background='rgba(34,197,94,.1)';status.style.color='var(--green)';status.style.border='1px solid rgba(34,197,94,.2)';
    status.innerHTML='✅ '+esc(j.message);
    toast('Restore complete — reloading…');
    setTimeout(()=>location.reload(),2000);
  }catch(e){
    status.style.background='rgba(239,68,68,.1)';status.style.color='var(--red)';status.style.border='1px solid rgba(239,68,68,.2)';
    status.innerHTML='❌ '+esc(e.message);
  }
}

/* ======================================================
   ORDER PICKING MODULE — full version with substitute
   ====================================================== */
const PICK_KEY      = 'invyrr_picking_v2';    // current active session
const PICK_LIST_KEY = 'invyrr_picking_list';  // all estimates for today
let _pickItems    = [];
let _pickFilter   = 'all';
let _pickOrderNo  = '';
let _pickCustomer = '';
let _pickLocationName = '';
let _pickLocationId = '';
let _pickSubIdx   = -1;   // index of the item currently showing the substitute picker (-1 = none)
let _pickSubCandidates = []; // candidate products for the open substitute picker
let _pickSubLoading = false;
let _pickVerifyModeOn = false; // true while the '✓✓ Verify' banner/tap-to-verify mode is active
let _dispatchOrderId = null; // id of the order currently in the Dispatch-details modal
let _pickDashStatusFilter = ''; // '' = All; otherwise one of the SM keys in renderPickDashboard()
let _pickEstimates = []; // [{id, orderNo, customer, phone, items, ts}]
let _pickActiveId  = null;
let _pickServerOk  = false; // true when server sync is working
let _pickFetchToken = 0; // race-guard: only the most recently issued picking-sessions fetch may apply its result

// Bulk-loads website_orders into the shared _woAllRows cache (same global
// the Customer Orders page uses) so Picking can look up an estimate's
// payment status by order number without a per-row fetch — used to flag
// overpayments both on the dashboard and on an open order's summary line.
async function refreshWoCacheForPicking(){
  try{
    const r=await api.get(API.websiteOrders);
    if(Array.isArray(r.data)) _woAllRows=r.data;
  }catch(e){ /* non-critical — badges just won't show until this succeeds */ }
}
function findWoRowForOrder(orderNo){
  if(!orderNo || !Array.isArray(_woAllRows)) return null;
  return _woAllRows.find(function(o){return o.order_number===orderNo;})||null;
}

async function initPickingPage(){
  const _myToken=++_pickFetchToken;
  try{_pickEstimates=JSON.parse(localStorage.getItem(PICK_LIST_KEY)||'[]');}catch(e){_pickEstimates=[];}
  showPickDashboard();
  populatePickDashLocationFilter();
  // Kick this off in parallel, but DON'T let its completion trigger its
  // own render — if it resolves before the picking_sessions fetch below
  // (quite possible, it's the smaller request), that render would run
  // against the raw localStorage cache from the line above instead of
  // server truth, showing a stale/incomplete list. Awaited once at the
  // end instead, after _pickEstimates is authoritative either way.
  const woCachePromise=refreshWoCacheForPicking();
  try{
    const r=await api.get(API.pickingSessions);
    if(_myToken!==_pickFetchToken) return; // a newer fetch (nav-away-and-back, manual refresh, etc.) superseded this one
    if(Array.isArray(r.data)){
      // Trust the server completely once we've successfully reached it.
      // Earlier versions merged in anything from the local cache that the
      // server didn't have (guessing it just 'hadn't synced yet') and even
      // re-POSTed it back — so a delete by one user kept getting silently
      // undone the next time any OTHER browser with a stale cached copy
      // loaded this page. A time-window heuristic (only resurrect recent
      // entries) still broke if that other browser had touched the order
      // recently for any reason. Simplest correct rule: once the server
      // answers, its list IS the list — no merging, no re-posting.
      _pickEstimates=r.data.map(row=>({id:row.id,orderNo:row.order_no,customer:row.customer,
        phone:row.phone,address:row.address||'',picker:row.picker,status:row.status||'pending',
        verified:!!row.verified,verifiedBy:row.verified_by||'',items:row.data||[],ts:Date.now(),
        shipDate:row.ship_date||'',transportName:row.transport_name||'',boxCount:row.box_count||'',
        verifiedAt:row.verified_at||'',pickingCompletedAt:row.picking_completed_at||'',
        locationId:row.location_id||'',locationName:row.location_name||''}));
      try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
      _pickServerOk=true;
      const syncEl=document.getElementById('pick-sync-status');
      if(syncEl){syncEl.style.display='';syncEl.innerHTML='&#9679; Live';syncEl.style.color='var(--green)';}
      renderPickDashboard();
    }
  }catch(e){
    _pickServerOk=false;
    const syncEl=document.getElementById('pick-sync-status');
    if(syncEl){syncEl.style.display='';syncEl.innerHTML='&#9650; Offline';syncEl.style.color='var(--orange)';}
  }
  await woCachePromise;
  renderPickDashboard();
  renderPickOrderSummary();
}

async function refreshPickDashboard(){
  // Re-fetches whatever's currently in view: a specific day if the date
  // filter is set, otherwise the full history (no date param), matching
  // initPickingPage()'s default 'show everything' view.
  const dateSel=document.getElementById('pick-dash-date-select');
  const date=dateSel?.value||'';
  const _myToken=++_pickFetchToken;
  try{
    const r=await api.get(API.pickingSessions+(date?'?date='+date:''));
    if(_myToken!==_pickFetchToken) return; // superseded by a newer fetch -- don't clobber its result
    if(Array.isArray(r.data)){
      // Trust the server completely on a successful response, even an
      // empty one — see the comment in initPickingPage() for why this
      // no longer merges in anything from the local cache.
      _pickEstimates=r.data.map(row=>({id:row.id,orderNo:row.order_no,customer:row.customer,
        phone:row.phone,address:row.address||'',picker:row.picker,status:row.status||'pending',
        verified:!!row.verified,verifiedBy:row.verified_by||'',items:row.data||[],ts:Date.now(),
        shipDate:row.ship_date||'',transportName:row.transport_name||'',boxCount:row.box_count||'',
        verifiedAt:row.verified_at||'',pickingCompletedAt:row.picking_completed_at||'',
        locationId:row.location_id||'',locationName:row.location_name||''}));
      try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
    }
    _pickServerOk=true;
    const syncEl=document.getElementById('pick-sync-status');
    if(syncEl){syncEl.style.display='';syncEl.innerHTML='&#9679; Live';syncEl.style.color='var(--green)';}
  }catch(e){
    _pickServerOk=false;
    const syncEl=document.getElementById('pick-sync-status');
    if(syncEl){syncEl.style.display='';syncEl.innerHTML='&#9650; Offline';syncEl.style.color='var(--orange)';}
    try{if(!_pickEstimates.length)_pickEstimates=JSON.parse(localStorage.getItem(PICK_LIST_KEY)||'[]');}catch(ex){}
  }
  renderPickDashboard();
  await refreshWoCacheForPicking();
  renderPickDashboard();
}

async function loadPickingDate(date){
  const _myToken=++_pickFetchToken;
  try{
    const r=await api.get(API.pickingSessions+(date?'?date='+date:''));
    if(_myToken!==_pickFetchToken) return; // superseded by a newer fetch -- don't clobber its result
    if(Array.isArray(r.data)){
      // Was deduplicating by order number here (keeping only the LAST
      // entry per orderNo), which silently dropped one of the two rows
      // any time two genuinely separate picking_sessions rows happened to
      // share the same order number (re-added order, two devices adding
      // the same order before syncing, etc.) — a real order would just
      // disappear from this date's view every time the date filter was
      // touched. picking_sessions.id is the actual primary key; the
      // server's response already has at most one row per id, so no
      // client-side dedup is needed at all. initPickingPage() and
      // refreshPickDashboard() already trust the server list as-is —
      // this brings loadPickingDate() in line with that.
      _pickEstimates=r.data.map(row=>({id:row.id,orderNo:row.order_no,customer:row.customer,
        phone:row.phone,address:row.address||'',picker:row.picker,status:row.status||'pending',
        verified:!!row.verified,verifiedBy:row.verified_by||'',items:row.data||[],ts:Date.now(),
        shipDate:row.ship_date||'',transportName:row.transport_name||'',boxCount:row.box_count||'',
        verifiedAt:row.verified_at||'',pickingCompletedAt:row.picking_completed_at||'',
        locationId:row.location_id||'',locationName:row.location_name||''}));
      try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
      renderPickDashboard();
    }
  }catch(e){toast('Could not load orders for that date','error');}
}
function showAllPickingDates(){
  const dateSel=document.getElementById('pick-dash-date-select');
  if(dateSel)dateSel.value='';
  loadPickingDate('');
}

function showPickDashboard(){
  document.getElementById('pick-dashboard').style.display='';
  document.getElementById('pick-upload-card').style.display='none';
  document.getElementById('pick-list-area').style.display='none';
  document.getElementById('pick-complete-screen').style.display='none';
  const vs=document.getElementById('pick-verify-screen'); if(vs) vs.style.display='none';
  _pickActiveId=null; _pickItems=[]; _pickOrderNo=''; _pickCustomer=''; _pickLocationName=''; _pickLocationId='';
  renderPickDashboard();
  // Auto-refresh every 30s while on dashboard
  clearInterval(window._pickRefreshTimer);
  window._pickRefreshTimer=setInterval(function(){
    if(document.getElementById('pick-dashboard')?.style.display!=='none') refreshPickDashboard();
    else clearInterval(window._pickRefreshTimer);
  },30000);
}

function showPickingUpload(){
  document.getElementById('pick-dashboard').style.display='none';
  document.getElementById('pick-upload-card').style.display='';
  document.getElementById('pick-list-area').style.display='none';
  document.getElementById('pick-complete-screen').style.display='none';
  const vs=document.getElementById('pick-verify-screen'); if(vs) vs.style.display='none';
  clearInterval(window._pickRefreshTimer);
  renderEstimateList();
  populatePickLocationSelect();
}
// Orders can be picked from any location; default to 'RR Crackers' specifically
// (falling back to whichever location is flagged default, then the first one)
// rather than the app-wide global-location selector, since picking should
// default to the main store regardless of what location staff last browsed.
async function populatePickLocationSelect(){
  try{
    const r=await api.get(API.locations);
    const sel=document.getElementById('pick-location');
    if(!sel||!r.data)return;
    const cur=sel.value;
    const rr=r.data.find(l=>(l.name||'').trim().toLowerCase()==='rr crackers');
    const def=r.data.find(l=>+l.is_default);
    const prefer=cur||(rr?rr.id:'')||(def?def.id:'')||(r.data[0]?r.data[0].id:'');
    sel.innerHTML=r.data.map(l=>`<option value="${l.id}" ${l.id==prefer?'selected':''}>${esc(l.name)}${+l.is_default?' ★':''}</option>`).join('');
  }catch{}
}
function getPickLocationChoice(){
  const sel=document.getElementById('pick-location');
  if(!sel||!sel.value)return{locationId:'',locationName:''};
  const opt=sel.options[sel.selectedIndex];
  return{locationId:sel.value,locationName:opt?opt.text.replace(' ★','').trim():''};
}
// Shared renderer for the '📜 Order · 👤 Customer · 📞 Phone · 🏪 Location'
// summary line shown above the picking list — the location segment is a
// click target that opens the change-location modal, since an order's
// items can turn out to be stocked at a different location than the one
// picked at creation time.
function renderPickOrderSummary(){
  const sumEl=document.getElementById('pick-order-summary');
  if(!sumEl)return;
  const ph=document.getElementById('pick-phone')?.value||'';
  const locHtml=_pickLocationName
    ? '&#127978; <a href="javascript:void(0)" onclick="openPickLocationChangeModal()" style="color:inherit;text-decoration:underline dotted;cursor:pointer" title="Change pick location">'+esc(_pickLocationName)+'</a>'
    : '<a href="javascript:void(0)" onclick="openPickLocationChangeModal()" style="color:var(--accent);cursor:pointer">+ Set location</a>';
  let html='&#128220; <b>'+esc(_pickOrderNo||'—')+'</b>'
    +' &nbsp;&middot;&nbsp; &#128100; '+esc(_pickCustomer||'—')
    +' &nbsp;&middot;&nbsp; &#128222; '+esc(ph||'—')
    +' &nbsp;&middot;&nbsp; '+locHtml;
  // Overpayment banner — same source (website_orders cache) as the
  // dashboard row badge, so the two never disagree. Bold and impossible
  // to miss: this is the screen where someone would actually act on it
  // by adding items to cover the extra amount paid.
  const woRow=findWoRowForOrder(_pickOrderNo);
  const extraPaid=woRow?Math.round(((+woRow.amount_paid||0)-(+woRow.amount||0))*100)/100:0;
  if(extraPaid>0.5){
    html+='<div style="margin-top:8px;font-weight:700;color:var(--yellow);background:rgba(234,179,8,.12);border:1px solid rgba(234,179,8,.4);border-radius:var(--radius-sm);padding:8px 12px;display:inline-block">'
      +'&#128176; Extra ₹'+extraPaid.toFixed(2)+' paid — add items to match</div>';
  }
  sumEl.innerHTML=html;
}
async function openPickLocationChangeModal(){
  if(!_pickActiveId){toast('No active order','error');return;}
  try{
    const r=await api.get(API.locations);
    const sel=document.getElementById('pick-location-change');
    if(!sel||!r.data)return;
    const est=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
    const cur=est?est.locationId||'':'';
    sel.innerHTML=r.data.map(function(l){return '<option value="'+l.id+'" '+(l.id==cur?'selected':'')+'>'+esc(l.name)+(+l.is_default?' ★':'')+'</option>';}).join('');
    openModal('modal-pick-location');
  }catch(e){toast(e.message,'error');}
}
function savePickLocationChange(){
  const sel=document.getElementById('pick-location-change');
  if(!sel||!sel.value){toast('Select a location','error');return;}
  const est=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
  if(!est){toast('No active order','error');return;}
  const opt=sel.options[sel.selectedIndex];
  est.locationId=sel.value;
  est.locationName=opt.text.replace(' ★','').trim();
  _pickLocationName=est.locationName;
  _pickLocationId=est.locationId;
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  api.post(API.pickingSessions,{id:est.id,orderNo:est.orderNo,customer:est.customer,
    phone:est.phone||'',address:est.address||'',picker:est.picker||'',
    items:est.items||[],status:est.status||_pickStatus||'pending',
    verified:est.verified?1:0,verifiedBy:est.verifiedBy||'',verifiedAt:est.verifiedAt||'',
    shipDate:est.shipDate||'',transportName:est.transportName||'',boxCount:est.boxCount||'',
    pickingCompletedAt:est.pickingCompletedAt||'',date:d,
    location_id:est.locationId}).catch(function(e){toast(e.message,'error');});
  closeModal('modal-pick-location');
  renderPickOrderSummary();
  renderPickDashboard();
  toast('Location changed to '+est.locationName);
}

function showPickingList(){
  document.getElementById('pick-dashboard').style.display='none';
  document.getElementById('pick-upload-card').style.display='none';
  document.getElementById('pick-list-area').style.display='';
  document.getElementById('pick-complete-screen').style.display='none';
  const vs=document.getElementById('pick-verify-screen'); if(vs) vs.style.display='none';
  clearInterval(window._pickRefreshTimer);
  renderPickOrderSummary();
  if(typeof setPickStatus==='function' && _pickStatus) setPickStatus(_pickStatus);
  if(typeof renderPickItems==='function') renderPickItems();
  updatePickLockState();
  loadPickItemStock();
}

// Fetches current stock for every item on screen, in one request (the
// whole priced catalog for this order's location, rather than one
// lookup per item — cheap for a handful of items, essential for orders
// with 60+ lines), then annotates each _pickItems entry with
// availableStock for renderPickItems() to display. On-screen only —
// printPickSheet() builds its own separate HTML from _pickItems and never
// reads availableStock, so the printed sheet is unaffected by design.
async function loadPickItemStock(){
  if(!Array.isArray(_pickItems)||!_pickItems.length) return;
  try{
    const locationId=_pickLocationId||'';
    const r=await api.get(API.products+(locationId?('?location_id='+encodeURIComponent(locationId)):''));
    const rows=Array.isArray(r.data)?r.data:[];
    const bySku={};
    rows.forEach(function(p){ if(p.sku) bySku[String(p.sku).toUpperCase()]=p; });
    _pickItems.forEach(function(it){
      if(!it.code) return;
      const p=bySku[String(it.code).toUpperCase()];
      if(!p) return;
      it.matched_id=p.id;
      it.availableStock=locationId?(+p.display_stock||0):(+p.stock||0);
    });
    if(typeof renderPickItems==='function') renderPickItems();
  }catch(e){ /* stock display is informational — fail silently */ }
}

// Locks the item grid, Select All / Verify toolbar, and Complete button
// while an order is Pending — a picker (or anyone) could otherwise still
// tick items off and finish picking on an unpaid order even though the
// stage itself couldn't advance. This is a belt-and-suspenders UI lock on
// top of the setPickStatus() gate, not a replacement for it — the item
// mutation functions themselves aren't individually guarded, so this
// pointer-events lock is what actually stops interaction.
function updatePickLockState(){
  const grid=document.getElementById('pick-items-grid');
  const toolbar=document.getElementById('pick-toolbar-row');
  const banner=document.getElementById('pick-payment-lock-banner');
  const completeBtn=document.getElementById('pick-complete-btn');
  const locked=_pickStatus==='pending';
  [grid,toolbar].forEach(function(el){
    if(!el)return;
    el.style.pointerEvents=locked?'none':'';
    el.style.opacity=locked?'.4':'';
  });
  if(completeBtn){completeBtn.disabled=locked;completeBtn.style.opacity=locked?'.5':'';completeBtn.style.cursor=locked?'not-allowed':'';}
  if(banner) banner.style.display=locked?'flex':'none';
  // The picking sheet is only meant to be printed while an order is
  // actively being picked -- never before payment clears (locked===true
  // covers that), and not once picking is done either (verification/
  // packing/dispatched have their own separate 'Print Check Sheet'
  // action, gated to CAN_VERIFY roles, that this doesn't touch).
  const printBtn=document.getElementById('pick-print-btn');
  if(printBtn){
    const printEnabled=_pickStatus==='picking';
    printBtn.disabled=!printEnabled;
    printBtn.style.opacity=printEnabled?'':'.5';
    printBtn.style.cursor=printEnabled?'':'not-allowed';
  }
}

async function handlePickFile(input){
  const files=[...(input.files||[])].filter(f=>/\.(pdf|txt)$/i.test(f.name));
  if(!files.length){toast('Select PDF or text files only','error');return;}
  for(const f of files) await processSinglePickFile(f);
  input.value='';
  showPickDashboard();
}

function setPickDashStatusFilter(status){
  _pickDashStatusFilter = (_pickDashStatusFilter===status) ? '' : status;
  renderPickDashboard();
}
function clearPickDashFilters(){
  _pickDashStatusFilter='';
  const sel=document.getElementById('pick-dash-location-filter');
  if(sel) sel.value='';
  renderPickDashboard();
}
async function populatePickDashLocationFilter(){
  try{
    const r=await api.get(API.locations);
    const sel=document.getElementById('pick-dash-location-filter');
    if(!sel||!r.data)return;
    const cur=sel.value;
    sel.innerHTML='<option value="">🏪 All Locations</option>'+
      r.data.map(function(l){return '<option value="'+l.id+'" '+(l.id==cur?'selected':'')+'>'+esc(l.name)+(+l.is_default?' ★':'')+'</option>';}).join('');
  }catch{}
}
function renderPickDashboard(){
  if(!_pickEstimates.length) try{_pickEstimates=JSON.parse(localStorage.getItem(PICK_LIST_KEY)||'[]');}catch(e){}
  const n=new Date();
  const dateSel=document.getElementById('pick-dash-date-select');
  const dateEl=document.getElementById('pick-dash-date');
  if(dateEl){
    dateEl.textContent = (dateSel&&dateSel.value)
      ? new Date(dateSel.value+'T00:00:00').toLocaleDateString('en-IN',{weekday:'long',day:'numeric',month:'long',year:'numeric'})
      : 'All estimates — through '+n.toLocaleDateString('en-IN',{day:'numeric',month:'long',year:'numeric'});
  }
  const syncEl=document.getElementById('pick-sync-status');
  if(syncEl){syncEl.style.display=_pickServerOk?'':'none';syncEl.innerHTML=_pickServerOk?'&#9679; Live':'';}
  const SM={
    pending:{label:'Pending',color:'var(--text3)',bg:'rgba(148,163,184,.15)',icon:'⏸'},
    picking:{label:'Picking',color:'var(--orange)',bg:'rgba(249,115,22,.15)',icon:'📦'},
    verification:{label:'Verification',color:'#ca8a04',bg:'rgba(234,179,8,.15)',icon:'🔍'},
    packing:{label:'Packing',color:'var(--accent)',bg:'rgba(79,142,255,.15)',icon:'📦'},
    dispatched:{label:'Dispatched',color:'var(--green)',bg:'rgba(34,197,94,.15)',icon:'🚚'},
  };
  const counts={};
  _pickEstimates.forEach(e=>{const s=e.status||'pending';counts[s]=(counts[s]||0)+1;});
  const statsEl=document.getElementById('pick-dash-stats');
  if(statsEl){
    const allOn=!_pickDashStatusFilter;
    statsEl.innerHTML='<button onclick="setPickDashStatusFilter(\'\')" style="cursor:pointer;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;border:1.5px solid '+(allOn?'var(--accent)':'transparent')+';background:'+(allOn?'var(--accent)':'var(--surface2)')+';color:'+(allOn?'#fff':'var(--text2)')+'">All ('+_pickEstimates.length+')</button>'
      +Object.keys(SM).map(s=>{
        if(!counts[s])return '';
        const on=_pickDashStatusFilter===s;
        return '<button onclick="setPickDashStatusFilter(\''+s+'\')" style="cursor:pointer;padding:5px 12px;border-radius:20px;font-size:.78rem;font-weight:700;border:1.5px solid '+(on?SM[s].color:'transparent')+';background:'+SM[s].bg+';color:'+SM[s].color+'">'+SM[s].icon+' '+SM[s].label+': '+counts[s]+'</button>';
      }).join('');
  }
  const tbody=document.getElementById('pick-dash-tbody');
  if(!tbody) return;
  const locFilter=document.getElementById('pick-dash-location-filter')?.value||'';
  let visibleEstimates=_pickDashStatusFilter?_pickEstimates.filter(e=>(e.status||'pending')===_pickDashStatusFilter):_pickEstimates;
  if(locFilter) visibleEstimates=visibleEstimates.filter(e=>String(e.locationId||'')===locFilter);
  if(!_pickEstimates.length){
    tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3)"><div style="font-size:1.5rem;margin-bottom:8px">📋</div><div style="font-weight:600;margin-bottom:8px">No orders yet</div><button class="btn btn-primary btn-sm" onclick="showPickingUpload()">+ Add First Order</button></td></tr>';
    return;
  }
  if(!visibleEstimates.length){
    tbody.innerHTML='<tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text3)"><div style="font-size:1.5rem;margin-bottom:8px">🔎</div><div style="font-weight:600;margin-bottom:8px">No orders match this filter</div><button class="btn btn-outline btn-sm" onclick="clearPickDashFilters()">Clear filter</button></td></tr>';
    return;
  }
  tbody.innerHTML='';
  visibleEstimates.forEach(est=>{
    const s=est.status||'pending',sm=SM[s]||SM.pending;
    const items=est.items||[];
    const done=items.filter(it=>{
      if(!it.unavailable) return it.picked>=it.qty;
      const sv=(it.substitutes||[]).reduce((a,b)=>a+(+b.sell||0)*(+b.picked||0),0);
      const ov=+it.amount||(+it.rate||0)*it.qty;
      return ov>0?sv>=ov:(it.substitutes||[]).reduce((a,b)=>a+(b.picked||0),0)>=it.qty;
    }).length;
    const pct=items.length>0?Math.round(done/items.length*100):0;
    // Net over/short across every substituted (unavailable) item in this
    // order — target estimate value vs. what's actually being given via
    // substitutes. Positive = short (customer getting less value than
    // estimated), negative = over (getting more).
    let netDiff=0;
    items.forEach(it=>{
      if(!it.unavailable)return;
      const tgt=+it.amount||(+it.rate||0)*(+it.qty||0);
      const subVal=(it.substitutes||[]).reduce((a,b)=>a+(+b.sell||0)*(+b.picked||0),0);
      netDiff+=(tgt-subVal);
    });
    netDiff=Math.round(netDiff*100)/100;
    const diffHtml=Math.abs(netDiff)>0.01
      ?'<div style="font-size:.74rem;margin-top:4px;font-weight:700;color:'+(netDiff>0?'var(--orange)':'var(--accent)')+'">'+(netDiff>0?'Short ₹'+netDiff.toFixed(2):'Over ₹'+(-netDiff).toFixed(2))+'</div>'
      :'';
    const addr=(est.address||'').trim();
    // Overpayment flag — pulled from the shared website_orders cache
    // (refreshWoCacheForPicking()) by matching order number, since the
    // amount/payment total lives there, not on the picking session itself.
    const woRow=findWoRowForOrder(est.orderNo);
    const extraPaid=woRow?Math.round(((+woRow.amount_paid||0)-(+woRow.amount||0))*100)/100:0;
    const extraHtmlRow=extraPaid>0.5
      ?'<div style="font-size:.72rem;font-weight:700;color:var(--yellow);margin-top:2px">💰 +₹'+extraPaid.toFixed(2)+' extra paid</div>'
      :'';
    const tr=document.createElement('tr');
    tr.style.cssText='border-bottom:1px solid var(--border2);cursor:pointer';
    tr.onmouseover=()=>tr.style.background='var(--surface2)';
    tr.onmouseout=()=>tr.style.background='';
    tr.innerHTML=
      '<td style="padding:12px;white-space:nowrap;font-size:.85rem"><b>'+esc(est.orderNo||'—')+'</b></td>'
      +'<td style="padding:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+esc(est.customer||'')+'"><span style="color:#f97316;font-weight:600">'+(est.customer&&est.customer.length>0&&est.customer!=='—'?esc(est.customer):'<span style="color:var(--text3);font-size:.8rem">No name</span>')+'</span>'+extraHtmlRow+'</td>'
      +'<td style="padding:12px;white-space:nowrap"><span style="color:#3b82f6">'+esc(est.phone||'—')+'</span></td>'
      +'<td style="padding:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem;color:var(--text3)" title="'+esc(addr)+'">'+esc(addr||'—')+'</td>'
      +'<td style="padding:12px;text-align:center;overflow:hidden"><span style="padding:4px 9px;border-radius:20px;font-size:.76rem;font-weight:700;background:'+sm.bg+';color:'+sm.color+';white-space:nowrap;display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis">'+sm.icon+' '+sm.label+'</span>'
        +(pct>0&&pct<100?'<div style="background:var(--border2);border-radius:10px;height:5px;margin-top:5px;overflow:hidden"><div style="background:'+sm.color+';width:'+pct+'%;height:100%;border-radius:10px"></div></div>':'')
        +diffHtml
      +'</td>'
      +'<td style="padding:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem;color:var(--text2)" title="'+esc(est.picker||'')+'">'+esc(est.picker||'—')+'</td>'
      +'<td style="padding:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.8rem;color:var(--text2)" title="'+esc(est.verifiedBy||'')+'">'+esc(est.verifiedBy||'—')+'</td>'
      +'<td style="padding:12px;text-align:center;font-size:.8rem;color:var(--text3)">'+done+'/'+items.length+'</td>'
      +'<td style="padding:12px 10px;text-align:right">';
    const ac=tr.lastElementChild;
    if(s==='verification'&&CAN_VERIFY){const vb=document.createElement('button');vb.className='btn btn-outline btn-sm';vb.style.cssText='border-color:#ca8a04;color:#ca8a04;margin-right:5px;font-size:.78rem';vb.textContent='🔍 Verify';vb.onclick=ev=>{ev.stopPropagation();openEstimateVerify(est.id);};ac.appendChild(vb);}
    if(s==='packing'){const db=document.createElement('button');db.className='btn btn-outline btn-sm';db.style.cssText='border-color:var(--green);color:var(--green);margin-right:5px;font-size:.78rem';db.textContent='🚚 Dispatch';db.onclick=ev=>{ev.stopPropagation();openDispatchModal(est.id);};ac.appendChild(db);}
    if(CAN_VERIFY){const pb=document.createElement('button');pb.className='btn btn-outline btn-sm';pb.style.cssText='border-color:var(--green);color:var(--green);margin-right:5px;font-size:.78rem';pb.textContent='💰';pb.title='Record payment';pb.onclick=ev=>{ev.stopPropagation();openEstimatePayment(est.id);};ac.appendChild(pb);}
    const ob=document.createElement('button');ob.className='btn btn-ghost btn-sm';ob.style.cssText='font-size:.78rem';ob.textContent='Open';ob.onclick=ev=>{ev.stopPropagation();openEstimate(est.id);};ac.appendChild(ob);
    if(CAN_DELETE){const db=document.createElement('button');db.className='btn btn-ghost btn-sm';db.textContent='🗑';db.title='Delete';db.style.cssText='color:var(--red);opacity:.6;margin-left:3px;font-size:.82rem';db.onclick=ev=>{ev.stopPropagation();deleteEstimate(est.id);};ac.appendChild(db);}
    tr.onclick=()=>openEstimate(est.id);
    tbody.appendChild(tr);
  });
}
function openEstimateVerify(id){openEstimate(id);setTimeout(()=>{if(typeof setPickStatus==='function')setPickStatus('verification');},200);}

// Packing -> Dispatched, reachable both from the dashboard's quick-action
// '🚚 Dispatch' button (packing-stage rows) and from the 'Dispatched' stage
// pill inside an open order. Either entry point opens the same modal to
// capture ship date / transport / box count before saving, since these
// details need to be recorded at the moment of dispatch.
async function openDispatchModal(id){
  if(!id){toast('No active order','error');return;}
  const est=_pickEstimates.find(function(e){return e.id===id;});
  if(!est){toast('Order not found','error');return;}
  // The Dispatched stage pill is reachable from any stage, including
  // straight off Pending — it doesn't go through setPickStatus(), so it
  // needs its own payment check to close the same loophole.
  if((est.status||'pending')==='pending'){
    const paid=await isOrderFullyPaid(est.orderNo);
    if(!paid){
      toast('Payment not recorded — record payment before this order can move forward','error');
      if(CAN_VERIFY) openEstimatePayment(id);
      return;
    }
  }
  // Same reasoning as the Packing check in setPickStatus() -- Picker
  // may assist with Dispatch, but only once the order has actually been
  // verified. This function bypasses setPickStatus() entirely (it POSTs
  // status:'dispatched' itself in confirmDispatch()), so it needs its own
  // copy of the check.
  if(!CAN_VERIFY && !est.verified){
    toast('Order must be verified before it can be dispatched','error');
    return;
  }
  _dispatchOrderId=id;
  const nameEl=document.getElementById('dispatch-order-name');
  if(nameEl)nameEl.textContent=(est.orderNo||id)+(est.customer?' — '+est.customer:'');
  const today=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  const sd=document.getElementById('dispatch-ship-date');if(sd)sd.value=est.shipDate||today;
  const tn=document.getElementById('dispatch-transport-name');if(tn)tn.value=est.transportName||'';
  const bc=document.getElementById('dispatch-box-count');if(bc)bc.value=est.boxCount||'';
  openModal('modal-dispatch');
}
function closeDispatchModal(){
  closeModal('modal-dispatch');
  _dispatchOrderId=null;
}
async function confirmDispatch(){
  const id=_dispatchOrderId;
  const est=_pickEstimates.find(function(e){return e.id===id;});
  if(!est){toast('Order not found','error');closeDispatchModal();return;}
  const shipDate=document.getElementById('dispatch-ship-date')?.value||'';
  const transportName=document.getElementById('dispatch-transport-name')?.value.trim()||'';
  const boxCountRaw=document.getElementById('dispatch-box-count')?.value||'';
  const boxCount=boxCountRaw?parseInt(boxCountRaw,10):'';
  // Transport details are mandatory before an order can be marked
  // Dispatched — they're the whole point of this modal.
  if(!shipDate){toast('Ship date is required','error');return;}
  if(!transportName){toast('Transport name is required','error');return;}
  if(!boxCount||boxCount<=0){toast('Number of boxes is required','error');return;}
  const prev={status:est.status,shipDate:est.shipDate,transportName:est.transportName,boxCount:est.boxCount};
  est.status='dispatched';
  est.shipDate=shipDate;
  est.transportName=transportName;
  est.boxCount=boxCount;
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  // If the order currently open in the full picking screen is the one being
  // dispatched, keep its in-memory status in sync and send the picker back
  // to the dashboard so the new stage/shipping info is visible immediately.
  if(_pickActiveId===id){_pickStatus='dispatched';}
  closeDispatchModal();
  renderPickDashboard();
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  try{
    await api.post(API.pickingSessions,{id:est.id,orderNo:est.orderNo,customer:est.customer,
      phone:est.phone||'',address:est.address||'',picker:est.picker||'',items:est.items||[],
      status:'dispatched',verified:est.verified?1:0,verifiedBy:est.verifiedBy||'',
      shipDate:est.shipDate||'',transportName:est.transportName||'',boxCount:est.boxCount||'',date:d});
    toast('Order '+(est.orderNo||id)+' dispatched');
    if(_pickActiveId===id) showPickDashboard();
  }catch(e){
    est.status=prev.status;est.shipDate=prev.shipDate;est.transportName=prev.transportName;est.boxCount=prev.boxCount;
    if(_pickActiveId===id)_pickStatus=prev.status;
    try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(ex){}
    renderPickDashboard();
    toast('Could not mark dispatched: '+e.message,'error');
  }
}

function renderEstimateList(){
  const el = document.getElementById('pick-estimate-list');
  if(!el) return;
  if(!_pickEstimates.length){
    el.innerHTML='<div style="color:var(--text3);font-size:.8rem;text-align:center;padding:20px">No estimates loaded yet</div>';
    return;
  }
  el.innerHTML = _pickEstimates.map(function(est){
    const isActive = est.id === _pickActiveId;
    const done = est.items.filter(it=>it.picked>=it.qty||it.unavailable).length;
    const total = est.items.length;
    const pct = total>0?Math.round(done/total*100):0;
    return '<div data-eid="'+est.id+'" onclick="openEstimate(this.dataset.eid)" style="background:'+(isActive?'rgba(79,142,255,.1)':'var(--surface2)')+';border:1.5px solid '+(isActive?'var(--accent)':'var(--border)')+';border-radius:var(--radius-sm);padding:10px 12px;cursor:pointer">'
      +'<div style="font-weight:700;font-size:.85rem;margin-bottom:2px">'+esc(est.orderNo||'No Order #')+'</div>'
      +'<div style="font-size:.75rem;color:var(--text3);margin-bottom:5px">'+esc(est.customer||'—')+(est.phone?' · '+est.phone:'')+'</div>'
      +'<div style="display:flex;align-items:center;gap:6px">'
        +'<div style="flex:1;background:var(--surface3);border-radius:10px;height:5px;overflow:hidden">'
          +'<div style="background:'+(pct===100?'var(--green)':'var(--accent)')+';width:'+pct+'%;height:100%;border-radius:10px"></div>'
        +'</div>'
        +'<span style="font-size:.7rem;color:'+(pct===100?'var(--green)':'var(--text3)')+'">'+done+'/'+total+'</span>'
      +'</div>'
      +'</div>';
  }).join('');
}

function openEstimate(id){
  const est = _pickEstimates.find(e=>e.id===id);
  if(!est) return;
    _pickActiveId = est.id;
  _pickItems    = est.items;
  _pickOrderNo  = est.orderNo||'';
  _pickCustomer = est.customer||'';
  _pickLocationName = est.locationName||'';
  _pickLocationId = est.locationId||'';
  _pickAddress  = est.address||'';
  _pickStatus   = est.status||'pending';
  // Populate these synchronously before savePickSession() runs below —
  // savePickSession() (and printPickSheet/sendWhatsApp/setPickStatus)
  // read the phone straight back out of #pick-phone, so if this was
  // deferred via setTimeout, savePickSession() would fire first, read
  // whatever stale value was left in the field from a previous order
  // (or blank), and overwrite the correct saved phone with it — every
  // single time an order was opened.
  const ph=document.getElementById('pick-phone');if(ph)ph.value=est.phone||'';
  const no=document.getElementById('pick-order-no');if(no)no.value=est.orderNo||'';
  const cu=document.getElementById('pick-customer');if(cu)cu.value=est.customer||'';
  savePickSession();
  renderEstimateList();
  showPickingList();
  updateShipInfoDisplay(est);
  // Was pending — attempt the pending->picking transition. setPickStatus()
  // gates this on payment and no-ops (with a toast) if it isn't paid yet,
  // so the order stays visibly Pending until someone records the payment.
  if(_pickStatus==='pending') setPickStatus('picking');
}
// Formats either a raw ms timestamp (just stamped locally via Date.now())
// or a 'YYYY-MM-DD HH:MM:SS' string (as returned by the server) into a
// short readable local date/time.
function formatPickTimestamp(v){
  if(!v)return '';
  const d=(typeof v==='number')?new Date(v):new Date((''+v).replace(' ','T'));
  if(isNaN(d.getTime()))return '';
  return d.toLocaleString('en-IN',{day:'numeric',month:'short',hour:'2-digit',minute:'2-digit'});
}
function updateShipInfoDisplay(est){
  const el=document.getElementById('pick-ship-info');
  if(!el)return;
  if(!est){el.style.display='none';el.textContent='';return;}
  const parts=[];
  const pickedAt=formatPickTimestamp(est.pickingCompletedAt);
  if(pickedAt)parts.push('📦 Picked '+pickedAt);
  const verifiedAt=formatPickTimestamp(est.verifiedAt);
  if(verifiedAt)parts.push('✅ Verified '+verifiedAt);
  if(est.shipDate){
    const shipParts=['🚚 Shipped '+est.shipDate];
    if(est.transportName)shipParts.push(est.transportName);
    if(est.boxCount)shipParts.push(est.boxCount+' box'+(est.boxCount==1?'':'es'));
    parts.push(shipParts.join(' · '));
  }
  if(!parts.length){el.style.display='none';el.textContent='';return;}
  el.textContent=parts.join('   ·   ');
  el.style.display='';
}

function savePickSession(){
  const phone=document.getElementById('pick-phone')?.value||'';
  // Carry the order's existing verified state forward on every save.
  // The server's UPDATE always overwrites verified with whatever gets
  // POSTed (it isn't merged/coalesced like verify_code is) — so any
  // ordinary picking save that omitted this would silently un-verify
  // an already-verified order the next time an item got adjusted.
  const existingEst=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
  // Picker is blank while the order is still pending (nobody has started
  // picking it) and gets stamped exactly once — the moment the order first
  // leaves 'pending' — then carried forward unchanged on every later save,
  // no matter who (picker, verifier, packer) triggers that save.
  let picker=existingEst?(existingEst.picker||''):'';
  if(!picker&&_pickStatus&&_pickStatus!=='pending') picker=CURRENT_USER;
  const session={id:_pickActiveId,orderNo:_pickOrderNo,customer:_pickCustomer,phone,
    address:_pickAddress||'',picker,items:_pickItems,status:_pickStatus||'pending',
    verified:existingEst?!!existingEst.verified:false,verifiedBy:existingEst?(existingEst.verifiedBy||''):'',
    verifiedAt:existingEst?(existingEst.verifiedAt||''):'',
    shipDate:existingEst?(existingEst.shipDate||''):'',transportName:existingEst?(existingEst.transportName||''):'',
    boxCount:existingEst?(existingEst.boxCount||''):'',
    pickingCompletedAt:existingEst?(existingEst.pickingCompletedAt||''):'',
    ts:Date.now()};
  const idx2=_pickEstimates.findIndex(e=>e.id===_pickActiveId);
  if(idx2>=0){_pickEstimates[idx2]={...session,verified:_pickEstimates[idx2].verified||false,verifiedBy:_pickEstimates[idx2].verifiedBy||''};}
  else if(_pickActiveId){_pickEstimates.push({...session,verified:false,verifiedBy:''}); }
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  try{localStorage.setItem(PICK_KEY,JSON.stringify(session));}catch(e){}
  if(_pickActiveId) syncPickSessionToServer(session);
}
function saveEstimateList(){
  // Update current estimate in list
  if(_pickActiveId){
    const idx = _pickEstimates.findIndex(e=>e.id===_pickActiveId);
    const phone = document.getElementById('pick-phone')?.value||'';
    const existing = idx>=0?_pickEstimates[idx]:null;
    let picker = existing?(existing.picker||''):'';
    if(!picker&&_pickStatus&&_pickStatus!=='pending') picker=CURRENT_USER;
    const entry = {id:_pickActiveId,orderNo:_pickOrderNo,customer:_pickCustomer,phone,address:_pickAddress||'',picker,items:_pickItems,status:_pickStatus||'pending',ts:Date.now()};
    if(idx>=0) _pickEstimates[idx]=entry;
    else _pickEstimates.push(entry);
  }
  try{ localStorage.setItem(PICK_LIST_KEY, JSON.stringify(_pickEstimates)); }catch(e){}
  renderEstimateList();
  if(document.getElementById('pick-dashboard')?.style.display!=='none') renderPickDashboard();
}

async function deleteEstimate(id){
  if(!CAN_DELETE){toast('Only admins can delete orders','error');return;}
  const est=_pickEstimates.find(e=>e.id===id);if(!est)return;
  if(!confirm('Delete order '+(est.orderNo||id)+'?'))return;
  // Previously this swallowed the delete request's error and removed the
  // row from the local list/localStorage regardless, showing 'Order
  // removed' even when the server-side delete failed (e.g. no
  // permission) — the row would then reappear on the next refresh with
  // no indication anything had gone wrong. Now a failed request stops
  // here and leaves the row in place with an error toast.
  try{
    await api.delete(API.pickingSessions+'?id='+id);
  }catch(ex){
    toast('Could not delete: '+ex.message,'error');
    return;
  }
  _pickEstimates=_pickEstimates.filter(e=>e.id!==id);
  if(_pickActiveId===id){_pickActiveId=null;_pickItems=[];_pickOrderNo='';_pickCustomer='';_pickLocationName='';_pickLocationId='';}
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(ex){}
  renderPickDashboard();toast('Order removed');
}
async function clearAllEstimates(){
  if(!CAN_DELETE){toast('Only admins can delete orders','error');return;}
  if(!_pickEstimates.length){toast('No orders to clear','error');return;}
  if(!confirm('Clear all '+_pickEstimates.length+' orders? Cannot be undone.'))return;
  const failed=[];
  for(const e of _pickEstimates){
    try{await api.delete(API.pickingSessions+'?id='+e.id);}
    catch(ex){failed.push(e);}
  }
  _pickEstimates=failed;
  _pickActiveId=null;_pickItems=[];_pickOrderNo='';_pickCustomer='';_pickLocationName='';_pickLocationId='';
  if(failed.length){
    try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(ex){}
    toast(failed.length+' order(s) could not be deleted','error');
  }else{
    localStorage.removeItem(PICK_LIST_KEY);localStorage.removeItem(PICK_KEY);
    toast('All orders cleared');
  }
  renderPickDashboard();
}

// ── PDF parsing & picking functions ──────────────────────────────────────
function parsePicking(){
  const orderNo=document.getElementById('pick-order-no')?.value.trim()||'';
  const customer=document.getElementById('pick-customer')?.value.trim()||'';
  const phone=document.getElementById('pick-phone')?.value.trim()||'';
  const text=document.getElementById('pick-paste-area')?.value||'';
  if(!orderNo&&!text){toast('Enter an order number or paste PDF text','error');return;}
  const id='est_'+Date.now();
  const items=text?parsePickingFromText(text).items:[];
  const{locationId,locationName}=getPickLocationChoice();
  const est={id,orderNo:orderNo||('EST'+Date.now()),customer,phone,address:'',
    picker:'',items,status:'pending',verified:false,verifiedBy:'',ts:Date.now(),
    locationId,locationName};
  // Check duplicate
  const dup=_pickEstimates.find(e=>e.orderNo&&e.orderNo===est.orderNo);
  if(dup){toast('Order '+est.orderNo+' already loaded','error');return;}
  _pickEstimates.push(est);
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  api.post(API.pickingSessions,{id:est.id,orderNo:est.orderNo,customer:est.customer,
    phone:est.phone,address:est.address||'',picker:'',items:est.items,
    status:'pending',date:d,location_id:locationId||null}).catch(()=>{});
  renderEstimateList();
  renderPickDashboard();
  toast('Order '+est.orderNo+' added');
}

function parsePickingFromText(text){
  var orderNo='',customer='',phone='',address='';
  var mO=/Estimate\s+Number\s*[:\-]?\s*(\S+)/i.exec(text)||/Estimate\s*[:#]\s*(\S+)/i.exec(text);
  if(mO) orderNo=mO[1].trim();
  // The 'Billed To' block is positional and consistent across every
  // real estimate we've tested: first line = customer name (verbatim,
  // even if it contains a bracketed area suffix like '[Kr Puram -
  // Bangalore]'), next line with a 10-12 digit number = phone, and
  // everything after that up to the bank-details heading = address.
  // (The previous version tried to guess 'is this line a name or a
  // place' via a blacklist of city/state keywords. That rejected any
  // customer name that happened to contain a place name — e.g. 'Jk
  // [Kr Puram - Bangalore]' — and, since address collection only
  // started once a customer name had been accepted, a rejected name
  // line also meant the address never got collected. Worse, 'Karnataka'
  // wasn't itself in the blacklist, so it got accepted as the 'customer
  // name' once the loop reached it a few lines later.)
  var billedLineIdx=-1;
  var pdfLinesB=text.split('\n');
  for(var pib=0;pib<pdfLinesB.length;pib++){if(/Billed\s+To/i.test(pdfLinesB[pib])){billedLineIdx=pib;break;}}
  var addrParts=[];
  if(billedLineIdx>=0){
    var searchLinesB=pdfLinesB.slice(billedLineIdx+1,billedLineIdx+14).map(function(l){return l.trim();}).filter(function(l){return l.length>0;});
    var bi2=0;
    if(bi2<searchLinesB.length&&!/^\d{10,12}$/.test(searchLinesB[bi2])&&!/^(a\/c|bank\s+name|bank\s+account|ifsc)/i.test(searchLinesB[bi2])){
      customer=searchLinesB[bi2];
      bi2++;
    }
    var foundPhoneAt=-1;
    for(var pk=bi2;pk<Math.min(bi2+2,searchLinesB.length);pk++){
      var mPh2=/(\d{10,12})/.exec(searchLinesB[pk]);
      if(mPh2){phone=mPh2[1];foundPhoneAt=pk;break;}
    }
    if(foundPhoneAt>=0)bi2=foundPhoneAt+1;
    for(;bi2<searchLinesB.length;bi2++){
      var addrLine=searchLinesB[bi2];
      if(/^(a\/c|bank\s+name|bank\s+account|ifsc|s\.no|product\s+code|tmbl)/i.test(addrLine))break;
      addrParts.push(addrLine);
    }
  }
  if(!phone){var mPfbB=/Billed\s+To[\s\S]{0,300}?(\d{10})/i.exec(text);if(mPfbB)phone=mPfbB[1];}
  if(customer)customer=customer.replace(/\s*(a\/c|bank account|ifsc|savings|account).*/i,'').replace(/\s*:.*$/,'').replace(/[,.\s]+$/,'').trim();
  address=addrParts.join(', ').replace(/,\s*,/g,',').replace(/\s+/g,' ').trim();

  var items=[];
  var lines=text.split('\n');
  var block='',collecting=false;
  for(var bi=0;bi<lines.length;bi++){
    var bline=lines[bi];
    if(/S\.No|Product Code|Sl\.No/i.test(bline)){collecting=true;continue;}
    if(!collecting)continue;
    var trimmed=bline.trim();
    if(/^Total\b|^Grand Total|^Packing|^Round|^Thanks|^Continued\s+to\s+Page/i.test(trimmed)){collecting=false;continue;}
    if(/^\d+%\s*Products?/i.test(trimmed))continue;
    block+=bline+'\n';
  }
  var itemRe=/([A-Za-z0-9][A-Za-z0-9\-]*)\s*-\s*([\s\S]+?)\s+(\d+)\s+(\d+)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})(?=\s|\n|$)/g;
  var im;
  while((im=itemRe.exec(block))){
    var code=im[1].trim(),name=im[2].replace(/\s+/g,' ').trim(),qty=parseInt(im[4]);
    var finalRate=parseFloat(im[7].replace(/,/g,'')),amount=parseFloat(im[8].replace(/,/g,''));
    if(qty>0)items.push({code:code,name:name,qty:qty,picked:0,rate:finalRate,amount:amount,unavailable:false,substitutes:[],matched_id:null,matched_name:name,brand:''});
  }
  // 'Net Rate Products' sub-section: bulk/combo lines with no product code
  // and no separate discount/final-rate columns — just
  // NAME  S.No  Qty  Rate  Amount (e.g. 'Super Sonic (30 Items) 1 220
  // 355.00 78,100.00', where qty=220 @ rate=355.00 = amount). The main
  // itemRe above requires a 'CODE - NAME' prefix and 6 trailing numbers,
  // so these lines never match it and were silently dropped, leaving
  // affected estimates with 0 items. Scanned separately since the format
  // is genuinely different, not a variant of the coded-item pattern.
  var blockLines=block.split('\n');
  var inNetRate=false;
  var netRateRe=/^(.+?)\s+(\d+)\s+(\d+)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})$/;
  for(var nri=0;nri<blockLines.length;nri++){
    var nrLine=blockLines[nri].trim();
    if(!nrLine)continue;
    if(/^Net\s+Rate\s+Products$/i.test(nrLine)){inNetRate=true;continue;}
    if(/^\d+%\s*Products?$/i.test(nrLine)){inNetRate=false;continue;}
    if(!inNetRate)continue;
    var nrM=netRateRe.exec(nrLine);
    if(nrM){
      var nrName=nrM[1].replace(/\s+/g,' ').trim(),nrQty=parseInt(nrM[3]);
      var nrRate=parseFloat(nrM[4].replace(/,/g,'')),nrAmount=parseFloat(nrM[5].replace(/,/g,''));
      if(nrQty>0)items.push({code:'',name:nrName,qty:nrQty,picked:0,rate:nrRate,amount:nrAmount,unavailable:false,substitutes:[],matched_id:null,matched_name:nrName,brand:''});
    }
  }
  if(!items.length){
    var inItems=false;
    for(var i=0;i<lines.length;i++){
      var line=lines[i].trim();
      if(!line)continue;
      if(/S\.No|Product Code|Sl\.No/i.test(line)){inItems=true;continue;}
      if(!inItems)continue;
      if(/^Total|^Grand Total|^Packing|^Round|^Thanks/i.test(line))break;
      var m=/^(\d+)\s+([A-Z0-9\-]+)\s*[–\-]?\s*(.+?)\s+(\d+)\s+[\d,]+\.\d{2}\s+[\d,]+\.\d{2}\s+[\d,]+\.\d{2}\s+([\d,]+\.\d{2})$/.exec(line);
      if(!m){
        m=/^(\d+)\s+([A-Z0-9\-]+\s*-\s*.+?)\s+(\d+)\s+[\d,.]+\s+[\d,.]+$/.exec(line);
        if(m){
          var parts=m[2].split(/\s*-\s*/,2);
          var code2=parts[0].trim(),name2=parts.slice(1).join(' ').trim()||m[2];
          var qty2=parseInt(m[3]);
          if(qty2>0) items.push({code:code2,name:name2,qty:qty2,picked:0,rate:0,amount:0,unavailable:false,substitutes:[],matched_id:null,matched_name:name2,brand:''});
        }
        continue;
      }
      var code3=m[2].trim(),name3=m[3].trim(),qty3=parseInt(m[4]),amount3=parseFloat(m[5].replace(/,/g,''));
      var rate3=qty3>0?Math.round(amount3/qty3):0;
      if(qty3>0)items.push({code:code3,name:name3,qty:qty3,picked:0,rate:rate3,amount:amount3,unavailable:false,substitutes:[],matched_id:null,matched_name:name3,brand:''});
    }
  }
  return {orderNo:orderNo,customer:customer,phone:phone,address:address,items:items};
}

async function handlePickDrop(e){
  e.preventDefault();
  const files=[...e.dataTransfer.files].filter(f=>/\.(pdf|txt)$/i.test(f.name));
  if(!files.length){toast('Drop PDF or text files only','error');return;}
  for(const f of files) await processSinglePickFile(f);
  showPickDashboard();
}

async function processSinglePickFile(file){
  if(/\.txt$/i.test(file.name)){
    const text=await file.text();
    const result=parsePickingFromText(text);
    addEstimateFromResult(result,file.name);
    return;
  }
  // PDF — use PDF.js if available
  if(window.pdfjsLib){
    try{
      const ab=await file.arrayBuffer();
      const pdf=await pdfjsLib.getDocument({data:ab}).promise;
      var fullText='';
      for(var p=1;p<=pdf.numPages;p++){
        const page=await pdf.getPage(p);
        const tc=await page.getTextContent();
        // Reconstruct line breaks from item Y-position — pdf.js returns text
        // items in content-stream order with no newlines, so joining them
        // with a plain space collapses the whole page into one line and
        // breaks every line-based regex in parsePickingFromText below.
        var pageText='',lastY=null;
        tc.items.forEach(function(it){
          var yPos=it.transform[5];
          if(lastY!==null&&Math.abs(yPos-lastY)>2){pageText+='\n';}
          else if(pageText&&!pageText.endsWith('\n')){pageText+=' ';}
          pageText+=it.str;
          lastY=yPos;
        });
        fullText+=pageText+'\n';
      }
      const result=parsePickingFromText(fullText);
      if(!result.orderNo) result.orderNo=file.name.replace(/\.pdf$/i,'');
      addEstimateFromResult(result,file.name);
    }catch(err){toast('Could not read '+file.name+': '+err.message,'error');}
  } else {
    toast('PDF.js not loaded — paste text manually','error');
  }
}

function addEstimateFromResult(result, filename){
  const id='est_'+Date.now()+'_'+Math.random().toString(36).slice(2,6);
  const dup=_pickEstimates.find(e=>e.orderNo&&e.orderNo===result.orderNo);
  if(dup){toast('Order '+result.orderNo+' already loaded','error');return;}
  const{locationId,locationName}=getPickLocationChoice();
  const est={id,orderNo:result.orderNo||(filename||'').replace(/\.pdf$/i,''),
    customer:result.customer,phone:result.phone,address:result.address||'',
    picker:'',items:result.items||[],status:'pending',
    verified:false,verifiedBy:'',ts:Date.now(),locationId,locationName};
  _pickEstimates.push(est);
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  api.post(API.pickingSessions,{id:est.id,orderNo:est.orderNo,customer:est.customer,
    phone:est.phone||'',address:est.address||'',picker:'',items:est.items,
    status:'pending',date:d,location_id:locationId||null}).catch(()=>{});
  renderEstimateList();
  renderPickDashboard();
  toast(est.orderNo+' added — '+est.items.length+' items');
}

function pickItemDone(it){
  if(!it.unavailable) return (+it.picked||0)>=(+it.qty||0);
  const sv=(it.substitutes||[]).reduce((a,b)=>a+(+b.sell||0)*(+b.picked||0),0);
  const ov=+it.amount||(+it.rate||0)*(+it.qty||0);
  return ov>0?sv>=ov:(it.substitutes||[]).reduce((a,b)=>a+(+b.picked||0),0)>=(+it.qty||0);
}

// Real guard behind the picking UI's payment lock -- see the big comment
// above pickSetPicked() for why this exists in addition to
// updatePickLockState()'s CSS-only pointer-events lock.
function pickBlockedByPayment(){
  if(_pickStatus==='pending'){
    toast('Payment not recorded — record payment before picking begins','error');
    return true;
  }
  return false;
}
function pickSetPicked(idx,val){
  if(pickBlockedByPayment())return;
  const it=_pickItems[idx];
  if(!it||it.unavailable)return;
  const qty=+it.qty||0;
  it.picked=Math.max(0,Math.min(qty,Math.round(+val||0)));
  saveEstimateList();savePickSession();renderPickItems();
}
function pickAdjustPicked(idx,delta){
  const it=_pickItems[idx];
  if(!it)return;
  pickSetPicked(idx,(+it.picked||0)+delta);
}
function pickToggleUnavailable(idx){
  if(pickBlockedByPayment())return;
  const it=_pickItems[idx];
  if(!it)return;
  it.unavailable=!it.unavailable;
  if(it.unavailable){
    pickOpenSubstitutePicker(idx);
  }else if(_pickSubIdx===idx){
    _pickSubIdx=-1;_pickSubCandidates=[];_pickSubLoading=false;
  }
  saveEstimateList();savePickSession();renderPickItems();
}

// ── Substitutes ───────────────────────────────────────────────────────
// Resolve candidate replacement products for an unavailable item's code:
// 1) look the code up directly against the product catalog (sku/name
//    match) to find its real category, 2) if that fails, fall back to
//    matching the code's leading digits against each category's
//    sku_prefix (the same convention Products/Categories already use
//    elsewhere in the app), 3) list other products in that category.
// Strips trailing pack/quantity tokens ("- 3's", "(4 Pcs)", "x2", "2 Pack")
// so 'Flower Pot - 3's' and 'Flower Pot - 2's' compare equal — a different
// pack size of the exact same item is usually the best possible substitute
// (e.g. the ordered 3-pack is out, but the 2-pack or 4-pack isn't).
function pickBaseName(name){
  return String(name||'')
    .replace(/[\(\[]?\s*\d+\s*(pcs?|pack|pc|nos?|units?)\s*[\)\]]?/gi,'')
    .replace(/[-–]?\s*\d+\s*'s\b/gi,'')
    .replace(/\bx\s*\d+\b/gi,'')
    .replace(/\s{2,}/g,' ')
    .trim()
    .toLowerCase();
}

// Ranked substitute suggestions for an unavailable estimate item, filtered
// to what's actually in stock at the order's own location (suggesting a
// product that's also out at this branch isn't useful). Tries, in order:
//  1. Same item, different pack size (base-name match, e.g. 2's/4's for a 3's)
//  2. Same item line across brands/vendors (products.item_code)
//  3. Same category
//  4. Similar sell price (+/- Rs.50), any category — last resort, only
//     runs if the above didn't turn up enough options
async function resolveSubstituteCandidates(item, locationId){
  const code=(item&&item.code)||'';
  const name=(item&&(item.matched_name||item.name))||'';
  const rate=+((item&&item.rate)||0);
  if(!code && !name) return [];
  try{
    let original=null, category=null;
    if(code){
      const r=await api.get(API.products+'?q='+encodeURIComponent(code));
      if(Array.isArray(r.data)&&r.data.length){
        original=r.data.find(function(p){return String(p.sku||'').toUpperCase()===String(code).toUpperCase();})||r.data[0];
        category=original.category||null;
      }
    }
    if(!category && code){
      const digits=(String(code).match(/^\d+/)||[''])[0];
      if(digits){
        const cr=await api.get(API.categories);
        if(Array.isArray(cr.data)){
          let best=null;
          cr.data.forEach(function(c){
            const p=String(c.sku_prefix||'').trim();
            if(p&&digits.indexOf(p)===0&&(!best||p.length>String(best.sku_prefix).length)) best=c;
          });
          if(best) category=best.name;
        }
      }
    }

    const locQ=locationId?('&location_id='+encodeURIComponent(locationId)):'';
    const inStock=function(p){ return locationId ? (+p.display_stock||0)>0 : (+p.stock||0)>0; };
    const notSelf=function(p){ return !code || String(p.sku||'').toUpperCase()!==String(code).toUpperCase(); };
    const seen={}, ranked=[];
    function addAll(list){
      (list||[]).forEach(function(p){
        if(seen[p.id]||!notSelf(p)||!inStock(p))return;
        seen[p.id]=true; ranked.push(p);
      });
    }

    if(name){
      const base=pickBaseName(name);
      if(base){
        const nr=await api.get(API.products+'?q='+encodeURIComponent(base)+locQ);
        if(Array.isArray(nr.data)) addAll(nr.data.filter(function(p){return pickBaseName(p.name)===base;}));
      }
    }
    if(original&&original.item_code){
      const ir=await api.get(API.products+'?item_code='+encodeURIComponent(original.item_code)+locQ);
      if(Array.isArray(ir.data)) addAll(ir.data);
    }
    if(category){
      const cr2=await api.get(API.products+'?category='+encodeURIComponent(category)+locQ);
      if(Array.isArray(cr2.data)) addAll(cr2.data);
    }
    if(rate>0 && ranked.length<5){
      const pr=await api.get(API.products+(locationId?('?location_id='+encodeURIComponent(locationId)):''));
      if(Array.isArray(pr.data)) addAll(pr.data.filter(function(p){return Math.abs((+p.sell||0)-rate)<=50;}));
    }
    return ranked.slice(0,15);
  }catch(e){ return []; }
}

async function pickOpenSubstitutePicker(idx){
  const it=_pickItems[idx];
  if(!it)return;
  _pickSubIdx=idx;_pickSubLoading=true;_pickSubCandidates=[];
  renderPickItems();
  const candidates=await resolveSubstituteCandidates(it, _pickLocationId);
  if(_pickSubIdx!==idx)return; // user moved on before this resolved
  _pickSubCandidates=candidates;_pickSubLoading=false;
  renderPickItems();
}

// Runs once, automatically, the moment a Pending order clears the payment
// gate and moves into Picking — cross-checks every line item's real stock
// at the order's own location and flags anything short as Unavailable
// upfront, instead of the picker discovering each gap one item at a time
// mid-pick. Doesn't auto-pick a substitute (that still needs a human to
// confirm via the substitute picker) — just surfaces the gap early with a
// summary toast so staff have visibility before handing it to a picker.
async function runAvailabilityPrecheck(est){
  if(!est||!Array.isArray(est.items)||!est.items.length) return;
  const locationId=est.locationId||'';
  let flagged=0;
  for(const it of est.items){
    if(it.isGift||it.unavailable||!it.code) continue;
    try{
      const r=await api.get(API.products+'?q='+encodeURIComponent(it.code)+(locationId?'&location_id='+encodeURIComponent(locationId):''));
      const rows=Array.isArray(r.data)?r.data:[];
      const match=rows.find(function(p){return String(p.sku||'').toUpperCase()===String(it.code).toUpperCase();})||rows[0];
      if(!match) continue;
      it.matched_id=match.id;
      const stock=locationId?(+match.display_stock||0):(+match.stock||0);
      if(stock<(+it.qty||0)){ it.unavailable=true; it.substitutes=it.substitutes||[]; flagged++; }
    }catch(e){ /* skip — don't let one lookup failure block the rest */ }
  }
  if(flagged>0){
    saveEstimateList();savePickSession();renderPickItems();
    toast(flagged+' item(s) short on stock at this location — flagged for substitution','error');
  }
}
function pickCloseSubstitutePicker(){
  _pickSubIdx=-1;_pickSubCandidates=[];_pickSubLoading=false;
  renderPickItems();
}
function pickAddSubstitute(idx,productId){
  const it=_pickItems[idx];
  if(!it)return;
  const p=_pickSubCandidates.find(function(c){return String(c.id)===String(productId);});
  if(!p)return;
  const qtyInput=document.getElementById('sub-add-qty-'+idx+'-'+productId);
  const qty=Math.max(1,Math.round(+((qtyInput&&qtyInput.value)||1)));
  it.substitutes=it.substitutes||[];
  const existing=it.substitutes.find(function(s){return String(s.product_id)===String(p.id);});
  if(existing){ existing.picked=(+existing.picked||0)+qty; }
  else { it.substitutes.push({product_id:p.id,code:p.sku||'',name:p.name||'',brand:p.brand||'',sell:+p.sell||0,picked:qty}); }
  saveEstimateList();savePickSession();renderPickItems();
}
function pickRemoveSubstitute(idx,subIdx){
  if(pickBlockedByPayment())return;
  const it=_pickItems[idx];
  if(!it||!it.substitutes)return;
  it.substitutes.splice(subIdx,1);
  saveEstimateList();savePickSession();renderPickItems();
}
function pickSubSetQty(idx,subIdx,val){
  if(pickBlockedByPayment())return;
  const it=_pickItems[idx];
  if(!it||!it.substitutes||!it.substitutes[subIdx])return;
  it.substitutes[subIdx].picked=Math.max(0,Math.round(+val||0));
  saveEstimateList();savePickSession();renderPickItems();
}
function pickSubAdjust(idx,subIdx,delta){
  const it=_pickItems[idx];
  if(!it||!it.substitutes||!it.substitutes[subIdx])return;
  pickSubSetQty(idx,subIdx,(+it.substitutes[subIdx].picked||0)+delta);
}
// Quick 'picked / not picked' toggle for a substitute row, mirroring the
// print sheet's substitute checkbox (checked when picked>0). The +/-
// stepper and number input still control the exact quantity; this
// checkbox is just a fast on/off — checking it with picked at 0 sets it
// to 1, unchecking clears it to 0. Remembers the last quantity so
// re-checking after an accidental uncheck doesn't lose it.
function pickSubToggleChecked(idx,subIdx,checked){
  if(pickBlockedByPayment())return;
  const it=_pickItems[idx];
  if(!it||!it.substitutes||!it.substitutes[subIdx])return;
  const sub=it.substitutes[subIdx];
  if(checked){
    sub.picked=(+sub._lastPicked||1)||1;
  }else{
    sub._lastPicked=+sub.picked||1;
    sub.picked=0;
  }
  saveEstimateList();savePickSession();renderPickItems();
}
function pickItemTargetAmount(it){
  return +it.amount||(+it.rate||0)*(+it.qty||0);
}
function pickSubstitutesValue(it){
  return (it.substitutes||[]).reduce(function(a,b){return a+(+b.sell||0)*(+b.picked||0);},0);
}

// Small inline stock readout next to an item's code/brand/price — screen
// only (see loadPickItemStock()'s comment for why the print sheet never
// picks this up). Blank until loadPickItemStock() resolves; red when
// stock can't cover the ordered qty, green otherwise.
function pickStockBadge(it){
  if(it.availableStock==null) return '';
  const qty=+it.qty||0;
  const short=qty>0&&it.availableStock<qty;
  return ' &middot; <b style="color:'+(short?'var(--red)':'var(--green)')+'">Stock: '+it.availableStock+'</b>';
}
function renderPickItems(){
  const grid=document.getElementById('pick-items-grid');
  if(!grid)return;
  const items=_pickItems||[];
  const totalDone=items.filter(pickItemDone).length;
  const ptEl=document.getElementById('pick-progress-text');
  if(ptEl)ptEl.textContent=totalDone+' / '+items.length+' picked';
  const pbEl=document.getElementById('pick-progress-bar');
  if(pbEl)pbEl.style.width=(items.length?Math.round(totalDone/items.length*100):0)+'%';
  const saEl=document.getElementById('pick-select-all');
  if(saEl){
    saEl.checked=_pickVerifyModeOn
      ?(items.length>0&&items.every(function(it){return !!it.itemVerified;}))
      :(items.length>0&&totalDone===items.length);
    saEl.title=_pickVerifyModeOn?'Mark all items verified':'Mark all items fully picked';
  }
  if(!items.length){
    grid.innerHTML='<div style="color:var(--text3);font-size:.85rem;text-align:center;padding:30px">No items in this order</div>';
    return;
  }
  const filtered=items.map((it,idx)=>({it,idx})).filter(({it})=>{
    const done=pickItemDone(it);
    if(_pickFilter==='pending')return !done;
    if(_pickFilter==='done')return done;
    return true;
  });
  if(!filtered.length){
    grid.innerHTML='<div style="color:var(--text3);font-size:.85rem;text-align:center;padding:30px">No items match this filter</div>';
    return;
  }
  grid.innerHTML=filtered.map(({it,idx})=>{
    const done=pickItemDone(it);
    const picked=+it.picked||0,qty=+it.qty||0;
    const fulfilled=qty>0&&picked>=qty;
    const amount=pickItemTargetAmount(it);
    const bg=it.isGift?'rgba(168,85,247,.08)':it.unavailable?'rgba(239,68,68,.06)':done?'rgba(34,197,94,.06)':'var(--surface2)';
    const bd=it.isGift?'rgba(168,85,247,.3)':it.unavailable?'rgba(239,68,68,.3)':done?'rgba(34,197,94,.3)':'var(--border2)';
    let html='<div style="background:'+bg+';border:1px solid '+bd+';border-radius:var(--radius-sm);padding:10px 12px;display:flex;flex-direction:column;gap:8px">'
      +'<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">'
      +'<div style="flex:1;min-width:160px">'
        +'<div style="font-weight:700;font-size:.85rem'+(it.unavailable?';text-decoration:line-through;color:var(--text3)':'')+'">'+esc(it.matched_name||it.name||'')+(it.isGift?' <span style="font-size:.68rem;color:#a855f7;font-weight:700">&#127873; GIFT</span>':'')+'</div>'
        +'<div style="font-size:.72rem;color:var(--text3)">'+esc(it.code||'')+(it.brand?' &middot; '+esc(it.brand):'')+(amount?' &middot; <b style="color:var(--text2)">&#8377;'+amount.toFixed(2)+'</b>':'')+pickStockBadge(it)+'</div>'
      +'</div>';
    if(!it.unavailable){
      html+='<label style="display:flex;align-items:center;gap:4px;font-size:.7rem;color:var(--text3);cursor:pointer" title="Mark full quantity picked">'
        +'<input type="checkbox" '+(fulfilled?'checked':'')+' onchange="pickSetPicked('+idx+',this.checked?'+qty+':0)" style="width:15px;height:15px;accent-color:var(--green);cursor:pointer">'
        +'Fulfil'
      +'</label>'
      +'<div style="display:flex;align-items:center;gap:6px">'
        +'<button class="btn btn-outline btn-xs" onclick="pickAdjustPicked('+idx+',-1)">&#8722;</button>'
        +'<span style="min-width:52px;text-align:center;font-weight:700;font-size:.85rem">'+picked+' / '+qty+'</span>'
        +'<button class="btn btn-outline btn-xs" onclick="pickAdjustPicked('+idx+',1)">&#43;</button>'
      +'</div>';
    }
    html+='<button class="btn btn-xs '+(it.unavailable?'btn-outline':'btn-ghost')+'" style="'+(it.unavailable?'border-color:var(--red);color:var(--red)':'color:var(--text3)')+'" onclick="pickToggleUnavailable('+idx+')">'+(it.unavailable?'&#8635; Available':'&#9888; Unavailable')+'</button>';
    if(_pickVerifyModeOn){
      // Tap-to-verify — a separate flag from 'done' (picked). Previously
      // the banner said 'Tap check mark to verify' but the only check
      // mark rendered here was the plain done-indicator below, which had
      // no click handler and was already green whenever the item was
      // picked — so tapping it did nothing and looked 'stuck checked'.
      html+='<button onclick="pickToggleItemVerified('+idx+')" title="Tap to mark verified" style="background:none;border:2px solid '+(it.itemVerified?'#a855f7':'var(--border2)')+';border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:'+(it.itemVerified?'#a855f7':'var(--text3)')+';font-size:1rem;padding:0">&#10003;</button>';
    }else{
      html+=(done?'<span style="color:var(--green);font-size:1.1rem">&#10003;</span>':'');
    }
    html+='</div>';
    if(it.unavailable){
      // Unavailable items don't get their own pick checkbox/qty controls
      // above (nothing to physically pick) — fulfillment here comes
      // entirely from the substitute(s) below, matched against this
      // item's estimate amount rather than a separate box per item.
      const subValue=pickSubstitutesValue(it);
      const diff=Math.round((amount-subValue)*100)/100;
      let diffHtml;
      if(!(it.substitutes||[]).length){
        diffHtml='<span style="color:var(--text3)">Target to match: &#8377;'+amount.toFixed(2)+'</span>';
      }else if(diff>0.01){
        diffHtml='<span style="color:var(--orange);font-weight:700">Short by &#8377;'+diff.toFixed(2)+'</span> <span style="color:var(--text3)">— add another substitute to cover it</span>';
      }else if(diff<-0.01){
        diffHtml='<span style="color:var(--accent);font-weight:700">Over by &#8377;'+(-diff).toFixed(2)+'</span>';
      }else{
        diffHtml='<span style="color:var(--green);font-weight:700">&#10003; Matched &#8377;'+amount.toFixed(2)+'</span>';
      }
      html+='<div style="border-top:1px dashed var(--border2);padding-top:8px;display:flex;flex-direction:column;gap:6px">'
        +'<div style="font-size:.78rem">'+diffHtml+'</div>';
      (it.substitutes||[]).forEach(function(sub,subIdx){
        const lineVal=(+sub.sell||0)*(+sub.picked||0);
        const subPicked=(+sub.picked||0)>0;
        html+='<div style="display:flex;align-items:center;gap:8px;background:var(--surface);border-radius:6px;padding:6px 8px;flex-wrap:wrap">'
          +'<input type="checkbox" '+(subPicked?'checked':'')+' onchange="pickSubToggleChecked('+idx+','+subIdx+',this.checked)" title="Mark this substitute as picked" style="width:16px;height:16px;accent-color:var(--green);cursor:pointer">'
          +'<div style="flex:1;min-width:120px;font-size:.78rem"><b>'+esc(sub.name||'')+'</b> <span style="color:var(--text3)">'+esc(sub.code||'')+'</span>'+(sub.sell?' <span style="color:var(--text3)">&#8377;'+sub.sell+' ea</span>':'')+'</div>'
          +'<button class="btn btn-outline btn-xs" onclick="pickSubAdjust('+idx+','+subIdx+',-1)">&#8722;</button>'
          +'<input type="number" min="0" value="'+(+sub.picked||0)+'" onchange="pickSubSetQty('+idx+','+subIdx+',this.value)" style="width:48px;text-align:center;font-size:.8rem;font-weight:700;border:1px solid var(--border2);border-radius:4px;background:var(--surface2);color:inherit">'
          +'<button class="btn btn-outline btn-xs" onclick="pickSubAdjust('+idx+','+subIdx+',1)">&#43;</button>'
          +'<span style="font-size:.78rem;color:var(--text3);min-width:70px;text-align:right">= &#8377;'+lineVal.toFixed(2)+'</span>'
          +'<button class="btn btn-ghost btn-xs" style="color:var(--red)" onclick="pickRemoveSubstitute('+idx+','+subIdx+')">&#10005;</button>'
        +'</div>';
      });
      if(_pickSubIdx===idx){
        if(_pickSubLoading){
          html+='<div style="font-size:.75rem;color:var(--text3);padding:6px 0">Finding substitutes&hellip;</div>';
        }else if(!_pickSubCandidates.length){
          html+='<div style="font-size:.75rem;color:var(--text3);padding:6px 0">No matching products found for this item\'s category.</div>'
            +'<button class="btn btn-ghost btn-xs" onclick="pickCloseSubstitutePicker()">Close</button>';
        }else{
          html+='<div style="display:flex;flex-direction:column;gap:4px;max-height:200px;overflow-y:auto">'
            +_pickSubCandidates.map(function(p){
              return '<div style="display:flex;align-items:center;gap:8px;font-size:.78rem;padding:4px 6px;border-radius:6px;background:var(--surface)">'
                +'<div style="flex:1">'+esc(p.name||'')+' <span style="color:var(--text3)">'+esc(p.sku||'')+'</span>'+(p.sell?' <span style="color:var(--text3)">&#8377;'+p.sell+'</span>':'')+(p.stock!==undefined?' <span style="color:var(--text3)">&middot; stock '+p.stock+'</span>':'')+'</div>'
                +'<input type="number" id="sub-add-qty-'+idx+'-'+p.id+'" min="1" value="1" style="width:44px;text-align:center;font-size:.75rem;border:1px solid var(--border2);border-radius:4px;background:var(--surface2);color:inherit">'
                +'<button class="btn btn-primary btn-xs" onclick="pickAddSubstitute('+idx+','+p.id+')">+ Add</button>'
              +'</div>';
            }).join('')
          +'</div>'
          +'<button class="btn btn-ghost btn-xs" style="align-self:flex-start" onclick="pickCloseSubstitutePicker()">Close</button>';
        }
      }else{
        html+='<button class="btn btn-outline btn-xs" style="align-self:flex-start" onclick="pickOpenSubstitutePicker('+idx+')">+ Find substitute</button>';
      }
      html+='</div>';
    }
    html+='</div>';
    return html;
  }).join('');
}

function pickSelectAll(checked){
  if(pickBlockedByPayment())return;
  if(_pickVerifyModeOn){
    // In Verification Mode, Select All ticks/unticks every item's
    // verified flag rather than touching picked quantities.
    (_pickItems||[]).forEach(it=>{ it.itemVerified=checked; });
  }else{
    (_pickItems||[]).forEach(it=>{ if(!it.unavailable) it.picked=checked?(+it.qty||0):0; });
  }
  saveEstimateList();savePickSession();renderPickItems();
}

function filterPickList(f){
  _pickFilter=f;
  ['all','pending','done','short'].forEach(id=>{
    const btn=document.getElementById('pf-'+id);
    if(!btn)return;
    btn.className='btn btn-sm '+(f===id?'btn-primary':'btn-outline');
    if(id==='short'){btn.style.borderColor=f==='short'?'':'var(--orange)';btn.style.color=f==='short'?'':'var(--orange)';}
  });
  renderPickItems();
}

function printPickSheet(mode){
  // The main picking sheet (mode 'picking', the header Print button) is
  // only meant to be printable while the order is actually in the
  // Picking stage -- not before payment clears, and not once picking is
  // already done. This mirrors the disabled state updatePickLockState()
  // sets on #pick-print-btn, but checked here too since this function is
  // reachable directly, not just through that button. The separate
  // 'checking'-mode print (verification checklist, CAN_VERIFY-only) is
  // unaffected.
  if(mode==='picking' && _pickStatus!=='picking'){
    toast(_pickStatus==='pending'?'Payment not recorded — record payment before picking begins':'Printing is only available while the order is in the Picking stage','error');
    return;
  }
  var items=_pickItems,orderNo=_pickOrderNo||'--',customer=_pickCustomer||'--';
  var phone=document.getElementById('pick-phone')?.value||'--';
  var address=typeof _pickAddress!=='undefined'?_pickAddress:'';
  var picker=CURRENT_USER||'--',now=new Date().toLocaleString('en-IN'),isC=mode==='checking';
  var rows=items.map(function(it,i){
    var hasSubs=it.substitutes&&it.substitutes.length;
    var subValue=hasSubs?it.substitutes.reduce(function(s,sub){return s+(+sub.sell||0)*(+sub.picked||0);},0):0;
    var amount=+it.amount||(+it.rate||0)*(+it.qty||0);
    var done=it.unavailable?(amount>0?subValue>=amount:hasSubs&&it.substitutes.reduce(function(s,sub){return s+(+sub.picked||0);},0)>=it.qty):it.picked>=it.qty;
    var picked=it.picked;
    var amtTag=amount?' <span style="font-size:10px;color:#e65">Rs.'+amount.toFixed(2)+'</span>':'';
    var stCell,vCell;
    if(it.unavailable){
      // Unavailable — nothing to physically check off on the original
      // line (it's being replaced), so this row carries no checkboxes
      // of its own. The substitute row(s) below carry the one set of
      // checkboxes for this line, instead of showing two checkable
      // boxes for what's really one item being fulfilled.
      stCell='<td style="text-align:center;font-size:10px;color:#999">see below</td>';
      vCell='<td></td>';
    }else{
      stCell=isC?'<td style="text-align:center;font-weight:700;color:'+(picked>=it.qty?'green':picked>0?'orange':'red')+'">'+picked+'/'+it.qty+'</td>':'<td style="text-align:center"><input type="checkbox" '+(done?'checked':'')+'></td>';
      // 'Verified' column — always printed empty (a physical checkbox
      // for whoever verifies the packed order to tick by hand),
      // adjacent to the existing Picked/Done column above.
      vCell='<td style="text-align:center"><input type="checkbox"></td>';
    }
    var subR='';
    if(hasSubs)it.substitutes.forEach(function(sub){
      var subOk=(+sub.picked||0)>0;
      var subStCell=isC?'<td style="text-align:center;font-size:11px;font-weight:700;color:'+((sub.picked||0)>=it.qty?'green':subOk?'orange':'red')+'">'+(sub.picked||0)+'/'+it.qty+'</td>':'<td style="text-align:center"><input type="checkbox" '+((sub.picked||0)>=it.qty?'checked':'')+'></td>';
      subR+='<tr style="background:#fffbf0"><td></td><td style="padding-left:16px;font-size:11px">&#8627; SUB: '+(sub.code||'')+' '+(sub.name||'')+(sub.sell?' <span style="color:#e65">Rs.'+sub.sell+' x '+(+sub.picked||0)+' = Rs.'+((+sub.sell||0)*(+sub.picked||0)).toFixed(2)+'</span>':'')+'</td><td style="text-align:center;font-size:11px">'+it.qty+'</td>'+subStCell+'<td style="text-align:center"><input type="checkbox"></td></tr>';
    });
    var bg=it.unavailable?'#fff5f5':done?'#f0fff4':'white';
    var sk=it.unavailable?'text-decoration:line-through;color:#999':'';
    return '<tr style="background:'+bg+';border-bottom:1px solid #eee"><td style="text-align:center;font-size:11px;color:#666">'+(i+1)+'</td><td style="'+sk+'"><b style="font-size:10px;color:#555">'+esc(it.code||'')+'</b>'+(it.brand?' <i style="font-size:10px;color:#888">'+esc(it.brand)+'</i>':'')+ ' '+esc(it.matched_name||it.name||'')+amtTag+'</td><td style="text-align:center;font-weight:700">'+it.qty+'</td>'+stCell+vCell+'</tr>'+subR;
  }).join('');
  var tot=items.length,pic=items.filter(function(it){return it.unavailable?(it.substitutes||[]).reduce(function(s,sub){return s+(sub.picked||0);},0)>=it.qty:it.picked>=it.qty;}).length;
  var html='<!DOCTYPE html><html><head><meta charset="utf-8"><title>'+(isC?'Checking':'Picking')+' - '+orderNo+'</title>'
    +'<style>body{font-family:Arial,sans-serif;font-size:13px;padding:14px} .hdr{display:flex;justify-content:space-between;border-bottom:2px solid #333;padding-bottom:8px;margin-bottom:10px} .meta{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;background:#f8f8f8;padding:8px 12px;border-radius:4px;margin-bottom:8px;font-size:12px} .meta b{display:block;font-size:10px;color:#888;font-weight:400} .addr{background:#e8f0ff;padding:6px 12px;border-radius:4px;margin-bottom:8px;font-size:12px} table{width:100%;border-collapse:collapse;margin-bottom:10px} th{background:#333;color:#fff;padding:6px 8px;text-align:left;font-size:10px} td{padding:6px 8px;vertical-align:middle} .sign{display:flex;gap:40px;margin-top:18px} .sign-box{flex:1;border-top:1px solid #999;padding-top:6px;font-size:10px;color:#666;text-align:center} @media print{button{display:none}}</style></head><body>'
    +'<div class="hdr"><div><h1 style="font-size:16px">'+(isC?'Checking Sheet':'Picking Sheet')+'</h1><div style="font-size:10px;color:#666">RR Crackers</div></div>'
    +'<div style="text-align:right;font-size:10px;color:#666">Printed: '+now+'<br>'+(isC?'Checker':'Picker')+': <b>'+esc(picker)+'</b></div></div>'
    +'<div class="meta"><div><b>Estimate</b>'+esc(orderNo)+'</div><div><b>Customer</b>'+esc(customer)+'</div><div><b>Phone</b>'+esc(phone)+'</div></div>'
    +(address?'<div class="addr"><b style="font-size:10px;color:#556;display:block">DISPATCH ADDRESS</b>'+esc(address)+'</div>':'')
    +'<table><thead><tr><th style="width:30px">#</th><th>Product</th><th style="width:50px;text-align:center">Qty</th><th style="width:80px;text-align:center">'+(isC?'Picked/Ord':'Done')+'</th><th style="width:70px;text-align:center">Verified</th></tr></thead><tbody>'+rows+'</tbody></table>'
    +'<div class="sign"><div class="sign-box">Picker</div><div class="sign-box">Checker</div><div class="sign-box">Packer</div></div>'
    +'<'+'script>window.onload=function(){window.print();};<\/script></body></html>';
  var w=window.open('','_blank','width=800,height=1000');
  if(!w){toast('Allow popups to print','error');return;}
  w.document.write(html);w.document.close();
}
function sendWhatsApp(){
  var ph=document.getElementById('pick-phone')?.value||'';
  if(!ph){toast('No phone number','error');return;}
  var cl=ph.replace(/\D/g,''),intl=cl.startsWith('91')?cl:'91'+cl;
  var msg='Dear '+(_pickCustomer||'Customer')+', your order *'+(_pickOrderNo||'')+'* is ready. Thank you! - RR Crackers';
  window.open('https://wa.me/'+intl+'?text='+encodeURIComponent(msg),'_blank');
}
async function setPickStatus(status){
  if(typeof _pickStatus==='undefined')return false;
  // A Pending order can only move to ANY later stage once payment is
  // fully recorded — not just the Picking pill specifically. Earlier this
  // only checked status==='picking', which blocked that one pill but left
  // completePicking() (Complete button, pending -> straight to
  // verification) and the Verification/Packing/Dispatched pills free to
  // skip the gate entirely. Every path that advances a pending order
  // funnels through here, so blocking any pending->(anything but pending)
  // transition closes all of them at once.
  if(_pickStatus==='pending' && status!=='pending'){
    const paid = await isOrderFullyPaid(_pickOrderNo);
    if(!paid){
      toast('Payment not recorded — record payment before picking begins','error');
      if(CAN_VERIFY) openEstimatePayment(_pickActiveId);
      return false;
    }
  }
  // Picker (and anyone else who isn't allowed to verify) may pick an
  // order and hand it to verification, but must not be able to skip the
  // actual verification step by jumping the stage pill straight to
  // Packing -- that stage is only meant to be reachable once someone
  // with CAN_VERIFY has verified the order (completeVerificationInList()/
  // confirmVerification() set est.verified=true). Dispatched has its own
  // equivalent check in openDispatchModal() since it bypasses this
  // function entirely.
  if(!CAN_VERIFY && status==='packing'){
    const packEst=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
    if(!packEst || !packEst.verified){
      toast('Order must be verified before it can move to Packing','error');
      return false;
    }
  }
  const wasPending=_pickStatus==='pending';
  _pickStatus=status;
  document.querySelectorAll('.pst-btn').forEach(function(btn){btn.style.background='var(--surface)';btn.style.color='var(--text2)';btn.style.borderColor='var(--border2)';btn.style.fontWeight='400';});
  var cm={pending:{bg:'rgba(148,163,184,.2)',c:'var(--text2)'},picking:{bg:'rgba(249,115,22,.15)',c:'var(--orange)'},verification:{bg:'rgba(234,179,8,.15)',c:'#ca8a04'},packing:{bg:'rgba(79,142,255,.15)',c:'var(--accent)'},dispatched:{bg:'rgba(34,197,94,.15)',c:'var(--green)'}};
  var ab=document.getElementById('pst-'+status);
  if(ab){var cl2=cm[status]||cm.pending;ab.style.background=cl2.bg;ab.style.color=cl2.c;ab.style.borderColor=cl2.c;ab.style.fontWeight='700';}
  var est=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
  // Picking-completion time: stamped once, the moment an order first
  // reaches 'verification' (i.e. picking is done), then left alone on
  // every later stage change — kept separate from verified_at
  // (verification completion, stamped in completeVerificationInList()/
  // confirmVerification()).
  var pkCompletedAt=est?(est.pickingCompletedAt||''):'';
  if(!pkCompletedAt&&status==='verification') pkCompletedAt=Date.now();
  if(est){
    est.status=status;
    if(pkCompletedAt) est.pickingCompletedAt=pkCompletedAt;
    try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  }
  // Picker is stamped once when the order first leaves 'pending', then
  // locked — this stage-pill transition must never reassign it to
  // whoever happens to click the pill.
  var pkPicker=est?(est.picker||''):'';
  if(!pkPicker&&status!=='pending') pkPicker=CURRENT_USER;
  await syncPickSessionToServer({id:_pickActiveId,orderNo:_pickOrderNo,customer:_pickCustomer,
    phone:document.getElementById('pick-phone')?.value||'',address:_pickAddress||'',
    picker:pkPicker,items:_pickItems,status:status,
    verified:est?!!est.verified:false,verifiedBy:est?(est.verifiedBy||''):'',
    verifiedAt:est?(est.verifiedAt||''):'',
    shipDate:est?(est.shipDate||''):'',transportName:est?(est.transportName||''):'',boxCount:est?(est.boxCount||''):'',
    pickingCompletedAt:pkCompletedAt||''});
  updateShipInfoDisplay(est);
  if(typeof updatePickLockState==='function') updatePickLockState();
  // Payment just cleared and this order is entering Picking for the first
  // time — kick off the stock availability check in the background (not
  // awaited, so it doesn't hold up the picker's screen from opening).
  if(wasPending && status==='picking' && est && typeof runAvailabilityPrecheck==='function') runAvailabilityPrecheck(est);
  return true;
}

async function completePicking(){
  // The single 'Complete' button in the header is shared by every
  // stage of this screen — previously it always ran the picker's
  // 'hand off to verification' action regardless of current status,
  // so a checker clicking it after tapping through Verification Mode
  // just got sent right back to 'verification' with the picker's
  // toast, instead of actually completing verification and moving to
  // Packing. Branch on the current stage instead.
  if(_pickStatus==='verification'){
    completeVerificationInList();
    return;
  }
  // setPickStatus() blocks pending->verification when payment isn't
  // recorded yet (returns false without changing anything) — wait for it
  // and bail out here too, otherwise this would navigate away and show a
  // 'Picking done' toast even though the order never actually advanced.
  const ok = typeof setPickStatus==='function' && await setPickStatus('verification');
  if(!ok) return;
  showPickDashboard();
  toast('Picking done — checker can verify from the dashboard');
}

async function completeVerificationInList(){
  if(!CAN_VERIFY){toast('You do not have permission to verify orders','error');return;}
  const items=_pickItems||[];
  const allVerified=items.length>0&&items.every(function(it){return !!it.itemVerified;});
  if(!allVerified&&!confirm('Not all items are tapped as verified. Mark this order verified anyway?'))return;
  const est=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
  const verifiedAtNow=Date.now();
  if(est){est.verified=true;est.verifiedBy=CURRENT_USER;est.verifiedAt=verifiedAtNow;est.status='packing';}
  _pickStatus='packing';
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  // The picker who picked this order is locked in — verifying it must not
  // reassign 'picked by' to the verifier.
  const lockedPicker=est?(est.picker||CURRENT_USER):CURRENT_USER;
  try{
    await api.post(API.pickingSessions,{id:_pickActiveId,orderNo:_pickOrderNo,customer:_pickCustomer,
      phone:document.getElementById('pick-phone')?.value||'',address:_pickAddress||'',
      picker:lockedPicker,items:items,status:'packing',
      verified:1,verifiedBy:CURRENT_USER,verifiedAt:verifiedAtNow,
      shipDate:est?(est.shipDate||''):'',transportName:est?(est.transportName||''):'',boxCount:est?(est.boxCount||''):'',
      pickingCompletedAt:est?(est.pickingCompletedAt||''):'',date:d});
  }catch(e){
    toast('Could not save verification: '+e.message,'error');
    return;
  }
  showPickDashboard();
  toast('Order verified — moved to Packing');
}

function syncPickSessionToServer(session){
  if(!session||!session.id)return Promise.resolve();
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  // Returns the request promise (always resolving, never rejecting -- the
  // .catch below turns a failed save into a resolved outcome) so callers
  // that need the write to have actually landed before doing anything else
  // -- like navigating away, which can trigger a GET that would otherwise
  // race ahead of this POST -- can await it.
  return api.post(API.pickingSessions,{id:session.id,orderNo:session.orderNo,customer:session.customer,
    phone:session.phone||'',address:session.address||'',picker:session.picker||'',
    items:session.items||[],status:session.status||_pickStatus||'pending',
    verified:session.verified?1:0,verifiedBy:session.verifiedBy||'',verifiedAt:session.verifiedAt||'',
    shipDate:session.shipDate||'',transportName:session.transportName||'',boxCount:session.boxCount||'',
    pickingCompletedAt:session.pickingCompletedAt||'',date:d})
  .then(()=>{_pickServerOk=true;const el=document.getElementById('pick-sync-status');if(el){el.style.display='';el.innerHTML='&#9679; Live';el.style.color='var(--green)';}})
  .catch(()=>{_pickServerOk=false;const el=document.getElementById('pick-sync-status');if(el){el.style.display='';el.innerHTML='&#9650; Offline';el.style.color='var(--orange)';}});
}

// ── Verify screen (code-based) ──────────────────────────────────────
// Gated behind CAN_VERIFY (admin/manager/partner) — see the '✓✓ Verify'
// button, the Verify screen's code entry, and confirmVerification().
let _verifyRow=null, _verifyItems=[], _verifyChecks=[], _verifyOrderId=null;

function generateVerifyCode(){
  if(!_pickActiveId){toast('No active order','error');return;}
  const code=Math.random().toString(36).slice(2,7).toUpperCase();
  const est=_pickEstimates.find(function(e){return e.id===_pickActiveId;});
  if(est)est.verifyCode=code;
  try{localStorage.setItem(PICK_LIST_KEY,JSON.stringify(_pickEstimates));}catch(e){}
  const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
  let vcPicker=est?(est.picker||''):'';
  if(!vcPicker&&_pickStatus&&_pickStatus!=='pending') vcPicker=CURRENT_USER;
  api.post(API.pickingSessions,{id:_pickActiveId,orderNo:_pickOrderNo,customer:_pickCustomer,
    phone:document.getElementById('pick-phone')?.value||'',address:_pickAddress||'',
    picker:vcPicker,items:_pickItems,status:_pickStatus||'pending',
    verified:est?!!est.verified:false,verifiedBy:est?(est.verifiedBy||''):'',verifiedAt:est?(est.verifiedAt||''):'',
    shipDate:est?(est.shipDate||''):'',transportName:est?(est.transportName||''):'',boxCount:est?(est.boxCount||''):'',
    pickingCompletedAt:est?(est.pickingCompletedAt||''):'',
    verifyCode:code,date:d}).catch(function(){});
  const box=document.getElementById('pick-verify-code-box');
  const disp=document.getElementById('pick-verify-code-display');
  const copyBtn=document.getElementById('pick-copy-code-btn');
  if(disp)disp.textContent=code;
  if(box)box.style.display='';
  if(copyBtn)copyBtn.style.display='';
  toast('Verification code generated');
}
function copyVerifyCode(){
  const disp=document.getElementById('pick-verify-code-display');
  const code=disp?disp.textContent:'';
  if(!code)return;
  if(navigator.clipboard&&navigator.clipboard.writeText){
    navigator.clipboard.writeText(code).then(function(){toast('Code copied');}).catch(function(){toast('Could not copy','error');});
  }else{
    toast('Copy not supported on this browser','error');
  }
}
async function openVerifyByCode(){
  if(!CAN_VERIFY){toast('You do not have permission to verify orders','error');return;}
  const codeInput=document.getElementById('pick-enter-code');
  const code=(codeInput&&codeInput.value||'').trim().toUpperCase();
  if(!code){toast('Enter a code','error');return;}
  try{
    const r=await api.get(API.pickingSessions+'?code='+encodeURIComponent(code));
    const row=Array.isArray(r.data)?r.data[0]:r.data;
    if(!row||!row.id){toast('No order found for that code','error');return;}
    openVerifyScreen(row);
  }catch(e){
    toast('Could not look up code: '+e.message,'error');
  }
}
function openVerifyScreen(row){
  _verifyRow=row;
  _verifyOrderId=row.id;
  const items=(typeof row.data==='string')?(JSON.parse(row.data||'[]')||[]):(row.data||[]);
  _verifyItems=items;
  _verifyChecks=items.map(function(it){return !!it.itemVerified;});
  _verifyGiftResults=[];
  const searchEl=document.getElementById('verify-gift-search');if(searchEl)searchEl.value='';
  const giftResultsEl=document.getElementById('verify-gift-results');if(giftResultsEl)giftResultsEl.innerHTML='';
  const summaryEl=document.getElementById('pick-verify-summary');
  if(summaryEl)summaryEl.innerHTML='<b>'+esc(row.order_no||'')+'</b>'+(row.customer?' &middot; '+esc(row.customer):'')+(row.phone?' &middot; '+esc(row.phone):'');
  renderVerifyItems();
  const badge=document.getElementById('pick-verified-badge');
  const byEl=document.getElementById('pick-verified-by');
  if(row.verified){if(badge)badge.style.display='';if(byEl)byEl.textContent=row.verified_by||'';}
  else if(badge){badge.style.display='none';}
  const nameEl=document.getElementById('pick-verifier-name');if(nameEl)nameEl.value='';
  ['pick-dashboard','pick-upload-card','pick-list-area','pick-complete-screen'].forEach(function(id){
    var el=document.getElementById(id);if(el)el.style.display='none';
  });
  clearInterval(window._pickRefreshTimer);
  const vs=document.getElementById('pick-verify-screen');if(vs)vs.style.display='';
}
function renderVerifyItems(){
  const el=document.getElementById('pick-verify-items');
  if(!el)return;
  if(!_verifyItems.length){el.innerHTML='<div style="color:var(--text3);font-size:.85rem;text-align:center;padding:20px">No items</div>';return;}
  el.innerHTML=_verifyItems.map(function(it,i){
    const picked=+it.picked||0,qty=+it.qty||0;
    const checked=!!_verifyChecks[i];
    const amount=+it.amount||(+it.rate||0)*qty;
    return '<div style="display:flex;align-items:center;gap:10px;background:'+(it.isGift?'rgba(168,85,247,.08)':'var(--surface2)')+';border-radius:var(--radius-sm);padding:8px 12px">'
      +'<div style="flex:1"><b style="font-size:.85rem">'+esc(it.matched_name||it.name||'')+'</b> <span style="font-size:.72rem;color:var(--text3)">'+esc(it.code||'')+'</span>'+(it.isGift?' <span style="font-size:.68rem;color:#a855f7;font-weight:700">&#127873; GIFT</span>':'')+(amount?' <span style="font-size:.72rem;color:var(--text3)">&middot; &#8377;'+amount.toFixed(2)+'</span>':'')+'</div>'
      +'<span style="font-size:.8rem;color:var(--text3)">'+picked+' / '+qty+'</span>'
      +'<label style="display:flex;align-items:center;gap:5px;font-size:.75rem;cursor:pointer">'
        +'<input type="checkbox" '+(checked?'checked':'')+' onchange="toggleVerifyItemCheck('+i+',this.checked)" style="width:16px;height:16px;accent-color:#a855f7;cursor:pointer">Verified'
      +'</label>'
    +'</div>';
  }).join('');
}
function toggleVerifyItemCheck(i,checked){
  _verifyChecks[i]=checked;
}

// ── Verifier: add gift / complimentary items ────────────────────────
// Extra items outside the original estimate that the verifier can add
// while packing/checking (e.g. a small compliment) — they get folded
// into the same items array that's saved back to the order, tagged
// isGift so they're visually distinguishable from estimate items.
let _verifyGiftResults=[];
async function searchVerifyGiftProduct(){
  if(!CAN_VERIFY)return;
  const input=document.getElementById('verify-gift-search');
  const q=(input&&input.value||'').trim();
  const resultsEl=document.getElementById('verify-gift-results');
  if(!q){if(resultsEl)resultsEl.innerHTML='';_verifyGiftResults=[];return;}
  try{
    const r=await api.get(API.products+'?q='+encodeURIComponent(q));
    _verifyGiftResults=Array.isArray(r.data)?r.data.slice(0,15):[];
    renderVerifyGiftResults();
  }catch(e){
    if(resultsEl)resultsEl.innerHTML='<div style="color:var(--red);font-size:.75rem">Search failed</div>';
  }
}
function renderVerifyGiftResults(){
  const el=document.getElementById('verify-gift-results');
  if(!el)return;
  if(!_verifyGiftResults.length){el.innerHTML='<div style="color:var(--text3);font-size:.75rem">No matches</div>';return;}
  el.innerHTML=_verifyGiftResults.map(function(p){
    return '<div style="display:flex;align-items:center;gap:8px;font-size:.78rem;padding:4px 6px;border-radius:6px;background:var(--surface)">'
      +'<div style="flex:1">'+esc(p.name||'')+' <span style="color:var(--text3)">'+esc(p.sku||'')+'</span>'+(p.sell?' <span style="color:var(--text3)">&#8377;'+p.sell+'</span>':'')+'</div>'
      +'<input type="number" id="gift-qty-'+p.id+'" min="1" value="1" style="width:44px;text-align:center;font-size:.75rem;border:1px solid var(--border2);border-radius:4px;background:var(--surface2);color:inherit">'
      +'<button class="btn btn-primary btn-xs" onclick="addVerifyGiftItem('+p.id+')">&#127873; Add Gift</button>'
    +'</div>';
  }).join('');
}
function addVerifyGiftItem(productId){
  if(!CAN_VERIFY)return;
  const p=_verifyGiftResults.find(function(x){return String(x.id)===String(productId);});
  if(!p)return;
  const qtyInput=document.getElementById('gift-qty-'+productId);
  const qty=Math.max(1,Math.round(+((qtyInput&&qtyInput.value)||1)));
  _verifyItems.push({code:p.sku||'',name:p.name||'',matched_name:p.name||'',brand:p.brand||'',
    qty:qty,picked:qty,rate:+p.sell||0,amount:(+p.sell||0)*qty,unavailable:false,substitutes:[],
    matched_id:p.id||null,isGift:true});
  _verifyChecks.push(false);
  const searchInput=document.getElementById('verify-gift-search');if(searchInput)searchInput.value='';
  const resultsEl=document.getElementById('verify-gift-results');if(resultsEl)resultsEl.innerHTML='';
  _verifyGiftResults=[];
  renderVerifyItems();
  toast(p.name+' added as gift');
}
async function confirmVerification(){
  if(!CAN_VERIFY){toast('You do not have permission to verify orders','error');return;}
  if(!_verifyOrderId||!_verifyRow){toast('No order loaded','error');return;}
  const nameEl=document.getElementById('pick-verifier-name');
  const name=(nameEl&&nameEl.value||'').trim();
  if(!name){toast('Enter your name','error');return;}
  const allChecked=_verifyChecks.length>0&&_verifyChecks.every(Boolean);
  if(!allChecked&&!confirm('Not all items are checked as verified. Confirm anyway?'))return;
  try{
    const itemsOut=_verifyItems.map(function(it,i){return Object.assign({},it,{itemVerified:!!_verifyChecks[i]});});
    const d=(function(){var n=new Date();return n.getFullYear()+'-'+String(n.getMonth()+1).padStart(2,'0')+'-'+String(n.getDate()).padStart(2,'0');})();
    const verifiedAtNow=Date.now();
    await api.post(API.pickingSessions,{id:_verifyOrderId,orderNo:_verifyRow.order_no||'',
      customer:_verifyRow.customer||'',phone:_verifyRow.phone||'',address:_verifyRow.address||'',
      picker:_verifyRow.picker||'',items:itemsOut,verified:1,verifiedBy:name,verifiedAt:verifiedAtNow,
      shipDate:_verifyRow.ship_date||'',transportName:_verifyRow.transport_name||'',boxCount:_verifyRow.box_count||'',
      pickingCompletedAt:_verifyRow.picking_completed_at||'',
      status:_verifyRow.status||'packing',date:d});
    _verifyRow.verified=1;_verifyRow.verified_by=name;_verifyRow.verified_at=verifiedAtNow;
    const badge=document.getElementById('pick-verified-badge');
    const byEl=document.getElementById('pick-verified-by');
    if(badge)badge.style.display='';
    if(byEl)byEl.textContent=name;
    toast('Order verified');
  }catch(e){
    toast('Could not save verification: '+e.message,'error');
  }
}
// ── Verifier (in-list Verification Mode): add gift / complimentary
// items ──────────────────────────────────────────────────────────────
// Same idea as the code-based Verify screen's gift-add box
// (searchVerifyGiftProduct/addVerifyGiftItem), but for the in-list
// Verification Mode on the main Picking/Verifying screen — extra items
// outside the original estimate that the verifier adds while checking
// (e.g. a small compliment), folded into _pickItems and tagged isGift
// so they're visually distinguishable from the estimate's own items.
let _pickGiftResults=[];
async function searchPickGiftProduct(){
  if(!CAN_VERIFY)return;
  const input=document.getElementById('pick-gift-search');
  const q=(input&&input.value||'').trim();
  const resultsEl=document.getElementById('pick-gift-results');
  if(!q){if(resultsEl)resultsEl.innerHTML='';_pickGiftResults=[];return;}
  try{
    const r=await api.get(API.products+'?q='+encodeURIComponent(q));
    _pickGiftResults=Array.isArray(r.data)?r.data.slice(0,15):[];
    renderPickGiftResults();
  }catch(e){
    if(resultsEl)resultsEl.innerHTML='<div style="color:var(--red);font-size:.75rem">Search failed</div>';
  }
}
function renderPickGiftResults(){
  const el=document.getElementById('pick-gift-results');
  if(!el)return;
  if(!_pickGiftResults.length){el.innerHTML='<div style="color:var(--text3);font-size:.75rem">No matches</div>';return;}
  el.innerHTML=_pickGiftResults.map(function(p){
    return '<div style="display:flex;align-items:center;gap:8px;font-size:.78rem;padding:4px 6px;border-radius:6px;background:var(--surface)">'
      +'<div style="flex:1">'+esc(p.name||'')+' <span style="color:var(--text3)">'+esc(p.sku||'')+'</span>'+(p.sell?' <span style="color:var(--text3)">&#8377;'+p.sell+'</span>':'')+'</div>'
      +'<input type="number" id="pick-gift-qty-'+p.id+'" min="1" value="1" style="width:44px;text-align:center;font-size:.75rem;border:1px solid var(--border2);border-radius:4px;background:var(--surface2);color:inherit">'
      +'<button class="btn btn-primary btn-xs" onclick="addPickGiftItem('+p.id+')">&#127873; Add Gift</button>'
    +'</div>';
  }).join('');
}
function addPickGiftItem(productId){
  if(!CAN_VERIFY)return;
  const p=_pickGiftResults.find(function(x){return String(x.id)===String(productId);});
  if(!p)return;
  const qtyInput=document.getElementById('pick-gift-qty-'+productId);
  const qty=Math.max(1,Math.round(+((qtyInput&&qtyInput.value)||1)));
  _pickItems.push({code:p.sku||'',name:p.name||'',matched_name:p.name||'',brand:p.brand||'',
    qty:qty,picked:qty,rate:+p.sell||0,amount:(+p.sell||0)*qty,unavailable:false,substitutes:[],
    matched_id:p.id||null,isGift:true,itemVerified:false});
  const searchInput=document.getElementById('pick-gift-search');if(searchInput)searchInput.value='';
  const resultsEl=document.getElementById('pick-gift-results');if(resultsEl)resultsEl.innerHTML='';
  _pickGiftResults=[];
  saveEstimateList();savePickSession();renderPickItems();
  toast(p.name+' added as gift');
}
function toggleVerifyMode(){
  if(!CAN_VERIFY){toast('You do not have permission to verify orders','error');return;}
  _pickVerifyModeOn=!_pickVerifyModeOn;
  const banner=document.getElementById('pick-verify-banner');
  const btn=document.getElementById('pick-verify-btn');
  if(banner)banner.style.display=_pickVerifyModeOn?'':'none';
  if(btn){btn.classList.toggle('btn-primary',_pickVerifyModeOn);btn.classList.toggle('btn-outline',!_pickVerifyModeOn);}
  renderPickItems();
}
function pickToggleItemVerified(idx){
  if(!CAN_VERIFY||!_pickVerifyModeOn)return;
  const it=_pickItems[idx];
  if(!it)return;
  it.itemVerified=!it.itemVerified;
  saveEstimateList();savePickSession();renderPickItems();
}

</script>
</body>
</html>