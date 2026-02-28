<?php

namespace App\Filament\Pages;
use App\Filament\Widgets\ProductionEfficiency; 
use App\Models\ProductionTotalLog;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $slug = 'dashboard';
    protected static ?string $title = 'Inicio';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.pages.dashboard';
    
    protected function getWidgets(): array
    {
        return [
            ProductionEfficiency::class, 
        ];
    }
    
    public function getProgressData(): array
    {
        $horaActual  = now()->hour;
        $turnoActual = match (true) {
            $horaActual >= 7  && $horaActual < 15 => '1er Turno',
            $horaActual >= 15 && $horaActual < 23 => '2do Turno',
            default                               => '3er Turno',
        };

        $metaDiaria     = 2000;
        $registrosDia   = ProductionTotalLog::whereDate('date_select', today())->get();
        $registrosTurno = $registrosDia->where('shift', $turnoActual);
        
        $totalDia    = (float) $registrosDia->sum('real');
        $totalTurno  = (float) $registrosTurno->sum('real');
        $metaTurno   = (float) $registrosTurno->sum('objetive');

        $porcentajeDia   = $metaDiaria > 0 ? min(($totalDia   / $metaDiaria) * 100, 100) : 0;
        $porcentajeTurno = $metaTurno  > 0 ? min(($totalTurno / $metaTurno)  * 100, 100) : 0;

        $colorBarra = fn(float $p) => match(true) {
            $p >= 100 => 'from-success-500 to-success-400',
            $p >= 50  => 'from-amber-500 to-yellow-400',
            default   => 'from-danger-600 to-rose-400',
        };

        $colorBadge = fn(float $p) => match(true) {
            $p >= 100 => 'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-300',
            $p >= 50  => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
            default   => 'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-300',
        };

        return [
            'turnoActual'     => $turnoActual,
            'metaDiaria'      => $metaDiaria,
            'totalDia'        => $totalDia,
            'porcentajeDia'   => $porcentajeDia,
            'colorBarraDia'   => $colorBarra($porcentajeDia),
            'colorBadgeDia'   => $colorBadge($porcentajeDia),
            'alcanzadaDia'    => $porcentajeDia >= 100,
            'metaTurno'       => $metaTurno,
            'totalTurno'      => $totalTurno,
            'porcentajeTurno' => $porcentajeTurno,
            'colorBarraTurno' => $colorBarra($porcentajeTurno),
            'colorBadgeTurno' => $colorBadge($porcentajeTurno),
            'alcanzadaTurno'  => $porcentajeTurno >= 100,
        ];
    }
    
}
