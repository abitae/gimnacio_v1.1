@props(['evaluacion'])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Gráfico Donut -->
    <div class="flex items-center justify-center">
        <div class="relative w-48 h-48">
            <canvas 
                x-data="composicionChartData({{ json_encode($evaluacion->composicion_corporal) }}, {{ $evaluacion->peso ?? 0 }})"
                x-init="initChart($el)"
                class="w-full h-full">
            </canvas>
        </div>
    </div>

    <!-- Detalle de Valores -->
    <div class="space-y-2">
        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Peso</p>
            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->peso ? number_format($evaluacion->peso, 2) . ' kg' : '-' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Masa Muscular</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->porcentaje_musculo ? number_format($evaluacion->porcentaje_musculo, 2) . '%' : '-' }}
                @if ($evaluacion->masa_muscular)
                    → <span class="font-medium">{{ number_format($evaluacion->masa_muscular, 2) }} kg</span>
                @endif
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Porcentaje de Grasa</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->porcentaje_grasa ? number_format($evaluacion->porcentaje_grasa, 2) . '%' : '-' }}
                @if ($evaluacion->masa_grasa)
                    → <span class="font-medium">{{ number_format($evaluacion->masa_grasa, 2) }} kg</span>
                @endif
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Masa Grasa</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->masa_grasa ? number_format($evaluacion->masa_grasa, 2) . ' kg' : '0 kg' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Masa Ósea</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->masa_osea ? number_format($evaluacion->masa_osea, 2) . ' kg' : '0 kg' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Masa Residual</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->masa_residual ? number_format($evaluacion->masa_residual, 2) . ' kg' : '0 kg' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">IMC</p>
            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $evaluacion->imc ? number_format($evaluacion->imc, 2) : '-' }}
            </p>
        </div>
    </div>
</div>

<script>
    function composicionChartData(data, pesoTotal) {
        return {
            chart: null,
            initChart(canvas) {
                if (typeof Chart === 'undefined') {
                    console.warn('Chart.js no está cargado');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                const composicion = data;
                
                const chartData = [];
                const labels = [];
                const colors = ['#3b82f6', '#ef4444', '#8b5cf6', '#f59e0b'];
                
                if (composicion.masa_muscular && composicion.masa_muscular.kg > 0) {
                    chartData.push(composicion.masa_muscular.kg);
                    labels.push('Masa Muscular');
                }
                
                if (composicion.masa_grasa && composicion.masa_grasa.kg > 0) {
                    chartData.push(composicion.masa_grasa.kg);
                    labels.push('Masa Grasa');
                }
                
                if (composicion.masa_osea && composicion.masa_osea.kg > 0) {
                    chartData.push(composicion.masa_osea.kg);
                    labels.push('Masa Ósea');
                }
                
                if (composicion.masa_residual && composicion.masa_residual.kg > 0) {
                    chartData.push(composicion.masa_residual.kg);
                    labels.push('Masa Residual');
                }
                
                if (chartData.length > 0) {
                    this.chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Composición Corporal',
                                data: chartData,
                                backgroundColor: colors.slice(0, chartData.length),
                                borderWidth: 2,
                                borderColor: '#ffffff',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: { size: 10 },
                                        padding: 8,
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const percentage = pesoTotal > 0 ? ((value / pesoTotal) * 100).toFixed(1) : 0;
                                            return label + ': ' + value.toFixed(2) + ' kg (' + percentage + '%)';
                                        }
                                    }
                                }
                                }
                            }
                        }
                    });
                }
            }
        }
    }
</script>
