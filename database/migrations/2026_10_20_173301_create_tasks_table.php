<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16);
            $table->string('status', 32)->default('backlog');
            $table->unsignedInteger('position')->default(0);
            $table->unsignedTinyInteger('difficulty')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status', 'position']);
            $table->index(['assignee_id', 'status', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
