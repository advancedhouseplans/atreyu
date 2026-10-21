{{-- Kitchen-sink page at /_obsidian (local only). Alpine-only demos: there is no Livewire component on this page. --}}
@php
    use Illuminate\Support\Js;
    use Illuminate\Support\Str;

    $page = url('/_obsidian');

    $longTitle = Str::substr(str_repeat('Checkout button stays disabled after the address form autofills on Safari, so customers cannot place orders ', 2), 0, 200);
    $token = str_repeat('a', 40);
    $longProject = 'Advanced House Plans customer portal and checkout replatforming (phase two)';

    $tokenGroups = [
        'Surfaces' => ['canvas', 'surface-1', 'surface-2', 'surface-3', 'hairline', 'hairline-strong'],
        'Text' => ['fg', 'fg-2', 'fg-3'],
        'Accent' => ['accent', 'accent-hover', 'accent-soft', 'accent-fg', 'on-accent'],
        'Status' => ['danger', 'danger-soft', 'danger-fg', 'warning', 'warning-soft', 'warning-fg', 'success', 'success-soft', 'success-fg', 'info', 'info-soft', 'info-fg', 'neutral', 'neutral-soft', 'neutral-fg'],
    ];

    $accents = ['iris' => '#6A5AF0', 'blue' => '#2563EB', 'teal' => '#14B8A6', 'green' => '#22C55E', 'amber' => '#F59E0B', 'rose' => '#E11D48'];

    $typeScale = [
        ['class' => 'text-[32px] font-semibold tracking-[-0.02em]', 'size' => '32 / 600'],
        ['class' => 'text-2xl font-semibold tracking-[-0.02em]', 'size' => '24 / 600'],
        ['class' => 'text-xl font-semibold tracking-[-0.02em]', 'size' => '20 / 600'],
        ['class' => 'text-base font-medium', 'size' => '16 / 500'],
        ['class' => 'text-sm', 'size' => '14 / 400'],
        ['class' => 'text-[13px]', 'size' => '13 / 400'],
        ['class' => 'text-xs text-fg-2', 'size' => '12 / 400'],
    ];

    $people = [
        ['value' => 1, 'label' => 'Ada Lovelace', 'description' => 'Engineering'],
        ['value' => 2, 'label' => 'Grace Hopper', 'description' => 'Platform'],
        ['value' => 3, 'label' => 'Katherine Johnson', 'description' => 'Data'],
        ['value' => 4, 'label' => 'Margaret Hamilton', 'description' => 'Mobile'],
        ['value' => 5, 'label' => 'Radia Perlman', 'description' => 'Infrastructure'],
    ];

    $statuses = [
        ['value' => 'new', 'label' => 'New'],
        ['value' => 'triaged', 'label' => 'Triaged'],
        ['value' => 'in_progress', 'label' => 'In progress'],
        ['value' => 'resolved', 'label' => 'Resolved'],
    ];

    $reports = [
        ['id' => 'BUG-1042', 'title' => 'Plan PDF download times out for large sets', 'project' => 'Storefront', 'status' => 'In progress', 'color' => 'info', 'priority' => 'High', 'priorityColor' => 'danger'],
        ['id' => 'BUG-1039', 'title' => 'Saved plans list shows duplicates after login', 'project' => 'Accounts', 'status' => 'Triaged', 'color' => 'warning', 'priority' => 'Medium', 'priorityColor' => 'warning'],
        ['id' => 'BUG-1031', 'title' => 'Floor plan zoom resets on rotate', 'project' => 'Viewer', 'status' => 'Resolved', 'color' => 'success', 'priority' => 'Low', 'priorityColor' => 'neutral'],
        ['id' => 'BUG-1077', 'title' => $longTitle, 'project' => $longProject, 'status' => 'New', 'color' => 'neutral', 'priority' => 'High', 'priorityColor' => 'danger'],
    ];

    $board = [
        ['id' => 'todo', 'title' => 'To do', 'cards' => [['id' => 1, 'title' => 'Audit checkout copy for sentence case'], ['id' => 2, 'title' => $longTitle]]],
        ['id' => 'doing', 'title' => 'In progress', 'cards' => [['id' => 3, 'title' => 'Migrate reports index to Obsidian']]],
        ['id' => 'done', 'title' => 'Done', 'cards' => []],
    ];

    $paletteActions = [
        ['label' => 'Go to tokens', 'href' => $page.'#tokens', 'icon' => 'swatch'],
        ['label' => 'Go to forms', 'href' => $page.'#forms', 'icon' => 'pencil-square'],
        ['label' => 'Go to data', 'href' => $page.'#data', 'icon' => 'table-cells'],
        ['label' => 'Show a toast', 'event' => 'showcase-toast', 'icon' => 'bell'],
        ['label' => 'Open the demo modal', 'event' => 'showcase-modal', 'icon' => 'window', 'shortcut' => 'M'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Obsidian</title>
    <x-ob::theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
<x-ob::app-shell
    x-on:showcase-toast.window="$dispatch('toast', { message: 'Report saved', variant: 'success' })"
    x-on:showcase-modal.window="$dispatch('modal-open', { name: 'demo-modal' })"
>
    <x-slot:sidebar>
        <x-ob::sidebar>
            <x-slot:brand>
                <span class="text-sm font-semibold tracking-[-0.01em]">Obsidian</span>
            </x-slot:brand>

            <x-ob::sidebar.group heading="Foundations">
                <x-ob::sidebar.item :href="$page.'#tokens'" icon="swatch" label="Tokens" :active="true" />
                <x-ob::sidebar.item :href="$page.'#type'" icon="language" label="Type" :active="false" />
            </x-ob::sidebar.group>
            <x-ob::sidebar.group heading="Components">
                <x-ob::sidebar.item :href="$page.'#buttons'" icon="cursor-arrow-rays" label="Buttons" :active="false" />
                <x-ob::sidebar.item :href="$page.'#forms'" icon="pencil-square" label="Forms" :active="false" />
                <x-ob::sidebar.item :href="$page.'#overlays'" icon="square-2-stack" label="Overlays" :active="false" badge="3" />
                <x-ob::sidebar.item :href="$page.'#layout'" icon="squares-2x2" label="Layout" :active="false" />
                <x-ob::sidebar.item :href="$page.'#data'" icon="table-cells" label="Data" :active="false" badge="12" />
                <x-ob::sidebar.item :href="$page.'#stress'" icon="bolt" label="Stress" :active="false" />
            </x-ob::sidebar.group>

            <x-slot:footer>
                <div class="flex items-center gap-2.5 px-1.5 py-1">
                    <x-ob::avatar initials="JH" size="sm" />
                    <span x-show="!collapsed" class="min-w-0 truncate text-sm text-fg-2">Jamie Henrickson</span>
                </div>
            </x-slot:footer>
        </x-ob::sidebar>
    </x-slot:sidebar>

    <div class="mx-auto max-w-5xl space-y-14 px-4 pb-16 pt-6 sm:px-6 sm:pt-10 lg:px-8">
        <div>
            <x-ob::page-header title="Obsidian" description="Every component in one place, in every mode and accent.">
                <x-slot:actions>
                    <x-ob::button variant="ghost" icon="magnifying-glass" x-on:click="$dispatch('command-palette-open')">Search <x-ob::kbd>⌘K</x-ob::kbd></x-ob::button>
                </x-slot:actions>
            </x-ob::page-header>

            {{-- Theme toolbar: mode picker plus accent swatches, both through $store.obTheme.apply(). --}}
            <div
                x-data="{ appearance: 'system' }"
                x-init="appearance = $store.obTheme.appearance; $watch('appearance', (value) => $store.obTheme.apply({ appearance: value }))"
                class="flex flex-wrap items-center gap-x-6 gap-y-3"
            >
                <x-ob::segmented
                    name="appearance"
                    label="Appearance"
                    x-model="appearance"
                    :options="[
                        ['value' => 'system', 'label' => 'System', 'icon' => 'computer-desktop'],
                        ['value' => 'light', 'label' => 'Light', 'icon' => 'sun'],
                        ['value' => 'dark', 'label' => 'Dark', 'icon' => 'moon'],
                    ]"
                />
                <div role="group" aria-label="Accent" class="flex items-center gap-1">
                    @foreach ($accents as $accent => $hex)
                        <button
                            type="button"
                            aria-label="{{ ucfirst($accent) }} accent"
                            x-bind:aria-pressed="$store.obTheme.accent === @js($accent)"
                            x-on:click="$store.obTheme.apply({ accent: @js($accent) })"
                            class="group grid size-8 place-items-center rounded-full focus-ring max-sm:size-11"
                        >
                            <span
                                class="size-5 rounded-full ring-offset-2 ring-offset-canvas transition duration-120 ease-ob group-aria-pressed:ring-2 group-aria-pressed:ring-fg"
                                style="background-color: {{ $hex }}"
                            ></span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <x-ob::section id="tokens" heading="Tokens" description="Colour tokens resolve per mode and accent.">
            <div class="space-y-6">
                @foreach ($tokenGroups as $group => $names)
                    <div>
                        <h3 class="mb-2 text-xs font-medium text-fg-3">{{ $group }}</h3>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                            @foreach ($names as $name)
                                <div class="min-w-0">
                                    <div class="h-12 rounded-lg border border-hairline" style="background-color: var(--color-{{ $name }})"></div>
                                    <div class="mt-1.5 truncate font-mono text-xs text-fg-2">{{ $name }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ob::section>

        <x-ob::section id="type" heading="Type scale" description="Inter for UI, JetBrains Mono for IDs and logs.">
            <x-ob::card class="space-y-3">
                @foreach ($typeScale as $step)
                    <div class="flex items-baseline gap-4">
                        <span class="w-16 shrink-0 font-mono text-xs text-fg-3">{{ $step['size'] }}</span>
                        <span class="{{ $step['class'] }} min-w-0 truncate">Triage the reports queue</span>
                    </div>
                @endforeach
                <x-ob::separator />
                <p class="font-mono text-[13px] text-fg-2">BUG-1042 · TypeError: Cannot read properties of undefined (reading 'id')</p>
                <p class="text-sm tabular-nums text-fg-2">Counts use tabular numbers: 1,042 · 318 · 77</p>
            </x-ob::card>
        </x-ob::section>

        <x-ob::section id="buttons" heading="Buttons and primitives">
            <x-ob::card class="space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ob::button variant="primary" icon="plus">New report</x-ob::button>
                    <x-ob::button>Secondary</x-ob::button>
                    <x-ob::button variant="ghost">Ghost</x-ob::button>
                    <x-ob::button variant="danger" icon="trash">Delete</x-ob::button>
                    <x-ob::button disabled>Disabled</x-ob::button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ob::button size="sm">Small</x-ob::button>
                    <x-ob::button size="md">Medium</x-ob::button>
                    <x-ob::button size="lg" icon:trailing="arrow-right">Large</x-ob::button>
                    <x-ob::button :href="$page.'#buttons'" icon="link">Link button</x-ob::button>
                    <x-ob::button variant="ghost" icon="cog-6-tooth" label="Settings" />
                    <x-ob::button icon="ellipsis-horizontal" label="More actions" />
                    <x-ob::tooltip text="Copy the report link">
                        <x-ob::button size="sm" icon="clipboard">Copy</x-ob::button>
                    </x-ob::tooltip>
                </div>
                <x-ob::separator label="Badges, avatars, keys" />
                <div class="flex flex-wrap items-center gap-2">
                    <x-ob::badge>Neutral</x-ob::badge>
                    <x-ob::badge color="accent">Accent</x-ob::badge>
                    <x-ob::badge color="info" dot>In progress</x-ob::badge>
                    <x-ob::badge color="success" dot>Resolved</x-ob::badge>
                    <x-ob::badge color="warning" dot>Triaged</x-ob::badge>
                    <x-ob::badge color="danger" dot>High</x-ob::badge>
                </div>
                <div class="flex flex-wrap items-center gap-4">
                    <x-ob::avatar initials="AL" size="sm" />
                    <x-ob::avatar initials="GH" />
                    <x-ob::avatar initials="KJ" size="lg" />
                    <x-ob::avatar.stack>
                        <x-ob::avatar initials="AL" />
                        <x-ob::avatar initials="GH" />
                        <x-ob::avatar initials="MH" />
                    </x-ob::avatar.stack>
                    <span class="inline-flex items-center gap-1"><x-ob::kbd>⌘</x-ob::kbd><x-ob::kbd>K</x-ob::kbd></span>
                    <x-ob::spinner class="text-fg-3" />
                    <x-ob::separator vertical class="h-6" />
                    <span class="text-sm text-fg-2">Vertical separator</span>
                </div>
            </x-ob::card>
        </x-ob::section>

        <x-ob::section id="forms" heading="Forms" description="Bound with x-model only; nothing here talks to a server.">
            <x-ob::card>
                <div
                    x-data="{ title: '', email: '', password: '', notes: '', status: 'new', plan: 'team', notify: true, agree: false, assignee: null, watchers: [2] }"
                    class="grid gap-5 sm:grid-cols-2"
                >
                    <x-ob::input label="Title" placeholder="Short summary" x-model="title" hint="Sentence case, under 80 characters." />
                    <x-ob::input label="Search" icon="magnifying-glass" clearable placeholder="Find a report" x-model="email" />
                    <x-ob::input label="Budget" prefix="$" suffix="USD" placeholder="0.00" x-model="email" />
                    <x-ob::password label="Password" x-model="password" />
                    <x-ob::select label="Status" :options="$statuses" x-model="status" />
                    <x-ob::combobox label="Assignee" :options="$people" x-model="assignee" placeholder="Unassigned" />
                    <x-ob::combobox label="Watchers" :options="$people" x-model="watchers" multiple placeholder="Add people" />
                    <x-ob::textarea label="Steps to reproduce" x-model="notes" placeholder="It grows as you type" />
                    <x-ob::radio-group label="Plan" x-model="plan" :options="[['value' => 'solo', 'label' => 'Solo'], ['value' => 'team', 'label' => 'Team']]" />
                    <div class="space-y-3">
                        <x-ob::switch label="Email me about updates" x-model="notify" />
                        <x-ob::checkbox label="I reproduced this on a clean install" x-model="agree" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-ob::segmented name="view" x-model="status" :options="[['value' => 'new', 'label' => 'New'], ['value' => 'triaged', 'label' => 'Triaged'], ['value' => 'resolved', 'label' => 'Resolved']]" label="Status" />
                    </div>
                    <p class="break-all font-mono text-xs text-fg-3 sm:col-span-2" x-text="JSON.stringify({ status, plan, notify, agree, assignee, watchers })"></p>
                </div>
            </x-ob::card>
        </x-ob::section>

        <x-ob::section id="overlays" heading="Overlays" description="Esc closes only the top layer; below 640px menus and modals become sheets.">
            <x-ob::card class="flex flex-wrap items-center gap-2">
                <x-ob::dropdown>
                    <x-slot:trigger>
                        <x-ob::button icon:trailing="chevron-down">Actions</x-ob::button>
                    </x-slot:trigger>
                    <x-ob::menu.group label="Report">
                        <x-ob::menu.item icon="pencil-square" shortcut="E">Edit</x-ob::menu.item>
                        <x-ob::menu.item icon="document-duplicate">Duplicate</x-ob::menu.item>
                        <x-ob::menu.item icon="link" :href="$page.'#overlays'">Copy link</x-ob::menu.item>
                    </x-ob::menu.group>
                    <x-ob::menu.separator />
                    <x-ob::menu.item icon="trash" variant="danger">Delete</x-ob::menu.item>
                </x-ob::dropdown>
                <x-ob::button x-on:click="$dispatch('modal-open', { name: 'demo-modal' })">Open modal</x-ob::button>
                <x-ob::button x-on:click="$dispatch('modal-open', { name: 'demo-sheet' })">Open bottom sheet</x-ob::button>
                <x-ob::button x-on:click="$dispatch('modal-open', { name: 'demo-side-sheet' })">Open side sheet</x-ob::button>
                <x-ob::button x-on:click="$dispatch('toast', { message: 'Report saved', variant: 'success' })">Success toast</x-ob::button>
                <x-ob::button x-on:click="$dispatch('toast', { message: 'Could not reach the server', variant: 'danger', action: { label: 'Retry', href: '#overlays' } })">Danger toast</x-ob::button>
                <x-ob::button x-on:click="$dispatch('toast', { message: 'Three reports were merged', variant: 'info' })">Info toast</x-ob::button>
                <x-ob::button x-on:click="$dispatch('command-palette-open')" icon="command-line">Command palette</x-ob::button>
            </x-ob::card>
        </x-ob::section>

        <x-ob::section id="layout" heading="Layout">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-ob::stat label="Open" value="42" icon="inbox" selected />
                <x-ob::stat label="Needs triage" value="7" icon="exclamation-triangle" />
                <x-ob::stat label="Resolved this week" value="1,318" icon="check-circle" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ob::card>
                    <h3 class="text-sm font-semibold">Card</h3>
                    <p class="mt-1 text-sm text-fg-2">Surface 1 with a hairline. Light mode adds a soft two-layer shadow; dark mode relies on layering.</p>
                </x-ob::card>
                <x-ob::card :padding="false">
                    <x-ob::empty-state icon="inbox" heading="No reports yet">
                        New reports from the companion widget land here.
                        <x-slot:action>
                            <x-ob::button size="sm" variant="primary" icon="plus">New report</x-ob::button>
                        </x-slot:action>
                    </x-ob::empty-state>
                </x-ob::card>
            </div>
        </x-ob::section>

        <x-ob::section id="data" heading="Data" description="Sorting, saving and drag-and-drop need Livewire, so these show static states.">
            <x-ob::table>
                <x-slot:head>
                    <x-ob::table.head class="w-28">ID</x-ob::table.head>
                    <x-ob::table.head sortable direction="asc">Title</x-ob::table.head>
                    <x-ob::table.head class="w-40">Project</x-ob::table.head>
                    <x-ob::table.head class="w-32">Status</x-ob::table.head>
                    <x-ob::table.head class="w-24">Priority</x-ob::table.head>
                </x-slot:head>
                @foreach (array_slice($reports, 0, 3) as $report)
                    <x-ob::table.row :href="$page.'#data'">
                        <x-ob::table.cell class="font-mono text-xs text-fg-3">{{ $report['id'] }}</x-ob::table.cell>
                        <x-ob::table.cell mobile="primary">{{ $report['title'] }}</x-ob::table.cell>
                        <x-ob::table.cell class="text-fg-2">{{ $report['project'] }}</x-ob::table.cell>
                        <x-ob::table.cell><x-ob::badge :color="$report['color']" dot>{{ $report['status'] }}</x-ob::badge></x-ob::table.cell>
                        <x-ob::table.cell><x-ob::badge :color="$report['priorityColor']">{{ $report['priority'] }}</x-ob::badge></x-ob::table.cell>
                    </x-ob::table.row>
                @endforeach
            </x-ob::table>

            <x-ob::card class="space-y-1">
                <x-ob::property label="Status" icon="signal">
                    <x-ob::inline-edit action="updateStatus" name="status" type="select" :options="$statuses" value="triaged" label="status" />
                </x-ob::property>
                <x-ob::property label="Title" icon="bars-3-bottom-left">
                    <x-ob::inline-edit action="updateTitle" name="title" value="Plan PDF download times out" label="title" />
                </x-ob::property>
                <x-ob::property label="Assignee" icon="user">
                    <span class="inline-flex items-center gap-2"><x-ob::avatar initials="AL" size="sm" /> Ada Lovelace</span>
                </x-ob::property>
                <x-ob::property label="Reported" icon="calendar">
                    <span class="tabular-nums text-fg-2">Oct 20, 2026</span>
                </x-ob::property>
            </x-ob::card>

            <x-ob::kanban>
                @foreach ($board as $column)
                    <x-ob::kanban.column :id="$column['id']" :title="$column['title']" :count="count($column['cards'])" sort="moveTask" group="tasks">
                        @foreach ($column['cards'] as $card)
                            <x-ob::kanban.card :id="$card['id']" :label="'Move '.$card['title']">
                                <p class="text-sm text-fg">{{ $card['title'] }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <x-ob::badge color="info" dot>Task</x-ob::badge>
                                    <x-ob::avatar initials="GH" size="sm" />
                                </div>
                            </x-ob::kanban.card>
                        @endforeach
                        <x-slot:empty>Drop tasks here</x-slot:empty>
                    </x-ob::kanban.column>
                @endforeach
            </x-ob::kanban>
        </x-ob::section>

        <x-ob::section id="command" heading="Command palette" description="⌘K or Ctrl+K anywhere, or / when focus is not in a field.">
            <x-ob::card class="flex flex-wrap items-center gap-3">
                <x-ob::button icon="command-line" x-on:click="$dispatch('command-palette-open')">Open palette</x-ob::button>
                <span class="text-sm text-fg-2">Type to fuzzy-match actions; any query also shows sample record results.</span>
            </x-ob::card>
        </x-ob::section>

        <x-ob::section id="stress" heading="Stress" description="Long content at 375px must truncate or wrap without horizontal scroll.">
            <x-ob::page-header :title="$longTitle" :description="$longProject">
                <x-slot:actions>
                    <x-ob::button size="sm">Edit</x-ob::button>
                </x-slot:actions>
            </x-ob::page-header>

            <x-ob::table>
                <x-slot:head>
                    <x-ob::table.head class="w-28">ID</x-ob::table.head>
                    <x-ob::table.head>Title</x-ob::table.head>
                    <x-ob::table.head class="w-40">Project</x-ob::table.head>
                    <x-ob::table.head class="w-32">Token</x-ob::table.head>
                </x-slot:head>
                <x-ob::table.row :href="$page.'#stress'">
                    <x-ob::table.cell class="font-mono text-xs text-fg-3">BUG-1077</x-ob::table.cell>
                    <x-ob::table.cell mobile="primary">{{ $longTitle }}</x-ob::table.cell>
                    <x-ob::table.cell class="text-fg-2">{{ $longProject }}</x-ob::table.cell>
                    <x-ob::table.cell class="font-mono text-xs">{{ $token }}</x-ob::table.cell>
                </x-ob::table.row>
            </x-ob::table>

            <x-ob::kanban>
                <x-ob::kanban.column id="stress" :title="$longProject" :count="1" sort="moveTask" group="stress">
                    <x-ob::kanban.card id="99" :label="'Move '.$longTitle">
                        <p class="text-sm text-fg">{{ $longTitle }}</p>
                        <p class="mt-1 font-mono text-xs text-fg-3">{{ $token }}</p>
                    </x-ob::kanban.card>
                </x-ob::kanban.column>
            </x-ob::kanban>

            <x-ob::card class="space-y-2">
                <x-ob::property label="Project">{{ $longProject }}</x-ob::property>
                <x-ob::property label="Token"><span class="font-mono">{{ $token }}</span></x-ob::property>
                <p class="text-sm text-fg-2">{{ $token }}</p>
                <x-ob::badge color="accent">{{ $longProject }}</x-ob::badge>
            </x-ob::card>

            <x-ob::button x-on:click="$dispatch('toast', { message: {{ Js::from($longTitle) }}, variant: 'info' }); $dispatch('toast', { message: {{ Js::from($token) }}, variant: 'neutral' })">Long toasts</x-ob::button>
        </x-ob::section>
    </div>

    <x-ob::toasts />

    <x-slot:dock>
        <x-ob::dock>
            <x-ob::dock.item :href="$page.'#tokens'" icon="swatch" label="Tokens" :active="true" />
            <x-ob::dock.item :href="$page.'#forms'" icon="pencil-square" label="Forms" :active="false" />
            <x-ob::dock.action icon="plus" label="Open bottom sheet" x-on:click="$dispatch('modal-open', { name: 'demo-sheet' })" />
            <x-ob::dock.item :href="$page.'#data'" icon="table-cells" label="Data" :active="false" />
            <x-ob::dock.item :href="$page.'#stress'" icon="bolt" label="Stress" :active="false" />
        </x-ob::dock>
    </x-slot:dock>
</x-ob::app-shell>

<x-ob::modal name="demo-modal" title="Rename report" description="The dropdown inside checks that Esc closes only the top layer.">
    <div x-data="{ name: 'Plan PDF download times out' }" class="space-y-4">
        <x-ob::input label="Report title" x-model="name" />
        <x-ob::dropdown position="bottom-start">
            <x-slot:trigger>
                <x-ob::button size="sm" icon:trailing="chevron-down">Move to project</x-ob::button>
            </x-slot:trigger>
            <x-ob::menu.item>Storefront</x-ob::menu.item>
            <x-ob::menu.item>Accounts</x-ob::menu.item>
            <x-ob::menu.item>Viewer</x-ob::menu.item>
        </x-ob::dropdown>
    </div>
    <x-slot:footer>
        <x-ob::button x-on:click="$dispatch('modal-close', { name: 'demo-modal' })">Cancel</x-ob::button>
        <x-ob::button variant="primary" x-on:click="$dispatch('modal-close', { name: 'demo-modal' }); $dispatch('toast', { message: 'Report renamed', variant: 'success' })">Save</x-ob::button>
    </x-slot:footer>
</x-ob::modal>

<x-ob::sheet name="demo-sheet" title="Quick actions">
    <div class="space-y-1">
        <button type="button" class="flex h-11 w-full items-center gap-3 rounded-lg px-2.5 text-left text-sm text-fg hover:bg-surface-3 focus-ring" x-on:click="$dispatch('modal-close', { name: 'demo-sheet' }); $dispatch('toast', { message: 'Report created', variant: 'success' })">
            <x-ob::icon name="bug-ant" variant="mini" class="size-4 text-fg-3" /> New report
        </button>
        <button type="button" class="flex h-11 w-full items-center gap-3 rounded-lg px-2.5 text-left text-sm text-fg hover:bg-surface-3 focus-ring" x-on:click="$dispatch('modal-close', { name: 'demo-sheet' }); $dispatch('toast', { message: 'Task created', variant: 'success' })">
            <x-ob::icon name="check-circle" variant="mini" class="size-4 text-fg-3" /> New task
        </button>
        <button type="button" class="flex h-11 w-full items-center gap-3 rounded-lg px-2.5 text-left text-sm text-fg hover:bg-surface-3 focus-ring" x-on:click="$dispatch('modal-close', { name: 'demo-sheet' }); $dispatch('command-palette-open')">
            <x-ob::icon name="magnifying-glass" variant="mini" class="size-4 text-fg-3" /> Search
        </button>
        <p class="pt-2 text-xs text-fg-3">Drag the handle down past 30% or flick to dismiss.</p>
    </div>
</x-ob::sheet>

<x-ob::sheet name="demo-side-sheet" side="right" title="Filters">
    <div x-data="{ status: 'new', mine: false }" class="space-y-4">
        <x-ob::select label="Status" :options="$statuses" x-model="status" />
        <x-ob::switch label="Only mine" x-model="mine" />
    </div>
</x-ob::sheet>

<x-ob::command-palette :actions="$paletteActions">
    <x-ob::command-palette.group heading="Reports">
        @foreach (array_slice($reports, 0, 3) as $report)
            <x-ob::command-palette.item :href="$page.'#data'" icon="bug-ant" :meta="$report['id']">{{ $report['title'] }}</x-ob::command-palette.item>
        @endforeach
    </x-ob::command-palette.group>
</x-ob::command-palette>

@livewireScripts
</body>
</html>
