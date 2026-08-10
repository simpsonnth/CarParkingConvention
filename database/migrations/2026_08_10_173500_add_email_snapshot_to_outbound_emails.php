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
            $table->string('subject')->nullable()->after('to_email');
            $table->text('body_html')->nullable()->after('subject');
            $table->json('attachments')->nullable()->after('body_html');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table): void {
            $table->dropColumn(['subject', 'body_html', 'attachments']);
        });
    }
};
