<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                focusOtp() {
                    this.$nextTick(() => this.$refs.otp?.querySelector('input')?.focus());
                },
                init() {
                    if (! this.showRecoveryInput) {
                        this.focusOtp();
                    }
                },
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;

                    this.code = '';
                    this.recovery_code = '';

                    $nextTick(() => {
                        this.showRecoveryInput
                            ? this.$refs.recovery_code?.focus()
                            : this.focusOtp();
                    });
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <div class="text-center">
                    <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ __('Authentication code') }}</h1>
                    <p class="mt-2 text-sm text-fg-2">{{ __('Enter the authentication code provided by your authenticator application.') }}</p>
                </div>
            </div>

            <div x-show="showRecoveryInput">
                <div class="text-center">
                    <h1 class="text-2xl font-semibold tracking-[-0.02em]">{{ __('Recovery code') }}</h1>
                    <p class="mt-2 text-sm text-fg-2">{{ __('Confirm access to your account by entering one of your emergency recovery codes.') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="my-5" x-ref="otp">
                            <x-ob::field error-key="code" for="code">
                                <input id="code" class="h-11 w-full min-w-0 rounded-lg border border-hairline bg-surface-1 px-3 text-center font-mono text-xl tracking-[0.5em] text-fg placeholder:text-fg-3 focus-ring in-data-invalid:border-danger" x-model="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" aria-label="{{ __('Authentication code') }}" />
                            </x-ob::field>
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5 text-left">
                            <x-ob::input name="recovery_code" :aria-label="__('Recovery code')" x-ref="recovery_code" x-bind:required="showRecoveryInput" autocomplete="one-time-code" x-model="recovery_code" />
                        </div>
                    </div>

                    <x-ob::button variant="primary" type="submit" class="w-full">{{ __('Continue') }}</x-ob::button>
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="text-fg-3">{{ __('or you can') }}</span>
                    <button type="button" class="rounded font-medium text-accent-fg hover:underline focus-ring" x-on:click="toggleInput()">
                        <span x-show="!showRecoveryInput">{{ __('log in using a recovery code') }}</span>
                        <span x-show="showRecoveryInput">{{ __('log in using an authentication code') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
