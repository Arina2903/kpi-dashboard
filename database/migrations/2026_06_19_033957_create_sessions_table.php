<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0001_01_01_000000_create_users_table already creates this table
        // (Laravel 11's stock skeleton bundles it with the users migration).
        // On any environment where this migration already ran before that
        // was noticed, the row stays recorded and this is a no-op forever;
        // on a genuinely fresh database (a disposable CI/test target) this
        // guard is what stops "table already exists" from failing the whole
        // migrate run. Never touch `down()` for a table this migration
        // doesn't actually own.
        if (Schema::hasTable('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        // Owned by 0001_01_01_000000_create_users_table; dropping it here
        // would tear down a table this migration never actually created.
    }
};
