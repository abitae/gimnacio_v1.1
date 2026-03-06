import Swal from 'sweetalert2';

window.Swal = Swal;

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
