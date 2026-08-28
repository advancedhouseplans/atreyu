<?php

namespace App\Models;

use Database\Factories\ReportNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportNote extends Model
{
    /** @use HasFactory<ReportNoteFactory> */
    use HasFactory;

    protected $guarded = [];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
