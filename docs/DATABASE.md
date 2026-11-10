# Configuración de Base de Datos - Stadium

## Configuración Inicial

1. **Copiar el archivo de configuración:**
   ```bash
   cp config/database.example.php config/database.php
   ```

2. **Editar la configuración:**
   Abrir `config/database.php` y ajustar los valores:
   ```php
   define('DB_HOST', 'tu_servidor');
   define('DB_USER', 'tu_usuario');
   define('DB_PASSWORD', 'tu_contraseña');  
   define('DB_NAME', 'tu_base_datos');
   ```

## Uso en APIs

Todos los archivos API deben incluir la configuración:

```php
// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

// Usar la función centralizada
function get_db_connection() {
    return get_database_connection();
}
```

## Funciones Disponibles

### `get_database_connection()`
Obtiene una conexión mysqli con singleton pattern.

### `execute_query($sql, $types, $params)`
Ejecuta consultas SELECT de manera segura.
```php
$productos = execute_query(
    "SELECT * FROM productos WHERE precio > ?", 
    "d", 
    [100.0]
);
```

### `execute_update($sql, $types, $params)`  
Ejecuta INSERT/UPDATE/DELETE y retorna filas afectadas o ID.
```php
$nuevo_id = execute_update(
    "INSERT INTO clientes (nombre, email) VALUES (?, ?)",
    "ss",
    ["Juan Pérez", "juan@email.com"]
);
```

## Seguridad

- El archivo `config/database.php` está en `.gitignore`
- Usa consultas preparadas automáticamente
- Manejo de errores centralizado
- Logs de errores habilitados

## Archivos Actualizados

- ✅ `/api/pedidos.php`
- ✅ `/api/pedidos_det.php`  
- ✅ `/api/articulos.php`
- ✅ `/api/clientes.php`

## Ventajas

1. **Seguridad:** Credenciales centralizadas y protegidas
2. **Mantenimiento:** Un solo lugar para cambiar configuración  
3. **Funciones auxiliares:** Simplifica consultas comunes
4. **Singleton:** Reutiliza conexiones para mejor performance
5. **Logs:** Mejor debugging y monitoreo