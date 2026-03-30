<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->dropUnique(['congregation_id']);
        });

        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->softDeletes();
            $table->index('congregation_id');
        });
    }

    public function down(): void
    {
        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['congregation_id']);
        });

        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->unique('congregation_id');
        });
    }
};
