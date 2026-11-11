<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class DatabaseBackupSettings
{
    private const PATH = 'database-backup-settings.json';

    /** @return array{automatic: bool, retention: int} */
    public function get(): array
    {
        if (! Storage::disk('local')->exists(self::PATH)) {
            return $this->defaults();
        }

        $settings = json_decode(Storage::disk('local')->get(self::PATH), true, 512, JSON_THROW_ON_ERROR);

        return [
            'automatic' => (bool) ($settings['automatic'] ?? true),
            'retention' => max(1, min(30, (int) ($settings['retention'] ?? 7))),
        ];
    }

    public function save(bool $automatic, int $retention): void
    {
        Storage::disk('local')->put(self::PATH, json_encode([
            'automatic' => $automatic,
            'retention' => max(1, min(30, $retention)),
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /** @return array{automatic: bool, retention: int} */
    private function defaults(): array
    {
        return ['automatic' => true, 'retention' => 7];
    }
}
