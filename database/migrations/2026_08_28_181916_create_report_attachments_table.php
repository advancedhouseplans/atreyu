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
        Schema::create('report_attachments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $t->string('disk', 32);
            $t->string('path');
            $t->string('mime', 128);
            $t->unsignedBigInteger('size');
            $t->string('kind', 32);
            $t->timestamps();
            $t->index('report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_attachments');
    }
};
