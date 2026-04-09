<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\OvertimeClaim;
use App\Models\OvertimeRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Legacy OvertimeRecord (employee/overtime) supervisor queue — separate from OT Claims inbox.
 */
class SupervisorOvertimeRecordController extends Controller
{
    private function supervisorEmployeeId(): int
    {
        $id = Auth::user()?->employee?->employee_id;
        abort_unless($id, 403, 'Employee profile not found');

        return (int) $id;
    }

    private function baseRecordQuery()
    {
        $sid = $this->supervisorEmployeeId();

        return OvertimeRecord::with(['employee.user', 'employee.department'])
            ->whereHas('employee', fn ($q) => $q->where('supervisor_id', $sid));
    }

    private function ensureSupervisorOwnsRecord(OvertimeRecord $overtime): void
    {
        $overtime->loadMissing('employee');
        if ((int) $overtime->employee?->supervisor_id !== $this->supervisorEmployeeId()) {
            abort(403, 'You are not the supervisor for this request.');
        }
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'pending');
        $q = $request->get('q');

        $query = $this->baseRecordQuery();
        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->whereHas('employee', fn ($e) => $e->where('employee_code', 'like', "%{$q}%"))
                    ->orWhereHas('employee.user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            });
        }

        if ($tab === 'reviewed') {
            $query->where('final_status', '!=', OvertimeRecord::FINAL_PENDING_SUPERVISOR);
        } else {
            $query->where('final_status', OvertimeRecord::FINAL_PENDING_SUPERVISOR);
        }

        $pendingCount = $this->baseRecordQuery()
            ->where('final_status', OvertimeRecord::FINAL_PENDING_SUPERVISOR)
            ->count();

        $records = $query->orderByDesc('date')->orderByDesc('ot_id')->paginate(15)->withQueryString();

        return view('supervisor.overtime_requests', compact('records', 'tab', 'pendingCount'));
    }

    public function approve(OvertimeRecord $overtime)
    {
        $this->ensureSupervisorOwnsRecord($overtime);
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_SUPERVISOR) {
            return back()->with('error', 'This request is not pending your approval.');
        }

        DB::transaction(function () use ($overtime) {
            $overtime->update([
                'final_status' => OvertimeRecord::FINAL_PENDING_ADMIN,
                'supervisor_decision' => OvertimeRecord::SUPERVISOR_APPROVED,
                'supervisor_approved_by' => Auth::id(),
                'supervisor_action_at' => now(),
                'submitted_to_admin_at' => now(),
            ]);

            $claim = OvertimeClaim::where('overtime_record_id', $overtime->ot_id)->first();
            if ($claim) {
                $claim->update([
                    'status' => OvertimeClaim::STATUS_ADMIN_PENDING,
                    'supervisor_action_type' => OvertimeClaim::SUPERVISOR_ACTION_APPROVED,
                    'supervisor_action_at' => now(),
                    'approved_hours' => $claim->hours,
                ]);
            }
        });

        return back()->with('success', 'OT request approved and sent to admin.');
    }

    public function reject(OvertimeRecord $overtime)
    {
        $this->ensureSupervisorOwnsRecord($overtime);
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_SUPERVISOR) {
            return back()->with('error', 'This request is not pending your approval.');
        }

        DB::transaction(function () use ($overtime) {
            $overtime->update([
                'final_status' => OvertimeRecord::FINAL_REJECTED_SUPERVISOR,
                'supervisor_decision' => OvertimeRecord::SUPERVISOR_REJECTED,
                'supervisor_action_at' => now(),
                'ot_status' => 'rejected',
            ]);

            $claim = OvertimeClaim::where('overtime_record_id', $overtime->ot_id)->first();
            if ($claim && $claim->status === OvertimeClaim::STATUS_SUBMITTED_TO_SUPERVISOR) {
                $claim->update([
                    'status' => OvertimeClaim::STATUS_SUPERVISOR_REJECTED,
                    'supervisor_action_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'OT request rejected.');
    }

    public function markIssue(Request $request, OvertimeRecord $overtime)
    {
        $this->ensureSupervisorOwnsRecord($overtime);
        if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_ADMIN) {
            return back()->with('error', 'You can only flag requests that are pending admin.');
        }

        $validated = $request->validate([
            'issue_reason' => ['required', 'string', 'max:500'],
        ]);

        $overtime->update([
            'flagged_for_admin' => true,
            'admin_review_remark' => $validated['issue_reason'],
            'issue_flagged_by' => Auth::id(),
            'issue_flagged_at' => now(),
        ]);

        return back()->with('success', 'Issue flagged for admin.');
    }

    public function approvalSummary(Request $request)
    {
        $q = $request->get('q');
        $query = $this->baseRecordQuery()
            ->where('final_status', OvertimeRecord::FINAL_PENDING_ADMIN)
            ->where('ot_status', 'pending');

        if ($q) {
            $query->where(function ($qq) use ($q) {
                $qq->whereHas('employee', fn ($e) => $e->where('employee_code', 'like', "%{$q}%"))
                    ->orWhereHas('employee.user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            });
        }

        $totalToSend = (clone $query)->count();
        $records = $query->orderByDesc('date')->orderByDesc('ot_id')->paginate(20)->withQueryString();

        return view('supervisor.overtime_approval_summary', compact('records', 'totalToSend'));
    }

    public function sendSummary(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'flags' => ['nullable', 'array'],
            'remarks' => ['nullable', 'array'],
        ]);

        $ids = $validated['ids'];
        $flags = $validated['flags'] ?? [];
        $remarks = $validated['remarks'] ?? [];

        $records = OvertimeRecord::whereIn('ot_id', $ids)->get();
        $sent = 0;

        foreach ($records as $overtime) {
            $this->ensureSupervisorOwnsRecord($overtime);
            if ($overtime->final_status !== OvertimeRecord::FINAL_PENDING_ADMIN || $overtime->ot_status !== 'pending') {
                continue;
            }

            $fid = (int) $overtime->ot_id;
            $flag = ! empty($flags[(string) $fid]) || ! empty($flags[$fid]);
            $remark = isset($remarks[$fid]) ? (string) $remarks[$fid] : (isset($remarks[(string) $fid]) ? (string) $remarks[(string) $fid] : '');

            $overtime->update([
                'flagged_for_admin' => $flag,
                'admin_review_remark' => $flag ? Str::limit($remark, 500, '') : $overtime->admin_review_remark,
            ]);
            $sent++;
        }

        if ($sent === 0) {
            return back()->with('error', 'No valid requests could be sent.');
        }

        $adminIds = User::whereRaw('LOWER(TRIM(role)) = ?', ['admin'])->pluck('user_id');
        foreach ($adminIds as $uid) {
            AppNotification::notify(
                (int) $uid,
                'ot_summary_sent',
                'OT approval summary from supervisor',
                Auth::user()->name . ' sent an OT approval summary (' . $sent . ' request(s)). Review pending OT in Payroll.',
                ['count' => $sent]
            );
        }

        return back()->with('success', 'Summary sent to admin (' . $sent . ' request(s)).');
    }
}
