@php
  $R = \App\Models\PayrollAdjustmentRemovalRequest::class;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Salary deduction removal - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding: 2rem; }
    .breadcrumb { font-size:.85rem; color:#94a3b8; margin-bottom:1rem; }
    h2 { color:#38bdf8; margin:0 0 .25rem 0; }
    .subtitle { color:#94a3b8; margin-bottom:1.5rem; }
    .notice { padding:12px 14px; border-radius:10px; margin-bottom:14px; font-size:13px; }
    .notice.success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
    .notice.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    .summary, .filters, .table-wrap { background:#fff; color:#111827; border-radius:12px; box-shadow:0 2px 10px rgba(0,0,0,.08); }
    .summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; padding:16px; margin-bottom:16px; }
    .summary .card { border:1px solid #edf2f7; border-radius:10px; text-align:center; padding:16px; text-decoration:none; color:inherit; }
    .summary .card:hover { border-color:#38bdf8; }
    .summary .card.active { background:#e0f2fe; border-color:#38bdf8; }
    .summary .card h3 { font-size:.95rem; color:#6b7280; margin:0 0 6px; }
    .summary .card p { font-size:1.4rem; font-weight:600; color:#111827; margin:0; }
    .table-wrap { overflow:hidden; border:1px solid #e5e7eb; }
    table { width:100%; border-collapse:collapse; }
    thead { background:#0f172a; color:#38bdf8; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top; }
    tbody tr:hover { background:#f8fafc; }
    .muted { color:#64748b; font-size:12px; }
    .status { padding:4px 8px; border-radius:999px; font-size:.8rem; white-space:nowrap; display:inline-block; }
    .pending  { background:#fef3c7; color:#92400e; }
    .approved { background:#dcfce7; color:#166534; }
    .rejected { background:#fee2e2; color:#991b1b; }
    .btn-xs { padding:6px 10px; font-size:.85rem; border-radius:8px; border:1px solid #d1d5db; background:#fff; cursor:pointer; }
    .btn-approve { background:#22c55e; border-color:#22c55e; color:#fff; }
    .btn-reject  { background:#ef4444; border-color:#ef4444; color:#fff; }
    .btn-outline { background:#fff; color:#111827; }
    .filters { padding:16px; margin-bottom:16px; }
    .filters .row { display:flex; gap:12px; flex-wrap:wrap; }
    .filters .split { flex:1 1 220px; }
    .filters label { display:block; font-size:.85rem; color:#6b7280; margin-bottom:6px; }
    .filters input, .filters select, .filters button { border:1px solid #d1d5db; border-radius:8px; padding:8px 10px; font-size:.92rem; }
    .filters .btn-primary { background:#38bdf8; border-color:#38bdf8; color:#0f172a; cursor:pointer; }
    .backdrop { position:fixed; inset:0; background:rgba(15,23,42,.55); display:none; align-items:center; justify-content:center; z-index:50; }
    .backdrop.open { display:flex; }
    .dialog { width:min(520px, 92vw); background:#fff; border-radius:14px; overflow:hidden; }
    .dialog header { padding:12px 16px; background:#0f172a; color:#e2e8f0; font-weight:600; }
    .dialog .body { padding:16px; }
    .dialog textarea { width:100%; min-height:110px; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; }
    .dialog .actions { display:flex; gap:8px; justify-content:flex-end; padding:12px 16px; border-top:1px solid #e5e7eb; }
    .overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.5); display: none; align-items: center; justify-content: center; z-index: 1000; }
    .overlay.open { display: flex; }
    .panel { width: 100%; max-width: 440px; background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; }
    .panel-title { margin: 0 0 8px; font-size: 1.1rem; font-weight: 600; color: #0f172a; }
    .panel-lead { margin: 0 0 12px; font-size: 13px; color: #64748b; }
    .panel-readonly { min-height: 88px; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; white-space: pre-wrap; word-break: break-word; font-size: 13px; }
    .panel-footer { margin-top: 16px; display: flex; justify-content: flex-end; }
    .btn-view-soft { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; font-size:12px; font-weight:600; border-radius:999px; border:1px solid #bfdbfe; background:#eff6ff; color:#1d4ed8; cursor:pointer; }
    .num { text-align:right; font-variant-numeric:tabular-nums; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <a href="{{ route('admin.profile') }}" style="text-decoration: none; color: inherit;">
        <i class="fa-regular fa-bell"></i> &nbsp; {{ Auth::user()->name ?? 'HR Admin' }}
      </a>
    </div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Home &gt; Attendance &gt; Salary deduction removal</div>
      <h2>Salary adjustment deduction removal</h2>
      <p class="subtitle">Final approval for employee requests (supervisor-approved) to remove payroll salary-adjustment deductions. Draft payroll: approving removes the line and recalculates totals. Locked periods: approval is recorded only — the line is not auto-removed.</p>

      @if(session('success'))<div class="notice success">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="notice error">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="notice error">{{ $errors->first() }}</div>@endif

      <section class="summary">
        <a class="card {{ ($status ?? '') === $R::STATUS_PENDING_ADMIN ? 'active' : '' }}"
           href="{{ route('admin.attendance.payroll_adjustment_removal.index', ['status' => $R::STATUS_PENDING_ADMIN]) }}">
          <h3>Pending</h3>
          <p>{{ $counts['pending'] ?? 0 }}</p>
        </a>
        <a class="card {{ ($status ?? '') === $R::STATUS_APPROVED_ADMIN ? 'active' : '' }}"
           href="{{ route('admin.attendance.payroll_adjustment_removal.index', ['status' => $R::STATUS_APPROVED_ADMIN]) }}">
          <h3>Approved</h3>
          <p>{{ $counts['approved'] ?? 0 }}</p>
        </a>
        <a class="card {{ ($status ?? '') === $R::STATUS_REJECTED_ADMIN ? 'active' : '' }}"
           href="{{ route('admin.attendance.payroll_adjustment_removal.index', ['status' => $R::STATUS_REJECTED_ADMIN]) }}">
          <h3>Rejected</h3>
          <p>{{ $counts['rejected'] ?? 0 }}</p>
        </a>
      </section>

      <section class="filters">
        <form method="GET" action="{{ route('admin.attendance.payroll_adjustment_removal.index') }}">
          <div class="row">
            <div class="split">
              <label for="q">Search</label>
              <input id="q" name="q" value="{{ request('q') }}" placeholder="Name or employee ID">
            </div>
            <div class="split">
              <label for="department">Department</label>
              <select id="department" name="department">
                <option value="">All</option>
                @foreach(($departments ?? []) as $d)
                  <option value="{{ $d->department_id }}" {{ (string) request('department') === (string) $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
                @endforeach
              </select>
            </div>
            <div class="split">
              <label for="reason">Appeal text</label>
              <input id="reason" name="reason" value="{{ request('reason') }}">
            </div>
            <div class="split">
              <label for="status">Status</label>
              <select id="status" name="status">
                <option value="{{ $R::STATUS_PENDING_ADMIN }}" {{ request('status', $R::STATUS_PENDING_ADMIN) === $R::STATUS_PENDING_ADMIN ? 'selected' : '' }}>Pending</option>
                <option value="{{ $R::STATUS_APPROVED_ADMIN }}" {{ request('status') === $R::STATUS_APPROVED_ADMIN ? 'selected' : '' }}>Approved</option>
                <option value="{{ $R::STATUS_REJECTED_ADMIN }}" {{ request('status') === $R::STATUS_REJECTED_ADMIN ? 'selected' : '' }}>Rejected</option>
              </select>
            </div>
            <div class="split" style="display:flex; align-items:flex-end; gap:8px;">
              <button type="submit" class="btn-primary"><i class="fa-solid fa-rotate"></i> Filter</button>
              <a class="btn-outline" href="{{ route('admin.attendance.payroll_adjustment_removal.index', ['status' => $R::STATUS_PENDING_ADMIN]) }}" style="padding:8px 12px; text-decoration:none; display:inline-flex; align-items:center;">Clear</a>
            </div>
          </div>
        </form>
      </section>

      <section class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Payroll month</th>
              <th>Employee</th>
              <th>Department</th>
              <th class="num">Amount (RM)</th>
              <th>Deduction</th>
              <th>Appeal</th>
              <th>Supervisor</th>
              <th>Supervisor note</th>
              <th>Attachment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($requests as $r)
              @php
                $emp = $r->employee;
                $user = $emp?->user;
                $dept = $emp?->department;
                $statusClass = match($r->status) {
                  $R::STATUS_PENDING_ADMIN => 'pending',
                  $R::STATUS_APPROVED_ADMIN => 'approved',
                  $R::STATUS_REJECTED_ADMIN => 'rejected',
                  default => 'pending',
                };
                $statusLabel = match($r->status) {
                  $R::STATUS_PENDING_ADMIN => 'Pending',
                  $R::STATUS_APPROVED_ADMIN => 'Approved',
                  $R::STATUS_REJECTED_ADMIN => 'Rejected',
                  default => $r->status,
                };
                $canAct = $r->status === $R::STATUS_PENDING_ADMIN;
                $supervisorNoteDecoded = html_entity_decode((string) ($r->supervisor_note ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $adminNoteDecoded = html_entity_decode((string) ($r->admin_note ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $adminComment = trim($adminNoteDecoded);
                $hasAdminComment = $adminComment !== '';
                $isApprovedByAdmin = $r->status === $R::STATUS_APPROVED_ADMIN;
                $showAdminViewButton = $hasAdminComment || $isApprovedByAdmin;
                $adminCommentPayload = $showAdminViewButton
                  ? htmlspecialchars(json_encode($adminNoteDecoded, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')
                  : '';
                $lineLabel = 'ADJ-' . $r->id;
              @endphp
              <tr>
                <td><strong>{{ $r->period_month }}</strong><br><span class="muted">{{ $lineLabel }}</span></td>
                <td>
                  <strong>{{ $user?->name ?? 'Unknown' }}</strong><br>
                  <span class="muted">{{ $emp?->employee_code ?? \App\Models\Employee::codeFallbackFromId($r->employee_id) }}</span>
                </td>
                <td>{{ $dept?->department_name ?? 'N/A' }}</td>
                <td class="num">− {{ number_format((float) $r->amount_snapshot, 2) }}</td>
                <td><span class="muted">{{ $r->sub_type_snapshot ?? '—' }}:</span> {{ \Illuminate\Support\Str::limit($r->reason_snapshot ?? '—', 80) }}</td>
                <td>{{ \Illuminate\Support\Str::limit($r->request_reason ?? '—', 120) }}</td>
                <td>{{ $r->supervisor?->name ?? '—' }}</td>
                <td><span class="muted" style="white-space:pre-wrap;">{{ filled($r->supervisor_note) ? \Illuminate\Support\Str::limit($supervisorNoteDecoded, 160) : '—' }}</span></td>
                <td>
                  @if($r->attachment_path)
                    <a href="{{ route('payroll_adjustment_removal.attachment', $r) }}" target="_blank" rel="noopener">View</a>
                  @else <span class="muted">—</span> @endif
                </td>
                <td><span class="status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                <td style="display:flex; flex-wrap:wrap; gap:6px;">
                  @if($canAct)
                    <button type="button" class="btn-xs btn-approve js-open-approve"
                      data-action="{{ route('admin.attendance.payroll_adjustment_removal.approve', $r) }}"
                      data-emp="{{ $user?->name ?? 'Employee' }}"
                      data-pid="{{ $r->period_month }}"
                    >Approve</button>
                    <button type="button" class="btn-xs btn-reject js-open-reject"
                      data-action="{{ route('admin.attendance.payroll_adjustment_removal.reject', $r) }}"
                      data-emp="{{ $user?->name ?? 'Employee' }}"
                      data-pid="{{ $r->period_month }}"
                    >Reject</button>
                  @endif
                  @if($showAdminViewButton)
                    <button type="button" class="btn-view-soft js-view-admin-comment" data-note="{{ $adminCommentPayload }}"><i class="fa-regular fa-eye"></i> View</button>
                  @elseif(!$canAct)
                    <span class="muted">—</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="11" style="text-align:center; color:#94a3b8; padding:20px;">No requests.</td></tr>
            @endforelse
          </tbody>
        </table>
      </section>
      <div style="margin-top:14px;">{{ $requests->links() }}</div>
    </main>
  </div>

  <div id="reject-backdrop" class="backdrop">
    <div class="dialog">
      <header>Reject request</header>
      <form id="reject-form" method="POST" action="">
        @csrf
        <div class="body">
          <div class="muted" id="reject-context" style="margin-bottom:10px;"></div>
          <label for="admin_note_rej">Reason (required)</label>
          <textarea id="admin_note_rej" name="admin_note" required maxlength="2000"></textarea>
        </div>
        <div class="actions">
          <button type="button" class="btn-xs btn-outline" id="reject-cancel">Cancel</button>
          <button type="submit" class="btn-xs btn-reject">Reject</button>
        </div>
      </form>
    </div>
  </div>

  <div id="approve-backdrop" class="backdrop">
    <div class="dialog">
      <header>Approve removal request</header>
      <form id="approve-form" method="POST" action="">
        @csrf
        <div class="body">
          <div class="muted" id="approve-context" style="margin-bottom:10px;"></div>
          <p class="muted" style="margin-bottom:12px;">If payroll for that month is <strong>draft</strong>, the deduction line is removed and totals recalculated. If the period is locked, only this decision is stored.</p>
          <label for="approve_note">Admin comment (optional)</label>
          <textarea id="approve_note" name="admin_note" maxlength="2000"></textarea>
        </div>
        <div class="actions">
          <button type="button" class="btn-xs btn-outline" id="approve-cancel">Cancel</button>
          <button type="submit" class="btn-xs btn-approve" id="approve-submit">Approve</button>
        </div>
      </form>
    </div>
  </div>

  <div id="admin-comment-overlay" class="overlay" aria-hidden="true">
    <div class="panel">
      <h4 class="panel-title">Admin comment</h4>
      <p class="panel-lead">Recorded when the request was approved or rejected.</p>
      <div id="admin-comment-body" class="panel-readonly"></div>
      <div class="panel-footer">
        <button type="button" class="btn-xs btn-outline" id="admin-comment-close">Close</button>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var rejectBackdrop = document.getElementById('reject-backdrop');
      var rejectForm = document.getElementById('reject-form');
      var approveBackdrop = document.getElementById('approve-backdrop');
      var approveForm = document.getElementById('approve-form');
      var approveSubmit = document.getElementById('approve-submit');
      var adminCommentOverlay = document.getElementById('admin-comment-overlay');
      var adminCommentBody = document.getElementById('admin-comment-body');

      function openAdminCommentModal(note) {
        var s = note != null && String(note).trim() !== '' ? String(note) : '—';
        adminCommentBody.textContent = s;
        adminCommentOverlay.classList.add('open');
      }
      function closeAdminCommentModal() { adminCommentOverlay.classList.remove('open'); }

      document.querySelectorAll('.js-view-admin-comment').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var raw = btn.getAttribute('data-note');
          if (!raw) return;
          try { openAdminCommentModal(JSON.parse(raw)); } catch (e) { openAdminCommentModal(raw); }
        });
      });
      document.getElementById('admin-comment-close').addEventListener('click', closeAdminCommentModal);

      document.querySelectorAll('.js-open-reject').forEach(function (btn) {
        btn.addEventListener('click', function () {
          rejectForm.action = btn.getAttribute('data-action');
          document.getElementById('reject-context').textContent =
            (btn.getAttribute('data-emp') || '') + ' · ' + (btn.getAttribute('data-pid') || '');
          document.getElementById('admin_note_rej').value = '';
          rejectBackdrop.classList.add('open');
        });
      });
      document.getElementById('reject-cancel').addEventListener('click', function () { rejectBackdrop.classList.remove('open'); });

      document.querySelectorAll('.js-open-approve').forEach(function (btn) {
        btn.addEventListener('click', function () {
          approveForm.action = btn.getAttribute('data-action');
          document.getElementById('approve-context').textContent =
            (btn.getAttribute('data-emp') || '') + ' · ' + (btn.getAttribute('data-pid') || '');
          document.getElementById('approve_note').value = '';
          if (approveSubmit) approveSubmit.disabled = false;
          approveBackdrop.classList.add('open');
        });
      });
      document.getElementById('approve-cancel').addEventListener('click', function () { approveBackdrop.classList.remove('open'); });
      if (approveForm) approveForm.addEventListener('submit', function () { if (approveSubmit) approveSubmit.disabled = true; });
    })();
  </script>
</body>
</html>
