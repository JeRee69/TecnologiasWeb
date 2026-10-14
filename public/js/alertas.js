(() => {
    'use strict';
    // Conservar los avisos del servidor si la biblioteca no pudo cargar.
    if (!window.Swal) return;
    const dialog = Swal.mixin({
        confirmButtonColor: '#1e3a8a', cancelButtonColor: '#64748b',
        confirmButtonText: 'Entendido', cancelButtonText: 'Cancelar', heightAuto: false,
        customClass: { popup: 'ediconnect-dialog' },
    });
    const style = document.createElement('style');
    style.textContent = '.ediconnect-dialog{font-family:Inter,system-ui,sans-serif;border-radius:1rem;padding:1.5rem}.ediconnect-dialog .swal2-title{font-size:1.35rem;color:#111827}.ediconnect-dialog .swal2-html-container{font-size:.95rem;line-height:1.6;white-space:pre-line}.ediconnect-dialog .swal2-styled{border-radius:.5rem;font-weight:600}.ediconnect-dialog .swal2-actions{gap:.4rem}';
    document.head.append(style);
    document.querySelectorAll('[data-swal-feedback]').forEach(node => {
        if (node.querySelector('a')) {
            const message = node.querySelector('[data-swal-message]');
            if (message) message.style.display = 'none';
        } else {
            node.style.display = 'none';
        }
    });
    const payload = document.getElementById('ediconnect-alertas');
    const alerts = payload ? JSON.parse(payload.textContent) : {};
    if (alerts.errores?.length) {
        dialog.fire({ icon: 'error', title: 'Revisa los datos', text: alerts.errores.join('\n') });
    } else if (alerts.exito) {
        dialog.fire({ icon: 'success', title: 'Operación completada', text: alerts.exito });
    }
    document.querySelectorAll('form[data-swal-confirm]').forEach(form => {
        let approved = false;
        let pending = false;
        form.addEventListener('submit', async event => {
            if (approved) return;
            event.preventDefault();
            if (pending) return;
            pending = true;
            const submitter = event.submitter;
            const result = await dialog.fire({
                icon: form.dataset.swalDanger === 'true' ? 'warning' : 'question',
                title: form.dataset.swalConfirm, text: form.dataset.swalText,
                showCancelButton: true, confirmButtonText: form.dataset.swalButton || 'Confirmar',
                confirmButtonColor: form.dataset.swalDanger === 'true' ? '#b91c1c' : '#1e3a8a',
                focusCancel: true, reverseButtons: true,
            });
            pending = false;
            if (result.isConfirmed) {
                approved = true;
                form.requestSubmit(submitter || undefined);
                form.querySelectorAll('button[type="submit"]').forEach(button => { button.disabled = true; });
            }
        });
    });
})();
