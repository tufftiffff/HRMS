<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeFace;
use App\Models\EmployeeFaceTemplate;
use App\Models\FaceAuditLog;
use App\Services\AuditLogService;
use App\Services\FaceApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FaceRecognitionController extends Controller
{
    public function __construct(private FaceApiService $faceApi)
    {
    }

    public function showEnrollForm()
    {
        $this->ensureAdmin();

        $employees = Employee::with('user')
            ->orderBy('employee_code')
            ->get();

        return view('admin.face_enroll', [
            'employees' => $employees,
            'selectedEmployeeId' => session('selected_employee'),
        ]);
    }

    public function enroll(Request $request, int $employeeId)
    {
        $this->ensureAdmin();

        $employee = Employee::findOrFail($employeeId);

        $validated = $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $result = $this->faceApi->enroll($employee->employee_id, $validated['image']);

        if (! $result['ok'] || empty($result['embedding'])) {
            return back()
                ->withErrors(['face' => $result['message'] ?? 'Face enrollment failed.'])
                ->with('selected_employee', $employee->employee_id);
        }

        EmployeeFace::updateOrCreate(
            ['employee_id' => $employee->employee_id],
            [
                'embedding' => $result['embedding'],
                'model_name' => $result['model'] ?? config('services.face_api.model', 'buffalo_l'),
            ]
        );

        return back()
            ->with('success', 'Face enrolled successfully for ' . ($employee->user->name ?? 'employee '.$employee->employee_code))
            ->with('selected_employee', $employee->employee_id);
    }

    public function showVerifyForm()
    {
        $user = Auth::user();

        $employee = $user->employee ?? null;

        if (! $employee) {
            abort(403, 'Only employees can access face verification.');
        }

        $faceData = $employee->faceData;

        return view('employee.face_verify', [
            'employee'      => $employee,
            'faceData'      => $faceData,
            'todayRecord'   => $employee->attendance()->where('date', now()->toDateString())->first(),
            'verifyResult'  => session('verify_result'),
        ]);
    }

    /**
     * Admin-facing wrapper around the face verification page.
     * Uses the same data as the employee view but with admin layout.
     */
    public function showAdminVerifyForm()
    {
        $this->ensureAdmin();

        $user = Auth::user();
        $employee = $user->employee ?? null;

        if (! $employee) {
            abort(403, 'Admin account is not linked to an employee record, so face verification is not available.');
        }

        $faceData = $employee->faceData;

        return view('admin.face_verify', [
            'employee'      => $employee,
            'faceData'      => $faceData,
            'todayRecord'   => $employee->attendance()->where('date', now()->toDateString())->first(),
            'verifyResult'  => session('verify_result'),
        ]);
    }

    /**
     * Admin self-enrollment wrapper around the employee face enrollment flow.
     * Renders the same content as employee enrollment but with the admin layout.
     */
    public function showAdminSelfEnrollForm()
    {
        $this->ensureAdmin();

        $user = Auth::user();
        $employee = $user->employee ?? null;

        if (! $employee) {
            abort(403, 'Admin account is not linked to an employee record, so face enrollment is not available.');
        }

        $faceData = $employee->faceData;

        return view('admin.face_enroll_self', compact('employee', 'faceData'));
    }

    public function verify(Request $request, int $employeeId)
    {
        $employee = Employee::findOrFail($employeeId);
        $user = Auth::user();

        $this->ensureAdminOrSelf($user?->employee?->employee_id, $employeeId, $user?->role);

        $faceData = $employee->faceData;

        if (! $faceData) {
            return back()->withErrors(['face' => 'No stored face data for this employee. Please enroll first.']);
        }

        $validated = $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'images' => ['nullable', 'array', 'min:1', 'max:3'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $validated['image'] ?? null;
        $files = $request->file('images');
        if (! $file && (!is_array($files) || count($files) < 1)) {
            return back()->withErrors(['face' => 'Please provide at least one image.']);
        }

        // If multiple frames are provided, use the /verify endpoint's multi-frame support.
        if (is_array($files) && count($files) > 0) {
            $result = $this->faceApi->verifyMany(
                $employee->employee_id,
                $faceData->embedding,
                $files
            );
        } else {
            $result = $this->faceApi->verify(
                $employee->employee_id,
                $faceData->embedding,
                $file
            );
        }

        if (! $result['ok']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $result['message'] ?? 'Face verification failed.',
                'failure_reason' => $result['failure_reason'] ?? null,
                ], 422);
            }
            return back()->withErrors(['face' => $result['message'] ?? 'Face verification failed.']);
        }

        // Business rule: mark attendance as present only when similarity score >= 0.80
        $score = isset($result['score']) ? (float) $result['score'] : null;
        $attendScoreThreshold = 0.70;
        $attendOk = $score !== null && $score >= $attendScoreThreshold;

        // One punch per successful verify: check-in if none today, else check-out only after a minimum
        // delay since check-in (prevents two HTTP calls in one scan from recording in+out at once).
        $punchApplied = null;
        $attendanceMessage = null;
        if ($attendOk) {
            $punchApplied = false;
            $today = now()->toDateString();
            $minGap = max(1, (int) config('services.face_api.min_seconds_before_checkout', 90));

            DB::transaction(function () use ($employee, $today, $score, $minGap, &$punchApplied, &$attendanceMessage) {
                $row = $employee->attendance()->where('date', $today)->lockForUpdate()->first();

                if (! $row) {
                    Attendance::updateOrCreate(
                        [
                            'employee_id' => $employee->employee_id,
                            'date' => $today,
                        ],
                        [
                            'clock_in_time' => now()->toTimeString(),
                            'clock_out_time' => null,
                            'at_status' => 'present',
                            'late_minutes' => 0,
                            'verified_method' => 'face',
                            'verify_score' => $score,
                        ]
                    );
                    $punchApplied = true;

                    return;
                }

                if ($row->clock_in_time && ! $row->clock_out_time) {
                    $dateStr = $row->date instanceof Carbon
                        ? $row->date->format('Y-m-d')
                        : Carbon::parse($row->date)->format('Y-m-d');
                    $inAt = Carbon::parse($dateStr.' '.$row->clock_in_time);
                    if ($inAt->diffInSeconds(now()) < $minGap) {
                        $attendanceMessage = 'Check-in recorded. Wait at least '.$minGap.' seconds, then tap Scan again to check out.';

                        return;
                    }
                    $row->update([
                        'clock_out_time' => now()->toTimeString(),
                        'verified_method' => 'face',
                        'verify_score' => $score,
                    ]);
                    $punchApplied = true;

                    return;
                }

                if ($row->clock_out_time) {
                    $attendanceMessage = 'You already checked in and out today. No new punch was recorded.';

                    return;
                }

                if (! $row->clock_in_time) {
                    $row->update([
                        'clock_in_time' => now()->toTimeString(),
                        'clock_out_time' => null,
                        'at_status' => 'present',
                        'late_minutes' => 0,
                        'verified_method' => 'face',
                        'verify_score' => $score,
                    ]);
                    $punchApplied = true;
                }
            });
        }

        if ($request->expectsJson()) {
            $baseMessage = $attendOk
                ? 'Verified (score ≥ '.number_format($attendScoreThreshold, 2).').'
                : 'Score below attend threshold ('.number_format($attendScoreThreshold, 2).'). Retrying…';

            return response()->json([
                'ok' => true,
                'matched' => $attendOk,
                'score' => $score,
                'threshold' => $result['threshold'] ?? null,
                'message' => $attendOk && $attendanceMessage
                    ? $attendanceMessage
                    : $baseMessage,
                'punch_applied' => $punchApplied,
                'attendance_message' => $attendanceMessage,
            ]);
        }

        $redirect = back()
            ->with('verify_result', $result)
            ->with($attendOk ? 'success' : 'error', $attendOk ? 'Verified.' : 'Score too low. Try again.');

        if ($attendOk && $attendanceMessage) {
            $redirect->with('info', $attendanceMessage);
        }

        return $redirect;
    }

    private const ADMIN_LEVEL_ROLES = ['admin', 'administrator', 'hr', 'manager'];

    private function ensureAdmin(): void
    {
        $role = strtolower(trim((string) (Auth::user()?->role ?? '')));
        if (! in_array($role, self::ADMIN_LEVEL_ROLES, true)) {
            abort(403, 'Admin access required.');
        }
    }

    private function ensureAdminOrSelf(?int $authEmployeeId, int $targetEmployeeId, ?string $role): void
    {
        $r = strtolower(trim((string) ($role ?? '')));
        if (in_array($r, self::ADMIN_LEVEL_ROLES, true)) {
            return;
        }

        if ($authEmployeeId !== $targetEmployeeId) {
            abort(403, 'You can only verify your own face.');
        }
    }

    /**
     * Re-enroll / Reset Face: HR/Admin only. Invalidates current template and allows employee to re-enroll.
     * When admin is resetting their own face (self), password must be re-entered.
     * Audit: who, when, why.
     */
    public function resetFace(Request $request, Employee $employee)
    {
        $this->ensureAdmin();

        $isSelfReset = Auth::user()->employee && (int) Auth::user()->employee->employee_id === (int) $employee->employee_id;

        $rules = ['reason' => ['nullable', 'string', 'max:500']];
        if ($isSelfReset) {
            $rules['password'] = ['required', 'string'];
        }
        $request->validate($rules);

        if ($isSelfReset) {
            $user = Auth::user();
            if (! Hash::check($request->input('password'), $user->getAuthPassword())) {
                return back()->withErrors(['password' => 'Invalid password. Please re-enter your password to proceed.']);
            }
        }

        $reason = $request->input('reason', 'HR/Admin reset');
        $hadFace = $employee->faceData !== null;

        if ($hadFace) {
            $face = $employee->faceData;
            if ($face && $face->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($face->photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($face->photo_path);
            }
            EmployeeFace::where('employee_id', $employee->employee_id)->delete();
            EmployeeFaceTemplate::where('employee_id', $employee->employee_id)->update(['is_active' => false]);
            if (\Illuminate\Support\Facades\Schema::hasColumn($employee->getTable(), 'face_enrolled')) {
                $employee->update(['face_enrolled' => false, 'face_enrolled_at' => null]);
            }
        }

        FaceAuditLog::log(
            'template_reset',
            $employee->employee_id,
            true,
            Auth::id(),
            'admin',
            null,
            null,
            $reason,
            ['had_template' => $hadFace]
        );
        AuditLogService::log(
            AuditLogService::CATEGORY_FACE,
            'face_re_enroll_requested',
            AuditLogService::STATUS_SUCCESS,
            'Face re-enroll requested / reset (by Admin). Reason: ' . $reason,
            ['had_template' => $hadFace, 'reason' => $reason],
            $employee->employee_id,
            AuditLogService::SEVERITY_INFO
        );

        return back()
            ->with('success', $hadFace ? 'Face template reset. Employee can re-enroll.' : 'No template was on file; nothing to reset.')
            ->with('selected_employee', $employee->employee_id);
    }
}
