</div>

<footer class="footer mt-auto py-3 bg-light border-top">
    <div class="container text-center">
        <span class="text-muted">&copy; <?= date("Y"); ?> Sabor & Frescura. Todos los derechos reservados.</span>
    </div>
</footer>

<!-- Contenedor global de toasts -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;" id="toast-container"></div>

<!-- Modal global de confirmación -->
<div class="modal fade" id="modal-confirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-question-circle me-2 text-warning"></i>Confirmar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-confirmar-texto">¿Está seguro?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="modal-confirmar-ok">Sí, continuar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * Muestra una notificación toast.
 * @param {string} mensaje
 * @param {'success'|'danger'|'warning'|'info'} tipo
 */
function mostrarToast(mensaje, tipo = 'success') {
    const iconos = { success: 'check-circle-fill', danger: 'x-circle-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const cont = document.getElementById('toast-container');
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${tipo} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-${iconos[tipo] || 'info-circle-fill'} me-2"></i>${mensaje}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>`;
    cont.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 4000 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/**
 * Modal de confirmación basado en promesa (reemplaza al confirm() nativo).
 * @returns {Promise<boolean>}
 */
function confirmarModal(texto = '¿Está seguro?', textoBoton = 'Sí, continuar') {
    return new Promise((resolve) => {
        const modalEl = document.getElementById('modal-confirmar');
        document.getElementById('modal-confirmar-texto').textContent = texto;
        const btnOk = document.getElementById('modal-confirmar-ok');
        btnOk.textContent = textoBoton;
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        const onOk = () => { cleanup(); modal.hide(); resolve(true); };
        const onHide = () => { cleanup(); resolve(false); };
        function cleanup() {
            btnOk.removeEventListener('click', onOk);
            modalEl.removeEventListener('hidden.bs.modal', onHide);
        }
        btnOk.addEventListener('click', onOk);
        modalEl.addEventListener('hidden.bs.modal', onHide, { once: true });
        modal.show();
    });
}

// Intercepta formularios con [data-confirm] para usar el modal en lugar de confirm() nativo
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.dataset.confirm && !form.dataset.confirmed) {
        e.preventDefault();
        confirmarModal(form.dataset.confirm, form.dataset.confirmBtn || 'Sí, continuar').then(ok => {
            if (ok) { form.dataset.confirmed = '1'; form.submit(); }
        });
    }
}, true);
</script>
</body>
</html>
