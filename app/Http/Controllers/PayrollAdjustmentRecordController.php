<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollLineItem;
use App\Models\PayrollPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only payroll correction line items (code ADJUSTMENT) for employees and their supervisors.
 */
class PayrollAdjustmentRecordController extends Controller
{
    protected function periodOptions()
    {
        return collect(range(-11, 2))
            ->map(fn ($i) => now()->startOfMonth()->addMonths($i)->format('Y-m'))
            ->sortDesc()
            ->values();
    }

    protected function parseAdjustmentRow(PayrollLineItem $item): array
    {
        $desc = (string) ($item->description ?? '');
        $colon = strpos($desc, ': ');
        $subType = $colon !== false ? substr($desc, 0, $colon) : 'Adjustment';
        $reason = $colon !== false ? trim(substr($desc, $colon + 2)) : $desc;
        $run = $item->payrollRun;
        $emp = $run?->employee;
        $user = $emp?->user;
        $dept = $emp?->department;
        $isDeduction = strtoupper((string) $item->item_type) === 'DEDUCTION';
        $amount = round((float) $item->amount, 2);

        return [
            'id'              => $item->id,
            'period_month'    => $run?->period?->period_month ?? '—',
            'employee_id'     => $emp?->employee_id,
            'employee_code'   => $emp?->employee_code ?? ($emp ? Employee::codeFallbackFromId($emp->employee_id) : '—'),
            'employee_name'   => $user?->name ?? 'Unknown',
            'department'      => $dept?->department_name ?? 'N/A',
            'category'        => $isDeduction ? 'Deduction' : 'Earning',
            'sub_type'        => $subType,
            'reason'          => $reason,
            'amount'          => $amount,
            'amount_signed'   => $isDeduction ? -$amount : $amount,
            'recorded_at'     => $item->created_at?->format('M j, Y g:i A') ?? '—',
        ];
    }

