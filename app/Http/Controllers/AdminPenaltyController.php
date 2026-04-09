<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class AdminPenaltyController extends Controller
{
    private const ALLOWED_ROLES = ['admin', 'administrator', 'hr', 'manager'];

    private function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        $role = strtolower(trim((string) ($user->role ?? '')));
        if (in_array($role, self::ALLOWED_ROLES, true)) {
            return true;
        }
        $userId = $user->user_id ?? $user->id ?? null;
        if ($userId === 1 || $userId === '1') {
            return true;
        }

        return false;
    }

    /**
     * Legacy route: redirect to attendance status update inbox for old bookmarks.
     * (Attendance penalties registry and admin approve/reject UI were removed; supervisors/employees view payroll adjustments in their portals.)
     */
    public function index()
    {
        if (! $this->canAccess()) {
            return redirect()->route('admin.dashboard')->with('error', 'Access denied. Only Admin, HR, or Manager can access penalty tracking.');
        }

        return redirect()->route('admin.attendance.penalty_removal_requests.index');
    }
}
