<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payslip Generation - Payroll - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <style>
    body{font-family:Poppins,system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
    .box{background:#fff;border-radius:10px;padding:16px;margin-bottom:16px}
    table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden}
    th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
    thead{background:#0f172a;color:#38bdf8}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .row>*{flex:1 1 200px}
    input,select,button{padding:8px;border:1px solid #d1d5db;border-radius:8px;background:#fff}
    .num{text-align:right}
    .btn{background:#38bdf8;color:#0f172a;border-color:#38bdf8;cursor:pointer}

    /* Sidebar enhancements */
    .sidebar-group .arrow{transition:transform .2s}
    .sidebar-group.open .arrow{transform:rotate(90deg)}
    .submenu{display:none;margin:0;padding-left:0;list-style:none}
    .sidebar-group.open .submenu{display:block}
    .submenu a{display:block;padding:8px 10px;border-radius:8px;margin:4px 8px;color:#0f172a;text-decoration:none}
    .submenu a.active{background:#e0f2fe;color:#0c4a6e;font-weight:600}
    .sidebar-toggle{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;text-decoration:none;color:#0f172a}
    .sidebar-toggle .left{display:flex;gap:8px;align-items:center}
    .sidebar-toggle:hover{background:#f1f5f9;border-radius:10px}
    .muted{color:#6b7280}
  </style>
</head>
<body>
<header>
  <div class="title">Web-Based HRMS</div>
  <div class="user-info">
    <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; HR Admin
    </a>
</div>
</header>

<div class="container">
  @include('admin.layout.sidebar')

  <main>
    <div class="breadcrumb">Home > Payroll > Payslip Generation</div>
    <h2>Payslip Generation</h2>
    <p class="subtitle">Pick an employee and period, then preview a clean payslip.</p>

    <div class="box">
      <div class="row">
        <div>
          <label>Employee</label>
          <select id="emp">
            <!-- Single source of truth (your data) -->
            <option value="EMP001|John Tan|IT|4500|300|6|20|50|350">John Tan (IT)</option>
            <option value="EMP002|Alicia Wong|Finance|4200|150|2|22|0|320">Alicia Wong (Finance)</option>
            <option value="EMP003|Marcus Lim|HR|3800|100|0|18|90|290">Marcus Lim (HR)</option>
            <option value="EMP004|Chen Wei|Marketing|4000|200|5|19|30|305">Chen Wei (Marketing)</option>
          </select>
        </div>
        <div>
          <label>Start</label>
          <input type="date" id="start">
        </div>
        <div>
          <label>End</label>
          <input type="date" id="end">
        </div>
        <div style="align-self:end">
          <button class="btn" id="preview"><i class="fa-solid fa-file-invoice"></i> Preview</button>
        </div>
      </div>
    </div>

    <div class="box">
      <table id="tbl">
        <thead>
          <tr>
            <th>Employee</th><th>Department</th>
            <th class="num">Base</th><th class="num">Allowance</th><th class="num">OT Pay</th>
            <th class="num">Penalty</th><th class="num">EPF/Tax</th><th class="num">Net</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>

    <!-- Inline payslip preview (modal removed) -->
    <div class="box" id="slipBox">
      <h3 style="margin:0 0 8px">Payslip Preview</h3>
      <div id="slip" class="muted">Select an employee and period, then click <strong>Preview</strong>.</div>
    </div>

    <footer>© 2025 Web-Based HRMS. All Rights Reserved.</footer>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  /* ==========================================
     SIDEBAR — single active, single open
     ========================================== */
  const groups  = document.querySelectorAll('.sidebar-group');
  const toggles = document.querySelectorAll('.sidebar-toggle');
  const links   = document.querySelectorAll('.submenu a');
  const STORAGE_KEY = 'hrms_sidebar_open_group';

  // Normalize a URL to a consistent path we can compare
  const normPath = (u) => {
    const url = new URL(u, location.origin);
    // strip /index.php (Laravel), trailing slashes, query, hash
    let p = url.pathname.replace(/\/index\.php/,'').replace(/\/+$/,'');
    return p === '' ? '/' : p;
  };

  const here = normPath(location.href);

  // 0) Clear any pre-rendered states to avoid double-highlighting
  groups.forEach(g => {
    g.classList.remove('open');
    const t = g.querySelector('.sidebar-toggle');
    if (t) t.setAttribute('aria-expanded','false');
  });
  links.forEach(a => a.classList.remove('active'));

  // 1) Find exactly one active link by exact path match
  let activeLink = null;
  for (const a of links) {
    if (normPath(a.href) === here) { activeLink = a; break; }
  }
  // (Optional fallback) If no exact match, try startsWith match once
  if (!activeLink) {
    for (const a of links) {
      if (here.startsWith(normPath(a.href))) { activeLink = a; break; }
    }
  }

  let openedByActive = false;
  if (activeLink) {
    activeLink.classList.add('active');
    const g = activeLink.closest('.sidebar-group');
    if (g) {
      g.classList.add('open');
      const t = g.querySelector('.sidebar-toggle');
      if (t) t.setAttribute('aria-expanded','true');
      openedByActive = true;
      const idx = Array.from(groups).indexOf(g);
      if (idx >= 0) localStorage.setItem(STORAGE_KEY, String(idx));
    }
  }

  // 2) If nothing opened via active link, restore last opened (or default to first)
  if (!openedByActive) {
    const idx = localStorage.getItem(STORAGE_KEY);
    if (idx !== null && groups[idx]) {
      groups[idx].classList.add('open');
      const t = groups[idx].querySelector('.sidebar-toggle');
      if (t) t.setAttribute('aria-expanded','true');
    } else if (groups[0]) {
      groups[0].classList.add('open');
      const t0 = groups[0].querySelector('.sidebar-toggle');
      if (t0) t0.setAttribute('aria-expanded','true');
    }
  }

  // 3) Accordion: only one open at a time + persistence
  toggles.forEach((btn, i) => {
    btn.setAttribute('role','button');
    btn.setAttribute('tabindex','0');

    const doToggle = (e) => {
      e.preventDefault();
      const group = btn.closest('.sidebar-group');
      const isOpen = group.classList.contains('open');

      // close all
      groups.forEach(g => {
        g.classList.remove('open');
        const t = g.querySelector('.sidebar-toggle');
        if (t) t.setAttribute('aria-expanded','false');
      });

      if (!isOpen) {
        group.classList.add('open');
        btn.setAttribute('aria-expanded','true');
        localStorage.setItem(STORAGE_KEY, String(i));
      } else {
        btn.setAttribute('aria-expanded','false');
        localStorage.removeItem(STORAGE_KEY);
      }
    };

    btn.addEventListener('click', doToggle);
    btn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') doToggle(e);
    });
  });

  /* ===========================
     PAYSLIP: read from <select>
     =========================== */
  const $ = s => document.querySelector(s);
  const tbody = document.querySelector('#tbl tbody');

  function parseOpt(val){
    const [id,name,dept,base,allow,otHrs,otRate,penalty,epfTax] = val.split('|');
    return {
      id, name, dept,
      base:+base, allow:+allow,
      otHrs:+otHrs, otRate:+otRate,
      penalty:+penalty, epfTax:+epfTax
    };
  }

  function readEmployees(){
    const sel = $('#emp');
    return Array.from(sel.options).map(o => parseOpt(o.value));
  }

  const money = n => Number(n).toLocaleString('en-MY',{minimumFractionDigits:2,maximumFractionDigits:2});
  const calc  = e => { const ot=e.otHrs*e.otRate; const gross=e.base+e.allow+ot; return {ot, net:gross-(e.penalty+e.epfTax)}; };

  function render(){
    const EMP = readEmployees();
    tbody.innerHTML = '';
    EMP.forEach(e=>{
      const c=calc(e);
      const tr=document.createElement('tr');
      tr.innerHTML=`
        <td><strong>${e.name}</strong><br><span class="muted">${e.id}</span></td>
        <td>${e.dept}</td>
        <td class="num">${money(e.base)}</td>
        <td class="num">${money(e.allow)}</td>
        <td class="num">${money(c.ot)}</td>
        <td class="num">-${money(e.penalty)}</td>
        <td class="num">-${money(e.epfTax)}</td>
        <td class="num"><strong>${money(c.net)}</strong></td>
        <td><button class="btn btn-sm" data-emp="${e.id}">Preview</button></td>`;
      tbody.appendChild(tr);
    });

    document.querySelectorAll('[data-emp]').forEach(b=>{
      b.addEventListener('click',()=>{
        const EMP = readEmployees();
        const found = EMP.find(x=>x.id===b.dataset.emp);
        if (found) renderSlip(found);
      });
    });
  }

  function renderSlip(e){
    const s=$('#start').value||'(start)'; 
    const nd=$('#end').value||'(end)'; 
    const c=calc(e);
    $('#slip').innerHTML=`
      <div><strong>${e.name}</strong> (${e.id}) — ${e.dept}</div>
      <div class="muted" style="margin:4px 0 10px">${s} → ${nd}</div>
      <table style="width:100%;border-collapse:collapse;border:1px solid #e5e7eb">
        <tr><td style="padding:8px">Base</td><td style="padding:8px;text-align:right">${money(e.base)}</td></tr>
        <tr><td style="padding:8px">Allowance</td><td style="padding:8px;text-align:right">${money(e.allow)}</td></tr>
        <tr><td style="padding:8px">OT Pay</td><td style="padding:8px;text-align:right">${money(c.ot)}</td></tr>
        <tr><td style="padding:8px">Penalty</td><td style="padding:8px;text-align:right">-${money(e.penalty)}</td></tr>
        <tr><td style="padding:8px">EPF/Tax</td><td style="padding:8px;text-align:right">-${money(e.epfTax)}</td></tr>
        <tr><td style="padding:8px"><strong>Net</strong></td><td style="padding:8px;text-align:right"><strong>${money(c.net)}</strong></td></tr>
      </table>`;
    document.getElementById('slipBox').scrollIntoView({behavior:'smooth', block:'start'});
  }

  document.getElementById('preview').addEventListener('click',()=>{
    const v=$('#emp').value;
    renderSlip(parseOpt(v));
  });

  render();
});
</script>


</body>
</html>
