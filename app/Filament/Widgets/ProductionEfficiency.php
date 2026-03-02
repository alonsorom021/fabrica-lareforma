<?php

namespace App\Filament\Widgets;

use App\Models\Machine;
use App\Models\ProductionLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductionEfficiency extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    protected int | string | array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 3;
    }

    private function turnoActual(): string
    {
        return match (true) {
            now()->hour >= 7  && now()->hour < 15 => 'Mañana',
            now()->hour >= 15 && now()->hour < 23 => 'Tarde',
            default                               => 'Noche',
        };
    }

    private function calcularMetaTurno(string $turno, $maquinasIds): float
    {
        return Machine::whereIn('id', $maquinasIds)
            ->get()
            ->sum(function (Machine $machine) use ($turno) {
                $real = (float) $machine->real;
                return match($turno) {
                    'Mañana' => $real,
                    'Tarde'  => ($real / 8) * 7.5,
                    'Noche'  => ($real / 8) * 8.5,
                    default  => $real,
                };
            });
    } 
    
    protected function getStats(): array
    {
        $turnoActual     = $this->turnoActual();

        $registros       = ProductionLog::query()
            ->whereDate('created_at', today())
            ->where('shift', $turnoActual)
            ->with('machine')
            ->get();

        $totalProducido  = (float) $registros->sum('kg_produced');
        $totalRegistros  = $registros->count();
        $maquinasActivas = $registros->pluck('machine_id')->unique();
        $metaTurno       = $this->calcularMetaTurno($turnoActual, $maquinasActivas);

        $porcentaje = $metaTurno > 0
            ? min(($totalProducido / $metaTurno) * 100, 100)
            : 0;
        $faltante   = max(0, $metaTurno - $totalProducido);

        // 👈 color dinámico según avance
        $colorAvance = match(true) {
            $totalProducido === 0.0 => 'gray',
            $porcentaje >= 100      => 'success',  // verde
            $porcentaje >= 50       => 'warning',  // amarillo
            default                 => 'danger',   // rojo
        };

        // 👈 color registros según actividad
        $colorRegistros = match(true) {
            $totalRegistros === 0          => 'gray',
            $totalRegistros >= 10          => 'success',
            $totalRegistros >= 5           => 'warning',
            default                        => 'danger',
        };

        // 👈 color máquinas según cantidad activa
        $colorMaquinas = match(true) {
            $maquinasActivas->count() === 0 => 'gray',
            $maquinasActivas->count() >= 5  => 'success',
            $maquinasActivas->count() >= 3  => 'warning',
            default                         => 'danger',
        };

        if ($totalProducido === 0.0) {
            $conteoSinTurno = ProductionLog::whereDate('created_at', today())->count();
            $descripcionAux = $conteoSinTurno > 0
                ? "Hay {$conteoSinTurno} registros hoy, pero no son del turno {$turnoActual}"
                : 'No hay registros hoy';
        } else {
            $descripcionAux = $porcentaje >= 100
                ? '✅ Meta alcanzada'
                : '⏳ Faltan ' . number_format($faltante, 2) . ' Kg — ' . number_format($porcentaje, 1) . '%';
        }

        $datosChart = $registros->sortBy('created_at')
            ->pluck('kg_produced')
            ->filter()
            ->map(fn ($v) => (float) $v)
            ->take(10)
            ->values()
            ->toArray();

        return [
           Stat::make('Producción: ' . $turnoActual, number_format($totalProducido, 2) . ' Kg')
                ->description($descripcionAux)
                ->descriptionIcon($porcentaje >= 100 ? 'heroicon-m-check-circle' : 'heroicon-m-scale') // 👈
                ->color($colorAvance)
                ->chart($datosChart),

            Stat::make('Registros del Turno', $totalRegistros)
                ->description('Último: ' . ($registros->last()?->created_at?->format('h:i A') ?? 'Sin registros'))
                ->descriptionIcon('heroicon-m-clipboard-document-list') // 👈
                ->color($colorRegistros),

            Stat::make('Máquinas Trabajando', $maquinasActivas->count())
                ->description("En turno {$turnoActual} hoy")
                ->descriptionIcon('heroicon-m-cog-6-tooth') // 👈
                ->color($colorMaquinas),
        ];
    }
}