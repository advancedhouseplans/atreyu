<?php

namespace BugCompanion\Http\Middleware;

use BugCompanion\Support\ContextBuffer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CaptureContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $debugId = (string) Str::uuid();
        $buffer = new ContextBuffer($debugId, $request->method(), $request->path());
        app()->instance(ContextBuffer::class, $buffer);

        $response = $next($request);

        if ($route = $request->route()) {
            $buffer->setRouteName($route->getName() ?: $route->uri());
        }

        if ($user = $request->user()) {
            $buffer->setAuth(auth()->getDefaultDriver(), $user->getKey(), $user->email ?? null);
        }

        $response->headers->set('X-Debug-Id', $debugId);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $buffer = app()->bound(ContextBuffer::class) ? app(ContextBuffer::class) : null;
        if (! $buffer instanceof ContextBuffer) {
            return;
        }
        $id = $response->headers->get('X-Debug-Id');
        if (! $id) {
            return;
        }
        Cache::put("bug-companion:ctx:{$id}", $buffer->toArray(), (int) config('bug-companion.context_ttl', 3600));
    }
}
