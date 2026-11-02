<?php

namespace App\Observers;

use App\Actions\InvalidateDashboardCache;
use Illuminate\Database\Eloquent\Model;

class DashboardCacheObserver
{
    public function created(Model $model): void
    {
        $this->invalidate();
    }

    public function updated(Model $model): void
    {
        $this->invalidate();
    }

    public function deleted(Model $model): void
    {
        $this->invalidate();
    }

    public function restored(Model $model): void
    {
        $this->invalidate();
    }

    public function forceDeleted(Model $model): void
    {
        $this->invalidate();
    }

    private function invalidate(): void
    {
        app(InvalidateDashboardCache::class)();
    }
}
