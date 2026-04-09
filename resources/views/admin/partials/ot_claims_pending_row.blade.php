@php
  $claimantRole = strtolower(trim((string) ($c->employee->user->role ?? '')));
  $isSupervisorDirectClaim = $claimantRole === 'supervisor';
@endphp
<tr class="{{ $c->hasNoProofFlag() ? 'row-no-proof' : '' }} {{ $isSupervisorDirectClaim ? 'row-supervisor-direct' : '' }}">
  <td><input
      type="checkbox"
      name="ids[]"
      form="adminOtBulkApproveForm"
      value="{{ $c->id }}"
      class="admin-ot-row-check"
      data-claim-id="{{ $c->id }}"
      data-employee-name="{{ $c->employee->user->name ?? '—' }}"
      data-employee-code="{{ $c->employee->employee_code ?? '' }}"
      data-department="{{ $c->employee->department->department_name ?? '—' }}"
      data-supervisor-name="{{ $c->supervisor->name ?? '—' }}"
      data-supervisor-action="{{ $c->supervisor_action_type ?? '' }}"
      data-supervisor-recommendation="{{ $c->getSupervisorRecommendationLabelForAdmin() ?? '—' }}"
      data-supervisor-remark="{{ $c->supervisor_remark ?? '' }}"
      data-date="{{ $c->date?->format('Y-m-d') ?? '' }}"
      data-hours="{{ number_format((float) $c->hours, 2, '.', '') }}"
      data-payout="{{ number_format(\App\Http\Controllers\AdminOvertimeClaimController::computePayout($c), 2, '.', '') }}"
      data-reason="{{ $c->reason ?? '' }}"
      data-supporting-info="{{ $c->supporting_info ?? '' }}"
      data-submitted-at="{{ $c->submitted_at ? $c->submitted_at->format('M j, Y · g:i A') : '—' }}"
      data-attachment-url="{{ $c->attachment_path ? route('admin.payroll.overtime_claims.attachment', $c) : '' }}"
    ></td>
  <td class="employee-cell">
    <strong>{{ $c->employee->user->name ?? '—' }}</strong>
    <div class="employee-meta">{{ $c->employee->employee_code ?? '' }}</div>
  </td>
  <td>{{ $c->employee->department->department_name ?? '—' }}</td>
  <td>{{ $c->supervisor->name ?? '—' }}</td>
  <td>
    @php $rec = $c->getSupervisorRecommendationLabelForAdmin(); @endphp
    @if($rec)
      <span class="status-badge {{ $c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED ? 'status-rejected' : ($c->supervisor_action_type === \App\Models\OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED ? 'status-approved' : 'status-hold') }}">{{ $rec }}</span>
    @elseif($isSupervisorDirectClaim)
      <span class="status-badge status-supervisor-direct">Supervisor direct request</span>
    @else
      <span class="employee-meta">—</span>
    @endif
  </td>
  @php
    $supRemDec = filled($c->supervisor_remark) ? html_entity_decode((string) $c->supervisor_remark, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
    $admRemDec = filled($c->admin_remark) ? html_entity_decode((string) $c->admin_remark, ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
  @endphp
  <td class="comment-cell">
    @if($supRemDec !== '')
      <span class="comment-preview" title="{{ e($supRemDec) }}">{{ Str::limit($supRemDec, 120) }}</span>
    @else
      <span class="employee-meta">—</span>
    @endif
  </td>
  <td class="comment-cell">
    @if($admRemDec !== '')
      <span class="comment-preview" title="{{ e($admRemDec) }}">{{ Str::limit($admRemDec, 120) }}</span>
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
  <td>{{ number_format($c->hours, 2) }}</td>
  <td>
    <a href="#" class="js-admin-view-claim" data-claim-id="{{ $c->id }}">View</a>
  </td>
  <td>{{ number_format(\App\Http\Controllers\AdminOvertimeClaimController::computePayout($c), 2) }}</td>
</tr>
