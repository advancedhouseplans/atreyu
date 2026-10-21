<details class="dropdown dropdown-top w-full">
    <summary class="btn btn-ghost h-auto w-full justify-start gap-3 rounded-2xl px-2 py-2 normal-case" data-test="sidebar-menu-button">
        <div class="avatar placeholder">
            <div class="w-9 rounded-full bg-base-300 text-base-content">
                <span class="text-xs font-semibold">{{ auth()->user()->initials() }}</span>
            </div>
        </div>
        <div class="min-w-0 flex-1 text-left">
            <div class="truncate text-sm font-semibold">{{ auth()->user()->name }}</div>
            <div class="truncate text-xs font-normal text-base-content/50">{{ auth()->user()->email }}</div>
        </div>
        <svg class="size-4 text-base-content/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m8 9 4-4 4 4M16 15l-4 4-4-4" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </summary>
    <ul class="menu dropdown-content bottom-full z-40 mb-2 w-full rounded-2xl border border-base-content/5 bg-base-100/80 p-2 shadow-2xl ring-1 ring-base-content/5 backdrop-blur-2xl">
        <li><a href="{{ route('profile.edit') }}" wire:navigate>{{ __('Settings') }}</a></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left" data-test="logout-button">{{ __('Log out') }}</button>
            </form>
        </li>
    </ul>
</details>
