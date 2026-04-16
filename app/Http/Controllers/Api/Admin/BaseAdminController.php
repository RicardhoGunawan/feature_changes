<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BaseAdminController extends Controller
{
    /**
     * Centralized permission check for admin controllers.
     * With 2-role system: only 'administrator' has full access.
     * Approval flow uses position hierarchy, not role.
     */
    protected function validatePermission(Request $request, string $permission)
    {
        $user = $request->user();

        // Administrators have all access
        if ($user->role === 'administrator') {
            return true;
        }

        abort(response()->json([
            'success' => false,
            'message' => 'Akses ditolak: Hanya Administrator yang dapat mengakses fitur ini.'
        ], 403));
    }

    /**
     * Check if the current user can approve a leave request.
     * Approval is based on POSITION HIERARCHY, not role.
     *
     * A user can approve if:
     * 1. They are an administrator (full access), OR
     * 2. Their position is the direct parent of the requester's position.
     */
    protected function canApproveForUser(Request $request, $requesterUser): bool
    {
        $approver = $request->user();

        // Administrators can always approve
        if ($approver->role === 'administrator') {
            return true;
        }

        // Check position hierarchy
        if (!$requesterUser->position_id || !$approver->position_id) {
            return false;
        }

        $requesterPosition = $requesterUser->jobPosition;
        if (!$requesterPosition) {
            return false;
        }

        // Approver's position must be the parent (or ancestor) of requester's position
        $parentPosition = $requesterPosition->parent;
        while ($parentPosition) {
            if ($parentPosition->id === $approver->position_id) {
                return true;
            }
            $parentPosition = $parentPosition->parent ?? null;
        }

        return false;
    }
}
