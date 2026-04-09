<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Leave - HRMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="{{ asset('css/hrms.css') }}">
  @include('employee.leave._styles')
</head>
<body>
  <header>
    <div class="title">Web-Based HRMS</div>
    <div class="user-info">
      <span><i class="fa-regular fa-bell"></i> &nbsp; <a href="{{ route('employee.profile') }}" style="color:inherit; text-decoration:none;">{{ Auth::user()->name ?? 'Employee' }}</a></span>
    </div>
  </header>

  <div class="container">
    @include('employee.layout.sidebar')

    <main>
      <div class="breadcrumb">Leave &gt; Apply</div>
      <h2>Apply for leave</h2>
      <p class="subtitle">Submit a new request. Balances and history are on <a href="{{ route('employee.leave.view') }}">View my leave</a>.</p>

      @include('employee.leave._subnav', ['leaveActiveTab' => 'apply'])

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      @if(isset($balances) && $balances->isNotEmpty())
      <div class="kpi-grid" style="margin-bottom:14px;">
        @foreach($balances as $bal)
          <div class="kpi">
            <div class="kpi-label">{{ $bal['name'] }} — Remaining</div>
            <div class="kpi-value">{{ $bal['remaining'] }} <span style="font-size:0.6em; font-weight:500; color:#64748b;">days</span></div>
            <div class="kpi-sub">Used: {{ $bal['used'] }} · Pending: {{ $bal['pending'] }} · Entitlement: {{ $bal['total'] }}</div>
          </div>
        @endforeach
      </div>
      @endif

      <div class="card">
        <header>
          <div class="card-title">Application form</div>
          <div class="pill"><i class="fa-solid fa-circle-info"></i> Inclusive of start & end dates</div>
        </header>
        <form id="leave-form" class="form" method="POST" action="{{ route('employee.leave.store') }}" enctype="multipart/form-data" novalidate>
          @csrf
          <div class="form-grid">
            <div>
              <label for="leave_type_id">Type</label>
              <select id="leave_type_id" name="leave_type_id" required>
                <option value="" disabled {{ old('leave_type_id') ? '' : 'selected' }}>Select type</option>
                @foreach($leaveTypes as $type)
                  <option value="{{ $type->leave_type_id }}"
                    data-proof-requirement="{{ $type->proof_requirement ?? 'none' }}"
                    data-proof-label="{{ $type->getProofLabel() }}"
                    {{ old('leave_type_id') == $type->leave_type_id ? 'selected' : '' }}>
                    {{ $type->leave_name }}
                  </option>
                @endforeach
              </select>
              <div class="error" data-err="leave_type_id"></div>
            </div>
            <div>
              <label for="start_date">Start Date</label>
              <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required>
              <div class="error" data-err="start_date"></div>
            </div>
            <div>
              <label for="end_date">End Date</label>
              <input type="date" id="end_date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}" required>
              <div class="error" data-err="end_date"></div>
            </div>
            <div>
              <label for="total_days_display">Total Days (auto)</label>
              <input type="text" id="total_days_display" value="-" readonly>
            </div>
          </div>
          <div style="margin-top:12px;">
            <label for="reason">Reason / Notes (optional)</label>
            <textarea id="reason" name="reason" placeholder="Reason / Notes" maxlength="500">{{ old('reason') }}</textarea>
            <div class="error" data-err="reason"></div>
          </div>
          <div id="proof-field-wrap" style="margin-top:12px; display:none;">
            <label for="proof" id="proof-label">Supporting document</label>
            <input type="file" id="proof" name="proof" accept=".pdf,.jpg,.jpeg,.png" style="padding:8px 0;">
            <div class="error" data-err="proof"></div>
          </div>
          <div style="margin-top:14px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary" id="submit-btn"><i class="fa-solid fa-paper-plane"></i> Submit Request</button>
            <a href="{{ route('employee.leave.view') }}" class="btn" style="background:#fff; border:1px solid #d1d5db; color:#334155;">Cancel</a>
            <span class="muted">Status starts as Pending. Total days is inclusive of start and end dates.</span>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('leave-form');
      const start = document.getElementById('start_date');
      const end = document.getElementById('end_date');
      const total = document.getElementById('total_days_display');
      const type = document.getElementById('leave_type_id');
      const proofWrap = document.getElementById('proof-field-wrap');
      const proofInput = document.getElementById('proof');
      const proofLabel = document.getElementById('proof-label');
      const errEls = {
        leave_type_id: document.querySelector('[data-err="leave_type_id"]'),
        start_date: document.querySelector('[data-err="start_date"]'),
        end_date: document.querySelector('[data-err="end_date"]'),
        reason: document.querySelector('[data-err="reason"]'),
        proof: document.querySelector('[data-err="proof"]'),
      };
      const setError = (field, msg) => { if (errEls[field]) errEls[field].textContent = msg || ''; };
      function updateProofField() {
        const opt = type.options[type.selectedIndex];
        if (!opt || !opt.value) {
          proofWrap.style.display = 'none';
          proofInput.removeAttribute('required');
          proofInput.value = '';
          setError('proof', '');
          return;
        }
        const req = (opt.dataset.proofRequirement || 'none').toLowerCase();
        proofLabel.textContent = opt.dataset.proofLabel || 'Supporting document';
        if (req === 'none') {
          proofWrap.style.display = 'none';
          proofInput.removeAttribute('required');
          proofInput.value = '';
          setError('proof', '');
        } else {
          proofWrap.style.display = 'block';
          if (req === 'required') proofInput.setAttribute('required', 'required');
          else proofInput.removeAttribute('required');
        }
      }
      type.addEventListener('change', updateProofField);
      updateProofField();
      const calc = () => {
        setError('start_date', '');
        setError('end_date', '');
        if (!start.value || !end.value) { total.value = '-'; return; }
        const s = new Date(start.value);
        const e = new Date(end.value);
        if (isNaN(s) || isNaN(e)) { total.value = '-'; return; }
        if (e < s) { total.value = '-'; setError('end_date', 'End date cannot be before start date.'); return; }
        const diff = Math.round((e - s) / 86400000) + 1;
        total.value = diff + ' day' + (diff === 1 ? '' : 's');
      };
      start.addEventListener('change', () => { end.min = start.value; calc(); });
      end.addEventListener('change', calc);
      type.addEventListener('change', () => setError('leave_type_id', ''));
      form.addEventListener('submit', (e) => {
        let ok = true;
        setError('leave_type_id',''); setError('start_date',''); setError('end_date',''); setError('proof','');
        if (!type.value) { setError('leave_type_id', 'Please select a leave type.'); ok = false; }
        if (!start.value) { setError('start_date', 'Start date is required.'); ok = false; }
        if (!end.value) { setError('end_date', 'End date is required.'); ok = false; }
        if (start.value && end.value) {
          const s = new Date(start.value); const e2 = new Date(end.value);
          if (e2 < s) { setError('end_date', 'End date cannot be before start date.'); ok = false; }
        }
        const opt = type.options[type.selectedIndex];
        if (opt && opt.value && (opt.dataset.proofRequirement || '').toLowerCase() === 'required' && !proofInput.files.length) {
          setError('proof', 'Proof document is required for this leave type.');
          ok = false;
        }
        if (!ok) e.preventDefault();
      });
      calc();
    });
  </script>
</body>
</html>
