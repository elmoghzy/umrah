<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 30)->default('branch_employee');
            });
        }

        if (! Schema::hasColumn('users', 'broker_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('broker_id')->nullable()->constrained('brokers')->nullOnDelete();
            });
        }

        if (! Schema::hasForeignKey('users', ['broker_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreign('broker_id')->references('id')->on('brokers')->nullOnDelete();
            });
        }

        if (! Schema::hasIndex('users', ['broker_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->index('broker_id');
            });
        }

        if (! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_active')->default(true);
            });
        }

        DB::table('users')->update(['role' => 'main_admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasForeignKey('users', ['broker_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropForeign(['broker_id']);
            });
        }

        if (Schema::hasIndex('users', ['broker_id'])) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropIndex(['broker_id']);
            });
        }

        if (Schema::hasColumn('users', 'broker_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('broker_id');
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }
};
