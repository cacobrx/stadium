/* SweetAlert2 Helper Functions */

// Success alert for CRUD operations
function showSuccessAlert(title, message, timer = 3000) {
    return Swal.fire({
        icon: 'success',
        title: title,
        text: message,
        confirmButtonColor: '#0d6efd',
        timer: timer,
        showConfirmButton: false
    });
}

// Error alert
function showErrorAlert(title, message) {
    return Swal.fire({
        icon: 'error',
        title: title,
        text: message,
        confirmButtonColor: '#0d6efd'
    });
}

// Confirmation alert for delete operations
function showDeleteConfirmation(message = '¿Está seguro de que desea eliminar este elemento? Esta acción no se puede deshacer.') {
    return Swal.fire({
        title: '¿Confirmar eliminación?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
}

// General confirmation alert
function showConfirmation(title, message, confirmText = 'Confirmar', cancelText = 'Cancelar') {
    return Swal.fire({
        title: title,
        text: message,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText
    });
}

// Warning alert
function showWarningAlert(title, message) {
    return Swal.fire({
        icon: 'warning',
        title: title,
        text: message,
        confirmButtonColor: '#0d6efd'
    });
}

// Info alert
function showInfoAlert(title, message) {
    return Swal.fire({
        icon: 'info',
        title: title,
        text: message,
        confirmButtonColor: '#0d6efd'
    });
}

// Loading alert
function showLoadingAlert(title = 'Procesando...', message = 'Por favor espere') {
    return Swal.fire({
        title: title,
        text: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Close loading alert
function closeLoadingAlert() {
    Swal.close();
}

// Toast notification (small notification)
function showToast(icon, message, position = 'top-end') {
    const Toast = Swal.mixin({
        toast: true,
        position: position,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    return Toast.fire({
        icon: icon,
        title: message
    });
}

// Specific CRUD operation helpers
const CRUDAlerts = {
    // Create operations
    created: (itemName = 'elemento') => {
        return showSuccessAlert('¡Creado!', `${itemName} creado correctamente`);
    },
    
    // Update operations  
    updated: (itemName = 'elemento') => {
        return showSuccessAlert('¡Actualizado!', `${itemName} actualizado correctamente`);
    },
    
    // Delete operations
    deleted: (itemName = 'elemento') => {
        return showSuccessAlert('¡Eliminado!', `${itemName} eliminado correctamente`);
    },
    
    // Delete confirmation
    confirmDelete: (itemName = 'elemento') => {
        return showDeleteConfirmation(`¿Está seguro de que desea eliminar este ${itemName}? Esta acción no se puede deshacer.`);
    },
    
    // Error operations
    createError: (error = 'Error desconocido') => {
        return showErrorAlert('Error al crear', error);
    },
    
    updateError: (error = 'Error desconocido') => {
        return showErrorAlert('Error al actualizar', error);
    },
    
    deleteError: (error = 'Error desconocido') => {
        return showErrorAlert('Error al eliminar', error);
    },
    
    connectionError: () => {
        return showErrorAlert('Error de conexión', 'No se pudo conectar con el servidor');
    }
};

// Alias cortos para uso general
function showError(message) {
    return Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        confirmButtonColor: '#0d6efd'
    });
}

function showSuccess(message) {
    return Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: message,
        confirmButtonColor: '#0d6efd',
        timer: 3000,
        showConfirmButton: false
    });
}

function showWarning(message) {
    return Swal.fire({
        icon: 'warning',
        title: 'Atención',
        text: message,
        confirmButtonColor: '#0d6efd'
    });
}

function showInfo(message) {
    return Swal.fire({
        icon: 'info',
        title: 'Información',
        text: message,
        confirmButtonColor: '#0d6efd'
    });
}