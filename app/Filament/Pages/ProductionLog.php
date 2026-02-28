<?php

namespace App\Filament\Pages;

use App\Models\Machine;
use App\Models\ProductionLog as ModelsProductionLog;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ProductionLog extends Page implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;
    
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Producción';
    protected static ?string $title           = 'Producción Diaria';
    protected static string  $view            = 'filament.pages.production-log';
    
    public ?array  $data        = [];
    public ?string $fechaFiltro = null;
    public ?string $turnoFiltro = null;
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERADOR,
            User::ROLE_SUPERVISOR,
        ]);
    }
    
    public function mount(): void
    {
        $this->form->fill();
        $this->fechaFiltro = now()->toDateString();
        $this->turnoFiltro = $this->turnoActual();
        
        abort_unless(auth()->user()->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERADOR,
            User::ROLE_SUPERVISOR,
        ]), 403);
    }
    
    private function turnoActual(): string
    {
        $now = now();
        $dia = $now->isoFormat('dddd'); // Lunes, Martes...
        $horaMinutos = $now->format('H:i');
        
        return match (true) {
            // *DOMINGO (Turno Extra)
            $dia === 'Domingo' => ($horaMinutos >= '22:00' || $horaMinutos < '06:00') ? 'Extra' : 'Fuera de Horario',
            
            // *CASO ESPECIAL: SÁBADO
            $dia === 'Sábado' => match (true) {
                $horaMinutos >= '06:30' && $horaMinutos < '14:30' => 'Mañana',
                $horaMinutos >= '14:30' && $horaMinutos < '22:00' => 'Tarde',
                default => 'Fuera de Horario',
            },

            // *VIERNES (Horario especial de cierre)
            $dia === 'Viernes' => match (true) {
                $horaMinutos >= '07:00' && $horaMinutos < '15:00' => 'Mañana',
                $horaMinutos >= '15:00' && $horaMinutos < '22:30' => 'Tarde',
                // El viernes la noche empieza 22:30
                $horaMinutos >= '22:30' || $horaMinutos < '07:00' => 'Noche',
                default => 'Fuera de Horario',
            },
            
            // *LUNES A JUEVES
            default => match (true) {
                $horaMinutos >= '07:00' && $horaMinutos < '15:00' => 'Mañana',
                $horaMinutos >= '15:00' && $horaMinutos < '22:30' => 'Tarde',
                // El turno de noche empieza 22:30 y termina a las 07:00 del día siguiente
                ($horaMinutos >= '22:30' || $horaMinutos < '07:00') => 'Noche',
                default => 'Fuera de Horario',
            },
        };
    }
    
    //!Usar en caso validación de horas
    private function validarHoraTurno(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            // 1. Parseo seguro: Filament a veces envía H:i:s, substr asegura H:i
            // Usamos parse para evitar errores si el formato varía ligeramente
            try {
                $horaStr = substr($value, 0, 5);
                $horaInput = Carbon::createFromFormat('H:i', $horaStr);
            } catch (\Exception $e) {
                $fail("⚠️ Formato de hora inválido.");
                return;
            }
            
            $turno = $this->turnoActual();
            
            $limites = [
                'Mañana' => ['start' => '07:00', 'end' => '14:59'],
                'Tarde'  => ['start' => '15:00', 'end' => '22:59'],
                'Noche'  => ['start' => '23:00', 'end' => '06:59'],
            ];
            
            // Verificamos que el turno exista en el array para evitar errores de índice
            if (!isset($limites[$turno])) {
                return; 
            }
            
            $start = Carbon::createFromFormat('H:i', $limites[$turno]['start']);
            $end   = Carbon::createFromFormat('H:i', $limites[$turno]['end']);
            
            // 2. Lógica de validación mejorada
            if ($start->gt($end)) {
                $esValido = ($horaInput->gte($start) || $horaInput->lte($end));
            } else {
                // Caso Mañana/Tarde: Rango lineal
                $esValido = $horaInput->between($start, $end);
            }
            
            if (!$esValido) {
                $campo = str_contains($attribute, 'start') ? 'inicio' : 'finalización';
                // Convertimos la hora de error a formato 12h para que el usuario la entienda mejor
                $horaAmigable = $horaInput->format('h:i A');
                
                $fail("⚠️ La hora de {$campo} ({$horaAmigable}) no pertenece al turno {$turno} ({$limites[$turno]['start']} - {$limites[$turno]['end']}).");
            }
        };
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección para Operador
                Section::make('Información de Jornada')
                    ->description('Fecha y turno correspondiente al horario actual.')
                    ->schema([
                        Placeholder::make('fecha_info')
                            ->label('Fecha de Operación')
                            ->content(function () {
                                $fecha = Carbon::now()->locale('es');
                                return ucfirst($fecha->translatedFormat('l, d F \d\e Y h:i'))
                                    . ' ' . strtolower($fecha->format('A'));
                            }),
                            
                        Placeholder::make('turno_info')
                            ->label('Turno en Curso')
                            ->content(fn () => match (true) {
                                now()->hour >= 7  && now()->hour < 15 => '☀️ Mañana — 07:00 am a 03:00 pm',
                                now()->hour >= 15 && now()->hour < 23 => '🌤️ Tarde — 03:00 pm a 11:00 pm',
                                default                               => '🌙 Noche — 11:00 pm a 07:00 am',
                            }),
                            
                        Placeholder::make('turno_rest')
                            ->label('Tiempo Restante')
                            ->content(function () {
                                $hora = now()->hour;
                                
                                if ($hora >= 7 && $hora < 15) {
                                    $fin  = now()->setTime(15, 0);
                                    $diff = now()->diff($fin);
                                    return new \Illuminate\Support\HtmlString(
                                        "☀️ Faltan <strong>{$diff->h}h {$diff->i}m</strong> para terminar"
                                    );
                                } elseif ($hora >= 15 && $hora < 23) {
                                    $fin  = now()->setTime(23, 0);
                                    $diff = now()->diff($fin);
                                    return new \Illuminate\Support\HtmlString(
                                        "🌤️ Faltan <strong>{$diff->h}h {$diff->i}m</strong> para terminar"
                                    );
                                } else {
                                    $fin  = $hora >= 23
                                        ? now()->addDay()->setTime(7, 0)
                                        : now()->setTime(7, 0);
                                    $diff = now()->diff($fin);
                                    return new \Illuminate\Support\HtmlString(
                                        "🌙 Faltan <strong>{$diff->h}h {$diff->i}m</strong> para terminar"
                                    );
                                }
                            }),
                    ])
                    ->columns(3)
                    ->compact()
                    ->visible(fn () => auth()->user()->hasRole(User::ROLE_OPERADOR)),
                    
                // Sección para Admin y Supervisor
                Section::make('Ajustes de Horario')
                    ->description('Configura la fecha y turno a consultar.')
                    ->schema([
                        DatePicker::make('created_at')
                            ->label('Fecha de Operación')
                            ->default(now())
                            ->live()
                            ->prefixIcon('heroicon-m-calendar-days')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $this->fechaFiltro    = $state ?? now()->toDateString();
                                $this->turnoFiltro    = null;
                                $this->data['shift']  = '';
                                $set('shift', '');
                                $this->resetTable();
                            }),
                            
                        Select::make('shift')
                            ->label('Turno en Curso')
                            ->options([
                                ''       => 'Seleccione el Turno:',
                                'Todos'  => '📋 Todos los Turnos',
                                'Mañana' => '☀️ Mañana',
                                'Tarde'  => '🌤️ Tarde',
                                'Noche'  => '🌙 Noche',
                            ])
                            ->default(fn () => $this->turnoActual())
                            ->live()
                            ->selectablePlaceholder(false)
                            ->afterStateUpdated(function ($state) {
                                $this->turnoFiltro = $state ?: null;
                                $this->resetTable();
                            }),
                    ])
                    ->columns(2)
                    ->hidden(fn () => auth()->user()->hasRole(User::ROLE_OPERADOR)),
            ])
            ->statePath('data');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrir_registro')
                ->hidden(fn () => auth()->user()->hasAnyRole([User::ROLE_SUPERVISOR, User::ROLE_ADMIN]))
                ->label('Registrar Producción')
                ->icon('heroicon-m-plus-circle')
                ->color('success')
                ->modalHeading('Nueva Entrada de Producción')
                ->modalWidth('md')
                ->form(function () {
                    
                    return [
                        Select::make('machine_id')
                            ->label('Máquina')
                            ->options(Machine::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->prefixIcon('heroicon-m-cpu-chip')
                            ->prefixIconColor('primary'),
                            
                        TextInput::make('kg_produced')
                            ->label('Kilogramos (Kg)')
                            ->placeholder('0.00')
                            ->step(0.01)
                            ->required()
                            ->prefixIcon('heroicon-m-scale')
                            ->prefixIconColor('primary')
                            ->extraInputAttributes([
                                'min'       => 1,
                                'max'       => 99.99,
                                'oninput'   => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1').replace(/(\.\d{2}).*/g, '$1'); if(parseFloat(this.value) > 99.99) this.value = 99.99;",
                            ]),
                            
                        Grid::make(2)
                            ->schema([
                                TimePicker::make('start_time')
                                    ->label('Hora Inicio')
                                    ->required()
                                    ->seconds(false)
                                    ->prefixIcon('heroicon-m-clock')
                                    ->hintIcon('heroicon-m-play')
                                    ->prefixIconColor('primary')
                                    ->hintColor('success')
                                    ->hintIconTooltip(fn () => match ($this->turnoActual()) {
                                        'Mañana' => '☀️ Inicia 07:00 am',
                                        'Tarde'  => '🌤️ Inicia 03:00 pm',
                                        'Noche'  => '🌙 Inicia 11:00 pm',
                                    }),
                                    /*->rules([fn () => $this->validarHoraTurno()
                                ]),*/
                                    
                                TimePicker::make('end_time')
                                    ->label('Hora Fin')
                                    ->required()
                                    ->seconds(false)
                                    ->prefixIcon('heroicon-m-clock')
                                    ->hintIcon('heroicon-m-pause')
                                    ->prefixIconColor('primary')
                                    ->hintColor('danger')
                                    ->hintIconTooltip(fn () => match ($this->turnoActual()) {
                                        'Mañana' => '☀️ Termina 02:59 am',
                                        'Tarde'  => '🌤️ Termina 10:59 pm',
                                        'Noche'  => '🌙 Termina 06:59 pm',
                                    })
                                    /*->rules([fn () => $this->validarHoraTurno()
                                ]),*/
                            ]),
                            
                                /*Select::make('observation')
                                    ->label('Observaciones')
                                    ->multiple()
                                    ->prefixIcon('heroicon-m-chat-bubble-left-right')
                                    ->options([
                                        'Maquina Trabada'   => 'Máquina Trabada',
                                        'Maquina Trabada'   => 'Máquina Trabada',
                                        'Descanso de Turno' => 'Descanso de Turno',
                                    ]),*/
                                TextInput::make('observation')
                                    ->label('Observaciones')
                                    ->placeholder('...')
                                    ->required()
                                    ->prefixIcon('heroicon-m-chat-bubble-left-right')
                                    ->prefixIconColor('primary'),
                    ];
                })
                ->action(function (array $data): void {
                    $pageData = $this->form->getState();
                    
                    $turno = $pageData['shift'] ?? $this->turnoActual();
                    if (empty($turno) || $turno === 'Todos') {
                        $turno = $this->turnoActual();
                    }
                    
                    /*$observacionesTexto = !empty($data['observation'])
                        ? implode(', ', $data['observation'])
                        : 'Ninguna';*/
                        
                    ModelsProductionLog::create([
                        'machine_id'  => $data['machine_id'],
                        'kg_produced' => $data['kg_produced'],
                        'start_time'  => $data['start_time'],
                        'end_time'    => $data['end_time'],
                        'user_id'     => auth()->id(),
                        'shift'       => $turno,
                        'observation' => $data['observation'],
                    ]);
                    
                    $this->resetTable();
                    
                    Notification::make()
                        ->title('¡Registro Exitoso!')
                        ->body('Los datos se han guardado correctamente.')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (empty($this->turnoFiltro)) {
                    return ModelsProductionLog::query()->whereRaw('1 = 0');
                }
                
                $query = ModelsProductionLog::query()
                    ->with(['machine', 'user'])
                    ->whereDate('created_at', $this->fechaFiltro ?? now()->toDateString());
                
                if ($this->turnoFiltro !== 'Todos') {
                    $query->where('shift', $this->turnoFiltro);
                }
                
                return $query;
            })
            ->columns([
                TextColumn::make('machine.name')
                    ->label('Máquina')
                    ->weight('bold')
                    ->color('primary')
                    ->sortable(),
                    
                TextColumn::make('user.name')
                    ->label('Operador')
                    ->icon('heroicon-m-user')
                    ->sortable(),
                    
                TextColumn::make('shift')
                    ->label('Turno')
                    ->weight('bold')
                    ->sortable(),
                    
                TextColumn::make('created_at')
                    ->label('Registro')
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Inicio')
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Finalizó')
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('kg_produced')
                    ->label('Producción')
                    ->suffix(' Kg')
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('observation')
                    ->label('Observaciones')
                    ->badge()
                    ->separator(', ')
                    ->color('warning'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin registros en este turno');
    }

    public function updatedData(): void
    {
        $this->resetTable();
    }
}