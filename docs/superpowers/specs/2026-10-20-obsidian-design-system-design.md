# Obsidian design system — design

**Date:** 2026-10-20
**Status:** Approved in brainstorming, pending spec review

## Goal

Replace MaryUI / DaisyUI with Obsidian: an in-house design system and Blade component library for the internal bug and task tracker (Atreyu), packaged as a Composer package other internal Laravel / Livewire apps can install. The look takes cues from Figma and Notion: a near-black dark mode, generous whitespace, precise hairlines, a refined palette, and reactive, animated components built on Livewire 4 and Alpine. It must work cleanly on mobile, including dock navigation and bottom-sheet menus.

## Decisions

| Topic | Decision |
|---|---|
| Reuse scope | Composer package `ahp/obsidian` at `packages/obsidian`, path repository (symlinked) like `bug-companion` |
| Component prefix | `<x-ob::*>` |
| Direction | Obsidian: near-black canvas, depth from layered surfaces and hairlines, Notion-like whitespace with Figma-like precision |
| Theming | Mode: `system` / `light` / `dark`. Accent: `iris` (default), `blue`, `teal`, `green`, `amber`, `rose` |
| Type | Inter (variable) for UI, JetBrains Mono for IDs, logs, stack traces. Self-hosted woff2, bundled by Vite |
| Mobile | Floating dock below 1024px. Dropdowns, selects, and modals render as bottom sheets below 640px |
| Floating surfaces | Backdrop blur ("glass") on popovers, menus, dock, sheets, toasts, tooltips, palette, sticky mobile header; blurred scrims behind modals and sheets |
| Architecture | Anonymous Blade components + Tailwind v4 utilities over CSS custom-property tokens + one Alpine plugin module. No new npm dependencies |
| v1 extras | Command palette (⌘K), combobox / multi-select, kanban board, inline-edit fields |

## 1. Design language

All values are CSS custom properties exposed to Tailwind v4 through `@theme`, so hosts write `bg-surface-1 text-fg-2 border-hairline ring-accent`.

### Surfaces and text

| Token | Dark | Light | Use |
|---|---|---|---|
| `canvas` | `#08080A` | `#FBFBFA` | Page background |
| `surface-1` | `#111114` | `#FFFFFF` | Cards, tables, inputs |
| `surface-2` | `#18181C` | `#FFFFFF` (+ shadow) | Popovers, sheets, modals (solid fallback for glass) |
| `surface-3` | `#202025` | `#F4F4F2` | Hover / pressed on raised surfaces |
| `hairline` | `#FFFFFF` 6% | `#000000` 6% | Default hairline |
| `hairline-strong` | `#FFFFFF` 10% | `#000000` 10% | Emphasized hairline, hover |
| `fg` | `#EDEDEF` | `#18181B` | Primary text |
| `fg-2` | `#9A9AA3` | `#5F5F66` | Secondary text |
| `fg-3` | `#75757F` | `#76767D` | Metadata, placeholders |

Every text token meets WCAG AA 4.5:1 against `canvas` and `surface-1` in its mode. Never dim text with `opacity`.

### Accent

Selected by `data-accent` on `<html>`. Each accent defines:

- `accent`: solid fill (buttons, active indicators)
- `accent-hover`
- `accent-soft`: 12–15% wash (selected rows, active nav, focus halo, count badges)
- `accent-text`: accent-coloured text, lighter in dark mode, darker in light mode
- `on-accent`: text on a solid `accent` fill. White for `iris`, `blue`, and `rose`; `#0A0A0B` for `teal`, `green`, and `amber`

Base hues: iris `#7C6CFF`, blue `#3B82F6`, teal `#14B8A6`, green `#22C55E`, amber `#F59E0B`, rose `#F43F5E`. Per-mode `accent-text` and `accent-hover` values are tuned so `accent-text` reaches 4.5:1 on `surface-1`.

### Status colours

Fixed, independent of accent: `danger`, `warning`, `success`, `info`, `neutral`. Each has a soft background (`*-soft`) and a text variant (`*-text`). Report/task priority and status badges map onto these, never onto the accent.

### Typography

- Families: `--font-sans` Inter, `--font-mono` JetBrains Mono.
- Scale (px): 12, 13, 14, 16, 20, 24, 32. Body UI text is 14. Dense tables use 13.
- Weights: 400, 500, 600.
- Headings (20 and up) use −0.02em tracking. Counts and numeric columns use `tabular-nums`.

### Shape

