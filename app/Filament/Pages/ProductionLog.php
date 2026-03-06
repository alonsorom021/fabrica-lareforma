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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TablesAction;
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
        abort_unless(auth()->user()->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_OPERADOR,
            User::ROLE_SUPERVISOR,
        ]), 403);

        $this->form->fill([
            'fechaFiltro' => now()->toDateString(),
            'shift'       => $this->turnoActual(),
        ]);

        $this->data['fechaFiltro'] = now()->toDateString();
        $this->data['shift']       = $this->turnoActual();
        $this->turnoFiltro         = $this->turnoActual();
    }

    private function turnoActual(): string
    {
        $now         = now();
        $dia         = $now->isoFormat('dddd');
        $horaMinutos = $now->format('H:i');

        return match (true) {
            $dia === 'Domingo' => ($horaMinutos >= '22:00' || $horaMinutos < '06:00')
                ? 'Extra'
                : 'Fuera de Horario',

            $dia === 'Sábado' => match (true) {
                $horaMinutos >= '06:30' && $horaMinutos < '14:30' => 'Mañana',
                $horaMinutos >= '14:30' && $horaMinutos < '22:00' => 'Tarde',
                default                                           => 'Fuera de Horario',
            },

            $dia === 'Viernes' => match (true) {
                $horaMinutos >= '07:00' && $horaMinutos < '15:00' => 'Mañana',
                $horaMinutos >= '15:00' && $horaMinutos < '22:30' => 'Tarde',
                $horaMinutos >= '22:30' || $horaMinutos < '07:00' => 'Noche',
                default                                           => 'Fuera de Horario',
            },

            default => match (true) {
                $horaMinutos >= '07:00' && $horaMinutos < '15:00' => 'Mañana',
                $horaMinutos >= '15:00' && $horaMinutos < '22:30' => 'Tarde',
                ($horaMinutos >= '22:30' || $horaMinutos < '07:00') => 'Noche',
                default                                            => 'Fuera de Horario',
            },
        };
    }
    
    private function actualizaTurno(string $hora): string
    {
        $horaMinutos = substr($hora, 0, 5);

        return match (true) {
            $horaMinutos >= '07:00' && $horaMinutos < '15:00' => 'Mañana',
            $horaMinutos >= '15:00' && $horaMinutos < '22:30' => 'Tarde',
            $horaMinutos >= '22:30' || $horaMinutos < '07:00' => 'Noche',
            default                                           => 'Fuera de Horario',
        };
    }

    private function advertenciaHoraTurno(?string $state): ?string
    {
        if (!$state) return null;

        $horaMinutos = substr($state, 0, 5);
        $turno       = $this->turnoActual();
        $limites     = [
            'Mañana' => ['start' => '07:00', 'end' => '15:00'],
            'Tarde'  => ['start' => '15:00', 'end' => '22:30'],
            'Noche'  => ['start' => '22:30', 'end' => '07:00'],
        ];

        if (!isset($limites[$turno])) return null;

        $start    = $limites[$turno]['start'];
        $end      = $limites[$turno]['end'];
        $esValido = $start > $end
            ? ($horaMinutos >= $start || $horaMinutos <= $end)
            : ($horaMinutos >= $start && $horaMinutos <= $end);

        if ($esValido) return null;

        // ← Formato 12hrs
        $startAmPm = Carbon::createFromFormat('H:i', $start)->format('h:i A');
        $endAmPm   = Carbon::createFromFormat('H:i', $end)->format('h:i A');

        return "⚠️ Fuera del turno ({$startAmPm} - {$endAmPm})";
    }
    //!No Borrar la función validarHoraTurno 
    private function validarHoraTurno(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            try {
                $horaStr   = substr($value, 0, 5);
                $horaInput = Carbon::createFromFormat('H:i', $horaStr);
            } catch (\Exception $e) {
                $fail("⚠️ Formato de hora inválido.");
                return;
            }

            $turno  = $this->turnoActual();
            $limites = [
                'Mañana' => ['start' => '07:00', 'end' => '14:59'],
                'Tarde'  => ['start' => '15:00', 'end' => '22:59'],
                'Noche'  => ['start' => '23:00', 'end' => '06:59'],
            ];

            if (!isset($limites[$turno])) {
                return;
            }

            $start = Carbon::createFromFormat('H:i', $limites[$turno]['start']);
            $end   = Carbon::createFromFormat('H:i', $limites[$turno]['end']);

            if ($start->gt($end)) {
                $esValido = ($horaInput->gte($start) || $horaInput->lte($end));
            } else {
                $esValido = $horaInput->between($start, $end);
            }

            if (!$esValido) {
                $campo        = str_contains($attribute, 'start') ? 'inicio' : 'finalización';
                $horaAmigable = $horaInput->format('h:i A');
                $fail("⚠️ La hora de {$campo} ({$horaAmigable}) no pertenece al turno {$turno} ({$limites[$turno]['start']} - {$limites[$turno]['end']}).");
            }
        };
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    
                Section::make('Ajustes de Horario')
                    ->description('Configura la fecha y turno a consultar.')
                    ->schema([
                        DatePicker::make('fechaFiltro')
                            ->label('Fecha de Operación')
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetTable())
                            ->minDate(fn () => auth()->user()->hasAnyRole(['Admin', 'Supervisor']) ? null : now()->subDay()->startOfDay())
                            ->maxDate(fn () => auth()->user()->hasAnyRole(['Admin', 'Supervisor']) ? null : now()->endOfDay())
                            ->helperText(function () {
                                $esAdmin = auth()->user()->hasAnyRole(['Admin', 'Supervisor']);
                                $mensaje = $esAdmin
                                    ? 'Acceso total al historial de fechas.'
                                    : 'Solo puedes consultar el turno actual y el anterior.';
                                $color   = $esAdmin ? '#3b82f6' : '#f59e0b';
                                
                                return new \Illuminate\Support\HtmlString("
                                    <span style='color: {$color}; font-weight: 500;'>{$mensaje}</span>
                                ");
                            })
                            ->prefixIcon('heroicon-m-calendar-days')
                            ->prefixIconColor(fn () => auth()->user()->hasAnyRole(['Admin', 'Supervisor']) ? 'info' : 'warning'),
                            
                        Select::make('shift')
                            ->label('Turno en Curso')
                            ->options([
                                'Todos'  => '📋 Todos los Turnos',
                                'Mañana' => '☀️ Mañana',
                                'Tarde'  => '🌤️ Tarde',
                                'Noche'  => '🌙 Noche',
                            ])
                            ->default('Todos')
                            ->live()
                            ->afterStateUpdated(fn () => $this->resetTable()),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(fn () => auth()->user()->hasRole(User::ROLE_OPERADOR)),
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
                ->form([
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
                        ->numeric()
                        ->step(0.01)
                        ->required()
                        ->prefixIcon('heroicon-m-scale')
                        ->prefixIconColor('primary')
                        ->extraInputAttributes([
                            'min'       => 1,
                            'max'       => 99.99,
                            'oninput' => " let v = this.value; let pos = this.selectionStart; let clean = v.replace(/[^0-9.]/g, ''); const parts = clean.split('.'); if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
                                if (parts[1] !== undefined && parts[1].length > 2) clean = parts[0] + '.' + parts[1].slice(0, 2);
                                if (clean !== v) {
                                    this.value = clean;
                                    this.setSelectionRange(pos, pos);
                                }
                            ",
                        ]),

                    TimePicker::make('start_time')
                            ->label('Hora Inicio')
                            ->required()
                            ->seconds(false)
                            ->live()
                            ->prefixIcon('heroicon-m-clock')
                            ->prefixIconColor('success') 
                            ->hint(fn ($state) => $this->advertenciaHoraTurno($state) ?? '')
                            ->hintColor(fn ($state) => $this->advertenciaHoraTurno($state) ? 'warning' : 'success'),

                    TextInput::make('observation')
                        ->label('Observaciones')
                        ->placeholder('...')
                        ->prefixIcon('heroicon-m-chat-bubble-left-right')
                        ->prefixIconColor('primary'),
                ])
                ->modalSubmitActionLabel('Registrar')
                ->modalCancelActionLabel('Cancelar')
                ->action(function (array $data): void {
                    $turnoAsignado = ($this->turnoFiltro && $this->turnoFiltro !== 'Todos')
                        ? $this->turnoFiltro
                        : $this->turnoActual();

                    ModelsProductionLog::create([
                        'machine_id'  => $data['machine_id'],
                        'kg_produced' => $data['kg_produced'],
                        'start_time'  => Carbon::parse($data['start_time'])->format('H:i:s'),
                        'end_time'    => null,
                        'user_id'     => auth()->id(),
                        'status'      => 'En Curso',
                        'shift'       => $turnoAsignado,
                        'observation' => $data['observation'] ?? null,
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
            ->query(
                ModelsProductionLog::query()
                    ->with(['machine', 'user', 'operatorStop'])
                    ->whereDate('created_at', $this->data['fechaFiltro'] ?? now()->toDateString())
                    ->when(($this->data['shift'] ?? 'Todos') !== 'Todos', fn ($q) =>
                        $q->where('shift', $this->data['shift'])
                    )
                    ->orderBy('start_time', 'desc')
                    ->orderBy('machine_id', 'desc')
            )
            ->columns([
                TextColumn::make('machine.name')
                    ->label('Máquina')
                    ->weight('bold')
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Op. Arranque')
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('success')
                    ->sortable(),

                TextColumn::make('operatorStop.name')
                    ->label('Op. Paro')
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('danger')
                    ->default('-')
                    ->sortable(),

                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'Mañana' => 'heroicon-m-sun',
                        'Tarde'  => 'heroicon-m-cloud',
                        'Noche'  => 'heroicon-m-moon',
                        default  => 'heroicon-m-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Mañana' => 'primary',
                        'Tarde'  => 'info',
                        'Noche'  => 'gray',
                        default  => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registro')
                    ->date('d/m/y')
                    ->sortable()
                    ->html()
                    ->description(function ($record) {
                        if (auth()->user()->hasAnyRole(['Admin', 'Supervisor'])) {
                            $hora = $record->created_at->format('h:i A');
                            return new \Illuminate\Support\HtmlString("
                                <span style='color: #3b82f6; font-size: 0.85rem; font-weight: 500;'>
                                    {$hora}
                                </span>
                            ");
                        }
                        return null;
                    }),

                TextColumn::make('start_time')
                    ->label('Inicio')
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Finalizó')
                    ->placeholder('⏳ En Curso')
                    ->time('h:i A')
                    ->sortable(),

                TextColumn::make('kg_produced')
                    ->label('Producción')
                    ->suffix(' Kg')
                    ->color('primary')
                    ->weight('bold')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),

                TextColumn::make('observation')
                    ->label('Observaciones')
                    ->limit(20)
                    ->size('sm')
                    ->color('gray')
                    ->placeholder('⏳ En proceso...')
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= $column->getCharacterLimit()) {
                            return null;
                        }
                        return $state;
                    }),
            ])
            ->actions([
            TablesAction::make('detener')
                ->label('Detener')
                ->icon('heroicon-m-stop-circle')
                ->color('danger')
                ->button()
                ->size('sm')
                ->visible(fn ($record) =>
                    $record->status === 'En Curso' &&
                    !auth()->user()->hasAnyRole(['Admin', 'Supervisor'])
                )
                ->fillForm(fn ($record) => [
                    'kg_produced' => number_format((float) $record->kg_produced, 2, '.', ''),
                    'start_time'  => $record->start_time ?? null,
                ])
                ->form([
                    TextInput::make('kg_produced')
                        ->label('Corregir Kilogramos')
                        ->placeholder('0.00')
                        ->step(0.01)
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->prefixIcon('heroicon-m-scale')
                        ->prefixIconColor('primary')
                        ->extraInputAttributes([
                            'min'       => 1,
                            'max'       => 99.99,
                            'oninput' => " let v = this.value; let pos = this.selectionStart; let clean = v.replace(/[^0-9.]/g, ''); const parts = clean.split('.'); if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
                                if (parts[1] !== undefined && parts[1].length > 2) clean = parts[0] + '.' + parts[1].slice(0, 2);
                                if (clean !== v) {
                                    this.value = clean;
                                    this.setSelectionRange(pos, pos);
                                }
                            ",
                        ]),
                        
                    TimePicker::make('start_time')
                        ->label('Corregir Hora Inicio')
                        ->required()
                        ->seconds(false)
                        ->live()
                        ->prefixIcon('heroicon-m-clock')
                        ->prefixIconColor('success') 
                        ->hint(fn ($state) => $this->advertenciaHoraTurno($state) ?? '')
                        ->hintColor(fn ($state) => $this->advertenciaHoraTurno($state) ? 'warning' : 'success'),
                        
                    TimePicker::make('end_time')
                        ->label('Hora Fin')
                        ->required()
                        ->seconds(false)
                        ->live()
                        ->prefixIcon('heroicon-m-clock')
                        ->prefixIconColor('danger')
                        ->hint(fn ($state) => $this->advertenciaHoraTurno($state) ?? '')
                        ->hintColor(fn ($state) => $this->advertenciaHoraTurno($state) ? 'warning' : 'danger'),
                        
                    TextInput::make('observation')
                        ->label('Observaciones')
                        ->placeholder('...')
                        ->required()
                        ->prefixIcon('heroicon-m-chat-bubble-left-right')
                        ->prefixIconColor('primary')
                        ->datalist([
                            'Finalización Mudada',
                            'Corrección de kilogramos',
                            'Error de captura',
                            'Ajuste por merma',
                            'Solicitud de supervisor',
                            'Paro por mantenimiento',
                            'Descanso de turno',
                        ]),
                ])
                ->modalSubmitActionLabel('Detener')
                ->modalCancelActionLabel('Cancelar')
                ->action(function ($record, array $data) {
                    $record->update([
                        'kg_produced'      => $data['kg_produced'],
                        'observation'      => $data['observation'],
                        'end_time'         => Carbon::parse($data['end_time'])->format('H:i:s'),
                        'shift'            => $this->actualizaTurno($data['end_time']),
                        'user_stop_id'     => auth()->id(),
                        'status'      => 'Completa', 
                    ]);
                    Notification::make()
                        ->title('Producción Detenida')
                        ->success()
                        ->send();
                }),

            // -- EDITAR (Admin/Supervisor: siempre | Operador: solo si no editó aún) --
            TablesAction::make('editar_registro')
                ->label('Editar')
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->button()
                ->size('sm')
                ->modalSubmitActionLabel('Actualizar')
                ->modalCancelActionLabel('Cancelar')
                ->visible(function ($record) {
                    if (auth()->user()->hasAnyRole(['Admin', 'Supervisor'])) {
                        return true;
                    }
                    return $record->status === 'Completa' && !$record->edited_by_operator;
                })
                ->fillForm(fn ($record) => [
                    'kg_produced'        => number_format((float) $record->kg_produced, 2, '.', ''),
                    'start_time'         => $record->start_time ?? null,
                    'end_time'           => $record->end_time ?? null,
                    'observation'        => $record->observation,
                    'machine_name'       => $record->machine->name       ?? '-',
                    'user_name'          => $record->user->name          ?? '-',
                    'user_stop_name'     => $record->operatorStop->name  ?? '-',
                    'shift'              => $record->shift,
                    'edited_by_operator' => $record->edited_by_operator,
                ])
                ->form([
                    Section::make('Datos de Producción')
                        ->description('Actualiza los campos correspondientes a tu turno.')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->schema([
                            TextInput::make('kg_produced')
                                ->label('Kilogramos Finales')
                                ->placeholder('0.00')
                                ->numeric()
                                ->step(0.01)
                                ->required()
                                ->minValue(1)
                                ->prefixIcon('heroicon-m-scale')
                                ->prefixIconColor('warning')
                                ->extraInputAttributes([
                                    'min'     => 1,
                                    'max'     => 99.99,
                                    'oninput' => "
                                        let v = this.value; let pos = this.selectionStart; let clean = v.replace(/[^0-9.]/g, ''); const parts = clean.split('.');
                                        if (parts.length > 2) clean = parts[0] + '.' + parts.slice(1).join('');
                                        if (parts[1] !== undefined && parts[1].length > 2) clean = parts[0] + '.' + parts[1].slice(0, 2);
                                        if (clean !== v) {
                                            this.value = clean;
                                            this.setSelectionRange(pos, pos);
                                        }
                                    ",
                                ]),
                                
                            TextInput::make('observation')
                                ->label('Observaciones')
                                ->placeholder('...')
                                ->prefixIcon('heroicon-m-chat-bubble-left-right')
                                ->prefixIconColor('primary')
                                ->datalist([
                                    'Finalización Mudada',
                                    'Corrección de kilogramos',
                                    'Error de captura',
                                    'Ajuste por merma',
                                    'Solicitud de supervisor',
                                    'Paro por mantenimiento',
                                    'Descanso de turno',
                                ]),
                                
                            TimePicker::make('start_time')
                                ->label('Hora Inicio')
                                ->required()
                                ->seconds(false)
                                ->live()
                                ->prefixIcon('heroicon-m-clock')
                                ->prefixIconColor('success') 
                                ->hint(fn ($state) => $this->advertenciaHoraTurno($state) ?? '')
                                ->hintColor(fn ($state) => $this->advertenciaHoraTurno($state) ? 'warning' : 'success'),
                                
                            TimePicker::make('end_time')
                                ->label('Hora Fin')
                                ->required()
                                ->seconds(false)
                                ->live()
                                ->prefixIcon('heroicon-m-clock')
                                ->prefixIconColor('danger')
                                ->hint(fn ($state) => $this->advertenciaHoraTurno($state) ?? '')
                                ->hintColor(fn ($state) => $this->advertenciaHoraTurno($state) ? 'warning' : 'danger'),
                                
                                Toggle::make('confirmar')
                                ->label('Confirmo que los datos son correctos')
                                ->required()
                                ->accepted()
                                ->validationMessages([
                                    'accepted' => 'Debes confirmar que los datos son correctos antes de continuar.',
                                ])
                                ->columnSpanFull()
                        ])
                        ->columns(2)
                        ->visible(fn () => auth()->user()->hasRole(User::ROLE_OPERADOR)),
                    // --- CAMPOS SOLO ADMIN/SUPERVISOR ---
                    Section::make('Ajustes Administrativos')
                        ->description('Solo visible para Admin y Supervisor.')
                        ->icon('heroicon-m-shield-check')
                        ->schema([
                            TextInput::make('machine_name')
                                ->label('Máquina')
                                ->readOnly()
                                ->prefixIcon('heroicon-m-cpu-chip')
                                ->prefixIconColor('primary'),
                                
                            TextInput::make('user_name')
                                ->label('Operador de Arranque')
                                ->readOnly()
                                ->prefixIcon('heroicon-m-user-circle')
                                ->prefixIconColor('success'),
                                
                            TextInput::make('user_stop_name')
                                ->label('Operador de Paro')
                                ->readOnly()
                                ->prefixIcon('heroicon-m-user-circle')
                                ->prefixIconColor('danger'),
                            Toggle::make('edited_by_operator')
                                ->label('Editar Registro de Operador')
                                ->helperText('Activa/Desactiva para permitir que el operador vuelva a editar.')
                                ->onColor('danger')
                                ->offColor('success')
                                ->onIcon('heroicon-m-lock-closed')
                                ->offIcon('heroicon-m-lock-open')
                                ->columnSpanFull()
                        ]) 
                        ->columns(3)
                        ->visible(fn () => auth()->user()->hasAnyRole(['Admin', 'Supervisor'])),
                ])
                ->action(function ($record, array $data) {
                    
                    $isOperator = !auth()->user()->hasAnyRole(['Admin', 'Supervisor']);
                    
                    if ($isOperator) {
                        // OPERADOR
                        $endTime = $data['end_time'] ?? $record->end_time;
                        
                        $record->update([
                            'kg_produced'        => $data['kg_produced'],
                            'observation'        => $data['observation'] ?? null,
                            'status'             => 'Completa',
                            'start_time'         => Carbon::parse($data['start_time'])->format('H:i:s'),
                            'end_time'           => Carbon::parse($endTime)->format('H:i:s'),
                            'shift'              => $this->actualizaTurno($endTime),
                            'edited_by_operator' => true,
                        ]);
                        
                        Notification::make()
                            ->title('Registro Actualizado')
                            ->success()
                            ->send();
                        
                    } else {
                        // ADMIN/SUPERVISOR
                        $record->update([
                            'edited_by_operator' => $data['edited_by_operator'],
                        ]);
                        
                        Notification::make()
                        ->title('Ajustes Realizados')
                        ->success()
                        ->send();
                    } 
                }),
                
                
            // ── ÍCONO "BLOQUEADO" (solo Operador cuando ya agotó su edición)
            TablesAction::make('ya_editado')
                ->label('')
                ->icon('heroicon-m-lock-closed')
                ->color('gray')
                ->iconButton()
                ->tooltip('Edición bloqueada. Solicita a tu Supervisor habilitar esta opción.')
                ->action(fn () => null)
                ->visible(fn ($record) =>
                    $record->status === 'Completa' &&
                    $record->edited_by_operator &&
                    !auth()->user()->hasAnyRole(['Admin', 'Supervisor'])
                ),
        ])
            
            ->actionsColumnLabel('Gestión');
    }

    public function updatedData($value, $key): void
    {
        $this->resetTable();
    }
}