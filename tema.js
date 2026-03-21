// 1. APLICACIÓN INSTANTÁNEA (Se ejecuta al instante en todas las páginas para evitar el destello blanco)
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.setAttribute('data-bs-theme', savedTheme);

// 2. LÓGICA DEL BOTÓN (Se ejecuta solo cuando la página ha terminado de cargar)
document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById('darkModeToggle');

    // Comprobamos si el botón existe (así evitamos errores en producto.php o perfil.php donde no estará el botón)
    if (themeToggle) {
        const themeIcon = themeToggle.querySelector('i');

        // Ponemos el icono correcto al cargar
        updateIcon(savedTheme, themeIcon);

        // Evento al hacer click en el botón (Solo en el index)
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            document.documentElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateIcon(newTheme, themeIcon);
        });
    }

    // Función auxiliar para cambiar el dibujito del sol/luna
    function updateIcon(theme, icon) {
        if (!icon) return;
        if (theme === 'dark') {
            icon.classList.remove('bi-moon-stars-fill');
            icon.classList.add('bi-sun-fill', 'text-warning');
        } else {
            icon.classList.remove('bi-sun-fill', 'text-warning');
            icon.classList.add('bi-moon-stars-fill');
        }
    }
});