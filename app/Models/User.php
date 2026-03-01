<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;  // ← Agregar
use Filament\Panel;                           // ← Agregar

class User extends Authenticatable implements FilamentUser  // ← Agregar
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'operator_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    
    const ROLE_ADMIN = 'Admin';
    const ROLE_OPERADOR = 'Operador';
    const ROLE_SUPERVISOR = 'Supervisor';
    
    // ← Agregar este método
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole([self::ROLE_ADMIN, self::ROLE_SUPERVISOR]);
    }
    
    public function hasRole(string $role): bool
    {
        return ($this->role ?? '') === $role;
    }
    
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role ?? '', $roles);
    }
}