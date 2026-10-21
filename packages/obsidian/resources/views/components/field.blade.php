@props(['label' => null, 'hint' => null, 'errorKey' => null, 'for' => null])

@php
    $message = $errorKey !== null && isset($errors) ? $errors->first($errorKey) : null;
    $hasError = filled($message);
    $messageId = $for !== null ? $for.'-desc' : null;
@endphp

<div{{ $hasError ? ' data-invalid' : '' }} class="{{ trim($attributes->get('class').' flex flex-col gap-1.5') }}" {{ $attributes->except('class') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="text-sm font-medium text-fg">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if ($hasError)
        <p @if ($messageId) id="{{ $messageId }}" @endif role="alert" class="text-xs text-danger-fg">{{ $message }}</p>
    @elseif ($hint)
        <p @if ($messageId) id="{{ $messageId }}" @endif class="text-xs text-fg-3">{{ $hint }}</p>
    @endif
</div>
