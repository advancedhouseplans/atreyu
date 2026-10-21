@props(['label' => null, 'hint' => null])

<div x-data="{ show: false }" class="contents">
    <x-ob::input
        :label="$label"
        :hint="$hint"
        type="password"
        x-bind:type="show ? 'text' : 'password'"
        {{ $attributes }}
    >
        <button
            type="button"
            x-on:click="show = ! show"
            aria-label="Show password"
            x-bind:aria-label="show ? 'Hide password' : 'Show password'"
            class="inline-flex size-6 max-sm:size-11 items-center justify-center rounded-full transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring"
        >
            <x-ob::icon name="eye" variant="mini" class="size-4" x-show="! show" />
            <x-ob::icon name="eye-slash" variant="mini" class="size-4" x-show="show" x-cloak />
        </button>
    </x-ob::input>
</div>
