<?php
/**
 * fix_variantes_faltantes.php
 * 
 * Agrega una variante por defecto a todos los productos que no tienen ninguna.
 * La variante se crea con el mismo SKU, nombre, stock y precio del producto,
 * copiando también sus imágenes.
 * 
 * Uso: php fix_variantes_faltantes.php
 *      O acceder por navegador (requiere autenticación de servidor).
 */

require_once __DIR__ . '/../config/database.php';

$db = get_database_connection();
$db->autocommit(false);

$ok = 0;
$errors = [];

try {
    // Productos sin ninguna variante
    $result = $db->query("
        SELECT p.id, p.sku, p.nombre, p.precio, p.stock
        FROM productos p
        WHERE NOT EXISTS (
            SELECT 1 FROM producto_variantes v WHERE v.producto_id = p.id
        )
        ORDER BY p.id
    ");

    if (!$result) {
        throw new Exception('Error al consultar productos: ' . $db->error);
    }

    $productos = $result->fetch_all(MYSQLI_ASSOC);
    $total = count($productos);

    echo "Productos sin variantes encontrados: {$total}\n\n";

    $stmt_var = $db->prepare("
        INSERT INTO producto_variantes (producto_id, sku_variante, nombre, stock, precio, activa)
        VALUES (?, ?, ?, ?, ?, 1)
    ");
    $stmt_img = $db->prepare("
        INSERT INTO variante_imagenes (variante_id, url, alt_text, es_principal, orden)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($productos as $p) {
        $pid    = (int)$p['id'];
        $sku    = $p['sku'];
        $nombre = $p['nombre'];
        $stock  = (int)$p['stock'];
        $precio = (float)$p['precio'];

        // Insertar variante por defecto
        $stmt_var->bind_param('issid', $pid, $sku, $nombre, $stock, $precio);
        if (!$stmt_var->execute()) {
            $errors[] = "Producto #{$pid} ({$sku}): " . $stmt_var->error;
            continue;
        }
        $variante_id = $db->insert_id;

        // Copiar imágenes del producto a la variante
        $img_result = $db->query("
            SELECT url, alt_text, es_principal, orden
            FROM producto_imagenes
            WHERE producto_id = {$pid}
            ORDER BY es_principal DESC, orden ASC
        ");

        if ($img_result && $img_result->num_rows > 0) {
            while ($img = $img_result->fetch_assoc()) {
                $url          = $img['url'];
                $alt          = $img['alt_text'] ?: $nombre;
                $es_principal = (int)$img['es_principal'];
                $orden        = (int)$img['orden'];
                $stmt_img->bind_param('issii', $variante_id, $url, $alt, $es_principal, $orden);
                if (!$stmt_img->execute()) {
                    $errors[] = "Imagen para variante #{$variante_id} (prod #{$pid}): " . $stmt_img->error;
                }
            }
        }

        $ok++;
        echo "  ✓ #{$pid} {$sku} — variante creada (id variante: {$variante_id})\n";
    }

    if (!empty($errors)) {
        $db->rollback();
        echo "\nERRORES — se revirtieron todos los cambios:\n";
        foreach ($errors as $e) {
            echo "  ✗ {$e}\n";
        }
    } else {
        $db->commit();
        echo "\nCompletado: {$ok}/{$total} variantes creadas. Cambios guardados.\n";
    }

} catch (Exception $e) {
    $db->rollback();
    echo "ERROR FATAL: " . $e->getMessage() . "\n";
} finally {
    $db->close();
}
