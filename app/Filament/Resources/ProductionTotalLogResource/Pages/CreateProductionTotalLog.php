<?php

namespace App\Filament\Resources\ProductionTotalLogResource\Pages;

use App\Filament\Resources\ProductionTotalLogResource;
use App\Models\ProductionTotalLog;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateProductionTotalLog extends CreateRecord
{
    protected static string $resource = ProductionTotalLogResource::class;
    
    // Guardado de Formulario
    protected function handleRecordCreation(array $data): ProductionTotalLog
    { 
        $maquinas = $data['detalles_produccion'] ?? [];
        
        unset($data['detalles_produccion']);
        
        $firstRecord = null;
        
        foreach ($maquinas as $item) {
            $record = ProductionTotalLog::create([
                'machine_id'  => $item['machine_id'],
                'shift'       => $data['shift'],
                'real'        => $item['kg_produced'],
                'objetive'    => $item['objetive'],
                'efficiency'  => (int) $item['efficiency'],
                'date_select' => $data['date_select'],
                'observations'=> $data['observations'] ?? 'Cierre de turno',
            ]);
            
            if (!$firstRecord) {
                $firstRecord = $record;
            }
        }
        
        return $firstRecord ?? new ProductionTotalLog();
    }
    
    // 2. La redirección a la tabla
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    // BOTÓN 1: Crear
    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->disabled(fn () => empty($this->form->getRawState()['detalles_produccion'] ?? []));
    }

    // BOTÓN 2: Crear y crear otro
    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()
            ->disabled(fn () => empty($this->form->getRawState()['detalles_produccion'] ?? []));
    }
}
