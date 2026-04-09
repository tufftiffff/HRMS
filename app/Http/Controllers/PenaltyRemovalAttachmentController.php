<?php

namespace App\Http\Controllers;

use App\Models\PenaltyRemovalRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PenaltyRemovalAttachmentController extends Controller
{
    /**
     * Stream attachment from the public disk (no /storage symlink required).
     * Authorized: owning employee, assigned supervisor, or HR admin roles.
     */
    public function show(PenaltyRemovalRequest $removal): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user, 403);

        $role = strtolower(trim((string) ($user->role ?? '')));
        $uid = (int) ($user->user_id ?? $user->getAuthIdentifier());

        $isAdmin = in_array($role, ['admin', 'administrator', 'hr', 'manager'], true);
        $isEmployeeOwner = (int) ($removal->employee?->user_id ?? 0) === $uid;
        $isAssignedSupervisor = (int) ($removal->supervisor_id ?? 0) === $uid;

        abort_unless($isAdmin || $isEmployeeOwner || $isAssignedSupervisor, 403, 'You cannot view this attachment.');

        $path = $removal->attachment_path;
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Attachment not found.');
        }

        $filename = basename($path);

        return Storage::disk('public')->response($path, $filename, [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
