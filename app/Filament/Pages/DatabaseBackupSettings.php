<?php

namespace App\Filament\Pages;

use App\Actions\Database\CreateDatabaseBackupAction;
use App\Support\DatabaseBackupSettings as BackupSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class DatabaseBackupSettings extends Page
{
    public bool $automatic = true;

    public int $retention = 7;

    /** @var array<int, array{name: string, size: string, date: string}> */
    public array $backups = [];

    protected string $view = 'filament.pages.database-backup-settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'الإعدادات';

    protected static ?string $navigationLabel = 'نسخ قاعدة البيانات';

    protected static ?string $title = 'نسخ قاعدة البيانات';

    public static function canAccess(): bool
    {
        return auth()->user()?->isMainAdmin() ?? false;
    }

    public function mount(BackupSettings $settings): void
    {
        $values = $settings->get();
        $this->automatic = $values['automatic'];
        $this->retention = $values['retention'];
        $this->refreshBackups();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBackup')
                ->label('إنشاء نسخة الآن')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action('createBackup')
                ->requiresConfirmation()
                ->modalHeading('إنشاء نسخة احتياطية')
                ->modalDescription('سيتم حفظ نسخة كاملة من قاعدة البيانات على الخادم.')
                ->modalSubmitActionLabel('إنشاء النسخة'),
        ];
    }

    public function saveSettings(BackupSettings $settings): void
    {
        $this->validate([
            'retention' => ['required', 'integer', 'min:1', 'max:30'],
        ], [
            'retention.required' => 'حدد عدد النسخ المحتفظ بها.',
            'retention.integer' => 'عدد النسخ يجب أن يكون رقمًا.',
            'retention.min' => 'يجب الاحتفاظ بنسخة واحدة على الأقل.',
            'retention.max' => 'لا يمكن الاحتفاظ بأكثر من 30 نسخة.',
        ]);
        $settings->save($this->automatic, $this->retention);
        $this->refreshBackups();

        Notification::make()
            ->title('تم حفظ إعدادات النسخ الاحتياطي')
            ->success()
            ->send();
    }

    public function createBackup(CreateDatabaseBackupAction $action): void
    {
        $action->handle();
        $this->refreshBackups();

        Notification::make()
            ->title('تم إنشاء النسخة الاحتياطية')
            ->success()
            ->send();
    }

    private function refreshBackups(): void
    {
        $disk = Storage::disk('local');
        $this->backups = collect($disk->files('backups'))
            ->sortDesc()
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'size' => number_format($disk->size($path) / 1024, 1).' كيلوبايت',
                'date' => date('d/m/Y H:i', $disk->lastModified($path)),
            ])
            ->values()
            ->all();
    }
}
