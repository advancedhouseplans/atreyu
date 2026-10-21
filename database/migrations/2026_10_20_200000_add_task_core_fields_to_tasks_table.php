<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('priority', 16)->default('normal')->after('difficulty');
            $table->date('due_date')->nullable()->after('priority');
            $table->timestamp('archived_at')->nullable()->after('description');

            $table->index(['archived_at', 'assignee_id', 'status']);
            $table->index(['priority', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['archived_at', 'assignee_id', 'status']);
            $table->dropIndex(['priority', 'due_date']);
            $table->dropColumn(['priority', 'due_date', 'archived_at']);
        });
    }
};
