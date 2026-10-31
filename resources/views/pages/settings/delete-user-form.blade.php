<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="app-danger-zone">
    <x-oc::heading :title="__('Delete account')" :description="__('Delete your account and all of its resources')" size="sm" />

    <div>
        <x-oc::button variant="destructive" command="show-modal" commandfor="confirm-user-deletion" data-test="delete-user-button">
            {{ __('Delete account') }}
        </x-oc::button>
    </div>

    <livewire:pages::settings.delete-user-modal />
</section>
