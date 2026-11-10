-- Script de prueba para verificar la integración Pedidos-Inventario
-- Ejecutar para verificar que los datos están correctos

-- 1. Verificar productos con variantes
SELECT 
    p.id as producto_id,
    p.nombre as producto,
    p.sku as producto_sku,
    COUNT(pv.id) as num_variantes
FROM productos p
LEFT JOIN producto_variantes pv ON p.id = pv.producto_id
GROUP BY p.id
ORDER BY p.nombre;

-- 2. Verificar variantes con precio y stock
SELECT 
    pv.id,
    p.nombre as producto,
    pv.sku_variante,
    pv.precio,
    pv.stock,
    pv.activa,
    GROUP_CONCAT(CONCAT(va.nombre, ': ', va.valor) SEPARATOR ', ') as atributos
FROM producto_variantes pv
INNER JOIN productos p ON pv.producto_id = p.id
LEFT JOIN variante_atributos va ON pv.id = va.variante_id
GROUP BY pv.id
ORDER BY p.nombre, pv.sku_variante;

-- 3. Verificar pedidos existentes y sus productos
SELECT 
    ped.id as pedido_id,
    ped.fecha,
    CONCAT(cli.apellido, ', ', cli.nombre) as cliente,
    det.idart,
    pv.sku_variante,
    p.nombre as producto,
    det.cantidad,
    det.precio,
    pv.stock as stock_actual
FROM pedidos ped
INNER JOIN pedidos_det det ON ped.id = det.idped
LEFT JOIN clientes cli ON ped.idcli = cli.id
LEFT JOIN producto_variantes pv ON det.idart = pv.id
LEFT JOIN productos p ON pv.producto_id = p.id
ORDER BY ped.id DESC
LIMIT 10;

-- 4. Verificar variantes sin precio (necesitarán ingreso manual)
SELECT 
    pv.id,
    p.nombre as producto,
    pv.sku_variante,
    pv.precio,
    pv.stock,
    CASE 
        WHEN pv.precio IS NULL OR pv.precio = 0 THEN '⚠️ SIN PRECIO'
        ELSE '✓ OK'
    END as estado_precio
FROM producto_variantes pv
INNER JOIN productos p ON pv.producto_id = p.id
WHERE pv.activa = 1
ORDER BY (pv.precio IS NULL OR pv.precio = 0) DESC, p.nombre;

-- 5. Verificar variantes sin stock
SELECT 
    pv.id,
    p.nombre as producto,
    pv.sku_variante,
    pv.precio,
    pv.stock,
    CASE 
        WHEN pv.stock IS NULL OR pv.stock <= 0 THEN '⚠️ SIN STOCK'
        WHEN pv.stock < 5 THEN '⚠️ STOCK BAJO'
        ELSE '✓ OK'
    END as estado_stock
FROM producto_variantes pv
INNER JOIN productos p ON pv.producto_id = p.id
WHERE pv.activa = 1
ORDER BY pv.stock ASC, p.nombre;
