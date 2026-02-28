<div class="p-4">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="border-b">
                <th class="py-2">Operador</th>
                <th class="py-2">Máquina</th>
                <th class="py-2">Calibre</th>
                <th class="py-2">Kilos</th>
                <th class="py-2">Fecha</th>
                <th class="py-2">Hora</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                <tr class="border-b text-sm">
                    <td class="py-2">{{ $log->user?->name ?? 'Sin operador' }}</td>
                    <td class="py-2">{{ $log->machine?->name }}</td>
                    <td class="py-2">{{ $log->machine?->yarn }}</td>
                    <td class="py-2">{{ $log->kg_produced }} kg</td>
                    <td class="py-2">{{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y') }}</td>
                    <td class="py-2">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i A') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-400">
                        Sin registros para este turno
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>