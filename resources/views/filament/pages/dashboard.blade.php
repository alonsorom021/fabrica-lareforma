<x-filament-panels::page>
    <div  class="mt-8">
        <div class="flex-1 space-y-4">
            <h3 class="text-2xl font-black tracking-tight text-gray-950 dark:text-white">
                Bienvenido al Sistema de Producción
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 max-w-2x2 leading-relaxed">
                Este sistema está diseñado para monitorear y optimizar los procesos de producción de la planta, proporcionando herramientas avanzadas para el seguimiento en tiempo real de sus indicadores clave.
            </p>
        </div> 
        <div class="flex items-center gap-3 pt-2">
            <x-filament::button icon="heroicon-s-play-circle" color="warning" class="shadow-sm">
                Ver Tutorial
            </x-filament::button>
            <x-filament::button color="gray" outlined>
                Documentación
            </x-filament::button> 
        </div>  
    </div>
    
    <div class="mt-2">
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="3"
        />
    </div>
    {{-- PROGRESS BAR --}}
    @php $d = $this->getProgressData(); @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-600">
                    <x-heroicon-o-chart-bar class="w-4 h-4 text-white"/>
                </div>
                <span>Progreso de Producción</span>
            </div>
        </x-slot>
        <div class="space-y-6">
            {{-- META DIARIA --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-sun class="w-4 h-4 text-gray-400"/>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Meta Diaria</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $d['colorBadgeDia'] }}">
                            {{ $d['alcanzadaDia'] ? '¡Meta alcanzada! 🎉' : 'En progreso' }}
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ number_format($d['porcentajeDia'], 1) }}%
                        </span>
                    </div>
                </div>

                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden shadow-inner">
                    <div class="bg-gradient-to-r {{ $d['colorBarraDia'] }} h-5 rounded-full transition-all duration-1000 flex items-center justify-end pr-2"
                         style="width: {{ $d['porcentajeDia'] }}%">
                        @if($d['porcentajeDia'] > 12)
                            <span class="text-white text-[10px] font-bold">
                                {{ number_format($d['porcentajeDia'], 0) }}%
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex justify-between mt-1.5 text-xs text-gray-400">
                    <span>{{ number_format($d['totalDia'], 0) }} Kg producidos</span>
                    <span>Meta: {{ number_format($d['metaDiaria'], 0) }} Kg</span>
                </div>
            </div>

            <hr class="border-gray-100 dark:border-gray-700">

            {{-- META DEL TURNO --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400"/>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ $d['turnoActual'] }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $d['colorBadgeTurno'] }}">
                            {{ $d['alcanzadaTurno'] ? '¡Objetivo cumplido! ✅' : 'En progreso' }}
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ number_format($d['porcentajeTurno'], 1) }}%
                        </span>
                    </div>
                </div>

                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden shadow-inner">
                    <div class="bg-gradient-to-r {{ $d['colorBarraTurno'] }} h-5 rounded-full transition-all duration-1000 flex items-center justify-end pr-2"
                         style="width: {{ $d['porcentajeTurno'] }}%">
                        @if($d['porcentajeTurno'] > 12)
                            <span class="text-white text-[10px] font-bold">
                                {{ number_format($d['porcentajeTurno'], 0) }}%
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex justify-between mt-1.5 text-xs text-gray-400">
                    <span>{{ number_format($d['totalTurno'], 0) }} Kg producidos</span>
                    <span>Objetivo: {{ number_format($d['metaTurno'], 0) }} Kg</span>
                </div>
            </div>

        </div>
    </x-filament::section>
<x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-600">
                    <x-heroicon-o-chart-bar class="w-4 h-4 text-white"/>
                </div>
                <span>Progreso de Producción</span>
            </div>
        </x-slot>
        <div class="space-y-6">
            {{-- META DIARIA --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-sun class="w-4 h-4 text-gray-400"/>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Meta Diaria</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $d['colorBadgeDia'] }}">
                            {{ $d['alcanzadaDia'] ? '¡Meta alcanzada! 🎉' : 'En progreso' }}
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ number_format($d['porcentajeDia'], 1) }}%
                        </span>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden shadow-inner">
                    <div class="bg-gradient-to-r {{ $d['colorBarraDia'] }} h-5 rounded-full transition-all duration-1000 flex items-center justify-end pr-2"
                        style="width: {{ $d['porcentajeDia'] }}%">
                        @if($d['porcentajeDia'] > 12)
                            <span class="text-white text-[10px] font-bold">
                                {{ number_format($d['porcentajeDia'], 0) }}%
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between mt-1.5 text-xs text-gray-400">
                    <span>{{ number_format($d['totalDia'], 0) }} Kg producidos</span>
                    <span>Meta: {{ number_format($d['metaDiaria'], 0) }} Kg</span>
                </div>
            </div>
            <hr class="border-gray-100 dark:border-gray-700">
            {{-- META DEL TURNO --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400"/>
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                            {{ $d['turnoActual'] }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $d['colorBadgeTurno'] }}">
                            {{ $d['alcanzadaTurno'] ? '¡Objetivo cumplido! ✅' : 'En progreso' }}
                        </span>
                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ number_format($d['porcentajeTurno'], 1) }}%
                        </span>
                    </div>
                </div>
                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-5 overflow-hidden shadow-inner">
                    <div class="bg-gradient-to-r {{ $d['colorBarraTurno'] }} h-5 rounded-full transition-all duration-1000 flex items-center justify-end pr-2"
                        style="width: {{ $d['porcentajeTurno'] }}%">
                        @if($d['porcentajeTurno'] > 12)
                            <span class="text-white text-[10px] font-bold">
                                {{ number_format($d['porcentajeTurno'], 0) }}%
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between mt-1.5 text-xs text-gray-400">
                    <span>{{ number_format($d['totalTurno'], 0) }} Kg producidos</span>
                    <span>Objetivo: {{ number_format($d['metaTurno'], 0) }} Kg</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>