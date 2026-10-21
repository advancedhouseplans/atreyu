<?php

namespace BugCompanion\Http\Middleware;

use BugCompanion\Support\ContextBuffer;
use BugCompanion\Support\Credential;
use BugCompanion\Support\Redactor;
use BugCompanion\Support\TokenSigner;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CaptureContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('_atreyu/*') || ! $this->isActive($request)) {
            return $next($request);
        }

        $buffer = new ContextBuffer(
            debugId: (string) Str::uuid(),
            method: $request->method(),
            path: '/'.$request->path(),
            redactor: app(Redactor::class),
        );
        app()->instance(ContextBuffer::class, $buffer);

        try {
            $response = $next($request);
            $this->complete($buffer, $request, $response->getStatusCode());
            $response->headers->set('X-Atreyu-Debug-Id', $buffer->debugId());

            return $response;
        } catch (Throwable $exception) {
            $buffer->addException($exception);
            $this->complete($buffer, $request, 500);
            $this->persist($buffer);
            app()->forgetInstance(ContextBuffer::class);

            throw $exception;
        }
    }

    public function terminate(Request $request, Response $response): void
    {
        $buffer = app()->bound(ContextBuffer::class) ? app(ContextBuffer::class) : null;
        if (! $buffer instanceof ContextBuffer) {
            return;
        }

        $this->persist($buffer);
        app()->forgetInstance(ContextBuffer::class);
    }

    private function complete(ContextBuffer $buffer, Request $request, int $status): void
    {
        $route = $request->route();
        $buffer->setRequestContext([
            'route_name' => $route instanceof Route ? $route->getName() : null,
            'route_uri' => $route instanceof Route ? $route->uri() : null,
            'controller' => $route instanceof Route ? $route->getActionName() : null,
            'route_parameters' => $route instanceof Route ? $route->parameters() : [],
            'query_fields' => array_keys($request->query()),
            'input_fields' => array_keys($request->except(array_keys($request->files->all()))),
        ]);

        if ($user = $request->user()) {
            $buffer->setUser(auth()->getDefaultDriver(), $user);
        }

        $buffer->finish($status);
    }

    private function persist(ContextBuffer $buffer): void
    {
        Cache::put(
            "bug-companion:ctx:{$buffer->debugId()}",
            $buffer->toArray(),
            (int) config('bug-companion.context_ttl', 3600),
        );
    }

    private function isActive(Request $request): bool
    {
        if ($request->cookie('atreyu_active') === '1') {
            return true;
        }

        $credential = Credential::fromConfig();
        $token = $request->header('X-Atreyu-Active');
        if (! $credential || ! is_string($token)) {
            return false;
        }

        $payload = TokenSigner::verify($token, $credential->secret);

        return is_array($payload) && ($payload['key_id'] ?? null) === $credential->keyId;
    }
}
