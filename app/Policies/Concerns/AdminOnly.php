<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AdminOnly
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->hasPanelAccess() || ! $user->isMainAdmin()) {
            return false;
        }

        return $this->shouldEvaluateAdminAction($ability) ? null : true;
    }

    protected function shouldEvaluateAdminAction(string $ability): bool
    {
        return false;
    }
}