| Element | Radius |
|---|---|
| Buttons, chips, badges, segmented controls | full (pill) |
| Inputs, menu items, table rows, small tiles | 8px |
| Cards, popovers, modals | 12px |
| Bottom sheets | 20px (top corners) |
| Dock | 20px |

### Elevation and glass

- **Dark in-flow:** no shadows. Depth comes from `canvas → surface-1 → surface-3` plus hairlines.
- **Light in-flow:** soft two-layer shadows on cards (`0 1px 2px` / `0 4px 12px`, very low alpha).
- **Glass (floating):** popovers, menus, dock, sheets, toasts, tooltips, command palette, sticky mobile header.
  - Dark: `surface-2` at ~72% alpha + `backdrop-filter: blur(24px) saturate(1.4)`.
  - Light: white at ~75% alpha with the same filter.
  - Both modes add a 1px inner top highlight (`inset 0 1px 0` white ~4–6%) and a deep soft drop shadow.
- **Scrim:** behind modals, sheets, and the palette. Black 40% (dark) / 20% (light) + `blur(8px)`, fading in with the panel.
- **Fallbacks:** solid `surface-2` when `@supports not (backdrop-filter: blur(1px))` applies or under `prefers-reduced-transparency: reduce`.
- **Limit:** no more than two blurred layers on screen at once (for example, scrim plus sheet). Nested popovers inside a sheet use solid `surface-3`.

### Spacing and density

- Tailwind's 4px scale.
- Page gutter: 16px below 640px, 24px at 640–1023px, 32px at 1024px and up.
- Touch targets: minimum 44px below 640px.
- Desktop table rows: 40px.

### Motion

| Token | Value | Use |
|---|---|---|
| `--dur-fast` | 120ms | Hover, press, toggles |
| `--dur-base` | 200ms | Menus, popovers, toasts |
| `--dur-slow` | 320ms | Sheets, modals, dock hide/show |
| `--ease-out` | `cubic-bezier(.2,.8,.2,1)` | Default |

- Press feedback: `scale(.98)`.
- Overlays enter with fade plus `scale(.96 → 1)` on desktop. Sheets slide up.
- `prefers-reduced-motion: reduce` collapses every transition to an opacity fade at `--dur-fast`.

### Focus

On `:focus-visible` only: a 1px `accent` ring plus a 4px `accent-soft` halo.

## 2. Package architecture

```
packages/obsidian/                       ahp/obsidian
├─ composer.json                         illuminate/support ^13, livewire/livewire ^4, blade-ui-kit/blade-heroicons ^2
├─ src/ObsidianServiceProvider.php       Blade::anonymousComponentPath(views/components, 'ob'); local-only showcase route
├─ resources/css/obsidian.css            @theme tokens; mode + accent layers; @font-face; base layer; @source '../views'
├─ resources/fonts/                      Inter var, JetBrains Mono (woff2)
├─ resources/js/obsidian.js              default export: Alpine plugin (stores, Alpine.data components, directives)
├─ resources/views/components/           anonymous components (nested dirs for compound parts, e.g. menu/item.blade.php)
├─ resources/views/showcase.blade.php    kitchen-sink page at /_obsidian, registered only when app()->isLocal()
└─ tests/                                Pest + Orchestra Testbench
```

### Host integration

```css
/* resources/css/app.css */
@import 'tailwindcss';
@import '../../vendor/ahp/obsidian/resources/css/obsidian.css';
@source '../views';
```

```js
// resources/js/app.js
import obsidian from '../../vendor/ahp/obsidian/resources/js/obsidian.js';
document.addEventListener('alpine:init', () => window.Alpine.plugin(obsidian));
```

```blade
{{-- <head>, before @vite --}}
<x-ob::theme-script :mode="$user?->appearance?->value" :accent="$user?->accent?->value" />
```

`theme-script` is an inline script. Before first paint it sets `data-mode` and `data-accent` on `<html>`, using the server value first, then `localStorage`, then the defaults (`system`, `iris`). It resolves `system` through `matchMedia('(prefers-color-scheme: dark)')` and listens for changes. Dark styles key off `[data-mode=dark]` and the resolved system mode via a `@custom-variant dark`.

### Dependencies

- Only Livewire 4's bundled Alpine plugins: `x-trap` (focus), `x-anchor` (popover positioning), `x-collapse`, `x-intersect`, and `wire:sort` (kanban).
- Written in-house: sheet drag-to-dismiss, dock hide-on-scroll, fuzzy matcher, toast queue, combobox keyboard model. No new npm packages.
- Icons: Heroicons through blade-icons, wrapped in `<x-ob::icon name="inbox" variant="outline|solid|mini" />`. The package requires `blade-ui-kit/blade-heroicons` directly.

