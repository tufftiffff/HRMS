<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team OT Approvals - HRMS</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    main { padding:20px 24px; }
    .page-header { display:flex; justify-content:space-between; align-items:flex-end; gap:12px; margin-bottom:12px; }
    .page-title { margin:0; font-size:1.35rem; }
    .page-subtitle { margin:2px 0 0; color:#64748b; font-size:0.9rem; }

    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; margin-bottom:14px; }
    .card .section-title { margin:0 0 12px; font-size:1.05rem; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .card .section-title i { color:#6366f1; opacity:0.9; }
    .empty-state { text-align:center; padding:32px 24px; color:#94a3b8; font-size:13px; background:#f8fafc; border-radius:12px; margin-top:8px; border:1px dashed #e2e8f0; }
    .empty-state i { font-size:28px; margin-bottom:8px; opacity:0.6; display:block; }
    .table-wrap { overflow-x:auto; border-radius:12px; border:1px solid #e2e8f0; margin-top:8px; }
    .ot-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; }
    .ot-table thead th { background:linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); color:#475569; font-weight:600; padding:12px 14px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; border-bottom:2px solid #e2e8f0; }
    .ot-table tbody td { padding:12px 14px; vertical-align:middle; color:#334155; border-bottom:1px solid #f1f5f9; }
    .progress-badge { padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; background:#e0e7ff; color:#4338ca; }
    .notice { padding:10px 14px; border-radius:10px; margin-bottom:12px; font-size:13px; }
    .notice.success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
    .notice.error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
    .notice.info { background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe; }

    /* OT summary cards (Pending Admin, Flagged Pending, Approved, Rejected) */
    .ot-summary-cards { display:flex; flex-wrap:wrap; gap:16px; margin-bottom:16px; }
    .ot-summary-card {
      flex:1 1 140px;
      min-width:120px;
      padding:16px 20px;
      border-radius:12px;
      text-align:center;
    }
    .ot-summary-card .num { display:block; font-size:1.75rem; font-weight:700; margin-bottom:4px; }
    .ot-summary-card .label { display:block; font-size:12px; font-weight:600; }
    .ot-summary-card.pending-admin { background:#dbeafe; color:#1e40af; }
    .ot-summary-card.pending-admin .num { color:#1d4ed8; }
    .ot-summary-card.flagged-pending { background:#fef9c3; color:#a16207; }
    .ot-summary-card.flagged-pending .num { color:#b45309; }
    .ot-summary-card.approved { background:#dcfce7; color:#166534; }
    .ot-summary-card.approved .num { color:#15803d; }
    .ot-summary-card.rejected { background:#fee2e2; color:#b91c1c; }
    .ot-summary-card.rejected .num { color:#dc2626; }

    /* Compact summary chips (fallback) */
    .summary-row { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
    .summary-chip {
      padding:6px 10px;
      border-radius:999px;
      background:#f8fafc;
      border:1px solid #e5e7eb;
      display:inline-flex;
      align-items:center;
      gap:6px;
      font-size:12px;
      color:#475569;
    }
    .summary-chip .num { font-weight:700; color:#0f172a; }

    .toolbar {
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:center;
      margin-bottom:6px;
      font-size:12px;
    }
    .toolbar input,
    .toolbar select {
      padding:6px 10px;
      border:1px solid #e5e7eb;
      border-radius:8px;
      font-size:12px;
      min-height:32px;
    }

    table { width:100%; border-collapse:collapse; font-size:13px; }
    th, td { padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top; }
    thead th { background:#0f172a; color:#e2e8f0; font-weight:500; }

    .employee-cell strong { display:block; font-weight:600; color:#0f172a; }
    .employee-meta { font-size:11px; color:#6b7280; }
    .reason-text { font-size:12px; color:#111827; max-width:320px; }
    .comment-cell { max-width:200px; vertical-align:top; }
    .comment-cell .comment-preview { display:block; white-space:pre-wrap; word-break:break-word; font-size:12px; color:#334155; line-height:1.35; }

    .status-badge {
      padding:3px 8px;
      border-radius:999px;
      font-size:11px;
      font-weight:600;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    .status-pending { background:#fef3c7; color:#92400e; }
    .status-approved { background:#dcfce7; color:#166534; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .status-other { background:#e5e7eb; color:#374151; }

    .btn-sm {
      padding:6px 10px;
      font-size:12px;
      border-radius:8px;
      border:none;
      cursor:pointer;
      margin:0 2px;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    .btn-approve { background:#16a34a; color:#fff; }
    .btn-reject { background:#dc2626; color:#fff; }
    .btn-outline {
      background:#fff;
      border:1px solid #e5e7eb;
      color:#374151;
    }
    .bulk-actions {
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      align-items:center;
      margin-bottom:12px;
      font-size:14px;
      padding:10px 0;
    }
    .bulk-actions .ot-selected-count {
      color:#6b7280;
      font-weight:500;
    }
    .bulk-actions .btn-ot-approve {
      padding:10px 20px;
      border-radius:999px;
      border:none;
      cursor:pointer;
      font-size:14px;
      font-weight:700;
      color:#fff;
      background:#22c55e;
      font-family:inherit;
    }
    .bulk-actions .btn-ot-approve:hover { background:#16a34a; }
    .bulk-actions .btn-ot-reject {
      padding:10px 20px;
      border-radius:999px;
      border:none;
      cursor:pointer;
      font-size:14px;
      font-weight:700;
      color:#fff;
      background:#f87171;
      font-family:inherit;
    }
    .bulk-actions .btn-ot-reject:hover { background:#ef4444; }

    .ot-rec-select {
      min-width: 168px;
      padding:8px 10px;
      border:1px solid #e5e7eb;
      border-radius:8px;
      font-size:12px;
      font-family:inherit;
      background:#fff;
      cursor:pointer;
    }
    .ot-rec-select:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,0.2); }

    table input[type="checkbox"]#select-all,
    table input[type="checkbox"].row-check { transform:scale(1.6); cursor:pointer; accent-color:#6366f1; }

    tr.row-no-proof { background:#fef2f2 !important; }
    .proof-badge { font-size:11px; padding:2px 6px; border-radius:4px; font-weight:600; }
    .proof-badge.has { background:#dcfce7; color:#166534; }
    .proof-badge.none { background:#fef2f2; color:#991b1b; }

    /* Modal / side panel */
    .overlay {
      position:fixed;
      inset:0;
      background:rgba(15,23,42,0.55);
      display:none;
      align-items:center;
      justify-content:center;
      z-index:1000;
    }
    .overlay.open { display:flex; }
    .panel {
      width:100%;
      max-width:420px;
      background:#fff;
      border-radius:14px;
      box-shadow:0 20px 45px rgba(15,23,42,0.35);
      padding:18px 18px 16px;
    }
    .panel-header {
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:10px;
    }
    .panel-title { margin:0; font-size:1rem; }
    .panel-close {
      background:none;
      border:none;
      cursor:pointer;
      color:#6b7280;
      font-size:16px;
    }
    .panel-body { font-size:13px; color:#4b5563; }
    .panel-body label { display:block; font-size:12px; margin:8px 0 4px; font-weight:600; }
    .panel-body input,
    .panel-body textarea {
      width:100%;
      padding:8px 10px;
      border-radius:8px;
      border:1px solid #e5e7eb;
      font-size:13px;
    }
    .panel-body textarea { min-height:80px; resize:vertical; }
    .panel-footer {
      margin-top:14px;
      display:flex;
      justify-content:flex-end;
      gap:8px;
    }
    .panel-footer .btn-sm { min-width:80px; justify-content:center; }
    /* Bulk action modals: clear error when user types */
    #bulkRejectReason:focus ~ .bulk-modal-error { display:none !important; }
  </style>
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><i class="fa-regular fa-bell"></i> &nbsp;
      <a href="{{ route('supervisor.profile') }}" style="color:inherit; text-decoration:none;">
        {{ Auth::user()->name ?? 'Supervisor' }}
      </a>
    </div>
  </header>
  <div class="container">
    @include('supervisor.layout.sidebar')
    <main>
      <div class="page-header">
        <div>
          <div class="breadcrumb">Supervisor · Team OT Approvals</div>
          <h2 class="page-title">Team OT Approvals</h2>
          <p class="page-subtitle">Set <strong>Recommended</strong> or <strong>Not recommended</strong> for each claim. Admin makes the final approval; your choice is shown on the admin OT queue.</p>
        </div>
      </div>

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error">{{ session('error') }}</div>
      @endif
      @if(session('message'))
        <div class="notice info">{{ session('message') }}</div>
      @endif

      <div class="ot-summary-cards">
        <div class="ot-summary-card pending-admin">
          <span class="num">{{ $pendingAdminCount ?? 0 }}</span>
          <span class="label">Pending Admin</span>
        </div>
        <div class="ot-summary-card flagged-pending">
          <span class="num" id="ot-pending-count">{{ $flaggedPendingCount ?? 0 }}</span>
          <span class="label">Flagged Pending</span>
        </div>
        <div class="ot-summary-card approved">
          <span class="num">{{ $approvedCount ?? 0 }}</span>
          <span class="label">Approved</span>
        </div>
        <div class="ot-summary-card rejected">
          <span class="num">{{ $rejectedCount ?? 0 }}</span>
          <span class="label">Rejected (Sup + Admin)</span>
        </div>
      </div>

      {{-- Pending your approval --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-inbox"></i> Pending your approval</h3>
        @if($pendingClaims->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-inbox"></i> No OT claims pending your approval.</div>
        @else
          <form method="GET" action="{{ route('employee.overtime_inbox.index') }}" class="toolbar" style="margin-bottom:12px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID/code">
            <select name="department">
              <option value="">All Depts</option>
              @foreach($departments as $d)
                <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
              @endforeach
            </select>
            <input type="date" name="start" value="{{ request('start') }}" placeholder="From">
            <input type="date" name="end" value="{{ request('end') }}" placeholder="To">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          </form>
            <div class="table-wrap">
              <table class="ot-table">
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Hours / Rate</th>
                    <th>Location</th>
                    <th>Reason</th>
                    <th>Submitted</th>
                    <th>Attachment</th>
                    <th>Supervisor comment</th>
                    <th>Admin comment</th>
                    <th>Recommendation</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingClaims as $c)
                    <tr class="{{ $c->hasNoProofFlag() ? 'row-no-proof' : '' }}">
                      <td class="employee-cell">
                        <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                        <div class="employee-meta">
                          {{ $c->employee->employee_code ?? '' }}
                          · {{ $c->employee->department->department_name ?? '—' }}
                        </div>
                      </td>
                      <td>{{ $c->date?->format('Y-m-d') }}</td>
                      <td>
                        {{ number_format($c->hours, 2) }} h
                        <div class="employee-meta">Rate {{ (float) $c->rate_type }}x</div>
                      </td>
                      <td>{{ $c->location_type ?? 'INSIDE' }}</td>
                      <td>
                        @if(($c->location_type ?? 'INSIDE') === \App\Models\OvertimeClaim::LOCATION_OUTSIDE)
                          @if($c->proof_image_path)
                            <span class="proof-badge has">Has proof</span>
                          @else
                            <span class="proof-badge none">NO PROOF</span>
                          @endif
                        @else
                          <span class="reason-text">{{ Str::limit($c->reason ?? '—', 80) }}</span>
                        @endif
                      </td>
                      <td>{{ $c->submitted_at ? $c->submitted_at->format('M j, H:i') : '—' }}</td>
                      <td>
                        @if($c->attachment_path)
                          <a href="{{ route('employee.overtime_inbox.attachment', $c) }}" target="_blank" rel="noopener">View</a>
                        @else
                          <span class="employee-meta">—</span>
                        @endif
                      </td>
                      <td class="comment-cell"><span class="employee-meta">—</span></td>
                      <td class="comment-cell"><span class="employee-meta">—</span></td>
                      <td>
                        <form method="POST" action="{{ route('employee.overtime_inbox.recommendation', $c) }}" style="margin:0;" class="js-supervisor-rec-form">
                          @csrf
                          <input type="hidden" name="supervisor_remark" value="{{ $c->supervisor_remark ?? '' }}">
                          <label class="employee-meta" for="rec-{{ $c->id }}" style="display:block; margin-bottom:4px;">Supervisor input</label>
                          <select name="recommendation" id="rec-{{ $c->id }}" class="ot-rec-select js-supervisor-rec-select" required>
                            <option value="" selected disabled>None</option>
                            <option value="recommended">Recommended</option>
                            <option value="not_recommended">Not recommended</option>
                          </select>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
        @endif
      </div>

      {{-- Claims you already acted on (recommendation sent or legacy approve/reject) --}}
      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Your recommendations &amp; history</h3>
        @if($actedClaims->isEmpty())
          <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i> Nothing here yet — set a recommendation on a pending claim above.</div>
        @else
          <form method="GET" action="{{ route('employee.overtime_inbox.index') }}" class="toolbar" style="margin-bottom:12px;">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID/code">
            <select name="department">
              <option value="">All Depts</option>
              @foreach($departments as $d)
                <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
              @endforeach
            </select>
            <input type="date" name="start" value="{{ request('start') }}" placeholder="From">
            <input type="date" name="end" value="{{ request('end') }}" placeholder="To">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          </form>
          <div class="table-wrap">
            <table class="ot-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Department</th>
                  <th>Date</th>
                  <th>Hours</th>
                  <th>Reason</th>
                  <th>Attachment</th>
                  <th>Supervisor comment</th>
                  <th>Admin comment</th>
                  <th>Your action</th>
                  <th>Progress</th>
                </tr>
              </thead>
              <tbody>
                @foreach($actedClaims as $c)
                  @php
                    $supComm = filled($c->supervisor_remark) ? html_entity_decode((string) $c->supervisor_remark, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    $admComm = filled($c->admin_remark) ? html_entity_decode((string) $c->admin_remark, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                  @endphp
                  <tr>
                    <td>
                      <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                      <div class="employee-meta">{{ $c->employee->employee_code ?? '' }}</div>
                    </td>
                    <td>{{ $c->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $c->date?->format('Y-m-d') }}</td>
                    <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }} h</td>
                    <td class="reason-text">{{ Str::limit($c->reason ?? '—', 50) }}</td>
                    <td>
                      @if($c->attachment_path)
                        <a href="{{ route('employee.overtime_inbox.attachment', $c) }}" target="_blank" rel="noopener">View</a>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    <td class="comment-cell">
                      @if($supComm !== '')
                        <span class="comment-preview" title="{{ e($supComm) }}">{{ Str::limit($supComm, 120) }}</span>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    <td class="comment-cell">
                      @if($admComm !== '')
                        <span class="comment-preview" title="{{ e($admComm) }}">{{ Str::limit($admComm, 120) }}</span>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    <td>
                      @if(
                        $c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_PENDING
                        && in_array($c->supervisor_action_type, [
                          \App\Models\OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED,
                          \App\Models\OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED,
                        ], true)
                      )
                        <form method="POST" action="{{ route('employee.overtime_inbox.recommendation', $c) }}" style="margin:0;" class="js-supervisor-rec-form">
                          @csrf
                          <input type="hidden" name="supervisor_remark" value="{{ $c->supervisor_remark ?? '' }}">
                          <select name="recommendation" class="ot-rec-select js-supervisor-rec-select" required>
                            <option value="" disabled {{ !in_array($c->supervisor_action_type, [\App\Models\OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED, \App\Models\OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED], true) ? 'selected' : '' }}>None</option>
                            <option value="recommended" {{ $c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED ? 'selected' : '' }}>Recommended</option>
                            <option value="not_recommended" {{ $c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED ? 'selected' : '' }}>Not recommended</option>
                          </select>
                        </form>
                      @elseif($c->status === \App\Models\OvertimeClaim::STATUS_SUPERVISOR_REJECTED)
                        <span class="status-badge status-rejected">Rejected (legacy)</span>
                      @elseif($c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED)
                        <span class="status-badge status-rejected">Not recommended</span>
                      @elseif($c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED)
                        <span class="status-badge status-approved">Recommended</span>
                      @elseif(in_array($c->supervisor_action_type, [\App\Models\OvertimeClaim::SUPERVISOR_ACTION_APPROVED, \App\Models\OvertimeClaim::SUPERVISOR_ACTION_APPROVED_WITH_ADJUSTMENT], true))
                        <span class="status-badge status-approved">Supervisor approved (legacy)</span>
                      @elseif($c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_ESCALATED_TO_ADMIN)
                        <span class="status-badge status-other">Escalated</span>
                      @elseif(in_array($c->status, [\App\Models\OvertimeClaim::STATUS_ADMIN_PENDING, \App\Models\OvertimeClaim::STATUS_ADMIN_APPROVED], true))
                        <span class="status-badge status-other">—</span>
                      @else
                        <span class="status-badge status-other">—</span>
                      @endif
                    </td>
                    <td>
                      <span class="progress-badge">{{ $c->getProgressLabelForSupervisor() }}</span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </main>
  </div>

  <div class="overlay" id="otSupRemarkModal" aria-hidden="true">
    <div class="panel" role="dialog" aria-labelledby="otSupRemarkTitle" aria-modal="true" style="max-width:440px;">
      <div class="panel-header">
        <h3 class="panel-title" id="otSupRemarkTitle">Not recommended</h3>
        <button type="button" class="panel-close" id="otSupRemarkClose" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="panel-body">
        <p style="margin:0 0 10px; font-size:13px; color:#64748b;">A <strong>supervisor comment</strong> is required. HR admin and the employee may see this note.</p>
        <label for="otSupRemarkText" style="display:block; font-size:12px; margin:8px 0 4px; font-weight:600;">Supervisor comment</label>
        <textarea id="otSupRemarkText" maxlength="500" rows="4" placeholder="e.g. OT not aligned with approved project hours."></textarea>
        <p id="otSupRemarkErr" class="notice error" style="display:none; margin-top:10px; padding:8px 10px; font-size:12px;"></p>
      </div>
      <div class="panel-footer">
        <button type="button" class="btn-sm btn-outline" id="otSupRemarkCancel">Cancel</button>
        <button type="button" class="btn-sm btn-approve" id="otSupRemarkConfirm"><i class="fa-solid fa-paper-plane"></i> Submit</button>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var remarkModal = document.getElementById('otSupRemarkModal');
      var remarkTextarea = document.getElementById('otSupRemarkText');
      var remarkErr = document.getElementById('otSupRemarkErr');
      var remarkForm = null;
      var remarkSelect = null;

      function closeRemarkModal() {
        if (remarkModal) remarkModal.classList.remove('open');
        if (remarkModal) remarkModal.setAttribute('aria-hidden', 'true');
        if (remarkSelect) {
          remarkSelect.value = remarkSelect.dataset.prevValue || '';
          remarkSelect = null;
        }
        remarkForm = null;
        if (remarkErr) { remarkErr.style.display = 'none'; remarkErr.textContent = ''; }
      }

      document.querySelectorAll('.js-supervisor-rec-select').forEach(function (selectEl) {
        selectEl.addEventListener('focus', function () {
          this.dataset.prevValue = this.value;
        });
        selectEl.addEventListener('change', function () {
          if (!this.value) return;
          var form = this.closest('form');
          if (!form) return;
          var remarkInput = form.querySelector('input[name="supervisor_remark"]');
          if (this.value === 'not_recommended') {
            remarkForm = form;
            remarkSelect = this;
            if (remarkTextarea) {
              var existing = (remarkInput && remarkInput.value) ? String(remarkInput.value).trim() : '';
              remarkTextarea.value = existing;
            }
            if (remarkModal) {
              remarkModal.classList.add('open');
              remarkModal.setAttribute('aria-hidden', 'false');
            }
            if (remarkTextarea) remarkTextarea.focus();
            return;
          }
          if (remarkInput) remarkInput.value = '';
          form.submit();
        });
      });

      document.getElementById('otSupRemarkCancel')?.addEventListener('click', closeRemarkModal);
      document.getElementById('otSupRemarkClose')?.addEventListener('click', closeRemarkModal);
      remarkModal?.addEventListener('click', function (e) {
        if (e.target === remarkModal) closeRemarkModal();
      });
      document.getElementById('otSupRemarkConfirm')?.addEventListener('click', function () {
        if (!remarkForm || !remarkSelect) return;
        var remarkInput = remarkForm.querySelector('input[name="supervisor_remark"]');
        var text = remarkTextarea ? String(remarkTextarea.value || '').trim() : '';
        if (!text) {
          if (remarkErr) {
            remarkErr.textContent = 'Please enter a supervisor comment before submitting.';
            remarkErr.style.display = 'block';
          }
          return;
        }
        if (remarkInput) remarkInput.value = text;
        if (remarkModal) remarkModal.classList.remove('open');
        if (remarkModal) remarkModal.setAttribute('aria-hidden', 'true');
        var f = remarkForm;
        remarkForm = null;
        remarkSelect = null;
        f.submit();
      });
    })();
  </script>
</body>
</html>
