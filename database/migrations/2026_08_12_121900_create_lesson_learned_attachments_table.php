<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_learned_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_learned_id')->constrained('lessons_learned')->cascadeOnDelete();
            $table->string('disk', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('kind', 32)->default('file'); // file|voice_note
            $table->timestamps();

            $table->index(['lesson_learned_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_learned_attachments');
    }
};
