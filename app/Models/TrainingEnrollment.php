<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingEnrollment extends Model
{
    protected $primaryKey = 'enrollment_id';

    protected $fillable = [
        'employee_id',
        'training_id',
        'enrollment_date',
        'completion_status',
        'score_or_result',
        'remarks'
    ];

    // Relationship: Enrollment belongs to an Employee
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    // Relationship: Enrollment belongs to a Training Program
    public function program()
    {
        return $this->belongsTo(TrainingProgram::class, 'training_id', 'training_id');
    }

    // Backward compatibility: alias
    public function training()
    {
        return $this->program();
    }
}
