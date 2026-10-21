<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg font-bold">{{ __('Delete account') }}</h3>
        <p class="mt-1 text-sm text-base-content/55">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <label for="confirm-user-deletion" class="btn btn-error" data-test="delete-user-button">{{ __('Delete account') }}</label>

    <livewire:pages::settings.delete-user-modal />
</section>
