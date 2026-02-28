<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionTotalLogResource\Pages;
use App\Models\ProductionLog;
use App\Models\ProductionTotalLog;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput; 
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group as ComponentsGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource; 
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductionTotalLogResource extends Resource
{
    protected static ?string $navigationGroup = 'Producción';
    protected static ?string $modelLabel = 'Producción Total';
    protected static ?string $pluralModelLabel = 'Producciones';
    protected static ?string $navigationLabel = 'Producción';
    
    protected static ?string $model = ProductionTotalLog::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack'; 
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([ 
                ComponentsGroup::make([
                    // !SECCIÓN 1: Selección de Turno y Fecha
                    Section::make('1. Selección de Turno y Fecha')
                        ->schema([ 
                            DatePicker::make('date_select')
                                ->label('Fecha de Producción')
                                ->default(now())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('detalles_produccion', []);
                                }),
                                //->reactive(),
                                
                            Select::make('shift')
                                ->label('Turno')
                                ->options([
                                    '1er Turno' => '☀️ Mañana',
                                    '2do Turno'  => '🌤️ Tarde',
                                    '3er Turno'  => '🌙 Noche',
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set) {
                                    $set('detalles_produccion', []);
                                }),
                                //->reactive(),
                                
                            Actions::make([
                                FormAction::make('ver_resumen')
                                    ->label('Ver Resumen de Registros')
                                    ->icon('heroicon-o-eye')
                                    ->color('warning')
                                    ->hidden(fn (Get $get) => ! $get('shift'))
                                    ->before(function (Get $get, Action $action) {
                                        $turnoStr = match((int) $get('shift')) {
                                            1 => 'Mañana',
                                            2 => 'Tarde',
                                            3 => 'Noche',
                                            default => ''
                                        };
                                        
                                        $count = ProductionLog::where('shift', $turnoStr)
                                            ->whereDate('created_at', $get('date_select'))
                                            ->count();
                                            
                                        if ($count === 0) {
                                            Notification::make()
                                            ->title('Sin registros')
                                            ->body("No hay producción registrada para el turno {$turnoStr}.")
                                            ->warning()
                                            ->send(); 
                                        }
                                    })
                                    ->modalContent(function (Get $get) {
                                        $turnoStr = match((int)$get('shift')) {1=>'Mañana', 2=>'Tarde', 3=>'Noche', default=>''};
                                        $logs = ProductionLog::with('machine')
                                            ->where('shift', $turnoStr)
                                            ->whereDate('created_at', $get('date_select'))
                                            ->get();
                                        return view('filament.components.resumen-produccion', ['logs' => $logs]);
                                    }),
                            ])->columnSpanFull(),
                        ])->columns(2),
                        
                    // !SECCIÓN 2: Cálculo por Máquina
                    Section::make('2. Validación por Máquina')
                        ->description('Se listarán todas las máquinas con actividad en este turno.')
                        ->visible(fn (Get $get) => $get('shift') && $get('date_select'))
                        ->schema([
                            Actions::make([
                                FormAction::make('cargar_maquinas')
                                    ->label('Cargar Máquinas')
                                    ->icon('heroicon-m-arrow-path')
                                    ->color('success')
                                    ->action(function (Get $get, Set $set) {
                                        $set('detalles_produccion', []);
                                        $turnoId = (int)$get('shift');
                                        $turnoStr = match($turnoId){1=>'Mañana', 2=>'Tarde', 3=>'Noche', default=>''};
                                        $fecha = $get('date_select');
                                            
                                        $maquinasEnLog = ProductionLog::where('shift', $turnoStr)
                                            ->whereDate('created_at', $fecha)
                                            ->select('machine_id')
                                            ->distinct()
                                            ->with('machine')
                                            ->get();
                                            
                                        if ($maquinasEnLog->isEmpty()) {
                                            Notification::make()
                                                ->title('Sin producción registrada')
                                                ->body("No se encontraron registros para el turno {$turnoStr} en la fecha {$fecha}.")
                                                ->warning()
                                                ->send();
                                            return;
                                        }
                                            
                                        $dataRepeater = [];
                                            
                                        foreach ($maquinasEnLog as $item) {
                                            $real = ProductionLog::where('machine_id', $item->machine_id)
                                                ->where('shift', $turnoStr)
                                                ->whereDate('created_at', $fecha)
                                                ->sum('kg_produced');
                                                
                                                // *Objetivo 
                                                $objetivoBase = (float) ($item->machine->real ?? 0);
                                                $turno = $turnoStr; // 'Mañana', 'Tarde' o 'Noche'
                                                
                                                // *Ajuste según el turno
                                                $objetivoAjustado = match ($turno) {
                                                    'Tarde' => ($objetivoBase / 8) * 7.5,
                                                    'Noche' => ($objetivoBase / 8) * 8.5,
                                                    default => $objetivoBase,
                                                };
                                                
                                                $eficiencia = $objetivoAjustado > 0 
                                                ? (int) round(($real / $objetivoAjustado) * 100) 
                                                : 0;
                                                
                                            $dataRepeater[] = [
                                                'machine_id'   => $item->machine_id,
                                                'machine_name' => $item->machine->name,
                                                'kg_produced'  => $real,
                                                'objetive'     => number_format($objetivoAjustado, 2, '.', ''),
                                                'efficiency'   => $eficiencia,
                                            ];
                                        }
                                            
                                        $set('detalles_produccion', $dataRepeater);
                                        Notification::make()
                                            ->title('Máquinas cargadas')
                                            ->body('Por favor, ingrese el Objetivo para cada máquina para calcular la eficiencia.')
                                            ->info()
                                            ->send();
                                    }),
                            ])->columnSpanFull(),
                            
                            // REPEATER: Aquí aparecen los 3 campos duplicados por máquina
                            Repeater::make('detalles_produccion')
                                ->required()
                                ->label('Resultados por Máquina')
                                ->default([])
                                ->hidden(fn (Get $get) => empty($get('detalles_produccion')))
                                ->schema([
                                    // Instrucción visual dentro del repeater
                                    Placeholder::make('instruccion')
                                        ->label('')
                                        ->content('Indique el objetivo para calcular %')
                                        ->columnSpanFull(),
                                        
                                    TextInput::make('machine_name')
                                        ->label('Máquina')
                                        ->readOnly(),
                                        
                                    Hidden::make('machine_id'),
                                        
                                    TextInput::make('kg_produced')
                                        ->label('Real (Kg)')
                                        ->numeric()
                                        ->readOnly()
                                        ->prefix('Kg'),
                                        
                                    TextInput::make('objetive')
                                    ->label('Objetivo (Kg)')
                                    ->numeric()
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                    ->readOnly(),
                                    
                                    TextInput::make('efficiency')
                                        ->label('Eficiencia (%)')
                                        ->suffix('%')
                                        ->readOnly(),
                                ])
                                ->minItems(1) // <--- Esto impide que se guarde si no hay máquinas cargadas
                                ->validationMessages([
                                    'min' => 'Debe cargar las máquinas antes de guardar.',
                                ])
                                ->addable(false)
                                ->deletable(false)
                                ->columns(4)
                                ->reorderable(true)
                                ->columnSpanFull(), 
                        ]) 
                    ])->visible(fn (string $operation) => $operation === 'create')
                    ->columnSpanFull()
                    ->columns(1),
                    // !SECCIÓN 3: SOLO VISIBLE AL EDITAR ---
                    Section::make('3. Editar Registro Individual')
                        ->visible(fn (string $operation) => $operation === 'edit')
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('shift')->label('Turno')->readOnly(),
                                    DatePicker::make('date_select')->label('Fecha')->readOnly(),
                                    TimePicker::make('created_at')->label('Hora de Registro')->native(false)->format('H:i:s')->displayFormat('H:i A')->readOnly(),
                                ]),
                                Grid::make(3)
                                ->schema([
                                    TextInput::make('real')
                                        ->label('Real (Kg)')
                                        ->numeric()
                                        ->prefix('Kg')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateEfficiency($get, $set)),
                                    TextInput::make('objetive')
                                        ->label('Objetivo (Kg)')
                                        ->numeric()
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateEfficiency($get, $set)),
                                    TextInput::make('efficiency')->label('Eficiencia (%)')->numeric()->suffix('%')->readOnly(),
                                ]),
                                Textarea::make('observations')->label('Observaciones')->columnSpanFull(),               
                ])  
            ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // FECHA DE PRODUCCIÓN
                TextColumn::make('date_select')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),
                    
                // TURNO
                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Mañana' => 'info',
                        'Tarde' => 'warning',
                        'Noche' => 'gray',
                        default => 'primary',
                    }),

                // MÁQUINA
                TextColumn::make('machine.name')
                    ->label('Máquina')
                    ->sortable()
                    ->searchable(),

                // KILOS REALES
                TextColumn::make('real')
                    ->label('Real (Kg)')
                    ->numeric()
                    ->suffix(' kg')
                    ->summarize(Sum::make()->label('Total')),

                // OBJETIVO
                TextColumn::make('objetive')
                    ->label('Objetivo (Kg)')
                    ->numeric()
                    ->suffix(' kg'),

                // EFICIENCIA
                TextColumn::make('efficiency')
                    ->label('Eficiencia')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('%')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state >= 90 => 'success', // Verde
                        $state >= 75 => 'warning', // Amarillo/Naranja
                        default => 'danger',       // Rojo
                    })
                    ->sortable(),
            ])
            /*->headerActions([
                Action::make('descargar_pdf')
                    ->label('Generar PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->action(function ($livewire) {
                        // Obtenemos los registros actuales de la tabla
                        $records = $livewire->getTableRecords(); 

                        // Si es un paginador, extraemos los elementos
                        if ($records instanceof \Illuminate\Pagination\LengthAwarePaginator) {
                            $records = $records->items();
                        }

                        // AJUSTE DE RUTA: 'reports' es la carpeta, 'production-pdf' el archivo
                        $pdf = Pdf::loadView('reports.production-pdf', [
                            'records' => $records,
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, 'reporte-produccion-' . now()->format('Y-m-d') . '.pdf');
                    }),
            ])*/
            
            ->filters([
                // Filtro por Turno
                SelectFilter::make('shift')
                    ->label('Turno')
                    ->options([
                        '1er Turno' => 'Mañana',
                        '2do Turno' => 'Tarde',
                        '3er Turno' => 'Noche',
                    ]),
                // Filtro por Fecha
                Filter::make('date_select')
                    ->form([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'], fn($q) => $q->whereDate('date_select', '>=', $data['desde']))
                            ->when($data['hasta'], fn($q) => $q->whereDate('date_select', '<=', $data['hasta']));
                    })
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
            // AGRUPACIÓN:
            //->defaultGroup('date_select')
            /*->groups([
                Group::make('date_select')
                    ->label('Fecha')
                    ->collapsible(),
                Group::make('shift')
                    ->label('Turno')
                    ->collapsible(),
            ]);*/
            
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductionTotalLogs::route('/'),
            'create' => Pages\CreateProductionTotalLog::route('/create'),
            'edit' => Pages\EditProductionTotalLog::route('/{record}/edit'),
        ];
    }

    public static function getFormActions(): array
    {
        return [
            // Acción de Crear / Guardar
            parent::getCreateFormAction()
                ->disabled(fn (Get $get) => empty($get('detalles_produccion')))
                ->tooltip(fn (Get $get) => empty($get('detalles_produccion')) ? 'Debe cargar las máquinas primero' : null),
            
            // Acción de Cancelar
            parent::getCancelFormAction(),
        ];
    }
}
