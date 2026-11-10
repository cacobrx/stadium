#!/bin/bash

# Script para probar el API de productos con imágenes de variantes
# Fecha: 2025-10-09

echo "=== PRUEBA API PRODUCTOS CON IMÁGENES DE VARIANTES ==="
echo

# Test 1: Obtener producto existente con variantes
echo "1. Obteniendo producto ID=1 con variantes..."
curl -s "http://localhost/stadium/api/productos.php?id=1" \
  -H "Content-Type: application/json" | jq .

echo
echo "================================"
echo

# Test 2: Crear producto con variantes e imágenes
echo "2. Creando producto de prueba con variantes e imágenes..."

# Datos del producto
read -r -d '' PRODUCTO_DATA << 'EOF'
{
  "sku": "CURL-TEST-001",
  "nombre": "Zapatillas Test CURL",
  "descripcion": "Producto de prueba creado via CURL para testear variantes con imágenes",
  "precio": 199.99,
  "stock": 0,
  "esta_activo": 1,
  "destacado": 0,
  "marca_id": 1,
  "deporte": "Running",
  "variantes": [
    {
      "sku": "CURL-TEST-001-40",
      "nombre": "Zapatillas Test CURL - Talle 40",
      "stock": 3,
      "precio": null,
      "activa": 1,
      "atributos": [
        { "nombre": "Talle", "valor": "40" },
        { "nombre": "Color", "valor": "Negro" }
      ],
      "imagenes": [
        {
          "url": "/uploads/productos/curl-test-001-40-main.jpg",
          "alt_text": "Zapatillas Test CURL Talle 40 - Vista Principal"
        },
        {
          "url": "/uploads/productos/curl-test-001-40-side.jpg",
          "alt_text": "Zapatillas Test CURL Talle 40 - Vista Lateral"
        },
        {
          "url": "/uploads/productos/curl-test-001-40-back.jpg",
          "alt_text": "Zapatillas Test CURL Talle 40 - Vista Trasera"
        }
      ]
    },
    {
      "sku": "CURL-TEST-001-41",
      "nombre": "Zapatillas Test CURL - Talle 41",
      "stock": 7,
      "precio": 209.99,
      "activa": 1,
      "atributos": [
        { "nombre": "Talle", "valor": "41" },
        { "nombre": "Color", "valor": "Azul" }
      ],
      "imagenes": [
        {
          "url": "/uploads/productos/curl-test-001-41-main.jpg",
          "alt_text": "Zapatillas Test CURL Talle 41 - Vista Principal"
        },
        {
          "url": "/uploads/productos/curl-test-001-41-detail.jpg",
          "alt_text": "Zapatillas Test CURL Talle 41 - Detalle"
        }
      ]
    }
  ]
}
EOF

# Enviar solicitud POST
RESPONSE=$(curl -s -X POST "http://localhost/stadium/api/productos.php" \
  -H "Content-Type: application/json" \
  -d "$PRODUCTO_DATA")

echo "$RESPONSE" | jq .

# Extraer ID del producto creado si fue exitoso
PRODUCTO_ID=$(echo "$RESPONSE" | jq -r '.id // empty')

if [ ! -z "$PRODUCTO_ID" ]; then
    echo
    echo "3. Verificando producto creado (ID: $PRODUCTO_ID)..."
    curl -s "http://localhost/stadium/api/productos.php?id=$PRODUCTO_ID" \
      -H "Content-Type: application/json" | jq .
fi

echo
echo "=== FIN DE LAS PRUEBAS ==="