<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('theme', 'light')->update(['theme' => 'atreyu']);
        DB::table('users')->where('theme', 'black')->update(['theme' => 'atreyu-dark']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 32)->default('atreyu')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('theme', 'atreyu')->update(['theme' => 'light']);
        DB::table('users')->where('theme', 'atreyu-dark')->update(['theme' => 'black']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 32)->default('light')->change();
        });
    }
};
