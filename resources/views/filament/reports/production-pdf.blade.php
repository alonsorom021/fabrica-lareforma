<!DOCTYPE html>
<html>
<head>
    <title>Reporte de Producción</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Producción Total</h1>
        <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Turno</th>
                <th>Máquina</th>
                <th>Real (Kg)</th>
                <th>Objetivo (Kg)</th>
                <th>Eficiencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
            <tr>
                <td>{{ $record->date_select }}</td>
                <td>{{ $record->shift }}</td>
                <td>{{ $record->machine->name ?? 'N/A' }}</td>
                <td>{{ $record->real }} kg</td>
                <td>{{ $record->objetive }} kg</td>
                <td>{{ $record->efficiency }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>