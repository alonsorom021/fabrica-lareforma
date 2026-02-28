<?php

namespace App\Filament\Widgets;

use App\Models\ProductionTotalLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductionEfficiency extends BaseWidget
{
    protected function getStats(): array
    {
        // 1. Turno
        $horaActual = now()->hour;
        $turnoActual = match (true) {
            $horaActual >= 7 && $horaActual < 15  => '1er Turno',
            $horaActual >= 15 && $horaActual < 23 => '2do Turno',
            default                               => '3er Turno',
        };
        // 2. Consulta
        $registros = ProductionTotalLog::query()
            ->whereDate('date_select', today())
            ->where('shift', $turnoActual)
            ->get();
        // 3. Cálculos Dinámicos
        $totalProducido     = (float) $registros->sum('real');
        $metaDelTurno       = (float) $registros->sum('objetive');
        $eficienciaPromedio = (float) ($registros->avg('efficiency') ?? 0);
        $datosEficiencia    = $registros->sortBy('created_at')
                                        ->pluck('efficiency')
                                        ->take(10)
                                        ->toArray();
        // 4. Cálculo de porcentaje de avance real
        if ($totalProducido === 0.0) {
            $conteoSinTurno = ProductionTotalLog::whereDate('date_select', today())->count();
            $descripcionAux = $conteoSinTurno > 0
                ? "Hay {$conteoSinTurno} registros hoy, pero no son del {$turnoActual}"
                : 'No hay registros hoy';
        } else {
            $descripcionAux = 'Meta del turno: ' . number_format($metaDelTurno, 2) . ' Kg';
        }

        // 5. Colores
        $porcentajeAvance = $metaDelTurno > 0 
            ? min(($totalProducido / $metaDelTurno) * 100, 100) 
            : 0;

        $colorEficiencia = match(true) {
            $eficienciaPromedio >= 90 => 'success',
            $eficienciaPromedio >= 70 => 'warning',
            default                   => 'danger',
        };

        $colorPendiente = match(true) {
            $metaDelTurno === 0.0    => 'gray',
            $porcentajeAvance >= 80  => 'success',
            $porcentajeAvance >= 50  => 'warning',
            default                  => 'danger',
        };
        
        return [
            Stat::make('Producción: ' . $turnoActual, number_format($totalProducido, 2) . ' Kg')
                ->description($descripcionAux)
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color($colorEficiencia),
            Stat::make('Objetivo', number_format(max(0, $metaDelTurno - $totalProducido), 2) . ' Kg')
            ->description('Avance del turno: ' . number_format($porcentajeAvance, 1) . '%')
            ->descriptionIcon('heroicon-m-arrow-path')
            ->color($colorPendiente),
            Stat::make('Eficiencia', number_format($eficienciaPromedio, 1) . '%')
                ->description($eficienciaPromedio >= 90 ? 'Excelente rendimiento' : 'Revisar máquinas lentas')
                ->descriptionIcon('heroicon-m-bolt')
                ->chart($datosEficiencia)
                ->color($colorEficiencia), 
        ];
    }
    
}