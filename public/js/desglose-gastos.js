(() => {
    'use strict';
    const form = document.getElementById('desglose-form');
    if (!form) return;
    const filas = document.getElementById('detalle-filas');
    const agregar = document.getElementById('agregar-concepto');
    const monto = Number(form.dataset.monto);
    const formato = new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 });
    const actualizar = () => {
        const registros = [...filas.querySelectorAll('[data-detalle]')];
        let total = 0;
        let validos = true;
        registros.forEach((fila, indice) => {
            fila.querySelectorAll('[data-campo]').forEach(input => {
                const campo = input.dataset.campo;
                input.name = `detalles[${indice}][${campo}]`;
                input.id = `${campo}-${indice}`;
                const label = fila.querySelector(`[data-label="${campo}"]`);
                label.htmlFor = input.id;
                if (campo === 'concepto') label.textContent = `Concepto ${indice + 1}`;
            });
            const input = fila.querySelector('[data-campo="monto"]');
            if (!input.value || !input.checkValidity()) validos = false;
            else total += Number(input.value);
            const quitar = fila.querySelector('[data-quitar]');
            quitar.hidden = false;
            quitar.disabled = registros.length === 1;
            quitar.setAttribute('aria-label', `Quitar concepto ${indice + 1}`);
        });
        agregar.disabled = registros.length >= 50;
        document.getElementById('total-conceptos').textContent = formato.format(total);
        document.getElementById('diferencia').textContent = formato.format(monto - total);
        document.getElementById('mensaje-total').textContent = !validos
            ? 'Completa los montos de todos los conceptos.'
            : total === monto ? 'El total coincide con el monto asignado.' : 'Ajusta los conceptos hasta que la diferencia sea cero.';
    };
    agregar.hidden = false;
    document.getElementById('resumen-desglose').hidden = false;
    agregar.addEventListener('click', () => {
        if (filas.children.length >= 50) return;
        const fila = filas.firstElementChild.cloneNode(true);
        fila.querySelectorAll('input').forEach(input => { input.value = ''; });
        fila.querySelectorAll('[data-error]').forEach(error => error.remove());
        filas.append(fila);
        actualizar();
        fila.querySelector('input').focus();
    });
    filas.addEventListener('click', event => {
        const quitar = event.target.closest('[data-quitar]');
        if (!quitar || filas.children.length <= 1) return;
        quitar.closest('[data-detalle]').remove();
        actualizar();
    });
    filas.addEventListener('input', actualizar);
    actualizar();
})();
