<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('does not expose a registration route', function () {
    $this->post('/register', [
        'name' => 'X', 'email' => 'x@x.com', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertStatus(404);
});

it('seeds jamie and keith', function () {
    config()->set('bugs.seed_password', 'seedpass');
    putenv('BUGS_SEED_PASSWORD=seedpass');
    Artisan::call('db:seed', ['--force' => true]);
    expect(User::where('email', 'jamie@advancedhouseplans.com')->exists())->toBeTrue();
    expect(User::where('email', 'keith@advancedhouseplans.com')->exists())->toBeTrue();
});
