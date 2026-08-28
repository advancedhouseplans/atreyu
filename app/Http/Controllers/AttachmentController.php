<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function show(Report $report, ReportAttachment $attachment): StreamedResponse
    {
        abort_if($attachment->report_id !== $report->id, 403);

        return Storage::disk($attachment->disk)->response($attachment->path, null, ['Content-Type' => $attachment->mime]);
    }
}
