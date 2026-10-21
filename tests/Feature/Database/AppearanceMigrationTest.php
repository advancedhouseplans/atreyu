<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('maps old daisyui themes to appearance', function () {
    $migration = require database_path('migrations/2026_10_21_000000_replace_theme_with_appearance_and_accent_on_users_table.php');
    $migration->down();
    $ids = collect(['night', 'corporate', 'atreyu'])->mapWithKeys(fn (string $theme) => [$theme => DB::table('users')->insertGetId([
        'name' => $theme, 'email' => "{$theme}@atreyu.test", 'password' => 'x', 'theme' => $theme, 'created_at' => now(), 'updated_at' => now(),
    ])])->all();
    $migration->up();
    expect(DB::table('users')->where('id', $ids['night'])->value('appearance'))->toBe('dark')
        ->and(DB::table('users')->where('id', $ids['corporate'])->value('appearance'))->toBe('light')
        ->and(DB::table('users')->where('id', $ids['atreyu'])->value('appearance'))->toBe('system')
        ->and(DB::table('users')->where('id', $ids['night'])->value('accent'))->toBe('iris')
        ->and(Schema::hasColumn('users', 'theme'))->toBeFalse();
});
