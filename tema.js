// =============================================================================
// ALGORYA - tema.js
// Gestión del modo oscuro/claro con persistencia en localStorage.
// Soporta múltiples botones de toggle (móvil + escritorio).
// =============================================================================

(function () {
    // Aplicar tema guardado inmediatamente para evitar flash de contenido
    const temaGuardado = localStorage.getItem('algorya-tema') || 'light';
    document.documentElement.setAttribute('data-bs-theme', temaGuardado);

    // Actualizar todos los iconos de toggle según el tema activo
    function actualizarIconos(tema) {
        const iconoClaro  = 'bi-moon-stars-fill';
        const iconoOscuro = 'bi-sun-fill';
        const icono       = tema === 'dark' ? iconoOscuro : iconoClaro;

        // Actualizar todos los toggles (móvil + escritorio)
        ['darkModeToggle', 'darkModeToggleMobile'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const i = el.querySelector('i');
                if (i) {
                    i.className = 'bi ' + icono + (id === 'darkModeToggleMobile' ? '' : ' fs-6');
                }
            }
        });
    }

    // Función para cambiar el tema
    function toggleTema() {
        const actual  = document.documentElement.getAttribute('data-bs-theme') || 'light';
        const nuevo   = actual === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', nuevo);
        localStorage.setItem('algorya-tema', nuevo);
        actualizarIconos(nuevo);
    }

    // Inicializar iconos al cargar
    document.addEventListener('DOMContentLoaded', function () {
        const temaActual = localStorage.getItem('algorya-tema') || 'light';
        actualizarIconos(temaActual);

        // Añadir listener a todos los toggles
        ['darkModeToggle', 'darkModeToggleMobile'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', toggleTema);
        });
    });
})();