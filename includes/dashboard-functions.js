/* Dashboard JavaScript Functions */

// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    sidebar.classList.toggle('sidebar-collapsed');
    mainContent.classList.toggle('content-collapsed');
}

// Load saved theme
function loadTheme() {
    const savedTheme = localStorage.getItem('stadium-theme');
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    
    // Si hay un tema guardado, aplicarlo
    if (savedTheme) {
        html.setAttribute('data-bs-theme', savedTheme);
        if (themeIcon) {
            themeIcon.className = savedTheme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
        }
    } else {
        // Tema por defecto (light)
        html.setAttribute('data-bs-theme', 'light');
        if (themeIcon) {
            themeIcon.className = 'bi bi-sun-fill';
        }
    }
}

// Toggle theme
function toggleTheme() {
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');
    
    if (html.getAttribute('data-bs-theme') === 'light') {
        html.setAttribute('data-bs-theme', 'dark');
        themeIcon.className = 'bi bi-moon-fill';
        localStorage.setItem('stadium-theme', 'dark');
    } else {
        html.setAttribute('data-bs-theme', 'light');
        themeIcon.className = 'bi bi-sun-fill';
        localStorage.setItem('stadium-theme', 'light');
    }
}

// Toggle submenu
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const isVisible = submenu.style.display !== 'none';
    submenu.style.display = isVisible ? 'none' : 'block';
}

// Abrir el sistema (dashboard) en nueva pestaña
function abrirEnNuevaPestana() {
    window.open('index.php?page=dashboard', '_blank');
}

// Navigate to page (legacy function - now handled by direct links)
function showSection(sectionName) {
    // Redirect to the specific page
    window.location.href = sectionName;
}

// Logout function
function logout() {
    showConfirmation(
        '¿Cerrar sesión?', 
        '¿Está seguro que desea cerrar sesión?', 
        'Sí, cerrar sesión',
        'Cancelar'
    ).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout';
        }
    });
}

// Mobile menu toggle
function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}