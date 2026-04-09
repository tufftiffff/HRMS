<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Claims — History - Admin - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @include('admin.partials.ot_claims_styles')
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info"><a href="{{ route('admin.profile') }}" style="text-decoration:none;color:inherit;"><i class="fa-regular fa-bell"></i> &nbsp; HR Admin</a></div>
  </header>
  <div class="container">
    @include('admin.layout.sidebar')
    <main>
      <div class="breadcrumb">Payroll · OT Claims · History</div>
      <h2 style="margin:0 0 4px;">OT Claims — View history</h2>
      <p style="margin:0; color:#64748b;">Read-only log of claims you have already approved, rejected, or placed on hold. Use <strong>Process claims</strong> to work the pending queue.</p>

      <nav class="ot-split-nav" aria-label="OT claims sections">
        <a href="{{ route('admin.payroll.overtime_claims') }}"><i class="fa-solid fa-inbox"></i> Process claims</a>
        <a href="{{ route('admin.payroll.overtime_claims.history') }}" class="active"><i class="fa-solid fa-clock-rotate-left"></i> View history</a>
      </nav>

      @if(session('success'))
        <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
      @endif
      @if(session('error'))
        <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
      @endif

      <div class="ot-summary-cards">
        <div class="ot-summary-card pending-admin">
          <span class="num">{{ $pendingCount ?? 0 }}</span>
          <span class="label">Still pending (filtered)</span>
        </div>
        <div class="ot-summary-card approved">
          <span class="num">{{ $historyApprovedCount ?? 0 }}</span>
          <span class="label">Approved in list</span>
        </div>
        <div class="ot-summary-card rejected">
          <span class="num">{{ $historyRejectedCount ?? 0 }}</span>
          <span class="label">Rejected in list</span>
        </div>
        <div class="ot-summary-card on-hold">
          <span class="num">{{ $historyOnHoldCount ?? 0 }}</span>
          <span class="label">On hold in list</span>
        </div>
      </div>
      @if(($pendingCount ?? 0) > 0)
        <p style="margin:-8px 0 16px; font-size:13px; color:#64748b;">
          <a href="{{ route('admin.payroll.overtime_claims', request()->only('q','department','start','end')) }}" style="color:#4f46e5; font-weight:600;">Go to process claims →</a>
        </p>
      @endif

      <div class="card">
        <h3 class="section-title"><i class="fa-solid fa-clipboard-list"></i> Processed claims</h3>
        <form method="GET" action="{{ route('admin.payroll.overtime_claims.history') }}" class="toolbar" style="margin-bottom:12px;">
          <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name/ID">
          <select name="department">
            <option value="">All Depts</option>
            @foreach($departments as $d)
              <option value="{{ $d->department_id }}" {{ request('department') == $d->department_id ? 'selected' : '' }}>{{ $d->department_name }}</option>
            @endforeach
          </select>
          <input type="date" name="start" value="{{ request('start') }}">
          <input type="date" name="end" value="{{ request('end') }}">
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>
        @if($actedClaims->total() === 0)
          <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i> No records match your filters.</div>
        @else
          <div class="table-wrap">
            <table class="ot-table">
              <thead>
                <tr>
                  <th>Employee</th>
                  <th>Dept</th>
                  <th>Supervisor</th>
                  <th>Status</th>
                  <th>Supervisor comment</th>
                  <th>Admin comment</th>
                  <th>OT &amp; filing</th>
                  <th>Hours</th>
                  <th>Reason</th>
                  <th>Attachment</th>
                  <th>Your action</th>
                  <th>Progress</th>
                </tr>
              </thead>
              <tbody>
                @foreach($actedClaims as $c)
                  <tr>
                    <td class="employee-cell">
                      <strong>{{ $c->employee->user->name ?? '—' }}</strong>
                      <div class="employee-meta">{{ $c->employee->employee_code ?? '' }}</div>
                    </td>
                    <td>{{ $c->employee->department->department_name ?? '—' }}</td>
                    <td>{{ $c->supervisor->name ?? '—' }}</td>
                    <td>
                      @php $rec2 = $c->getSupervisorRecommendationLabelForAdmin(); @endphp
                      @if($rec2)
                        <span class="status-badge {{ $c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED ? 'status-rejected' : ($c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED ? 'status-approved' : 'status-hold') }}">{{ $rec2 }}</span>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    @php
                      $histSupDec = filled($c->supervisor_remark) ? html_entity_decode((string) $c->supervisor_remark, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                      $histAdmDec = filled($c->admin_remark) ? html_entity_decode((string) $c->admin_remark, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
                    @endphp
                    <td class="comment-cell">
                      @if($histSupDec !== '')
                        <span class="comment-preview" title="{{ e($histSupDec) }}">{{ Str::limit($histSupDec, 120) }}</span>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    <td class="comment-cell">
                      @if($histAdmDec !== '')
                        <span class="comment-preview" title="{{ e($histAdmDec) }}">{{ Str::limit($histAdmDec, 120) }}</span>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    <td class="ot-when-cell">
                      <div class="ot-when-block">
                        <span class="ot-when-label">OT work date</span>
                        <span class="ot-when-value">{{ $c->date?->format('Y-m-d') ?? '—' }}</span>
                      </div>
                      <div class="ot-when-block">
                        <span class="ot-when-label">Claim filed</span>
                        <span>{{ $c->submitted_at ? $c->submitted_at->format('M j, Y · g:i A') : '—' }}</span>
                      </div>
                    </td>
                    <td>{{ number_format($c->getEffectiveApprovedHours(), 2) }} h</td>
                    <td class="reason-text">{{ Str::limit($c->reason ?? '—', 50) }}</td>
                    <td>
                      @if($c->attachment_path)
                        <a href="{{ route('admin.payroll.overtime_claims.attachment', $c) }}" target="_blank" rel="noopener">View</a>
                      @else
                        <span class="employee-meta">—</span>
                      @endif
                    </td>
                    <td>
                      @if($c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_APPROVED)
                        <span class="status-badge status-approved">Approved by you</span>
                      @elseif($c->status === \App\Models\OvertimeClaim::STATUS_ADMIN_REJECTED)
                        <span class="status-badge status-rejected">Rejected by you</span>
                      @else
                        <span class="status-badge status-hold">On hold</span>
                      @endif
                    </td>
                    <td><span class="progress-badge">{{ $c->getProgressLabelForAdmin() }}</span></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="pagination-wrap">
            {{ $actedClaims->links() }}
          </div>
        @endif
      </div>
    </main>
  </div>
</body>
</html>
