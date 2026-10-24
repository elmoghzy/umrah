<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->hasPanelAccess()) {
            return false;
        }

        return $user->isMainAdmin() ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isBranchEmployee() && filled($user->broker_id);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        return $this->belongsToUserBranch($user, $booking);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isBranchEmployee() && filled($user->broker_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking): bool
    {
        return $this->belongsToUserBranch($user, $booking);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return false;
    }

    private function belongsToUserBranch(User $user, Booking $booking): bool
    {
        return $user->hasPanelAccess()
            && $user->isBranchEmployee()
            && filled($user->broker_id)
            && (int) $booking->broker_id === (int) $user->broker_id;
    }
}
