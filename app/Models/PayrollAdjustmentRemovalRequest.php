<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollAdjustmentRemovalRequest extends Model
{
    protected $table = 'payroll_adjustment_removal_requests';

    protected $fillable = [
        'payroll_line_item_id',
        'employee_id',
        'supervisor_id',
        'admin_id',
        'period_month',
        'amount_snapshot',
        'reason_snapshot',
        'sub_type_snapshot',
        'request_reason',
        'attachment_path',
        'employee_note',
        'supervisor_note',
        'admin_note',
        'status',
        'submitted_at',
        'supervisor_reviewed_at',
        'admin_reviewed_at',
        'final_decision_at',
    ];

    protected $casts = [
        'amount_snapshot'        => 'decimal:2',
        'submitted_at'           => 'datetime',
        'supervisor_reviewed_at' => 'datetime',
        'admin_reviewed_at'      => 'datetime',
        'final_decision_at'      => 'datetime',
    ];

    public const STATUS_PENDING_SUPERVISOR = 'pending_supervisor_review';

    public const STATUS_PENDING_ADMIN = 'pending_admin';

    public const STATUS_REJECTED_SUPERVISOR = 'rejected_by_supervisor';

    public const STATUS_APPROVED_ADMIN = 'approved_by_admin';

    public const STATUS_REJECTED_ADMIN = 'rejected_by_admin';

    public const STATUS_CANCELLED_EMPLOYEE = 'cancelled_by_employee';

    public static function terminalStatuses(): array
    {
        return [
            self::STATUS_REJECTED_SUPERVISOR,
            self::STATUS_REJECTED_ADMIN,
            self::STATUS_APPROVED_ADMIN,
            self::STATUS_CANCELLED_EMPLOYEE,
        ];
    }

    public function payrollLineItem()
    {
        return $this->belongsTo(PayrollLineItem::class, 'payroll_line_item_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id', 'user_id');
    }
}
