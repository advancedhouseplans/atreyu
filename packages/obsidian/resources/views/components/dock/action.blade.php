@props(['icon', 'label'])

<button
    type="button"
    aria-label="{{ $label }}"
    {{ $attributes->merge(['class' => 'flex size-11 items-center justify-center rounded-[14px] bg-accent text-on-accent transition duration-120 ease-ob hover:bg-accent-hover active:scale-[.96] focus-ring']) }}
>
    <x-ob::icon :name="$icon" class="size-6" />
</button>
