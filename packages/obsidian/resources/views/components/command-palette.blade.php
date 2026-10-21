@props(['actions' => [], 'placeholder' => 'Search or jump to…'])

@php
    use Illuminate\Support\Facades\Blade;

    $iconNames = collect($actions)->pluck('icon')->filter()->push('clock')->unique();
    $icons = $iconNames->mapWithKeys(fn (string $name): array => [
        $name => Blade::render('<x-ob::icon :name="$name" variant="mini" class="size-4 shrink-0 text-fg-3" />', ['name' => $name]),
    ]);
    $rowClasses = 'flex min-h-9 w-full items-center gap-2.5 rounded-lg px-2.5 text-left text-sm text-fg aria-selected:bg-surface-3 max-sm:min-h-11';
@endphp

<div
    x-data="obCommandPalette({ actions: @js(array_values($actions)), icons: @js($icons) })"
    x-on:keydown.window="onShortcut($event)"
    x-on:command-palette-open.window="show()"
    {{ $attributes->whereDoesntStartWith('wire:model') }}
>
    <div
        x-show="open"
        x-cloak
        x-trap.inert.noscroll="open"
        tabindex="-1"
        data-ob-layer="command-palette"
        class="fixed inset-0 z-50 flex focus:outline-none sm:items-start sm:justify-center sm:px-6 sm:pt-[15vh]"
    >
        <div
            x-show="open"
            x-on:click="close()"
            x-transition:enter="transition-opacity duration-[320ms] ease-ob"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200 ease-ob"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            data-ob-motion
            aria-hidden="true"
            class="scrim absolute inset-0"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transition duration-200 ease-ob"
            x-transition:enter-start="opacity-0 sm:scale-[.96]"
            x-transition:enter-end="opacity-100 sm:scale-100"
            x-transition:leave="transition duration-120 ease-ob"
            x-transition:leave-start="opacity-100 sm:scale-100"
            x-transition:leave-end="opacity-0 sm:scale-[.96]"
            data-ob-motion
            role="dialog"
            aria-modal="true"
            aria-label="Command palette"
            class="glass relative flex w-full flex-col max-sm:pt-[env(safe-area-inset-top)] sm:max-h-[70vh] sm:max-w-xl sm:rounded-xl"
        >
            <div class="flex items-center gap-2 border-b border-hairline px-3">
                <x-ob::icon name="magnifying-glass" variant="mini" class="size-4 shrink-0 text-fg-3" />
                <input
                    x-ref="input"
                    type="text"
                    role="combobox"
                    aria-expanded="true"
                    aria-controls="ob-command-list"
                    aria-autocomplete="list"
                    autocomplete="off"
                    placeholder="{{ $placeholder }}"
                    aria-label="{{ $placeholder }}"
                    :value="query"
                    x-on:input="query = $event.target.value"
                    x-on:keydown="onKeydown($event)"
                    {{ $attributes->whereStartsWith('wire:model')->merge(['class' => 'h-12 min-w-0 flex-1 bg-transparent text-sm text-fg placeholder:text-fg-3 focus:outline-none max-sm:h-14']) }}
                >
                <button
                    type="button"
                    x-on:click="close()"
                    aria-label="Close"
                    class="grid size-11 shrink-0 place-items-center rounded-full text-fg-3 hover:text-fg focus-ring sm:hidden"
                ><x-ob::icon name="x-mark" variant="mini" class="size-4" /></button>
            </div>

            <div
                x-ref="list"
                id="ob-command-list"
                role="listbox"
                aria-label="Results"
                x-on:click="onItemClick($event)"
                x-on:mousemove="onHover($event)"
                class="min-h-0 flex-1 overflow-y-auto p-1.5"
            >
                <div x-show="showRecent" role="group" aria-label="Recent">
                    <div aria-hidden="true" class="px-2.5 pb-1 pt-2 text-xs font-medium text-fg-3">Recent</div>
                    <template x-for="entry in recent" :key="entry.href">
                        <a :href="entry.href" wire:navigate role="option" tabindex="-1" aria-selected="false" data-ob-command-item :data-icon="entry.icon" class="{{ $rowClasses }}">
                            <span x-html="iconFor('clock')"></span>
                            <span data-ob-label class="min-w-0 flex-1 truncate" x-text="entry.label"></span>
                        </a>
                    </template>
                </div>

                <div x-show="visibleActions.length > 0" role="group" aria-label="Actions">
                    <div aria-hidden="true" x-show="showRecent || query !== ''" class="px-2.5 pb-1 pt-2 text-xs font-medium text-fg-3">Actions</div>
                    <template x-for="action in visibleActions" :key="action.label + (action.href ?? action.event ?? '')">
                        <div role="presentation">
                            <template x-if="action.href">
                                <a :href="action.href" wire:navigate role="option" tabindex="-1" aria-selected="false" data-ob-command-item :data-icon="action.icon" class="{{ $rowClasses }}">
                                    <span x-html="iconFor(action.icon)"></span>
                                    <span data-ob-label class="min-w-0 flex-1 truncate" x-text="action.label"></span>
                                    <x-ob::kbd x-show="action.shortcut" x-text="action.shortcut" />
                                </a>
                            </template>
                            <template x-if="! action.href">
                                <button type="button" role="option" tabindex="-1" aria-selected="false" data-ob-command-item :data-event="action.event" class="{{ $rowClasses }}">
                                    <span x-html="iconFor(action.icon)"></span>
                                    <span data-ob-label class="min-w-0 flex-1 truncate" x-text="action.label"></span>
                                    <x-ob::kbd x-show="action.shortcut" x-text="action.shortcut" />
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div x-show="query.trim() !== ''">
                    {{ $slot }}
                </div>

                <p x-show="isEmpty" class="px-2.5 py-6 text-center text-sm text-fg-3">No results</p>
            </div>
        </div>
    </div>
</div>
