@php
  $role = strtolower(Auth::user()->role ?? 'employee');
  $isSupervisorNav = ($role === 'supervisor' || $role === 'manager');
  $pageTitle = $scope === 'team' ? 'Team salary adjustments' : 'My salary adjustments';
  $tableCols = $scope === 'team' ? 8 : 6;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $pageTitle }} - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding:2rem; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#6366f1; margin:0 0 .4rem 0; }
    .subtitle { color:#64748b; margin-bottom:1.2rem; max-width:48rem; line-height:1.45; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 8px 24px rgba(15,23,42,0.06); }
    .toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; margin-bottom:12px; }
    .toolbar label { display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:4px; }
    .toolbar input, .toolbar select { padding:8px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; min-width:140px; }
    .btn-primary { padding:9px 16px; background:#2563eb; color:#fff; border:none; border-radius:8px; font-weight:600; cursor:pointer; }
    .btn-ghost { padding:8px 14px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; font-size:13px; }
    .btn-sm { font-size:12px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th, td { padding:10px 12px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; font-weight:600; }
    .num { text-align:right; font-variant-numeric:tabular-nums; }
    .muted { color:#64748b; font-size:13px; }
    .empty { text-align:center; padding:32px; color:#94a3b8; }
    .pagination-wrap { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; margin-top:12px; font-size:13px; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:2000; align-items:center; justify-content:center; padding:16px; overflow-y:auto; }
    .modal-overlay.show { display:flex; }
    .detail-modal { background:#fff; border-radius:14px; max-width:520px; width:100%; max-height:90vh; overflow:auto; box-shadow:0 25px 50px rgba(0,0,0,0.2); border:1px solid #e5e7eb; }
    .detail-modal header { padding:16px 18px; border-bottom:1px solid #e5e7eb; position:sticky; top:0; background:#fff; z-index:1; }
    .detail-modal h3 { margin:0 0 4px; font-size:1.05rem; color:#0f172a; }
    .detail-modal .body { padding:16px 18px; font-size:13px; }
    .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }
    .badge-draft { background:#fef3c7; color:#92400e; }
    .badge-released { background:#d1fae5; color:#065f46; }
    .detail-section { margin-top:14px; }
    .detail-section h4 { margin:0 0 8px; font-size:11px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; }
    .detail-kv { width:100%; border-collapse:collapse; font-size:13px; }
    .detail-kv th, .detail-kv td { padding:8px 10px; border:1px solid #e5e7eb; text-align:left; }
    .detail-kv th { background:#f8fafc; width:42%; font-weight:600; color:#334155; }
    .detail-kv td.num { text-align:right; font-variant-numeric:tabular-nums; }
    .detail-muted { color:#64748b; font-size:12px; line-height:1.45; margin:0; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ $isSupervisorNav ? route('supervisor.profile') : route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'User' }}</a></span>
    </div>
  </header>
  <div class="container">
    @if($isSupervisorNav)
      @include('supervisor.layout.sidebar')
    @else
      @include('employee.layout.sidebar')
    @endif

    <main>
      <div class="breadcrumb">Payroll @if($scope === 'team') · Team @endif · Salary adjustments</div>
      <h2>{{ $pageTitle }}</h2>
      <p class="subtitle">
        Payroll corrections recorded by HR (bonuses, allowances, late penalties, and other earnings or deductions) for the selected month.
        Use <strong>View</strong> on a row to see only that deduction or earning line (including after payroll is released).
        @if($scope === 'self')
          This list is read-only and shows your records only.
        @else
          Read-only: direct reports only. Use filters to narrow by employee or search.
        @endif
      </p>

      <div class="card">
        <form class="toolbar" id="filterForm" onsubmit="return false;">
          <div>
            <label for="period_month">Payroll month</label>
            <select id="period_month" name="period_month">
              @foreach($periodOptions as $p)
                <option value="{{ $p }}" {{ $p === $currentPeriod ? 'selected' : '' }}>{{ $p }}</option>
              @endforeach
            </select>
          </div>
          @if($scope === 'team' && $subordinates->isNotEmpty())
          <div>
            <label for="employee_id">Employee</label>
            <select id="employee_id" name="employee_id">
              <option value="">All direct reports</option>
              @foreach($subordinates as $sub)
                <option value="{{ $sub->employee_id }}">{{ optional($sub->user)->name ?? 'Employee' }} ({{ $sub->employee_code ?? \App\Models\Employee::codeFallbackFromId($sub->employee_id) }})</option>
              @endforeach
            </select>
          </div>
          @endif
          <div>
            <label for="q">Search</label>
            <input type="text" id="q" placeholder="Name, code, or reason">
          </div>
          <div>
            <button type="button" class="btn-primary" id="btnFilter"><i class="fa-solid fa-filter"></i> Apply</button>
          </div>
        </form>

        <p id="infoMsg" class="muted" style="display:none; margin-bottom:8px;"></p>

        <div style="overflow-x:auto;">
          <table>
            <thead>
              <tr>
                <th>Month</th>
                @if($scope === 'team')
                <th>Employee</th>
                <th>Department</th>
                @endif
                <th>Category</th>
                <th class="num">Amount (RM)</th>
                <th>Reason</th>
                <th>Recorded</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="{{ $tableCols }}" class="empty">Loading…</td></tr>
            </tbody>
          </table>
        </div>

        <div class="pagination-wrap">
          <span id="paginationInfo">0 records</span>
          <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="btn-ghost btn-sm" id="prevPage" disabled>Prev</button>
            <span id="pageNum">Page 1</span>
            <button type="button" class="btn-ghost btn-sm" id="nextPage" disabled>Next</button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle">
    <div class="detail-modal">
      <header>
        <h3 id="detailModalTitle">Adjustment details</h3>
        <p class="detail-muted" id="detailModalSub" style="margin:0;"></p>
        <button type="button" class="btn-ghost btn-sm" id="detailModalClose" style="margin-top:10px;">Close</button>
      </header>
      <div class="body" id="detailModalBody">
        <p class="muted">Loading…</p>
      </div>
    </div>
  </div>

  <script>
    const ENDPOINT = @json($scope === 'team' ? route('employee.team.payroll_adjustments.data') : route('employee.payroll.salary_adjustments.data'));
    const DETAIL_ENDPOINT = @json($scope === 'team' ? route('employee.team.payroll_adjustments.detail') : route('employee.payroll.salary_adjustments.detail'));
    const SCOPE = @json($scope);
    const TABLE_COLS = {{ (int) $tableCols }};
    let page = 1;
    let lastPage = 1;

    function money(n) {
      const v = Number(n);
      return v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    function periodStatusLabel(status, isReleased) {
      if (isReleased) return { text: 'Released (locked)', cls: 'badge badge-released' };
      const u = String(status || '').toUpperCase();
      if (u === 'DRAFT') return { text: 'Draft payroll', cls: 'badge badge-draft' };
      if (u === 'OPEN') return { text: 'Open', cls: 'badge badge-draft' };
      return { text: String(status || '—'), cls: 'badge badge-draft' };
    }

    function kvRow(label, valueHtml) {
      return '<tr><th>' + escapeHtml(label) + '</th><td class="num">' + valueHtml + '</td></tr>';
    }

    const detailModal = document.getElementById('detailModal');
    const detailModalBody = document.getElementById('detailModalBody');
    const detailModalTitle = document.getElementById('detailModalTitle');
    const detailModalSub = document.getElementById('detailModalSub');

    function closeDetailModal() {
      detailModal.classList.remove('show');
      detailModalBody.innerHTML = '';
    }

    document.getElementById('detailModalClose')?.addEventListener('click', closeDetailModal);
    detailModal?.addEventListener('click', (e) => { if (e.target === detailModal) closeDetailModal(); });

    async function openAdjustmentDetail(id) {
      detailModal.classList.add('show');
      detailModalTitle.textContent = 'Loading…';
      detailModalSub.innerHTML = '';
      detailModalBody.innerHTML = '<p class="muted">Loading details…</p>';
      try {
        const r = await fetch(DETAIL_ENDPOINT + '?id=' + encodeURIComponent(String(id)), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        });
        const j = await r.json();
        if (!r.ok) throw new Error(j.message || 'Could not load details');
        const row = j.row || {};
        const line = j.line || {};
        const period = j.period || {};
        const isDeduction = (row.category || '') === 'Deduction';
        detailModalTitle.textContent = isDeduction ? 'Deduction details' : 'Earning details';
        const st = periodStatusLabel(period.status, period.is_released);
        detailModalSub.innerHTML =
          '<span class="' + st.cls + '">' + escapeHtml(st.text) + '</span> · Payroll month <strong>' + escapeHtml(row.period_month || '—') + '</strong>';

        const neg = isDeduction;
        const lineAmt = neg ? '−RM ' + money(line.amount) : 'RM ' + money(line.amount);

        const sectionTitle = isDeduction ? 'This deduction' : 'This earning';
        let html = '';
        html += '<div class="detail-section" style="margin-top:0;"><h4>' + sectionTitle + '</h4>';
        html += '<table class="detail-kv">';
        html += '<tr><th>Category</th><td>' + escapeHtml(row.category || '—') + '</td></tr>';
        html += kvRow('Amount', lineAmt);
        html += '<tr><th>Reason</th><td>' + escapeHtml(row.reason || '—') + '</td></tr>';
        html += '<tr><th>Recorded</th><td>' + escapeHtml(row.recorded_at || '—') + '</td></tr>';
        html += '<tr><th>Full description (HR)</th><td style="text-align:left;white-space:pre-wrap;">' + escapeHtml(line.description || '—') + '</td></tr>';
        html += '</table></div>';

        detailModalBody.innerHTML = html;
      } catch (e) {
        detailModalTitle.textContent = 'Details';
        detailModalSub.innerHTML = '';
        detailModalBody.innerHTML = '<p class="muted">' + escapeHtml(e.message || 'Error') + '</p>';
      }
    }

    async function loadPage(p) {
      const tbody = document.getElementById('tbody');
      const infoMsg = document.getElementById('infoMsg');
      const params = new URLSearchParams({
        period_month: document.getElementById('period_month').value,
        page: String(p),
        per_page: '25',
        q: document.getElementById('q').value.trim(),
      });
      const empSel = document.getElementById('employee_id');
      if (SCOPE === 'team' && empSel && empSel.value) {
        params.set('employee_id', empSel.value);
      }
      tbody.innerHTML = '<tr><td colspan="' + TABLE_COLS + '" class="empty">Loading…</td></tr>';
      infoMsg.style.display = 'none';
      try {
        const r = await fetch(ENDPOINT + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        if (!r.ok) throw new Error(j.message || 'Failed to load');
        lastPage = j.pagination?.last_page || 1;
        page = j.pagination?.current_page || 1;
        document.getElementById('paginationInfo').textContent =
          (j.pagination?.total ?? 0) + ' record' + ((j.pagination?.total === 1) ? '' : 's');
        document.getElementById('pageNum').textContent = 'Page ' + page + ' / ' + lastPage;
        document.getElementById('prevPage').disabled = page <= 1;
        document.getElementById('nextPage').disabled = page >= lastPage;
        if (j.message) {
          infoMsg.textContent = j.message;
          infoMsg.style.display = 'block';
        }
        const rows = j.data || [];
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="' + TABLE_COLS + '" class="empty">No salary adjustments for this month.</td></tr>';
          return;
        }
        const team = SCOPE === 'team';
        tbody.innerHTML = rows.map(row => {
          const neg = row.category === 'Deduction';
          const amt = (neg ? '- ' : '') + 'RM ' + money(row.amount);
          return '<tr>' +
            '<td>' + (row.period_month || '—') + '</td>' +
            (team ? '<td>' + (row.employee_name || '—') + '<br><span class="muted">' + (row.employee_code || '') + '</span></td>' +
              '<td>' + (row.department || 'N/A') + '</td>' : '') +
            '<td>' + (row.category || '—') + '</td>' +
            '<td class="num">' + amt + '</td>' +
            '<td>' + escapeHtml(row.reason || '—') + '</td>' +
            '<td>' + (row.recorded_at || '—') + '</td>' +
            '<td><button type="button" class="btn-ghost btn-sm adj-detail-btn" data-id="' + String(row.id) + '">View</button></td>' +
            '</tr>';
        }).join('');
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="' + TABLE_COLS + '" class="empty">' + (e.message || 'Error') + '</td></tr>';
      }
    }

    document.getElementById('btnFilter').addEventListener('click', () => { page = 1; loadPage(1); });
    document.getElementById('prevPage').addEventListener('click', () => { if (page > 1) loadPage(page - 1); });
    document.getElementById('nextPage').addEventListener('click', () => { if (page < lastPage) loadPage(page + 1); });
    document.getElementById('period_month').addEventListener('change', () => {
      const p = document.getElementById('period_month').value;
      const base = window.location.pathname;
      window.history.replaceState({}, '', base + '?period=' + encodeURIComponent(p));
      page = 1;
      loadPage(1);
    });

    document.getElementById('tbody')?.addEventListener('click', (e) => {
      const btn = e.target.closest('.adj-detail-btn');
      if (!btn) return;
      const id = btn.getAttribute('data-id');
      if (id) openAdjustmentDetail(id);
    });

    loadPage(1);
  </script>
</body>
</html>
