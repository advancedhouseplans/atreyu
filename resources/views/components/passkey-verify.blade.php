@props([
    'optionsRoute' => 'passkey.login-options',
    'submitRoute' => 'passkey.login',
    'label' => __('Sign in with a passkey'),
    'loadingLabel' => __('Authenticating...'),
    'separator' => __('Or continue with email'),
])

@assets
@vite('resources/js/passkeys.js')
@endassets

<div
    x-data="{
        supported: false,
        loading: false,
        error: null,
        updateSupport() {
            this.supported = Boolean(window.Passkeys?.isSupported());
        },
        init() {
            this.updateSupport();

            window.addEventListener('passkeys:ready', () => this.updateSupport(), { once: true });
        },
        async verify() {
            this.loading = true;
            this.error = null;
            try {
                const response = await window.Passkeys.verify({
                    routes: {
                        options: '{{ route($optionsRoute) }}',
                        submit: '{{ route($submitRoute) }}',
                    },
                });
                Livewire.navigate(response.redirect || '/reports');
            } catch (e) {
                if (e.constructor?.name !== 'UserCancelledError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
    }"
>
    <template x-if="supported">
        <div>
            <div class="grid gap-2">
                <button type="button" class="btn btn-secondary w-full" x-on:click="verify()" x-bind:disabled="loading">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 11a3 3 0 0 1 3 3c0 2.8-.8 5.1-2.2 7M9 21c1.3-2.1 2-4.4 2-7a1 1 0 0 1 2 0c0 2.4-.5 4.4-1.5 6.2M6.5 19.5A14 14 0 0 0 8 14a4 4 0 0 1 8 0c0 1.7-.2 3.3-.7 4.8M5 15v-1a7 7 0 0 1 14 0v2M7 7.5A8.8 8.8 0 0 1 12 6c2.1 0 4 .7 5.5 2" stroke-linecap="round" /></svg>
                    <span x-show="!loading">{{ $label }}</span>
                    <span x-show="loading" x-cloak>{{ $loadingLabel }}</span>
                </button>
                <p x-show="error" x-text="error" x-cloak
                   class="text-error text-center text-sm"></p>
            </div>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-base-content/10"></div>
                </div>
                <div class="relative flex justify-center text-xs uppercase">
                    <span class="bg-base-100 px-2 text-base-content/45">
                        {{ $separator }}
                    </span>
                </div>
            </div>
        </div>
    </template>
</div>
