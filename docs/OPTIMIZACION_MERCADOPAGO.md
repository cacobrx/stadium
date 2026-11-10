# Optimización MercadoPago — Documentación Completa

**Fecha:** 26 de febrero de 2026  
**Versión:** 2.0  
**Basado en:** Skill `mercadopago-integration` (Checkout Pro)

---

## Índice

1. [Resumen de cambios](#1-resumen-de-cambios)
2. [Archivos modificados/creados](#2-archivos-modificadoscreados)
3. [Fase 1 — Seguridad](#3-fase-1--seguridad)
4. [Fase 2 — Lógica crítica](#4-fase-2--lógica-crítica)
5. [Fase 3 — Mejores prácticas MercadoPago](#5-fase-3--mejores-prácticas-mercadopago)
6. [Fase 4 — Webhook robusto](#6-fase-4--webhook-robusto)
7. [Fase 5 — Limpieza general](#7-fase-5--limpieza-general)
8. [Configuración requerida](#8-configuración-requerida)
9. [Checklist de producción](#9-checklist-de-producción)
10. [Flujo de pago completo](#10-flujo-de-pago-completo)
11. [Troubleshooting](#11-troubleshooting)
12. [Compatibilidad hacia atrás](#12-compatibilidad-hacia-atrás)

---

## 1. Resumen de cambios

Se optimizaron los archivos `api/pedidos/crear.php` y `api/pedidos/webhook.php` siguiendo las recomendaciones de la skill `mercadopago-integration`. Los cambios se organizaron en 5 fases:

| Fase | Descripción | Impacto |
|------|-------------|---------|
| 1 | Seguridad | Tokens centralizados, CORS restringido, errores no expuestos, validación de montos |
| 2 | Lógica crítica | Commit después de MP, precios siempre desde BD, preference_id guardado |
| 3 | Best practices MP | Items individuales, expiración, statement_descriptor, currency_id |
| 4 | Webhook robusto | Verificación HMAC, anti-replay, cross-check de monto, actualización atómica |
| 5 | Limpieza | Código de prueba eliminado, mensajes genéricos, funciones helper |

**Funcionalidad de base de datos preservada:** Toda la lógica de guardado en BD (clientes, pedidos, pedidos_det, clientes_direcciones, pagos) se mantiene intacta.

---

## 2. Archivos modificados/creados

### Archivo nuevo
| Archivo | Descripción |
|---------|-------------|
| `config/mercadopago.php` | Configuración centralizada de MercadoPago (tokens, URLs, límites, funciones helper) |

### Archivos modificados
| Archivo | Cambios principales |
|---------|-------------------|
| `api/pedidos/crear.php` | Reescrito con todas las optimizaciones (fases 1-3, 5) |
| `api/pedidos/webhook.php` | Reescrito con verificación HMAC, anti-replay, cross-check de monto (fase 4) |

---

## 3. Fase 1 — Seguridad

### 3.1 Tokens centralizados (`config/mercadopago.php`)

**Antes:** Tokens hardcodeados en `crear.php` y `webhook.php` (duplicados).

**Después:** Un solo archivo `config/mercadopago.php` que define:
- `MP_TOKEN_SANDBOX` y `MP_TOKEN_PRODUCTION`
- `MP_WEBHOOK_SECRET` (para verificación HMAC)
- `MP_APP_URL` y `MP_BACKEND_URL` (URLs base)
- `MP_MAX_CHECKOUT_AMOUNT` / `MP_MIN_CHECKOUT_AMOUNT` (límites de seguridad)
- `MP_STATEMENT_DESCRIPTOR`, `MP_CURRENCY_ID`, `MP_PREFERENCE_EXPIRATION_HOURS`
- Funciones helper: `mp_get_token()`, `mp_get_back_urls()`, `mp_get_notification_url()`

```php
// Uso en crear.php y webhook.php:
require_once __DIR__ . '/../../config/mercadopago.php';
$token = mp_get_token('sandbox');
```

### 3.2 display_errors desactivado

**Antes:** `ini_set('display_errors', 1)` — exponía errores al cliente.

**Después:** `ini_set('display_errors', 0)` + `ini_set('log_errors', 1)` — errores solo en logs del servidor.

### 3.3 CORS restringido

**Antes:** `Access-Control-Allow-Origin: *` — cualquier dominio podía hacer requests.

**Después:** Lista blanca de dominios permitidos:
```php
$allowedOrigins = [
    'https://stadiumapp.planbsistemas.com.ar',
    'https://stadium.planbsistemas.com.ar',
    'http://localhost:3000',
    'http://localhost:5173',
    'http://localhost',
];
```

### 3.4 Validación de montos server-side

**Nuevo:** Validación de monto máximo y mínimo antes de crear la preferencia de MP:
```php
if ($total > MP_MAX_CHECKOUT_AMOUNT) { ... } // Default: $5.000.000 ARS
if ($total < MP_MIN_CHECKOUT_AMOUNT) { ... } // Default: $100 ARS
```

### 3.5 Mensajes de error genéricos

**Antes:** `$e->getMessage()` se enviaba al cliente.

**Después:** Mensajes genéricos al cliente, detalles solo en logs:
```php
function jsonError(int $httpCode, string $message, ?string $internalDetail = null): void {
    if ($internalDetail) {
        error_log("crear.php ERROR [{$httpCode}]: {$message} — {$internalDetail}");
    }
    // Solo $message se envía al cliente
}
```

### 3.6 Validación de email

**Nuevo:** Se valida el email del pagador con `filter_var()`:
```php
if (!filter_var($pagador['email'], FILTER_VALIDATE_EMAIL)) {
    jsonError(400, 'Email del pagador inválido');
}
```

### 3.7 Validación de modo

**Nuevo:** Se valida que el campo `modo` solo sea `'sandbox'` o `'production'`:
```php
if (!in_array($data['modo'], ['sandbox', 'production'], true)) {
    jsonError(400, 'El campo "modo" debe ser "sandbox" o "production"');
}
```

---

## 4. Fase 2 — Lógica crítica

### 4.1 Commit después de MercadoPago

**Antes:** `$conn->commit()` se ejecutaba ANTES de crear la preferencia MP. Si MP fallaba, quedaba un pedido en BD sin link de pago.

**Después:** El flujo es:
1. `begin_transaction()`
2. Crear cliente, pedido, items en BD
3. Crear preferencia en MercadoPago (SDK)
4. Guardar `mercadopago_preference_id` en BD
5. `$conn->commit()` — solo si TODO fue exitoso

Si MP falla, el `catch` ejecuta `$conn->rollback()` y no queda nada a medias.

### 4.2 Precios siempre desde la BD

**Antes:** Si el JSON incluía `precioUnitario`, se usaba ese valor (el cliente podía manipularlo).

**Después:** El precio **siempre** viene de la tabla `productos`:
```php
// SEGURIDAD: precio SIEMPRE desde la BD (ignorar precioUnitario del cliente)
$precio = (float)$producto['precio'];
```

Además, si no se encuentra el producto por SKU, se lanza excepción:
```php
if ($resultProd->num_rows === 0) {
    throw new Exception("Producto no encontrado con SKU: {$sku}");
}
```

### 4.3 preference_id guardado en BD

**Antes:** El código para guardar el `preference_id` estaba comentado.

**Después:** Se guarda en la columna `mercadopago_preference_id` (que ya existía en el schema):
```php
$stmtMp = $conn->prepare("UPDATE pedidos SET mercadopago_preference_id = ? WHERE id = ?");
$stmtMp->bind_param("si", $mpPreference->id, $idped);
$stmtMp->execute();
```

### 4.4 Detalle de items nunca null

**Antes:** Se usaba `$item['descripcion']` que podía ser null.

**Después:** Se usa `$item['titulo']` como fallback:
```php
$detalle = !empty($item['titulo']) ? $item['titulo'] : $item['descripcion'];
```

---

## 5. Fase 3 — Mejores prácticas MercadoPago

### 5.1 Items individuales en la preferencia

**Antes:** Se enviaba un solo item genérico "Pedido #X - Envío" con el total.

**Después:** Se envían los items individuales + envío como item separado:
```php
foreach ($itemsCompletos as $item) {
    $mpItem              = new MercadoPago\Item();
    $mpItem->id          = $item['sku'];
    $mpItem->title       = mb_substr($item['titulo'], 0, 255);
    $mpItem->quantity    = $item['cantidad'];
    $mpItem->unit_price  = (float)$item['precio'];
    $mpItem->currency_id = MP_CURRENCY_ID; // 'ARS'
    $mpItems[]           = $mpItem;
}
// Envío como item separado
if ($costoEnvio > 0) {
    $mpItemEnvio = new MercadoPago\Item();
    $mpItemEnvio->id = 'ENVIO';
    $mpItemEnvio->title = $nombreEnvio;
    // ...
}
```

**Beneficio:** El comprador ve exactamente qué está pagando en la pantalla de MercadoPago.

### 5.2 currency_id en cada item

**Nuevo:** Cada item tiene `currency_id = 'ARS'` (configurable desde `config/mercadopago.php`).

### 5.3 statement_descriptor

**Nuevo:** Se agrega `statement_descriptor = 'STADIUM'` (max 22 chars). Es el texto que aparece en el resumen bancario del comprador.

### 5.4 Expiración de preferencias

**Nuevo:** Las preferencias de pago expiran a las 24 horas:
```php
$mpPreference->expires = true;
$mpPreference->expiration_date_from = date('c');
$mpPreference->expiration_date_to   = date('c', strtotime('+24 hours'));
```
Configurable con `MP_PREFERENCE_EXPIRATION_HOURS` en `config/mercadopago.php`.

### 5.5 auto_return solo con HTTPS

**Antes:** Se activaba siempre que venía en el JSON (podía causar error 400 en localhost).

**Después:** Solo se activa si la URL base usa HTTPS:
```php
if (strpos(MP_APP_URL, 'https') === 0) {
    $mpPreference->auto_return = 'approved';
}
```

### 5.6 URLs centralizadas

**Antes:** URLs hardcodeadas repetidas en varios lugares.

**Después:** Vienen de funciones helper:
```php
$backUrls = mp_get_back_urls($token);
$mpPreference->notification_url = mp_get_notification_url();
```

### 5.7 Verificación de creación de preferencia

**Nuevo:** Se verifica que MP haya creado la preferencia correctamente:
```php
if (empty($mpPreference->id)) {
    throw new Exception("No se pudo crear la preferencia de pago en MercadoPago");
}
```

---

## 6. Fase 4 — Webhook robusto

### 6.1 Verificación de firma HMAC-SHA256

**Nuevo:** Función `verificarFirmaWebhook()` que verifica la firma de MercadoPago usando:
- Header `x-signature` (contiene `ts` y `v1`)
- Header `x-request-id`
- Secret `MP_WEBHOOK_SECRET`

```php
$template = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
$expected = hash_hmac('sha256', $template, $secret);
hash_equals($expected, $hash); // timing-safe
```

**Modo de migración:** Si `MP_WEBHOOK_SECRET` está vacío, el webhook logea una advertencia pero sigue funcionando. Esto permite una migración gradual.

**Para activar la protección completa:**
1. Ir al [Panel de Desarrolladores de MercadoPago](https://www.mercadopago.com/developers/panel/app) → Webhooks
2. Copiar el secret
3. Pegarlo en `config/mercadopago.php` → `MP_WEBHOOK_SECRET`

### 6.2 Protección anti-replay

**Nuevo:** Se rechaza cualquier webhook con timestamp mayor a 5 minutos:
```php
$webhookAge = time() - (int)$ts;
if ($webhookAge < 0 || $webhookAge > MP_MAX_WEBHOOK_AGE_SECONDS) {
    return false; // Rechazar
}
```

### 6.3 Verificación de monto (cross-check)

**Nuevo:** Se compara el `transaction_amount` de MP contra el `total` del pedido en BD:
```php
if ($totalPedido > 0 && abs($mpMonto - $totalPedido) > 0.01) {
    // Guardar como 'amount_mismatch' para auditoría
    // NO confirmar el pedido
}
```

Si no coinciden, se registra el pago con estado `amount_mismatch` pero **no se confirma** el pedido.

### 6.4 Validación de payment_id

**Nuevo:** Solo se aceptan IDs numéricos de 1-20 dígitos:
```php
if (!preg_match('/^\d{1,20}$/', $paymentIdStr)) {
    // Rechazar sin reintentar
}
```

### 6.5 Actualización atómica del estado

**Antes:** Solo actualizaba estado y fechapago.

**Después:** Actualización atómica que también guarda `mercadopago_payment_id` y `mercadopago_status`:
```sql
UPDATE pedidos
SET estado = ?, fechapago = NOW(), mercadopago_payment_id = ?, mercadopago_status = ?
WHERE id = ? AND estado < 1  -- Solo si está pendiente (atómico, previene race conditions)
```

### 6.6 Manejo de estados rejected/cancelled/refunded

**Nuevo:** Si MP reporta rechazo o cancelación, se registra en la tabla pedidos:
```php
} elseif (in_array($mpStatus, ['rejected', 'cancelled', 'refunded'], true)) {
    // Registrar en pedidos sin confirmar
}
```

### 6.7 Siempre retornar HTTP 200

**Antes:** Retornaba 422, 404, 500 en errores — MP reintentaba indefinidamente.

**Después:** Siempre HTTP 200 (excepto firma inválida = 401 y token inválido = 403). Según la recomendación de la skill, esto evita reintentos infinitos.

### 6.8 GET endpoint para verificación

**Nuevo:** MP puede enviar pings GET para verificar que el webhook está activo:
```php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['status' => 'ok']);
    exit(0);
}
```

### 6.9 No exponer token del pedido en respuesta

**Antes:** Se devolvía el `token` del pedido en la respuesta del webhook.

**Después:** Solo se devuelven `idped` y `mp_status`.

---

## 7. Fase 5 — Limpieza general

### 7.1 Código de prueba eliminado

Se eliminaron ~80 líneas de datos de prueba comentados (JSON hardcodeados, `$datajson`, etc.).

### 7.2 print_r eliminados

Se eliminaron las líneas `// print_r($mpPreference)` y `// print_r($rr)`.

### 7.3 Función helper jsonError()

Se creó la función `jsonError()` para respuestas de error consistentes y limpias:
```php
function jsonError(int $httpCode, string $message, ?string $internalDetail = null): void
```

---

## 8. Configuración requerida

### `config/mercadopago.php` — Valores a personalizar

| Constante | Valor actual | Qué hacer |
|-----------|-------------|-----------|
| `MP_TOKEN_SANDBOX` | Token de test | Mantener para desarrollo |
| `MP_TOKEN_PRODUCTION` | Token de producción | Verificar que es vigente |
| `MP_WEBHOOK_SECRET` | `''` (vacío) | **CONFIGURAR** desde el Panel de MP |
| `MP_APP_URL` | `https://stadiumapp.planbsistemas.com.ar` | Verificar URL del frontend |
| `MP_BACKEND_URL` | `https://stadium.planbsistemas.com.ar` | Verificar URL del backend |
| `MP_MAX_CHECKOUT_AMOUNT` | `5000000` | Ajustar según negocio |
| `MP_MIN_CHECKOUT_AMOUNT` | `100` | Ajustar según MP (mín ~$15 ARS) |
| `MP_STATEMENT_DESCRIPTOR` | `'STADIUM'` | Nombre en resumen bancario (max 22 chars) |
| `MP_CURRENCY_ID` | `'ARS'` | Cambiar solo si la cuenta MP es de otro país |

### Obtener el Webhook Secret

1. Ir a https://www.mercadopago.com/developers/panel/app
2. Seleccionar la aplicación
3. Ir a **Webhooks** → **Configuración**
4. Copiar el **Secret** generado
5. Pegarlo en `config/mercadopago.php`:
   ```php
   define('MP_WEBHOOK_SECRET', 'tu-secret-aquí');
   ```

---

## 9. Checklist de producción

### Seguridad
- [ ] `MP_WEBHOOK_SECRET` configurado con el secret real de MP
- [ ] Verificar que `display_errors` está en `0` en el servidor
- [ ] Revisar que los tokens de producción son vigentes
- [ ] `MP_MAX_CHECKOUT_AMOUNT` ajustado al máximo real del negocio
- [ ] CORS restringido a dominios de producción (quitar localhost si no aplica)

### Configuración MP
- [ ] URLs de `back_urls` apuntan a producción (HTTPS)
- [ ] `notification_url` accesible públicamente desde internet
- [ ] `statement_descriptor` configurado (lo que verá el comprador en su banco)
- [ ] Tokens de producción (no TEST-) para modo producción

### Base de datos
- [ ] Columna `mercadopago_preference_id` existe en tabla `pedidos`
- [ ] Columna `mercadopago_payment_id` existe en tabla `pedidos`
- [ ] Columna `mercadopago_status` existe en tabla `pedidos`
- [ ] Tabla `pagos` con `UNIQUE KEY uk_mp_payment (mp_payment_id)`

### Testing
- [ ] Probar flujo completo en sandbox (crear pedido → pagar → webhook)
- [ ] Verificar que los items aparecen individuales en la pantalla de MP
- [ ] Verificar que el `mercadopago_preference_id` se guarda en la BD
- [ ] Probar pago aprobado: pedido pasa a estado 1
- [ ] Probar pago rechazado: pedido queda en estado 0, `mercadopago_status = 'rejected'`
- [ ] Verificar logs en `log/wh_YYMMDD.log`

---

## 10. Flujo de pago completo

```
┌────────────────────────────────────────────────────────────────────┐
│                     FLUJO DE PAGO OPTIMIZADO                       │
└────────────────────────────────────────────────────────────────────┘

Frontend envía POST /api/pedidos/crear.php
       │
       ▼
┌──────────────────────────────────────────────────┐
│ 1. Validar JSON, campos requeridos, email, modo  │
│ 2. Validar montos (min/max server-side)          │
│ 3. ── begin_transaction() ──                     │
│ 4. Buscar productos en BD (precio SIEMPRE de BD) │
│ 5. Crear/actualizar cliente                      │
│ 6. Crear pedido (estado=0, pendiente)            │
│ 7. Insertar items en pedidos_det                 │
│ 8. Crear preferencia en MercadoPago (SDK)        │
│    - Items individuales + envío                  │
│    - Expiración 24h                              │
│    - statement_descriptor                        │
│ 9. Guardar mercadopago_preference_id en pedido   │
│ 10. ── commit() ── (solo si TODO OK)             │
│ 11. Retornar init_point/sandbox_init_point       │
└──────────────────────────────────────────────────┘
       │
       ▼
Frontend redirige al usuario a MercadoPago
       │
       ▼
Usuario paga en MercadoPago
       │
       ├──────────────────────────┐
       │                          │
       ▼                          ▼
Redirect a back_url         MP envía Webhook POST
/checkout/success?token=X   /api/pedidos/webhook.php
       │                          │
       │                          ▼
       │               ┌───────────────────────────────────┐
       │               │ 1. Verificar firma HMAC-SHA256    │
       │               │ 2. Protección anti-replay (5 min) │
       │               │ 3. Validar payment_id (numérico)  │
       │               │ 4. Consultar API de MP             │
       │               │ 5. Buscar pedido en BD             │
       │               │ 6. Cross-check monto               │
       │               │ 7. UPSERT en tabla pagos           │
       │               │ 8. Actualización atómica estado    │
       │               │    (solo si estado < 1)            │
       │               │ 9. Guardar payment_id + status     │
       │               └───────────────────────────────────┘
       │
       ▼
Frontend verifica estado del pedido
```

---

## 11. Troubleshooting

### Error 400 al crear preferencia
- **Causa:** `auto_return` con URL HTTP (localhost)
- **Solución:** Se corrigió: solo se activa `auto_return` si `MP_APP_URL` empieza con `https`

### Preferencia no se crea
- **Causa:** Token inválido o expirado
- **Solución:** Verificar tokens en `config/mercadopago.php`. Regenerar desde el Panel de MP.

### Webhook no llega (desarrollo local)
- **Causa:** MercadoPago no puede alcanzar localhost
- **Solución:** Usar ngrok: `ngrok http 80` y configurar la URL generada en `MP_BACKEND_URL`

### Pedido sin link de pago
- **Antes:** Podía pasar porque `commit()` se hacía antes de MP.
- **Después:** Si MP falla, se hace `rollback()` — no queda pedido sin link.

### Monto no coincide en webhook
- **Protección:** El webhook registra el pago como `amount_mismatch` y NO confirma el pedido.
- **Revisar:** Tabla `pagos` donde `estado = 'amount_mismatch'`.

### Webhooks duplicados
- **Protección:** La actualización de estado es atómica (`WHERE estado < 1`). Si ya estaba confirmado, no hace nada.
- **La tabla pagos** usa `ON DUPLICATE KEY UPDATE` — es idempotente.

### "⚠ MP_WEBHOOK_SECRET no configurado" en logs
- **Causa:** El secret no está configurado en `config/mercadopago.php`.
- **Solución:** Obtener el secret desde el Panel de MP y configurarlo. Ver [sección 8](#8-configuración-requerida).

---

## 12. Compatibilidad hacia atrás

### JSON de entrada (crear.php)
**Sin cambios.** El JSON que envía el frontend sigue siendo el mismo:
```json
{
  "modo": "sandbox",
  "items": [{ "productoId": "...", "sku": "...", "cantidad": 1 }],
  "pagador": { "email": "...", "nombre": "...", ... },
  "envio": { "tipo": "domicilio", "nombre": "...", "costo": 2500 },
  "preferenciasCheckout": { ... },
  "retornoAutomatico": "approved"
}
```

### JSON de respuesta (crear.php)
**Cambio menor:** Se eliminó el campo `mp_error` de la respuesta exitosa (ahora si MP falla, se hace rollback y se devuelve error 500).

### Tablas BD
**Sin cambios en schema.** Se usan columnas que ya existían:
- `pedidos.mercadopago_preference_id`
- `pedidos.mercadopago_payment_id`
- `pedidos.mercadopago_status`

### Webhook
**Compatible:** Si `MP_WEBHOOK_SECRET` está vacío, el webhook funciona igual que antes (sin verificación HMAC, con advertencia en logs). Esto permite migrar gradualmente.

---

## Resumen de mejoras aplicadas vs. skill mercadopago-integration

| Recomendación skill | Estado | Archivo |
|--------------------|--------|---------|
| Tokens en config, no en código | ✅ Implementado | `config/mercadopago.php` |
| Webhook HMAC-SHA256 + timing-safe | ✅ Implementado | `webhook.php` |
| Protección anti-replay (5 min) | ✅ Implementado | `webhook.php` |
| Payment ID validation (numérico) | ✅ Implementado | `webhook.php` |
| Amount ceiling (MAX_CHECKOUT_AMOUNT) | ✅ Implementado | `crear.php` |
| Amount cross-check en webhook | ✅ Implementado | `webhook.php` |
| Atomic status updates | ✅ Implementado | `webhook.php` |
| Items individuales en preferencia | ✅ Implementado | `crear.php` |
| currency_id en items | ✅ Implementado | `crear.php` |
| statement_descriptor | ✅ Implementado | `crear.php` |
| Expiración de preferencias | ✅ Implementado | `crear.php` |
| auto_return solo con HTTPS | ✅ Implementado | `crear.php` |
| Precios server-side (no del cliente) | ✅ Implementado | `crear.php` |
| preference_id guardado en BD | ✅ Implementado | `crear.php` |
| CORS restringido | ✅ Implementado | `crear.php` |
| Errores no expuestos | ✅ Implementado | `crear.php` |
| Commit después de MP | ✅ Implementado | `crear.php` |
| Siempre HTTP 200 en webhook | ✅ Implementado | `webhook.php` |
| GET endpoint en webhook | ✅ Implementado | `webhook.php` |
| Idempotency key | ⚠ Parcial | El SDK v1 (dx-php) no soporta `requestOptions.idempotencyKey` directamente |
| Zod-style input validation | ⚠ Adaptado | PHP no tiene Zod; se usa validación manual equivalente |
| Rate limiting | ⏳ Pendiente | Configurar a nivel de infraestructura (nginx/Apache) |
