<?php

namespace App\Filament\Pages;

use App\Models\Machine;
use App\Models\ProductionLog;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ProductionCalendar extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Producción';
    protected static ?string $navigationLabel = 'Calendario';
    protected static ?string $title           = 'Calendario de Producción';
    protected static string  $view            = 'filament.pages.production-calendar';
    
    public ?array $data = [];
    public string $semanaInicio = '';
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_SUPERVISOR,
        ]);
    }
    
    public function mount(): void
    {
        // Lunes de la semana actual
        $this->semanaInicio = now()->startOfWeek()->toDateString();
        $this->form->fill(['semana' => $this->semanaInicio]);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('semana')
                    ->label('Seleccionar Semana:')
                    ->default(now()->startOfWeek())
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        // Ajusta al lunes de la semana seleccionada
                        $this->semanaInicio = Carbon::parse($state)
                            ->startOfWeek()
                            ->toDateString();
                    })
                    ->prefixIcon('heroicon-m-calendar-days'),
            ])
            ->statePath('data');
    }
    
    public function getCalendarioData(): array
    {
        $inicio   = Carbon::parse($this->semanaInicio)->startOfWeek();
        $turnos   = ['Mañana', 'Tarde', 'Noche'];
        $maquinas = Machine::where('is_active', true)->orderBy('name')->get();
        
        // Genera los 6 días (Lunes a Sábado)
        $dias = [];
        for ($i = 0; $i < 6; $i++) {
            $dias[] = $inicio->copy()->addDays($i);
        }
        
        // Query de registros de la semana
        $registros = ProductionLog::query()
            ->whereBetween('created_at', [
                $inicio->copy()->startOfDay(),
                $inicio->copy()->addDays(5)->endOfDay(),
            ])
            ->whereIn('status', ['Completa'])
            ->get()
            ->groupBy([
                fn($r) => Carbon::parse($r->created_at)->toDateString(),
                fn($r) => $r->shift,
                fn($r) => $r->machine_id,
            ]);
            
        // Filas por máquina
        $filas = [];
        foreach ($maquinas as $maquina) {
            $fila = ['maquina' => $maquina->name, 'dias' => []];
            
            foreach ($dias as $fecha) {
                $fechaStr = $fecha->toDateString();
                $totalDia = 0;
                $celdas   = [];
                
                foreach ($turnos as $turno) {
                    $kg = ($registros[$fechaStr][$turno][$maquina->id] ?? collect())
                        ->sum('kg_produced') ?: null;
                        
                    $celdas[$turno] = $kg;
                    if ($kg !== null) {
                        $totalDia += $kg;
                    }
                }
                
                $fila['dias'][] = [
                    'fecha'  => $fechaStr,
                    'celdas' => $celdas,
                    'total'  => $totalDia > 0 ? $totalDia : null,
                ];
            }
            
            $filas[] = $fila;
        }
        
        // Totales por turno y día
        $totalesTurnos = [];
        
        // Inicializa todas las fechas
        foreach ($dias as $fecha) {
            $totalesTurnos[$fecha->toDateString()] = [
                'Mañana' => null,
                'Tarde'  => null,
                'Noche'  => null,
                'total'  => null,
            ];
        }
        
        // Llena con datos reales
        foreach ($dias as $fecha) {
            $fechaStr = $fecha->toDateString();
            $totalDia = 0;
            
            foreach ($turnos as $turno) {
                $kg = 0;
                foreach ($maquinas as $maquina) {
                    $kg += ($registros[$fechaStr][$turno][$maquina->id] ?? collect())
                        ->sum('kg_produced');
                }
                $totalesTurnos[$fechaStr][$turno] = $kg > 0 ? $kg : null;
                $totalDia += $kg;
            }
            $totalesTurnos[$fechaStr]['total'] = $totalDia > 0 ? $totalDia : null;
        }
        
        return [
            'dias'          => $dias,
            'turnos'        => $turnos,
            'filas'         => $filas,
            'totalesTurnos' => $totalesTurnos,
            'numSemana' => $inicio->isoWeek(),
        ];
    }
}
