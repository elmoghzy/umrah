<?php

namespace App\Console\Commands;

use App\Actions\Database\CreateDatabaseBackupAction;
use App\Support\DatabaseBackupSettings;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-database-backup {--force : تنفيذ النسخة حتى عند إيقاف النسخ التلقائي}')]
#[Description('إنشاء نسخة احتياطية من قاعدة البيانات')]
class CreateDatabaseBackup extends Command
{
    public function handle(CreateDatabaseBackupAction $action, DatabaseBackupSettings $settings): int
    {
        if (! $settings->get()['automatic'] && ! $this->option('force')) {
            $this->info('النسخ التلقائي متوقف من الإعدادات.');

            return self::SUCCESS;
        }

        $filename = $action->handle();
        $this->info('تم إنشاء النسخة الاحتياطية: '.$filename);

        return self::SUCCESS;
    }
}
