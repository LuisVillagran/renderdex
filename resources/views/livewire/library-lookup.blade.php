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
            class="min-w-0 flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-base placeholder:text-zinc-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-500/40 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:placeholder:text-zinc-500"
        >

        <button
            type="submit"
            class="rounded-lg bg-sky-600 px-4 py-2 font-medium text-white hover:bg-sky-700 focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 focus-visible:outline-none disabled:opacity-60 dark:focus-visible:ring-offset-zinc-950"
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="lookup">{{ __('lookup.submit') }}</span>
            <span wire:loading wire:target="lookup">{{ __('lookup.loading') }}</span>
        </button>
    </div>

    @error('profile')
        <p id="profile-error" class="text-sm text-red-700 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror

    <p id="profile-help" class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('lookup.help') }}</p>
</form>
