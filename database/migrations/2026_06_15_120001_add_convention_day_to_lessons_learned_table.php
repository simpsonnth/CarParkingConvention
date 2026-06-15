<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons_learned', function (Blueprint $table) {
            $table->string('convention_day')->default('all_days')->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('lessons_learned', function (Blueprint $table) {
            $table->dropColumn('convention_day');
        });
    }
};
