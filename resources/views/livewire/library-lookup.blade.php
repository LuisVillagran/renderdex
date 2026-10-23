<form wire:submit="lookup" class="flex flex-col gap-2" novalidate>
    <label for="profile" class="text-sm font-medium">{{ __('lookup.label') }}</label>

    <div class="flex flex-col gap-2 sm:flex-row">
        <input
            id="profile"
            type="text"
            wire:model="profile"
            placeholder="{{ __('lookup.placeholder') }}"
            autocomplete="off"
            spellcheck="false"
            aria-describedby="profile-help @error('profile') profile-error @enderror"
            @error('profile') aria-invalid="true" @enderror
            class="min-w-0 flex-1 rounded-lg border border-line bg-canvas/60 px-3 py-2.5 font-mono text-sm placeholder:text-muted focus:border-accent focus:ring-2 focus:ring-accent/30 focus:outline-none aria-invalid:border-red-500"
        >

        <button
            type="submit"
            class="focus-ring bg-brand rounded-lg px-5 py-2.5 font-semibold shadow-lg shadow-accent-2/20 transition hover:brightness-110 disabled:opacity-60"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="lookup">{{ __('lookup.submit') }}</span>
            <span wire:loading wire:target="lookup">{{ __('lookup.loading') }}</span>
        </button>
    </div>

    @error('profile')
        <p id="profile-error" class="text-sm text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror

    <p id="profile-help" class="text-xs text-muted">{{ __('lookup.help') }}</p>
</form>
