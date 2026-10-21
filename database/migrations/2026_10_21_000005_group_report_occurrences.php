<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('occurrence_parent_id')->nullable()->after('reporter_installation_id')->constrained('reports')->cascadeOnDelete();
            $table->string('fingerprint', 64)->nullable()->after('occurrence_parent_id');
            $table->index(['source_slug', 'environment', 'fingerprint'], 'reports_group_fingerprint_index');
        });

        DB::table('reports')->orderBy('id')->eachById(function (object $report): void {
            DB::table('reports')->where('id', $report->id)->update([
                'fingerprint' => hash('sha256', 'legacy-report-'.$report->id),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex('reports_group_fingerprint_index');
            $table->dropConstrainedForeignId('occurrence_parent_id');
            $table->dropColumn('fingerprint');
        });
    }
};
