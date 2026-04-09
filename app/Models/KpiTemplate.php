<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiTemplate extends Model
{
    // Defined in migration: $table->id('kpi_id');
    protected $primaryKey = 'kpi_id';

    protected $fillable = [
        'kpi_title',
        'kpi_description',
        'kpi_type',       // 'Quantitative' or 'Qualitative'
        'default_target', // decimal(8,2)
        'weight',         // decimal(5,2)
    ];

    // Relationship: One template can be used by many departments
    public function departmentKpis()
    {
        return $this->hasMany(DepartmentKpi::class, 'kpi_id', 'kpi_id');
    }

    // Relationship: One template can be assigned to many employees
    public function employeeKpis()
    {
        return $this->hasMany(EmployeeKpi::class, 'kpi_id', 'kpi_id');
    }
}