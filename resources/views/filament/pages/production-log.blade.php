<x-filament-panels::page>
    
    {{-- 1. Banner de Advertencia --}}
    @if(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Supervisor'))
        <x-filament::section icon="heroicon-o-information-circle" icon-color="info"  collapsed="true" collapsible="true">
            <x-slot name="heading">Información del Panel</x-slot>
            <h2 class="text-sm">
                Revisa el resumen de producción diaria y gestiona los registros de manera eficiente.
            </h2>
        </x-filament::section>
    @endif

    {{-- 2. Sección de Fecha y Turno --}}
    <form wire:submit.prevent="submit">
        {{ $this->form }}
    </form>

    {{-- 3. Tabla de Últimos Registros --}}
    <div class="mt-8">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <x-heroicon-o-list-bullet class="w-5 h-5 text-gray-400" />
            Ultima Producción Registrada en el Turno
        </h3>
        {{ $this->table }}
    </div>
</x-filament-panels::page>