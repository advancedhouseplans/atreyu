@php
    $endpoint = rtrim((string) config('bug-companion.endpoint'), '/');
    $secret   = (string) config('bug-companion.secret');
    $user     = auth()->user();
    $payload  = [
        'source_slug' => (string) config('bug-companion.source_slug'),
        'env'         => app()->environment(),
        'endpoint'    => $endpoint,
        'user_id'     => $user?->getKey(),
        'user_email'  => $user?->email,
        'user_name'   => $user?->name,
    ];
    $token = \BugCompanion\Support\TokenSigner::sign($payload, $secret, 900);
@endphp
<script src="{{ $endpoint }}/widget.js" defer data-config="{{ $token }}"></script>
