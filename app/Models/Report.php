<?php

namespace App\Models;

use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'laravel_context' => 'array',
        'client_events' => 'array',
        'network_log' => 'array',
        'dpr' => 'float',
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(ReportNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
