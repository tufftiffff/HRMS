<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesSupervisorUserId;
use App\Models\Employee;
use App\Models\PayrollAdjustmentRemovalRequest;
use App\Models\PayrollLineItem;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeePayrollAdjustmentRemovalController extends Controller
{
    use ResolvesSupervisorUserId;

    protected function parseAdjustmentDescription(PayrollLineItem $item): array
    {
        $desc = (string) ($item->description ?? '');
        $colon = strpos($desc, ': ');
        $subType = $colon !== false ? substr($desc, 0, $colon) : 'Adjustment';
        $reason = $colon !== false ? trim(substr($desc, $colon + 2)) : $desc;

        return [$subType, $reason];
    }

    protected function periodOptions()
    {
        return collect(range(-11, 2))
            ->map(fn ($i) => now()->startOfMonth()->addMonths($i)->format('Y-m'))
            ->sortDesc()
            ->values();
    }

    public function index()
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee record not found.');
        }

        $myRequests = PayrollAdjustmentRemovalRequest::query()
            ->where('employee_id', $employee->employee_id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('employee.payroll_adjustment_removal', [
            'periodOptions' => $this->periodOptions(),
            'currentPeriod' => request('period', now()->format('Y-m')),
            'myRequests'    => $myRequests,
        ]);
    }

    public function data(Request $request)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return response()->json(['message' => 'Employee record not found.'], 403);
        }

        $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'q'            => ['nullable', 'string', 'max:255'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

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
            ->whereHas('payrollRun', function ($q) use ($period, $employee) {
                $q->where('payroll_period_id', $period->period_id)
                    ->where('employee_id', $employee->employee_id);
            })
            ->with(['payrollRun.period'])
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
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
            $active = $activeByLine[$item->id] ?? null;

            return [
                'id'              => $item->id,
                'period_month'    => $run?->period?->period_month ?? '—',
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

    public function store(Request $request, PayrollLineItem $lineItem)
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee, 403, 'Employee profile not found');

        $run = $lineItem->payrollRun;
        abort_unless($run && (int) $run->employee_id === (int) $employee->employee_id, 403, 'Not allowed');
        abort_unless($lineItem->code === 'ADJUSTMENT' && $lineItem->item_type === 'DEDUCTION', 422, 'Only salary adjustment deductions can be appealed.');

        $existing = PayrollAdjustmentRemovalRequest::where('payroll_line_item_id', $lineItem->id)
            ->whereNotIn('status', PayrollAdjustmentRemovalRequest::terminalStatuses())
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return redirect()->route('employee.attendance.payroll_adjustment_removal.index')
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

        $employee->loadMissing(['supervisor', 'department']);

        [$subType, $reason] = $this->parseAdjustmentDescription($lineItem);
        $periodMonth = $run->period?->period_month ?? now()->format('Y-m');

        $supervisorUserId = $this->resolveSupervisorUserIdForEmployee($employee);
        $user = Auth::user();
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isSupervisorRequester = $role === 'supervisor';
        $autoForwardToAdmin = $isSupervisorRequester || $supervisorUserId === null;

        PayrollAdjustmentRemovalRequest::create([
            'payroll_line_item_id' => $lineItem->id,
            'employee_id'          => $employee->employee_id,
            'supervisor_id'        => $isSupervisorRequester
                ? ($user?->user_id ?? Auth::id())
                : $supervisorUserId,
            'period_month'         => $periodMonth,
            'amount_snapshot'      => round((float) $lineItem->amount, 2),
            'reason_snapshot'      => $reason,
            'sub_type_snapshot'    => $subType,
            'request_reason'       => $data['request_reason'],
            'employee_note'        => null,
            'attachment_path'      => $path,
            'status'               => $autoForwardToAdmin
                ? PayrollAdjustmentRemovalRequest::STATUS_PENDING_ADMIN
                : PayrollAdjustmentRemovalRequest::STATUS_PENDING_SUPERVISOR,
            'submitted_at'         => now(),
            'supervisor_reviewed_at' => $autoForwardToAdmin ? now() : null,
            'supervisor_note'      => $autoForwardToAdmin
                ? ($isSupervisorRequester
                    ? 'Auto-forwarded (requester is supervisor)'
                    : 'Auto-forwarded (no supervisor assigned)')
                : null,
        ]);

        return redirect()->route('employee.attendance.payroll_adjustment_removal.index')
            ->with('success', $autoForwardToAdmin
                ? ($isSupervisorRequester
                    ? 'Removal request sent to HR admin for review.'
                    : 'Removal request submitted. Pending admin review.')
                : 'Removal request submitted. Pending supervisor review.');
    }

    public function cancel(PayrollAdjustmentRemovalRequest $removal)
    {
        $employee = Auth::user()?->employee;
        abort_unless($employee, 403, 'Employee profile not found');
        abort_unless((int) $removal->employee_id === (int) $employee->employee_id, 403, 'Not allowed');

        if (in_array($removal->status, PayrollAdjustmentRemovalRequest::terminalStatuses(), true)) {
            return redirect()->route('employee.attendance.payroll_adjustment_removal.index');
        }

        if ($removal->status !== PayrollAdjustmentRemovalRequest::STATUS_PENDING_SUPERVISOR) {
            return redirect()->route('employee.attendance.payroll_adjustment_removal.index')
                ->with('error', 'Only requests pending supervisor review can be cancelled.');
        }

        $removal->status = PayrollAdjustmentRemovalRequest::STATUS_CANCELLED_EMPLOYEE;
        $removal->final_decision_at = now();
        $removal->save();

        return redirect()->route('employee.attendance.payroll_adjustment_removal.index')
            ->with('success', 'Removal request cancelled.');
    }
}
