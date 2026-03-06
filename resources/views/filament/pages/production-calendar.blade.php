<x-filament-panels::page>

@php
    $data    = $this->getCalendarioData();
    $dias    = $data['dias'];
    $turnos  = $data['turnos'];
    $filas   = $data['filas'];
    $totales = $data['totalesTurnos'];
    $semana  = $data['numSemana']; 

    $diasNombre = ['LUNES', 'MARTES', 'MIÉRCOLES', 'JUEVES', 'VIERNES', 'SÁBADO'];
@endphp

<div class="mt-0 space-y-6">
    {{-- Header --}}
    <div>
        <h2 class="text-lg font-bold text-gray-100 dark:text-white">
            Producción Semanal
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Semana {{ $semana }} • {{ $dias[0]->format('d/m/Y') }} — {{ $dias[5]->format('d/m/Y') }}
        </p>
    </div>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>
    {{-- Tabla estilo Filament --}}
    <div class="fi-ta overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900"
        style="max-height:70vh; overflow-y:auto;">
        <table class="fi-ta-table w-full text-sm divide-y divide-gray-200 dark:divide-white/10">
            <thead class="bg-gray-50 dark:bg-white/5" style="position:sticky; top:0; z-index:30;">
                <tr>
                    <th rowspan="2"
                        class="px-4 py-3 text-left text-xs font-semibold
                            text-gray-500 dark:text-gray-400 uppercase tracking-wide
                            bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-white/10"
                        style="position:sticky; left:0; top:0; z-index:40;">
                        Máquina
                    </th>
                    @foreach($dias as $i => $dia)
                        <th colspan="3"
                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide
                                bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-white/10">
                            {{ $diasNombre[$i] }}
                            <div class="text-[11px] font-normal text-gray-400">
                                {{ $dia->format('d/m/Y') }}
                            </div>
                        </th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($dias as $dia)
                        @foreach($turnos as $turno) 
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400
                                bg-gray-50 dark:bg-gray-800 border-r border-gray-200 dark:border-white/10">
                                {{ $turno }}
                            </th>
                        @endforeach
                    @endforeach
                </tr>
            </thead>
            
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse($filas as $fila)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                        {{-- Máquina --}}
                        <td class="px-4 py-3 font-medium whitespace-nowrap
                                    text-gray-950 dark:text-white
                                    bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-white/10"
                                style="position:sticky; left:0; ">
                            {{ $fila['maquina'] }}
                        </td>
                        @foreach($fila['dias'] as $diaData)
                            @foreach($turnos as $turno)
                                @php
                                    $kg = $diaData['celdas'][$turno] ?? null;
                                @endphp
                                @php
                                    $kg = $diaData['celdas'][$turno] ?? null;
                                    $bgTurno = match($turno) {
                                        'Mañana' => 'background:rgba(191, 4, 197, 0.06);',  // Morado
                                        'Tarde'  => 'background:rgba(9, 255, 0, 0.06);',  // Verde
                                        'Noche'  => 'background:rgba(245, 181, 6, 0.12);',   // Naranja
                                        default  => '',
                                    };
                                @endphp
                                <td class="px-4 py-3 text-center"
                                    style="{{ $bgTurno }}">
                                    @if($kg !== null)
                                        <span style="color: rgb(var(--info-600));">{{ number_format($kg, 2) }}</span>
                                    @else
                                        <span>N/T</span>
                                    @endif
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 1 + count($dias) * count($turnos) }}"
                            class="px-4 py-16 text-center text-sm text-gray-400">
                            Sin máquinas activas registradas
                        </td>
                    </tr>
                @endforelse
                {{-- Totales --}}
                <tr class="bg-gray-50 dark:bg-white/5 font-semibold">
                    <td class="px-4 py-3 text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-800"
                        style="position:sticky; left:0; z-index:10;">
                        Total Kg
                    </td>
                    @foreach($dias as $dia)
                        @foreach($turnos as $turno)
                            @php
                                $kg = $totales[$dia->toDateString()][$turno] ?? null;
                            @endphp
                            <td class="px-4 py-3 text-center text-gray-900 dark:text-white">
                                {{ $kg !== null ? number_format($kg,2) : '—' }}
                            </td>
                        @endforeach
                    @endforeach
                </tr>
            </tbody>
            
        </table>
    </div>
</div>

</x-filament-panels::page>