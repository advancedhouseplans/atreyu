<?php

namespace BugCompanion\Http\Controllers;

use Symfony\Component\HttpFoundation\Response;

class DeactivateTelemetryController
{
    public function __invoke(): Response
    {
        return response()->noContent()->withoutCookie('atreyu_active');
    }
}
