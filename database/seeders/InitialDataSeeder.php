<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Machine;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios por Rol
        $users = [
            [
                'name' => 'Alonso Romero',
                'email' => 'aloromerooya280@gmail.com',
                'password' => Hash::make('admin123'),
                'role' => 'Administrador',
                'operator_id' => 'ADM001',
            ],
            [
                'name' => 'Juan',
                'email' => 'eljuan@lareforma.com',
                'password' => Hash::make('super123'),
                'role' => 'Supervisor',
                'operator_id' => 'SUP001',
            ],
            [
                'name' => 'Pedro',
                'email' => 'elpedro@lareforma.com',
                'password' => Hash::make('op123'),
                'role' => 'Operador',
                'operator_id' => 'OP001',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }

        // 2. Datos de Máquinas (Sincronizado con tu SQL de HeidiSQL)
        $machines = [
            [1, 'TROCIL 1', '30/1/Z', 12.00, 1104, 30, 15.63, 125.06, 100.05, 1],
            [2, 'TROCIL 2', '20/1/Z', 10.00, 1104, 20, 19.54, 156.33, 125.06, 0],
            [3, 'TROCIL 3', '10/1/Z', 15.00, 1008, 10, 53.52, 428.20, 342.56, 1],
            [4, 'TROCIL 4', '15/1/Z', 15.00, 1056, 15, 37.38, 299.06, 239.25, 1],
            [5, 'TROCIL 5', '30/1/Z', 12.00, 1056, 30, 14.95, 119.62, 95.70, 1],
            [6, 'TROCIL 6', '30/1/S', 9.00, 440, 30, 4.67, 37.38, 29.91, 1],
            [7, 'TROCIL 7', '30/1/S', 9.00, 440, 30, 4.67, 37.38, 29.91, 1],
            [8, 'TROCIL 8', '14/1/Z CDO', 10.50, 460, 14, 12.21, 97.70, 78.16, 1],
            [9, 'TROCIL 9', '5/1/Z', 14.00, 234, 5, 23.19, 185.55, 148.44, 1],
            [10, 'TROCIL 10', '15/1/Z', 12.00, 504, 15, 14.27, 114.19, 91.35, 1],
            [11, 'TROCIL 11', '10/1/Z', 14.00, 504, 10, 24.98, 199.83, 159.86, 1],
            [12, 'TROCIL 12', '30/1/Z', 12.00, 504, 30, 7.14, 57.09, 45.67, 1],
            [13, 'TROCIL 13', '30/1/Z', 12.00, 504, 30, 7.14, 57.09, 45.67, 1],
            [14, 'TROCIL 14', '15/1/Z', 12.00, 540, 15, 15.29, 122.34, 97.87, 0],
            [15, 'TROCIL 15', '30/1/S', 10.50, 540, 30, 6.69, 53.52, 42.82, 1],
            [16, 'TROCIL 16', '30/1/S', 10.50, 540, 30, 6.69, 53.52, 42.82, 1],
            [17, 'TROCIL 17', '15/1/Z CDO', 12.00, 270, 15, 7.65, 61.17, 48.94, 1],
            [18, 'TROCIL 18', '16/1/Z', 12.00, 540, 16, 14.34, 114.70, 91.76, 1],
            [19, 'TROCIL 19', '20/1/Z-E', 12.00, 540, 20, 11.47, 91.76, 73.41, 1],
        ];

        foreach ($machines as $m) {
            Machine::updateOrCreate(
                ['id' => $m[0]],
                [
                    'name'           => $m[1],
                    'yarn'           => $m[2],
                    'speed'          => $m[3],
                    'usage'          => $m[4],
                    'yarn_weight'    => $m[5],
                    'productive_cap' => $m[6],
                    'shift_cap'      => $m[7],
                    'real_val'       => $m[8],
                    'is_active'      => $m[9],
                ]
            );
        }
    }
}