### Component API conventions

- **Common props:** `variant` (`primary` | `secondary` | `ghost` | `danger`, default `secondary`), `size` (`sm` | `md` | `lg`, default `md`), `icon`, `icon:trailing`.
- **Attributes:** remaining attributes merge onto the root element with `$attributes->class()`.
- **Form controls:** `wire:model*` and `x-model` are forwarded to the real `<input>`, `<select>`, or `<textarea>`.
- **Field props:** controls accept `label`, `hint`, and `name`. The error for the `wire:model` name (or `name`) comes from `$errors` automatically.
- **Loading state:** a button with `wire:click` shows a spinner scoped to its own action (`wire:loading` + `wire:target`). An explicit `loading` prop overrides this.
- **Links:** a button with `href` renders `<a>`. Passing `navigate` adds `wire:navigate`.
- **Icon-only controls:** require `label`, which becomes `aria-label` and the tooltip text.
- **Blaze:** stateless components (`icon`, `badge`, `avatar`, `kbd`, `spinner`, `separator`, and `button` if compatible) opt into Blaze. The implementation plan confirms Blaze's constraints from its docs.
- **Package boundary:** the package knows nothing about app models. Data-driven components (palette, kanban, combobox remote search) expose slots and Alpine/Livewire hooks. The host app owns the queries.

## 3. Component inventory

### Primitives

| Component | Notes |
|---|---|
| `button` | Variants, sizes, icon, loading, `href`; at most one `primary` per view by convention |
| `icon` | Heroicons wrapper |
| `badge` | `color` = status colour or `neutral`; optional `dot`; pill |
| `avatar` | Image or initials, sizes, `avatar.stack` |
| `kbd` | Keyboard hint |
| `spinner` | |
| `separator` | Horizontal or vertical, optional label |
| `tooltip` | `x-anchor`, 400ms open delay, glass, not shown on touch devices |

### Forms

| Component | Notes |
|---|---|
| `field` | Label, hint, and error wrapper; used internally by every control |
| `input` | Leading/trailing icon, `clearable`, prefix/suffix |
| `password` | Reveal toggle |
| `textarea` | Auto-grow |
| `select` | Native `<select>`, styled |
| `checkbox`, `switch` | |
| `radio-group`, `segmented` | `segmented` is the pill segmented control used for the mode picker |
| `combobox` | Searchable single or multi select with chips. Keyboard: arrows, Enter, Backspace removes last chip, Esc. Client-side `:options` or remote search via `wire:model.live` on its query. Renders as a sheet below 640px |

### Layout

| Component | Notes |
|---|---|
| `app-shell` | Desktop sidebar (collapsible to icons, state persisted) at 1024px and up; content plus `dock` below 1024px |
| `sidebar`, `sidebar.item`, `sidebar.group` | Active by route, count badge, icon-only when collapsed (with tooltip) |
| `dock`, `dock.item`, `dock.action` | Glass, floating, 3–4 items plus a central create action; hides on scroll down, returns on scroll up; respects `env(safe-area-inset-bottom)` |
| `page-header` | Title, description, actions slot. On mobile it becomes a compact sticky glass header once the large title scrolls out (`x-intersect`) |
| `card`, `section` | |
| `empty-state` | Icon, headline, body, action |
| `stat` | Selectable count tile (the report triage tiles) |

### Data

| Component | Notes |
|---|---|
| `table` (+ `table.head`, `table.row`, `table.cell`) | Sticky header, sortable header slot, row-as-link; below 640px rows render as stacked list items with a primary line and a meta line |
| `kanban`, `kanban.column`, `kanban.card` | `wire:sort` within and across columns. The dragged card lifts with a soft shadow and slight scale; an animated placeholder holds its spot. The host handles the sort event and persists order |
| `property` | Notion-style label/value row for show pages |
| `inline-edit` | Click or Enter to edit, Esc cancels, Enter or blur saves through `wire:model.blur`. Shows the new value optimistically, then saving and saved states; validation errors appear inline and keep the field open |

### Overlays

