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
        Schema::table('congregations', function (Blueprint $table) {
            $table->dropForeign(['car_park_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE congregations MODIFY car_park_id BIGINT UNSIGNED NULL');
        } else {
            // SQLite: recreate table with nullable car_park_id
            DB::statement('CREATE TABLE congregations_new (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, car_park_id BIGINT UNSIGNED NULL, created_at DATETIME NULL, updated_at DATETIME NULL, uuid VARCHAR(255) NOT NULL)');
            DB::statement('INSERT INTO congregations_new (id, name, car_park_id, created_at, updated_at, uuid) SELECT id, name, car_park_id, created_at, updated_at, uuid FROM congregations');
            Schema::drop('congregations');
            DB::statement('ALTER TABLE congregations_new RENAME TO congregations');
            DB::statement('CREATE UNIQUE INDEX congregations_uuid_unique ON congregations (uuid)');
        }

        Schema::table('congregations', function (Blueprint $table) {
            $table->foreign('car_park_id')->references('id')->on('car_parks')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('congregations', function (Blueprint $table) {
            $table->dropForeign(['car_park_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE congregations MODIFY car_park_id BIGINT UNSIGNED NOT NULL');
        }
        // SQLite down: would require table recreation; omitted for simplicity

        Schema::table('congregations', function (Blueprint $table) {
            $table->foreign('car_park_id')->references('id')->on('car_parks')->cascadeOnDelete();
        });
    }
};