    public function employeeIndex()
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return redirect()->route('employee.dashboard')
                ->with('error', 'Employee record not found.');
        }

        return view('employee.payroll_salary_adjustments', [
            'scope'           => 'self',
            'periodOptions'   => $this->periodOptions(),
            'currentPeriod'   => request('period', now()->format('Y-m')),
            'subordinates'    => collect(),
        ]);
    }

    public function employeeData(Request $request)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return response()->json(['message' => 'Employee record not found.'], 403);
        }

        return $this->adjustmentData($request, [$employee->employee_id]);
    }

    public function teamIndex()
    {
        $role = strtolower(trim((string) (Auth::user()->role ?? '')));
        if ($role !== 'supervisor') {
            abort(403, 'Only supervisors can view team salary adjustments.');
        }

        $supervisorEmp = Employee::where('user_id', Auth::id())->first();
        $subordinates = $supervisorEmp
            ? $supervisorEmp->subordinates()->with('user')->orderBy('employee_id')->get()
            : collect();

        return view('employee.payroll_salary_adjustments', [
            'scope'           => 'team',
            'periodOptions'   => $this->periodOptions(),
            'currentPeriod'   => request('period', now()->format('Y-m')),
            'subordinates'    => $subordinates,
        ]);
    }

    public function teamData(Request $request)
    {
        $role = strtolower(trim((string) (Auth::user()->role ?? '')));
        if ($role !== 'supervisor') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $supervisorEmp = Employee::where('user_id', Auth::id())->first();
        if (! $supervisorEmp) {
            return response()->json(['message' => 'Supervisor employee record not found.'], 403);
        }

        $ids = $supervisorEmp->subordinates()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return response()->json([
                'data'         => [],
                'pagination'   => [
                    'current_page' => 1,
                    'last_page'    => 1,
                    'per_page'     => 25,
                    'total'        => 0,
                ],
                'message'      => 'You have no direct reports.',
            ]);
        }

        if ($request->filled('employee_id')) {
            $filterId = (int) $request->input('employee_id');
            if (! in_array($filterId, $ids, true)) {
                return response()->json(['message' => 'Invalid employee.'], 403);
            }
            $ids = [$filterId];
        }

        return $this->adjustmentData($request, $ids);
    }

    public function employeeAdjustmentDetail(Request $request)
    {
        $employee = Employee::where('user_id', Auth::id())->first();
        if (! $employee) {
            return response()->json(['message' => 'Employee record not found.'], 403);
        }

        return $this->adjustmentDetailResponse($request, [(int) $employee->employee_id]);
    }

    public function teamAdjustmentDetail(Request $request)
    {
        $role = strtolower(trim((string) (Auth::user()->role ?? '')));
        if ($role !== 'supervisor') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        $supervisorEmp = Employee::where('user_id', Auth::id())->first();
        if (! $supervisorEmp) {
            return response()->json(['message' => 'Supervisor employee record not found.'], 403);
        }

        $ids = $supervisorEmp->subordinates()->pluck('employee_id')->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return response()->json(['message' => 'You have no direct reports.'], 403);
        }

        return $this->adjustmentDetailResponse($request, $ids);
    }

    /**
     * JSON detail for one ADJUSTMENT line only (own employee or team subordinate).
     * Works for DRAFT and released (LOCKED / PAID / PUBLISHED) periods. No full payslip breakdown.
     */
    protected function adjustmentDetailResponse(Request $request, array $allowedEmployeeIds)
    {
        $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $id = (int) $request->input('id');

        $item = PayrollLineItem::query()
            ->where('id', $id)
            ->where('code', 'ADJUSTMENT')
            ->whereIn('item_type', ['EARNING', 'DEDUCTION'])
            ->whereHas('payrollRun', function ($q) use ($allowedEmployeeIds) {
                $q->whereIn('employee_id', $allowedEmployeeIds);
            })
            ->with([
                'payrollRun.period',
                'payrollRun.employee.user',
            ])
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Adjustment not found.'], 404);
        }

        $run = $item->payrollRun;
        $period = $run?->period;
        $periodStatus = 'OPEN';
        if ($period && Schema::hasColumn('payroll_periods', 'status')) {
            $periodStatus = (string) ($period->status ?? 'OPEN');
        }

        $row = $this->parseAdjustmentRow($item);

        return response()->json([
            'row'    => $row,
            'line'   => [
                'item_type'   => (string) $item->item_type,
                'code'        => (string) $item->code,
                'description' => (string) ($item->description ?? ''),
                'amount'      => round((float) $item->amount, 2),
            ],
            'period' => [
                'month'       => $period?->period_month,
                'status'      => $periodStatus,
                'is_released' => in_array($periodStatus, ['LOCKED', 'PAID', 'PUBLISHED'], true),
            ],
        ]);
    }

    protected function adjustmentData(Request $request, array $employeeIds)
    {
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
                'message'    => 'No payroll generated for this month yet. Adjustments appear after payroll is generated.',
            ]);
        }

        $query = PayrollLineItem::query()
            ->where('code', 'ADJUSTMENT')
            ->whereIn('item_type', ['EARNING', 'DEDUCTION'])
            ->whereHas('payrollRun', function ($q) use ($period, $employeeIds) {
                $q->where('payroll_period_id', $period->period_id)
                    ->whereIn('employee_id', $employeeIds);
            })
            ->with([
                'payrollRun.employee.user',
                'payrollRun.employee.department',
                'payrollRun.period',
            ]);

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('payrollRun.employee', function ($e) use ($search) {
                        $e->where('employee_code', 'like', "%{$search}%")
                            ->orWhere('employee_id', $search);
                    })
                    ->orWhereHas('payrollRun.employee.user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $query->orderByDesc('id');

        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(fn (PayrollLineItem $item) => $this->parseAdjustmentRow($item));

        return response()->json([
            'data'         => $data,
            'pagination'   => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