| Component | Notes |
|---|---|
| `dropdown` + `menu.item`, `menu.separator`, `menu.group` | Anchored glass popover, arrow-key navigation, typeahead; bottom sheet below 640px |
| `modal` | Centered dialog on desktop, bottom sheet below 640px. Opened by `wire:model` (bool) or by `name` plus `$dispatch('modal-open', { name })`. `x-trap`, scroll lock, Esc, blurred scrim |
| `sheet` | `side` = `bottom` / `right`. Drag to dismiss: release past 30% of its height, or a downward flick faster than 0.5px/ms |
| `command-palette` | ⌘K / Ctrl+K, or `/` when focus isn't in a field. Recent items plus grouped results; arrows, Enter, Esc; full screen below 640px. Static actions are fuzzy-matched client-side; record search goes to the host's Livewire component |
| `toast` | Stack bottom-right on desktop, above the dock on mobile. Triggered by `$dispatch('toast', {…})` in Alpine or `$this->dispatch('toast', …)` in Livewire. Variants: `success`, `danger`, `info`, `neutral`. Auto-dismisses after 4s, pauses on hover; an optional action button |

### Motion and live updates

- Overlay enter and leave transitions follow the motion tokens.
- Lists cross-fade on Livewire morphs via `wire:transition`.
- The dock slides away on scroll down and returns on scroll up.
- Reduced motion turns all of these into fades.

### Accessibility

- Correct roles and aria attributes on menus, dialogs, combobox (`listbox`), and segmented controls.
- Focus returns to the trigger when an overlay closes.
- Every control is keyboard reachable.
- Icon-only controls must have a `label`.

## 4. App migration

### Views

- Rewrite the layouts (`layouts/app/sidebar`, `layouts/app/header`, `layouts/auth/*`) onto `x-ob::app-shell` and the auth card.
- Rewrite the Livewire views (`reports`, `projects`, `tasks` index/show) and the auth and settings pages onto `x-ob::*`.
- Replace hand-rolled DaisyUI classes (`bg-base-*`, `text-base-content`, `btn`, `table`, `*-content`) with token utilities.
- Delete views Obsidian replaces: `resources/views/flux/`, `components/page-header`, `components/desktop-user-menu`, `components/auth-header`. Keep `app-logo*`, `passkey-*`, and `auth-session-status` (restyled).

### New app features built on the library

- **`App\Livewire\CommandPalette`**, mounted once in the app shell. It searches reports, tasks, and projects the current user can view (same scoping as the index pages), limited to 5 per group, and offers static navigation and create actions.
- **Tasks board view** with `x-ob::kanban`, grouped by status. Dropping a card persists its status and position.
- **Inline-edit properties** (status, priority, assignee, title) on the report and task show pages, validated and authorized like the existing update actions.

### Dependencies

- Remove `robsontenorio/mary` (Composer) and `daisyui` (npm).
- Add `ahp/obsidian` (path repository, `@dev`) to `require`.
- The package requires `blade-ui-kit/blade-heroicons`.

### Theming data

- Replace `App\Enums\Theme` with `App\Enums\Appearance` (`System`, `Light`, `Dark`) and `App\Enums\Accent` (`Iris`, `Blue`, `Teal`, `Green`, `Amber`, `Rose`).
- Migration: add `appearance` (default `system`) and `accent` (default `iris`). Map existing `theme` values: DaisyUI light themes → `light`, dark themes → `dark`, anything else or null → `system`. Then drop `theme`.
- Update `User` casts and `#[Fillable]`.
- The API `me` response drops `theme` and gains `appearance` and `accent`. The plan checks bug-companion and other consumers for reads of `theme` first.
- The appearance settings page becomes a `segmented` mode control plus accent swatches. It saves immediately and updates `<html>` live through `$store.ob.theme`.
- `partials/head` uses `<x-ob::theme-script>` and drops the `mary-theme` / `atreyu-theme` localStorage keys.

### Out of scope

- The bug-companion widget (it has its own Vite build and styles).
- Information-architecture changes beyond what the new components imply.

## 5. Testing

- **Package (Pest + Testbench):** render tests for behaviour that lives in PHP:
  - field shows the `$errors` message for its `wire:model` name
  - `button` with `href` renders `<a>`, and with `navigate` adds `wire:navigate`
  - variant and size classes are applied
  - an icon-only button without `label` throws
  - `wire:model` is forwarded to the inner control
  - the showcase route is absent outside the local environment
- **JS (Vitest via `vp test`):** the fuzzy matcher's ranking and the sheet dismiss threshold (distance and velocity).
- **App (Pest):**
  - the appearance/accent migration mapping
  - appearance settings save
  - command palette scoping (no records the user can't view)
  - kanban drop persists status and order and is authorized
  - inline-edit validation and authorization
  - existing `NavigationTest`, `AppearanceTest`, and `MeControllerTest` updated
- **Visual:** walk `/_obsidian` and every migrated page at 1440px and 375px, in dark and light, with at least iris and amber. Check that glass fallbacks hold under forced reduced transparency.
