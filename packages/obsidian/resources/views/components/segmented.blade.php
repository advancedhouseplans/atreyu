@props(['options' => [], 'name', 'label' => null])

<div
    x-data="{
        left: 0,
        width: 0,
        place() {
            const checked = this.$root.querySelector('input:checked')?.closest('label')
            this.left = checked?.offsetLeft ?? 0
            this.width = checked?.offsetWidth ?? 0
        },
    }"
    x-init="place()"
    x-on:change="place()"
    x-on:resize.window="place()"
    role="radiogroup"
    @if ($label) aria-label="{{ $label }}" @endif
    {{ $attributes->only('class')->merge(['class' => 'relative inline-flex rounded-full border border-hairline bg-surface-1 p-0.5']) }}
>
    <span
        aria-hidden="true"
        data-ob-motion
        class="absolute inset-y-0.5 rounded-full bg-surface-3 transition-[left,width] duration-200 ease-ob"
        x-bind:style="{ left: left + 'px', width: width + 'px' }"
    ></span>
    @foreach ($options as $option)
        <label class="relative z-10 inline-flex h-8 max-sm:h-10 cursor-pointer items-center gap-1.5 rounded-full px-3 text-sm font-medium">
            <input
                type="radio"
                value="{{ $option['value'] }}"
                {{ $attributes->except(['class', 'label'])->merge(['name' => $name, 'class' => 'peer sr-only']) }}
            >
            <span class="inline-flex items-center gap-1.5 rounded-full text-fg-2 transition duration-120 ease-ob peer-checked:text-fg peer-focus-visible:shadow-[0_0_0_1px_var(--color-accent),0_0_0_5px_var(--color-accent-soft)]">
                @if (! empty($option['icon']))
                    <x-ob::icon :name="$option['icon']" variant="mini" class="size-4" />
                @endif
                {{ $option['label'] }}
            </span>
        </label>
    @endforeach
</div>
