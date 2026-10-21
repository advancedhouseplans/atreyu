@use('App\Enums\Appearance')
@php($user = auth()->user())
lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-appearance="{{ $user?->appearance->value ?? 'system' }}" data-mode="{{ ($user?->appearance ?? Appearance::System) === Appearance::Light ? 'light' : 'dark' }}" data-accent="{{ $user?->accent->value ?? 'iris' }}"
