<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('users', 'idnp')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('idnp', 13)->nullable()->unique();
            });
        }

        if (! Schema::hasColumn('users', 'driver_license')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('driver_license', 30)->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('user');
            });
        }
    }

    public function down(): void
    {
        // These columns are part of the current base users migration.
        // Keeping rollback non-destructive protects fresh databases.
    }
};
