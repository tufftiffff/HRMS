<style>
    main { padding:24px; }
    .card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; }
    .tabs { display:flex; gap:4px; margin-bottom:16px; border-bottom:1px solid #e5e7eb; }
    .tabs a { padding:10px 16px; text-decoration:none; color:#64748b; font-weight:500; border-bottom:2px solid transparent; margin-bottom:-1px; }
    .tabs a:hover { color:#0f172a; }
    .tabs a.active { color:#0ea5e9; border-bottom-color:#0ea5e9; }
    .tabs .badge { font-size:11px; padding:2px 6px; border-radius:999px; background:#e2e8f0; color:#475569; margin-left:6px; }
    .ot-split-nav { display:flex; flex-wrap:wrap; gap:10px; margin:0 0 16px; align-items:center; }
    .ot-split-nav a {
      padding:10px 18px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none;
      border:1px solid #e2e8f0; color:#475569; background:#f8fafc;
    }
    .ot-split-nav a:hover { background:#f1f5f9; color:#0f172a; }
    .ot-split-nav a.active { background:#4f46e5; color:#fff; border-color:#4f46e5; }
    .toolbar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .toolbar input, .toolbar select { padding:6px 10px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    th, td { padding:8px 10px; border-bottom:1px solid #e5e7eb; text-align:left; }
    thead th { background:#0f172a; color:#e2e8f0; font-weight:500; }
    .btn-sm { padding:8px 14px; font-size:12px; border-radius:8px; border:none; cursor:pointer; margin:0 2px; display:inline-flex; align-items:center; gap:6px; }
    .btn-approve { background:#16a34a; color:#fff; }
    .btn-reject { background:#dc2626; color:#fff; }
    .btn-hold { background:#f59e0b; color:#fff; }
    .btn-outline { background:#fff; border:1px solid #e5e7eb; color:#374151; }
    tr.row-no-proof { background:#fef2f2 !important; }
    .overlay { position:fixed; inset:0; background:rgba(15,23,42,0.5); display:none; align-items:center; justify-content:center; z-index:1000; }
    .overlay.open { display:flex; }
    .panel { width:100%; max-width:560px; max-height:90vh; overflow:auto; background:#fff; border-radius:16px; box-shadow:0 24px 48px rgba(0,0,0,0.18); padding:0; }
    .panel-header { padding:20px 24px 16px; border-bottom:1px solid #e5e7eb; }
    .panel-header h3 { margin:0; font-size:1.2rem; font-weight:600; color:#0f172a; }
    .panel-body { padding:20px 24px; font-size:13px; }
    .modal-field { margin-bottom:12px; }
    .modal-field label { display:block; margin-bottom:6px; font-size:12px; font-weight:600; color:#374151; }
    .modal-field input[type="password"],
    .modal-field textarea { width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:8px; font-size:13px; font-family:inherit; resize:vertical; min-height:44px; }
    .modal-field textarea { min-height:72px; }
    .panel-footer { padding:16px 24px 20px; display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; border-top:1px solid #e5e7eb; background:#fafafa; border-radius:0 0 16px 16px; }
    .panel-footer .btn-sm { min-width:100px; justify-content:center; }
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
    .ot-summary-card.on-hold { background:#fef3c7; color:#92400e; }
    .ot-summary-card.on-hold .num { color:#b45309; }
    .card .section-title { margin:0 0 12px; font-size:1.05rem; font-weight:600; color:#0f172a; display:flex; align-items:center; gap:8px; }
    .card .section-title i { color:#6366f1; opacity:0.9; }
    .empty-state { text-align:center; padding:32px 24px; color:#94a3b8; font-size:13px; background:#f8fafc; border-radius:12px; margin-top:8px; border:1px dashed #e2e8f0; }
    .empty-state i { font-size:28px; margin-bottom:8px; opacity:0.6; display:block; }
    .table-wrap { overflow-x:auto; border-radius:12px; border:1px solid #e2e8f0; margin-top:8px; }
    .ot-table { width:100%; border-collapse:collapse; font-size:13px; background:#fff; }
    .ot-table thead th { background:linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%); color:#475569; font-weight:600; padding:12px 14px; font-size:12px; text-transform:uppercase; letter-spacing:0.04em; border-bottom:2px solid #e2e8f0; }
    .ot-table tbody td { padding:12px 14px; vertical-align:middle; color:#334155; border-bottom:1px solid #f1f5f9; }
    .employee-cell strong { display:block; font-weight:600; color:#0f172a; }
    .employee-meta { font-size:11px; color:#6b7280; }
    .ot-when-cell { font-size:13px; color:#334155; line-height:1.35; }
    .ot-when-cell .ot-when-block { margin-top:6px; }
    .ot-when-cell .ot-when-block:first-child { margin-top:0; }
    .ot-when-cell .ot-when-label { display:block; font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8; margin-bottom:2px; }
    .ot-when-cell .ot-when-value { font-weight:600; color:#0f172a; }
    .reason-text { font-size:12px; color:#64748b; max-width:320px; }
    .ot-table .comment-cell { max-width:200px; vertical-align:top; }
    .ot-table .comment-cell .comment-preview { display:block; white-space:pre-wrap; word-break:break-word; font-size:12px; color:#334155; line-height:1.35; }
    .progress-badge { padding:4px 10px; border-radius:999px; font-size:11px; font-weight:600; background:#e0e7ff; color:#4338ca; }
    .status-badge { padding:3px 8px; border-radius:999px; font-size:11px; font-weight:600; }
    .status-info-icon { margin-left:6px; color:#2563eb; font-size:12px; cursor:help; font-weight:700; }
    .status-approved { background:#dcfce7; color:#166534; }
    .status-rejected { background:#fee2e2; color:#991b1b; }
    .status-hold { background:#fef3c7; color:#92400e; }
    .status-supervisor-direct { background:#ede9fe; color:#5b21b6; border:1px solid #c4b5fd; }
    tr.row-supervisor-direct { background:#f5f3ff; }
    tr.row-supervisor-direct td:first-child { border-left:4px solid #8b5cf6; }
    .claim-review-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:10px 14px; margin-bottom:12px; }
    .claim-review-grid .label { display:block; font-size:11px; color:#64748b; text-transform:uppercase; font-weight:600; letter-spacing:0.04em; margin-bottom:2px; }
    .claim-review-grid .value { color:#0f172a; font-size:13px; word-break:break-word; }
    .claim-review-block { margin-bottom:12px; }
    .claim-review-block .label { display:block; font-size:11px; color:#64748b; text-transform:uppercase; font-weight:600; letter-spacing:0.04em; margin-bottom:3px; }
    .claim-review-block .value { color:#0f172a; font-size:13px; white-space:pre-wrap; word-break:break-word; }
    .warning-box { border:1px solid #fde68a; background:#fffbeb; color:#92400e; border-radius:10px; padding:10px 12px; margin-bottom:10px; font-size:13px; }
    .bulk-actions { display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-bottom:12px; font-size:14px; padding:10px 0; }
    .bulk-actions .ot-selected-count { color:#6b7280; font-weight:500; }
    .bulk-actions .btn-ot-approve { padding:10px 20px; border-radius:999px; border:none; cursor:pointer; font-size:14px; font-weight:700; color:#fff; background:#22c55e; font-family:inherit; }
    .bulk-actions .btn-ot-approve:hover { background:#16a34a; }
    .bulk-actions .btn-ot-reject { padding:10px 20px; border-radius:999px; border:none; cursor:pointer; font-size:14px; font-weight:700; color:#fff; background:#f87171; font-family:inherit; }
    .bulk-actions .btn-ot-reject:hover { background:#ef4444; }
    table input[type="checkbox"]#admin-ot-select-all,
    table input[type="checkbox"].admin-ot-row-check { transform:scale(1.6); cursor:pointer; accent-color:#6366f1; }
    .tabs a.active { color:#6366f1; border-bottom-color:#6366f1; }
    .pagination-wrap { margin-top:16px; font-size:13px; }
</style>
