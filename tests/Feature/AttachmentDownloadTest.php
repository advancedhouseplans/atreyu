<?php

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('requires auth', function () {
    $r = Report::factory()->create();
    $a = ReportAttachment::factory()->for($r)->create();
    $this->get("/reports/{$r->id}/attachments/{$a->id}")->assertRedirect('/login');
});

it('streams the file for a matching report', function () {
    Storage::fake('local');
    $u = User::factory()->create();
    $r = Report::factory()->create();
    $path = UploadedFile::fake()->create('a.txt', 1)->storeAs("reports/{$r->id}", 'a.txt', 'local');
    $a = ReportAttachment::factory()->for($r)->create(['disk' => 'local', 'path' => $path, 'mime' => 'text/plain']);

    $this->actingAs($u)->get("/reports/{$r->id}/attachments/{$a->id}")->assertOk();
});

it('403s on mismatched report', function () {
    $u = User::factory()->create();
    [$r1, $r2] = Report::factory()->count(2)->create();
    $a = ReportAttachment::factory()->for($r2)->create();
    $this->actingAs($u)->get("/reports/{$r1->id}/attachments/{$a->id}")->assertStatus(403);
});
