<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Salary Calculation - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">

  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body { background:#f5f7fb; }
    main { padding:28px 32px; }
    .card {
      background:#fff;
      border:1px solid #e5e7eb;
      border-radius:12px;
      padding:16px;
      margin-bottom:16px;
      box-shadow:0 12px 28px rgba(15,23,42,0.08);
    }
    h2 { margin-bottom:4px; }
    .subtitle { color:#6b7280; }
    .grid { display:flex; gap:12px; flex-wrap:wrap; }
    .grid > * { flex:1 1 220px; }
    label { display:block; margin-bottom:6px; font-weight:600; color:#0f172a; }
    input, select, button {
      width:100%;
      padding:10px 12px;
      border:1px solid #d1d5db;
      border-radius:10px;
      background:#fff;
      font-size:14px;
    }
    button { cursor:pointer; font-weight:700; }
    .btn-primary { background:#1f78f0; color:#fff; border-color:#1f78f0; box-shadow:0 10px 20px rgba(31,120,240,0.25); }
    .btn-ghost { background:#fff; color:#1f2937; }
    table { width:100%; border-collapse:collapse; background:#fff; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#f8fafc; color:#0f172a; }
    .num { text-align:right; }
    .muted { color:#6b7280; font-size:13px; }
    .chip { display:inline-block; padding:6px 10px; background:#e0f2fe; color:#0c4a6e; border-radius:999px; font-weight:600; }
    .modal { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:none; align-items:center; justify-content:center; }
    .sheet { background:#fff; border-radius:12px; padding:18px; width:min(760px,92vw); box-shadow:0 16px 40px rgba(0,0,0,0.25); }
    .status-card { display:flex; flex-direction:column; gap:10px; }
    .status-badge { display:inline-flex; align-items:center; gap:10px; padding:10px 14px; border-radius:14px; font-weight:800; letter-spacing:0.04em; text-transform:uppercase; font-size:13px; }
    .badge-open { background:#ecfdf3; color:#166534; }
    .badge-draft { background:#e0f2fe; color:#0c4a6e; }
    .badge-locked { background:#fff7ed; color:#9a3412; }
    .badge-paid { background:#eef2ff; color:#3730a3; }
    .badge-published { background:#f3e8ff; color:#6b21a8; }
    .status-helper { color:#475569; font-size:13px; line-height:1.35; }
    .meta-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:10px; }
    .meta-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:10px 12px; }
    .meta-label { font-size:12px; text-transform:uppercase; color:#94a3b8; letter-spacing:0.05em; margin-bottom:4px; }
    .meta-value { font-weight:700; color:#0f172a; }
    .meta-sub { margin-top:2px; }
    .section-disabled { opacity:0.55; pointer-events:none; }
    .insights { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap:10px; margin-bottom:10px; }
    .insight-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px; box-shadow:0 6px 16px rgba(15,23,42,0.05); cursor:pointer; transition:all .15s ease; }
    .insight-card:hover { box-shadow:0 10px 24px rgba(15,23,42,0.08); transform:translateY(-1px); }
    .insight-count { font-size:22px; font-weight:800; color:#0f172a; }
    .insight-label { color:#475569; font-weight:600; }
    .insight-card.active { border-color:#2563eb; box-shadow:0 12px 28px rgba(37,99,235,0.18); }
    .release-window-msg { margin:0; font-size:13px; font-weight:600; color:#b91c1c; }
    .confirm-box { background:#fff; border-radius:12px; padding:18px; width:min(520px, 92vw); box-shadow:0 16px 40px rgba(0,0,0,0.22); }
    .confirm-box.release-report-box { width: min(920px, 96vw); max-width: 96vw; }
    .release-report-sub { text-align: center; color: #64748b; font-size: 13px; margin: 0 0 12px; }
    .release-report-body { max-height: min(65vh, 560px); overflow: auto; margin: 12px 0; font-size: 13px; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; background: #fafafa; }
    .release-report-body h4 { margin: 18px 0 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: 700; }
    .release-report-body h4:first-child { margin-top: 0; }
    .release-report-body table { width: 100%; border-collapse: collapse; margin-bottom: 4px; background: #fff; }
    .release-report-body th, .release-report-body td { border: 1px solid #e5e7eb; padding: 8px 10px; text-align: left; vertical-align: top; }
    .release-report-body th { background: #f1f5f9; font-weight: 600; color: #0f172a; }
    .release-report-body td.num, .release-report-body th.num { text-align: right; font-variant-numeric: tabular-nums; }
    .release-report-body tfoot td { font-weight: 700; background: #f8fafc; }
    .release-report-title { text-align: center; margin: 0 0 4px; font-size: 1.25rem; color: #0f172a; }
    .confirm-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; flex-wrap:wrap; }
    .adj-confirm-summary { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; margin:12px 0; font-size:14px; line-height:1.55; color:#334155; }
    .adj-confirm-summary strong { color:#0f172a; }
    .adj-confirm-modal-title { display:flex; align-items:center; gap:10px; margin:0 0 10px; font-size:18px; color:#0f172a; border-bottom:1px solid #e5e7eb; padding-bottom:12px; }
    .adj-confirm-modal-title .lock-icon { color:#059669; font-size:1.2rem; }
    .adj-confirm-check { display:flex; gap:10px; align-items:flex-start; margin:14px 0; font-size:14px; color:#374151; }
    .adj-confirm-check input { margin-top:3px; }
    .btn-confirm-save { background:#059669 !important; border-color:#059669 !important; color:#fff !important; }
    .btn-confirm-save:disabled { opacity:0.5; cursor:not-allowed; }
    .detail-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
    .detail-tab { border:none; border-radius:10px; padding:10px 14px; background:#f1f5f9; color:#475569; font-weight:700; display:flex; align-items:center; gap:8px; cursor:pointer; transition:all .15s ease; flex:1 1 140px; justify-content:center; }
    .detail-tab.active { background:#1f78f0; color:#fff; box-shadow:0 10px 22px rgba(31,120,240,0.25); }
    .detail-tab:hover { transform:translateY(-1px); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .salary-detail-header { margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #e2e8f0; }
    .salary-detail-header .emp-name { font-size:1.1rem; font-weight:700; color:#0f172a; }
    .salary-detail-header .emp-meta { font-size:0.9rem; color:#64748b; margin-top:2px; }
    .salary-detail-formula { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; margin-bottom:14px; font-size:0.85rem; color:#475569; line-height:1.5; }
    .salary-detail-formula strong { color:#0f172a; }
    .salary-detail-formula .scope { margin-top:8px; font-size:0.8rem; color:#94a3b8; }
    .salary-detail-section { margin-bottom:18px; }
    .salary-detail-section:last-child { margin-bottom:0; }
    .salary-detail-section-title { font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; margin-bottom:8px; padding-bottom:4px; }
    .info-grid .info-row { display:grid; grid-template-columns:1fr 1.2fr; gap:10px 16px; align-items:baseline; padding:8px 0; border-bottom:1px solid #f1f5f9; font-size:0.9rem; }
    .info-grid .info-row:last-child { border-bottom:none; }
    .info-grid .info-row .info-label { color:#475569; font-weight:600; }
    .info-grid .info-row .info-value { color:#0f172a; word-break:break-word; }
    .info-grid .info-row.highlight .info-value { font-weight:700; color:#0f172a; }
    .info-grid .info-row.formula-row { background:#fefce8; border-radius:8px; padding:10px 12px; margin-top:6px; border:1px solid #fef08a; }
    .info-grid .info-row.formula-row .info-value { font-size:0.85rem; }
    .sheet { max-width:560px; }
    .salary-split-nav { display:flex; gap:10px; flex-wrap:wrap; margin:10px 0 16px; }
    .salary-split-nav a {
      padding:10px 16px;
      border-radius:10px;
      border:1px solid #dbe3ef;
      text-decoration:none;
      color:#334155;
      font-weight:600;
      background:#f8fafc;
    }
    .salary-split-nav a.active {
      background:#2563eb;
      border-color:#2563eb;
      color:#fff;
    }
    .payroll-control-grid { align-items:end; }
    .payroll-actions {
      display:flex;
      gap:10px;
      justify-content:flex-end;
      flex:1 1 360px;
      flex-wrap:wrap;
      align-items:flex-end;
      min-height:46px;
    }
    .payroll-actions .btn-primary,
    .payroll-actions .btn-ghost {
      width:auto;
      min-width:180px;
      padding:10px 16px;
      white-space:nowrap;
    }
    .payroll-actions .muted {
      width:100%;
      text-align:right;
      margin-top:2px;
    }
    @media (max-width: 1100px) {
      .payroll-actions { justify-content:flex-start; }
      .payroll-actions .muted { text-align:left; }
    }
    @media (max-width: 640px) {
      .detail-tabs { flex-direction:column; }
      .detail-tab { justify-content:space-between; }
      .tab-panel { display:none; }
      .tab-panel.active { display:block; }
      .info-grid .info-row { grid-template-columns:1fr; }
    }
  </style>
</head>
<body>
  @php
    $salarySection = $salarySection ?? 'calculation';
    $adjustmentPage = $adjustmentPage ?? 'payroll';
  @endphp
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
      <div class="breadcrumb">Home > Payroll > {{ $salarySection === 'adjustment' ? 'Salary Adjustment' : 'Salary Calculation' }}</div>
      <h2>{{ $salarySection === 'adjustment' ? 'Salary Adjustment' : 'Salary Calculation' }}</h2>
      <p class="subtitle">
        @if($salarySection === 'adjustment')
          Manage payroll corrections and base-salary updates for draft payroll periods.
        @else
          Gross -> Deductions -> Net pay, with clear rates, details, and quick adjustments.
        @endif
      </p>
      @php
        $salaryTabQuery = ['period' => $currentPeriod];
        if (isset($currentDept) && $currentDept !== '' && $currentDept !== null) {
            $salaryTabQuery['dept'] = $currentDept;
        }
        if (! empty($currentEmployee ?? '')) {
            $salaryTabQuery['employee'] = $currentEmployee;
        }
      @endphp
      <nav class="salary-split-nav" aria-label="Salary page navigator" style="margin-top:-4px;">
        <a href="{{ route('admin.payroll.salary', $salaryTabQuery) }}" class="{{ $salarySection === 'calculation' ? 'active' : '' }}">Salary Calculation</a>
        <a href="{{ route('admin.payroll.salary.adjustments', $salaryTabQuery) }}" class="{{ $salarySection === 'adjustment' && $adjustmentPage === 'payroll' ? 'active' : '' }}">Payroll Adjustment</a>
        <a href="{{ route('admin.payroll.salary.basic_salary', $salaryTabQuery) }}" class="{{ $salarySection === 'adjustment' && $adjustmentPage === 'basic' ? 'active' : '' }}">Basic Salary Update</a>
      </nav>

      @php
        $status = strtoupper($payrollStatus ?? 'OPEN');
        $statusClass = [
          'OPEN' => 'badge-open',
          'DRAFT' => 'badge-draft',
          'LOCKED' => 'badge-locked',
          'PAID' => 'badge-paid',
          'PUBLISHED' => 'badge-published',
        ][$status] ?? 'badge-open';
      @endphp

      <div class="card status-card" style="{{ $salarySection === 'adjustment' ? 'display:none;' : '' }}">
        <div>
          <div class="muted" style="font-weight:700; letter-spacing:0.04em; text-transform:uppercase; margin-bottom:6px;">Payroll Status</div>
          <span id="payrollStatusBadge" class="status-badge {{ $statusClass }}">{{ $status }}</span>
        </div>
        <div class="status-helper">Each month has its own status. Only the selected month is affected by Generate, Release, or Adjustments.</div>
        @if(in_array($status, ['LOCKED', 'PAID', 'PUBLISHED']))
          <div class="status-helper" style="color:#b91c1c; font-weight:600;">Payroll for this month is locked. Corrections must be applied in a later month.</div>
        @else
          <div class="status-helper">Only DRAFT payroll can be recalculated or adjusted. Release (DRAFT → LOCKED) freezes this month only; other months are unchanged.</div>
        @endif

        @php
          $showLocked    = $status === 'LOCKED' || (!empty($payrollMeta['locked_by']) || !empty($payrollMeta['locked_at']));
          $showPaid      = $status === 'PAID' || !empty($payrollMeta['paid_at']);
          $showPublished = $status === 'PUBLISHED' || !empty($payrollMeta['published_at']);
        @endphp

        <div class="meta-grid">
          <div class="meta-item">
            <div class="meta-label">Created by</div>
            <div class="meta-value">{{ $payrollMeta['created_by'] ?? '--' }}</div>
            <div id="generatedAtText" class="meta-sub muted">Generated at {{ $payrollMeta['generated_at'] ?? '--' }}</div>
          </div>
          @if($showLocked)
            <div class="meta-item">
              <div class="meta-label">Locked by</div>
              <div class="meta-value">{{ $payrollMeta['locked_by'] ?? '--' }}</div>
              <div class="meta-sub muted">Locked at {{ $payrollMeta['locked_at'] ?? '--' }}</div>
            </div>
          @endif
          @if($showPaid)
            <div class="meta-item">
              <div class="meta-label">Paid at</div>
              <div class="meta-value">{{ $payrollMeta['paid_at'] ?? '--' }}</div>
            </div>
          @endif
          @if($showPublished)
            <div class="meta-item">
              <div class="meta-label">Published at</div>
              <div class="meta-value">{{ $payrollMeta['published_at'] ?? '--' }}</div>
            </div>
          @endif
        </div>
      </div>

      <details class="card" style="margin-bottom:1rem; {{ $salarySection === 'adjustment' ? 'display:none;' : '' }}">
        <summary style="cursor:pointer; font-weight:700;">Payroll by month (each month has its own status)</summary>
        <p class="muted" style="margin:8px 0;">Release affects only the selected month. Other months remain unchanged.</p>
        <div style="overflow-x:auto;">
          <table class="table" style="margin-top:8px;">
            <thead>
              <tr>
                <th>Month</th>
                <th>Status</th>
                <th>Generated</th>
                <th>Released at</th>
                <th>Released by</th>
              </tr>
            </thead>
            <tbody>
              @foreach($payrollHistory ?? [] as $row)
                <tr>
                  <td>{{ $row['period_month'] }}</td>
                  <td><span class="status-badge {{ ($row['status'] === 'LOCKED' ? 'badge-locked' : ($row['status'] === 'DRAFT' ? 'badge-draft' : ($row['status'] === 'PAID' ? 'badge-paid' : ($row['status'] === 'PUBLISHED' ? 'badge-published' : 'badge-open')))) }}">{{ strtoupper($row['status']) }}</span></td>
                  <td>{{ $row['generated_at'] }}</td>
                  <td>{{ $row['released_at'] }}</td>
                  <td>{{ $row['released_by'] }}</td>
                </tr>
              @endforeach
              @if(empty($payrollHistory))
                <tr><td colspan="5" class="muted">No payroll periods yet.</td></tr>
              @endif
            </tbody>
          </table>
        </div>
      </details>

      <div class="card">
        <div class="grid payroll-control-grid">
          <div>
            <label>Payroll Period (Month)</label>
            <select id="period">
              @foreach($periodOptions as $period)
                <option value="{{ $period }}" {{ $period === $currentPeriod ? 'selected' : '' }}>{{ $period }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Department</label>
            <select id="dept">
              <option value="">All</option>
              @foreach($departments as $dept)
                <option value="{{ $dept->department_id }}" {{ (string)$dept->department_id === (string)($currentDept ?? '') ? 'selected' : '' }}>{{ $dept->department_name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="salary-emp-filter">Employee</label>
            <select id="salary-emp-filter">
              <option value="">All employees</option>
            </select>
          </div>
          <div id="actionButtonsWrap" class="payroll-actions">
            @if(in_array($status, ['OPEN','DRAFT']) && $status === 'DRAFT')
              <p id="release-window-msg" class="release-window-msg" style="{{ ($releaseWindowClosed ?? false) ? '' : 'display:none;' }}">Payroll release window for this previous month has passed.</p>
            @endif
            <button class="btn-ghost" id="action-lock" title="Release (lock) payroll" {{ ($releaseWindowClosed ?? false) ? 'disabled' : '' }} style="{{ $salarySection === 'adjustment' ? 'display:none;' : (in_array($status, ['OPEN','DRAFT']) ? ($status === 'DRAFT' ? '' : 'display:none;') : 'display:none;') }}">Release Payroll</button>
            <button class="btn-primary" id="action-generate" style="{{ $salarySection === 'adjustment' ? 'display:none;' : ($status === 'OPEN' ? '' : ($status === 'DRAFT' ? '' : 'display:none;')) }}">{{ $status === 'OPEN' ? 'Generate Payroll' : ($status === 'DRAFT' ? 'Recalculate Payroll' : '') }}</button>
            @if(in_array($status, ['LOCKED', 'PAID']))
              <button class="btn-primary" id="action-publish">Publish Payslips</button>
            @else
              <div class="muted">Read-only mode.</div>
            @endif
          </div>
        </div>
      </div>

      <div class="insights" id="insightsRow" style="display:none;">
        <div class="insight-card" data-filter="absent">
          <div class="insight-count" id="insight-absent">0</div>
          <div class="insight-label">Employees with Absent</div>
        </div>
        <div class="insight-card" data-filter="late">
          <div class="insight-count" id="insight-late">0</div>
          <div class="insight-label">Employees with Late</div>
        </div>
        <div class="insight-card" data-filter="unpaid">
          <div class="insight-count" id="insight-unpaid">0</div>
          <div class="insight-label">Employees with Unpaid Leave</div>
        </div>
        <div class="insight-card" data-filter="incomplete">
          <div class="insight-count" id="insight-incomplete">0</div>
          <div class="insight-label">Employees with Incomplete Punch</div>
        </div>
      </div>

      @php
        $adjCalMonthOnly = (bool) config('hrms.payroll.adjustments_calendar_month_only', true);
        $adjCalYm = now()->format('Y-m');
        $adjSelectedPeriod = $currentPeriod ?? $adjCalYm;
        $adjWrongCalendarMonth = $adjCalMonthOnly && $adjSelectedPeriod !== $adjCalYm;
      @endphp
      <div class="card" id="adjustments-card" style="{{ $salarySection === 'adjustment' && $adjustmentPage === 'payroll' ? '' : 'display:none;' }}">
        <div class="adj-tool-header">
          <div class="adj-tool-header-row">
            <div>
              <h3 style="margin:0 0 4px;">Payroll corrections</h3>
              <p class="adj-period-tie" id="adj-period-tie">For payroll month: <strong id="adj-period-label">{{ \Carbon\Carbon::createFromFormat('Y-m', $currentPeriod ?? now()->format('Y-m'))->format('F Y') }}</strong></p>
              <p class="muted" style="margin:6px 0 0; font-size:12px; max-width:52rem;">Earnings and deductions on this page apply <strong>only to this payroll month’s run</strong> (not basic salary or other months).</p>
              @if($adjCalMonthOnly)
                <p class="muted" style="margin:4px 0 0; font-size:12px; max-width:52rem;">New corrections can be <strong>saved only</strong> when <strong>Payroll Period</strong> is the <strong>current calendar month</strong> ({{ \Carbon\Carbon::now()->format('F Y') }}).</p>
              @endif
              @if($adjWrongCalendarMonth)
                <div class="adj-calendar-banner" id="adj-calendar-month-banner" role="alert">
                  <strong>Corrections are view-only for this period.</strong> Set Payroll Period to <strong>{{ \Carbon\Carbon::createFromFormat('Y-m', $adjCalYm)->format('F Y') }}</strong> to add earnings or deductions.
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="adj-card-body" id="adj-card-body">
        <div class="adj-form-section">
          <div class="grid">
            <div>
              <label for="adj-emp">Employee</label>
              <select id="adj-emp"><option value="">Select employee</option></select>
            </div>
          </div>

          <div id="adj-summary-box" class="adj-summary-box">
            <div class="adj-summary-title">Current payroll summary (this month)</div>
            <div class="adj-summary-grid" id="adj-summary-grid">
              <div class="item"><span class="k">Summary</span><span class="v">Select an employee to load payroll figures for this month.</span></div>
            </div>
          </div>

          <div class="adj-form-fields grid" id="adj-form-fields">
            <div>
              <label>Category</label>
              <select id="adj-type">
                <option value="earning">Earning</option>
                <option value="deduction">Deduction</option>
              </select>
            </div>
            <div>
              <label>Sub-type</label>
              <select id="adj-subtype">
                <option value="bonus">Bonus</option>
                <option value="allowance">Allowance</option>
                <option value="other_earning">Other (Earning)</option>
              </select>
            </div>
            <div>
              <label>Amount (RM)</label>
              <input type="number" id="adj-amount" step="0.01" min="0" placeholder="e.g. 150.00">
            </div>
            <div class="adj-reason-wrap">
              <label>Reason <span class="required">*</span></label>
              <textarea id="adj-reason" rows="2" placeholder="Describe the reason for this adjustment (min. 10 characters)" maxlength="500"></textarea>
            </div>
            <div class="adj-preview-wrap" id="adj-preview-wrap" style="display:none;">
              <div class="adj-preview-title">Impact preview</div>
              <div class="adj-preview-content" id="adj-preview-content"></div>
            </div>
            <div class="adj-buttons">
              <button type="button" class="btn-primary" id="adj-apply">Save adjustment</button>
              <button type="button" class="btn-ghost" id="adj-reset">Clear form</button>
            </div>
          </div>
        </div>

        <div class="muted" id="adj-note" style="margin-top:8px;">@if(in_array($status, ['LOCKED', 'PAID', 'PUBLISHED'])) Payroll for this month is locked. Corrections must be applied in a later month. @else Payroll is in DRAFT — adjustments are editable until release. @endif</div>

        <div class="adj-history-section" id="adj-history-section">
          <div class="adj-history-title">Recent adjustments (this employee, this month)</div>
          <div class="adj-history-editable" id="adj-history-editable"></div>
          <table class="adj-history-table" id="adj-history-table">
            <thead><tr><th>Category</th><th>Sub-type</th><th class="num">Amount</th><th>Reason</th><th>Date</th><th>Action</th></tr></thead>
            <tbody id="adj-history-tbody"></tbody>
          </table>
          <p class="muted adj-history-empty" id="adj-history-empty">Select an employee to see recent adjustments for this month.</p>
        </div>

        </div>
      </div>

      <div class="card" id="basic-salary-card" style="{{ $salarySection === 'adjustment' && $adjustmentPage === 'basic' ? '' : 'display:none;' }}">
        <div class="adj-basic-salary-section" id="adj-basic-salary-section" style="margin-top:0; padding-top:0; border-top:none;">
          <div class="adj-basic-salary-title">Basic salary (current &amp; future months)</div>
          <div class="adj-basic-salary-fields grid" id="adj-basic-salary-fields">
            <div class="adj-basic-emp-wrap">
              <label for="adj-basic-emp">Employee <span class="required">*</span></label>
              <select id="adj-basic-emp"><option value="">Select employee</option></select>
            </div>
            <div>
              <label>Effective from month</label>
              <input type="text" id="adj-effective-month" readonly class="readonly" placeholder="Same as selected period">
            </div>
            <div>
              <label>Current basic salary (RM)</label>
              <input type="text" id="adj-current-base" readonly class="readonly" placeholder="Select employee">
            </div>
            <div>
              <label for="adj-new-base">New basic salary (RM) <span class="required">*</span></label>
              <input type="number" id="adj-new-base" step="0.01" min="0.01" placeholder="e.g. 3500.00">
            </div>
            <div class="adj-basic-reason-wrap">
              <label for="adj-basic-reason">Reason (optional)</label>
              <textarea id="adj-basic-reason" rows="2" placeholder="e.g. Annual increment" maxlength="500"></textarea>
            </div>
            <div class="adj-basic-buttons">
              <button type="button" class="btn-primary" id="adj-update-basic-btn">Update basic salary</button>
            </div>
          </div>
          <div class="adj-basic-revision-history" id="adj-basic-revision-history" style="margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb;">
            <div class="adj-history-title">Basic salary adjustment history</div>
            <p class="muted" id="adj-basic-revision-hint" style="margin:0 0 10px; font-size:13px;">Select an employee to view recorded basic salary changes.</p>
            <div style="overflow-x:auto;">
              <table class="adj-history-table" id="adj-basic-revision-table">
                <thead>
                  <tr>
                    <th>Effective from</th>
                    <th class="num">Previous (RM)</th>
                    <th class="num">New (RM)</th>
                    <th>Reason</th>
                    <th>Recorded</th>
                    <th>By</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="adj-basic-revision-tbody"></tbody>
              </table>
            </div>
            <p class="muted adj-history-empty" id="adj-basic-revision-empty" style="margin-top:10px; display:none;"></p>
          </div>
          <div class="adj-basic-revision-history" id="adj-payroll-run-history" style="margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb;">
            <div class="adj-history-title">Recent payslips</div>
            <p class="muted" id="adj-payroll-history-hint" style="margin:0 0 10px; font-size:13px;">Select an employee. Same list as employee <strong>My Payroll</strong> (released periods only: LOCKED / PAID / PUBLISHED).</p>
            <div style="overflow-x:auto;">
              <table class="adj-history-table" id="adj-payroll-history-table">
                <thead>
                  <tr>
                    <th>Period</th>
                    <th class="num">Gross</th>
                    <th class="num">Net</th>
                    <th>Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="adj-payroll-history-tbody"></tbody>
              </table>
            </div>
            <p class="muted adj-history-empty" id="adj-payroll-history-empty" style="margin-top:10px; display:none;"></p>
          </div>
        </div>
      </div>

      <div class="adj-payroll-overlay" id="basicAdjPayrollDetailOverlay" aria-hidden="true">
        <div class="adj-payroll-detail-card">
          <div class="card-head">
            <h3 id="basicAdjPayrollDetailTitle">Payroll details</h3>
            <button type="button" class="card-close" id="basicAdjPayrollDetailClose" aria-label="Close">&times;</button>
          </div>
          <div class="card-body" id="basicAdjPayrollDetailBody">
            <div class="muted" style="text-align:center; padding:2rem;">Loading…</div>
          </div>
        </div>
      </div>

      <style>
        .adj-tool-header { margin-bottom:12px; }
        .adj-tool-header-row { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; }
        .adj-period-tie { margin:0; font-size:13px; color:#64748b; }
        .adj-calendar-banner { background:#fff7ed; border:1px solid #fdba74; color:#9a3412; border-radius:10px; padding:10px 12px; margin:10px 0 0; font-size:13px; line-height:1.4; }
        .adj-summary-box { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin:12px 0; }
        .adj-summary-title { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin-bottom:10px; font-weight:700; }
        .adj-summary-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(100px, 1fr)); gap:10px; font-size:14px; }
        .adj-summary-grid .item { display:flex; flex-direction:column; }
        .adj-summary-grid .item .k { color:#64748b; font-size:12px; }
        .adj-summary-grid .item .v { font-weight:700; color:#0f172a; }
        .adj-form-fields { margin-top:14px; }
        .adj-reason-wrap { grid-column:1 / -1; }
        .adj-reason-wrap textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; resize:vertical; min-height:60px; }
        .adj-preview-wrap { grid-column:1 / -1; background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:12px; margin-top:8px; }
        .adj-preview-title { font-size:12px; font-weight:700; color:#1e40af; margin-bottom:6px; }
        .adj-preview-content { font-size:14px; color:#1e3a8a; }
        .adj-buttons { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .adj-history-section { margin-top:20px; padding-top:16px; border-top:1px solid #e5e7eb; }
        .adj-history-title { font-size:13px; font-weight:700; color:#0f172a; margin-bottom:10px; }
        .adj-history-editable { font-size:12px; color:#059669; margin-bottom:8px; }
        .adj-history-table { width:100%; border-collapse:collapse; font-size:13px; }
        .adj-history-table th, .adj-history-table td { padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; }
        .adj-history-table th.num, .adj-history-table td.num { text-align:right; }
        .adj-revision-cancelled td { color:#64748b; background:#f8fafc; }
        .adj-revision-status-active { font-size:12px; font-weight:600; color:#166534; }
        .adj-revision-status-cancelled { font-size:12px; font-weight:600; color:#64748b; }
        .btn-cancel-revision { padding:6px 10px; font-size:12px; border-radius:8px; border:1px solid #fecaca; background:#fef2f2; color:#b91c1c; cursor:pointer; font-family:inherit; }
        .btn-cancel-revision:hover:not(:disabled) { background:#fee2e2; }
        .btn-cancel-revision:disabled { opacity:0.45; cursor:not-allowed; }
        .adj-history-empty { margin:10px 0 0; font-size:13px; }
        .required { color:#b91c1c; }
        .adj-basic-salary-section { margin-top:24px; padding-top:20px; border-top:1px solid #e5e7eb; }
        .adj-basic-salary-title { font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px; }
        .adj-basic-salary-fields { align-items:flex-end; }
        .adj-basic-emp-wrap { grid-column:1 / -1; }
        .adj-basic-reason-wrap { grid-column:1 / -1; }
        .adj-basic-reason-wrap textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:14px; resize:vertical; min-height:56px; }
        .adj-basic-buttons { display:flex; gap:10px; align-items:center; }
        input.readonly { background:#f1f5f9; color:#475569; cursor:default; }
        .adj-payroll-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.4); display:none; align-items:center; justify-content:center; z-index:1000; padding:20px; }
        .adj-payroll-overlay.show { display:flex; }
        .adj-payroll-detail-card { background:#fff; border-radius:14px; box-shadow:0 20px 50px rgba(0,0,0,0.2); max-width:520px; width:100%; max-height:90vh; overflow:auto; }
        .adj-payroll-detail-card .card-head { padding:16px 20px; border-bottom:1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; }
        .adj-payroll-detail-card .card-head h3 { margin:0; font-size:1.1rem; color:#0f172a; }
        .adj-payroll-detail-card .card-close { background:none; border:none; font-size:1.4rem; color:#64748b; cursor:pointer; padding:0 4px; line-height:1; }
        .adj-payroll-detail-card .card-body { padding:20px; }
        .adj-payroll-detail-card .detail-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
        .adj-payroll-detail-card .detail-row:last-child { border-bottom:none; }
        .adj-payroll-detail-card .detail-section { font-size:12px; text-transform:uppercase; letter-spacing:.05em; color:#64748b; margin:14px 0 8px 0; }
        .adj-payroll-detail-card .detail-section:first-child { margin-top:0; }
        .adj-payroll-detail-card .total-row { font-weight:700; font-size:1.05rem; margin-top:10px; padding-top:10px; border-top:2px solid #e5e7eb; }
        .adj-payroll-detail-card .line-items { font-size:13px; width:100%; border-collapse:collapse; }
        .adj-payroll-detail-card .line-items th, .adj-payroll-detail-card .line-items td { padding:6px 8px; text-align:left; }
        .adj-payroll-detail-card .line-items .num { text-align:right; }
        .adj-payroll-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .adj-btn-view-pay { padding:6px 12px; font-size:13px; border-radius:8px; background:#6366f1; color:#fff; border:none; cursor:pointer; }
        .adj-btn-view-pay:hover { background:#4f46e5; color:#fff; }
        .adj-btn-dl-pay { padding:6px 10px; font-size:13px; border-radius:8px; background:#f1f5f9; color:#334155; border:1px solid #e2e8f0; text-decoration:none; display:inline-block; }
        .adj-btn-dl-pay:hover { background:#e2e8f0; color:#0f172a; }
      </style>

      <div class="card" style="{{ $salarySection === 'adjustment' ? 'display:none;' : '' }}">
        <table id="tbl">
          <thead>
            <tr>
              <th>Employee</th>
              <th>Department</th>
              <th class="num sortable" data-sort="base">Basic Salary</th>
              <th class="num">Allowance</th>
              <th class="num">EPF</th>
              <th class="num">Tax</th>
              <th class="num">Adjustments</th>
              <th class="num sortable" data-sort="net">Net pay</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <section class="pagination-wrap" style="display:{{ $salarySection === 'adjustment' ? 'none' : 'flex' }}; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-top:12px;">
        <span class="pagination-info" id="paginationInfo">0 records</span>
        <div style="display:flex; align-items:center; gap:10px;">
          <button type="button" class="btn btn-ghost btn-icon" id="firstPage" disabled><i class="fa-solid fa-angles-left"></i> First</button>
          <button type="button" class="btn btn-ghost btn-icon" id="prevPage" disabled>Prev</button>
          <span id="pageNum">Page 1 of 1</span>
          <button type="button" class="btn btn-ghost btn-icon" id="nextPage" disabled>Next</button>
          <button type="button" class="btn btn-ghost btn-icon" id="lastPage" disabled>Last <i class="fa-solid fa-angles-right"></i></button>
        </div>
        <div>
          <label>Show </label>
          <select id="perPage">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </section>

      <footer style="text-align:center; color:#94a3b8; font-size:12px;">Ac 2025 Web-Based HRMS. All Rights Reserved.</footer>
    </main>
  </div>

  <div class="modal" id="modal">
    <div class="sheet">
      <h3 style="margin:0 0 4px;">Salary Details</h3>
      <div id="meta" class="salary-detail-header"></div>
      <div id="formulaBox" class="salary-detail-formula"></div>

      <div class="detail-tabs">
        <button class="detail-tab active" data-tab="breakdown"><i class="fa-solid fa-calculator"></i> Payroll Breakdown</button>
        <button class="detail-tab" data-tab="attendance"><i class="fa-solid fa-calendar"></i> Attendance Source</button>
        <button class="detail-tab" data-tab="bank"><i class="fa-solid fa-building-columns"></i> Bank Account</button>
      </div>

      <div class="tab-panels">
        <div class="tab-panel active" id="tab-breakdown">
          <table style="width:100%; border-collapse:collapse; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
            <tbody id="breakdown"></tbody>
          </table>
        </div>

        <div class="tab-panel" id="tab-attendance">
          <div class="info-grid" id="attendanceLists"></div>
        </div>

        <div class="tab-panel" id="tab-bank">
          <div class="info-grid" id="bankDetails"></div>
        </div>
      </div>

      <div style="margin-top:14px; text-align:right;">
        <button class="btn-ghost" id="close">Close</button>
      </div>
    </div>
  </div>

  <!-- Release: payroll report preview (step 1), then password modal (step 2) -->
  <div class="modal" id="releasePayrollReportModal" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box release-report-box">
      <h3 class="release-report-title" style="margin:0 0 4px;">HR Payroll Report</h3>
      <p class="release-report-sub">Prepared by: {{ auth()->user()->name ?? 'HR Admin' }} · {{ config('app.name', 'Your Company') }}</p>
      <p class="muted" style="margin:0 0 8px; font-size:13px;">Review this summary for the payroll you are about to release. If anything looks wrong, cancel and correct it before releasing.</p>
      <div class="release-report-body" id="releaseReportBody">
        <p class="muted">Loading…</p>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn-ghost" id="releaseReportCancel">Cancel</button>
        <button type="button" class="btn-primary" id="releaseReportProceed" disabled>Proceed to release payroll</button>
      </div>
    </div>
  </div>

  <!-- Confirmation modals -->
  <div class="modal" id="confirmLock" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 style="margin:0 0 8px;">Release Payroll</h3>
      <p style="margin:0 0 8px;" id="releasePeriodMessage">This will lock the payroll for the selected month. You cannot edit after release. Corrections must be done as next-month adjustments.</p>
      <p style="margin:0 0 10px; font-weight:700; color:#0f172a; font-size:15px;" id="releasePeriodLabel"></p>
      <div style="margin-bottom:10px;">
        <label style="display:block; font-weight:600; margin-bottom:4px;">Release note (recommended)</label>
        <textarea id="releaseNote" rows="3" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:8px;" placeholder="Optional note for audit trail"></textarea>
      </div>
      <div style="margin-bottom:12px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Re-enter your password <span style="color:#b91c1c;">*</span></label>
        <input type="password" id="releasePassword" autocomplete="current-password" placeholder="Enter your admin password" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:15px;">
        <small style="display:block; margin-top:4px; color:#64748b;">You must confirm your password to release payroll.</small>
      </div>
      <label style="display:flex; gap:8px; align-items:center; font-size:13px; color:#475569;">
        <input type="checkbox" id="lockConfirmCheck"> I understand this action is irreversible for this period.
      </label>
      <div class="confirm-actions">
        <button class="btn-ghost" id="lockCancel">Cancel</button>
        <button class="btn-primary" id="lockConfirmBtn" disabled>Release Payroll</button>
      </div>
    </div>
  </div>

  <div class="modal" id="confirmPublish" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 style="margin:0 0 8px;">Publish Payslips</h3>
      <p style="margin:0;">Employees will be able to view their payslips for this period.</p>
      <div class="confirm-actions">
        <button class="btn-ghost" id="publishCancel">Cancel</button>
        <button class="btn-primary" id="publishConfirmBtn">Publish Payslips</button>
      </div>
    </div>
  </div>

  <div class="modal" id="confirmAdjustment" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 class="adj-confirm-modal-title"><span class="lock-icon" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></span> Confirm payroll correction</h3>
      <p class="muted" style="margin:0 0 8px; font-size:14px;">This updates the <strong>draft</strong> payroll run only. Releasing the whole month still requires your password. The change is logged in the payroll audit trail.</p>
      <div id="adjConfirmSummary" class="adj-confirm-summary"></div>
      <label class="adj-confirm-check">
        <input type="checkbox" id="adjConfirmAck" autocomplete="off">
        <span>I confirm this payroll correction is accurate and authorized.</span>
      </label>
      <div style="margin-bottom:4px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Remark (optional)</label>
        <textarea id="adjConfirmRemark" rows="2" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; resize:vertical;" placeholder="Note for audit records"></textarea>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn-ghost" id="adjConfirmCancel">Cancel</button>
        <button type="button" class="btn-primary btn-confirm-save" id="adjConfirmSave" disabled><i class="fa-solid fa-check"></i> Confirm &amp; save</button>
      </div>
    </div>
  </div>

  <div class="modal" id="confirmCancelAdjustment" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 class="adj-confirm-modal-title"><span class="lock-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span> Remove payroll correction</h3>
      <p class="muted" style="margin:0 0 8px; font-size:14px;">This deletes the selected correction line and recalculates draft totals. Enter your password to confirm.</p>
      <div id="cancelAdjSummary" class="adj-confirm-summary"></div>
      <div style="margin-bottom:10px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Password <span style="color:#b91c1c;">*</span></label>
        <input type="password" id="cancelAdjPassword" autocomplete="current-password" placeholder="Your account password" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:15px;">
      </div>
      <p id="cancelAdjError" style="display:none; margin:0 0 8px; font-size:13px; color:#b91c1c;"></p>
      <div class="confirm-actions">
        <button type="button" class="btn-ghost" id="cancelAdjDismiss">Close</button>
        <button type="button" class="btn-primary btn-confirm-save" id="cancelAdjSubmit" style="background:#b91c1c;"><i class="fa-solid fa-trash-can"></i> Remove correction</button>
      </div>
    </div>
  </div>

  <div class="modal" id="confirmBasicSalary" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 class="adj-confirm-modal-title"><span class="lock-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span> Confirm basic salary update</h3>
      <p class="muted" style="margin:0 0 8px; font-size:14px;">Re-enter your password to apply this change. It updates the employee record and recalculates draft payroll from the effective month.</p>
      <div id="basicSalaryConfirmSummary" class="adj-confirm-summary"></div>
      <label class="adj-confirm-check">
        <input type="checkbox" id="basicSalaryConfirmAck" autocomplete="off">
        <span>I confirm this basic salary change is accurate and authorized.</span>
      </label>
      <div style="margin-bottom:10px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Password <span style="color:#b91c1c;">*</span></label>
        <input type="password" id="basicSalaryConfirmPassword" autocomplete="current-password" placeholder="Your account password" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:15px;">
      </div>
      <div style="margin-bottom:4px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Remark (optional)</label>
        <textarea id="basicSalaryConfirmRemark" rows="2" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px; resize:vertical;" placeholder="Note for audit records"></textarea>
      </div>
      <div class="confirm-actions">
        <button type="button" class="btn-ghost" id="basicSalaryConfirmCancel">Cancel</button>
        <button type="button" class="btn-primary btn-confirm-save" id="basicSalaryConfirmSave" disabled><i class="fa-solid fa-check"></i> Confirm &amp; update</button>
      </div>
    </div>
  </div>

  <div class="modal" id="confirmCancelBasicRevision" style="display:none; align-items:center; justify-content:center;">
    <div class="confirm-box">
      <h3 class="adj-confirm-modal-title"><span class="lock-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span> Cancel basic salary change</h3>
      <p class="muted" style="margin:0 0 8px; font-size:14px;">This restores the employee&rsquo;s basic salary to the <strong>previous</strong> amount and recalculates <strong>draft</strong> payroll from the effective month. Re-enter your password to confirm.</p>
      <div id="cancelBasicRevisionSummary" class="adj-confirm-summary"></div>
      <div style="margin-bottom:10px;">
        <label style="display:block; font-weight:600; margin-bottom:4px; color:#0f172a;">Password <span style="color:#b91c1c;">*</span></label>
        <input type="password" id="cancelBasicRevisionPassword" autocomplete="current-password" placeholder="Your account password" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:15px;">
      </div>
      <p id="cancelBasicRevisionError" style="display:none; margin:0 0 8px; font-size:13px; color:#b91c1c;"></p>
      <div class="confirm-actions">
        <button type="button" class="btn-ghost" id="cancelBasicRevisionDismiss">Close</button>
        <button type="button" class="btn-primary btn-confirm-save" id="cancelBasicRevisionSubmit" style="background:#b91c1c;"><i class="fa-solid fa-rotate-left"></i> Cancel revision</button>
      </div>
    </div>
  </div>

  <script>
    const ADJ_CALENDAR_MONTH_ONLY = {{ $adjCalMonthOnly ? 'true' : 'false' }};
    const ADJ_CALENDAR_MONTH_YM = @json($adjCalYm);
  document.addEventListener('DOMContentLoaded', () => {

    const INITIAL_EMPLOYEE_FILTER = @json((string)($currentEmployee ?? ''));
    let salaryEmpFilterInitialApplied = false;
    const ENDPOINT = "{{ route('admin.payroll.salary.data') }}";
    const SALARY_PAGE_URL = "{{ $salarySection === 'adjustment' ? ($adjustmentPage === 'basic' ? route('admin.payroll.salary.basic_salary') : route('admin.payroll.salary.adjustments')) : route('admin.payroll.salary') }}";

    function salaryPageQueryParams(opts = {}) {
      const period = opts.period != null ? opts.period : document.getElementById('period')?.value;
      const q = new URLSearchParams({ period: period || '' });
      const dept = opts.dept != null ? opts.dept : document.getElementById('dept')?.value;
      if (dept) q.set('dept', dept);
      const emp = opts.employee != null ? opts.employee : (document.getElementById('salary-emp-filter')?.value || '');
      if (emp) q.set('employee', emp);
      return q;
    }

    function syncSalaryFiltersToUrl() {
      try {
        const path = window.location.pathname;
        const q = salaryPageQueryParams();
        history.replaceState(null, '', path + (q.toString() ? '?' + q.toString() : ''));
      } catch (e) { /* ignore */ }
    }

    function refreshSalaryEmployeeFilter(employees) {
      const sel = document.getElementById('salary-emp-filter');
      if (!sel) return;
      const fromUrlBootstrap = !sel.value && !salaryEmpFilterInitialApplied && INITIAL_EMPLOYEE_FILTER;
      let preserved = sel.value;
      if (!preserved && fromUrlBootstrap) {
        preserved = INITIAL_EMPLOYEE_FILTER;
      }
      const prefix = '<option value="">All employees</option>';
      const opts = (employees || []).map(e => {
        const vid = e.employee_id != null ? e.employee_id : e.id;
        return `<option value="${vid}">${e.name} (${e.id})</option>`;
      }).join('');
      sel.innerHTML = prefix + opts;
      const optExists = preserved && [...sel.options].some(o => String(o.value) === String(preserved));
      if (!optExists) {
        sel.value = '';
        if (fromUrlBootstrap) salaryEmpFilterInitialApplied = true;
      } else {
        sel.value = String(preserved);
        if (INITIAL_EMPLOYEE_FILTER && String(sel.value) === String(INITIAL_EMPLOYEE_FILTER)) {
          salaryEmpFilterInitialApplied = true;
        }
      }
    }
    const ROUTES = {
      generate: "{{ route('admin.payroll.salary.generate') }}",
      adjustment: "{{ route('admin.payroll.salary.adjustment') }}",
      adjustment_cancel: "{{ route('admin.payroll.salary.adjustment_cancel') }}",
      adjustment_summary: "{{ route('admin.payroll.salary.adjustment_summary') }}",
      release_report_adjustments: "{{ route('admin.payroll.salary.release_report_adjustments') }}",
      update_basic_salary: "{{ route('admin.payroll.salary.update_basic_salary') }}",
      cancel_basic_salary_revision: "{{ route('admin.payroll.salary.cancel_basic_salary_revision') }}",
      basic_salary_revisions: "{{ route('admin.payroll.salary.basic_salary_revisions') }}",
      employee_payroll_history: "{{ route('admin.payroll.salary.employee_payroll_history') }}",
      salary_detail: "{{ route('admin.payroll.salary.detail') }}",
      payslip_download_prefix: "{{ url('/admin/payroll/salary/payslip') }}",
      lock: "{{ route('admin.payroll.salary.lock') }}",
      pay: "{{ route('admin.payroll.salary.pay') }}",
      publish: "{{ route('admin.payroll.salary.publish') }}",
    };
    const ADJ_SUBTYPES = {
      earning: [
        { value: 'bonus', label: 'Bonus' },
        { value: 'allowance', label: 'Allowance' },
        { value: 'other_earning', label: 'Other (Earning)' },
      ],
      deduction: [
        { value: 'deduction', label: 'Deduction' },
        { value: 'late_penalty', label: 'Late Penalty' },
        { value: 'absence', label: 'Absence' },
        { value: 'other_deduction', label: 'Other (Deduction)' },
      ],
    };
    function getCSRF() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return (meta && meta.getAttribute('content')) || '';
    }
    const CSRF = getCSRF();
    const toast = document.createElement('div');
    toast.setAttribute('role', 'alert');
    toast.style.cssText = 'position:fixed; top:24px; right:24px; padding:20px 28px; border-radius:14px; box-shadow:0 20px 40px rgba(0,0,0,0.25), 0 0 0 1px rgba(0,0,0,0.05); display:none; z-index:99999; font-size:17px; font-weight:700; max-width:420px; min-width:280px; align-items:center; gap:14px; line-height:1.35;';
    toast.style.display = 'none';
    const toastIcon = document.createElement('span');
    toastIcon.setAttribute('aria-hidden', 'true');
    toast.appendChild(toastIcon);
    const toastText = document.createElement('span');
    toast.appendChild(toastText);
    document.body.appendChild(toast);
    const TOAST_STYLES = {
      success: {
        bg: '#ecfdf5',
        border: '#10b981',
        text: '#065f46',
        icon: '<i class="fa-solid fa-circle-check" style="font-size:1.5em;"></i>',
      },
      error: {
        bg: '#fef2f2',
        border: '#dc2626',
        text: '#991b1b',
        icon: '<i class="fa-solid fa-circle-xmark" style="font-size:1.5em;"></i>',
      },
      info: {
        bg: '#eff6ff',
        border: '#3b82f6',
        text: '#1e40af',
        icon: '<i class="fa-solid fa-circle-info" style="font-size:1.5em;"></i>',
      },
    };
    function showToast(msg, type = 'info') {
      const style = TOAST_STYLES[type] || TOAST_STYLES.info;
      toast.style.background = style.bg;
      toast.style.border = '3px solid ' + style.border;
      toast.style.color = style.text;
      toast.style.borderLeftWidth = '8px';
      toast.style.borderLeftColor = style.border;
      toastIcon.innerHTML = style.icon;
      toastIcon.style.color = style.border;
      toastText.textContent = msg;
      toast.style.display = 'flex';
      clearTimeout(toast._tid);
      toast._tid = setTimeout(() => { toast.style.display = 'none'; }, 6000);
    }
    const PAYROLL_STATUS = "{{ $payrollStatus }}";
    const RELEASE_WINDOW_CLOSED = {{ ($releaseWindowClosed ?? false) ? 'true' : 'false' }};
    let currentPayrollStatus = PAYROLL_STATUS;
    const CAN_ADJUST = () => String(currentPayrollStatus || '').toUpperCase() === 'DRAFT';
    let DATA = [];
    let FILTER = null;
    let currentPage = 1;
    let perPage = 25;
    let pagination = { total: 0, last_page: 1, current_page: 1 };
    const ADJ = {};

    const $ = (s) => document.querySelector(s);
    const tbody = $('#tbl tbody');

    const money = (n) => Number(n ?? 0).toLocaleString('en-MY', { minimumFractionDigits:2, maximumFractionDigits:2 });

    function calc(e) {
      // Formula: Gross = Basic Salary + Allowance + Adjustments; Deductions = Late + Absent + Unpaid Leave + Penalties + EPF + Tax; Net pay = Gross - Deductions
      const base = Number(e.base || 0);
      const allow = Number(e.allow || 0);
      const adjTotal = Number(e.adjustment_total ?? 0);
      const gross = base + allow + adjTotal;
      const lateDed = Number(e.late_ded ?? 0);
      const absentDed = Number(e.absent_ded ?? 0);
      const unpaidDed = Number(e.unpaid_ded ?? 0);
      const penaltyDed = Number(e.penalty ?? 0);
      const employeeEpf = Number(e.epfTax || 0);
      const tax = Number(e.tax_total || 0);
      const totalDeductions = lateDed + absentDed + unpaidDed + penaltyDed + employeeEpf + tax;
      const net = gross - totalDeductions;

      const adj = (adjTotal !== 0) ? { amount: Math.abs(adjTotal), type: adjTotal >= 0 ? 'earning' : 'deduction' } : (ADJ[e.id] || null);
      const adjAmount = adj ? (adj.type === 'deduction' ? -Number(adj.amount) : Number(adj.amount)) : 0;
      const isDeduction = adj && adj.type === 'deduction';

      return {
        gross,
        deductions: totalDeductions,
        net,
        adj,
        adjAmount,
        isDeduction,
      };
    }

    function applyFilter(rows) {
      if (!FILTER) return rows;
      switch (FILTER) {
        case 'absent': return rows.filter(r => (r.absent_days || 0) > 0);
        case 'late': return rows.filter(r => (r.late_minutes || 0) > 0);
        case 'unpaid': return rows.filter(r => (r.unpaid_leave_days || 0) > 0);
        case 'incomplete': return rows.filter(r => (r.incomplete_punches || 0) > 0);
        default: return rows;
      }
    }

    function render(rows) {
      const filtered = applyFilter(rows);
      tbody.innerHTML = '';
      if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="9">No records.</td></tr>';
        return;
      }
      filtered.forEach(e => {
        const c = calc(e);
        const displayNet = Math.max(0, (e.net != null && e.net !== undefined && e.net !== '') ? Number(e.net) : c.net);
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><strong>${e.name}</strong><br><span class="muted">${e.id}</span></td>
          <td>${e.dept}</td>
          <td class="num"><strong>${money(e.base)}</strong></td>
          <td class="num" title="${e.allowItems.map(i => i[0] + ': RM ' + i[1]).join(', ')}">${money(e.allow)}</td>
          <td class="num">-${money(e.epfTax || 0)}</td>
          <td class="num">-${money(e.tax_total || 0)}</td>
          <td class="num">${c.adj ? `<span class="chip" style="display:inline-flex;align-items:center;gap:6px;padding:6px 10px;font-size:12px;">Adj ${c.isDeduction ? '-' : '+'}RM ${money(c.adjAmount)}</span>` : '—'}</td>
          <td class="num"><strong>${money(displayNet)}</strong></td>
          <td><button class="btn-ghost" data-id="${e.id}">Details</button></td>
        `;
        tbody.appendChild(tr);
      });
      document.querySelectorAll('[data-id]').forEach(btn => {
        btn.addEventListener('click', () => {
          const row = rows.find(x => x.id === btn.dataset.id);
          if (row) openModal(row);
        });
      });
    }

    function updateInsights(rows) {
      const counts = {
        absent: rows.filter(r => (r.absent_days || 0) > 0).length,
        late: rows.filter(r => (r.late_minutes || 0) > 0).length,
        unpaid: rows.filter(r => (r.unpaid_leave_days || 0) > 0).length,
        incomplete: rows.filter(r => (r.incomplete_punches || 0) > 0).length,
      };
      document.getElementById('insight-absent').textContent = counts.absent;
      document.getElementById('insight-late').textContent = counts.late;
      document.getElementById('insight-unpaid').textContent = counts.unpaid;
      document.getElementById('insight-incomplete').textContent = counts.incomplete;
    }

    // Sorting
    const headers = document.querySelectorAll('th.sortable');
    headers.forEach(h => {
      h.style.cursor = 'pointer';
      h.addEventListener('click', () => {
        const key = h.dataset.sort;
        const dir = h.dataset.dir === 'desc' ? 'asc' : 'desc';
        h.dataset.dir = dir;
        DATA = [...DATA].sort((a, b) => {
          const av = key === 'net' ? ((a.net != null && a.net !== undefined && a.net !== '') ? Number(a.net) : calc(a).net) : Number(a.base || 0);
          const bv = key === 'net' ? ((b.net != null && b.net !== undefined && b.net !== '') ? Number(b.net) : calc(b).net) : Number(b.base || 0);
          return dir === 'asc' ? av - bv : bv - av;
        });
        render(DATA);
      });
    });


    function updatePagination() {
      const el = document.getElementById('paginationInfo');
      const num = document.getElementById('pageNum');
      const prevBtn = document.getElementById('prevPage');
      const nextBtn = document.getElementById('nextPage');
      const firstBtn = document.getElementById('firstPage');
      const lastBtn = document.getElementById('lastPage');
      if (el) el.textContent = (pagination.total || 0) + ' records';
      if (num) num.textContent = 'Page ' + (pagination.current_page || 1) + ' of ' + (pagination.last_page || 1);
      if (prevBtn) prevBtn.disabled = (pagination.current_page || 1) <= 1;
      if (nextBtn) nextBtn.disabled = (pagination.current_page || 1) >= (pagination.last_page || 1);
      if (firstBtn) firstBtn.disabled = (pagination.current_page || 1) <= 1;
      if (lastBtn) lastBtn.disabled = (pagination.current_page || 1) >= (pagination.last_page || 1);
    }

    async function loadData() {
      tbody.innerHTML = '<tr><td colspan="9">Loading...</td></tr>';
      const adjSelPre = document.getElementById('adj-emp');
      const basicEmpSelPre = document.getElementById('adj-basic-emp');
      const prevAdjEmp = adjSelPre?.value?.trim() || '';
      const prevBasicEmp = basicEmpSelPre?.value?.trim() || '';
      const params = new URLSearchParams({
        department: $('#dept').value,
        period: $('#period').value,
        page: String(currentPage),
        per_page: String(perPage),
      });
      let empF = document.getElementById('salary-emp-filter')?.value?.trim();
      if (!empF && !salaryEmpFilterInitialApplied && INITIAL_EMPLOYEE_FILTER) {
        empF = String(INITIAL_EMPLOYEE_FILTER).trim();
      }
      if (empF) params.set('employee_id', empF);
      try {
        const resp = await fetch(`${ENDPOINT}?${params.toString()}`, { headers: { 'Accept': 'application/json' }});
        if (!resp.ok) throw new Error('Failed to load salaries');
        const json = await resp.json();
        DATA = Array.isArray(json.data) ? json.data : [];
        pagination = json.pagination || { total: 0, last_page: 1, current_page: 1, per_page: perPage };
        currentPage = pagination.current_page || 1;
        if (pagination.per_page) perPage = pagination.per_page;
        const perPageEl = document.getElementById('perPage');
        if (perPageEl && perPageEl.value !== String(perPage)) perPageEl.value = String(perPage);
        if (json.insights) {
          document.getElementById('insight-absent').textContent = json.insights.absent ?? 0;
          document.getElementById('insight-late').textContent = json.insights.late ?? 0;
          document.getElementById('insight-unpaid').textContent = json.insights.unpaid ?? 0;
          document.getElementById('insight-incomplete').textContent = json.insights.incomplete ?? 0;
        } else {
          updateInsights(DATA);
        }
        render(DATA);
        updatePagination();
        const adjSel = document.getElementById('adj-emp');
        const basicEmpSel = document.getElementById('adj-basic-emp');
        function restoreAdjEmpSelections() {
          if (adjSel && prevAdjEmp && [...adjSel.options].some(o => String(o.value) === String(prevAdjEmp))) {
            adjSel.value = String(prevAdjEmp);
          }
          if (basicEmpSel && prevBasicEmp && [...basicEmpSel.options].some(o => String(o.value) === String(prevBasicEmp))) {
            basicEmpSel.value = String(prevBasicEmp);
          }
        }
        if (adjSel && Array.isArray(json.employees)) {
          const prefix = '<option value="">Select employee</option>';
          if (json.employees.length) {
            const opts = json.employees.map(e => {
              const vid = e.employee_id ?? e.id;
              return `<option value="${vid}">${e.name} (${e.id})</option>`;
            }).join('');
            adjSel.innerHTML = prefix + opts;
            if (basicEmpSel) basicEmpSel.innerHTML = prefix + opts;
          } else {
            adjSel.innerHTML = prefix;
            if (basicEmpSel) basicEmpSel.innerHTML = prefix;
          }
          restoreAdjEmpSelections();
          refreshSalaryEmployeeFilter(json.employees);
        } else {
          populateAdjEmp(DATA);
          restoreAdjEmpSelections();
          refreshSalaryEmployeeFilter(DATA.map(r => ({ employee_id: r.employee_id, id: r.id, name: r.name })));
        }
        if (prevAdjEmp && adjSel && String(adjSel.value) === String(prevAdjEmp)) {
          fetchAdjSummary();
        }
        syncSalaryFiltersToUrl();
        updateBasicSalaryFormEnabled();
        const lockBtn = document.getElementById('action-lock');
        if (lockBtn) lockBtn.disabled = RELEASE_WINDOW_CLOSED;
      } catch (err) {
        tbody.innerHTML = `<tr><td colspan="9">Error: ${err.message}</td></tr>`;
      }
    }

    function populateAdjEmp(rows) {
      const sel = document.getElementById('adj-emp');
      const selBasic = document.getElementById('adj-basic-emp');
      const activeOnly = (rows || []).filter(e => !e.employee_status || e.employee_status === 'active');
      const opts = '<option value="">Select employee</option>' + activeOnly.map(e => {
        const vid = e.employee_id ?? e.id;
        return `<option value="${vid}">${e.name} (${e.id})</option>`;
      }).join('');
      if (sel) sel.innerHTML = opts;
      if (selBasic) selBasic.innerHTML = opts;
    }

    $('#period').addEventListener('change', () => {
      const q = salaryPageQueryParams();
      window.location.href = SALARY_PAGE_URL + '?' + q.toString();
    });
    $('#dept').addEventListener('change', () => {
      const sf = document.getElementById('salary-emp-filter');
      if (sf) sf.value = '';
      currentPage = 1;
      loadData();
    });
    document.getElementById('salary-emp-filter')?.addEventListener('change', () => { currentPage = 1; loadData(); });
    document.getElementById('firstPage').addEventListener('click', () => { if (currentPage > 1) { currentPage = 1; loadData(); } });
    document.getElementById('prevPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadData(); } });
    document.getElementById('nextPage').addEventListener('click', () => { if (currentPage < (pagination.last_page || 1)) { currentPage++; loadData(); } });
    document.getElementById('lastPage').addEventListener('click', () => { if (currentPage < (pagination.last_page || 1)) { currentPage = pagination.last_page; loadData(); } });
    document.getElementById('perPage').addEventListener('change', function() { perPage = parseInt(this.value, 10); currentPage = 1; loadData(); });

    const btnGenerate = document.getElementById('action-generate');
    btnGenerate?.addEventListener('click', async (e) => {
      e.preventDefault();
      if (!btnGenerate) return;
      const originalText = btnGenerate.textContent;
      btnGenerate.disabled = true;
      btnGenerate.textContent = 'Processing...';
      try {
        const resp = await postAction(ROUTES.generate, {
          period_month: $('#period').value,
          department_id: $('#dept').value,
        });
        showToast(resp.message || 'Payroll generated successfully.', 'success');
        currentPayrollStatus = 'DRAFT';
        // Update UI to DRAFT without refresh
        const badge = document.getElementById('payrollStatusBadge');
        if (badge) {
          badge.textContent = 'DRAFT';
          badge.className = 'status-badge badge-draft';
        }
        const genAt = document.getElementById('generatedAtText');
        if (genAt) genAt.textContent = 'Generated at ' + new Date().toLocaleString('en-MY', { dateStyle: 'medium', timeStyle: 'short' });
        const btnGen = document.getElementById('action-generate');
        if (btnGen) { btnGen.textContent = 'Recalculate Payroll'; btnGen.style.display = ''; }
        const btnLock = document.getElementById('action-lock');
        if (btnLock) btnLock.style.display = 'inline-flex';
        await loadData();
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        btnGenerate.disabled = false;
        btnGenerate.textContent = currentPayrollStatus === 'DRAFT' ? 'Recalculate Payroll' : originalText;
      }
    });

    // Confirmation modal helpers
    const confirmLock = document.getElementById('confirmLock');
    const confirmPublish = document.getElementById('confirmPublish');
    const lockCheck = document.getElementById('lockConfirmCheck');
    const lockConfirmBtn = document.getElementById('lockConfirmBtn');

    const hideModal = (el) => { if (el) el.style.display = 'none'; };
    const showModal = (el) => { if (el) el.style.display = 'flex'; };

    const releasePasswordEl = document.getElementById('releasePassword');
    function updateLockConfirmBtnState() {
      const pwd = releasePasswordEl?.value?.trim() || '';
      lockConfirmBtn.disabled = !lockCheck?.checked || !pwd;
    }
    lockCheck?.addEventListener('change', updateLockConfirmBtnState);
    releasePasswordEl?.addEventListener('input', updateLockConfirmBtnState);

    async function postAction(url, payload) {
      const token = getCSRF();
      if (!token) {
        showToast('Session expired or invalid. Please refresh the page and try again.', 'error');
        return Promise.reject(new Error('CSRF token missing'));
      }
      const form = new FormData();
      form.append('_token', token);
      Object.entries(payload).forEach(([k,v]) => form.append(k, v));
      const resp = await fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body: form,
        credentials: 'same-origin',
      });
      if (!resp.ok) {
        const j = await resp.json().catch(() => ({}));
        throw new Error(j.message || 'Action failed');
      }
      return resp.json();
    }

    let releaseTargetPeriod = ''; // month selected when opening Release modal (YYYY-MM)
    function formatPeriodLabel(ym) {
      if (!ym) return '';
      const [y, m] = ym.split('-');
      const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      return (months[parseInt(m, 10) - 1] || m) + ' ' + y;
    }

    const releasePayrollReportModal = document.getElementById('releasePayrollReportModal');
    const releaseReportBody = document.getElementById('releaseReportBody');
    const releaseReportProceed = document.getElementById('releaseReportProceed');

    function escapeReportCell(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    function fmtReportMoney(n) {
      if (n == null || n === '' || Number.isNaN(Number(n))) return '—';
      return Number(n).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function fetchAllRowsForReleaseReport() {
      const period = document.getElementById('period')?.value;
      const dept = document.getElementById('dept')?.value || '';
      const all = [];
      let page = 1;
      let lastPage = 1;
      do {
        const params = new URLSearchParams({ period, page: String(page), per_page: '100' });
        if (dept) params.set('department', dept);
        const resp = await fetch(`${ENDPOINT}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
        if (!resp.ok) {
          const j = await resp.json().catch(() => ({}));
          throw new Error(j.message || 'Failed to load payroll data for the report.');
        }
        const json = await resp.json();
        const chunk = Array.isArray(json.data) ? json.data : [];
        all.push(...chunk);
        lastPage = json.pagination?.last_page || 1;
        page += 1;
      } while (page <= lastPage);
      return all;
    }

    async function fetchReleaseReportAdjustments(periodYm) {
      const dept = document.getElementById('dept')?.value || '';
      const params = new URLSearchParams({ period_month: periodYm });
      if (dept) params.set('department', dept);
      const resp = await fetch(ROUTES.release_report_adjustments + '?' + params.toString(), { headers: { 'Accept': 'application/json' } });
      if (!resp.ok) {
        const j = await resp.json().catch(() => ({}));
        throw new Error(j.message || 'Failed to load payroll adjustments for the report.');
      }
      const json = await resp.json();
      return Array.isArray(json.adjustments) ? json.adjustments : [];
    }

    function buildReleaseReportHtml(rows, periodYm, adjustments) {
      const deptSel = document.getElementById('dept');
      const deptLabel = deptSel?.options?.[deptSel.selectedIndex]?.text?.trim() || 'All';
      const [yy, mm] = (periodYm || '').split('-').map((x) => parseInt(x, 10));
      const start = (yy && mm) ? new Date(yy, mm - 1, 1) : new Date();
      const end = (yy && mm) ? new Date(yy, mm, 0) : new Date();
      const fmtD = (dt) => dt.toLocaleDateString('en-MY', { day: 'numeric', month: 'long', year: 'numeric' });

      const infoRows = (rows || []).map((r) => {
        const netPay = Number(r.net != null ? r.net : 0);
        return `<tr>
        <td>${escapeReportCell(r.name)}</td>
        <td>${escapeReportCell(r.id)}</td>
        <td>${escapeReportCell(r.dept)}</td>
        <td>${escapeReportCell(r.job_title != null ? r.job_title : '—')}</td>
        <td class="num"><strong>${fmtReportMoney(netPay)}</strong></td>
      </tr>`;
      }).join('');

      const adjList = Array.isArray(adjustments) ? adjustments : [];
      const adjRows = adjList.map((a) => {
        const impact = Number(a.signed_impact != null ? a.signed_impact : (a.category === 'Deduction' ? -Math.abs(Number(a.amount || 0)) : Number(a.amount || 0)));
        const amtHtml = impact < 0
          ? '<span style="color:#b91c1c;">−RM ' + escapeReportCell(fmtReportMoney(Math.abs(impact))) + '</span>'
          : 'RM ' + escapeReportCell(fmtReportMoney(Math.abs(impact)));
        return `<tr>
        <td>${escapeReportCell(a.employee_name)}</td>
        <td>${escapeReportCell(a.employee_id_display)}</td>
        <td>${escapeReportCell(a.department)}</td>
        <td>${escapeReportCell(a.category)}</td>
        <td>${escapeReportCell(a.sub_type)}</td>
        <td class="num">${amtHtml}</td>
        <td>${escapeReportCell(a.reason)}</td>
        <td>${escapeReportCell(a.recorded_at || '—')}</td>
      </tr>`;
      }).join('');

      return `
        <h4>I. Employee information</h4>
        <table>
          <thead><tr><th>Employee name</th><th>Employee ID</th><th>Department</th><th>Job title</th><th class="num">Net pay</th></tr></thead>
          <tbody>${infoRows || '<tr><td colspan="5" class="muted">No employees</td></tr>'}</tbody>
        </table>
        <h4>II. Pay period</h4>
        <p class="muted" style="margin:0 0 8px; font-size:12px;">Payroll month: <strong>${escapeReportCell(formatPeriodLabel(periodYm))}</strong> · Department scope: <strong>${escapeReportCell(deptLabel)}</strong> · <strong>${rows.length}</strong> employee(s)</p>
        <table>
          <thead><tr><th>Pay period start</th><th>Pay period end</th></tr></thead>
          <tbody><tr><td>${escapeReportCell(fmtD(start))}</td><td>${escapeReportCell(fmtD(end))}</td></tr></tbody>
        </table>
        <h4>III. Payroll adjustments (included in net pay)</h4>
        <p class="muted" style="margin:0 0 8px; font-size:12px;">Draft corrections saved under <strong>Payroll Adjustment</strong>. They are already reflected in net pay above and will <strong>lock together</strong> when you release this payroll.</p>
        <table>
          <thead><tr><th>Employee</th><th>Employee ID</th><th>Department</th><th>Category</th><th>Sub-type</th><th class="num">Amount</th><th>Reason</th><th>Recorded</th></tr></thead>
          <tbody>${adjRows || '<tr><td colspan="8" class="muted">No payroll adjustments for this month and department scope.</td></tr>'}</tbody>
        </table>
      `;
    }

    document.getElementById('action-lock')?.addEventListener('click', async (e) => {
      e.preventDefault();
      releaseTargetPeriod = $('#period').value;
      showModal(releasePayrollReportModal);
      if (releaseReportBody) releaseReportBody.innerHTML = '<p class="muted">Loading payroll report…</p>';
      if (releaseReportProceed) releaseReportProceed.disabled = true;
      try {
        const [rows, adjustments] = await Promise.all([
          fetchAllRowsForReleaseReport(),
          fetchReleaseReportAdjustments(releaseTargetPeriod),
        ]);
        if (releaseReportBody) releaseReportBody.innerHTML = buildReleaseReportHtml(rows, releaseTargetPeriod, adjustments);
        if (releaseReportProceed) releaseReportProceed.disabled = rows.length === 0;
      } catch (err) {
        if (releaseReportBody) releaseReportBody.innerHTML = '<p class="muted">' + escapeReportCell(err.message || 'Could not load report.') + '</p>';
        if (releaseReportProceed) releaseReportProceed.disabled = true;
      }
    });

    document.getElementById('releaseReportCancel')?.addEventListener('click', () => hideModal(releasePayrollReportModal));
    document.getElementById('releaseReportProceed')?.addEventListener('click', () => {
      hideModal(releasePayrollReportModal);
      const labelEl = document.getElementById('releasePeriodLabel');
      if (labelEl) labelEl.textContent = 'Month to release: ' + formatPeriodLabel(releaseTargetPeriod);
      lockCheck.checked = false;
      if (releasePasswordEl) releasePasswordEl.value = '';
      lockConfirmBtn.disabled = true;
      const releaseNote = document.getElementById('releaseNote');
      if (releaseNote) releaseNote.value = '';
      showModal(confirmLock);
    });

    document.getElementById('lockCancel')?.addEventListener('click', () => hideModal(confirmLock));
    lockConfirmBtn?.addEventListener('click', async () => {
      try {
        if (!releaseTargetPeriod) {
          showToast('Please close and open Release again to select the month.', 'error');
          return;
        }
        const password = releasePasswordEl?.value?.trim() || '';
        if (!password) {
          showToast('Please re-enter your password to confirm.', 'error');
          return;
        }
        const payload = { period_month: releaseTargetPeriod, password: password };
        const dept = $('#dept').value;
        if (dept) payload.department_id = dept;
        const note = document.getElementById('releaseNote')?.value?.trim();
        if (note) payload.release_note = note;
        await postAction(ROUTES.lock, payload);
        showToast('Payroll released successfully for ' + formatPeriodLabel(releaseTargetPeriod) + '.', 'success');
        const q = salaryPageQueryParams({ period: releaseTargetPeriod }); window.location.href = SALARY_PAGE_URL + '?' + q.toString();
      } catch (err) {
        showToast(err.message || 'Release failed.', 'error');
      }
    });

    document.getElementById('action-publish')?.addEventListener('click', (e) => {
      e.preventDefault(); showModal(confirmPublish);
    });
    document.getElementById('publishCancel')?.addEventListener('click', () => hideModal(confirmPublish));
    document.getElementById('publishConfirmBtn')?.addEventListener('click', async () => {
      try {
        await postAction(ROUTES.publish, { period_month: $('#period').value });
        const period = $('#period').value;
        const q = salaryPageQueryParams({ period }); window.location.href = SALARY_PAGE_URL + '?' + q.toString();
      } catch (err) { showToast(err.message, 'error'); }
    });

    document.getElementById('action-export')?.addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Export options pending implementation.', 'info');
    });

    // Insight card filtering
    document.querySelectorAll('.insight-card').forEach(card => {
      card.addEventListener('click', () => {
        const f = card.dataset.filter;
        if (FILTER === f) {
          FILTER = null;
          document.querySelectorAll('.insight-card').forEach(c => c.classList.remove('active'));
        } else {
          FILTER = f;
          document.querySelectorAll('.insight-card').forEach(c => c.classList.remove('active'));
          card.classList.add('active');
        }
        render(DATA);
      });
    });

    // Adjustment controls: period label, summary, sub-types, preview, history
    const adjNote = document.getElementById('adj-note');
    const adjCard = document.getElementById('adjustments-card');
    const basicSalaryCard = document.getElementById('basic-salary-card');
    const adjPeriodLabel = document.getElementById('adj-period-label');
    const adjSummaryBox = document.getElementById('adj-summary-box');
    const adjSummaryGrid = document.getElementById('adj-summary-grid');
    const adjFormFields = document.getElementById('adj-form-fields');
    const adjPreviewWrap = document.getElementById('adj-preview-wrap');
    const adjPreviewContent = document.getElementById('adj-preview-content');
    const adjHistorySection = document.getElementById('adj-history-section');
    const adjHistoryTbody = document.getElementById('adj-history-tbody');
    const adjHistoryEmpty = document.getElementById('adj-history-empty');
    const adjHistoryEditable = document.getElementById('adj-history-editable');

    function formatAdjPeriod(ym) {
      if (!ym) return '';
      const [y, m] = ym.split('-');
      const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      return (months[parseInt(m, 10) - 1] || m) + ' ' + y;
    }
    /** When enabled in config, corrections may only be saved for calendar current month. */
    function isAdjSaveAllowedByCalendar() {
      return !ADJ_CALENDAR_MONTH_ONLY || $('#period').value === ADJ_CALENDAR_MONTH_YM;
    }
    const adjEffectiveMonth = document.getElementById('adj-effective-month');
    const adjCurrentBase = document.getElementById('adj-current-base');
    const adjNewBase = document.getElementById('adj-new-base');
    const adjBasicReason = document.getElementById('adj-basic-reason');
    function updateAdjPeriodLabel() {
      const periodYm = $('#period').value;
      const label = formatAdjPeriod(periodYm);
      if (adjPeriodLabel) adjPeriodLabel.textContent = label;
      if (adjEffectiveMonth) adjEffectiveMonth.value = label;
    }
    $('#period')?.addEventListener('change', updateAdjPeriodLabel);
    updateAdjPeriodLabel();

    function renderSubtypes(category) {
      const sel = document.getElementById('adj-subtype');
      if (!sel) return;
      const opts = ADJ_SUBTYPES[category] || ADJ_SUBTYPES.earning;
      sel.innerHTML = opts.map(o => `<option value="${o.value}">${o.label}</option>`).join('');
    }
    document.getElementById('adj-type')?.addEventListener('change', function() {
      renderSubtypes(this.value);
      updateAdjPreview();
    });

    let adjSummaryData = null;
    function showAdjPlaceholderNoEmployee() {
      if (!adjSummaryBox || !adjSummaryGrid || !adjFormFields || !adjHistorySection) return;
      adjSummaryData = null;
      adjSummaryBox.style.display = 'block';
      adjSummaryGrid.innerHTML = '<div class="item"><span class="k">Summary</span><span class="v">Select an employee to load payroll figures for this month.</span></div>';
      adjFormFields.style.display = 'block';
      adjHistorySection.style.display = 'block';
      if (adjHistoryEditable) adjHistoryEditable.textContent = '';
      if (adjHistoryTbody) adjHistoryTbody.innerHTML = '';
      if (adjHistoryEmpty) {
        adjHistoryEmpty.style.display = 'block';
        adjHistoryEmpty.textContent = 'Select an employee to see recent adjustments for this month.';
      }
      if (adjPreviewWrap) adjPreviewWrap.style.display = 'none';
      const noEmp = !$('#adj-emp').value || !$('#adj-emp').value.trim();
      ['adj-type','adj-subtype','adj-amount','adj-reason','adj-apply','adj-reset'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !CAN_ADJUST() || noEmp || !isAdjSaveAllowedByCalendar();
      });
    }
    async function fetchAdjSummary() {
      const empId = $('#adj-emp').value;
      const period = $('#period').value;
      if (!empId || !period) {
        showAdjPlaceholderNoEmployee();
        return;
      }
      try {
        const r = await fetch(ROUTES.adjustment_summary + '?period_month=' + encodeURIComponent(period) + '&employee_id=' + encodeURIComponent(empId), { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        adjSummaryData = j;
        if (j.run) {
          adjSummaryBox.style.display = 'block';
          adjSummaryGrid.innerHTML = `
            <div class="item"><span class="k">Basic Salary</span><span class="v">RM ${Number(j.run.base).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Allowance</span><span class="v">RM ${Number(j.run.allowance).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Adjustments</span><span class="v">RM ${Number(j.run.adjustment_total).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Gross</span><span class="v">RM ${Number(j.run.gross).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">EPF (11%)</span><span class="v">- RM ${Number(j.run.epf).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Tax (3%)</span><span class="v">- RM ${Number(j.run.tax).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
            <div class="item"><span class="k">Net</span><span class="v">RM ${Number(j.run.net).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</span></div>
          `;
        } else {
          adjSummaryBox.style.display = 'block';
          adjSummaryGrid.innerHTML = '<div class="item"><span class="k">No payroll run</span><span class="v">Generate payroll for this month first.</span></div>';
        }
        adjFormFields.style.display = 'block';
        adjHistorySection.style.display = 'block';
        let adjStatusMsg = j.is_editable ? 'Payroll is in Draft — adjustments are editable until release.' : 'Payroll is locked for this month.';
        if (j.adjustments_calendar_month_only && j.calendar_payroll_month && j.period_month && j.period_month !== j.calendar_payroll_month && j.is_editable) {
          adjStatusMsg += ' Saving new earnings/deductions is only allowed for the current month (' + formatAdjPeriod(j.calendar_payroll_month) + ').';
        }
        adjHistoryEditable.textContent = adjStatusMsg;
        const list = j.adjustments || [];
        const empIdForRow = ($('#adj-emp').value || '').trim();
        const canSaveNewAdj = CAN_ADJUST() && (
          typeof j.can_apply_adjustment === 'boolean'
            ? j.can_apply_adjustment
            : (j.is_editable && j.run && isAdjSaveAllowedByCalendar())
        );
        const canCancelAdj = CAN_ADJUST() && j.is_editable && j.run;
        if (list.length) {
          adjHistoryTbody.innerHTML = list.map(a => {
            const reasonEsc = (a.reason || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
            const reasonShort = reasonEsc.slice(0, 80) + ((a.reason && a.reason.length > 80) ? '…' : '');
            const cancelBtn = canCancelAdj && empIdForRow
              ? `<button type="button" class="btn-ghost btn-sm adj-cancel-btn" data-line-id="${a.id}" data-employee-id="${empIdForRow.replace(/"/g, '&quot;')}" data-category="${String(a.category || '').replace(/"/g, '&quot;')}" data-sub-type="${String(a.sub_type || '').replace(/"/g, '&quot;')}" data-amt="${Number(a.amount)}" data-is-deduction="${a.category === 'Deduction' ? '1' : '0'}" title="Remove this correction" style="color:#b91c1c;border-color:#fecaca;">Cancel</button>`
              : '<span class="muted">—</span>';
            return `<tr><td>${a.category}</td><td>${a.sub_type}</td><td class="num">${a.category === 'Deduction' ? '-' : ''} RM ${Number(a.amount).toLocaleString('en-MY', { minimumFractionDigits: 2 })}</td><td>${reasonShort}</td><td>${a.date || '—'}</td><td>${cancelBtn}</td></tr>`;
          }).join('');
          adjHistoryEmpty.style.display = 'none';
        } else {
          adjHistoryTbody.innerHTML = '';
          adjHistoryEmpty.style.display = 'block';
          adjHistoryEmpty.textContent = 'No adjustments yet for this employee this month.';
        }
        updateAdjPreview();
        ['adj-type','adj-subtype','adj-amount','adj-reason','adj-apply','adj-reset'].forEach(id => {
          const el = document.getElementById(id);
          if (el) el.disabled = !canSaveNewAdj;
        });
      } catch (e) {
        adjSummaryData = null;
        if (adjSummaryBox && adjSummaryGrid) {
          adjSummaryBox.style.display = 'block';
          adjSummaryGrid.innerHTML = '<div class="item"><span class="k">Error</span><span class="v">Could not load summary. Try again or refresh.</span></div>';
        }
        adjFormFields.style.display = 'block';
        adjHistorySection.style.display = 'block';
      }
    }

    function updateAdjPreview() {
      if (!adjSummaryData || !adjSummaryData.run) { adjPreviewWrap.style.display = 'none'; return; }
      const amount = Number($('#adj-amount').value || 0);
      const type = $('#adj-type').value;
      const currentGross = adjSummaryData.run.gross;
      const currentNet = adjSummaryData.run.net;
      if (amount <= 0) { adjPreviewWrap.style.display = 'none'; return; }
      const delta = type === 'earning' ? amount : -amount;
      const newAdjTotal = adjSummaryData.run.adjustment_total + delta;
      const base = adjSummaryData.run.base;
      const allowance = adjSummaryData.run.allowance;
      const newGross = base + allowance + newAdjTotal;
      const newEpf = Math.round(newGross * 0.11 * 100) / 100;
      const newTax = Math.round(newGross * 0.03 * 100) / 100;
      const newNet = Math.round((newGross - newEpf - newTax) * 100) / 100;
      adjPreviewWrap.style.display = 'block';
      adjPreviewContent.innerHTML = `After this adjustment: <strong>Gross</strong> = RM ${newGross.toLocaleString('en-MY', { minimumFractionDigits: 2 })}, <strong>Net</strong> = RM ${newNet.toLocaleString('en-MY', { minimumFractionDigits: 2 })}`;
    }
    document.getElementById('adj-amount')?.addEventListener('input', updateAdjPreview);
    document.getElementById('adj-amount')?.addEventListener('change', updateAdjPreview);
    document.getElementById('adj-reason')?.addEventListener('input', updateAdjPreview);

    $('#adj-emp')?.addEventListener('change', fetchAdjSummary);

    let adjCancelPending = null;
    const confirmCancelAdjModal = document.getElementById('confirmCancelAdjustment');
    const cancelAdjSummary = document.getElementById('cancelAdjSummary');
    const cancelAdjPassword = document.getElementById('cancelAdjPassword');
    const cancelAdjError = document.getElementById('cancelAdjError');
    const cancelAdjDismiss = document.getElementById('cancelAdjDismiss');
    const cancelAdjSubmit = document.getElementById('cancelAdjSubmit');

    function closeCancelAdjModal() {
      if (confirmCancelAdjModal) confirmCancelAdjModal.style.display = 'none';
      adjCancelPending = null;
      if (cancelAdjPassword) cancelAdjPassword.value = '';
      if (cancelAdjError) { cancelAdjError.style.display = 'none'; cancelAdjError.textContent = ''; }
    }

    adjHistoryTbody?.addEventListener('click', (e) => {
      const btn = e.target.closest('.adj-cancel-btn');
      if (!btn) return;
      const lineId = btn.getAttribute('data-line-id');
      const employeeId = (btn.getAttribute('data-employee-id') || $('#adj-emp').value || '').trim();
      if (!lineId || !CAN_ADJUST()) {
        if (!CAN_ADJUST()) showToast('Payroll is not in DRAFT; corrections cannot be removed.', 'error');
        return;
      }
      const period = ($('#period').value || '').trim();
      if (!period || !employeeId) {
        showToast('Select an employee first, then remove the correction.', 'error');
        return;
      }
      const cat = btn.getAttribute('data-category') || '';
      const sub = btn.getAttribute('data-sub-type') || '';
      const isDed = btn.getAttribute('data-is-deduction') === '1';
      const amt = Number(btn.getAttribute('data-amt') || 0);
      const amtStr = (isDed ? '- ' : '') + 'RM ' + amt.toLocaleString('en-MY', { minimumFractionDigits: 2 });
      adjCancelPending = { lineId, employeeId, period };
      if (cancelAdjSummary) {
        cancelAdjSummary.innerHTML =
          '<div><strong>Category</strong> ' + basicRevisionEscape(cat) + '</div>' +
          '<div><strong>Sub-type</strong> ' + basicRevisionEscape(sub) + '</div>' +
          '<div><strong>Amount</strong> ' + basicRevisionEscape(amtStr) + '</div>';
      }
      if (cancelAdjPassword) cancelAdjPassword.value = '';
      if (cancelAdjError) { cancelAdjError.style.display = 'none'; cancelAdjError.textContent = ''; }
      if (confirmCancelAdjModal) confirmCancelAdjModal.style.display = 'flex';
    });

    cancelAdjDismiss?.addEventListener('click', closeCancelAdjModal);
    confirmCancelAdjModal?.addEventListener('click', (e) => { if (e.target === confirmCancelAdjModal) closeCancelAdjModal(); });

    cancelAdjSubmit?.addEventListener('click', async () => {
      if (!adjCancelPending) return;
      const pwd = (cancelAdjPassword?.value || '').trim();
      if (!pwd) {
        if (cancelAdjError) {
          cancelAdjError.textContent = 'Password is required.';
          cancelAdjError.style.display = 'block';
        }
        return;
      }
      const token = getCSRF();
      if (!token) {
        showToast('Session expired. Please refresh.', 'error');
        return;
      }
      const { lineId, employeeId, period } = adjCancelPending;
      const submitBtn = cancelAdjSubmit;
      const origLabel = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      try {
        const form = new FormData();
        form.append('_token', token);
        form.append('period_month', period);
        form.append('employee_id', employeeId);
        form.append('line_item_id', lineId);
        form.append('password', pwd);
        const resp = await fetch(ROUTES.adjustment_cancel, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: form,
          credentials: 'same-origin',
        });
        const j = await resp.json().catch(() => ({}));
        if (!resp.ok) {
          throw new Error(j.message || (j.errors && Object.values(j.errors).flat().join(' ')) || 'Cancel failed');
        }
        showToast(j.message || 'Adjustment removed.', 'success');
        closeCancelAdjModal();
        fetchAdjSummary();
        loadData();
      } catch (err) {
        const msg = err.message || 'Cancel failed.';
        if (cancelAdjError) {
          cancelAdjError.textContent = msg;
          cancelAdjError.style.display = 'block';
        }
        if (cancelAdjPassword && /password/i.test(msg)) cancelAdjPassword.value = '';
        showToast(msg, 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origLabel;
      }
    });

    async function fetchBasicSalaryContext() {
      const empId = document.getElementById('adj-basic-emp')?.value?.trim();
      const period = $('#period').value;
      if (adjEffectiveMonth) adjEffectiveMonth.value = formatAdjPeriod(period);
      if (!empId || !period) {
        if (adjCurrentBase) adjCurrentBase.value = '';
        fetchBasicSalaryRevisions();
        fetchEmployeePayrollHistory();
        return;
      }
      try {
        const r = await fetch(ROUTES.adjustment_summary + '?period_month=' + encodeURIComponent(period) + '&employee_id=' + encodeURIComponent(empId), { headers: { 'Accept': 'application/json' } });
        const j = await r.json();
        if (adjEffectiveMonth) adjEffectiveMonth.value = j.period_label || formatAdjPeriod(period);
        if (adjCurrentBase) {
          adjCurrentBase.value = (j.employee_base_salary != null && j.employee_base_salary !== '')
            ? Number(j.employee_base_salary).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '—';
        }
      } catch (e) {
        if (adjCurrentBase) adjCurrentBase.value = '—';
      }
      fetchBasicSalaryRevisions();
      fetchEmployeePayrollHistory();
    }

    function basicRevisionEscape(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    }

    async function fetchBasicSalaryRevisions() {
      const empId = document.getElementById('adj-basic-emp')?.value?.trim();
      const tbody = document.getElementById('adj-basic-revision-tbody');
      const emptyEl = document.getElementById('adj-basic-revision-empty');
      const hint = document.getElementById('adj-basic-revision-hint');
      if (!tbody) return;
      if (!empId) {
        tbody.innerHTML = '';
        if (emptyEl) {
          emptyEl.style.display = 'block';
          emptyEl.textContent = 'Select an employee to view history.';
        }
        if (hint) hint.style.display = '';
        return;
      }
      if (hint) hint.style.display = 'none';
      tbody.innerHTML = '<tr><td colspan="8" class="muted">Loading…</td></tr>';
      if (emptyEl) emptyEl.style.display = 'none';
      try {
        const r = await fetch(ROUTES.basic_salary_revisions + '?employee_id=' + encodeURIComponent(empId), { headers: { 'Accept': 'application/json' } });
        const j = await r.json().catch(() => ({}));
        const rows = Array.isArray(j.data) ? j.data : [];
        if (j.message && rows.length === 0) {
          tbody.innerHTML = '';
          if (emptyEl) {
            emptyEl.style.display = 'block';
            emptyEl.textContent = j.message;
          }
          return;
        }
        if (rows.length === 0) {
          tbody.innerHTML = '';
          if (emptyEl) {
            emptyEl.style.display = 'block';
            emptyEl.textContent = 'No basic salary changes recorded yet for this employee.';
          }
          return;
        }
        if (emptyEl) emptyEl.style.display = 'none';
        tbody.innerHTML = rows.map((row) => {
          const prev = Number(row.previous_salary).toLocaleString('en-MY', { minimumFractionDigits: 2 });
          const nw = Number(row.new_salary).toLocaleString('en-MY', { minimumFractionDigits: 2 });
          const rs = basicRevisionEscape((row.reason || '—').slice(0, 160));
          const cancelled = (row.status || '') === 'cancelled';
          const trClass = cancelled ? ' class="adj-revision-cancelled"' : '';
          const stLabel = cancelled
            ? '<span class="adj-revision-status-cancelled">Cancelled</span>'
            : '<span class="adj-revision-status-active">Active</span>';
          let actionCell = '—';
          if (!cancelled && row.can_cancel && CAN_ADJUST()) {
            actionCell = '<button type="button" class="btn-cancel-revision js-cancel-basic-revision" data-revision-id="' + String(row.id) + '" data-effective="' + basicRevisionEscape(row.effective_month) + '" data-prev="' + basicRevisionEscape(prev) + '" data-new="' + basicRevisionEscape(nw) + '">Cancel</button>';
          } else if (!cancelled && row.can_cancel && !CAN_ADJUST()) {
            actionCell = '<button type="button" class="btn-cancel-revision" disabled title="Payroll is not in draft">Cancel</button>';
          }
          return '<tr' + trClass + '><td>' + basicRevisionEscape(row.effective_month) + '</td><td class="num">' + prev + '</td><td class="num">' + nw + '</td><td>' + rs + '</td><td>' + basicRevisionEscape(row.approved_at) + '</td><td>' + basicRevisionEscape(row.approved_by_name) + '</td><td>' + stLabel + '</td><td>' + actionCell + '</td></tr>';
        }).join('');
      } catch (e) {
        tbody.innerHTML = '';
        if (emptyEl) {
          emptyEl.style.display = 'block';
          emptyEl.textContent = 'Could not load history.';
        }
      }
    }

    let cancelBasicRevisionPayload = null;
    const confirmCancelBasicRevisionModal = document.getElementById('confirmCancelBasicRevision');
    const cancelBasicRevisionSummary = document.getElementById('cancelBasicRevisionSummary');
    const cancelBasicRevisionPassword = document.getElementById('cancelBasicRevisionPassword');
    const cancelBasicRevisionError = document.getElementById('cancelBasicRevisionError');
    const cancelBasicRevisionDismiss = document.getElementById('cancelBasicRevisionDismiss');
    const cancelBasicRevisionSubmit = document.getElementById('cancelBasicRevisionSubmit');

    function closeCancelBasicRevisionModal() {
      if (confirmCancelBasicRevisionModal) confirmCancelBasicRevisionModal.style.display = 'none';
      cancelBasicRevisionPayload = null;
      if (cancelBasicRevisionPassword) cancelBasicRevisionPassword.value = '';
      if (cancelBasicRevisionError) {
        cancelBasicRevisionError.style.display = 'none';
        cancelBasicRevisionError.textContent = '';
      }
    }

    document.getElementById('adj-basic-revision-tbody')?.addEventListener('click', (ev) => {
      const btn = ev.target.closest('.js-cancel-basic-revision');
      if (!btn) return;
      const empId = document.getElementById('adj-basic-emp')?.value?.trim();
      const rid = btn.getAttribute('data-revision-id');
      if (!empId || !rid) return;
      cancelBasicRevisionPayload = { revisionId: rid, employeeId: empId };
      const eff = btn.getAttribute('data-effective') || '';
      const p = btn.getAttribute('data-prev') || '';
      const n = btn.getAttribute('data-new') || '';
      if (cancelBasicRevisionSummary) {
        cancelBasicRevisionSummary.innerHTML =
          '<div><strong>Effective from</strong> ' + basicRevisionEscape(eff) + '</div>' +
          '<div><strong>Will revert to</strong> RM ' + basicRevisionEscape(p) + '</div>' +
          '<div><strong>Removes change to</strong> RM ' + basicRevisionEscape(n) + '</div>';
      }
      if (cancelBasicRevisionPassword) cancelBasicRevisionPassword.value = '';
      if (cancelBasicRevisionError) {
        cancelBasicRevisionError.style.display = 'none';
        cancelBasicRevisionError.textContent = '';
      }
      if (confirmCancelBasicRevisionModal) confirmCancelBasicRevisionModal.style.display = 'flex';
    });

    cancelBasicRevisionDismiss?.addEventListener('click', closeCancelBasicRevisionModal);
    confirmCancelBasicRevisionModal?.addEventListener('click', (e) => {
      if (e.target === confirmCancelBasicRevisionModal) closeCancelBasicRevisionModal();
    });

    cancelBasicRevisionSubmit?.addEventListener('click', async () => {
      if (!cancelBasicRevisionPayload) return;
      const pwd = (cancelBasicRevisionPassword?.value || '').trim();
      if (!pwd) {
        if (cancelBasicRevisionError) {
          cancelBasicRevisionError.textContent = 'Password is required.';
          cancelBasicRevisionError.style.display = 'block';
        }
        return;
      }
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const submitBtn = cancelBasicRevisionSubmit;
      const origHtml = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      try {
        const form = new FormData();
        form.append('_token', token || '');
        form.append('revision_id', cancelBasicRevisionPayload.revisionId);
        form.append('employee_id', cancelBasicRevisionPayload.employeeId);
        form.append('password', pwd);
        const resp = await fetch(ROUTES.cancel_basic_salary_revision, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token || '', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: form,
          credentials: 'same-origin',
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok) {
          throw new Error(data.message || 'Cancel failed');
        }
        showToast(data.message || 'Revision cancelled.', 'success');
        closeCancelBasicRevisionModal();
        await fetchBasicSalaryContext();
      } catch (err) {
        const msg = err.message || 'Cancel failed.';
        if (cancelBasicRevisionError) {
          cancelBasicRevisionError.textContent = msg;
          cancelBasicRevisionError.style.display = 'block';
        }
        if (cancelBasicRevisionPassword && /password/i.test(msg)) cancelBasicRevisionPassword.value = '';
        showToast(msg, 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origHtml;
      }
    });

    function moneyCell(n) {
      if (n === null || n === undefined || n === '') return '—';
      const v = Number(n);
      if (Number.isNaN(v)) return '—';
      return v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatRmPayroll(n) {
      if (n === null || n === undefined || n === '') return '—';
      const v = Number(n);
      if (Number.isNaN(v)) return '—';
      return 'RM ' + v.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function adminPayslipDownloadUrl(payslipId, employeeId) {
      return ROUTES.payslip_download_prefix + '/' + payslipId + '/download?employee_id=' + encodeURIComponent(employeeId);
    }

    (function setupBasicSalaryPayrollDetailModal() {
      const overlay = document.getElementById('basicAdjPayrollDetailOverlay');
      const titleEl = document.getElementById('basicAdjPayrollDetailTitle');
      const bodyEl = document.getElementById('basicAdjPayrollDetailBody');
      const closeBtn = document.getElementById('basicAdjPayrollDetailClose');
      if (!overlay || !titleEl || !bodyEl || !closeBtn) return;

      function formatMoney(n) {
        return n != null && n !== '' && !Number.isNaN(Number(n))
          ? 'RM ' + Number(n).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
          : '—';
      }

      function renderAdminPayrollDetail(data) {
        const b = data.breakdown || {};
        const gross = b.gross != null ? b.gross : null;
        const net = b.net != null ? b.net : null;
        const totalDed = b.total_deductions;
        let html = '<div class="detail-section">Summary</div>';
        html += '<div class="detail-row"><span>Gross pay</span><span><strong>' + formatMoney(gross) + '</strong></span></div>';
        html += '<div class="detail-row"><span>Total deductions</span><span>- ' + formatMoney(totalDed) + '</span></div>';
        html += '<div class="detail-row total-row"><span>Net pay</span><span>' + formatMoney(net) + '</span></div>';
        const items = Array.isArray(data.line_items) ? data.line_items : [];
        if (items.length) {
          html += '<div class="detail-section">Line items</div>';
          html += '<table class="line-items"><thead><tr><th>Type</th><th>Description</th><th class="num">Amount</th></tr></thead><tbody>';
          items.forEach((item) => {
            const amt = item.amount != null
              ? ((item.item_type || '').toUpperCase() === 'DEDUCTION' ? '- ' + formatMoney(item.amount) : formatMoney(item.amount))
              : '—';
            const desc = (item.description || item.code || '').toString();
            html += '<tr><td>' + basicRevisionEscape(item.item_type || '') + '</td><td>' + basicRevisionEscape(desc) + '</td><td class="num">' + amt + '</td></tr>';
          });
          html += '</tbody></table>';
        }
        bodyEl.innerHTML = html;
      }

      function closeOverlay() {
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
      }

      closeBtn.addEventListener('click', closeOverlay);
      overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(); });

      document.getElementById('adj-payroll-history-tbody')?.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('.js-basic-payroll-view');
        if (!btn) return;
        const period = btn.getAttribute('data-period');
        const empId = document.getElementById('adj-basic-emp')?.value?.trim();
        if (!period || !empId) return;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        titleEl.textContent = 'Payroll details — ' + (period ? new Date(period + '-01').toLocaleDateString('en-GB', { month: 'long', year: 'numeric' }) : '');
        bodyEl.innerHTML = '<div class="muted" style="text-align:center; padding:2rem;">Loading…</div>';
        const url = ROUTES.salary_detail + '?period_month=' + encodeURIComponent(period) + '&employee_id=' + encodeURIComponent(empId);
        try {
          const r = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
          const json = await r.json().catch(() => ({}));
          if (!r.ok) {
            bodyEl.innerHTML = '<p class="muted">' + (json.message || 'No details for this period.') + '</p>';
            return;
          }
          if (json.message && (String(json.message).includes('not found') || String(json.message).includes('No payroll'))) {
            bodyEl.innerHTML = '<p class="muted">' + json.message + '</p>';
            return;
          }
          renderAdminPayrollDetail(json);
        } catch (e) {
          bodyEl.innerHTML = '<p class="muted">Could not load details. Please try again.</p>';
        }
      });
    })();

    async function fetchEmployeePayrollHistory() {
      const empId = document.getElementById('adj-basic-emp')?.value?.trim();
      const tbody = document.getElementById('adj-payroll-history-tbody');
      const emptyEl = document.getElementById('adj-payroll-history-empty');
      const hint = document.getElementById('adj-payroll-history-hint');
      if (!tbody) return;
      if (!empId) {
        tbody.innerHTML = '';
        if (hint) hint.style.display = '';
        if (emptyEl) {
          emptyEl.style.display = 'block';
          emptyEl.textContent = 'Select an employee to view payslips.';
        }
        return;
      }
      if (hint) hint.style.display = 'none';
      tbody.innerHTML = '<tr><td colspan="5" class="muted">Loading…</td></tr>';
      if (emptyEl) emptyEl.style.display = 'none';
      try {
        const r = await fetch(ROUTES.employee_payroll_history + '?employee_id=' + encodeURIComponent(empId), { headers: { 'Accept': 'application/json' } });
        const j = await r.json().catch(() => ({}));
        const rows = Array.isArray(j.data) ? j.data : [];
        if (rows.length === 0) {
          tbody.innerHTML = '';
          if (emptyEl) {
            emptyEl.style.display = 'block';
            emptyEl.textContent = 'No payroll periods released yet. Released periods will appear here.';
          }
          return;
        }
        if (emptyEl) emptyEl.style.display = 'none';
        tbody.innerHTML = rows.map((row) => {
          const pl = basicRevisionEscape(row.period_label || row.period_month || '—');
          const gross = row.gross;
          const net = row.net;
          const st = basicRevisionEscape(row.status || '—');
          const hasGross = gross !== null && gross !== undefined && gross !== '' && !Number.isNaN(Number(gross));
          let actions = '<span class="muted">—</span>';
          if (hasGross) {
            const viewBtn = '<button type="button" class="adj-btn-view-pay js-basic-payroll-view" data-period="' + basicRevisionEscape(row.period_month || '') + '">View</button>';
            let dl = '';
            if (row.payslip_id) {
              const href = adminPayslipDownloadUrl(row.payslip_id, empId);
              dl = '<a href="' + href + '" class="adj-btn-dl-pay">Download</a>';
            }
            actions = '<div class="adj-payroll-actions">' + viewBtn + dl + '</div>';
          } else if (row.payslip_id) {
            const href = adminPayslipDownloadUrl(row.payslip_id, empId);
            actions = '<div class="adj-payroll-actions"><a href="' + href + '" class="adj-btn-dl-pay">Download</a></div>';
          }
          return '<tr><td>' + pl + '</td><td class="num">' + formatRmPayroll(gross) + '</td><td class="num">' + formatRmPayroll(net) + '</td><td>' + st + '</td><td>' + actions + '</td></tr>';
        }).join('');
      } catch (e) {
        tbody.innerHTML = '';
        if (emptyEl) {
          emptyEl.style.display = 'block';
          emptyEl.textContent = 'Could not load payroll history.';
        }
      }
    }

    function updateBasicSalaryFormEnabled() {
      const hasEmp = !!(document.getElementById('adj-basic-emp')?.value?.trim());
      const ok = CAN_ADJUST() && hasEmp;
      ['adj-new-base', 'adj-basic-reason', 'adj-update-basic-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = !ok;
      });
    }

    document.getElementById('adj-basic-emp')?.addEventListener('change', () => {
      fetchBasicSalaryContext();
      updateBasicSalaryFormEnabled();
    });

    if (!CAN_ADJUST()) {
      ['adj-emp','adj-basic-emp','adj-type','adj-subtype','adj-amount','adj-reason','adj-apply','adj-reset','adj-new-base','adj-basic-reason','adj-update-basic-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = true;
      });
      adjCard?.classList.add('section-disabled');
      basicSalaryCard?.classList.add('section-disabled');
      if (adjNote) adjNote.textContent = 'Adjustments locked: payroll is not in DRAFT.';
    } else {
      ['adj-emp','adj-basic-emp','adj-type','adj-subtype','adj-amount','adj-reason','adj-new-base','adj-basic-reason','adj-update-basic-btn'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = false;
      });
      updateBasicSalaryFormEnabled();
      adjCard?.classList.remove('section-disabled');
      basicSalaryCard?.classList.remove('section-disabled');
    }

    const _adjCardVis = document.getElementById('adjustments-card');
    if (_adjCardVis && window.getComputedStyle(_adjCardVis).display !== 'none') {
      showAdjPlaceholderNoEmployee();
    }
    updateBasicSalaryFormEnabled();

    function escapeHtml(str) {
      const d = document.createElement('div');
      d.textContent = str == null ? '' : String(str);
      return d.innerHTML;
    }
    function adjSubtypeLabel(type, value) {
      const list = ADJ_SUBTYPES[type] || [];
      const o = list.find(x => x.value === value);
      return o ? o.label : value;
    }
    let adjPendingPayload = null;
    const confirmAdjModal = document.getElementById('confirmAdjustment');
    const adjConfirmSummary = document.getElementById('adjConfirmSummary');
    const adjConfirmAck = document.getElementById('adjConfirmAck');
    const adjConfirmRemark = document.getElementById('adjConfirmRemark');
    const adjConfirmSave = document.getElementById('adjConfirmSave');
    const adjConfirmCancel = document.getElementById('adjConfirmCancel');
    function updateAdjConfirmSaveEnabled() {
      if (!adjConfirmSave || !adjConfirmAck) return;
      adjConfirmSave.disabled = !adjConfirmAck.checked;
    }
    function closeAdjConfirmModal() {
      if (confirmAdjModal) confirmAdjModal.style.display = 'none';
      adjPendingPayload = null;
      if (adjConfirmRemark) adjConfirmRemark.value = '';
      if (adjConfirmAck) adjConfirmAck.checked = false;
      updateAdjConfirmSaveEnabled();
    }
    adjConfirmAck?.addEventListener('change', updateAdjConfirmSaveEnabled);
    adjConfirmCancel?.addEventListener('click', closeAdjConfirmModal);
    confirmAdjModal?.addEventListener('click', (e) => {
      if (e.target === confirmAdjModal) closeAdjConfirmModal();
    });

    $('#adj-apply')?.addEventListener('click', () => {
      if (!CAN_ADJUST()) return;
      if (!isAdjSaveAllowedByCalendar()) {
        showToast('Payroll corrections can only be saved for the current month (' + formatAdjPeriod(ADJ_CALENDAR_MONTH_YM) + '). Change Payroll Period.', 'error');
        return;
      }
      const employeeId = $('#adj-emp').value;
      const type = $('#adj-type').value;
      const subType = $('#adj-subtype').value;
      const amount = Number($('#adj-amount').value || 0);
      const reason = $('#adj-reason').value.trim();
      if (!employeeId) return showToast('Select an employee', 'error');
      if (reason.length < 10) return showToast('Reason must be at least 10 characters', 'error');
      if (amount <= 0) return showToast('Amount must be greater than 0', 'error');
      const empSel = document.getElementById('adj-emp');
      const empLabel = empSel && empSel.options[empSel.selectedIndex] ? empSel.options[empSel.selectedIndex].text : '—';
      const periodYm = $('#period').value;
      const catLabel = type === 'deduction' ? 'Deduction' : 'Earning';
      adjPendingPayload = { employeeId, type, subType, amount, reason, empLabel, periodYm, catLabel };
      if (adjConfirmSummary) {
        adjConfirmSummary.innerHTML =
          '<div><strong>Payroll month</strong> ' + escapeHtml(periodYm) + '</div>' +
          '<div><strong>Employee</strong> ' + escapeHtml(empLabel) + '</div>' +
          '<div><strong>Category</strong> ' + escapeHtml(catLabel) + '</div>' +
          '<div><strong>Sub-type</strong> ' + escapeHtml(adjSubtypeLabel(type, subType)) + '</div>' +
          '<div><strong>Amount</strong> RM ' + escapeHtml(Number(amount).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) + '</div>' +
          '<div><strong>Reason</strong> ' + escapeHtml(reason) + '</div>';
      }
      if (adjConfirmRemark) adjConfirmRemark.value = '';
      if (adjConfirmAck) adjConfirmAck.checked = false;
      updateAdjConfirmSaveEnabled();
      if (confirmAdjModal) confirmAdjModal.style.display = 'flex';
    });

    adjConfirmSave?.addEventListener('click', async () => {
      if (!adjPendingPayload) return;
      if (!adjConfirmAck?.checked) {
        showToast('Please confirm the checkbox to continue.', 'error');
        return;
      }
      const p = adjPendingPayload;
      const btn = adjConfirmSave;
      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
      try {
        const token = getCSRF();
        if (!token) { showToast('Session expired. Please refresh.', 'error'); return; }
        const form = new FormData();
        form.append('_token', token);
        form.append('period_month', p.periodYm);
        form.append('employee_id', p.employeeId);
        form.append('adjustment_type', p.type);
        form.append('adjustment_sub_type', p.subType);
        form.append('amount', String(p.amount));
        form.append('reason', p.reason);
        const remark = (adjConfirmRemark?.value || '').trim();
        if (remark) form.append('audit_remark', remark);
        const resp = await fetch(ROUTES.adjustment, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: form,
          credentials: 'same-origin',
        });
        if (!resp.ok) {
          const j = await resp.json().catch(() => ({}));
          const msg = j.message || (j.errors && Object.values(j.errors).flat().join(' ')) || 'Save failed';
          throw new Error(msg);
        }
        showToast('Adjustment saved.', 'success');
        $('#adj-amount').value = '';
        $('#adj-reason').value = '';
        closeAdjConfirmModal();
        fetchAdjSummary();
        loadData();
      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
        updateAdjConfirmSaveEnabled();
      }
    });

    $('#adj-reset')?.addEventListener('click', () => {
      if (!CAN_ADJUST()) return;
      $('#adj-amount').value = '';
      $('#adj-reason').value = '';
      $('#adj-type').value = 'earning';
      renderSubtypes('earning');
      updateAdjPreview();
    });

    let basicSalaryPendingPayload = null;
    const confirmBasicSalaryModal = document.getElementById('confirmBasicSalary');
    const basicSalaryConfirmSummary = document.getElementById('basicSalaryConfirmSummary');
    const basicSalaryConfirmAck = document.getElementById('basicSalaryConfirmAck');
    const basicSalaryConfirmPassword = document.getElementById('basicSalaryConfirmPassword');
    const basicSalaryConfirmRemark = document.getElementById('basicSalaryConfirmRemark');
    const basicSalaryConfirmSave = document.getElementById('basicSalaryConfirmSave');
    const basicSalaryConfirmCancel = document.getElementById('basicSalaryConfirmCancel');
    function updateBasicSalaryConfirmSaveEnabled() {
      if (!basicSalaryConfirmSave || !basicSalaryConfirmPassword || !basicSalaryConfirmAck) return;
      basicSalaryConfirmSave.disabled = !(basicSalaryConfirmAck.checked && basicSalaryConfirmPassword.value.trim().length > 0);
    }
    function closeBasicSalaryConfirmModal() {
      if (confirmBasicSalaryModal) confirmBasicSalaryModal.style.display = 'none';
      basicSalaryPendingPayload = null;
      if (basicSalaryConfirmPassword) basicSalaryConfirmPassword.value = '';
      if (basicSalaryConfirmRemark) basicSalaryConfirmRemark.value = '';
      if (basicSalaryConfirmAck) basicSalaryConfirmAck.checked = false;
      updateBasicSalaryConfirmSaveEnabled();
    }
    basicSalaryConfirmAck?.addEventListener('change', updateBasicSalaryConfirmSaveEnabled);
    basicSalaryConfirmPassword?.addEventListener('input', updateBasicSalaryConfirmSaveEnabled);
    basicSalaryConfirmCancel?.addEventListener('click', closeBasicSalaryConfirmModal);
    confirmBasicSalaryModal?.addEventListener('click', (e) => {
      if (e.target === confirmBasicSalaryModal) closeBasicSalaryConfirmModal();
    });

    document.getElementById('adj-update-basic-btn')?.addEventListener('click', () => {
      if (!CAN_ADJUST()) return;
      const periodMonth = $('#period').value;
      const employeeId = (document.getElementById('adj-basic-emp')?.value || '').trim();
      const newBaseVal = $('#adj-new-base').value.trim();
      const reason = (document.getElementById('adj-basic-reason')?.value || '').trim();
      if (!employeeId) {
        showToast('Select an employee to update.', 'error');
        return;
      }
      const newBase = parseFloat(newBaseVal);
      if (isNaN(newBase) || newBase < 0.01) {
        showToast('Enter a valid new base salary (min 0.01).', 'error');
        return;
      }
      const empSel = document.getElementById('adj-basic-emp');
      const empLabel = empSel && empSel.options[empSel.selectedIndex] ? empSel.options[empSel.selectedIndex].text : '—';
      const currentDisp = (document.getElementById('adj-current-base')?.value || '—').trim();
      basicSalaryPendingPayload = { periodMonth, employeeId, newBase, reason, empLabel, currentDisp };
      if (basicSalaryConfirmSummary) {
        basicSalaryConfirmSummary.innerHTML =
          '<div><strong>Employee</strong> ' + escapeHtml(empLabel) + '</div>' +
          '<div><strong>Effective from</strong> ' + escapeHtml(formatAdjPeriod(periodMonth)) + '</div>' +
          '<div><strong>Current basic (RM)</strong> ' + escapeHtml(currentDisp) + '</div>' +
          '<div><strong>New basic (RM)</strong> ' + escapeHtml(newBase.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })) + '</div>' +
          (reason ? '<div><strong>Reason</strong> ' + escapeHtml(reason) + '</div>' : '');
      }
      if (basicSalaryConfirmPassword) basicSalaryConfirmPassword.value = '';
      if (basicSalaryConfirmRemark) basicSalaryConfirmRemark.value = '';
      if (basicSalaryConfirmAck) basicSalaryConfirmAck.checked = false;
      updateBasicSalaryConfirmSaveEnabled();
      if (confirmBasicSalaryModal) confirmBasicSalaryModal.style.display = 'flex';
    });

    basicSalaryConfirmSave?.addEventListener('click', async () => {
      if (!basicSalaryPendingPayload) return;
      if (!basicSalaryConfirmAck?.checked) {
        showToast('Please confirm the checkbox to continue.', 'error');
        return;
      }
      const pwd = (basicSalaryConfirmPassword?.value || '').trim();
      if (!pwd) {
        showToast('Password is required.', 'error');
        return;
      }
      const p = basicSalaryPendingPayload;
      const btn = basicSalaryConfirmSave;
      const orig = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
      try {
        const token = getCSRF();
        if (!token) { showToast('Session expired. Please refresh.', 'error'); return; }
        const form = new FormData();
        form.append('_token', token);
        form.append('period_month', p.periodMonth);
        form.append('employee_id', p.employeeId);
        form.append('new_base_salary', String(p.newBase));
        form.append('reason', p.reason);
        form.append('password', pwd);
        form.append('basic_salary_confirm', '1');
        const ar = (basicSalaryConfirmRemark?.value || '').trim();
        if (ar) form.append('audit_remark', ar);
        const resp = await fetch(ROUTES.update_basic_salary, {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
          body: form,
          credentials: 'same-origin',
        });
        const data = await resp.json().catch(() => ({}));
        if (!resp.ok) {
          const msg = data.message || (data.errors && Object.values(data.errors).flat().join(' ')) || 'Update failed';
          if (basicSalaryConfirmPassword && /password/i.test(msg)) basicSalaryConfirmPassword.value = '';
          throw new Error(msg);
        }
        showToast(data.message || 'Basic salary updated.', 'success');
        $('#adj-new-base').value = '';
        if (adjBasicReason) adjBasicReason.value = '';
        closeBasicSalaryConfirmModal();
        await fetchBasicSalaryContext();
        fetchAdjSummary();
        loadData();
      } catch (err) {
        showToast(err.message || 'Failed to update basic salary', 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
        updateBasicSalaryConfirmSaveEnabled();
      }
    });

    const modal = document.getElementById('modal');
    const meta = document.getElementById('meta');
    const breakdown = document.getElementById('breakdown');
    const attendanceLists = document.getElementById('attendanceLists');
    const bankDetails = document.getElementById('bankDetails');
    const tabLinks = modal.querySelectorAll('.detail-tab');
    const tabPanels = {
      breakdown: document.getElementById('tab-breakdown'),
      attendance: document.getElementById('tab-attendance'),
      bank: document.getElementById('tab-bank'),
    };
    document.getElementById('close').addEventListener('click', () => modal.style.display = 'none');

    tabLinks.forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        tabLinks.forEach(b => b.classList.remove('active'));
        Object.values(tabPanels).forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        tabPanels[target]?.classList.add('active');
      });
    });

    async function openModal(e) {
      const c = calc(e);
      const rawNet = (e.net != null && e.net !== undefined && e.net !== '') ? Number(e.net) : c.net;
      const detailNet = Math.max(0, rawNet);
      const detailGross = (e.gross != null && e.gross !== undefined && e.gross !== '') ? Number(e.gross) : c.gross;
      const formulaBoxEl = document.getElementById('formulaBox');
      meta.innerHTML = `<div class="emp-name">${e.name}</div><div class="emp-meta">${e.id} · ${e.dept}</div>`;
      if (formulaBoxEl) formulaBoxEl.innerHTML = `
        <strong>Gross</strong> = Basic + Allowance + Adjustments.<br>
        Attendance deductions (Late + Absent + Unpaid Leave + Penalties) are capped at Basic Salary.<br>
        EPF and Tax apply only to <strong>Chargeable Salary</strong> (after cap).<br>
        <strong>Net Pay</strong> = max(0, Chargeable − EPF − Tax).
        <div class="scope">All figures for selected payroll month only.</div>`;

      breakdown.innerHTML = `
        <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Earnings</td></tr>
        <tr><td style="padding:8px;">Basic Salary</td><td style="padding:8px;text-align:right">RM ${money(e.base || 0)}</td></tr>
        <tr><td style="padding:8px;">Allowance</td><td style="padding:8px;text-align:right">RM ${money(e.allow || 0)}</td></tr>
        <tr><td style="padding:8px;">Adjustments</td><td style="padding:8px;text-align:right">${c.adj ? (c.isDeduction ? '-' : '+') + ' RM ' + money(c.adjAmount) : 'RM 0.00'}</td></tr>
        <tr><td style="padding:8px;background:#e0f2fe;"><strong>Gross Pay</strong></td><td style="padding:8px;text-align:right;background:#e0f2fe;"><strong>RM ${money(detailGross)}</strong></td></tr>
        <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Deductions</td></tr>
        <tr><td style="padding:8px;">Late</td><td style="padding:8px;text-align:right">- RM ${money(e.late_ded || 0)}</td></tr>
        <tr><td style="padding:8px;">Absent</td><td style="padding:8px;text-align:right">- RM ${money(e.absent_ded || 0)}</td></tr>
        <tr><td style="padding:8px;">Unpaid Leave</td><td style="padding:8px;text-align:right">- RM ${money(e.unpaid_ded || 0)}</td></tr>
        <tr><td style="padding:8px;">Penalties</td><td style="padding:8px;text-align:right">- RM ${money(e.penalty || 0)}</td></tr>
        <tr><td style="padding:8px;">EPF (11%)</td><td style="padding:8px;text-align:right">- RM ${money(e.epfTax || 0)}</td></tr>
        <tr><td style="padding:8px;">Tax (3%)</td><td style="padding:8px;text-align:right">- RM ${money(e.tax_total || 0)}</td></tr>
        <tr><td style="padding:8px;background:#fee2e2;"><strong>Total Deductions</strong></td><td style="padding:8px;text-align:right;background:#fee2e2;"><strong>- RM ${money(c.deductions)}</strong></td></tr>
        <tr><td style="padding:8px;background:#f8fafc"><strong>Net Pay</strong></td><td style="padding:8px;text-align:right;background:#f8fafc"><strong>RM ${money(detailNet)}</strong></td></tr>
      `;

      tabLinks.forEach(b => b.classList.remove('active'));
      Object.values(tabPanels).forEach(p => p.classList.remove('active'));
      tabLinks[0].classList.add('active');
      tabPanels.breakdown.classList.add('active');

      try {
        const url = `{{ route('admin.payroll.salary.detail') }}?period_month=${encodeURIComponent($('#period').value)}&employee_id=${encodeURIComponent(e.employee_id || '')}`;
        const resp = await fetch(url, { headers: { 'Accept': 'application/json' }});
        if (!resp.ok) throw new Error('Failed to load details');
        const detail = await resp.json();

        // Bank account (employee)
        const bank = (detail.employee && detail.employee.bank) ? detail.employee.bank : {};
        const bankName = bank.bank_name || bank.bank_code || '—';
        const accType = bank.account_type_label || bank.account_type || '—';
        const accNo = bank.account_number_masked || bank.account_number || '—';
        const branch = bank.branch || '—';
        const swift = bank.swift || '—';
        if (bankDetails) {
          bankDetails.innerHTML = `
            <div class="salary-detail-section">
              <div class="salary-detail-section-title">Bank account</div>
              <div class="info-row"><span class="info-label">Bank</span><span class="info-value">${bankName}</span></div>
              <div class="info-row"><span class="info-label">Account number</span><span class="info-value">${accNo}</span></div>
              <div class="info-row"><span class="info-label">Account type</span><span class="info-value">${accType}</span></div>
              <div class="info-row"><span class="info-label">Branch</span><span class="info-value">${branch}</span></div>
              <div class="info-row"><span class="info-label">SWIFT</span><span class="info-value">${swift}</span></div>
            </div>
          `;
        }

        const att = detail.attendance || {};
        const present = Array.isArray(att.present_days) ? att.present_days : [];
        const absentMarked = Array.isArray(att.absent_days) ? att.absent_days : [];
        const absentPayroll = Number(att.absent_days_payroll ?? 0);
        const workedDays = Number(att.worked_days ?? 0);
        const approvedLeaveDays = Number(att.approved_leave_days ?? 0);
        const workingDaysInMonth = Number(att.working_days_in_month ?? 26);
        const late = Array.isArray(att.late) ? att.late : [];
        const incomplete = Array.isArray(att.incomplete) ? att.incomplete : [];
        const leaveDays = Array.isArray(att.leave_days) ? att.leave_days : [];
        attendanceLists.innerHTML = `
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Working days</div>
            <div class="info-row"><span class="info-label">Present days</span><span class="info-value">${present.length} day${present.length !== 1 ? 's' : ''}</span></div>
            <div class="info-row"><span class="info-label">Late</span><span class="info-value">${late.length} record${late.length !== 1 ? 's' : ''}</span></div>
            <div class="info-row highlight"><span class="info-label">Worked days (present + late)</span><span class="info-value">${workedDays} of ${workingDaysInMonth} days</span></div>
          </div>
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Leave</div>
            <div class="info-row"><span class="info-label">Leave days (in attendance)</span><span class="info-value">${leaveDays.length} day${leaveDays.length !== 1 ? 's' : ''}</span></div>
            <div class="info-row"><span class="info-label">Approved leave (this period)</span><span class="info-value">${approvedLeaveDays} day${approvedLeaveDays !== 1 ? 's' : ''}</span></div>
          </div>
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Absent (payroll deduction)</div>
            <div class="info-row formula-row"><span class="info-label">Absent days used in payroll</span><span class="info-value">${absentPayroll} day${absentPayroll !== 1 ? 's' : ''} (${workingDaysInMonth} − ${workedDays} − ${approvedLeaveDays})</span></div>
          </div>
          <div class="salary-detail-section">
            <div class="salary-detail-section-title">Other</div>
            <div class="info-row"><span class="info-label">Incomplete punch</span><span class="info-value">${incomplete.length} day${incomplete.length !== 1 ? 's' : ''}</span></div>
          </div>
        `;

        const b = detail.breakdown || {};
        const bGross = b.gross != null ? Number(b.gross) : detailGross;
        const bNet = Math.max(0, (b.net != null ? Number(b.net) : detailNet));
        const bChargeable = Number(b.chargeable_salary ?? 0);
        const bOriginalAtt = Number(b.original_attendance_deduction ?? 0);
        const bCappedAtt = Number(b.capped_attendance_deduction ?? 0);
        const bDed = b.total_deductions != null ? Number(b.total_deductions) : c.deductions;
        const showEpfTax = bChargeable > 0;
        const epfRow = showEpfTax ? `<tr><td style="padding:8px;">EPF (11% on chargeable)</td><td style="padding:8px;text-align:right">- RM ${money(b.epf ?? e.epfTax ?? 0)}</td></tr>` : '';
        const taxRow = showEpfTax ? `<tr><td style="padding:8px;">Tax (3% on chargeable)</td><td style="padding:8px;text-align:right">- RM ${money(b.tax ?? e.tax_total ?? 0)}</td></tr>` : '';
        breakdown.innerHTML = `
          <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Earnings</td></tr>
          <tr><td style="padding:8px;">Basic Salary</td><td style="padding:8px;text-align:right">RM ${money(b.base ?? e.base ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Allowance</td><td style="padding:8px;text-align:right">RM ${money(b.allowance ?? e.allow ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Adjustments</td><td style="padding:8px;text-align:right">RM ${money(b.adjustment ?? e.adjustment_total ?? 0)}</td></tr>
          <tr><td style="padding:8px;background:#e0f2fe;"><strong>Gross Pay</strong></td><td style="padding:8px;text-align:right;background:#e0f2fe;"><strong>RM ${money(bGross)}</strong></td></tr>
          <tr><td colspan="2" style="padding:8px;background:#f1f5f9;font-weight:700;">Attendance-related deductions</td></tr>
          <tr><td style="padding:8px;">Late</td><td style="padding:8px;text-align:right">- RM ${money(b.late_ded ?? e.late_ded ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Absent</td><td style="padding:8px;text-align:right">- RM ${money(b.absent_ded ?? e.absent_ded ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Unpaid Leave</td><td style="padding:8px;text-align:right">- RM ${money(b.unpaid_ded ?? e.unpaid_ded ?? 0)}</td></tr>
          <tr><td style="padding:8px;">Penalties</td><td style="padding:8px;text-align:right">- RM ${money(b.penalty ?? e.penalty ?? 0)}</td></tr>
          <tr><td style="padding:8px;background:#fef3c7;"><strong>Original deduction total</strong></td><td style="padding:8px;text-align:right;background:#fef3c7;"><strong>- RM ${money(bOriginalAtt)}</strong></td></tr>
          <tr><td style="padding:8px;background:#fef3c7;">Capped at Basic Salary</td><td style="padding:8px;text-align:right;background:#fef3c7;"><strong>- RM ${money(bCappedAtt)}</strong></td></tr>
          <tr><td style="padding:8px;background:#d1fae5;"><strong>Chargeable salary (after cap)</strong></td><td style="padding:8px;text-align:right;background:#d1fae5;"><strong>RM ${money(bChargeable)}</strong></td></tr>
          ${epfRow}
          ${taxRow}
          <tr><td style="padding:8px;background:#fee2e2;"><strong>Total deductions</strong></td><td style="padding:8px;text-align:right;background:#fee2e2;"><strong>- RM ${money(bDed)}</strong></td></tr>
          <tr><td style="padding:8px;background:#f8fafc"><strong>Net Pay</strong></td><td style="padding:8px;text-align:right;background:#f8fafc"><strong>RM ${money(bNet)}</strong></td></tr>
        `;

      } catch (err) {
        attendanceLists.innerHTML = '<div class="salary-detail-section"><div class="info-row"><span class="info-label">—</span><span class="info-value">No records for this period.</span></div></div>';
        if (bankDetails) {
          bankDetails.innerHTML = '<div class="salary-detail-section"><div class="info-row"><span class="info-label">Bank</span><span class="info-value">—</span></div></div>';
        }
      }

      modal.style.display = 'flex';
    }


    loadData();
  });
  </script>
</body>
</html>
