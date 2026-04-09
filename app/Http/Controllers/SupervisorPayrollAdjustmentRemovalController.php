<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\PayrollAdjustmentRemovalRequest;
use App\Models\PayrollLineItem;
use App\Models\PayrollPeriod;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorPayrollAdjustmentRemovalController extends Controller
{
    protected function supervisorOwnsRequest(PayrollAdjustmentRemovalRequest $removal): bool
    {
        return (int) $removal->supervisor_id === (int) Auth::id();
    }

    protected function periodOptions()
    {
        return collect(range(-11, 2))
            ->map(fn ($i) => now()->startOfMonth()->addMonths($i)->format('Y-m'))
            ->sortDesc()
            ->values();
    }

    protected function parseAdjustmentDescription(PayrollLineItem $item): array
    {
        $desc = (string) ($item->description ?? '');
        $colon = strpos($desc, ': ');
        $subType = $colon !== false ? substr($desc, 0, $colon) : 'Adjustment';
        $reason = $colon !== false ? trim(substr($desc, $colon + 2)) : $desc;

        return [$subType, $reason];
    }

    protected function subordinateEmployeeIds(): array
    {
        $supervisorEmp = Employee::where('user_id', Auth::id())->first();
        if (! $supervisorEmp) {
            return [];
        }

        return $supervisorEmp->subordinates()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
    }

    public function index(Request $request)
    {
        $userId = Auth::id();

        $teamRemovalRecent = PayrollAdjustmentRemovalRequest::with([
            'payrollLineItem.payrollRun.period',
            'employee.user',
            'employee.department',
        ])
            ->where('supervisor_id', $userId)
            ->whereHas('employee', fn ($e) => $e->where('user_id', '!=', $userId))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $counts = [
            'pending' => PayrollAdjustmentRemovalRequest::where('supervisor_id', $userId)
                ->where('status', PayrollAdjustmentRemovalRequest::STATUS_PENDING_SUPERVISOR)
                ->whereHas('employee', fn ($e) => $e->where('user_id', '!=', $userId))
                ->count(),
            'submitted' => PayrollAdjustmentRemovalRequest::where('supervisor_id', $userId)
                ->where('status', PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN)
                ->whereHas('employee', fn ($e) => $e->where('user_id', '!=', $userId))
                ->count(),
            'rejected' => PayrollAdjustmentRemovalRequest::where('supervisor_id', $userId)
                ->where('status', PayrollAdjustmentRemovalRequest::STATUS_REJECTED_SUPERVISOR)
                ->whereHas('employee', fn ($e) => $e->where('user_id', '!=', $userId))
                ->count(),
        ];

        $departments = Department::orderBy('department_name')->get();

        return view('supervisor.payroll_adjustment_removal_requests', [
            'teamRemovalRecent' => $teamRemovalRecent,
            'counts'            => $counts,
            'departments'       => $departments,
            'periodOptions'     => $this->periodOptions(),
            'currentPeriod'     => $request->input('period', now()->format('Y-m')),
        ]);
    }

    /**
     * Paginated salary-adjustment deduction lines for direct reports (same month/search UX as employee page).
     */
    public function data(Request $request)
    {
        $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'q'            => ['nullable', 'string', 'max:255'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:10', 'max:100'],
            'department'   => ['nullable', 'integer', 'exists:departments,department_id'],
        ]);

        $employeeIds = $this->subordinateEmployeeIds();
        if ($employeeIds === []) {
            return response()->json([
                'data'       => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => 25,
                    'total'        => 0,
                ],
                'message'    => 'You have no direct reports.',
            ]);
        }

        $periodMonth = $request->input('period_month');
        $period = PayrollPeriod::where('period_month', $periodMonth)->first();

        if (! $period) {
            return response()->json([
                'data'       => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => 25,
                    'total'        => 0,
                ],
                'message'    => 'No payroll generated for this month yet.',
            ]);
        }

        $query = PayrollLineItem::query()
            ->where('code', 'ADJUSTMENT')
            ->where('item_type', 'DEDUCTION')
            ->whereHas('payrollRun', function ($q) use ($period, $employeeIds, $request) {
                $q->where('payroll_period_id', $period->period_id)
                    ->whereIn('employee_id', $employeeIds);
                if ($request->filled('department')) {
                    $deptId = (int) $request->input('department');
                    $q->whereHas('employee', fn ($e) => $e->where('department_id', $deptId));
                }
            })
            ->with([
                'payrollRun.period',
                'payrollRun.employee.user',
                'payrollRun.employee.department',
            ])
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('payrollRun.employee', function ($e) use ($search) {
                        $e->where('employee_code', 'like', "%{$search}%")
                            ->orWhere('employee_id', $search);
                    })
                    ->orWhereHas('payrollRun.employee.user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $paginator = $query->paginate($perPage);

        $lineIds = $paginator->getCollection()->pluck('id')->filter()->all();
        $activeByLine = [];
        if ($lineIds !== []) {
            $rows = PayrollAdjustmentRemovalRequest::query()
                ->whereIn('payroll_line_item_id', $lineIds)
                ->whereNotIn('status', PayrollAdjustmentRemovalRequest::terminalStatuses())
                ->orderByDesc('id')
                ->get();
            foreach ($rows as $r) {
                $lid = $r->payroll_line_item_id;
                if ($lid && ! isset($activeByLine[$lid])) {
                    $activeByLine[$lid] = $r;
                }
            }
        }

        $data = $paginator->getCollection()->map(function (PayrollLineItem $item) use ($activeByLine) {
            [$subType, $reason] = $this->parseAdjustmentDescription($item);
            $run = $item->payrollRun;
            $emp = $run?->employee;
            $active = $activeByLine[$item->id] ?? null;

            return [
                'id'              => $item->id,
                'period_month'    => $run?->period?->period_month ?? '—',
                'employee_name'   => $emp?->user?->name ?? '—',
                'employee_code'   => $emp?->employee_code ?? '—',
                'category'        => 'Deduction',
                'sub_type'        => $subType,
                'reason'          => $reason,
                'amount'          => round((float) $item->amount, 2),
                'amount_signed'   => -round((float) $item->amount, 2),
                'recorded_at'     => $item->created_at?->format('M j, Y g:i A') ?? '—',
                'removal_request' => $active ? [
                    'id'     => $active->id,
                    'status' => $active->status,
                ] : null,
            ];
        });

        return response()->json([
            'data'       => $data,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Supervisor submits a removal request for a direct report’s deduction line; forwards straight to HR admin.
     */
    public function store(Request $request, PayrollLineItem $lineItem)
    {
        $supervisorEmp = Employee::where('user_id', Auth::id())->first();
        abort_unless($supervisorEmp, 403, 'Supervisor employee record not found.');

        $run = $lineItem->payrollRun;
        abort_unless($run, 404);
        $employee = $run->employee;
        abort_unless($employee, 404);

        $subIds = $this->subordinateEmployeeIds();
        abort_unless(in_array((int) $employee->employee_id, $subIds, true), 403, 'You can only request removal for your direct reports.');

        abort_unless($lineItem->code === 'ADJUSTMENT' && $lineItem->item_type === 'DEDUCTION', 422, 'Only salary adjustment deductions can be appealed.');

        $existing = PayrollAdjustmentRemovalRequest::where('payroll_line_item_id', $lineItem->id)
            ->whereNotIn('status', PayrollAdjustmentRemovalRequest::terminalStatuses())
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return redirect()->route('supervisor.attendance.payroll_adjustment_removal.index')
                ->with('error', 'A removal request is already in progress for this deduction.');
        }

        $data = $request->validate([
            'request_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'attachment'     => ['nullable', 'file', 'max:5120'],
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('payroll_adjustment_removal_requests', 'public');
        }

        [$subType, $reason] = $this->parseAdjustmentDescription($lineItem);
        $periodMonth = $run->period?->period_month ?? now()->format('Y-m');
        $supervisorUserId = (int) Auth::id();

        $removal = PayrollAdjustmentRemovalRequest::create([
            'payroll_line_item_id'   => $lineItem->id,
            'employee_id'            => $employee->employee_id,
            'supervisor_id'          => $supervisorUserId,
            'period_month'           => $periodMonth,
            'amount_snapshot'        => round((float) $lineItem->amount, 2),
            'reason_snapshot'        => $reason,
            'sub_type_snapshot'      => $subType,
            'request_reason'         => $data['request_reason'],
            'employee_note'          => null,
            'attachment_path'        => $path,
            'status'                 => PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN,
            'submitted_at'           => now(),
            'supervisor_reviewed_at' => now(),
            'supervisor_note'        => 'Submitted by supervisor on behalf of employee; sent to HR admin.',
        ]);

        AuditLogService::log(
            AuditLogService::CATEGORY_PROFILE,
            'payroll_adjustment_removal_forwarded_to_admin',
            AuditLogService::STATUS_SUCCESS,
            'Supervisor submitted salary adjustment deduction removal for direct report; sent to admin.',
            [
                'removal_request_id'   => $removal->id,
                'payroll_line_item_id' => $lineItem->id,
                'employee_id'          => $employee->employee_id,
            ],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PayrollAdjustmentRemovalRequest',
            $removal->id
        );

        return redirect()->route('supervisor.attendance.payroll_adjustment_removal.index')
            ->with('success', 'Removal request sent to HR admin for final decision.');
    }

    public function approve(Request $request, PayrollAdjustmentRemovalRequest $removal)
    {
        if (! $this->supervisorOwnsRequest($removal)) {
            abort(403, 'Not authorized to review this request.');
        }
        if ($removal->status !== PayrollAdjustmentRemovalRequest::STATUS_PENDING_SUPERVISOR) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $validated = $request->validate([
            'supervisor_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $supervisorComment = isset($validated['supervisor_note']) ? trim($validated['supervisor_note']) : '';
        $supervisorComment = $supervisorComment === '' ? null : $supervisorComment;

        $removal->update([
            'status'                 => PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN,
            'supervisor_note'        => $supervisorComment,
            'supervisor_reviewed_at' => now(),
            'submitted_at'           => $removal->submitted_at ?? now(),
        ]);

        AuditLogService::log(
            AuditLogService::CATEGORY_PROFILE,
            'payroll_adjustment_removal_forwarded_to_admin',
            AuditLogService::STATUS_SUCCESS,
            'Supervisor forwarded salary adjustment deduction removal request to admin.',
            ['removal_request_id' => $removal->id, 'payroll_line_item_id' => $removal->payroll_line_item_id, 'employee_id' => $removal->employee_id],
            $removal->employee_id,
            AuditLogService::SEVERITY_INFO,
            'PayrollAdjustmentRemovalRequest',
            $removal->id
        );

        return back()->with('success', 'Request forwarded to admin for final decision.');
    }

    public function reject(Request $request, PayrollAdjustmentRemovalRequest $removal)
    {
        if (! $this->supervisorOwnsRequest($removal)) {
            abort(403, 'Not authorized to review this request.');
        }
        if ($removal->status !== PayrollAdjustmentRemovalRequest::STATUS_PENDING_SUPERVISOR) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $validated = $request->validate([
            'supervisor_note' => ['required', 'string', 'max:2000'],
        ]);
        $supervisorComment = trim($validated['supervisor_note']);

        $removal->update([
            'status'                 => PayrollAdjustmentRemovalRequest::STATUS_REJECTED_SUPERVISOR,
            'supervisor_note'        => $supervisorComment,
            'supervisor_reviewed_at' => now(),
            'final_decision_at'      => now(),
        ]);

        return back()->with('success', 'Request rejected.');
    }
}
