<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('reporter_installations')
            ->select('project_id')
            ->groupBy('project_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('project_id')
            ->each(function (int $projectId): void {
                $connections = DB::table('reporter_installations')
                    ->where('project_id', $projectId)
                    ->orderByDesc('id')
                    ->get();
                $keeper = $connections->firstWhere('revoked_at', null) ?? $connections->first();
                $duplicateIds = $connections->where('id', '!=', $keeper->id)->pluck('id');

                DB::table('reports')
                    ->whereIn('reporter_installation_id', $duplicateIds)
                    ->update(['reporter_installation_id' => $keeper->id]);
                DB::table('reporter_installations')->whereIn('id', $duplicateIds)->delete();
            });

        Schema::table('reporter_installations', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'environment']);
            $table->unique('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('reporter_installations', function (Blueprint $table) {
            $table->dropUnique(['project_id']);
            $table->index(['project_id', 'environment']);
        });
    }
};
