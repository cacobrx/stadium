# SweetAlert2 Implementation Guide

## Archivos Necesarios

Para usar SweetAlert2 en cualquier página del sistema Stadium, incluir:

```html
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php include __DIR__ . '/../includes/sweetalert-helpers.js'; ?>
</script>
```

## Funciones Helper Disponibles

### Alertas Básicas

```javascript
// Alerta de éxito
showSuccessAlert('¡Éxito!', 'Operación completada correctamente');

// Alerta de error
showErrorAlert('Error', 'Ha ocurrido un error inesperado');

// Alerta de advertencia
showWarningAlert('Advertencia', 'Revise los datos ingresados');

// Alerta informativa
showInfoAlert('Información', 'Datos actualizados');
```

### Confirmaciones

```javascript
// Confirmación general
const result = await showConfirmation('¿Continuar?', 'Esta acción modificará los datos');
if (result.isConfirmed) {
    // Proceder con la acción
}

// Confirmación de eliminación
const result = await showDeleteConfirmation('¿Eliminar este elemento?');
if (result.isConfirmed) {
    // Proceder con eliminación
}
```

### Alertas de Carga

```javascript
// Mostrar loading
showLoadingAlert('Procesando...', 'Guardando datos');

// Cerrar loading
closeLoadingAlert();
```

### Notificaciones Toast

```javascript
// Toast de éxito
showToast('success', 'Datos guardados correctamente');

// Toast de error
showToast('error', 'Error en la conexión');

// Toast de advertencia
showToast('warning', 'Revise los datos');

// Toast de información
showToast('info', 'Proceso completado');
```

### Helpers CRUD

```javascript
// Para operaciones de creación
CRUDAlerts.created('Usuario'); // "Usuario creado correctamente"

// Para operaciones de actualización
CRUDAlerts.updated('Cliente'); // "Cliente actualizado correctamente"

// Para operaciones de eliminación
CRUDAlerts.deleted('Producto'); // "Producto eliminado correctamente"

// Confirmación de eliminación
const result = await CRUDAlerts.confirmDelete('usuario');
if (result.isConfirmed) {
    // Proceder con eliminación
}

// Errores CRUD
CRUDAlerts.createError('El email ya existe');
CRUDAlerts.updateError('Datos inválidos');
CRUDAlerts.deleteError('No se puede eliminar');
CRUDAlerts.connectionError(); // Error de conexión genérico
```

## Ejemplos de Uso en Operaciones CRUD

### Crear Elemento
```javascript
async function createElement() {
    try {
        const response = await fetch('./api/endpoint.php', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            CRUDAlerts.created('Elemento');
            loadElements(); // Recargar lista
        } else {
            CRUDAlerts.createError(result.error);
        }
    } catch (error) {
        CRUDAlerts.connectionError();
    }
}
```

### Eliminar Elemento
```javascript
async function deleteElement(id) {
    const confirmation = await CRUDAlerts.confirmDelete('elemento');
    
    if (!confirmation.isConfirmed) {
        return;
    }
    
    try {
        const response = await fetch('./api/endpoint.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            CRUDAlerts.deleted('Elemento');
            loadElements(); // Recargar lista
        } else {
            CRUDAlerts.deleteError(result.error);
        }
    } catch (error) {
        CRUDAlerts.connectionError();
    }
}
```

### Actualizar Elemento
```javascript
async function updateElement(id, data) {
    try {
        const response = await fetch('./api/endpoint.php', {
            method: 'PUT',
            body: JSON.stringify({ id, ...data })
        });
        
        const result = await response.json();
        
        if (result.success) {
            CRUDAlerts.updated('Elemento');
            loadElements(); // Recargar lista
        } else {
            CRUDAlerts.updateError(result.error);
        }
    } catch (error) {
        CRUDAlerts.connectionError();
    }
}
```

## Personalización de Colores

Todas las alertas usan los colores del tema Bootstrap por defecto:
- Botón principal: `#0d6efd` (Bootstrap primary)
- Botón de cancelar: `#6c757d` (Bootstrap secondary)
- Botón de eliminar: `#dc3545` (Bootstrap danger)

## Páginas Actualizadas

✅ Dashboard (`pages/dashboard.php`)
✅ Usuarios (`pages/usuarios.php`) 
✅ Inicio/Login (`pages/inicio.php`)
✅ Funciones principales (`includes/dashboard-functions.js`)

## Template para Nuevas Páginas

Usar el archivo `includes/page-template.php` como base para nuevas páginas, que ya incluye SweetAlert2 y las funciones helper configuradas.