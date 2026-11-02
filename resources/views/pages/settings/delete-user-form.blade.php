<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="app-danger-zone">
    <x-oc::heading :title="__('Delete account')" :description="__('Delete your account and all of its resources')" />

    <div class="app-danger-box">
        <div class="app-danger-box-text">
            <p class="app-danger-box-title">{{ __('Warning') }}</p>
            <p>{{ __('Please proceed with caution, this cannot be undone.') }}</p>
        </div>
        <x-oc::button variant="destructive" command="show-modal" commandfor="confirm-user-deletion" data-test="delete-user-button">
            {{ __('Delete account') }}
        </x-oc::button>
    </div>

    <livewire:pages::settings.delete-user-modal />
</section>
