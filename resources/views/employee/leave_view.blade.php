<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Leave - HRMS</title>
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
      <div class="breadcrumb">Leave &gt; View</div>
      <h2>My leave</h2>
      <p class="subtitle">Balances, pending requests, and full history. To request time off, use <a href="{{ route('employee.leave.apply') }}">Apply for leave</a>.</p>

      @include('employee.leave._subnav', ['leaveActiveTab' => 'view'])

      @if(session('success'))
        <div class="notice success">{{ session('success') }}</div>
      @endif
      @if($errors->any())
        <div class="notice error">{{ $errors->first() }}</div>
      @endif

      <div class="kpi-grid">
        <div class="kpi">
          <div class="kpi-label">Leave Requests</div>
          <div class="kpi-value">{{ $summary['total'] ?? 0 }}</div>
          <div class="kpi-sub">All time</div>
        </div>
        <div class="kpi">
          <div class="kpi-label">Pending Requests</div>
          <div class="kpi-value">{{ $summary['pending'] ?? 0 }}</div>
          @if(($summary['pending'] ?? 0) > 0)
            <div class="kpi-sub">Awaiting approval</div>
          @endif
        </div>
        <div class="kpi">
          <div class="kpi-label">Approved</div>
          <div class="kpi-value">{{ $summary['approved'] ?? 0 }}</div>
        </div>
      </div>

      <div class="card">
        <div class="card-title" style="margin-bottom:10px;">View leave balance</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Leave Description</th>
                <th>Entitlement</th>
                <th>Utilisation</th>
                <th>Balance Pending</th>
              </tr>
            </thead>
            <tbody>
              @forelse($balances as $bal)
                <tr>
                  <td>{{ $bal['name'] }}</td>
                  <td>{{ $bal['total'] }} day{{ (int) $bal['total'] === 1 ? '' : 's' }}</td>
                  <td>{{ $bal['used'] }} day{{ (int) $bal['used'] === 1 ? '' : 's' }}</td>
                  <td>{{ $bal['remaining'] }} day{{ (int) $bal['remaining'] === 1 ? '' : 's' }} <span class="muted">(Pending: {{ $bal['pending'] }})</span></td>
                </tr>
              @empty
                <tr><td colspan="4" style="text-align:center; color:#94a3b8;">No leave balance data.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @if(isset($pendingRequests) && $pendingRequests->isNotEmpty())
      <div class="card pending-strip">
        <div class="card-title" style="margin-bottom:8px;"><i class="fa-solid fa-clock"></i> Pending requests</div>
        <ul class="pending-list">
          @foreach($pendingRequests as $req)
            <li>{{ $req->leaveType->leave_name ?? 'Leave' }}: {{ $req->start_date?->format('M j') }} – {{ $req->end_date?->format('M j, Y') }} ({{ $req->total_days }} day{{ $req->total_days === 1 ? '' : 's' }})</li>
          @endforeach
        </ul>
      </div>
      @endif

      <div class="card">
        <div class="card-title" style="margin-bottom:10px;">Request history</div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date Range</th>
                <th>Type</th>
                <th>Days</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Submitted</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($requests as $request)
                <tr>
                  <td>{{ $request->start_date?->format('Y-m-d') }} → {{ $request->end_date?->format('Y-m-d') }}</td>
                  <td>{{ $request->leaveType->leave_name ?? 'N/A' }}</td>
                  <td>{{ $request->total_days }} {{ $request->total_days == 1 ? 'day' : 'days' }}</td>
                  <td>
                    <span class="status {{ $request->leave_status }}">{{ $request->getStatusLabel() }}</span>
                    @if($request->leave_status === 'rejected' && $request->reject_reason)
                      <div class="muted">Reason: {{ $request->reject_reason }}</div>
                    @endif
                  </td>
                  <td>{{ $request->reason ?? 'N/A' }}</td>
                  <td>{{ $request->created_at?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                  <td>
                    @if($request->leave_status === \App\Models\LeaveRequest::STATUS_PENDING)
                      <form method="POST" action="{{ route('employee.leave.cancel', $request) }}" onsubmit="return confirm('Cancel this pending request?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-small" style="background:#ef4444;border-color:#ef4444;">Cancel</button>
                      </form>
                    @else
                      <span class="muted">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align:center; color:#94a3b8;">No leave requests yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
