<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentKpi extends Model
{
    // Defined in migration: $table->id('dept_kpi_id');
    protected $primaryKey = 'dept_kpi_id';

    protected $fillable = [
        'department_id',
        'kpi_id',        // FK to KpiTemplate
        'period_start',
        'period_end',
        'deadline',
        'target',
        'progress',
        'status',        // 'active', 'completed', 'failed'
        'notes',
        'user_id'        // The Admin (User) who created this
    ];

    // Link to the Template (Title, Description, Type)
    public function template()
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_id', 'kpi_id');
    }

    // Link to the Department Model
    public function department()
    {
        // Assuming you have a Department model with primary key 'department_id'
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    // Link to the Admin User who created it
    public function creator()
{
    // Defines that the 'user_id' in this table matches 'user_id' in the users table
    return $this->belongsTo(User::class, 'user_id', 'user_id');
}
}