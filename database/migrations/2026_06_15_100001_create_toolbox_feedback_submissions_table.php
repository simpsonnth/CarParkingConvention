<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toolbox_feedback_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submitter_name');
            $table->string('submitter_email');
            $table->string('submitter_phone')->nullable();
            $table->text('feedback');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toolbox_feedback_submissions');
    }
};
