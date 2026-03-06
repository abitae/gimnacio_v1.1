@props(['tipo' => 'ventas', 'fechaDesde' => '', 'fechaHasta' => '', 'estado' => '', 'createdById' => '', 'trainerUserId' => ''])
@php
    $params = [];
    if ($fechaDesde) $params['fecha_desde'] = $fechaDesde;
    if ($fechaHasta) $params['fecha_hasta'] = $fechaHasta;
    if ($estado !== '' && $estado !== null) $params['estado'] = $estado;
    if ($tipo === 'clientes') {
        if ($createdById !== '' && $createdById !== null) $params['created_by'] = $createdById;
        if ($trainerUserId !== '' && $trainerUserId !== null) $params['trainer_user_id'] = $trainerUserId;
    }
    $urlPdf = route('reportes.' . $tipo . '.exportar.pdf', $params);
    $urlExcel = route('reportes.' . $tipo . '.exportar.excel', $params);
@endphp
<div class="flex flex-wrap gap-2">
    <a href="{{ $urlPdf }}" target="_blank" rel="noopener"
        class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 px-2.5 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/30">
        <flux:icon name="document-arrow-down" class="size-4" />
        Exportar PDF
    </a>
    <a href="{{ $urlExcel }}" target="_blank" rel="noopener"
        class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 px-2.5 py-1.5 text-xs font-medium text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/30">
        <flux:icon name="table-cells" class="size-4" />
        Exportar Excel
    </a>
</div>
