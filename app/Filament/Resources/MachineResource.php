<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MachineResource\Pages;

use App\Models\Machine;

use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

use Filament\Resources\Resource;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class MachineResource extends Resource
{
    // *Cambio de idiomas
    protected static ?string $modelLabel = 'Máquina';
    protected static ?string $pluralModelLabel = 'Máquinas';
    protected static ?string $navigationLabel = 'Máquinas';
    protected static ?string $navigationGroup = 'Trociles';
    
    protected static ?string $model = Machine::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static function recalcular(callable $set, callable $get): void
    {
        $speed      = (float) $get('speed');
        $yarnWeight = (int) $get('yarn_weight');
        $usage = (int) $get('usage');
        
        if ($speed > 0 && $yarnWeight > 0 && $usage > 0) {
            $productiveCap = 0.59 * $speed / $yarnWeight * $usage * 60 / 1000;
            $shiftCap      = $productiveCap * 8;
            $real          = $shiftCap * 0.8;
        } else {
            $productiveCap = 0;
            $shiftCap      = 0;
            $real          = 0;
        }
        
        $set('productive_cap', number_format($productiveCap, 2));
        $set('shift_cap',      number_format($shiftCap,      2));
        $set('real',           number_format($real,           2));
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('1. Detalles de la Máquina')
                    ->description('Información básica de la unidad en planta.')
                    ->icon('heroicon-m-cpu-chip')
                    ->iconColor('primary')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre de la Máquina')
                            ->placeholder('Ej. Trocil N.º')
                            ->required()
                            ->autofocus()
                            ->prefixIcon('heroicon-m-wrench-screwdriver')
                            ->prefixIconColor('primary'),
                            
                        TextInput::make('yarn')
                            ->label('Hilo')
                            ->placeholder('Ej. N.º/1/Z')
                            ->required()
                            ->prefixIcon('heroicon-m-swatch')
                            ->prefixIconColor('primary'),
                            
                        TextInput::make('speed')
                            ->label('Velocidad')
                            ->placeholder('0.00')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->extraInputAttributes([
                                'min'       => 1,
                                'max'       => 99.99,
                                'oninput'   => "this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1').replace(/(\.\d{2}).*/g, '$1'); if(parseFloat(this.value) > 99.99) this.value = 99.99;",
                            ])
                            ->prefixIcon('heroicon-m-bolt')
                            ->prefixIconColor('primary')
                            ->live()
                            // *Actualza status campos dinamicos
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::recalcular($set, $get);
                            }),
                            
                        TextInput::make('usage')
                            ->label('Uso')
                            ->numeric()
                            ->minValue(1)
                            ->step(1)
                            ->required()
                            ->placeholder('0')
                            ->extraInputAttributes([
                                'min'       => 1,
                                'max'       => 9999,
                                'oninput'   => "this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1').replace(/(\.\d{2}).*/g, '$1'); if(parseFloat(this.value) > 9999) this.value = 9999;",
                            ])
                            ->prefixIcon('heroicon-m-variable')
                            ->prefixIconColor('primary')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::recalcular($set, $get);
                            }),
                            
                        TextInput::make('yarn_weight')
                            ->label('Grosor del Hilo')
                            ->placeholder('0')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0)
                            ->required()
                            ->extraInputAttributes([
                                'min'       => 1,
                                'max'       => 99,
                                'oninput'   => "this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1').replace(/(\.\d{2}).*/g, '$1'); if(parseFloat(this.value) > 99) this.value = 99;",
                            ])
                            ->prefixIcon('heroicon-m-adjustments-horizontal')
                            ->prefixIconColor('primary')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                self::recalcular($set, $get);
                            }),
                    ])
                    ->columns(2),
                    
                Section::make('2. Capacidades Calculadas')
                    ->description('Valores generados automáticamente según los parámetros.')
                    ->icon('heroicon-m-calculator')
                    ->iconColor('success')
                    ->schema([
                        TextInput::make('productive_cap')
                            ->label('Capacidad Productiva')
                            ->placeholder('Calculado automáticamente')
                            ->readOnly() 
                            ->hint('KGS/HRA')
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', '') : null)
                            ->prefixIcon('heroicon-m-arrow-trending-up')
                            ->prefixIconColor('success'),
                        
                        TextInput::make('shift_cap')
                            ->label('Capacidad por Turno')
                            ->placeholder('Calculado automáticamente')
                            ->readOnly()
                            ->hint('KGS/TURNO')
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', '') : null)
                            ->prefixIcon('heroicon-m-clock')
                            ->prefixIconColor('success'),
                            
                        TextInput::make('real')
                            ->label('Capacidad Real')
                            ->placeholder('Calculado automáticamente')
                            ->readOnly()
                            ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', '') : null)
                            ->prefixIcon('heroicon-m-check-badge')
                            ->prefixIconColor('success'),
                            
                        Toggle::make('is_active')
                            ->label('Estado Operativo')
                            ->default(true)
                            ->helperText('Activa o Desactiva la máquina del inventario actual.')
                            ->columnSpan('full'),
                    ])
                    ->columns(3),
                ]);
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Maquina')
                    ->alignCenter()
                    ->searchable(),
                TextColumn::make('yarn')
                    ->label('Calibre'),
                TextColumn::make('speed')
                    ->label('Velocidad')
                    ->alignCenter(),
                TextColumn::make('usage')
                    ->label('Usos')
                    ->alignCenter(),
                TextColumn::make('real')
                    ->label('Capacidad Real')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, '.', '') : null),
                IconColumn::make('is_active')
                    ->label('Estado')
                    ->alignCenter()
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Registrada')
                    ->since()
                    ->tooltip(fn ($state) => $state->format('d/m/Y H:i:s')),
                    
            ])
            ->filters([
                // *Filtro
                TernaryFilter::make('is_active')
                    ->label('Estado Operativo'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    /*public static function getRelations(): array
    {
        return [
            //
        ];
    }*/
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMachines::route('/'),
            'create' => Pages\CreateMachine::route('/create'),
            'edit' => Pages\EditMachine::route('/{record}/edit'),
        ];
    }
}
