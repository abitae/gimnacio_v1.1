import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';

window.Swal = Swal;
window.Chart = Chart;

// Gráfico de barras reutilizable (CRM Reportes y otros módulos analíticos livianos).
document.addEventListener('alpine:init', () => {
    Alpine.data('crmBarChart', (config) => ({
        chart: null,
        init() {
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? '#3f3f46' : '#e4e4e7';
            const tickColor = isDark ? '#a1a1aa' : '#52525b';
            this.chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: {
                    labels: config.labels,
                    datasets: [{
                        data: config.values,
                        backgroundColor: config.colors || '#71717a',
                        borderRadius: 6,
                        maxBarThickness: 32,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { color: tickColor, precision: 0 }, grid: { color: gridColor } },
                        y: { ticks: { color: tickColor }, grid: { display: false } },
                    },
                },
            });
        },
    }));
});

const flashTitles = {
    success: 'Éxito',
    error: 'Error',
    warning: 'Aviso',
    info: 'Información',
};

function showFlashToast(type, message) {
    const msg = typeof message === 'string' ? message : (message ? String(message) : '');
    if (!msg.trim()) return;
    Swal.fire({
        icon: type || 'info',
        title: flashTitles[type] || type || 'Información',
        text: msg,
        toast: true,
        position: 'top-end',
        timer: 5000,
        timerProgressBar: true,
        showConfirmButton: false,
    });
}

window.showFlashToast = showFlashToast;

// Flash desde sesión (recarga/redirect): el layout inyecta window.__flashToast
function showPendingFlash() {
    if (window.__flashToast && window.__flashToast.type && window.__flashToast.message) {
        showFlashToast(window.__flashToast.type, window.__flashToast.message);
        window.__flashToast = null;
    }
}
showPendingFlash();
window.addEventListener('flash-toast-pending', showPendingFlash);

// Flash desde Livewire (sin recarga): evento show-flash
document.addEventListener('livewire:init', () => {
    Livewire.on('show-flash', (e) => {
        const type = (e && (e.type ?? e[0])) || 'info';
        const message = (e && (e.message ?? e[1])) || '';
        showFlashToast(type, message);
    });
});
