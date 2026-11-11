<?php

namespace App\Actions\Database;

use App\Support\DatabaseBackupSettings;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class CreateDatabaseBackupAction
{
    public function __construct(private readonly DatabaseBackupSettings $settings) {}

    public function handle(): string
    {
        $directory = 'backups';
        $disk = Storage::disk('local');
        $disk->makeDirectory($directory);
        $filename = 'database-'.Carbon::now()->format('Y-m-d_H-i-s').'.'.DB::connection()->getDriverName();
        $path = $disk->path($directory.'/'.$filename);

        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->backupSqlite($path),
            'mysql' => $this->backupMysql($path),
            default => throw new RuntimeException('محرك قاعدة البيانات الحالي غير مدعوم للنسخ الاحتياطي.'),
        };

        $this->removeOldBackups($disk, $directory);

        return $filename;
    }

    private function backupSqlite(string $path): void
    {
        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:' || ! is_file($database)) {
            throw new RuntimeException('ملف قاعدة بيانات SQLite غير موجود لإنشاء النسخة الاحتياطية.');
        }

        if (! copy($database, $path)) {
            throw new RuntimeException('تعذر إنشاء نسخة قاعدة البيانات.');
        }
    }

    private function backupMysql(string $path): void
    {
        $connection = config('database.connections.mysql');
        $process = new Process([
            'mysqldump',
            '--host='.$connection['host'],
            '--port='.$connection['port'],
            '--user='.$connection['username'],
            '--result-file='.$path,
            $connection['database'],
        ], null, ['MYSQL_PWD' => $connection['password']]);
        $process->mustRun();
    }

    private function removeOldBackups(Filesystem $disk, string $directory): void
    {
        $files = collect($disk->files($directory))
            ->sort()
            ->values();
        $keep = $this->settings->get()['retention'];

        $files->take(max(0, $files->count() - $keep))->each(
            fn (string $file): bool => $disk->delete($file),
        );
    }
}
