<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LeaveRequestProofController extends Controller
{
    /**
     * Stream the employee's leave proof file (auth + role enforced by route middleware).
     */
    public function show(Request $request, LeaveRequest $leave): Response
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $name = $request->route()?->getName();

        if ($name === 'admin.leave.request.attachment') {
            // Middleware: role admin, administrator, hr, manager
        } elseif ($name === 'supervisor.leave.attachment') {
            $uid = (int) $user->getAuthIdentifier();
            $isSupervisor = (int) ($leave->supervisor_id ?? 0) === $uid
                || (int) ($leave->supervisor_approved_by ?? 0) === $uid;
            abort_unless($isSupervisor, 403, 'You cannot view this attachment.');
        } else {
            abort(404);
        }

        $path = $leave->proof_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Attachment not found.');
        }

        $filename = basename($path);

        return Storage::disk('public')->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
