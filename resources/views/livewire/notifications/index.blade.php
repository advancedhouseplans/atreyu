<div class="p-5 sm:p-7 lg:p-8">
    <x-ob::page-header title="Notifications">
        @if ($unreadCount)
            <x-slot:actions>
                <x-ob::button variant="secondary" icon="check" wire:click="markAllRead">Mark all read</x-ob::button>
            </x-slot:actions>
        @endif
    </x-ob::page-header>

    <div class="mb-4">
        <x-ob::segmented
            name="notification-view"
            label="Notification view"
            wire:model.live="view"
            :options="[
                ['value' => 'unread', 'label' => 'Unread'],
                ['value' => 'all', 'label' => 'All'],
            ]"
        />
    </div>

    @if ($notifications->isEmpty())
        <x-ob::card :padding="false">
            <x-ob::empty-state icon="bell" heading="You're all caught up">New reports and task assignments will appear here.</x-ob::empty-state>
        </x-ob::card>
    @else
        <x-ob::card :padding="false">
            <div class="divide-y divide-hairline">
                @foreach ($notifications as $notification)
                    @php($isReport = data_get($notification->data, 'kind') === 'report_received')
                    <button type="button" wire:click="openNotification('{{ $notification->id }}')" wire:key="notification-{{ $notification->id }}" class="flex w-full items-start gap-3 px-4 py-4 text-left transition duration-120 ease-ob hover:bg-surface-3 focus-ring sm:px-5">
                        <span class="grid size-9 shrink-0 place-items-center rounded-lg {{ $notification->read_at ? 'bg-surface-3 text-fg-3' : 'bg-accent-soft text-accent-fg' }}">
                            <x-ob::icon :name="$isReport ? 'inbox-stack' : 'user-plus'" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="truncate text-sm font-semibold">{{ data_get($notification->data, 'title', 'Notification') }}</span>
                                @unless ($notification->read_at)
                                    <span class="size-2 shrink-0 rounded-full bg-accent" aria-label="Unread"></span>
                                @endunless
                            </span>
                            <span class="mt-1 block truncate text-sm text-fg-2">{{ data_get($notification->data, 'message') }}</span>
                            <span class="mt-1 block text-xs text-fg-3">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                @endforeach
            </div>
        </x-ob::card>

        @if ($notifications->hasPages())
            <div class="mt-4">{{ $notifications->links() }}</div>
        @endif
    @endif
</div>
