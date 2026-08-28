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
        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->string('source_slug', 32)->index();
            $t->string('environment', 32);
            $t->unsignedBigInteger('reporter_user_id')->nullable();
            $t->string('reporter_email')->index();
            $t->string('reporter_name')->nullable();
            $t->string('type', 16);
            $t->string('severity', 16);
            $t->string('status', 16)->default('new')->index();
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title');
            $t->text('description')->nullable();
            $t->text('url');
            $t->text('referrer')->nullable();
            $t->string('app_version')->nullable();
            $t->text('user_agent')->nullable();
            $t->string('browser')->nullable();
            $t->string('browser_version')->nullable();
            $t->string('os')->nullable();
            $t->string('os_version')->nullable();
            $t->string('device')->nullable();
            $t->string('screen_wh', 32)->nullable();
            $t->string('viewport_wh', 32)->nullable();
            $t->decimal('dpr', 4, 2)->nullable();
            $t->string('timezone')->nullable();
            $t->string('locale', 16)->nullable();
            $t->json('laravel_context')->nullable();
            $t->json('client_events')->nullable();
            $t->json('network_log')->nullable();
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
