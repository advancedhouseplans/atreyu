@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        showForm: false,
        name: '',
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        getDefaultPasskeyName() {
            const ua = navigator.userAgent;

            const browser = [
                { pattern: /Edg|Edge/, name: 'Edge' },
                { pattern: /OPR|Opera|OPiOS/, name: 'Opera' },
                { pattern: /Firefox|FxiOS/, name: 'Firefox' },
                { pattern: /Chrome|CriOS/, name: 'Chrome' },
                { pattern: /Safari/, name: 'Safari' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            const os = [
                { pattern: /iPhone/, name: 'iPhone' },
                { pattern: /iPad|Macintosh(?=.*Mobile)/, name: 'iPad' },
                { pattern: /Android/, name: 'Android' },
                { pattern: /Mac/, name: 'Mac' },
                { pattern: /Windows/, name: 'Windows' },
            ].find(({ pattern }) => pattern.test(ua))?.name;

            return [browser, os].filter(Boolean).join(' on ') || '';
        },
        init() {
            this.name = this.getDefaultPasskeyName();
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async register() {
            if (!this.name.trim()) return;

            this.loading = true;
            this.error = null;

            try {
                await window.Passkeys.register({ name: this.name });
                this.name = '';
                this.showForm = false;
                await $wire.loadPasskeys();
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
        cancel() {
            this.showForm = false;
            this.name = '';
            this.error = null;
        },
    }"
>
    <template x-if="!supported">
        <p class="text-sm text-base-content/60">{{ __('Passkeys are not supported in this browser.') }}</p>
    </template>

    <template x-if="supported && !showForm">
        <div>
            <button type="button" class="btn btn-primary" x-on:click="showForm = true">
                <span class="text-lg leading-none">+</span>
                {{ __('Add passkey') }}
            </button>
        </div>
    </template>

    <template x-if="supported && showForm">
        <div class="space-y-4 rounded-box border border-base-content/10 bg-base-200 p-4">
            <label class="form-control w-full">
                <span class="label-text mb-2 font-medium">{{ __('Passkey name') }}</span>
                <input class="input input-bordered w-full" x-model="name" placeholder="{{ __('e.g., MacBook Pro, iPhone') }}" x-on:keydown.enter.prevent="register()" x-ref="passkeyNameInput" x-init="$nextTick(() => $refs.passkeyNameInput?.focus())" />
            </label>
            <p class="text-sm text-base-content/60">{{ __('Give this passkey a name to help you identify it later.') }}</p>

            <p x-show="error" x-text="error" x-cloak class="text-error text-sm"></p>

            <div class="flex gap-2">
                <button type="button" class="btn btn-primary" x-on:click="register()" x-bind:disabled="loading || !name.trim()">
                    <span x-show="!loading">{{ __('Register passkey') }}</span>
                    <span x-show="loading" x-cloak>{{ __('Registering...') }}</span>
                </button>
                <button type="button" class="btn btn-ghost" x-on:click="cancel()">
                    {{ __('Cancel') }}
                </button>
            </div>
        </div>
    </template>
</div>
