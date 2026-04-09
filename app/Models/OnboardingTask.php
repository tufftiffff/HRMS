<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTask extends Model
{
    use HasFactory;

    protected $table = 'onboarding_task';
    protected $primaryKey = 'task_id';

    protected $fillable = [
        'onboarding_id',
        'task_name',
        'task_description',
        'is_completed',
        'completed_at',
        'category',
        'due_date',
        'user_id',
        'remarks'
    ];

    public function onboarding()
    {
        return $this->belongsTo(Onboarding::class, 'onboarding_id', 'onboarding_id');
    }
}