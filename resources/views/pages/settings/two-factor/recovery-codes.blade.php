<?php

use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public array $recoveryCodes = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->loadRecoveryCodes();
    }

    /**
     * Generate new recovery codes for the user.
     */
    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generateNewRecoveryCodes): void
    {
        $generateNewRecoveryCodes(auth()->user());

        $this->loadRecoveryCodes();
    }

    /**
     * Load the recovery codes for the user.
     */
    private function loadRecoveryCodes(): void
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactorAuthentication() && $user->two_factor_recovery_codes) {
            try {
                $this->recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
            } catch (Exception) {
                $this->addError('recoveryCodes', 'Failed to load recovery codes');

                $this->recoveryCodes = [];
            }
        }
    }
}; ?>

<div class="oc-card" wire:cloak x-data="{ showRecoveryCodes: false }">
    <div class="oc-card-header">
        <h3 class="oc-card-title">{{ __('2FA recovery codes') }}</h3>
        <p class="oc-card-description">{{ __('Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.') }}</p>
    </div>

    <div class="oc-card-content flex flex-col gap-1">
        <div class="flex flex-wrap gap-0.5">
            <x-oc::button x-on:click="showRecoveryCodes = ! showRecoveryCodes" aria-controls="recovery-codes-section" x-bind:aria-expanded="showRecoveryCodes">
                <span x-show="!showRecoveryCodes">{{ __('View recovery codes') }}</span>
                <span x-show="showRecoveryCodes" x-cloak>{{ __('Hide recovery codes') }}</span>
            </x-oc::button>
            @if (filled($recoveryCodes))
                <x-oc::button variant="outline" x-show="showRecoveryCodes" x-cloak wire:click="regenerateRecoveryCodes">{{ __('Regenerate codes') }}</x-oc::button>
            @endif
        </div>

        <div x-show="showRecoveryCodes" x-cloak id="recovery-codes-section" x-bind:aria-hidden="!showRecoveryCodes">
            @error('recoveryCodes')
                <div class="oc-alert oc-alert--danger" role="alert"><p class="oc-alert-title">{{ $message }}</p></div>
            @enderror

            @if (filled($recoveryCodes))
                {{-- OmniaUI recovery-codes panel: a grid of codes in a monospace font. --}}
                <div class="oc-recovery-codes">
                    <p class="oc-recovery-codes-warning">{{ __('Each recovery code can be used once to access your account and will be removed after use. If you need more, click Regenerate codes above.') }}</p>
                    <ul class="oc-recovery-codes-list" aria-label="{{ __('Recovery codes') }}" wire:loading.class="opacity-50">
                        @foreach ($recoveryCodes as $code)
                            <li>{{ $code }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
