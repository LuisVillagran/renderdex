{{-- Las bibliotecas no se indexan: son de personas, aunque el perfil sea público. --}}
<x-layout :title="__('library.title')" noindex>
    <livewire:library-table :steam-id="$steamId" />
</x-layout>
