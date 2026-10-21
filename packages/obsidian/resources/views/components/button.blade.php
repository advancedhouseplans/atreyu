@blaze
@props([
    'variant' => 'secondary',
    'size' => 'md',
    'icon' => null,
    'href' => null,
    'navigate' => false,
    'loading' => null,
    'label' => null,
    'type' => 'button',
])

@php
    use Illuminate\Support\Str;

    $trailingIcon = $attributes->get('icon:trailing');
    $iconOnly = $slot->isEmpty();

    if ($iconOnly && blank($label)) {
        throw new InvalidArgumentException('Icon-only ob::button requires a label.');
    }

    $variantClasses = [
        'primary' => 'bg-accent text-on-accent hover:bg-accent-hover',
        'secondary' => 'bg-surface-1 text-fg border border-hairline-strong hover:bg-surface-3',
        'ghost' => 'text-fg-2 hover:bg-surface-3 hover:text-fg',
        'danger' => 'bg-danger-soft text-danger-fg hover:bg-danger hover:text-white',
    ][$variant];

    $sizeClasses = $iconOnly
        ? ['sm' => 'size-8', 'md' => 'size-9 max-sm:size-11', 'lg' => 'size-11'][$size]
        : ['sm' => 'h-8 px-3 text-[13px]', 'md' => 'h-9 px-4 text-sm max-sm:h-11', 'lg' => 'h-11 px-5 text-[15px]'][$size];

    $iconSize = $size === 'lg' ? 'size-5' : 'size-4';
    $wireClick = $attributes->whereStartsWith('wire:click')->first();
    $isLoadingAware = $wireClick !== null && $loading !== false;

    $leadingIconClasses = $iconSize.($isLoadingAware ? ' in-data-loading:hidden' : '');

    $classes = "inline-flex items-center justify-center gap-2 rounded-full font-medium transition duration-120 ease-ob active:scale-[.98] focus-ring disabled:opacity-50 {$variantClasses} {$sizeClasses}";

    $rootAttributes = $attributes->except('icon:trailing')->merge(['class' => $classes]);

    if ($isLoadingAware) {
        $rootAttributes = $rootAttributes->merge([
            'wire:loading.attr' => 'data-loading',
            'wire:target' => trim(Str::before($wireClick, '(')),
        ]);
    }

    if ($iconOnly) {
        $rootAttributes = $rootAttributes->merge(['aria-label' => $label]);
    }

    if ($href !== null) {
        $rootAttributes = $rootAttributes->merge(['href' => $href]);

        if ($navigate) {
            $rootAttributes = $rootAttributes->merge(['wire:navigate' => true]);
        }
    } else {
        $rootAttributes = $rootAttributes->merge(['type' => $type]);
    }
@endphp

@php ob_start(); @endphp
@if ($href !== null)
    <a {{ $rootAttributes }}>
@else
    <button {{ $rootAttributes }}>
@endif
    @if ($isLoadingAware)
        <x-ob::spinner class="hidden in-data-loading:inline-flex" />
    @endif
    @if ($icon)
        <x-ob::icon :name="$icon" variant="mini" :class="$leadingIconClasses" />
    @endif
    {{ $slot }}
    @if ($trailingIcon)
        <x-ob::icon :name="$trailingIcon" variant="mini" :class="$iconSize" />
    @endif
@if ($href !== null)
    </a>
@else
    </button>
@endif
@php $control = ob_get_clean(); @endphp

{{-- Blaze reads component tags statically, so a tooltip tag pair cannot straddle a conditional; wrap the buffered markup instead. --}}
@if ($iconOnly)
    <x-ob::tooltip :text="$label">{!! $control !!}</x-ob::tooltip>
@else
    {!! $control !!}
@endif
