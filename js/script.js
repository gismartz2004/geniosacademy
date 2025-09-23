// Funciones generales para GeiosBot Academy
document.addEventListener('DOMContentLoaded', function() {
    // Manejo de formularios
    initForms();
    
    // Manejo de tabs
    initTabs();
    
    // Manejo de carga de archivos
    initFileUploads();
    
    // Funcionalidades de administración
    initAdminFunctions();
});

// Inicializar comportamientos de formularios
function initForms() {
    // Validación de contraseñas coincidentes
    const passwordForms = document.querySelectorAll('form');
    passwordForms.forEach(form => {
        if (form.querySelector('input[type="password"]')) {
            form.addEventListener('submit', function(e) {
                const password = form.querySelector('#nuevo_password');
                const confirmPassword = form.querySelector('#confirmar_password');
                
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    e.preventDefault();
                    alert('Las contraseñas no coinciden. Por favor, verifica.');
                    confirmPassword.focus();
                }
            });
        }
    });
    
    // Mostrar/ocultar contraseñas
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.textContent = 'Ocultar';
            } else {
                passwordInput.type = 'password';
                this.textContent = 'Mostrar';
            }
        });
    });
}

// Sistema de pestañas/tabs
function initTabs() {
    const tabContainers = document.querySelectorAll('.tabs');
    
    tabContainers.forEach(container => {
        const tabButtons = container.querySelectorAll('.tab-button');
        const tabPanes = container.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Desactivar todas las pestañas
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Activar la pestaña seleccionada
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
    });
}

// Manejo de carga de archivos
function initFileUploads() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const fileName = this.files[0]?.name;
            const fileSize = this.files[0]?.size;
            const maxSize = this.getAttribute('data-max-size') || 50 * 1024 * 1024; // 50MB por defecto
            
            if (fileName) {
                // Actualizar texto del label
                const label = this.nextElementSibling;
                if (label && label.classList.contains('file-label')) {
                    label.textContent = fileName;
                }
                
                // Validar tamaño de archivo
                if (fileSize > maxSize) {
                    alert('El archivo excede el tamaño máximo permitido.');
                    this.value = '';
                }
            }
        });
    });
}

// Funciones de administración
function initAdminFunctions() {
    // Botones de eliminar con confirmación
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });
    
    // Búsqueda en tiempo real en tablas
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableId = this.getAttribute('data-table');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    });
}

// Funciones para el panel de administración
function adminCreateUser(userType) {
    const form = document.getElementById('admin-create-user-form');
    const typeField = document.getElementById('user_type');
    
    if (form && typeField) {
        typeField.value = userType;
        form.scrollIntoView({ behavior: 'smooth' });
    }
}

// Mostrar notificación toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Mostrar toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    // Ocultar y eliminar después de 3 segundos
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Cargar contenido dinámico (para modales, etc.)
function loadContent(url, containerId) {
    fetch(url)
        .then(response => response.text())
        .then(data => {
            document.getElementById(containerId).innerHTML = data;
        })
        .catch(error => {
            console.error('Error loading content:', error);
        });
}