<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->json('shared_with_congregation_ids')->nullable()->after('sharing_coach_with_others');
        });

        $rows = DB::table('congregation_numbers_responses')
            ->whereNotNull('shared_with_congregation_id')
            ->get(['id', 'shared_with_congregation_id']);

        foreach ($rows as $row) {
            DB::table('congregation_numbers_responses')
                ->where('id', $row->id)
                ->update([
                    'shared_with_congregation_ids' => json_encode([(int) $row->shared_with_congregation_id]),
                ]);
        }

        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->dropForeign(['shared_with_congregation_id']);
            $table->dropColumn('shared_with_congregation_id');
        });
    }

    public function down(): void
    {
        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->foreignId('shared_with_congregation_id')->nullable()->after('sharing_coach_with_others')->constrained('congregations')->nullOnDelete();
        });

        $rows = DB::table('congregation_numbers_responses')
            ->whereNotNull('shared_with_congregation_ids')
            ->get(['id', 'shared_with_congregation_ids']);

        foreach ($rows as $row) {
            $ids = json_decode($row->shared_with_congregation_ids, true);
            $first = is_array($ids) && $ids !== [] ? (int) reset($ids) : null;
            DB::table('congregation_numbers_responses')
                ->where('id', $row->id)
                ->update(['shared_with_congregation_id' => $first]);
        }

        Schema::table('congregation_numbers_responses', function (Blueprint $table) {
            $table->dropColumn('shared_with_congregation_ids');
        });
    }
};
