<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OT Claims Process - Admin - HRMS</title>
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
      <div class="breadcrumb">Payroll / OT Claims / Process</div>
      <h2 style="margin:0 0 4px;">OT Claims - Process</h2>
      <p style="margin:0; color:#64748b;">Approve or reject claims pending your decision. Re-enter your password when approving.</p>

      <nav class="ot-split-nav" aria-label="OT claims sections">
        <a href="{{ route('admin.payroll.overtime_claims') }}" class="active"><i class="fa-solid fa-inbox"></i> Process claims</a>
        <a href="{{ route('admin.payroll.overtime_claims.history') }}"><i class="fa-solid fa-clock-rotate-left"></i> View history</a>
      </nav>

      @include('admin.partials.ot_claims_pending_body')
    </main>
  </div>

  @include('admin.partials.ot_claims_pending_modals_script')
</body>
</html>
