<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\PayrollAdjustmentRemovalRequest;
use App\Models\PayrollLineItem;
use App\Services\AuditLogService;
use App\Services\PayrollAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPayrollAdjustmentRemovalController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status'     => ['nullable', 'string', 'in:' . implode(',', [
                PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN,
                PayrollAdjustmentRemovalRequest::STATUS_APPROVED_ADMIN,
                PayrollAdjustmentRemovalRequest::STATUS_REJECTED_ADMIN,
            ])],
            'q'          => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'integer', 'exists:departments,department_id'],
            'reason'     => ['nullable', 'string', 'max:2000'],
        ]);

        $status = $request->input('status', PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN);
        $departments = Department::orderBy('department_name')->get();

        $query = PayrollAdjustmentRemovalRequest::with([
            'payrollLineItem.payrollRun.period',
            'employee.user',
            'employee.department',
            'supervisor',
            'admin',
        ])->whereIn('status', [
            PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN,
            PayrollAdjustmentRemovalRequest::STATUS_APPROVED_ADMIN,
            PayrollAdjustmentRemovalRequest::STATUS_REJECTED_ADMIN,
        ]);

        if ($status) {
            $query->where('status', $status);
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->whereHas('employee', function ($e) use ($search) {
                    $e->where('employee_code', 'like', "%{$search}%")
                        ->orWhere('employee_id', $search);
                })->orWhereHas('employee.user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('department')) {
            $deptId = (int) $request->input('department');
            $query->whereHas('employee', fn ($e) => $e->where('department_id', $deptId));
        }

        if ($request->filled('reason')) {
            $reason = (string) $request->input('reason');
            $query->where('request_reason', 'like', "%{$reason}%");
        }

        $query->orderByDesc('submitted_at')->orderByDesc('id');

        $requests = $query->paginate(25)->withQueryString();

        $counts = [
            'pending'  => PayrollAdjustmentRemovalRequest::where('status', PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN)->count(),
            'approved' => PayrollAdjustmentRemovalRequest::where('status', PayrollAdjustmentRemovalRequest::STATUS_APPROVED_ADMIN)->count(),
            'rejected' => PayrollAdjustmentRemovalRequest::where('status', PayrollAdjustmentRemovalRequest::STATUS_REJECTED_ADMIN)->count(),
        ];

        return view('admin.payroll_adjustment_removal_requests', compact('requests', 'counts', 'status', 'departments'));
    }

    public function approve(Request $request, PayrollAdjustmentRemovalRequest $removal)
    {
        if ($removal->status !== PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN) {
            return back()->with('error', 'Only submitted requests can be approved.');
        }

        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $adminComment = isset($validated['admin_note']) ? trim($validated['admin_note']) : '';
        $adminComment = $adminComment === '' ? null : $adminComment;

        $lineRemoved = false;
        $periodLockedNote = null;

        DB::transaction(function () use ($removal, $adminComment, &$lineRemoved, &$periodLockedNote) {
            $item = $removal->payroll_line_item_id
                ? PayrollLineItem::query()->find($removal->payroll_line_item_id)
                : null;

            if ($item && $item->code === 'ADJUSTMENT' && $item->item_type === 'DEDUCTION') {
                $run = $item->payrollRun;
                $period = $run?->period;
                $periodStatus = 'OPEN';
                if ($period && Schema::hasColumn('payroll_periods', 'status')) {
                    $periodStatus = (string) ($period->status ?? 'OPEN');
                }

                if ($periodStatus === 'DRAFT') {
                    $meta = [
                        'line_item_id'         => $item->id,
                        'item_type'          => $item->item_type,
                        'amount'             => (float) $item->amount,
                        'description'        => (string) ($item->description ?? ''),
                        'removed_by_user_id' => Auth::id(),
                        'removal_request_id' => $removal->id,
                    ];
                    $item->delete();
                    app(\App\Http\Controllers\AdminSalaryController::class)
                        ->syncPayrollRunTotalsFromAdjustmentLines($run->fresh());
                    $lineRemoved = true;

                    if ($period) {
                        PayrollAudit::log(
                            PayrollAudit::ACTION_ADJUSTMENT_REMOVED,
                            $period->period_month,
                            $period->period_id,
                            (int) $removal->employee_id,
                            $meta,
                            'Payroll correction line removed after approved salary deduction removal request.'
                        );
                    }
                } else {
                    $periodLockedNote = 'Payroll period is not in DRAFT; deduction line was not removed automatically.';
                }
            } else {
                $periodLockedNote = 'Original payroll line is no longer available; totals were not changed.';
            }

            $removal->update([
                'status'            => PayrollAdjustmentRemovalRequest::STATUS_APPROVED_ADMIN,
                'admin_id'          => Auth::id(),
                'admin_note'        => $adminComment,
                'admin_reviewed_at' => now(),
                'final_decision_at' => now(),
            ]);
        });

        AuditLogService::log(
            AuditLogService::CATEGORY_PROFILE,
            'payroll_adjustment_removal_admin_approved',
            AuditLogService::STATUS_SUCCESS,
            $lineRemoved
                ? 'Admin approved salary adjustment deduction removal; line removed and payroll totals updated.'
                : 'Admin approved salary adjustment deduction removal request.',
            [
                'removal_request_id' => $removal->id,
                'employee_id'        => $removal->employee_id,
                'line_removed'       => $lineRemoved,
            ],
            $removal->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PayrollAdjustmentRemovalRequest',
            $removal->id
        );

        $msg = $lineRemoved
            ? 'Request approved. The deduction line was removed and payroll totals were recalculated.'
            : 'Request approved. ' . ($periodLockedNote ?? '');

        return back()->with('success', $msg);
    }

    public function reject(Request $request, PayrollAdjustmentRemovalRequest $removal)
    {
        if ($removal->status !== PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN) {
            return back()->with('error', 'Only submitted requests can be rejected.');
        }

        $validated = $request->validate([
            'admin_note' => ['required', 'string', 'max:2000'],
        ]);
        $adminComment = trim($validated['admin_note']);

        $removal->update([
            'status'            => PayrollAdjustmentRemovalRequest::STATUS_REJECTED_ADMIN,
            'admin_id'          => Auth::id(),
            'admin_note'        => $adminComment,
            'admin_reviewed_at' => now(),
            'final_decision_at' => now(),
        ]);

        AuditLogService::log(
            AuditLogService::CATEGORY_PROFILE,
            'payroll_adjustment_removal_admin_rejected',
            AuditLogService::STATUS_SUCCESS,
            'Admin rejected salary adjustment deduction removal request.',
            ['removal_request_id' => $removal->id, 'employee_id' => $removal->employee_id],
            $removal->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PayrollAdjustmentRemovalRequest',
            $removal->id
        );

        return back()->with('success', 'Request rejected.');
    }
}
