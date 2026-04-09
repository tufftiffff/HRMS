<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::withCount('employees')
            ->with('manager:user_id,name,email')
            ->orderBy('department_name')
            ->get();

        // Group by first letter of department name for display
        $grouped = $departments->groupBy(function (Department $d) {
            $first = mb_strtoupper(mb_substr($d->department_name, 0, 1));
            return ctype_alpha($first) ? $first : '#';
        })->map(fn ($group) => $group->values())->sortKeys();

        return view('admin.departments.index', compact('departments', 'grouped'));
    }

    public function create()
    {
        $department = new Department;
        $creating = true;
        $managers = User::where('role', 'supervisor')->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        $employeesInDept = collect();
        $allEmployees = Employee::query()
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->with(['user', 'department', 'position'])
            ->orderBy('employee_code')
            ->get();

        $managerToSubordinateIds = [];
        $managerEmpIds = Employee::query()
            ->whereIn('user_id', $managers->pluck('user_id'))
            ->pluck('employee_id', 'user_id');
        foreach ($managerEmpIds as $managerUserId => $managerEmployeeId) {
            $subordinateIds = Employee::query()
                ->where('supervisor_id', $managerEmployeeId)
                ->pluck('employee_id')
                ->values()
                ->all();
            $managerToSubordinateIds[(string) $managerUserId] = $subordinateIds;
        }

        return view('admin.departments.edit', compact(
            'department',
            'managers',
            'allEmployees',
            'employeesInDept',
            'managerToSubordinateIds',
            'creating'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,user_id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,employee_id'],
        ]);
        $ids = $validated['employee_ids'] ?? [];
        $ids = Employee::query()
            ->whereIn('employee_id', $ids)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->pluck('employee_id')
            ->values()
            ->all();

        $department = DB::transaction(function () use ($validated, $ids) {
            $dept = Department::create([
                'department_name' => $validated['department_name'],
                'manager_id' => $validated['manager_id'] ?? null,
            ]);
            if ($ids !== []) {
                Employee::whereIn('employee_id', $ids)->update(['department_id' => $dept->department_id]);
                $userIds = Employee::whereIn('employee_id', $ids)->pluck('user_id')->filter()->values()->all();
                if ($userIds !== []) {
                    User::whereIn('user_id', $userIds)->update(['dept_id' => $dept->department_id]);
                }
            }

            return $dept;
        });

        return redirect()->route('admin.departments.edit', $department)
            ->with('success', 'Department created. You can adjust assignments below if needed.');
    }

    public function edit(Department $department)
    {
        $department->load('manager');
        $managers = User::where('role', 'supervisor')->orderBy('name')->get(['user_id', 'name', 'email', 'role']);
        // Employees: current department first, then others (for "auto show certain department first")
        $employeesInDept = Employee::query()
            ->where('department_id', $department->department_id)
            // Do not allow supervisors to appear in the employee selection list.
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->with(['user', 'position', 'department'])
            ->orderBy('employee_code')
            ->get();
        $employeesOther = Employee::where(function ($q) use ($department) {
            $q->whereNull('department_id')->orWhere('department_id', '!=', $department->department_id);
        })
            // Do not allow supervisors to appear in the employee selection list.
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->with(['user', 'department', 'position'])
            ->orderBy('employee_code')
            ->get();
        $allEmployees = $employeesInDept->concat($employeesOther);

        // Build a lookup: manager user_id => subordinate employee_id[]
        // so UI can auto-tick employees assigned under selected supervisor.
        $managerToSubordinateIds = [];
        $managerEmpIds = Employee::query()
            ->whereIn('user_id', $managers->pluck('user_id'))
            ->pluck('employee_id', 'user_id');
        foreach ($managerEmpIds as $managerUserId => $managerEmployeeId) {
            $subordinateIds = Employee::query()
                ->where('supervisor_id', $managerEmployeeId)
                ->pluck('employee_id')
                ->values()
                ->all();
            $managerToSubordinateIds[(string) $managerUserId] = $subordinateIds;
        }

        return view('admin.departments.edit', compact(
            'department',
            'managers',
            'allEmployees',
            'employeesInDept',
            'managerToSubordinateIds'
        ));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,user_id'],
        ]);
        $department->update($validated);
        return redirect()->route('admin.departments.index')->with('success', 'Department updated.');
    }

    /** Bulk assign employees to this department (checkbox selection). Also syncs user.dept_id. */
    public function assignEmployees(Request $request, Department $department)
    {
        $validated = $request->validate([
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,employee_id'],
        ]);
        $ids = $validated['employee_ids'] ?? [];

        // Never assign/unassign supervisors as employees via this UI.
        // (They shouldn't appear in the checkbox list and should not affect department sync.)
        $ids = Employee::query()
            ->whereIn('employee_id', $ids)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->pluck('employee_id')
            ->values()
            ->all();

        $supervisorEmployeeIds = Employee::query()
            ->where('department_id', $department->department_id)
            ->whereHas('user', fn ($q) => $q->where('role', 'supervisor'))
            ->pluck('employee_id')
            ->values()
            ->all();

        // employees.department_id is NOT NULL, so when we "remove" employees from this department,
        // move them to a safe fallback department instead of setting it to null.
        $fallbackDeptId = Department::query()
            ->where('department_name', 'General')
            ->value('department_id');
        if ($fallbackDeptId === null) {
            // Fallback: keep them in the current department if no "General" exists.
            $fallbackDeptId = $department->department_id;
        }

        // Unassign employees currently in this department but not in the submitted list
        $keepIds = array_values(array_unique(array_merge($ids, $supervisorEmployeeIds)));

        $removed = Employee::query()
            ->where('department_id', $department->department_id)
            ->whereNotIn('employee_id', $keepIds)
            ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
            ->get();
        $removedUserIds = $removed->pluck('user_id')->filter()->values()->all();
        if (!empty($removed->pluck('employee_id')->values()->all())) {
            Employee::query()
                ->where('department_id', $department->department_id)
                ->whereNotIn('employee_id', $keepIds)
                ->whereHas('user', fn ($q) => $q->where('role', '!=', 'supervisor'))
                ->update(['department_id' => $fallbackDeptId]);
        }
        if (!empty($removedUserIds)) {
            User::whereIn('user_id', $removedUserIds)->update(['dept_id' => $fallbackDeptId]);
        }

        // Set this department for selected employees and sync user.dept_id
        Employee::whereIn('employee_id', $ids)->update(['department_id' => $department->department_id]);
        $userIds = Employee::whereIn('employee_id', $ids)->pluck('user_id')->filter()->values()->all();
        if (!empty($userIds)) {
            User::whereIn('user_id', $userIds)->update(['dept_id' => $department->department_id]);
        }

        return redirect()->back()->with('success', 'Employee assignment updated.');
    }

    /** Delete department. Clears supervisor links for this department, moves employees to a fallback dept, then deletes. */
    public function destroy(Department $department)
    {
        $deptId = $department->department_id;

        $fallbackDeptId = Department::query()
            ->where('department_name', 'General')
            ->where('department_id', '!=', $deptId)
            ->value('department_id');

        if ($fallbackDeptId === null) {
            $fallbackDeptId = Department::query()
                ->where('department_id', '!=', $deptId)
                ->orderBy('department_id')
                ->value('department_id');
        }

        if ($fallbackDeptId === null) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Cannot delete the only department. Create another department first, then try again.');
        }

        DB::transaction(function () use ($department, $deptId, $fallbackDeptId) {
            $inDeptEmployeeIds = Employee::query()
                ->where('department_id', $deptId)
                ->pluck('employee_id')
                ->values()
                ->all();

            // Anyone supervised by an employee in this department loses that link.
            if (! empty($inDeptEmployeeIds)) {
                Employee::query()
                    ->whereIn('supervisor_id', $inDeptEmployeeIds)
                    ->update(['supervisor_id' => null]);
            }

            // Employees in this department: drop their supervisor assignment (dept supervisor chain).
            Employee::query()
                ->where('department_id', $deptId)
                ->update(['supervisor_id' => null]);

            // Department manager may supervise people in other departments — clear those links too.
            if ($department->manager_id) {
                $managerEmployeeId = Employee::query()
                    ->where('user_id', $department->manager_id)
                    ->value('employee_id');
                if ($managerEmployeeId) {
                    Employee::query()
                        ->where('supervisor_id', $managerEmployeeId)
                        ->update(['supervisor_id' => null]);
                }
            }

            $userIds = Employee::query()
                ->where('department_id', $deptId)
                ->pluck('user_id')
                ->filter()
                ->values()
                ->all();

            Employee::query()
                ->where('department_id', $deptId)
                ->update(['department_id' => $fallbackDeptId]);

            if (! empty($userIds)) {
                User::query()->whereIn('user_id', $userIds)->update(['dept_id' => $fallbackDeptId]);
            }

            $department->delete();
        });

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department deleted. Employees were moved to another department and supervisor links for this department were cleared.');
    }
}