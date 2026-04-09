@php
  $R = \App\Models\PayrollAdjustmentRemovalRequest::class;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team salary deduction removal - HRMS</title>
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
    .card-title { margin:0 0 8px; font-size:1rem; font-weight:600; color:#0f172a; }
    .card-hint { margin:0 0 14px; font-size:12px; color:#64748b; line-height:1.45; }
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
    .data-table .cell-reason { max-width:min(260px, 22vw); word-break:break-word; }
    .data-table .request-stack { display:flex; flex-direction:column; align-items:flex-start; gap:8px; }
    .data-table .request-stack .badge { margin:0; }
    .btn-table {
      display:inline-flex; align-items:center; justify-content:center;
      padding:6px 14px; font-size:12px; font-weight:600; font-family:inherit;
      border-radius:8px; border:1px solid #d1d5db; background:#fff; color:#475569;
      cursor:pointer; transition: border-color .15s, background .15s, color .15s;
    }
    .btn-table:hover { border-color:#94a3b8; background:#f8fafc; color:#0f172a; }
    .btn-table:active { background:#f1f5f9; }
    .btn-table.btn-send { border-color:#bbf7d0; background:#f0fdf4; color:#166534; }
    .btn-table.btn-send:hover { background:#dcfce7; border-color:#86efac; }
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
    }
    .req-form .file-field-name { font-size:12px; color:#64748b; flex:1 1 140px; min-width:0; word-break:break-word; }
    .req-form .file-field-name.has-file { color:#0f172a; font-weight:500; }
    .modal-request .modal-footer {
      margin-top:22px; padding:16px 22px 20px; border-top:1px solid #f1f5f9;
      display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;
    }
    .modal-request .btn-modal-cancel {
      padding:10px 18px; font-size:13px; font-weight:600; font-family:inherit;
      border-radius:10px; border:1px solid #e2e8f0; background:#fff; color:#475569; cursor:pointer;
    }
    .modal-request .btn-modal-submit {
      padding:10px 20px; font-size:13px; font-weight:600; font-family:inherit;
      border-radius:10px; border:none; background:#2563eb; color:#fff; cursor:pointer;
    }
    .sv-panel .modal-footer { margin-top:16px; padding-top:16px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('supervisor.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'User' }}</a></span>
    </div>
  </header>
  <div class="container">
    @include('supervisor.layout.sidebar')

    <main>
      <div class="breadcrumb">Manager actions · Team salary deduction removal</div>
      <h2>Salary adjustment deductions (team)</h2>
      <p class="subtitle">
        Review <strong>deduction</strong> lines on your direct reports’ payroll adjustments. When a team member asks for removal, use <strong>Forward to admin</strong> to send the case to HR for a final decision.
        You can also start a removal request on a report’s behalf; it goes straight to admin. If payroll for that month is still in <strong>draft</strong>, an approved request removes the line and updates totals automatically.
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
        <h3 class="card-title">Team removal requests</h3>
        <p class="card-hint">
          Pending your review: <strong>{{ $counts['pending'] ?? 0 }}</strong>
          · With HR admin: <strong>{{ $counts['submitted'] ?? 0 }}</strong>
          · Rejected by you: <strong>{{ $counts['rejected'] ?? 0 }}</strong>
          <span class="cell-muted">(showing up to 25 most recent)</span>
        </p>
        @if($teamRemovalRecent->isEmpty())
          <p class="muted" style="margin:0;">No team requests yet.</p>
        @else
          <div class="table-scroll">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Month</th>
                  <th class="num">Amount (RM)</th>
                  <th>Deduction</th>
                  <th>Appeal</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th>Attachment</th>
                  <th>Review</th>
                </tr>
              </thead>
              <tbody>
                @foreach($teamRemovalRecent as $req)
                  @php
                    $emp = $req->employee;
                    $st = $req->status;
                    $canAct = $st === $R::STATUS_PENDING_SUPERVISOR;
                    $badge = match ($st) {
                      $R::STATUS_PENDING_SUPERVISOR => ['cls' => 'badge-pending', 'label' => 'Pending your review'],
                      $R::STATUS_PENDING_ADMIN => ['cls' => 'badge-admin', 'label' => 'Pending admin'],
                      $R::STATUS_APPROVED_ADMIN => ['cls' => 'badge-ok', 'label' => 'Approved'],
                      $R::STATUS_REJECTED_SUPERVISOR, $R::STATUS_REJECTED_ADMIN => ['cls' => 'badge-bad', 'label' => 'Rejected'],
                      $R::STATUS_CANCELLED_EMPLOYEE => ['cls' => 'badge-bad', 'label' => 'Cancelled'],
                      default => ['cls' => 'badge-pending', 'label' => $st],
                    };
                  @endphp
                  <tr>
                    <td>
                      <strong>{{ $emp->user->name ?? '—' }}</strong>
                      <div class="cell-muted">{{ $emp->department->department_name ?? '' }}</div>
                    </td>
                    <td><strong>{{ $req->period_month }}</strong></td>
                    <td class="num">− RM {{ number_format((float) $req->amount_snapshot, 2) }}</td>
                    <td class="cell-reason">{{ \Illuminate\Support\Str::limit($req->reason_snapshot ?? '—', 48) }}</td>
                    <td class="cell-reason">{{ \Illuminate\Support\Str::limit($req->request_reason ?? '—', 48) }}</td>
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
                      @if($canAct)
                        <button type="button" class="btn-table btn-send btn-sv-approve" data-action="{{ route('supervisor.attendance.payroll_adjustment_removal.approve', $req) }}">Forward to admin</button>
                        <button type="button" class="btn-table btn-sv-reject" data-action="{{ route('supervisor.attendance.payroll_adjustment_removal.reject', $req) }}">Reject</button>
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
        <h3 class="card-title">Team deduction lines by month</h3>
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
            <label for="department">Department</label>
            <select id="department" name="department">
              <option value="">All</option>
              @foreach(($departments ?? []) as $d)
                <option value="{{ $d->department_id }}">{{ $d->department_name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="q">Search</label>
            <input type="text" id="q" placeholder="Name, code, or reason">
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
                <th>Employee</th>
                <th>Category</th>
                <th class="num">Amount (RM)</th>
                <th>Reason</th>
                <th>Recorded</th>
                <th>Request</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="8" class="empty">Loading…</td></tr>
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

  <div class="modal-overlay" id="requestModal" role="dialog" aria-modal="true">
    <div class="detail-modal modal-request">
      <header class="modal-request-head">
        <h3 class="modal-request-title">Request removal (send to admin)</h3>
        <p class="modal-request-hint">Goes directly to <strong>HR admin</strong>. At least <strong>10 characters</strong> required.</p>
      </header>
      <div class="body req-form-body">
        <form id="requestForm" class="req-form" method="post" action="" enctype="multipart/form-data">
          @csrf
          <div class="field">
            <div class="label-row">
              <label class="field-label" for="request_reason">Reason for request</label>
              <span class="label-hint">Required</span>
            </div>
            <textarea id="request_reason" class="req-input" name="request_reason" required minlength="10" maxlength="2000" rows="4" placeholder="Explain why this deduction should be removed for your team member."></textarea>
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
            <button type="submit" class="btn-modal-submit">Submit to admin</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="approveOverlay" role="dialog" aria-modal="true">
    <div class="detail-modal sv-panel">
      <header class="modal-request-head">
        <h3 class="modal-request-title">Forward to HR admin</h3>
        <p class="modal-request-hint">Optional note for HR.</p>
      </header>
      <div class="body req-form-body">
        <form id="approveForm" method="post" action="">
          @csrf
          <div class="field">
            <label class="field-label" for="approve_note">Supervisor comment</label>
            <textarea id="approve_note" class="req-input" name="supervisor_note" maxlength="2000" rows="3" placeholder="Optional"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" id="approveCancel">Cancel</button>
            <button type="submit" class="btn-modal-submit">Forward to admin</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="rejectOverlay" role="dialog" aria-modal="true">
    <div class="detail-modal sv-panel">
      <header class="modal-request-head">
        <h3 class="modal-request-title">Reject request</h3>
        <p class="modal-request-hint">A reason is <strong>required</strong>.</p>
      </header>
      <div class="body req-form-body">
        <form id="rejectForm" method="post" action="">
          @csrf
          <div class="field">
            <label class="field-label" for="reject_note">Reason</label>
            <textarea id="reject_note" class="req-input" name="supervisor_note" required maxlength="2000" rows="3" placeholder="Why this request is rejected"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" id="rejectCancel">Cancel</button>
            <button type="submit" class="btn-modal-submit" style="background:#dc2626;">Reject</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    const DATA_URL = @json(route('supervisor.attendance.payroll_adjustment_removal.data'));
    const DETAIL_URL = @json(route('employee.team.payroll_adjustments.detail'));
    const STORE_BASE = @json(url('/supervisor/attendance/payroll-adjustment-removal'));
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
      const dept = document.getElementById('department').value;
      if (dept) params.set('department', dept);
      tbody.innerHTML = '<tr><td colspan="8" class="empty">Loading…</td></tr>';
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
          tbody.innerHTML = '<tr><td colspan="8" class="empty">No deduction lines for this month.</td></tr>';
          return;
        }
        tbody.innerHTML = rows.map(row => {
          const amt = '- RM ' + money(row.amount);
          const hasActive = !!row.removal_request;
          const emp = escapeHtml(row.employee_name || '—');
          const reqBtn = hasActive
            ? '<span class="cell-muted">In progress</span>'
            : '<button type="button" class="btn-table js-req" data-id="' + String(row.id) + '">Request removal</button>';
          const statusHtml = statusCell(row.removal_request);
          return '<tr>' +
            '<td><strong>' + escapeHtml(row.period_month || '—') + '</strong></td>' +
            '<td>' + emp + '<div class="cell-muted">' + escapeHtml(row.employee_code || '') + '</div></td>' +
            '<td>Deduction</td>' +
            '<td class="num">' + amt + '</td>' +
            '<td class="cell-reason">' + escapeHtml(row.reason || '—') + '</td>' +
            '<td class="cell-muted">' + escapeHtml(row.recorded_at || '—') + '</td>' +
            '<td class="cell-request"><div class="request-stack">' + statusHtml + reqBtn + '</div></td>' +
            '<td><button type="button" class="btn-table adj-detail-btn" data-id="' + String(row.id) + '">View</button></td>' +
            '</tr>';
        }).join('');
      } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty">' + escapeHtml(e.message || 'Error') + '</td></tr>';
      }
    }

    const detailModal = document.getElementById('detailModal');
    const requestModal = document.getElementById('requestModal');
    const requestForm = document.getElementById('requestForm');
    const attachmentInput = document.getElementById('attachment');
    const attachmentFileLabel = document.getElementById('attachmentFileLabel');
    const defaultFileHint = 'PDF, PNG or JPG';
    const approveOverlay = document.getElementById('approveOverlay');
    const rejectOverlay = document.getElementById('rejectOverlay');
    const approveForm = document.getElementById('approveForm');
    const rejectForm = document.getElementById('rejectForm');

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
    document.getElementById('approveCancel')?.addEventListener('click', () => approveOverlay.classList.remove('show'));
    approveOverlay?.addEventListener('click', (e) => { if (e.target === approveOverlay) approveOverlay.classList.remove('show'); });
    document.getElementById('rejectCancel')?.addEventListener('click', () => rejectOverlay.classList.remove('show'));
    rejectOverlay?.addEventListener('click', (e) => { if (e.target === rejectOverlay) rejectOverlay.classList.remove('show'); });

    document.querySelectorAll('.btn-sv-approve').forEach(btn => {
      btn.addEventListener('click', () => {
        approveForm.action = btn.getAttribute('data-action');
        document.getElementById('approve_note').value = '';
        approveOverlay.classList.add('show');
      });
    });
    document.querySelectorAll('.btn-sv-reject').forEach(btn => {
      btn.addEventListener('click', () => {
        rejectForm.action = btn.getAttribute('data-action');
        document.getElementById('reject_note').value = '';
        rejectOverlay.classList.add('show');
      });
    });

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
          html += '<tr><th>Employee</th><td>' + escapeHtml(row.employee_name || '—') + '</td></tr>';
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
    document.getElementById('department').addEventListener('change', () => { page = 1; loadPage(1); });

    loadPage(1);
  </script>
</body>
</html>
