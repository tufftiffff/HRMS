@php
  $role = strtolower(Auth::user()->role ?? 'employee');
  $isSupervisorNav = ($role === 'supervisor' || $role === 'manager');
  $R = \App\Models\PayrollAdjustmentRemovalRequest::class;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Salary deduction removal - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding:2rem; background:#f1f5f9; min-height:100vh; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#4f46e5; margin:0 0 .4rem 0; font-weight:700; letter-spacing:-0.02em; }
    .subtitle { color:#64748b; margin-bottom:1.2rem; max-width:48rem; line-height:1.45; }
    .card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:20px 22px; margin-bottom:18px; box-shadow:0 1px 3px rgba(15,23,42,0.06), 0 8px 24px rgba(15,23,42,0.04); }
    .card-title { margin:0 0 14px; font-size:1rem; font-weight:600; color:#0f172a; }
    .toolbar { display:flex; flex-wrap:wrap; gap:14px 18px; align-items:flex-end; margin-bottom:18px; padding-bottom:18px; border-bottom:1px solid #f1f5f9; }
    .toolbar label { display:block; font-size:12px; font-weight:600; color:#475569; margin-bottom:6px; letter-spacing:0.02em; }
    .toolbar input, .toolbar select {
      padding:9px 12px; border:1px solid #e2e8f0; border-radius:9px; font-size:13px;
      min-width:160px; background:#fff; color:#0f172a; box-shadow:0 1px 2px rgba(15,23,42,0.04);
    }
    .toolbar input::placeholder { color:#94a3b8; }
    .toolbar input:focus, .toolbar select:focus { outline:none; border-color:#94a3b8; box-shadow:0 0 0 3px rgba(99,102,241,0.12); }
    .btn-primary {
      padding:10px 18px; background:#2563eb; color:#fff; border:none; border-radius:9px; font-weight:600; font-size:13px;
      cursor:pointer; display:inline-flex; align-items:center; gap:8px; box-shadow:0 1px 2px rgba(37,99,235,0.25);
      transition: background .15s ease, transform .1s ease;
    }
    .btn-primary:hover { background:#1d4ed8; }
    .btn-primary:active { transform:translateY(1px); }
    .btn-ghost { padding:8px 14px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; cursor:pointer; font-size:13px; color:#334155; }
    .btn-sm { font-size:12px; }
    /* Table module */
    .table-scroll { overflow-x:auto; margin:0 -2px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; }
    .data-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; color:#334155; }
    .data-table thead th {
      background:linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
      color:#f8fafc; font-weight:600; font-size:12px; text-align:left;
      padding:14px 16px; border:none; letter-spacing:0.04em; text-transform:uppercase;
      white-space:nowrap;
    }
    .data-table thead th:first-child { border-radius:10px 0 0 0; }
    .data-table thead th:last-child { border-radius:0 10px 0 0; }
    .data-table tbody td {
      padding:14px 16px; border-bottom:1px solid #f1f5f9; vertical-align:middle;
      line-height:1.45;
    }
    .data-table tbody tr { background:#fff; transition: background .12s ease; }
    .data-table tbody tr:nth-child(even) { background:#fafbfc; }
    .data-table tbody tr:hover { background:#f8fafc; }
    .data-table tbody tr:last-child td { border-bottom:none; }
    .data-table .num { text-align:right; font-variant-numeric:tabular-nums; font-weight:500; color:#0f172a; }
    .data-table .cell-muted { color:#64748b; font-size:12px; }
    .data-table .cell-request { min-width:148px; }
    .data-table .cell-reason { max-width:min(320px, 28vw); word-break:break-word; }
    .data-table .request-stack { display:flex; flex-direction:column; align-items:flex-start; gap:8px; }
    .data-table .request-stack .badge { margin:0; }
    /* Outlined table actions (reference UI) */
    .btn-table {
      display:inline-flex; align-items:center; justify-content:center;
      padding:6px 14px; font-size:12px; font-weight:600; font-family:inherit;
      border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#475569;
      cursor:pointer; transition: border-color .15s, background .15s, color .15s;
    }
    .btn-table:hover { border-color:#94a3b8; background:#f8fafc; color:#0f172a; }
    .btn-table:active { background:#f1f5f9; }
    .muted { color:#64748b; font-size:13px; }
    .empty { text-align:center; padding:40px 20px; color:#94a3b8; font-size:13px; }
    .pagination-wrap {
      display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:14px;
      margin-top:16px; padding-top:16px; border-top:1px solid #f1f5f9; font-size:13px; color:#64748b;
    }
    .pagination-wrap #paginationInfo { font-weight:500; color:#475569; }
    .pagination-ctrls { display:flex; align-items:center; gap:10px; }
    .pagination-ctrls #pageNum { color:#64748b; font-variant-numeric:tabular-nums; min-width:5.5rem; text-align:center; }
    .btn-page {
      padding:8px 14px; font-size:12px; font-weight:600; font-family:inherit;
      border-radius:8px; border:1px solid #e2e8f0; background:#f8fafc; color:#475569;
      cursor:pointer; transition: background .15s, border-color .15s;
    }
    .btn-page:hover:not(:disabled) { background:#fff; border-color:#cbd5e1; color:#0f172a; }
    .btn-page:disabled { opacity:.45; cursor:not-allowed; }
    .notice { padding:12px 14px; border-radius:10px; margin-bottom:14px; font-size:13px; }
    .notice.success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
    .notice.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    .badge { display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600; }
    .badge-pending { background:#fef3c7; color:#92400e; }
    .badge-admin { background:#dbeafe; color:#1e40af; }
    .badge-ok { background:#dcfce7; color:#166534; }
    .badge-bad { background:#fee2e2; color:#991b1b; }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.5); z-index:2000; align-items:center; justify-content:center; padding:16px; overflow-y:auto; }
    .modal-overlay.show { display:flex; }
    .detail-modal { background:#fff; border-radius:14px; max-width:520px; width:100%; max-height:90vh; overflow:auto; box-shadow:0 25px 50px rgba(0,0,0,0.2); border:1px solid #e5e7eb; }
    .detail-modal header { padding:16px 18px; border-bottom:1px solid #e5e7eb; position:sticky; top:0; background:#fff; z-index:1; }
    .detail-modal h3 { margin:0 0 4px; font-size:1.05rem; color:#0f172a; }
    .detail-modal .body { padding:16px 18px; font-size:13px; }
    .detail-kv { width:100%; border-collapse:collapse; font-size:13px; }
    .detail-kv th, .detail-kv td { padding:8px 10px; border:1px solid #e5e7eb; text-align:left; }
    .detail-kv th { background:#f8fafc; width:42%; font-weight:600; color:#334155; }
    /* Request removal modal */
    .modal-request.detail-modal { max-width:560px; border-radius:16px; border-color:#e2e8f0; box-shadow:0 25px 60px rgba(15,23,42,0.18); }
    .modal-request header.modal-request-head {
      padding:20px 22px 18px; border-bottom:1px solid #f1f5f9;
      display:flex; flex-direction:column; align-items:stretch; gap:8px;
    }
    .modal-request .modal-request-title { margin:0; font-size:1.15rem; font-weight:700; color:#0f172a; letter-spacing:-0.02em; }
    .modal-request .modal-request-hint {
      margin:0; font-size:13px; line-height:1.5; color:#475569; max-width:42rem;
    }
    .modal-request .modal-request-hint strong { color:#334155; font-weight:600; }
    @media (min-width: 540px) {
      .modal-request header.modal-request-head {
        flex-direction:row; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:12px 20px;
      }
      .modal-request .modal-request-hint { flex:1; text-align:right; min-width:200px; }
    }
    .modal-request .req-form-body { padding:20px 22px 0; }
    .req-form .field { margin-bottom:18px; }
    .req-form .field:last-of-type { margin-bottom:0; }
    .req-form label.field-label {
      display:block; font-size:12px; font-weight:600; color:#334155; margin:0 0 8px; letter-spacing:0.02em;
    }
    .req-form .label-row { display:flex; flex-wrap:wrap; align-items:baseline; justify-content:space-between; gap:6px; margin-bottom:8px; }
    .req-form .label-row .field-label { margin:0; }
    .req-form .label-hint { font-size:11px; font-weight:500; color:#94a3b8; }
    .req-form textarea.req-input {
      display:block; width:100%; box-sizing:border-box;
      min-height:100px; max-height:200px; resize:vertical;
      padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px;
      font-size:13px; line-height:1.5; font-family:inherit; color:#0f172a;
      background:#fff; transition:border-color .15s, box-shadow .15s;
    }
    .req-form textarea.req-input::placeholder { color:#94a3b8; }
    .req-form textarea.req-input:hover { border-color:#cbd5e1; }
    .req-form textarea.req-input:focus {
      outline:none; border-color:#6366f1;
      box-shadow:0 0 0 3px rgba(99,102,241,0.15);
    }
    .req-form .file-field {
      display:flex; flex-wrap:wrap; align-items:center; gap:10px 12px;
      padding:12px 14px; border:1px dashed #cbd5e1; border-radius:10px; background:#f8fafc;
    }
    .req-form .file-field-input {
      position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;
    }
    .req-form .file-field-btn {
      display:inline-flex; align-items:center; gap:8px; padding:8px 14px; font-size:12px; font-weight:600; font-family:inherit;
      border-radius:8px; border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer;
      transition:border-color .15s, background .15s, color .15s;
    }
    .req-form .file-field-btn:hover { border-color:#94a3b8; background:#fff; color:#0f172a; }
    .req-form .file-field-name { font-size:12px; color:#64748b; flex:1 1 140px; min-width:0; word-break:break-word; }
    .req-form .file-field-name.has-file { color:#0f172a; font-weight:500; }
    .modal-request .modal-footer {
      margin-top:22px; padding:16px 22px 20px; border-top:1px solid #f1f5f9;
      display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;
    }
    .modal-request .btn-modal-cancel {
      padding:10px 18px; font-size:13px; font-weight:600; font-family:inherit;
      border-radius:10px; border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer;
      transition:background .15s, border-color .15s;
    }
    .modal-request .btn-modal-cancel:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
    .modal-request .btn-modal-submit {
      padding:10px 20px; font-size:13px; font-weight:600; font-family:inherit;
      border-radius:10px; border:none; background:#2563eb; color:#fff; cursor:pointer;
      box-shadow:0 1px 2px rgba(37,99,235,0.3); transition:background .15s;
    }
    .modal-request .btn-modal-submit:hover { background:#1d4ed8; }
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
      <div class="breadcrumb">Attendance · Salary deduction removal</div>
      <h2>Salary adjustment deductions</h2>
      <p class="subtitle">
        Payroll <strong>deduction</strong> lines recorded under salary adjustments (for example late penalties or other corrections) for each month.
        You can ask your supervisor to review a removal request; HR admin makes the final decision. If payroll for that month is still in <strong>draft</strong>, an approved request removes the deduction line and updates totals automatically.
      </p>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      <div class="card">
        <h3 class="card-title">Your recent removal requests</h3>
        @if($myRequests->isEmpty())
          <p class="muted" style="margin:0;">No requests yet.</p>
        @else
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Month</th>
                  <th class="num">Amount (RM)</th>
                  <th>Reason</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Attachment</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($myRequests as $req)
                  @php
                    $st = $req->status;
                    $badge = match ($st) {
                      $R::STATUS_PENDING_SUPERVISOR => ['cls' => 'badge-pending', 'label' => 'Pending supervisor'],
                      $R::STATUS_PENDING_ADMIN => ['cls' => 'badge-admin', 'label' => 'Pending admin'],
                      $R::STATUS_APPROVED_ADMIN => ['cls' => 'badge-ok', 'label' => 'Approved'],
                      $R::STATUS_REJECTED_SUPERVISOR, $R::STATUS_REJECTED_ADMIN => ['cls' => 'badge-bad', 'label' => 'Rejected'],
                      $R::STATUS_CANCELLED_EMPLOYEE => ['cls' => 'badge-bad', 'label' => 'Cancelled'],
                      default => ['cls' => 'badge-pending', 'label' => $st],
                    };
                  @endphp
                  <tr>
                    <td><strong>{{ $req->period_month }}</strong></td>
                    <td class="num">− RM {{ number_format((float) $req->amount_snapshot, 2) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($req->reason_snapshot ?? '—', 56) }}</td>
                    <td><span class="badge {{ $badge['cls'] }}">{{ $badge['label'] }}</span></td>
                    <td class="cell-muted">{{ $req->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td>
                    <td>
                      @if($req->attachment_path)
                        <a href="{{ route('payroll_adjustment_removal.attachment', $req) }}" target="_blank" rel="noopener" class="btn-table">View</a>
                      @else
                        <span class="cell-muted">—</span>
                      @endif
                    </td>
                    <td>
                      @if($st === $R::STATUS_PENDING_SUPERVISOR)
                        <form method="post" action="{{ route('employee.attendance.payroll_adjustment_removal.cancel', $req) }}" onsubmit="return confirm('Cancel this request?');" style="display:inline;">
                          @csrf
                          <button type="submit" class="btn-table">Cancel</button>
                        </form>
                      @else
                        <span class="cell-muted">—</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>

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
          <div>
            <label for="q">Search</label>
            <input type="text" id="q" placeholder="Reason text">
          </div>
          <div>
            <button type="button" class="btn-primary" id="btnFilter"><i class="fa-solid fa-filter"></i> Apply</button>
          </div>
        </form>

        <p id="infoMsg" class="muted" style="display:none; margin-bottom:12px;"></p>

        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Month</th>
                <th>Category</th>
                <th class="num">Amount (RM)</th>
                <th>Reason</th>
                <th>Recorded</th>
                <th>Request</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="7" class="empty">Loading…</td></tr>
            </tbody>
          </table>
        </div>

        <div class="pagination-wrap">
          <span id="paginationInfo">0 records</span>
          <div class="pagination-ctrls">
            <button type="button" class="btn-page" id="prevPage" disabled>Prev</button>
            <span id="pageNum">Page 1 / 1</span>
            <button type="button" class="btn-page" id="nextPage" disabled>Next</button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <div class="modal-overlay" id="detailModal" role="dialog" aria-modal="true">
    <div class="detail-modal">
      <header>
        <h3 id="detailModalTitle">Deduction details</h3>
        <button type="button" class="btn-ghost btn-sm" id="detailModalClose" style="margin-top:10px;">Close</button>
      </header>
      <div class="body" id="detailModalBody"></div>
    </div>
  </div>

  <div class="modal-overlay" id="requestModal" role="dialog" aria-modal="true" aria-labelledby="requestModalTitle">
    <div class="detail-modal modal-request">
      <header class="modal-request-head">
        <h3 id="requestModalTitle" class="modal-request-title">Request removal</h3>
        <p class="modal-request-hint">Explain why this deduction should be reviewed. At least <strong>10 characters</strong> required.</p>
      </header>
      <div class="body req-form-body">
        <form id="requestForm" class="req-form" method="post" action="" enctype="multipart/form-data">
          @csrf
          <div class="field">
            <div class="label-row">
              <label class="field-label" for="request_reason">Reason for request</label>
              <span class="label-hint">Required</span>
            </div>
            <textarea id="request_reason" class="req-input" name="request_reason" required minlength="10" maxlength="2000" rows="4" placeholder="e.g. Wrong adjustment — please verify against attendance records."></textarea>
          </div>
          <div class="field">
            <div class="label-row">
              <label class="field-label" for="attachment">Attachment</label>
              <span class="label-hint">Optional · max 5MB</span>
            </div>
            <div class="file-field">
              <input type="file" id="attachment" class="file-field-input" name="attachment" accept=".pdf,.png,.jpg,.jpeg,application/pdf">
              <label for="attachment" class="file-field-btn"><i class="fa-solid fa-paperclip"></i> Choose file</label>
              <span class="file-field-name" id="attachmentFileLabel">PDF, PNG or JPG</span>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" id="requestModalClose">Cancel</button>
            <button type="submit" class="btn-modal-submit">Submit request</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    const DATA_URL = @json(route('employee.attendance.payroll_adjustment_removal.data'));
    const DETAIL_URL = @json(route('employee.payroll.salary_adjustments.detail'));
    const STORE_BASE = @json(url('/employee/attendance/payroll-adjustment-removal'));
    const R = {
      PENDING_SV: @json($R::STATUS_PENDING_SUPERVISOR),
      PENDING_AD: @json($R::STATUS_PENDING_ADMIN),
    };
    let page = 1;
    let lastPage = 1;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function money(n) {
      const v = Number(n);
      return v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function escapeHtml(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    function statusCell(removal) {
      if (!removal) return '<span class="cell-muted">—</span>';
      const st = removal.status;
      let label = st;
      let cls = 'badge-pending';
      if (st === R.PENDING_SV) { label = 'Pending supervisor'; cls = 'badge-pending'; }
      else if (st === R.PENDING_AD) { label = 'Pending admin'; cls = 'badge-admin'; }
      return '<span class="badge ' + cls + '">' + escapeHtml(label) + '</span>';
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
      tbody.innerHTML = '<tr><td colspan="7" class="empty">Loading…</td></tr>';
      infoMsg.style.display = 'none';
      try {
        const r = await fetch(DATA_URL + '?' + params.toString(), {
          headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
          credentials: 'same-origin',
        });
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
          tbody.innerHTML = '<tr><td colspan="7" class="empty">No deduction lines for this month.</td></tr>';
          return;
        }
        tbody.innerHTML = rows.map(row => {
          const amt = '- RM ' + money(row.amount);
          const hasActive = !!row.removal_request;
          const reqBtn = hasActive
            ? '<span class="cell-muted">In progress</span>'
            : '<button type="button" class="btn-table js-req" data-id="' + String(row.id) + '">Request removal</button>';
          const statusHtml = statusCell(row.removal_request);
          return '<tr>' +
            '<td><strong>' + escapeHtml(row.period_month || '—') + '</strong></td>' +
            '<td>Deduction</td>' +
            '<td class="num">' + amt + '</td>' +
            '<td class="cell-reason">' + escapeHtml(row.reason || '—') + '</td>' +
            '<td class="cell-muted">' + escapeHtml(row.recorded_at || '—') + '</td>' +
            '<td class="cell-request"><div class="request-stack">' + statusHtml + reqBtn + '</div></td>' +
            '<td><button type="button" class="btn-table adj-detail-btn" data-id="' + String(row.id) + '">View</button></td>' +
            '</tr>';
        }).join('');
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="7" class="empty">' + escapeHtml(e.message || 'Error') + '</td></tr>';
      }
    }

    const detailModal = document.getElementById('detailModal');
    const requestModal = document.getElementById('requestModal');
    const requestForm = document.getElementById('requestForm');
    const attachmentInput = document.getElementById('attachment');
    const attachmentFileLabel = document.getElementById('attachmentFileLabel');
    const defaultFileHint = 'PDF, PNG or JPG';

    function resetAttachmentLabel() {
      if (!attachmentFileLabel) return;
      attachmentFileLabel.textContent = defaultFileHint;
      attachmentFileLabel.classList.remove('has-file');
    }

    attachmentInput?.addEventListener('change', function () {
      if (!attachmentFileLabel) return;
      if (this.files && this.files.length) {
        attachmentFileLabel.textContent = this.files[0].name;
        attachmentFileLabel.classList.add('has-file');
      } else {
        resetAttachmentLabel();
      }
    });

    document.getElementById('detailModalClose')?.addEventListener('click', () => detailModal.classList.remove('show'));
    detailModal?.addEventListener('click', (e) => { if (e.target === detailModal) detailModal.classList.remove('show'); });
    document.getElementById('requestModalClose')?.addEventListener('click', () => requestModal.classList.remove('show'));
    requestModal?.addEventListener('click', (e) => { if (e.target === requestModal) requestModal.classList.remove('show'); });

    document.getElementById('tbody')?.addEventListener('click', async (e) => {
      const dbtn = e.target.closest('.adj-detail-btn');
      if (dbtn) {
        const id = dbtn.getAttribute('data-id');
        detailModal.classList.add('show');
        document.getElementById('detailModalBody').innerHTML = '<p class="muted">Loading…</p>';
        try {
          const r = await fetch(DETAIL_URL + '?id=' + encodeURIComponent(String(id)), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          });
          const j = await r.json();
          if (!r.ok) throw new Error(j.message || 'Could not load');
          const row = j.row || {};
          const line = j.line || {};
          let html = '<table class="detail-kv">';
          html += '<tr><th>Month</th><td>' + escapeHtml(row.period_month || '—') + '</td></tr>';
          html += '<tr><th>Amount</th><td class="num">− RM ' + money(line.amount) + '</td></tr>';
          html += '<tr><th>Reason</th><td>' + escapeHtml(row.reason || '—') + '</td></tr>';
          html += '<tr><th>Recorded</th><td>' + escapeHtml(row.recorded_at || '—') + '</td></tr>';
          html += '<tr><th>Full description</th><td style="white-space:pre-wrap;">' + escapeHtml(line.description || '—') + '</td></tr>';
          html += '</table>';
          document.getElementById('detailModalBody').innerHTML = html;
        } catch (err) {
          document.getElementById('detailModalBody').innerHTML = '<p class="muted">' + escapeHtml(err.message) + '</p>';
        }
        return;
      }
      const rbtn = e.target.closest('.js-req');
      if (rbtn) {
        const id = rbtn.getAttribute('data-id');
        requestForm.action = STORE_BASE + '/' + encodeURIComponent(String(id));
        requestForm.reset();
        resetAttachmentLabel();
        requestModal.classList.add('show');
        document.getElementById('request_reason')?.focus();
      }
    });

    document.getElementById('btnFilter').addEventListener('click', () => { page = 1; loadPage(1); });
    document.getElementById('prevPage').addEventListener('click', () => { if (page > 1) loadPage(page - 1); });
    document.getElementById('nextPage').addEventListener('click', () => { if (page < lastPage) loadPage(page + 1); });
    document.getElementById('period_month').addEventListener('change', () => {
      const p = document.getElementById('period_month').value;
      window.history.replaceState({}, '', window.location.pathname + '?period=' + encodeURIComponent(p));
      page = 1;
      loadPage(1);
    });

    loadPage(1);
  </script>
</body>
</html>
