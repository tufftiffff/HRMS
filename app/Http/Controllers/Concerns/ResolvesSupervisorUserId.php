<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;

trait ResolvesSupervisorUserId
{
    /**
     * Resolve HR supervisor user_id for workflow routing (matches penalty removal logic).
     */
    protected function resolveSupervisorUserIdForEmployee(Employee $employee): ?int
    {
        $supervisorUserId = null;
        if ($employee->supervisor_id) {
            $rawSupervisorId = (int) $employee->supervisor_id;
            $supervisorEmployee = $employee->supervisor;
            $supervisorUserId = $supervisorEmployee?->user_id;
            if ($supervisorUserId === null) {
                $supervisorEmployeeDirect = Employee::query()->where('employee_id', $rawSupervisorId)->first();
                $supervisorUserId = $supervisorEmployeeDirect?->user_id;
            }
            if ($supervisorUserId === null) {
                $supervisorUserId = $rawSupervisorId;
            }
        }
        if ($supervisorUserId === null && $employee->department && $employee->department->manager_id) {
            $supervisorUserId = (int) $employee->department->manager_id;
        }

        return $supervisorUserId;
    }
}
