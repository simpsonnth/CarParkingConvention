<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table): void {
            $table->string('provider_status', 32)->nullable()->after('status')->index();
            $table->string('provider_email_id', 64)->nullable()->after('provider_status')->index();
            $table->text('provider_detail')->nullable()->after('provider_email_id');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('bounced_at')->nullable()->after('delivered_at');
        });

        Schema::create('outbound_email_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outbound_email_id')->nullable()->constrained('outbound_emails')->nullOnDelete();
            $table->string('provider', 32)->default('resend');
            $table->string('event_type', 64)->index();
            $table->string('provider_email_id', 64)->nullable()->index();
            $table->string('svix_id', 128)->nullable()->unique();
            $table->string('to_email')->nullable()->index();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_email_events');

        Schema::table('outbound_emails', function (Blueprint $table): void {
            $table->dropColumn([
                'provider_status',
                'provider_email_id',
                'provider_detail',
                'delivered_at',
                'bounced_at',
            ]);
        });
    }
};
