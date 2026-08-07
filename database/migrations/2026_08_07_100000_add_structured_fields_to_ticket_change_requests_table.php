<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_change_requests', function (Blueprint $table): void {
            $table->string('request_type', 32)->nullable()->after('id')->index();
            $table->foreignId('parking_registration_id')
                ->nullable()
                ->after('request_type')
                ->constrained('parking_registrations')
                ->nullOnDelete();
            $table->string('notification_email')->nullable()->after('congregation');
            $table->json('payload')->nullable()->after('notes');
            $table->json('before_snapshot')->nullable()->after('payload');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_change_requests', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parking_registration_id');
            $table->dropColumn([
                'request_type',
                'notification_email',
                'payload',
                'before_snapshot',
            ]);
        });
    }
};
