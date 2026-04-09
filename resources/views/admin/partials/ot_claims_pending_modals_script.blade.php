<div class="overlay" id="adminOtApproveOverlay" aria-hidden="true">
  <div class="panel" role="dialog" aria-modal="true" style="max-width:420px;">
    <div class="panel-header">
      <h3 id="admin_ot_approve_title"><i class="fa-solid fa-lock" style="color:#16a34a; margin-right:8px;"></i> Approve & post to payroll</h3>
    </div>
    <div class="panel-body">
      <div id="admin_ot_override_warning" class="warning-box" style="display:none;"></div>
      <p id="admin_ot_approve_intro" style="margin:0 0 14px; color:#64748b;">Re-enter your admin password to approve the selected claim(s) and post to payroll.</p>
      <div class="modal-field">
        <label for="admin_ot_password">Password</label>
        <input type="password" id="admin_ot_password" autocomplete="current-password" placeholder="Your admin password">
      </div>
      <div class="modal-field">
        <label for="admin_ot_remark" id="admin_ot_remark_label">Remark (optional)</label>
        <textarea id="admin_ot_remark" rows="2" placeholder="Note for records" maxlength="500"></textarea>
      </div>
      <p id="admin_ot_password_error" style="display:none; margin:8px 0 0; font-size:13px; color:#dc2626;"></p>
    </div>
    <div class="panel-footer">
      <button type="button" class="btn-sm btn-outline" data-close="adminOtApproveOverlay">Cancel</button>
      <button type="button" class="btn-sm btn-approve" id="admin_ot_approve_confirm"><i class="fa-solid fa-check"></i> <span id="admin_ot_approve_btn_text">Approve</span></button>
    </div>
  </div>
</div>

<div class="overlay" id="adminOtRejectOverlay" aria-hidden="true">
  <div class="panel" role="dialog" aria-modal="true" style="max-width:420px;">
    <div class="panel-header">
      <h3><i class="fa-solid fa-times-circle" style="color:#dc2626; margin-right:8px;"></i> Confirm rejection</h3>
    </div>
    <div class="panel-body">
      <p id="admin_ot_reject_summary" style="margin:0 0 12px; color:#64748b;"></p>
      <div id="admin_ot_reject_supervisor_note" class="warning-box" style="display:none;"></div>
      <div class="modal-field">
        <label for="admin_ot_reject_remark">HR remarks / reason for rejection <span style="color:#dc2626;">*</span></label>
        <textarea id="admin_ot_reject_remark" rows="3" placeholder="Enter reason" maxlength="500"></textarea>
      </div>
      <p id="admin_ot_reject_error" style="display:none; margin:8px 0 0; font-size:13px; color:#dc2626;"></p>
    </div>
    <div class="panel-footer">
      <button type="button" class="btn-sm btn-outline" data-close="adminOtRejectOverlay">Cancel</button>
      <button type="button" class="btn-sm btn-reject" id="admin_ot_reject_confirm"><i class="fa-solid fa-xmark"></i> Reject</button>
    </div>
  </div>
</div>

<div class="overlay" id="adminOtViewOverlay" aria-hidden="true">
  <div class="panel" role="dialog" aria-modal="true" style="max-width:720px;">
    <div class="panel-header">
      <h3><i class="fa-solid fa-file-lines" style="color:#2563eb; margin-right:8px;"></i> OT claim details</h3>
    </div>
    <div class="panel-body">
      <div class="claim-review-grid">
        <div><span class="label">Employee</span><span class="value" id="admin_ot_view_employee">—</span></div>
        <div><span class="label">Employee code</span><span class="value" id="admin_ot_view_employee_code">—</span></div>
        <div><span class="label">Department</span><span class="value" id="admin_ot_view_department">—</span></div>
        <div><span class="label">Supervisor</span><span class="value" id="admin_ot_view_supervisor">—</span></div>
        <div><span class="label">OT work date</span><span class="value" id="admin_ot_view_date">—</span></div>
        <div><span class="label">Claim filed</span><span class="value" id="admin_ot_view_submitted_at">—</span></div>
        <div><span class="label">Claimed hours</span><span class="value" id="admin_ot_view_hours">—</span></div>
        <div><span class="label">Estimated payout</span><span class="value" id="admin_ot_view_payout">—</span></div>
      </div>
      <div class="claim-review-block">
        <span class="label">Supervisor recommendation</span>
        <span class="value" id="admin_ot_view_recommendation">—</span>
      </div>
      <div class="claim-review-block">
        <span class="label">Supervisor remarks</span>
        <span class="value" id="admin_ot_view_supervisor_remark">—</span>
      </div>
      <div class="claim-review-block">
        <span class="label">Employee reason</span>
        <span class="value" id="admin_ot_view_reason">—</span>
      </div>
      <div class="claim-review-block">
        <span class="label">Supporting information</span>
        <span class="value" id="admin_ot_view_supporting_info">—</span>
      </div>
      <div class="claim-review-block">
        <span class="label">Submitted proof / attachment</span>
        <span class="value"><a href="#" id="admin_ot_view_attachment" target="_blank" rel="noopener" style="display:none;">Open attachment</a><span id="admin_ot_view_attachment_empty">No attachment submitted</span></span>
      </div>
    </div>
    <div class="panel-footer">
      <button type="button" class="btn-sm btn-outline" data-close="adminOtViewOverlay">Close</button>
    </div>
  </div>
