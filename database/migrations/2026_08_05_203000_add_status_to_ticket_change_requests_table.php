<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_change_requests', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->index()->after('notes');
            $table->timestamp('actioned_at')->nullable()->after('status');
            $table->foreignId('actioned_by')
                ->nullable()
                ->after('actioned_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_change_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actioned_by');
            $table->dropColumn(['status', 'actioned_at']);
        });
    }
};
