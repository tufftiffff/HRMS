  <style>
    body { background:#f6f8fb; }
    main { padding:28px; }
    .breadcrumb { font-size:.9rem; color:#94a3b8; margin-bottom:.4rem; letter-spacing:.01em; }
    h2 { color:#16a34a; margin:0 0 .2rem 0; }
    .subtitle { color:#64748b; margin:0 0 1.2rem 0; }
    .leave-subnav { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
    .leave-subnav-link { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px; font-weight:600; font-size:0.95rem; text-decoration:none; border:1px solid #d1d5db; background:#fff; color:#334155; transition:background .15s, border-color .15s, color .15s; }
    .leave-subnav-link:hover { background:#f8fafc; border-color:#94a3b8; color:#0f172a; }
    .leave-subnav-link.is-active { background:#2563eb; border-color:#2563eb; color:#fff; box-shadow:0 8px 20px rgba(37,99,235,0.2); }
    .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:14px; margin-bottom:18px; }
    .kpi { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; box-shadow:0 10px 25px rgba(15,23,42,.06); }
    .kpi-label { font-size:12px; letter-spacing:.02em; text-transform:uppercase; color:#94a3b8; }
    .kpi-value { font-size:28px; font-weight:700; color:#0f172a; }
    .kpi-sub { color:#94a3b8; font-size:.9rem; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:16px; box-shadow:0 10px 25px rgba(15,23,42,.06); margin-bottom:16px; }
    .card header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
    .card-title { font-weight:700; color:#0f172a; letter-spacing:.01em; }
    label { font-weight:600; color:#0f172a; font-size:0.95rem; display:block; margin-bottom:6px; }
    input, select, textarea { width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:10px; font-size:0.95rem; background:#fff; }
    textarea { min-height:120px; resize:vertical; }
    .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:12px; }
    .muted { color:#64748b; font-size:0.9rem; }
    .error { color:#b91c1c; font-size:0.9rem; margin-top:6px; min-height:16px; }
    .btn { display:inline-flex; gap:8px; align-items:center; padding:10px 14px; border-radius:10px; border:1px solid transparent; cursor:pointer; font-weight:600; text-decoration:none; }
    .btn-primary { background:#2563eb; color:#fff; border-color:#2563eb; box-shadow:0 10px 20px rgba(37,99,235,0.18); }
    .btn-primary:disabled { opacity:.6; cursor:not-allowed; }
    .status { padding:4px 10px; border-radius:999px; font-size:0.85rem; font-weight:700; display:inline-block; }
    .pending, .supervisor_approved, .pending_admin { background:#fef9c3; color:#854d0e; }
    .approved { background:#dcfce7; color:#166534; }
    .rejected, .cancelled { background:#fee2e2; color:#991b1b; }
    .table-wrap { overflow-x:auto; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:12px 14px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead { background:#0f172a; color:#22c55e; }
    .pill { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px; background:#ecfdf3; color:#15803d; font-weight:600; font-size:0.9rem; }
    .pending-strip { margin-bottom:16px; }
    .pending-list { margin:0; padding-left:20px; color:#475569; font-size:0.9rem; }
    .pending-list li { margin-bottom:4px; }
  </style>
