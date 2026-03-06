<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administración';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel      = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole(User::ROLE_ADMIN);
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole(User::ROLE_ADMIN);
    }
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Personal')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre Completo')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-user')
                            ->prefixIconColor('primary'),
                            
                        TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope')
                            ->prefixIconColor('info'),
                            
                        TextInput::make('operator_id')
                            ->label('ID Operador')
                            ->prefixIcon('heroicon-m-identification')
                            ->prefixIconColor('warning'),
                            
                        Select::make('role')
                            ->label('Rol')
                            ->options([
                                User::ROLE_ADMIN      => '👑 Administrador',
                                User::ROLE_SUPERVISOR => '🧑‍💼 Supervisor',
                                User::ROLE_OPERADOR   => '👷 Operador',
                            ])
                            ->required()
                            ->prefixIcon('heroicon-m-shield-check')
                            ->prefixIconColor('success'),
                    ])
                    ->columns(2),
                    
                Section::make('Seguridad')
                    ->schema([
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->prefixIconColor('danger')
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $operation) => $operation === 'create')
                            ->minLength(5)
                            ->confirmed(),
                            
                        TextInput::make('password_confirmation')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-m-lock-closed')
                            ->prefixIconColor('danger')
                            ->dehydrated(false)
                            ->required(fn(string $operation) => $operation === 'create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('operator_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                    
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->icon('heroicon-m-envelope'),
                    
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        User::ROLE_ADMIN      => 'danger',
                        User::ROLE_SUPERVISOR => 'warning',
                        User::ROLE_OPERADOR   => 'success',
                        default               => 'gray',
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
