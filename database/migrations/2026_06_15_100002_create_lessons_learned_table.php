<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons_learned', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_name');
            $table->string('category');
            $table->string('title')->nullable();
            $table->text('worked_well')->nullable();
            $table->text('didnt_work_well')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons_learned');
    }
};
