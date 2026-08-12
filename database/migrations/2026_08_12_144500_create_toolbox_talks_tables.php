<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolbox_talks', function (Blueprint $table) {
            $table->id();
            $table->date('talk_date');
            $table->string('scope', 16); // core | park
            $table->foreignId('car_park_id')->nullable()->constrained('car_parks')->nullOnDelete();
            $table->string('deck_key', 64);
            $table->timestamps();

            $table->unique(['talk_date', 'deck_key']);
            $table->index(['talk_date', 'scope']);
        });

        Schema::create('toolbox_talk_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toolbox_talk_id')->constrained('toolbox_talks')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['toolbox_talk_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toolbox_talk_slides');
        Schema::dropIfExists('toolbox_talks');
    }
};
