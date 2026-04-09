<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Department;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Services\OtClaimAudit;
use App\Services\OtClaimNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SupervisorOvertimeController extends Controller
{
   public function index(Request $request)
    {
        $myAreaIds = Area::where('supervisor_id', Auth::id())->pluck('id');
        $myDeptIds = Department::where('manager_id', Auth::id())->pluck('department_id');

        // IF THE SUPERVISOR HAS NO ASSIGNED TEAM:
        if ($myAreaIds->isEmpty() && $myDeptIds->isEmpty()) {
            return view('supervisor.overtime_inbox', [
                'pendingClaims' => collect(),
                'actedClaims' => collect(),
                'departments' => Department::orderBy('department_name')->get(),
                'pendingAdminCount' => 0,
                'flaggedPendingCount' => 0,
                'approvedCount' => 0,
                'rejectedCount' => 0,
                'otRequests' => collect(),
                'otRequestsPendingCount' => 0,
            ])->with('message', 'No area or department assigned to you. Contact HR to be set as area supervisor or department manager.');
        }

        $q = $request->get('q');
        $deptId = $request->get('department');
        $start = $request->get('start');
        $end = $request->get('end');

        // BASE QUERY: Fetch claims belonging to this supervisor's areas/departments
        $query = OvertimeClaim::with(['employee.user', 'employee.department', 'area', 'user'])
            ->where(function ($qry) use ($myAreaIds, $myDeptIds) {
                if ($myAreaIds->isNotEmpty()) {
                    $qry->orWhereIn('area_id', $myAreaIds);
                }
                if ($myDeptIds->isNotEmpty()) {
                    $qry->orWhereHas('user', fn($u) => $u->whereIn('dept_id', $myDeptIds));
                }
            });

        // APPLY FILTERS
        if ($q) {
            $query->where(function ($qry) use ($q) {
                $qry->whereHas('employee', function ($e) use ($q) {
                    $e->where('employee_code', 'like', "%{$q}%")
                        ->orWhere('employee_id', $q);
                })->orWhereHas('employee.user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
                });
            });
        }
        if ($deptId) {
            $query->whereHas('employee', fn($e) => $e->where('department_id', $deptId));
        }
        if ($start) {
            $query->whereDate('date', '>=', $start);
        }
        if ($end) {
            $query->whereDate('date', '<=', $end);
        }

        // Claims from users with role "supervisor" go to admin only — never list them here.
        $pendingClaims = $this->withoutSupervisorRoleClaimants(
            (clone $query)->where('status', OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR)
        )
            ->orderByDesc('submitted_at')
            ->get();

        $actedClaims = $this->withoutSupervisorRoleClaimants(
            (clone $query)->whereIn('status', [
                OvertimeClaim::STATUS_SUPERVISOR_APPROVED,
                OvertimeClaim::STATUS_SUPERVISOR_REJECTED,
                OvertimeClaim::STATUS_ADMIN_PENDING,
                OvertimeClaim::STATUS_ADMIN_APPROVED,
            ])
        )
            ->orderByDesc('updated_at')
            ->get();

        // 3. COUNTS FOR THE TOP SUMMARY CARDS
        $pendingAdminCount = (clone $query)->where('status', OvertimeClaim::STATUS_ADMIN_PENDING)->count();
        $flaggedPendingCount = $pendingClaims->count();
        $approvedCount = (clone $query)->whereIn('status', [
            OvertimeClaim::STATUS_SUPERVISOR_APPROVED, 
            OvertimeClaim::STATUS_ADMIN_PENDING, 
            OvertimeClaim::STATUS_ADMIN_APPROVED
        ])->count();
        $rejectedCount = (clone $query)->where('status', OvertimeClaim::STATUS_SUPERVISOR_REJECTED)->count();

        $departments = Department::orderBy('department_name')->get();

        // 4. PRESERVE ORIGINAL OT REQUESTS (Just in case the Sidebar uses it)
        $supervisorEmpId = Auth::user()->employee->employee_id ?? 0;
        $otRequestsQuery = OvertimeRecord::with(['employee.user', 'employee.department'])
            ->whereHas('employee', function ($q) use ($supervisorEmpId) {
                $q->where('supervisor_id', $supervisorEmpId);
            })
            ->where('ot_status', 'pending')
            ->where('final_status', OvertimeRecord::FINAL_PENDING_SUPERVISOR)
            ->whereDoesntHave('employee.user', function ($u) {
                $u->whereRaw('LOWER(TRIM(COALESCE(users.role, \'\'))) = ?', ['supervisor']);
            });
            
        $otRequestsPendingCount = (clone $otRequestsQuery)->count();
        $otRequests = (clone $otRequestsQuery)->orderBy('date', 'desc')->orderBy('ot_id', 'desc')->limit(10)->get();

        // RETURN ALL ALIGNED VARIABLES TO THE VIEW!
        return view('supervisor.overtime_inbox', compact(
            'pendingClaims', 
            'actedClaims', 
            'departments', 
            'pendingAdminCount', 
            'flaggedPendingCount', 
            'approvedCount', 
            'rejectedCount',
            'otRequests',
            'otRequestsPendingCount'
        ));
    }

    public function show(OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        $claim->load(['employee.user', 'employee.department', 'period']);
        return response()->json([
            'claim' => [
                'id' => $claim->id,
                'employee' => $claim->employee->user->name ?? 'Unknown',
                'employee_code' => $claim->employee->employee_code ?? '',
                'department' => $claim->employee->department->department_name ?? 'N/A',
                'date' => $claim->date->format('Y-m-d'),
                'hours' => (float) $claim->hours,
                'rate_type' => (float) $claim->rate_type,
                'reason' => $claim->reason,
                'supporting_info' => $claim->supporting_info,
                'status' => $claim->status,
                'submitted_at' => $claim->submitted_at?->toIso8601String(),
                'supervisor_remark' => $claim->supervisor_remark,
                'admin_remark' => $claim->admin_remark,
                'supervisor_recommendation' => $claim->getSupervisorRecommendationLabelForAdmin(),
            ],
        ]);
    }

    public function viewAttachment(OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);

        if (! $claim->attachment_path) {
            abort(404, 'Attachment not found.');
        }

        if (! Storage::disk('public')->exists($claim->attachment_path)) {
            abort(404, 'Attachment file is missing.');
        }

        $filename = basename($claim->attachment_path);
        return Storage::disk('public')->response(
            $claim->attachment_path,
            $filename,
            ['Content-Disposition' => 'inline; filename="' . $filename . '"']
        );
    }

    /**
     * Supervisor sets Recommended or Not recommended; claim is queued to admin (no final approve/reject here).
     */
    public function setRecommendation(Request $request, OvertimeClaim $claim)
    {
        $this->ensureSupervisorOf($claim);
        if (! $this->canSupervisorSetRecommendation($claim)) {
            return redirect()->back()->with('error', 'This claim can no longer be updated.');
        }

        $validated = $request->validate([
            'recommendation' => ['required', 'in:recommended,not_recommended'],
            'supervisor_remark' => ['nullable', 'string', 'max:500'],
        ]);

        $actionType = $validated['recommendation'] === 'recommended'
            ? OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED
            : OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED;
        $auditAction = $validated['recommendation'] === 'recommended'
            ? OtClaimAudit::ACTION_SUPERVISOR_RECOMMENDED
            : OtClaimAudit::ACTION_SUPERVISOR_NOT_RECOMMENDED;

        $isUpdate = $claim->status === OvertimeClaim::STATUS_ADMIN_PENDING;
        $supervisorRemark = trim((string) ($validated['supervisor_remark'] ?? ''));
        if ($validated['recommendation'] === 'not_recommended' && $supervisorRemark === '') {
            return redirect()->back()->withErrors([
                'supervisor_remark' => 'Reason is required when marking a claim as Not recommended.',
            ]);
        }
        $supervisorRemark = $supervisorRemark !== '' ? $supervisorRemark : null;

        DB::transaction(function () use ($claim, $actionType, $auditAction, $isUpdate, $supervisorRemark) {
            $before = $claim->status;
            $claim->update([
                'supervisor_action_type' => $actionType,
                'supervisor_action_at' => now(),
                'supervisor_remark' => $supervisorRemark,
                'approved_hours' => $claim->hours,
                'status' => OvertimeClaim::STATUS_ADMIN_PENDING,
            ]);
            OtClaimAudit::log($auditAction, $claim, $before, $claim->status, [
                'supervisor_action_type' => $actionType,
                'supervisor_remark' => $supervisorRemark,
            ], $isUpdate ? 'Supervisor recommendation updated while pending admin' : 'Supervisor recommendation recorded; queued to admin');

            // Notify on first send to admin and on later recommendation updates.
            OtClaimNotifier::onSupervisorRecommendationToAdmin($claim->load(['employee.user']));
        });

        if ($isUpdate) {
            $msg = $validated['recommendation'] === 'recommended'
                ? 'Updated to Recommended.'
                : 'Updated to Not recommended.';
        } else {
            $msg = $validated['recommendation'] === 'recommended'
                ? 'Marked as Recommended and sent to admin.'
                : 'Marked as Not recommended and sent to admin.';
        }

        return redirect()->back()->with('success', $msg);
    }

    private function canSupervisorSetRecommendation(OvertimeClaim $claim): bool
    {
        if ((int) $claim->supervisor_id !== (int) Auth::id()) {
            return false;
        }

        if ($claim->status === OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR) {
            return true;
        }

        if ($claim->status !== OvertimeClaim::STATUS_ADMIN_PENDING || $claim->admin_action_at !== null) {
            return false;
        }

        return in_array($claim->supervisor_action_type, [
            OvertimeClaim::SUPERVISOR_ACTION_RECOMMENDED,
            OvertimeClaim::SUPERVISOR_ACTION_NOT_RECOMMENDED,
        ], true);
    }

    private function ensureSupervisorOf(OvertimeClaim $claim): void
    {
        $claim->loadMissing('employee.user');

        if ($this->claimantUserIsSupervisor($claim)) {
            abort(403, 'Supervisor overtime claims are approved by admin only.');
        }

        $myAreaIds = Area::where('supervisor_id', Auth::id())->pluck('id');
        $myDeptIds = Department::where('manager_id', Auth::id())->pluck('department_id');

        if ($myAreaIds->contains($claim->area_id)) {
            return;
        }
        if ($claim->user_id && $myDeptIds->contains($claim->user?->dept_id)) {
            return;
        }
        abort(403, 'You are not the approver for this claim.');
    }

    /** @param \Illuminate\Database\Eloquent\Builder<\App\Models\OvertimeClaim> $query */
    private function withoutSupervisorRoleClaimants($query)
    {
        return $query->whereDoesntHave('employee.user', function ($u) {
            $u->whereRaw('LOWER(TRIM(COALESCE(users.role, \'\'))) = ?', ['supervisor']);
        });
    }

    private function claimantUserIsSupervisor(OvertimeClaim $claim): bool
    {
        $role = strtolower(trim((string) ($claim->employee?->user?->role ?? '')));

        return $role === 'supervisor';
    }
}