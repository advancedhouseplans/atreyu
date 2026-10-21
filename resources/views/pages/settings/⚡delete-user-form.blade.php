<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10">
    <x-ob::section :heading="__('Delete account')" :description="__('Delete your account and all of its resources')">
        <x-ob::button variant="danger" x-on:click="$dispatch('modal-open', { name: 'delete-account' })" data-test="delete-user-button">{{ __('Delete account') }}</x-ob::button>
    </x-ob::section>

    <livewire:pages::settings.delete-user-modal />
</section>
