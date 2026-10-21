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
            $table->timestamp('archived_at')->nullable()->after('network_log')->index();
        });

        DB::table('reports')
            ->whereIn('status', ['done', 'wontfix'])
            ->update(['archived_at' => now()]);

        DB::table('reports')
            ->whereIn('id', DB::table('tasks')->select('report_id')->whereNotNull('report_id')->where('status', 'done'))
            ->update(['archived_at' => now()]);

        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['status']);
            $table->dropConstrainedForeignId('assignee_id');
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status', 16)->default('new')->index();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
        });

        DB::table('reports')->whereNotNull('archived_at')->update(['status' => 'wontfix']);
        DB::table('reports')
            ->whereIn('id', DB::table('tasks')->select('report_id')->whereNotNull('report_id'))
            ->update(['status' => 'in_progress']);
        DB::table('reports')
            ->whereIn('id', DB::table('tasks')->select('report_id')->whereNotNull('report_id')->where('status', 'done'))
            ->update(['status' => 'done']);

        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
