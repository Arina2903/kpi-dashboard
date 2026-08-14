<?php

namespace App\Http\Controllers\Platform\Concerns;

use App\Services\SupabaseUserService;
use Illuminate\Http\Request;

/**
 * Writes to `admin_action_logs`. Deliberately not best-effort: this doesn't
 * catch its own exceptions, so a failed write propagates to the caller
 * instead of the audit trail silently growing gaps. Callers are expected to
 * catch it themselves and tell the operator the underlying action already
 * happened but wasn't logged — see CompanyController::store() for the
 * pattern.
 */
trait LogsAdminActions
{
    protected function logAdminAction(
        Request $request,
        string $action,
        ?string $targetCompanyId = null,
        ?string $targetUserId = null,
        array $metadata = []
    ): void {
        $platformUser = $request->attributes->get('platformUser');

        /** @var SupabaseUserService $supabase */
        $supabase = $request->attributes->get('platformSupabase');

        $supabase->insert('admin_action_logs', [
            'actor_user_id' => $platformUser['id'],
            'action' => $action,
            'target_company_id' => $targetCompanyId,
            'target_user_id' => $targetUserId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Only logs when the actor is using their Super Admin bypass on a
     * company they don't otherwise belong to — that's the "Richworks support
     * access" moment requirement #3 wants audited. A Company/Department
     * Admin managing their own company isn't a support action and would
     * just be noise here. Returns a redirect to send back from the caller
     * when logging itself fails; returns null when there's nothing to do or
     * logging succeeded.
     */
    protected function logIfSuperAdmin(
        Request $request,
        string $action,
        string $targetCompanyId,
        array $metadata = []
    ): ?\Illuminate\Http\RedirectResponse {
        if (!($request->attributes->get('platformUser')['is_super_admin'] ?? false)) {
            return null;
        }

        try {
            $this->logAdminAction($request, $action, $targetCompanyId, null, $metadata);
        } catch (\Throwable) {
            return back()->with('error', 'The action was completed, but could not be logged — contact support before continuing.');
        }

        return null;
    }
}
