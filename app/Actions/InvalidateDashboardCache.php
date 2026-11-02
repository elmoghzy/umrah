<?php

namespace App\Actions;

use Illuminate\Support\Facades\Cache;

class InvalidateDashboardCache
{
    public const VERSION_KEY = 'dashboard.cache.version';

    public function __invoke(): void
    {
        Cache::add(self::VERSION_KEY, 0, now()->addYear());
        Cache::increment(self::VERSION_KEY);
    }

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 0);
    }
}
