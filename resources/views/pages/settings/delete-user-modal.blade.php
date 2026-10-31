<?php

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap(Auth::user(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

{{-- Opened by the "Delete account" button (command="show-modal"); stays open while the password is wrong. --}}
<dialog id="confirm-user-deletion" class="oc-modal" closedby="any" wire:ignore.self aria-labelledby="confirm-user-deletion-title"
    x-data x-init="@if ($errors->isNotEmpty()) $el.showModal() @endif">
    <form method="POST" wire:submit="deleteUser" class="oc-modal-content">
        <div class="oc-modal-header">
            <h2 class="oc-modal-title" id="confirm-user-deletion-title">{{ __('Are you sure you want to delete your account?') }}</h2>
            <p class="oc-modal-description">
                {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
            </p>
        </div>

        <div class="oc-modal-body">
            <x-password-field id="delete-password" wire:model="password" :label="__('Password')" autocomplete="current-password" />
        </div>

        <div class="oc-modal-footer">
            <x-oc::button variant="outline" command="close" commandfor="confirm-user-deletion">{{ __('Cancel') }}</x-oc::button>
            <x-oc::button variant="destructive" type="submit" data-test="confirm-delete-user-button">{{ __('Delete account') }}</x-oc::button>
        </div>
    </form>
</dialog>
