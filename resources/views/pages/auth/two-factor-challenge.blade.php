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
                    <p class="mt-2 text-sm text-fg-2">{{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}">
                @csrf

                <div class="space-y-5 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="flex items-center justify-center my-5" x-ref="otp">
                            <input class="input input-bordered w-full text-center font-mono text-xl tracking-[0.5em]" x-model="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" aria-label="OTP Code" />
                            @error('code')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-5">
                            <input class="input input-bordered w-full" type="text" name="recovery_code" x-ref="recovery_code" x-bind:required="showRecoveryInput" autocomplete="one-time-code" x-model="recovery_code" />
                        </div>

                        @error('recovery_code')
                            <p class="text-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="btn btn-primary w-full" type="submit">
                        {{ __('Continue') }}
                    </button>
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-center">
                    <span class="opacity-50">{{ __('or you can') }}</span>
                    <div class="inline font-medium underline cursor-pointer opacity-80">
                        <span x-show="!showRecoveryInput" @click="toggleInput()">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput" @click="toggleInput()">{{ __('login using an authentication code') }}</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
