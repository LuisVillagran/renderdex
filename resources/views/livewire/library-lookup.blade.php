<form wire:submit="lookup" class="flex flex-col gap-2" novalidate>
    <label for="profile" class="label">{{ __('lookup.label') }}</label>

    <div class="flex flex-col gap-2 sm:flex-row sm:gap-0">
        <div class="relative flex min-w-0 flex-1">
            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 font-mono text-sm text-accent" aria-hidden="true">›</span>
            <input
                id="profile"
                type="text"
                wire:model="profile"
                placeholder="{{ __('lookup.placeholder') }}"
                autocomplete="off"
                spellcheck="false"
                aria-describedby="profile-help @error('profile') profile-error @enderror"
                @error('profile') aria-invalid="true" @enderror
                class="w-full border border-line-strong bg-canvas py-2.5 pr-3 pl-7 font-mono text-sm placeholder:text-muted focus:border-accent focus:outline-2 focus:-outline-offset-1 focus:outline-accent aria-invalid:border-red-500"
            >
        </div>

        <button
            type="submit"
            class="focus-ring border border-accent bg-accent px-5 py-2.5 font-mono text-sm font-bold text-on-accent uppercase transition hover:brightness-110 disabled:opacity-60 sm:-ml-px"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="lookup">{{ __('lookup.submit') }} ↵</span>
            <span wire:loading wire:target="lookup">{{ __('lookup.loading') }}</span>
        </button>
    </div>

    @error('profile')
        <p id="profile-error" class="font-mono text-xs text-red-700 dark:text-red-400" role="alert"><span aria-hidden="true">✕ </span>{{ $message }}</p>
    @enderror

    <p id="profile-help" class="text-xs text-muted">{{ __('lookup.help') }}</p>
</form>
