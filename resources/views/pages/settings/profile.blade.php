<?php

use App\Concerns\ProfileValidationRules;
/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Omniacss\Livewire\WithOcToast;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;
    use WithOcToast;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->ocToast('success', __('Profile updated.'));
    }

    /* @chisel-email-verification */
    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
    /* @end-chisel-email-verification */
}; ?>

<section>
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="flex flex-col gap-1">
            <x-field name="name" wire:model="name" :label="__('Name')" required autofocus autocomplete="name" />
            <x-field name="email" wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            {{-- @chisel-email-verification --}}
            @if ($this->hasUnverifiedEmail)
                <div class="oc-alert oc-alert--warning" role="status">
                    <p class="oc-alert-description">
                        {{ __('Your email address is unverified.') }}
                        <button type="button" class="oc-btn oc-btn--link oc-btn--inline" wire:click.prevent="resendVerificationNotification">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="oc-alert-description font-medium">{{ __('A new verification link has been sent to your email address.') }}</p>
                    @endif
                </div>
            @endif
            {{-- @end-chisel-email-verification --}}

            <div>
                <x-oc::button type="submit" data-test="update-profile-button">{{ __('Save') }}</x-oc::button>
            </div>
        </form>

        {{-- @chisel-email-verification --}}
        @if ($this->showDeleteUser)
        {{-- @end-chisel-email-verification --}}
            <livewire:pages::settings.delete-user-form />
        {{-- @chisel-email-verification --}}
        @endif
        {{-- @end-chisel-email-verification --}}
    </x-pages::settings.layout>
</section>
