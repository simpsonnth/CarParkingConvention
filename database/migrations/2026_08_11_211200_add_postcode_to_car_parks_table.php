<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_parks', function (Blueprint $table): void {
            $table->string('postcode', 32)->nullable()->after('longitude');
        });

        $postcodes = [
            'North Car Park' => 'Twickenham TW2 7BA',
            'North' => 'Twickenham TW2 7BA',
            'North 2' => 'Isleworth TW7 7LA',
            'West' => 'Twickenham TW2 7RE',
            'Rosebine 1' => 'Twickenham TW2 7PS',
            'Rosebine 2' => 'Twickenham TW2 7PS',
        ];

        foreach ($postcodes as $name => $postcode) {
            DB::table('car_parks')
                ->where('name', $name)
                ->update(['postcode' => $postcode]);
        }
    }

    public function down(): void
    {
        Schema::table('car_parks', function (Blueprint $table): void {
            $table->dropColumn('postcode');
        });
    }
};
