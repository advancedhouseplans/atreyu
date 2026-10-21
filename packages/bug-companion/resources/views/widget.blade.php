@php
    $endpoint = rtrim((string) config('bug-companion.url'), '/');
    $credential = \BugCompanion\Support\Credential::fromConfig();
    $user     = auth()->user();
    $payload  = [
        'source_slug' => (string) config('bug-companion.project'),
        'key_id'      => $credential?->keyId,
        'env'         => app()->environment(),
        'endpoint'    => $endpoint,
        'source_url'  => url('/'),
        'launcher'    => (bool) config('bug-companion.launcher', true),
        'debug_id'    => app()->bound(\BugCompanion\Support\ContextBuffer::class)
            ? app(\BugCompanion\Support\ContextBuffer::class)->debugId()
            : null,
        'debug_ts'    => now()->getTimestampMs(),
        'diagnostic_window_ms' => (int) config('bug-companion.diagnostic_window', 300) * 1000,
        'diagnostic_requests' => (int) config('bug-companion.diagnostic_requests', 5),
        'activation_refresh_ms' => (int) config('bug-companion.activation_refresh', 60) * 1000,
        'user_id'     => $user?->getKey(),
        'user_email'  => $user?->email,
        'user_name'   => $user?->name,
    ];
    $token = $credential
        ? \BugCompanion\Support\TokenSigner::sign($payload, $credential->secret, (int) config('bug-companion.token_ttl', 900))
        : null;
@endphp
@if ($endpoint !== '' && $token)
    <script src="{{ $endpoint }}/widget.js" defer data-atreyu-config="{{ $token }}"></script>
@endif
