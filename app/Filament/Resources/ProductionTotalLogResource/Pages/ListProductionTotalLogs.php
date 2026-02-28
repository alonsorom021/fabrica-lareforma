<?php

namespace App\Filament\Resources\ProductionTotalLogResource\Pages;

use App\Filament\Resources\ProductionTotalLogResource;
use App\Models\ProductionTotalLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListProductionTotalLogs extends ListRecords
{
    protected static string $resource = ProductionTotalLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Botón "Crear Produccion Total"
            CreateAction::make(),
            // Botón Reporte
            Action::make('descargar_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () { 
                    $records = ProductionTotalLog::all();
                    $pdf = Pdf::loadView('filament.reports.production-pdf', [
                        'records' => $records,
                    ]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, 'reporte-general-' . now()->format('d-m-Y') . '.pdf');
                }
            ),
        ];
    }
}
