<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;
use Omniacss\Livewire\WithOcToast;
/* @chisel-passkeys */
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
/* @end-chisel-passkeys */
/* @chisel-2fa */
use Livewire\Attributes\On;
/* @end-chisel-2fa */

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;
    use WithOcToast;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /* @chisel-2fa */
    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;
    /* @end-chisel-2fa */

    /* @chisel-passkeys */
    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';
    /* @end-chisel-passkeys */

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        /* @chisel-2fa */
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
        /* @end-chisel-2fa */

        /* @chisel-passkeys */
        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
        /* @end-chisel-passkeys */
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->ocToast('success', __('Password updated.'));
    }

    /* @chisel-passkeys */
    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }
    /* @end-chisel-passkeys */

    /* @chisel-2fa */
    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }
    /* @end-chisel-2fa */
}; ?>

<section>
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="flex flex-col gap-1">
            <x-password-field name="current_password" wire:model="current_password" :label="__('Current password')" required autocomplete="current-password" />
            <x-password-field name="password" wire:model="password" :label="__('New password')" required autocomplete="new-password" rules />
            <x-password-field name="password_confirmation" wire:model="password_confirmation" :label="__('Confirm password')" required autocomplete="new-password" rules />

            <div>
                <x-oc::button type="submit" data-test="update-password-button">{{ __('Save') }}</x-oc::button>
            </div>
        </form>

        {{-- @chisel-2fa --}}
        @if ($canManageTwoFactor)
            <section class="app-settings-section" wire:cloak>
                <x-oc::heading :title="__('Two-factor authentication')" :description="__('Manage your two-factor authentication settings')" size="sm" />

                @if ($twoFactorEnabled)
                    <div class="oc-alert oc-alert--success" role="status">
                        <p class="oc-alert-title"><x-oc::badge variant="success">{{ __('Enabled') }}</x-oc::badge></p>
                        <p class="oc-alert-description">{{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}</p>
                    </div>
                    <div>
                        <x-oc::button variant="destructive" wire:click="disable">{{ __('Disable 2FA') }}</x-oc::button>
                    </div>

                    <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                @else
                    <p class="app-muted">{{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}</p>
                    <div>
                        <x-oc::button wire:click="$dispatch('start-two-factor-setup')">{{ __('Enable 2FA') }}</x-oc::button>
                    </div>

                    <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                @endif
            </section>
        @endif
        {{-- @end-chisel-2fa --}}

        {{-- @chisel-passkeys --}}
        @if ($canManagePasskeys)
            <section class="app-settings-section" wire:cloak>
                <x-oc::heading :title="__('Passkeys')" :description="__('Manage your passkeys for passwordless sign-in')" size="sm" />

                @if ($passkeys)
                    <ul class="oc-card app-passkeys" role="list">
                        @foreach ($passkeys as $passkey)
                            <li class="app-passkey">
                                <span class="app-passkey-icon"><x-icon name="key" /></span>
                                <div class="app-passkey-text">
                                    <p class="font-medium">
                                        {{ $passkey['name'] }}
                                        @if ($passkey['authenticator'])
                                            <x-oc::badge variant="secondary">{{ $passkey['authenticator'] }}</x-oc::badge>
                                        @endif
                                    </p>
                                    <p class="app-muted">
                                        {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                        @if ($passkey['last_used_at_diff'])
                                            · {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                        @endif
                                    </p>
                                </div>
                                <x-oc::button variant="ghost" size="sm" wire:click="confirmDelete({{ $passkey['id'] }})" aria-label="{{ __('Remove passkey') }}">{{ __('Remove') }}</x-oc::button>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-oc::empty-state icon="🔑" :title="__('No passkeys yet')" :text="__('Add a passkey to sign in without a password')" />
                @endif

                <x-passkey-registration />
            </section>
        @endif
        {{-- @end-chisel-passkeys --}}
    </x-pages::settings.layout>

    {{-- @chisel-passkeys --}}
    <x-oc::wire.modal model="showDeleteModal" :title="__('Remove passkey')" size="sm">
        <p>{{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}</p>
        <x-slot:footer>
            <x-oc::button variant="outline" wire:click="closeDeleteModal">{{ __('Cancel') }}</x-oc::button>
            <x-oc::button variant="destructive" wire:click="deletePasskey">{{ __('Remove passkey') }}</x-oc::button>
        </x-slot:footer>
    </x-oc::wire.modal>
    {{-- @end-chisel-passkeys --}}
</section>
