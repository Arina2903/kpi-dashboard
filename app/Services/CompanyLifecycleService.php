<?php

namespace App\Services;

/**
 * The pre-activation half of a company's lifecycle — `draft` -> `onboarding`
 * -> `configuring` — advances automatically as real setup work happens
 * elsewhere (`CompanyController::storeAdmin()`, `OnboardingController`'s own
 * live-computed steps), the same "computed, monotonic, never regresses"
 * pattern `onboarding_status` already established.
 *
 * `active`, `suspended`, and `archived` are deliberately NOT part of this
 * progression — they're admin-triggered actions
 * (`CompanyController::activate()`/`suspend()`/`reactivate()`/`archive()`/
 * `unarchive()`), never auto-advanced, so `advanceTo()` refuses to target
 * them and no-ops on a company that has already left the pre-active stages.
 */
class CompanyLifecycleService
{
    private const PRE_ACTIVE_ORDER = ['draft', 'onboarding', 'configuring'];

    public static function advanceTo(SupabaseUserService $supabase, string $companyId, string $target): void
    {
        $targetRank = array_search($target, self::PRE_ACTIVE_ORDER, true);

        if ($targetRank === false) {
            throw new \InvalidArgumentException("'{$target}' is not a pre-active lifecycle stage.");
        }

        $company = $supabase->first('companies', ['id' => 'eq.' . $companyId, 'select' => 'status']);
        $currentRank = array_search($company['status'] ?? null, self::PRE_ACTIVE_ORDER, true);

        if ($currentRank === false || $currentRank >= $targetRank) {
            return;
        }

        $supabase->update('companies', ['id' => 'eq.' . $companyId], ['status' => $target]);
    }
}
