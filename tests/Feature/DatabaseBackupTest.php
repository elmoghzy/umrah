<?php

namespace Tests\Feature;

use App\Models\Broker;
use App\Models\User;
use App\Support\DatabaseBackupSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_main_admin_can_open_backup_settings_and_download_a_backup(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('backups/database-2026-11-11_02-00-00.mysql', 'backup');
        $manager = User::factory()->create(['role' => User::ROLE_MAIN_ADMIN, 'is_active' => true]);
        $branch = Broker::query()->create(['name' => 'فرع الاختبار']);
        $employee = User::factory()->create([
            'role' => User::ROLE_BRANCH_EMPLOYEE,
            'broker_id' => $branch->getKey(),
            'is_active' => true,
        ]);

        $this->actingAs($manager);

        $this->get('/admin/database-backup-settings')->assertOk()->assertSee('نسخ قاعدة البيانات');
        $this->get(route('admin.database-backups.download', [
            'filename' => 'database-2026-11-11_02-00-00.mysql',
        ]))->assertDownload('database-2026-11-11_02-00-00.mysql');

        $this->actingAs($employee);

        $this->get('/admin/database-backup-settings')->assertForbidden();
        $this->get(route('admin.database-backups.download', [
            'filename' => 'database-2026-11-11_02-00-00.mysql',
        ]))->assertForbidden();
    }

    public function test_backup_settings_are_saved_with_safe_retention_limits(): void
    {
        Storage::fake('local');
        $settings = app(DatabaseBackupSettings::class);

        $settings->save(false, 99);

        $this->assertSame(['automatic' => false, 'retention' => 30], $settings->get());
    }
}
