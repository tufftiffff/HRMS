@if(session('success'))
  <div class="notice success" style="padding:10px; background:#dcfce7; color:#166534; border-radius:10px; margin-bottom:12px;">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">{{ session('error') }}</div>
@endif
@if(isset($errors) && $errors->any())
  <div class="notice error" style="padding:10px; background:#fee2e2; color:#991b1b; border-radius:10px; margin-bottom:12px;">
    @foreach($errors->all() as $err) {{ $err }} @endforeach
  </div>
@endif

<div class="ot-summary-cards">
  <div class="ot-summary-card pending-admin">
    <span class="num">{{ $pendingCount ?? 0 }}</span>
    <span class="label">Pending Admin</span>
  </div>
  <div class="ot-summary-card flagged-pending">
    <span class="num">{{ $exceptionsCount ?? 0 }}</span>
    <span class="label">Exceptions queue</span>
  </div>
  <div class="ot-summary-card approved">
    <span class="num">{{ $approvedCount ?? 0 }}</span>
    <span class="label">Approved (all time)</span>
  </div>
  <div class="ot-summary-card rejected">
    <span class="num">{{ $rejectedCount ?? 0 }}</span>
    <span class="label">Rejected</span>
  </div>
</div>

<div class="card">
  <h3 class="section-title"><i class="fa-solid fa-inbox"></i> Pending your approval</h3>
  @if($pendingClaims->isEmpty())
    <div class="empty-state"><i class="fa-solid fa-inbox"></i> No OT claims pending your approval.</div>
  @else
    <form method="GET" action="{{ route('admin.payroll.overtime_claims') }}" class="toolbar" style="margin-bottom:12px;">
      <input type="hidden" name="queue" value="{{ $queue ?? 'all' }}">
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
    <form method="POST" action="{{ route('admin.payroll.overtime_claims.bulk_approve') }}" id="adminOtBulkApproveForm" style="display:none;">
      @csrf
      <input type="hidden" name="queue" value="{{ $queue ?? 'all' }}">
      <input type="hidden" name="q" value="{{ request('q') }}">
      <input type="hidden" name="department" value="{{ request('department') }}">
      <input type="hidden" name="start" value="{{ request('start') }}">
      <input type="hidden" name="end" value="{{ request('end') }}">
      <input type="hidden" name="password" id="admin_ot_bulk_password">
      <input type="hidden" name="remark" id="admin_ot_bulk_remark">
    </form>
    <form method="POST" action="{{ route('admin.payroll.overtime_claims.bulk_reject') }}" id="adminOtBulkRejectForm">
      @csrf
      <input type="hidden" name="queue" value="{{ $queue ?? 'all' }}">
      <input type="hidden" name="q" value="{{ request('q') }}">
      <input type="hidden" name="department" value="{{ request('department') }}">
      <input type="hidden" name="start" value="{{ request('start') }}">
      <input type="hidden" name="end" value="{{ request('end') }}">
      <input type="hidden" name="remark" id="admin_ot_bulk_reject_remark">
    </form>
    <div class="bulk-actions">
      <span class="ot-selected-count" id="admin-ot-selected-count">0 selected</span>
      <button type="button" class="btn-ot-approve js-admin-bulk-approve">Approve selected</button>
      <button type="button" class="btn-ot-reject js-admin-bulk-reject">Reject selected</button>
    </div>
    <div class="table-wrap">
      <table class="ot-table">
        <thead>
          <tr>
            <th style="width:42px"><input type="checkbox" id="admin-ot-select-all" title="Select all"></th>
            <th>Employee</th>
            <th>Dept</th>
            <th>Supervisor</th>
            <th>Status</th>
            <th>Supervisor comment</th>
            <th>Admin comment</th>
            <th>OT &amp; filing</th>
            <th>Hours</th>
            <th>Attachment</th>
            <th>Payout</th>
          </tr>
        </thead>
        <tbody>
          @foreach($pendingClaims as $c)
            @include('admin.partials.ot_claims_pending_row', ['c' => $c])
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
