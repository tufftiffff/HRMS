<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use App\Models\OvertimeRecord;
use App\Models\Penalty;
use App\Models\Department;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\PayrollLineItem;
use App\Models\LeaveType;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Services\PayrollGenerator;
use App\Services\PayrollAudit;
use App\Events\PayslipsPublished;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AdminSalaryController extends Controller
{
    private function renderSalaryPage(Request $request, string $viewName, string $salarySection, string $adjustmentPage)
    {
        $departments = Department::orderBy('department_name')->get();
        // Build window: last 6 months through next 2 months (includes current)
        $periodOptions = collect(range(-5, 2))
            ->map(fn($i) => now()->startOfMonth()->addMonths($i)->format('Y-m'))
            ->sortDesc()
            ->values();
        $currentPeriod = request('period', now()->format('Y-m'));
        $currentDept = request('dept', '');
        $currentEmployee = request('employee', '');

        $periodRow = PayrollPeriod::where('period_month', $currentPeriod)->first();
        $payrollStatus = $periodRow->status ?? 'OPEN';
        $releaseWindowClosed = self::isReleaseWindowClosed($currentPeriod);
        $lockedByName = $periodRow && $periodRow->locked_by
            ? \App\Models\User::find($periodRow->locked_by)?->name : null;
        $paidByName = $periodRow && $periodRow->paid_by
            ? \App\Models\User::find($periodRow->paid_by)?->name : null;
        $payrollMeta = [
            'created_by'   => $periodRow?->created_by ?? (auth()->user()->name ?? 'HR Admin'),
            'generated_at' => $periodRow?->created_at?->format('M d, Y g:i A') ?? null,
            'locked_by'    => $lockedByName,
            'locked_at'    => optional($periodRow?->locked_at)->format('M d, Y g:i A'),
            'paid_at'      => optional($periodRow?->paid_at)->format('M d, Y g:i A'),
            'published_at' => optional($periodRow?->published_at)->format('M d, Y g:i A'),
        ];

        // Payroll by month (history): each month has its own status; release affects only selected month
        $payrollHistory = PayrollPeriod::orderByDesc('period_month')
            ->limit(24)
            ->get()
            ->map(function ($p) {
                $lockedBy = $p->locked_by ? \App\Models\User::find($p->locked_by)?->name : null;
                return [
                    'period_month'   => $p->period_month,
                    'status'         => $p->status ?? 'OPEN',
                    'generated_at'   => $p->created_at?->format('M d, Y') ?? '--',
                    'released_at'    => $p->locked_at?->format('M d, Y g:i A') ?? '--',
                    'released_by'    => $lockedBy ?? '--',
                ];
            });

        return view($viewName, compact('departments', 'periodOptions', 'currentPeriod', 'currentDept', 'currentEmployee', 'payrollStatus', 'payrollMeta', 'payrollHistory', 'releaseWindowClosed', 'salarySection', 'adjustmentPage'));
    }

    /**
     * Show salary calculation page.
     */
    public function index(Request $request)
    {
        return $this->renderSalaryPage($request, 'admin.payroll_salary', 'calculation', 'payroll');
    }

    public function adjustments(Request $request)
    {
        return $this->renderSalaryPage($request, 'admin.payroll_adjustment', 'adjustment', 'payroll');
    }

    public function basicSalary(Request $request)
    {
        return $this->renderSalaryPage($request, 'admin.payroll_basic_salary', 'adjustment', 'basic');
    }

    /**
     * Return salary calc dataset (lightweight, not persisted).
     */
    public function data(Request $request)
    {
        $request->validate([
            'period'       => ['required', 'date_format:Y-m'],
            'department'   => ['nullable', 'integer', 'exists:departments,department_id'],
            'employee_id'  => ['nullable', 'integer', 'exists:employees,employee_id'],
            'page'         => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $period = $request->input('period');
        $empFilter = $request->input('employee_id');
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString();
        $deptId = $request->input('department');

        // If payroll runs exist for this period, return persisted numbers.
        $periodRow = PayrollPeriod::where('period_month', $period)->first();
        if ($periodRow) {
            $runs = PayrollRun::with(['employee.department', 'employee.user', 'employee.position'])
                ->where('payroll_period_id', $periodRow->period_id)
                ->when($deptId, fn($q) => $q->whereHas('employee', fn($qq) => $qq->where('department_id', $deptId)))
                ->get();

            if ($runs->isNotEmpty()) {
                $generator = app(PayrollGenerator::class);
                $data = $runs->map(function (PayrollRun $run) use ($start, $end, $period, $generator, $periodRow) {
                    $empId = $run->employee_id;
                    $employee = $run->employee;
                    $attendance = Attendance::where('employee_id', $empId)->whereBetween('date', [$start, $end]);
                    $incompletePunches = (float) (clone $attendance)
                        ->where('at_status', 'present')
                        ->where(fn($q) => $q->whereNull('clock_in_time')->orWhereNull('clock_out_time'))
                        ->count();

                    // Live formula (includes no-attendance / rounding rules) so grid matches Salary Details without requiring DB sync from last generate.
                    $computed = $employee
                        ? $generator->computeForEmployee(
                            $employee,
                            $period,
                            (float) $run->adjustment_total,
                            $periodRow->period_id
                        )
                        : null;

                    $empStatus = strtolower(trim((string) ($run->employee->employee_status ?? 'active')));

                    if ($computed === null) {
                        return [
                            'employee_id'=> $run->employee_id,
                            'id'         => Employee::codeFallbackFromId($run->employee_id),
                            'name'       => 'Unknown',
                            'dept'       => 'N/A',
                            'job_title'  => '—',
                            'employee_status' => $empStatus,
                            'base'       => (float) $run->basic_salary,
                            'allow'      => (float) $run->allowance_total,
                            'allowItems' => [],
                            'ot_total'   => (float) $run->ot_total,
                            'penalty'    => (float) $run->penalty_total,
                            'epfTax'     => (float) $run->epf_total,
                            'tax_total'  => (float) $run->tax_total,
                            'unpaid_ded' => (float) $run->unpaid_leave_deduction,
                            'absent_ded' => (float) $run->absent_deduction,
                            'late_ded'   => (float) $run->late_deduction,
                            'absent_days'=> 0.0,
                            'late_minutes'=> 0.0,
                            'unpaid_leave_days'=> 0.0,
                            'incomplete_punches'=> $incompletePunches,
                            'adjustment_total' => (float) $run->adjustment_total,
                            'net'        => (float) $run->net_pay,
                            'gross'      => (float) $run->gross_pay,
                            'status'     => $run->status,
                            'last'       => $run->updated_at,
                        ];
                    }

                    return [
                        'employee_id'=> $run->employee_id,
                        'id'         => $run->employee->employee_code ?? Employee::codeFallbackFromId($run->employee_id),
                        'name'       => $run->employee->user->name ?? 'Unknown',
                        'dept'       => $run->employee->department->department_name ?? 'N/A',
                        'job_title'  => $run->employee->position?->position_name ?? '—',
                        'employee_status' => $empStatus,
                        'base'       => $computed['basic_salary'],
                        'allow'      => $computed['allowance_total'],
                        'allowItems' => [],
                        'ot_total'   => (float) $run->ot_total,
                        'penalty'    => $computed['penalty_total'],
                        'epfTax'     => $computed['epf_total'],
                        'tax_total'  => $computed['tax_total'],
                        'unpaid_ded' => $computed['unpaid_leave_deduction'],
                        'absent_ded' => $computed['absent_deduction'],
                        'late_ded'   => $computed['late_deduction'],
                        'absent_days'=> $computed['absent_days'],
                        'late_minutes'=> $computed['late_minutes'],
                        'unpaid_leave_days'=> $computed['unpaid_leave_days'],
                        'incomplete_punches'=> $incompletePunches,
                        'adjustment_total' => $computed['adjustment_total'],
                        'net'        => $computed['net_pay'],
                        'gross'      => $computed['gross_pay'],
                        'status'     => $run->status,
                        'last'       => $run->updated_at,
                    ];
                });
                $allRows = $data->values();
                $employees = $allRows
                    ->filter(fn ($r) => ($r['employee_status'] ?? 'active') === 'active')
                    ->map(fn ($r) => ['employee_id' => $r['employee_id'], 'id' => $r['id'], 'name' => $r['name']])
                    ->values()
                    ->all();
                $forTable = $empFilter
                    ? $allRows->filter(fn ($r) => (int) $r['employee_id'] === (int) $empFilter)->values()
                    : $allRows;
                $insights = self::computeInsights($forTable);

                return self::paginatedJson($forTable, $request, ['insights' => $insights, 'employees' => $employees]);
            }
        }

        // Fallback: on-the-fly calculation when no persisted runs yet (same formula as PayrollGenerator).
        $employees = Employee::with(['department', 'user', 'position'])
            ->where('employee_status', 'active')
            ->whereDate('hire_date', '<=', $end)
            ->when($deptId, fn($q) => $q->where('department_id', $deptId))
            ->get();

        $generator = app(PayrollGenerator::class);
        $result = $employees->map(function ($emp) use ($period, $generator) {
            $computed = $generator->computeForEmployee($emp, $period, 0.0, $periodRow?->period_id);
            $attendance = Attendance::where('employee_id', $emp->employee_id)
                ->whereBetween('date', [
                    \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString(),
                    \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString(),
                ]);
            $incompletePunches = (float) (clone $attendance)
                ->where('at_status', 'present')
                ->where(fn($q) => $q->whereNull('clock_in_time')->orWhereNull('clock_out_time'))
                ->count();
            return [
                'employee_id'       => $emp->employee_id,
                'id'                => $emp->employee_code ?? Employee::codeFallbackFromId($emp->employee_id),
                'name'              => $emp->user->name ?? 'Unknown',
                'dept'              => $emp->department->department_name ?? 'N/A',
                'job_title'         => $emp->position?->position_name ?? '—',
                'employee_status'   => strtolower(trim((string) ($emp->employee_status ?? 'active'))),
                'base'              => $computed['basic_salary'],
                'allow'             => $computed['allowance_total'],
                'allowItems'        => [],
                'ot_total'          => $computed['allowance_total'],
                'penalty'           => $computed['penalty_total'],
                'epfTax'            => $computed['epf_total'],
                'tax_total'         => $computed['tax_total'],
                'unpaid_ded'        => $computed['unpaid_leave_deduction'],
                'absent_ded'        => $computed['absent_deduction'],
                'late_ded'          => $computed['late_deduction'],
                'absent_days'       => $computed['absent_days'],
                'late_minutes'      => $computed['late_minutes'],
                'unpaid_leave_days' => $computed['unpaid_leave_days'],
                'incomplete_punches'=> $incompletePunches,
                'adjustment_total'  => $computed['adjustment_total'],
                'gross'             => $computed['gross_pay'],
                'net'               => $computed['net_pay'],
                'status'            => 'OPEN',
                'last'              => now()->toDateString(),
            ];
        });

        $resultCollection = collect($result);
        $employees = $resultCollection->map(fn ($r) => ['employee_id' => $r['employee_id'], 'id' => $r['id'], 'name' => $r['name']])->values()->all();
        $forTable = $empFilter
            ? $resultCollection->filter(fn ($r) => (int) $r['employee_id'] === (int) $empFilter)->values()
            : $resultCollection;
        $insights = self::computeInsights($forTable);

        return self::paginatedJson($forTable, $request, ['insights' => $insights, 'employees' => $employees]);
    }

    /**
     * Compute insight counts (absent, late, unpaid, incomplete) from a collection of payroll rows.
     */
    private static function computeInsights($collection): array
    {
        $rows = $collection instanceof \Illuminate\Support\Collection ? $collection : collect($collection);
        return [
            'absent' => $rows->filter(fn($r) => ($r['absent_days'] ?? 0) > 0)->count(),
            'late' => $rows->filter(fn($r) => ($r['late_minutes'] ?? 0) > 0)->count(),
            'unpaid' => $rows->filter(fn($r) => ($r['unpaid_leave_days'] ?? 0) > 0)->count(),
            'incomplete' => $rows->filter(fn($r) => ($r['incomplete_punches'] ?? 0) > 0)->count(),
        ];
    }

    /**
     * Return JSON with data and pagination from a collection.
     * @param array $extra Optional keys to merge into the response (e.g. insights, employees).
     */
    private static function paginatedJson($collection, Request $request, array $extra = []): \Illuminate\Http\JsonResponse
    {
        $collection = $collection instanceof \Illuminate\Support\Collection ? $collection : collect($collection);
        $total = $collection->count();
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));
        $page = max(1, (int) $request->input('page', 1));
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page = min($page, $lastPage);
        $items = $collection->forPage($page, $perPage)->values()->all();
        $payload = [
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
            ],
        ];
        return response()->json(array_merge($payload, $extra));
    }

    /**
     * Pre-release checklist: must pass before Release Payroll (DRAFT → LOCKED).
     */
    public function checklist(Request $request)
    {
        $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
        ]);

        $periodMonth = $request->input('period_month');
        $deptId = $request->input('department_id');
        $errors = [];
        $warnings = [];

        $period = PayrollPeriod::where('period_month', $periodMonth)->first();
        if (!$period) {
            $errors[] = 'Payroll period not found. Generate payroll for this month first.';
            return response()->json(['ok' => false, 'errors' => $errors, 'warnings' => $warnings]);
        }

        if (!in_array($period->status, ['OPEN', 'DRAFT'])) {
            $errors[] = 'Only DRAFT payroll can be released.';
            return response()->json(['ok' => false, 'errors' => $errors, 'warnings' => $warnings]);
        }

        $runs = PayrollRun::with('lineItems')
            ->where('payroll_period_id', $period->period_id)
            ->when($deptId, fn($q) => $q->whereHas('employee', fn($qq) => $qq->where('department_id', $deptId)))
            ->get();

        if ($runs->isEmpty()) {
            $errors[] = 'No payroll records for this period and scope. Generate payroll first.';
            return response()->json(['ok' => false, 'errors' => $errors, 'warnings' => $warnings]);
        }

        $tolerance = 0.02;
        foreach ($runs as $run) {
            // Gross = Basic + Allowance + Adjustments; Deductions = Late + Absent + Unpaid Leave + Penalties + EPF + Tax; Net = Gross - Deductions
            $gross = round((float) $run->basic_salary + (float) $run->allowance_total + (float) $run->adjustment_total, 2);
            $config = config('hrms.payroll', []);
            $epfRate = (float) ($config['epf_employee_rate'] ?? 0.11);
            $taxRate = (float) ($config['tax_rate'] ?? 0.03);
            $statutory = PayrollGenerator::applyAttendanceCapAndStatutory(
                $gross,
                (float) $run->basic_salary,
                (float) $run->late_deduction,
                (float) $run->absent_deduction,
                (float) $run->unpaid_leave_deduction,
                (float) $run->penalty_total,
                $epfRate,
                $taxRate
            );
            $expectedNet = $statutory['net_pay'];
            $actualNet = (float) $run->net_pay;
            if (abs($expectedNet - $actualNet) > $tolerance) {
                $emp = $run->employee;
                $name = $emp && $emp->user ? $emp->user->name : ('Employee ' . $run->employee_id);
                $errors[] = "Totals inconsistent for {$name}: Net should be {$expectedNet}, got {$actualNet}.";
            }

            foreach ($run->lineItems as $item) {
                if ($item->code !== 'ADJUSTMENT') {
                    continue;
                }
                $reason = trim((string) ($item->description ?? ''));
                if ($reason === '') {
                    $emp = $run->employee;
                    $name = $emp && $emp->user ? $emp->user->name : ('Employee ' . $run->employee_id);
                    $errors[] = "Adjustment for {$name} has no reason. Reason is required.";
                }
                $amt = (float) $item->amount;
                if ($amt < 0 || !is_numeric($item->amount)) {
                    $errors[] = "Adjustment amount must be a valid positive number.";
                }
            }
        }

        return response()->json([
            'ok' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ]);
    }

    /**
     * Detailed view per employee for a period.
     */
    public function detail(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'employee_id'  => ['required', 'exists:employees,employee_id'],
        ]);

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if (!$period) {
            return response()->json(['message' => 'Payroll period not found.'], 404);
        }

        $start = Carbon::createFromFormat('Y-m', $validated['period_month'])->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $validated['period_month'])->endOfMonth()->toDateString();

        $employee = Employee::with(['user', 'department'])->findOrFail($validated['employee_id']);
        $baseSalary = (float) $employee->base_salary;
        $dailyRate  = $baseSalary / 26;
        $hourlyRate = $dailyRate / 8;

        // Attendance: same date range as data() (whereDate for consistent matching), format for display
        $empId = (int) $employee->employee_id;
        $attendance = Attendance::where('employee_id', $empId)
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->orderBy('date')
            ->get();
        $formatDate = function ($d) {
            if ($d instanceof \Carbon\Carbon) {
                return $d->format('Y-m-d');
            }
            if (is_string($d) || is_numeric($d)) {
                return \Carbon\Carbon::parse($d)->format('Y-m-d');
            }
            return (string) $d;
        };
        $statusEq = fn($a, $s) => strtolower((string) ($a->at_status ?? '')) === $s;
        $presentDays = $attendance->filter(fn($a) => $statusEq($a, 'present'))->map(fn($a) => $formatDate($a->date))->values()->all();
        $absentDays  = $attendance->filter(fn($a) => $statusEq($a, 'absent'))->map(fn($a) => $formatDate($a->date))->values()->all();
        $lateRecords = $attendance->filter(fn($a) => $statusEq($a, 'late'))
            ->map(fn($a) => ['date' => $formatDate($a->date), 'late_minutes' => (int) ($a->late_minutes ?? 0)])
            ->values()->all();
        $incomplete = $attendance->filter(fn($a) => $statusEq($a, 'present'))
            ->filter(fn($a) => empty($a->clock_in_time) || empty($a->clock_out_time))
            ->map(fn($a) => $formatDate($a->date))->values()->all();
        $leaveDays = $attendance->filter(fn($a) => $statusEq($a, 'leave'))->map(fn($a) => $formatDate($a->date))->values()->all();

        // Leave (same statuses as PayrollGenerator: approved + supervisor_approved)
        $leaves = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->employee_id)
            ->whereIn('leave_status', ['approved', 'supervisor_approved'])
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get();
        $startCarbon = Carbon::parse($start);
        $endCarbon = Carbon::parse($end);
        $approvedLeaveDaysInPeriod = 0;
        foreach ($leaves as $leave) {
            $overlapStart = Carbon::parse($leave->start_date)->max($startCarbon);
            $overlapEnd = Carbon::parse($leave->end_date)->min($endCarbon);
            if ($overlapStart->lte($overlapEnd)) {
                $approvedLeaveDaysInPeriod += $overlapStart->diffInDays($overlapEnd) + 1;
            }
        }
        $workingDaysInMonth = (int) (config('hrms.payroll.working_days_per_month', 26));
        $workedDays = count($presentDays) + count($lateRecords);
        // Same formula as payroll: unworked days (not on approved leave) = absent for deduction
        $absentDaysPayroll = max(0, $workingDaysInMonth - $workedDays - $approvedLeaveDaysInPeriod);
        $paidLeave = $leaves->filter(fn($l) => stripos($l->leaveType->leave_name ?? '', 'unpaid') === false)
            ->map(fn($l) => ['start' => $l->start_date, 'end' => $l->end_date, 'days' => $l->total_days, 'type' => $l->leaveType->leave_name ?? 'Leave'])
            ->values();
        $unpaidLeave = $leaves->filter(fn($l) => stripos($l->leaveType->leave_name ?? '', 'unpaid') !== false)
            ->map(fn($l) => ['start' => $l->start_date, 'end' => $l->end_date, 'days' => $l->total_days, 'type' => $l->leaveType->leave_name ?? 'Unpaid'])
            ->values();

        // OT
        $ots = OvertimeRecord::where('employee_id', $employee->employee_id)
            ->where('ot_status', 'approved')
            ->whereBetween('date', [$start, $end])
            ->get()
            ->map(function ($ot) use ($hourlyRate) {
                $mult = (float) ($ot->rate_type ?? 1);
                $amount = round((float) $ot->hours * $hourlyRate * $mult, 2);
                return [
                    'date' => $ot->date,
                    'hours' => $ot->hours,
                    'multiplier' => $mult,
                    'amount' => $amount,
                ];
            })->values();

        // Penalties
        $penalties = Penalty::where('employee_id', $employee->employee_id)
            ->where('status', 'approved')
            ->whereBetween('assigned_at', [$start, $end])
            ->get()
            ->map(fn($p) => [
                'name' => $p->penalty_name,
                'amount' => $p->default_amount,
                'status' => $p->status,
            ])->values();

        // Payroll run + line items
        $run = PayrollRun::where('payroll_period_id', $period->period_id)
            ->where('employee_id', $employee->employee_id)
            ->first();
        $lineItems = $run
            ? PayrollLineItem::where('payroll_run_id', $run->id)
                ->get()
                ->map(fn($li) => [
                    'code' => $li->code,
                    'item_type' => $li->item_type,
                    'quantity' => $li->quantity,
                    'rate' => $li->rate,
                    'amount' => $li->amount,
                    'source_ref_type' => $li->source_ref_type,
                    'source_ref_id' => $li->source_ref_id,
                    'description' => $li->description,
                ])
            : collect();

        // Use live PayrollGenerator output whenever possible so breakdown matches current rules (e.g. no pay without attendance)
        // and matches the salary grid, which also uses computeForEmployee for persisted runs.
        $adjustmentForCompute = $run ? (float) $run->adjustment_total : 0.0;
        $computed = app(PayrollGenerator::class)->computeForEmployee(
            $employee,
            $validated['period_month'],
            $adjustmentForCompute,
            $period->period_id
        );
        $breakdown = [
            'base'                          => $computed['basic_salary'],
            'allowance'                     => $computed['allowance_total'],
            'ot'                            => $computed['allowance_total'],
            'penalty'                       => $computed['penalty_total'],
            'unpaid_ded'                    => $computed['unpaid_leave_deduction'],
            'absent_ded'                    => $computed['absent_deduction'],
            'late_ded'                      => $computed['late_deduction'],
            'original_attendance_deduction' => $computed['original_attendance_deduction'] ?? ($computed['late_deduction'] + $computed['absent_deduction'] + $computed['unpaid_leave_deduction'] + $computed['penalty_total']),
            'capped_attendance_deduction'   => $computed['capped_attendance_deduction'] ?? 0,
            'chargeable_salary'             => $computed['chargeable_salary'] ?? 0,
            'epf'                           => $computed['epf_total'],
            'tax'                           => $computed['tax_total'],
            'adjustment'                    => $computed['adjustment_total'],
            'gross'                         => $computed['gross_pay'],
            'net'                           => $computed['net_pay'],
            'total_deductions'              => ($computed['capped_attendance_deduction'] ?? 0) + $computed['epf_total'] + $computed['tax_total'],
        ];

        if ($run && $lineItems->isNotEmpty()) {
            $lineItems = $lineItems->map(function (array $li) use ($computed) {
                $code = $li['code'] ?? '';
                $amount = match ($code) {
                    'LATE' => round($computed['late_deduction'], 2),
                    'ABSENT' => round($computed['absent_deduction'], 2),
                    'UNPAID_LEAVE' => round($computed['unpaid_leave_deduction'], 2),
                    'PENALTY' => round($computed['penalty_total'], 2),
                    'EPF' => round($computed['epf_total'], 2),
                    'TAX' => round($computed['tax_total'], 2),
                    'BASIC' => round($computed['basic_salary'], 2),
                    'ALLOWANCE' => round($computed['allowance_total'], 2),
                    default => $li['amount'],
                };

                return array_merge($li, ['amount' => $amount]);
            })->values();
        }

        return response()->json([
            'employee' => [
                'name' => $employee->user->name ?? 'Employee',
                'code' => $employee->employee_code,
                'department' => $employee->department->department_name ?? 'N/A',
                'bank' => [
                    'bank_name' => method_exists($employee, 'getBankDisplayName') ? $employee->getBankDisplayName() : ($employee->bank_name ?? null),
                    'bank_code' => $employee->bank_code ?? null,
                    'account_number_masked' => method_exists($employee, 'getMaskedAccountNumber') ? $employee->getMaskedAccountNumber() : ($employee->bank_account_number ?? null),
                    'account_number' => $employee->bank_account_number ?? null,
                    'account_type' => $employee->account_type ?? null,
                    'account_type_label' => method_exists($employee, 'getAccountTypeLabel') ? $employee->getAccountTypeLabel() : ($employee->account_type ?? null),
                    'branch' => $employee->bank_branch ?? null,
                    'swift' => $employee->bank_swift ?? null,
                ],
            ],
            'period' => [
                'period_month' => $period->period_month,
                'status' => $period->status,
            ],
            'breakdown' => $breakdown,
            'attendance' => [
                'present_days'              => $presentDays,
                'absent_days'               => $absentDays,
                'absent_days_payroll'       => $absentDaysPayroll,
                'worked_days'               => $workedDays,
                'approved_leave_days'       => $approvedLeaveDaysInPeriod,
                'working_days_in_month'     => $workingDaysInMonth,
                'late'                     => $lateRecords,
                'incomplete'               => $incomplete,
                'leave_days'               => $leaveDays,
            ],
            'leave' => [
                'paid' => $paidLeave,
                'unpaid' => $unpaidLeave,
            ],
            'ot' => $ots,
            'penalties' => $penalties,
            'line_items' => $lineItems,
        ]);
    }

    /**
     * Generate payroll runs for a period (and optional department). Allowed only when OPEN or DRAFT.
     */
    public function generate(Request $request, PayrollGenerator $generator)
    {
        $validated = $request->validate([
            'period_month'  => ['required', 'date_format:Y-m'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
        ]);

        Log::info('payroll.generate.request', $validated);

        $periodExistedBefore = (bool) PayrollPeriod::where('period_month', $validated['period_month'])->first();
        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if ($period && Schema::hasColumn('payroll_periods', 'status') && !in_array($period->status, ['OPEN', 'DRAFT'])) {
            return response()->json(['message' => 'This month is locked. Only OPEN or DRAFT payroll can be generated or recalculated. Other months are unaffected.'], 422);
        }

        try {
            if (!Schema::hasTable('payroll_periods') || !Schema::hasTable('payroll_runs')) {
                throw new \RuntimeException('Required payroll tables are missing (payroll_periods / payroll_runs).');
            }

            $runs = $generator->generate($validated['period_month'], $validated['department_id'] ?? null);
        } catch (\Throwable $e) {
            Log::error('payroll.generate.error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => $e->getMessage()], 500);
        }

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if ($period) {
            $period->update(['status' => 'DRAFT']);
        }

        $isRecalc = $periodExistedBefore;
        PayrollAudit::log(
            $isRecalc ? PayrollAudit::ACTION_RECALCULATED : PayrollAudit::ACTION_GENERATED,
            $validated['period_month'],
            $period?->period_id,
            null,
            [
                'department_id' => $validated['department_id'] ?? null,
                'runs_count'     => $runs->count(),
            ]
        );

        $runs->each(fn (PayrollRun $run) => $run->load(['employee.user', 'employee.department']));
        $payload = $runs->map(function (PayrollRun $run) {
            return [
                'employee_id'   => $run->employee_id,
                'employee_code' => $run->employee->employee_code ?? null,
                'name'          => $run->employee->user->name ?? null,
                'department'    => $run->employee->department->department_name ?? null,
                'basic_salary'  => $run->basic_salary,
                'allowance'     => $run->allowance_total,
                'ot_total'      => $run->ot_total,
                'gross_pay'     => $run->gross_pay,
                'net_pay'       => $run->net_pay,
                'status'        => $run->status,
            ];
        });

        return response()->json([
            'period_month' => $validated['period_month'],
            'status'       => 'DRAFT',
            'runs'         => $payload,
            'message'      => "Payroll generated successfully for {$validated['period_month']}.",
        ]);
    }

    /**
     * For previous months only: release is allowed only within a window (e.g. 1st–7th of next month).
     * Current and future months are not restricted. Returns true if release should be blocked.
     */
    public static function isReleaseWindowClosed(string $periodMonth): bool
    {
        $currentYm = Carbon::now()->format('Y-m');
        if ($periodMonth >= $currentYm) {
            return false; // current or future month: no restriction
        }
        $config = config('hrms.payroll_release_window', ['start_day' => 1, 'end_day' => 7]);
        $startDay = (int) ($config['start_day'] ?? 1);
        $endDay = (int) ($config['end_day'] ?? 7);
        $nextMonth = Carbon::createFromFormat('Y-m', $periodMonth)->addMonth();
        $windowStart = $nextMonth->copy()->day($startDay)->startOfDay();
        $windowEnd = $nextMonth->copy()->day(min($endDay, $nextMonth->daysInMonth))->endOfDay();
        $today = Carbon::now();
        return !$today->between($windowStart, $windowEnd);
    }

    /**
     * Release (lock) payroll: DRAFT → LOCKED. Pre-release checklist must pass.
     * Admin must re-enter password to confirm.
     * Previous months: release only allowed within the configured window; current/future months unrestricted.
     */
    public function lock(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'password'     => ['required', 'string'],
            'release_note' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'integer', 'exists:departments,department_id'],
        ]);

        $user = Auth::user();
        if (! $user || ! Hash::check($validated['password'], $user->getAuthPassword())) {
            return response()->json(['message' => 'Invalid password. Please re-enter your admin password.'], 422);
        }

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if (!$period) {
            return response()->json(['message' => 'Please Generate Payroll for this month first.'], 422);
        }

        if (Schema::hasColumn('payroll_periods', 'status') && !in_array($period->status, ['OPEN', 'DRAFT'])) {
            return response()->json(['message' => 'Only DRAFT payroll can be released. LOCKED cannot be reverted in normal UI.'], 422);
        }

        if (self::isReleaseWindowClosed($period->period_month)) {
            return response()->json(['message' => 'Payroll release window for this previous month has passed.'], 422);
        }

        $deptId = $validated['department_id'] ?? null;
        $runs = PayrollRun::where('payroll_period_id', $period->period_id)
            ->when($deptId, fn($q) => $q->whereHas('employee', fn($qq) => $qq->where('department_id', $deptId)))
            ->get();

        $snapshot = [
            'released_at'   => now()->toIso8601String(),
            'period_month'  => $period->period_month,
            'department_id' => $deptId,
            'runs_count'    => $runs->count(),
            'total_gross'   => round($runs->sum('gross_pay'), 2),
            'total_net'     => round($runs->sum('net_pay'), 2),
            'total_deductions' => round($runs->sum('unpaid_leave_deduction') + $runs->sum('absent_deduction') + $runs->sum('late_deduction') + $runs->sum('penalty_total') + $runs->sum('epf_total') + $runs->sum('tax_total'), 2),
        ];

        DB::transaction(function () use ($period, $validated, $snapshot) {
            $period->update([
                'status'       => 'LOCKED',
                'locked_at'    => now(),
                'locked_by'    => Auth::id(),
                'release_note' => $validated['release_note'] ?? null,
                'snapshot'     => $snapshot,
            ]);

            PayrollRun::where('payroll_period_id', $period->period_id)->update(['status' => 'LOCKED']);
        });

        PayrollAudit::log(
            PayrollAudit::ACTION_RELEASED,
            $period->period_month,
            $period->period_id,
            null,
            [
                'release_note' => $validated['release_note'] ?? null,
                'snapshot'     => $snapshot,
            ],
            'Payroll released (locked).'
        );

        return response()->json([
            'message' => 'Payroll released successfully. No further edits allowed for this period.',
            'period'  => $period->fresh()->only(['period_month', 'status', 'locked_at', 'locked_by']),
        ]);
    }

    /**
     * Adjustment sub-type labels for display (stored in description as "SubType: reason").
     */
    private static function adjustmentSubTypes(): array
    {
        return [
            'earning'  => ['bonus' => 'Bonus', 'allowance' => 'Allowance', 'other_earning' => 'Other (Earning)'],
            'deduction' => ['deduction' => 'Deduction', 'late_penalty' => 'Late Penalty', 'absence' => 'Absence', 'other_deduction' => 'Other (Deduction)'],
        ];
    }

    /**
     * Whether saving payroll correction line items is allowed for this period month.
     * When adjustments_calendar_month_only is true, only the calendar current month (Y-m) is allowed.
     */
    private function payrollCorrectionsAllowedForMonth(string $periodMonth): bool
    {
        if (! (bool) config('hrms.payroll.adjustments_calendar_month_only', true)) {
            return true;
        }

        return $periodMonth === Carbon::now()->format('Y-m');
    }

    /**
     * Shared flags for adjustment UI / API.
     *
     * @return array{adjustments_calendar_month_only: bool, calendar_payroll_month: string, can_apply_adjustment: bool}
     */
    private function adjustmentApplyFlags(PayrollPeriod $period, ?PayrollRun $run, bool $isEditable): array
    {
        $calendarOnly = (bool) config('hrms.payroll.adjustments_calendar_month_only', true);
        $isCalMonth = $period->period_month === Carbon::now()->format('Y-m');
        $canApply = $isEditable && $run !== null && (! $calendarOnly || $isCalMonth);

        return [
            'adjustments_calendar_month_only' => $calendarOnly,
            'calendar_payroll_month'          => Carbon::now()->format('Y-m'),
            'can_apply_adjustment'            => $canApply,
        ];
    }

    /**
     * GET adjustment summary for an employee and period (current run totals + recent adjustments list).
     */
    public function adjustmentSummary(Request $request)
    {
        $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'employee_id'  => ['required', 'exists:employees,employee_id'],
        ]);

        $period = PayrollPeriod::where('period_month', $request->input('period_month'))->first();
        if (!$period) {
            return response()->json(['message' => 'Period not found.'], 404);
        }

        $employee = Employee::find($request->input('employee_id'));
        $run = PayrollRun::where('payroll_period_id', $period->period_id)
            ->where('employee_id', $request->input('employee_id'))
            ->first();

        $periodLabel = Carbon::createFromFormat('Y-m', $period->period_month)->format('F Y');
        $isEditable = Schema::hasColumn('payroll_periods', 'status') && $period->status === 'DRAFT';
        $applyFlags = $this->adjustmentApplyFlags($period, $run, $isEditable);

        if (!$run) {
            return response()->json(array_merge([
                'period_label'         => $periodLabel,
                'period_month'         => $period->period_month,
                'status'               => $period->status ?? 'OPEN',
                'is_editable'          => $isEditable,
                'run'                  => null,
                'adjustments'          => [],
                'employee_base_salary' => $employee ? (float) $employee->base_salary : null,
            ], $applyFlags));
        }

        $adjustments = PayrollLineItem::where('payroll_run_id', $run->id)
            ->where('code', 'ADJUSTMENT')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $desc = (string) ($item->description ?? '');
                $colon = strpos($desc, ': ');
                $subType = $colon !== false ? substr($desc, 0, $colon) : 'Adjustment';
                $reason = $colon !== false ? trim(substr($desc, $colon + 2)) : $desc;
                return [
                    'id'         => $item->id,
                    'category'   => $item->item_type === 'DEDUCTION' ? 'Deduction' : 'Earning',
                    'sub_type'   => $subType,
                    'amount'     => (float) $item->amount,
                    'reason'     => $reason,
                    'date'       => $item->created_at?->format('M j, Y g:i A'),
                ];
            })->values()->all();

        return response()->json(array_merge([
            'period_label'         => $periodLabel,
            'period_month'         => $period->period_month,
            'status'               => $period->status ?? 'DRAFT',
            'is_editable'          => $isEditable,
            'employee_base_salary' => $employee ? (float) $employee->base_salary : null,
            'run'                  => [
                'base'             => (float) $run->basic_salary,
                'allowance'        => (float) $run->allowance_total,
                'adjustment_total' => (float) $run->adjustment_total,
                'gross'            => (float) $run->gross_pay,
                'epf'              => (float) $run->epf_total,
                'tax'              => (float) $run->tax_total,
                'net'              => (float) $run->net_pay,
            ],
            'adjustments' => $adjustments,
        ], $applyFlags));
    }

    /**
     * All payroll correction line items (ADJUSTMENT) for the release-report preview.
     * Scoped by period and optional department (same as salary grid).
     */
    public function releaseReportAdjustments(Request $request)
    {
        $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'department'   => ['nullable', 'integer', 'exists:departments,department_id'],
        ]);

        $period = PayrollPeriod::where('period_month', $request->input('period_month'))->first();
        if (! $period) {
            return response()->json(['adjustments' => []]);
        }

        $deptId = $request->input('department');

        $items = PayrollLineItem::query()
            ->where('code', 'ADJUSTMENT')
            ->whereHas('payrollRun', function ($q) use ($period, $deptId) {
                $q->where('payroll_period_id', $period->period_id);
                if ($deptId) {
                    $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $deptId));
                }
            })
            ->with(['payrollRun.employee.user', 'payrollRun.employee.department'])
            ->orderBy('payroll_run_id')
            ->orderByDesc('id')
            ->get();

        $rows = $items->map(function (PayrollLineItem $item) {
            $run = $item->payrollRun;
            $emp = $run?->employee;
            $desc = (string) ($item->description ?? '');
            $colon = strpos($desc, ': ');
            $subType = $colon !== false ? substr($desc, 0, $colon) : 'Adjustment';
            $reason = $colon !== false ? trim(substr($desc, $colon + 2)) : $desc;
            $name = $emp && $emp->user ? $emp->user->name : 'Unknown';
            $code = $emp ? ($emp->employee_code ?? Employee::codeFallbackFromId($emp->employee_id)) : '—';
            $deptName = $emp && $emp->department ? $emp->department->department_name : 'N/A';
            $isDeduction = $item->item_type === 'DEDUCTION';

            return [
                'employee_name'       => $name,
                'employee_id_display' => $code,
                'department'          => $deptName,
                'category'            => $isDeduction ? 'Deduction' : 'Earning',
                'sub_type'            => $subType,
                'amount'              => (float) $item->amount,
                'signed_impact'       => $isDeduction ? -1 * (float) $item->amount : (float) $item->amount,
                'reason'              => $reason,
                'recorded_at'         => $item->created_at?->format('M j, Y g:i A'),
            ];
        })->values()->all();

        return response()->json(['adjustments' => $rows]);
    }

    /**
     * Recalculate adjustment_total, gross_pay, EPF/tax/net from ADJUSTMENT line items on this run.
     */
    public function syncPayrollRunTotalsFromAdjustmentLines(PayrollRun $run): void
    {
        $adjustmentTotal = (float) PayrollLineItem::where('payroll_run_id', $run->id)
            ->where('code', 'ADJUSTMENT')
            ->get()
            ->sum(function ($item) {
                return $item->item_type === 'DEDUCTION' ? -1 * (float) $item->amount : (float) $item->amount;
            });

        $config = config('hrms.payroll', []);
        $epfRate = (float) ($config['epf_employee_rate'] ?? 0.11);
        $taxRate = (float) ($config['tax_rate'] ?? 0.03);
        $gross = round((float) $run->basic_salary + (float) $run->allowance_total + $adjustmentTotal, 2);
        $statutory = PayrollGenerator::applyAttendanceCapAndStatutory(
            $gross,
            (float) $run->basic_salary,
            (float) $run->late_deduction,
            (float) $run->absent_deduction,
            (float) $run->unpaid_leave_deduction,
            (float) $run->penalty_total,
            $epfRate,
            $taxRate
        );

        $run->update([
            'adjustment_total' => round($adjustmentTotal, 2),
            'epf_total'        => $statutory['epf_total'],
            'tax_total'        => $statutory['tax_total'],
            'gross_pay'        => $gross,
            'net_pay'          => $statutory['net_pay'],
        ]);
    }

    /**
     * Apply an adjustment to an employee's payroll (only when DRAFT).
     */
    public function applyAdjustment(Request $request)
    {
        $subTypesEarning = array_keys(self::adjustmentSubTypes()['earning']);
        $subTypesDeduction = array_keys(self::adjustmentSubTypes()['deduction']);

        $validated = $request->validate([
            'period_month'         => ['required', 'date_format:Y-m'],
            'employee_id'          => ['required', 'exists:employees,employee_id'],
            'adjustment_type'      => ['required', 'in:earning,deduction'],
            'adjustment_sub_type'  => ['nullable', 'string'],
            'amount'               => ['required', 'numeric', 'min:0.01'],
            'reason'               => ['required', 'string', 'min:10', 'max:500'],
            'audit_remark'         => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $isDeduction = $validated['adjustment_type'] === 'deduction';
        $allowedSubTypes = $isDeduction ? $subTypesDeduction : $subTypesEarning;
        $subTypeKey = $validated['adjustment_sub_type'] ?? null;
        if ($subTypeKey && !in_array($subTypeKey, $allowedSubTypes, true)) {
            $subTypeKey = $isDeduction ? 'other_deduction' : 'other_earning';
        }
        if (!$subTypeKey) {
            $subTypeKey = $isDeduction ? 'other_deduction' : 'other_earning';
        }

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if (!$period) {
            return response()->json(['message' => 'Payroll period not found. Generate payroll first.'], 422);
        }
        if (Schema::hasColumn('payroll_periods', 'status') && $period->status !== 'DRAFT') {
            return response()->json(['message' => 'Payroll for this month is locked. Corrections must be applied in a later month. Only DRAFT payroll can be adjusted.'], 422);
        }

        if (! $this->payrollCorrectionsAllowedForMonth($validated['period_month'])) {
            return response()->json([
                'message' => 'Payroll corrections (earnings and deductions) can only be saved for the current month (' . Carbon::now()->format('F Y') . '). Select that month under Payroll Period, or set PAYROLL_ADJUSTMENTS_CALENDAR_MONTH_ONLY=false if you must post to other months.',
            ], 422);
        }

        $run = PayrollRun::where('payroll_period_id', $period->period_id)
            ->where('employee_id', $validated['employee_id'])
            ->first();
        if (!$run) {
            return response()->json(['message' => 'No payroll run found for this employee in this period.'], 422);
        }

        $amount = round((float) $validated['amount'], 2);
        $itemType = $isDeduction ? 'DEDUCTION' : 'EARNING';
        $reason = trim($validated['reason']);
        $auditRemark = trim((string) ($validated['audit_remark'] ?? ''));
        $subTypeLabel = $isDeduction
            ? (self::adjustmentSubTypes()['deduction'][$subTypeKey] ?? 'Other (Deduction)')
            : (self::adjustmentSubTypes()['earning'][$subTypeKey] ?? 'Other (Earning)');
        $description = $subTypeLabel . ': ' . $reason;

        DB::transaction(function () use ($run, $itemType, $amount, $description) {
            PayrollLineItem::create([
                'payroll_run_id' => $run->id,
                'item_type'      => $itemType,
                'code'           => 'ADJUSTMENT',
                'quantity'       => 0,
                'rate'           => 0,
                'amount'         => $amount,
                'description'    => $description,
                'created_by'     => Auth::id(),
            ]);

            $this->syncPayrollRunTotalsFromAdjustmentLines($run);
        });

        PayrollAudit::log(
            PayrollAudit::ACTION_ADJUSTMENT_ADDED,
            $validated['period_month'],
            $period->period_id,
            (int) $validated['employee_id'],
            [
                'adjustment_type'     => $validated['adjustment_type'],
                'adjustment_sub_type' => $subTypeKey,
                'amount'              => $amount,
                'reason'              => $reason,
                'confirmed_by_user_id'=> $user->getAuthIdentifier(),
                'audit_remark'        => $auditRemark !== '' ? $auditRemark : null,
            ],
            'Adjustment added (draft payroll; release payroll uses password when locking the period).'
        );

        return response()->json([
            'message' => 'Adjustment saved. Payroll totals updated.',
            'run'     => $run->fresh()->only(['adjustment_total', 'gross_pay', 'net_pay']),
        ]);
    }

    /**
     * Remove one payroll correction line (ADJUSTMENT) and recalculate the run — DRAFT only.
     */
    public function cancelAdjustment(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
            'employee_id'  => ['required', 'exists:employees,employee_id'],
            'line_item_id' => ['required', 'integer', 'exists:payroll_line_items,id'],
            'password'     => ['required', 'string'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Incorrect password. Please try again.'], 422);
        }

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if (! $period) {
            return response()->json(['message' => 'Payroll period not found. Generate payroll first.'], 422);
        }
        if (Schema::hasColumn('payroll_periods', 'status') && $period->status !== 'DRAFT') {
            return response()->json(['message' => 'Payroll for this month is locked. Adjustments cannot be cancelled.'], 422);
        }

        // Removing draft corrections is allowed for the selected payroll month even when
        // PAYROLL_ADJUSTMENTS_CALENDAR_MONTH_ONLY blocks *new* saves for non-current months.

        $run = PayrollRun::where('payroll_period_id', $period->period_id)
            ->where('employee_id', $validated['employee_id'])
            ->first();
        if (! $run) {
            return response()->json(['message' => 'No payroll run found for this employee in this period.'], 422);
        }

        $item = PayrollLineItem::where('id', $validated['line_item_id'])
            ->where('payroll_run_id', $run->id)
            ->where('code', 'ADJUSTMENT')
            ->whereRaw('UPPER(TRIM(item_type)) IN (?, ?)', ['EARNING', 'DEDUCTION'])
            ->first();

        if (! $item) {
            return response()->json(['message' => 'Adjustment line not found or does not belong to this employee and month.'], 404);
        }

        $meta = [
            'line_item_id'   => $item->id,
            'item_type'      => $item->item_type,
            'amount'         => (float) $item->amount,
            'description'    => (string) ($item->description ?? ''),
            'removed_by_user_id' => $user->getAuthIdentifier(),
        ];

        DB::transaction(function () use ($item, $run) {
            $item->delete();
            $this->syncPayrollRunTotalsFromAdjustmentLines($run->fresh());
        });

        PayrollAudit::log(
            PayrollAudit::ACTION_ADJUSTMENT_REMOVED,
            $validated['period_month'],
            $period->period_id,
            (int) $validated['employee_id'],
            $meta,
            'Payroll correction line removed (draft).'
        );

        return response()->json([
            'message' => 'Adjustment removed. Payroll totals updated.',
            'run'     => $run->fresh()->only(['adjustment_total', 'gross_pay', 'net_pay']),
        ]);
    }

    /**
     * Basic salary revision history for an employee (from salary_revisions when table exists).
     */
    public function basicSalaryRevisionHistory(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
        ]);

        if (! Schema::hasTable('salary_revisions')) {
            return response()->json(['data' => [], 'message' => 'Salary revision history is not available.']);
        }

        $empId = (int) $request->input('employee_id');

        $rows = DB::table('salary_revisions')
            ->where('employee_id', $empId)
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $latestApprovedId = (int) DB::table('salary_revisions')
            ->where('employee_id', $empId)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->value('id');

        $approverIds = $rows->pluck('approved_by')->filter()->unique()->values()->all();
        $nameMap = [];
        if ($approverIds !== []) {
            $nameMap = User::whereIn('user_id', $approverIds)->pluck('name', 'user_id')->all();
        }

        $data = $rows->map(function ($r) use ($nameMap, $latestApprovedId) {
            $uid = $r->approved_by ?? null;
            $by = '—';
            if ($uid) {
                $key = (int) $uid;
                $by = (string) ($nameMap[$key] ?? $nameMap[$uid] ?? ('User #' . $uid));
            }

            $status = strtolower((string) ($r->status ?? 'approved'));

            return [
                'id'                 => (int) $r->id,
                'effective_month'    => $r->effective_month,
                'previous_salary'    => round((float) $r->previous_salary, 2),
                'new_salary'         => round((float) $r->new_salary, 2),
                'reason'             => $r->reason ? (string) $r->reason : '—',
                'approved_at'        => $r->approved_at ? Carbon::parse($r->approved_at)->format('M j, Y g:i A') : '—',
                'approved_by_name'   => $by,
                'status'             => $status,
                'can_cancel'         => $status === 'approved' && $latestApprovedId > 0 && (int) $r->id === $latestApprovedId,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * Released payroll periods for the employee, same shape as employee My Payroll → Recent payslips
     * (LOCKED / PAID / PUBLISHED only; payslip preferred, else run, else empty row).
     */
    public function employeePayrollHistory(Request $request)
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,employee_id'],
        ]);

        $empId = (int) $request->input('employee_id');

        $payslips = Payslip::where('employee_id', $empId)
            ->with(['period', 'payrollRun'])
            ->get()
            ->sortByDesc(function (Payslip $p) {
                return $p->period_month ?? $p->period?->period_month ?? '';
            })
            ->values();

        $releasedPeriods = PayrollPeriod::whereIn('status', ['LOCKED', 'PAID', 'PUBLISHED'])
            ->orderByDesc('period_month')
            ->limit(24)
            ->get();

        $payslipByPeriod = $payslips->keyBy(function (Payslip $p) {
            return $p->period_month ?? $p->period?->period_month ?? '';
        });

        $periodIds = $releasedPeriods->pluck('period_id')->filter()->values()->all();
        $runsByPeriod = collect();
        if ($periodIds !== []) {
            $runsByPeriod = PayrollRun::where('employee_id', $empId)
                ->whereIn('payroll_period_id', $periodIds)
                ->with('period')
                ->get()
                ->keyBy(function (PayrollRun $r) {
                    return $r->period ? $r->period->period_month : '';
                });
        }

        $data = $releasedPeriods->take(12)->map(function (PayrollPeriod $period) use ($payslipByPeriod, $runsByPeriod) {
            $periodMonth = $period->period_month ?? '';
            $label = '—';
            if ($periodMonth !== '' && preg_match('/^\d{4}-\d{2}$/', (string) $periodMonth)) {
                try {
                    $label = Carbon::createFromFormat('Y-m', (string) $periodMonth)->format('M Y');
                } catch (\Throwable) {
                    $label = (string) $periodMonth;
                }
            }

            $payslip = $payslipByPeriod->get($periodMonth);
            $run = $runsByPeriod->get($periodMonth);

            $statusLabel = match (strtoupper((string) ($period->status ?? ''))) {
                'PUBLISHED' => 'Published',
                'PAID' => 'Paid',
                'LOCKED' => 'Released',
                default => 'Released',
            };

            if ($payslip) {
                return [
                    'period_month' => $periodMonth,
                    'period_label' => $label,
                    'gross' => round((float) $payslip->basic_salary + (float) $payslip->total_allowances, 2),
                    'net' => round((float) $payslip->net_salary, 2),
                    'status' => $statusLabel,
                    'payslip_id' => $payslip->payslip_id,
                    'has_payslip' => true,
                ];
            }

            if ($run) {
                return [
                    'period_month' => $periodMonth,
                    'period_label' => $label,
                    'gross' => round((float) $run->gross_pay, 2),
                    'net' => round((float) $run->net_pay, 2),
                    'status' => $statusLabel,
                    'payslip_id' => null,
                    'has_payslip' => false,
                ];
            }

            return [
                'period_month' => $periodMonth,
                'period_label' => $label,
                'gross' => null,
                'net' => null,
                'status' => 'Released — No payroll for this period',
                'payslip_id' => null,
                'has_payslip' => false,
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }

    /**
     * Download payslip HTML for an employee (admin); payslip must belong to the given employee_id.
     */
    public function downloadPayslipForEmployee(Request $request, Payslip $payslip)
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,employee_id'],
        ]);

        if ((int) $payslip->employee_id !== (int) $validated['employee_id']) {
            abort(403, 'Payslip does not belong to this employee.');
        }

        $payslip->load('period');
        $periodLabel = $payslip->period_month
            ? Carbon::createFromFormat('Y-m', $payslip->period_month)->format('F Y')
            : ($payslip->period ? Carbon::parse($payslip->period->end_date)->format('F Y') : 'Payslip');
        $gross = (float) $payslip->basic_salary + (float) $payslip->total_allowances;

        return response()->streamDownload(function () use ($payslip, $periodLabel, $gross) {
            echo view('employee.payslip_plain', [
                'payslip' => $payslip,
                'period_label' => $periodLabel,
                'gross' => $gross,
            ])->render();
        }, 'payslip-' . ($payslip->period_month ?? '') . '.html', ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Recalculate DRAFT payroll runs' basic salary and derived totals from effective month onward.
     */
    private function recalculateDraftPayrollRunsBasicSalary(Employee $employee, float $newBasicSalary, string $effectiveMonth): void
    {
        $newBasicSalary = round($newBasicSalary, 2);
        $periods = PayrollPeriod::where('period_month', '>=', $effectiveMonth)
            ->orderBy('period_month')
            ->get();

        $config = config('hrms.payroll', []);
        $epfRate = (float) ($config['epf_employee_rate'] ?? 0.11);
        $taxRate = (float) ($config['tax_rate'] ?? 0.03);

        foreach ($periods as $period) {
            $run = PayrollRun::where('payroll_period_id', $period->period_id)
                ->where('employee_id', $employee->employee_id)
                ->first();
            if (! $run || (Schema::hasColumn('payroll_runs', 'status') && $run->status !== 'DRAFT')) {
                continue;
            }

            $adjustmentTotal = (float) PayrollLineItem::where('payroll_run_id', $run->id)
                ->where('code', 'ADJUSTMENT')
                ->get()
                ->sum(fn ($item) => $item->item_type === 'DEDUCTION' ? -1 * (float) $item->amount : (float) $item->amount);

            $gross = round($newBasicSalary + (float) $run->allowance_total + $adjustmentTotal, 2);
            $statutory = PayrollGenerator::applyAttendanceCapAndStatutory(
                $gross,
                $newBasicSalary,
                (float) $run->late_deduction,
                (float) $run->absent_deduction,
                (float) $run->unpaid_leave_deduction,
                (float) $run->penalty_total,
                $epfRate,
                $taxRate
            );

            $run->update([
                'basic_salary' => round($newBasicSalary, 2),
                'gross_pay'    => $gross,
                'epf_total'    => $statutory['epf_total'],
                'tax_total'    => $statutory['tax_total'],
                'net_pay'      => $statutory['net_pay'],
            ]);
        }
    }

    /**
     * Update employee basic salary for current and future months. Updates employees.base_salary,
     * optionally logs to salary_revisions, and recalculates DRAFT runs for this employee from effective month onward.
     */
    public function updateBasicSalary(Request $request)
    {
        $validated = $request->validate([
            'period_month'           => ['required', 'date_format:Y-m'],
            'employee_id'            => ['required', 'exists:employees,employee_id'],
            'new_base_salary'        => ['required', 'numeric', 'min:0.01'],
            'reason'                 => ['nullable', 'string', 'max:500'],
            'password'               => ['required', 'string'],
            'basic_salary_confirm'   => ['required', 'accepted'],
            'audit_remark'           => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Incorrect password. Please try again.'], 422);
        }

        $auditRemark = trim((string) ($validated['audit_remark'] ?? ''));

        $employee = Employee::findOrFail($validated['employee_id']);
        $previousSalary = (float) $employee->base_salary;
        $newSalary = round((float) $validated['new_base_salary'], 2);
        $effectiveMonth = $validated['period_month'];
        $reason = trim((string) ($validated['reason'] ?? ''));

        if ($newSalary === $previousSalary) {
            return response()->json(['message' => 'New basic salary is the same as current. No change made.'], 422);
        }

        DB::transaction(function () use ($employee, $previousSalary, $newSalary, $effectiveMonth, $reason) {
            $employee->update(['base_salary' => $newSalary]);

            if (Schema::hasTable('salary_revisions')) {
                DB::table('salary_revisions')->insert([
                    'employee_id'     => $employee->employee_id,
                    'previous_salary' => $previousSalary,
                    'new_salary'      => $newSalary,
                    'effective_month' => $effectiveMonth,
                    'reason'          => $reason ?: null,
                    'status'          => 'approved',
                    'approved_by'     => Auth::id(),
                    'approved_at'     => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $this->recalculateDraftPayrollRunsBasicSalary($employee, $newSalary, $effectiveMonth);
        });

        PayrollAudit::log(
            PayrollAudit::ACTION_ADJUSTMENT_ADDED,
            $effectiveMonth,
            null,
            (int) $employee->employee_id,
            [
                'action'               => 'update_basic_salary',
                'previous'             => $previousSalary,
                'new'                  => $newSalary,
                'reason'               => $reason,
                'confirmed_by_user_id' => $user->getAuthIdentifier(),
                'audit_remark'         => $auditRemark !== '' ? $auditRemark : null,
            ],
            'Basic salary updated (password confirmed).'
        );

        return response()->json([
            'message' => 'Basic salary updated. Current and future DRAFT payroll runs have been recalculated.',
            'previous_salary' => $previousSalary,
            'new_salary'     => $newSalary,
        ]);
    }

    /**
     * Cancel the latest approved basic salary revision (LIFO), after password check.
     * Restores employees.base_salary to the revision's previous amount and recalculates DRAFT runs.
     */
    public function cancelBasicSalaryRevision(Request $request)
    {
        $validated = $request->validate([
            'revision_id' => ['required', 'integer'],
            'employee_id' => ['required', 'exists:employees,employee_id'],
            'password'    => ['required', 'string'],
        ]);

        $user = Auth::user();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Incorrect password. Please try again.'], 422);
        }

        if (! Schema::hasTable('salary_revisions')) {
            return response()->json(['message' => 'Salary revision history is not available.'], 422);
        }

        $revision = DB::table('salary_revisions')->where('id', $validated['revision_id'])->first();
        if (! $revision || (int) $revision->employee_id !== (int) $validated['employee_id']) {
            return response()->json(['message' => 'Revision not found.'], 404);
        }

        if (strtolower((string) ($revision->status ?? '')) !== 'approved') {
            return response()->json(['message' => 'This revision is already cancelled or cannot be reversed.'], 422);
        }

        $latestApprovedId = (int) DB::table('salary_revisions')
            ->where('employee_id', (int) $revision->employee_id)
            ->where('status', 'approved')
            ->orderByDesc('id')
            ->value('id');

        if ((int) $revision->id !== $latestApprovedId) {
            return response()->json(['message' => 'Only the most recent basic salary change can be cancelled.'], 422);
        }

        $employee = Employee::findOrFail((int) $revision->employee_id);
        $revertedSalary = round((float) $revision->previous_salary, 2);
        $wasSalary = round((float) $revision->new_salary, 2);
        if (round((float) $employee->base_salary, 2) !== $wasSalary) {
            return response()->json(['message' => 'Current basic salary no longer matches this record. Cancel is not allowed.'], 422);
        }

        $effectiveMonth = (string) $revision->effective_month;

        $blocking = PayrollRun::where('employee_id', $employee->employee_id)
            ->whereHas('period', fn ($q) => $q->where('period_month', '>=', $effectiveMonth))
            ->when(Schema::hasColumn('payroll_runs', 'status'), fn ($q) => $q->where('status', '!=', 'DRAFT'))
            ->exists();

        if ($blocking) {
            return response()->json([
                'message' => 'Cannot cancel: payroll for a period from '.$effectiveMonth.' onward is no longer in DRAFT (released or locked).',
            ], 422);
        }

        DB::transaction(function () use ($revision, $employee, $revertedSalary, $user) {
            $employee->update(['base_salary' => $revertedSalary]);

            $update = [
                'status'     => 'cancelled',
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('salary_revisions', 'cancelled_at')) {
                $update['cancelled_at'] = now();
            }
            if (Schema::hasColumn('salary_revisions', 'cancelled_by')) {
                $update['cancelled_by'] = $user->getAuthIdentifier();
            }
            DB::table('salary_revisions')->where('id', $revision->id)->update($update);

            $this->recalculateDraftPayrollRunsBasicSalary($employee->fresh(), $revertedSalary, (string) $revision->effective_month);
        });

        PayrollAudit::log(
            PayrollAudit::ACTION_ADJUSTMENT_REMOVED,
            $effectiveMonth,
            null,
            (int) $employee->employee_id,
            [
                'action'         => 'cancel_basic_salary_revision',
                'revision_id'    => (int) $revision->id,
                'removed_salary' => $wasSalary,
                'reverted_to'    => $revertedSalary,
            ],
            'Basic salary revision cancelled (password confirmed).'
        );

        return response()->json([
            'message'     => 'Basic salary change cancelled. Employee salary restored and draft payroll recalculated.',
            'base_salary' => $revertedSalary,
        ]);
    }

    /**
     * Mark payroll as paid (only when LOCKED).
     */
    public function pay(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
        ]);

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if (!$period) {
            return response()->json(['message' => 'Please Generate Payroll for this month first.'], 422);
        }

        if (Schema::hasColumn('payroll_periods', 'status') && $period->status !== 'LOCKED') {
            return response()->json(['message' => 'Payroll must be LOCKED before marking as PAID.'], 422);
        }

        $period->update([
            'status'   => 'PAID',
            'paid_at'  => now(),
            'paid_by'  => Auth::id(),
        ]);

        PayrollRun::where('payroll_period_id', $period->period_id)
            ->update(['status' => 'PAID']);

        PayrollAudit::log(PayrollAudit::ACTION_PAID, $period->period_month, $period->period_id, null, [], 'Payroll marked as paid.');

        return response()->json([
            'message' => 'Payroll marked as PAID. Ready to publish payslips.',
            'period'  => $period->only(['period_month', 'status', 'paid_at', 'paid_by']),
        ]);
    }

    /**
     * Publish payslips. If period is LOCKED, marks as PAID first then publishes.
     * Combined flow: Mark as Paid + Publish in one action.
     */
    public function publish(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'date_format:Y-m'],
        ]);

        $period = PayrollPeriod::where('period_month', $validated['period_month'])->first();
        if (!$period) {
            return response()->json(['message' => 'Please Generate Payroll for this month first.'], 422);
        }

        if (Schema::hasColumn('payroll_periods', 'status') && !in_array($period->status, ['LOCKED', 'PAID'])) {
            return response()->json(['message' => 'Payroll must be LOCKED or PAID before publishing.'], 422);
        }

        // If LOCKED, mark as PAID first (combined flow)
        if ($period->status === 'LOCKED') {
            $period->update([
                'status'   => 'PAID',
                'paid_at'  => now(),
                'paid_by'  => Auth::id(),
            ]);
            PayrollRun::where('payroll_period_id', $period->period_id)->update(['status' => 'PAID']);
            PayrollAudit::log(PayrollAudit::ACTION_PAID, $period->period_month, $period->period_id, null, [], 'Payroll marked as paid (via Publish).');
            $period->refresh();
        }

        $runs = PayrollRun::where('payroll_period_id', $period->period_id)->get();
        if ($runs->isEmpty()) {
            return response()->json(['message' => 'No payroll runs to publish.'], 422);
        }
        if ($runs->contains(fn($r) => $r->status !== 'PAID')) {
            return response()->json(['message' => 'All payroll runs must be PAID before publishing.'], 422);
        }

        $now = now();
        $userId = Auth::id();

        \DB::transaction(function () use ($period, $runs, $now, $userId) {
            $period->update([
                'status'        => 'PUBLISHED',
                'published_at'  => $now,
                'published_by'  => $userId,
            ]);

            PayrollRun::where('payroll_period_id', $period->period_id)
                ->update(['is_published' => true, 'published_at' => $now]);

            foreach ($runs as $run) {
                \App\Models\Payslip::updateOrCreate(
                    [
                        'payroll_run_id' => $run->id,
                        'employee_id'    => $run->employee_id,
                    ],
                    [
                        'period_id'          => $period->period_id,
                        'period_month'       => $period->period_month,
                        'basic_salary'       => $run->basic_salary,
                        'total_allowances'   => $run->allowance_total,
                        'total_deductions'   => (float) $run->epf_total + (float) $run->tax_total,
                        'total_overtime_amount' => 0,
                        'net_salary'         => $run->net_pay,
                        'published_at'       => $now,
                        'publish_version'    => \DB::raw('COALESCE(publish_version,0)+1'),
                    ]
                );
            }
        });

        // Trigger notifications/exports (handled by listeners)
        PayslipsPublished::dispatch($period);

        return response()->json([
            'message' => 'Payslips published. Employees can now view this period.',
            'period'  => $period->only(['period_month', 'status', 'published_at', 'published_by']),
        ]);
    }
}
