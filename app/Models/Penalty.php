<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
    protected $primaryKey = 'penalty_id';

    protected $fillable = [
        'employee_id',
        'attendance_id',
        'penalty_name',
        'default_amount',
        'assigned_at',
        'removed_at',
        'status',
        'rejection_remark',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'removed_at'  => 'date',
    ];

    /**
     * In-progress removal workflow (employee → supervisor → admin).
     */
    public static function inProgressRemovalStatuses(): array
    {
        return [
            PenaltyRemovalRequest::STATUS_PENDING_SUPERVISOR,
            PenaltyRemovalRequest::STATUS_PENDING_ADMIN,
            PenaltyRemovalRequest::STATUS_SUBMITTED_ADMIN,
            PenaltyRemovalRequest::STATUS_NEEDS_CLARIFICATION,
        ];
    }

    /**
     * Latest open removal request for this penalty (excludes completed / cancelled).
     */
    public function activeRemovalRequest()
    {
        return $this->hasOne(PenaltyRemovalRequest::class, 'penalty_id', 'penalty_id')
            ->whereIn('status', self::inProgressRemovalStatuses());
    }

    /**
     * Relationship: Get all removal requests for this penalty.
     */
    public function removalRequests()
    {
        return $this->hasMany(PenaltyRemovalRequest::class, 'penalty_id', 'penalty_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    /**
     * Human-facing reference for this attendance record (e.g. AR-0001).
     */
    public function attendanceRecordCode(): string
    {
        return self::formatAttendanceRecordCode($this->getKey());
    }

    /**
     * @param  int|string|null  $penaltyId  Primary key of the penalties row
     */
    public static function formatAttendanceRecordCode(int|string|null $penaltyId): string
    {
        if ($penaltyId === null || $penaltyId === '') {
            return 'AR-0000';
        }

        return 'AR-'.str_pad((string) $penaltyId, 4, '0', STR_PAD_LEFT);
    }
}