<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Visa;

class VisaPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->hasPanelAccess()) {
            return false;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isMainAdmin()
            || ($user->isBranchEmployee() && filled($user->broker_id));
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Visa $visa): bool
    {
        if ($user->isMainAdmin()) {
            return true;
        }

        return $this->belongsToUserBranch($user, $visa);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Visa $visa): bool
    {
        if ($user->isMainAdmin()) {
            return true;
        }

        return $this->belongsToUserBranch($user, $visa);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Visa $visa): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Visa $visa): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Visa $visa): bool
    {
        return false;
    }

    private function belongsToUserBranch(User $user, Visa $visa): bool
    {
        return $user->hasPanelAccess()
            && $user->isBranchEmployee()
            && filled($user->broker_id)
            && (int) $visa->booking?->broker_id === (int) $user->broker_id;
    }
}
