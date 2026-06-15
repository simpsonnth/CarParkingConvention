<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('toolbox_feedback_submissions', function (Blueprint $table) {
            $table->boolean('added_to_toolbox_talk')->default(false)->after('feedback');
            $table->string('toolbox_talk_day')->nullable()->after('added_to_toolbox_talk');
        });
    }

    public function down(): void
    {
        Schema::table('toolbox_feedback_submissions', function (Blueprint $table) {
            $table->dropColumn(['added_to_toolbox_talk', 'toolbox_talk_day']);
        });
    }
};
