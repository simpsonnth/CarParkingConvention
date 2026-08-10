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
            $table->string('mailer', 32)->nullable()->after('to_email');
        });
    }

    public function down(): void
    {
        Schema::table('outbound_emails', function (Blueprint $table): void {
            $table->dropColumn('mailer');
        });
    }
};