</div>

<script>
(function() {
  var NOT_RECOMMENDED = 'not_recommended';

  function closeOverlay(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.remove('open'); el.setAttribute('aria-hidden', 'true'); }
  }
  function openOverlay(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.add('open'); el.setAttribute('aria-hidden', 'false'); }
  }
  document.querySelectorAll('[data-close]').forEach(function(btn) {
    btn.addEventListener('click', function() { closeOverlay(this.getAttribute('data-close')); });
  });
  document.querySelectorAll('.overlay').forEach(function(el) {
    el.addEventListener('click', function(e) { if (e.target === el) closeOverlay(el.id); });
  });

  var approveOverlay = document.getElementById('adminOtApproveOverlay');
  var rejectOverlay = document.getElementById('adminOtRejectOverlay');
  var viewOverlay = document.getElementById('adminOtViewOverlay');

  function getSelectedRows() {
    return Array.from(document.querySelectorAll('.admin-ot-row-check:checked'));
  }

  function updateAdminOtSelectedCount() {
    var el = document.getElementById('admin-ot-selected-count');
    if (!el) return;
    el.textContent = getSelectedRows().length + ' selected';
  }
  var selectAll = document.getElementById('admin-ot-select-all');
  var rowChecks = document.querySelectorAll('.admin-ot-row-check');
  if (selectAll) {
    selectAll.addEventListener('change', function() {
      rowChecks.forEach(function(cb) { cb.checked = selectAll.checked; });
      updateAdminOtSelectedCount();
    });
  }
  rowChecks.forEach(function(cb) { cb.addEventListener('change', updateAdminOtSelectedCount); });
  updateAdminOtSelectedCount();

  document.querySelectorAll('.js-admin-view-claim').forEach(function(link) {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      var claimId = this.getAttribute('data-claim-id');
      var rowCheck = document.querySelector('.admin-ot-row-check[data-claim-id="' + claimId + '"]');
      if (!rowCheck || !viewOverlay) {
        return;
      }
      document.getElementById('admin_ot_view_employee').textContent = rowCheck.dataset.employeeName || '—';
      document.getElementById('admin_ot_view_employee_code').textContent = rowCheck.dataset.employeeCode || '—';
      document.getElementById('admin_ot_view_department').textContent = rowCheck.dataset.department || '—';
      document.getElementById('admin_ot_view_supervisor').textContent = rowCheck.dataset.supervisorName || '—';
      document.getElementById('admin_ot_view_date').textContent = rowCheck.dataset.date || '—';
      document.getElementById('admin_ot_view_submitted_at').textContent = rowCheck.dataset.submittedAt || '—';
      document.getElementById('admin_ot_view_hours').textContent = (rowCheck.dataset.hours || '0.00') + ' h';
      document.getElementById('admin_ot_view_payout').textContent = 'RM ' + (rowCheck.dataset.payout || '0.00');
      document.getElementById('admin_ot_view_recommendation').textContent = rowCheck.dataset.supervisorRecommendation || '—';
      document.getElementById('admin_ot_view_supervisor_remark').textContent = rowCheck.dataset.supervisorRemark || 'No supervisor remark submitted.';
      document.getElementById('admin_ot_view_reason').textContent = rowCheck.dataset.reason || '—';
      document.getElementById('admin_ot_view_supporting_info').textContent = rowCheck.dataset.supportingInfo || '—';

      var attachmentLink = document.getElementById('admin_ot_view_attachment');
      var attachmentEmpty = document.getElementById('admin_ot_view_attachment_empty');
      if (rowCheck.dataset.attachmentUrl) {
        attachmentLink.href = rowCheck.dataset.attachmentUrl;
        attachmentLink.style.display = 'inline';
        attachmentEmpty.style.display = 'none';
      } else {
        attachmentLink.style.display = 'none';
        attachmentEmpty.style.display = 'inline';
      }
      openOverlay('adminOtViewOverlay');
    });
  });

  document.querySelector('.js-admin-bulk-approve')?.addEventListener('click', function() {
    var selectedRows = getSelectedRows();
    if (!selectedRows.length) { alert('Please select at least one claim.'); return; }
    document.getElementById('admin_ot_password').value = '';
    document.getElementById('admin_ot_remark').value = '';
    document.getElementById('admin_ot_password_error').style.display = 'none';
    var overrides = selectedRows.filter(function(row) { return row.dataset.supervisorAction === NOT_RECOMMENDED; });
    var warning = document.getElementById('admin_ot_override_warning');
    var intro = document.getElementById('admin_ot_approve_intro');
    var title = document.getElementById('admin_ot_approve_title');
    var remarkLabel = document.getElementById('admin_ot_remark_label');
    var remarkInput = document.getElementById('admin_ot_remark');
    var buttonText = document.getElementById('admin_ot_approve_btn_text');
    if (overrides.length) {
      var firstSupervisor = overrides[0].dataset.supervisorName || 'the assigned supervisor';
      title.innerHTML = '<i class="fa-solid fa-triangle-exclamation" style="color:#d97706; margin-right:8px;"></i> Override warning';
      warning.textContent = '⚠ Warning: You are approving ' + overrides.length + ' OT claim(s) that were explicitly Not Recommended by the supervisor (' + firstSupervisor + ').';
      warning.style.display = 'block';
      intro.textContent = 'Provide a clear override justification for audit purposes, then re-enter your admin password.';
      remarkLabel.innerHTML = 'Override justification <span style="color:#dc2626;">*</span>';
      remarkInput.placeholder = 'Why are you overriding the supervisor recommendation?';
      buttonText.textContent = 'Approve & Override';
    } else {
      title.innerHTML = '<i class="fa-solid fa-lock" style="color:#16a34a; margin-right:8px;"></i> Approve & post to payroll';
      warning.style.display = 'none';
      intro.textContent = 'Re-enter your admin password to approve the selected claim(s) and post to payroll.';
      remarkLabel.textContent = 'Remark (optional)';
      remarkInput.placeholder = 'Note for records';
      buttonText.textContent = 'Approve';
    }
    openOverlay('adminOtApproveOverlay');
  });

  document.getElementById('admin_ot_approve_confirm')?.addEventListener('click', function() {
    var pwd = (document.getElementById('admin_ot_password').value || '').trim();
    var remark = (document.getElementById('admin_ot_remark').value || '').trim();
    var errEl = document.getElementById('admin_ot_password_error');
    var hasOverride = getSelectedRows().some(function(row) { return row.dataset.supervisorAction === NOT_RECOMMENDED; });
    if (!pwd) {
      errEl.textContent = 'Please enter your password.';
      errEl.style.display = 'block';
      return;
    }
    if (hasOverride && !remark) {
      errEl.textContent = 'Override justification is required when approving Not Recommended claims.';
      errEl.style.display = 'block';
      return;
    }
    var form = document.getElementById('adminOtBulkApproveForm');
    form.querySelector('#admin_ot_bulk_password').value = pwd;
    form.querySelector('#admin_ot_bulk_remark').value = remark;
    form.submit();
  });

  document.querySelector('.js-admin-bulk-reject')?.addEventListener('click', function() {
    var selectedRows = getSelectedRows();
    if (!selectedRows.length) { alert('Please select at least one claim.'); return; }
    document.getElementById('admin_ot_reject_remark').value = '';
    document.getElementById('admin_ot_reject_error').style.display = 'none';
    document.getElementById('admin_ot_reject_summary').textContent = 'You are about to reject ' + selectedRows.length + ' OT claim(s).';
    var noteBox = document.getElementById('admin_ot_reject_supervisor_note');
    var notRecommendedRows = selectedRows.filter(function(row) { return row.dataset.supervisorAction === NOT_RECOMMENDED; });
    if (notRecommendedRows.length) {
      var first = notRecommendedRows[0];
      var supName = first.dataset.supervisorName || 'Supervisor';
      var supRemark = first.dataset.supervisorRemark || 'No supervisor reason was submitted.';
      noteBox.textContent = supName + ' noted: "' + supRemark + '"';
      noteBox.style.display = 'block';
    } else {
      noteBox.style.display = 'none';
    }
    openOverlay('adminOtRejectOverlay');
  });

  document.getElementById('admin_ot_reject_confirm')?.addEventListener('click', function() {
    var remark = (document.getElementById('admin_ot_reject_remark').value || '').trim();
    var errEl = document.getElementById('admin_ot_reject_error');
    if (!remark) {
      errEl.textContent = 'Please enter a rejection reason.';
      errEl.style.display = 'block';
      return;
    }
    var form = document.getElementById('adminOtBulkRejectForm');
    form.querySelectorAll('input[name="ids[]"]').forEach(function(inp) { inp.remove(); });
    getSelectedRows().forEach(function(cb) {
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'ids[]';
      inp.value = cb.value;
      form.appendChild(inp);
    });
    form.querySelector('#admin_ot_bulk_reject_remark').value = remark;
    form.submit();
  });
})();
</script>
