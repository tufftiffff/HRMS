<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeKpi extends Model
{
    // Defined in migration: $table->id('emp_kpi_id');
    protected $primaryKey = 'emp_kpi_id';

    protected $fillable = [
        'employee_id',
        'kpi_id',        // FK to KpiTemplate
        'dept_kpi_id',   // Nullable FK to DepartmentKpi
        'assigned_date',
        'deadline',
        'actual_score',
        'rating',        // 'A', 'B', 'C', etc.
        'comments',
        'kpi_status',
        'employee_comments',  // Employee's Self-Reflection
        'self_rating'     // 'pending', 'in_progress', 'completed'
    ];

    // Link to the Template
    public function template()
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_id', 'kpi_id');
    }

    // Link to the Employee
    public function employee()
    {
        // Assuming you have an Employee model with primary key 'employee_id'
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Link to the parent Department Goal (Optional)
    public function departmentKpi()
    {
        return $this->belongsTo(DepartmentKpi::class, 'dept_kpi_id', 'dept_kpi_id');
    }
}