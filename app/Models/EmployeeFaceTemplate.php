<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeFaceTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'embedding',
        'image_path',
        'is_active',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'embedding' => 'array',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
