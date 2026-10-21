<div
    x-data="obToasts"
    x-on:toast.window="add($event.detail)"
    role="region"
    aria-label="Notifications"
    aria-live="polite"
    {{ $attributes->merge(['class' => 'pointer-events-none fixed inset-x-0 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-[60] flex flex-col items-center gap-2 px-4 lg:inset-x-auto lg:w-96 lg:bottom-6 lg:right-6 lg:items-end lg:px-0']) }}
>
    <template x-for="toast in items" :key="toast.id">
        <div
            x-on:mouseenter="pause(toast.id)"
            x-on:mouseleave="resume(toast.id)"
            x-on:focusin="pause(toast.id)"
            x-on:focusout="resume(toast.id)"
            data-ob-motion
            class="glass pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-xl py-2.5 pl-4 pr-2 text-sm text-fg transition duration-200 ease-ob starting:translate-y-2 starting:opacity-0"
        >
            <span x-show="toast.variant === 'success'" class="shrink-0 text-success-fg"><x-ob::icon name="check-circle" variant="mini" class="size-5" /></span>
            <span x-show="toast.variant === 'danger'" class="shrink-0 text-danger-fg"><x-ob::icon name="exclamation-circle" variant="mini" class="size-5" /></span>
            <span x-show="toast.variant === 'warning'" class="shrink-0 text-warning-fg"><x-ob::icon name="exclamation-triangle" variant="mini" class="size-5" /></span>
            <span x-show="toast.variant === 'info'" class="shrink-0 text-info-fg"><x-ob::icon name="information-circle" variant="mini" class="size-5" /></span>
            <p class="min-w-0 flex-1 wrap-break-word" x-text="toast.message"></p>
            <a
                x-show="toast.action"
                :href="toast.action?.href"
                x-text="toast.action?.label"
                class="shrink-0 rounded-lg px-2 py-1 font-medium text-accent-fg hover:bg-surface-3 focus-ring max-sm:py-3"
            ></a>
            <button
                type="button"
                x-on:click="dismiss(toast.id)"
                aria-label="Dismiss notification"
                class="grid size-8 shrink-0 place-items-center rounded-full text-fg-3 transition duration-120 ease-ob hover:bg-surface-3 hover:text-fg focus-ring max-sm:size-11"
            ><x-ob::icon name="x-mark" variant="mini" class="size-4" /></button>
        </div>
    </template>
</div>
