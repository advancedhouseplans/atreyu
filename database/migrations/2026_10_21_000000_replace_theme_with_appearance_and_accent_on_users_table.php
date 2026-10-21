<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('appearance', 16)->default('system')->after('password');
            $table->string('accent', 16)->default('iris')->after('appearance');
        });

        DB::table('users')
            ->whereIn('theme', ['light', 'cupcake', 'bumblebee', 'emerald', 'corporate', 'retro', 'nord', 'winter'])
            ->update(['appearance' => 'light']);

        DB::table('users')
            ->whereIn('theme', ['dark', 'black', 'synthwave', 'halloween', 'forest', 'luxury', 'night', 'business'])
            ->update(['appearance' => 'dark']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 32)->default('light')->after('password');
        });

        DB::table('users')->where('appearance', 'dark')->update(['theme' => 'black']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['appearance', 'accent']);
        });
    }
};
