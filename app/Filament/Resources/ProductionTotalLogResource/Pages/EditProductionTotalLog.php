<?php

namespace App\Filament\Resources\ProductionTotalLogResource\Pages;

use App\Filament\Resources\ProductionTotalLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionTotalLog extends EditRecord
{
    protected static string $resource = ProductionTotalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
