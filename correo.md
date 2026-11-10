# /rates [POST]

Devuelve las cotizaciones de un envío a destino o a una sucursal.

## Endpoint
POST ${BASE_URL}/rates

Headers obligatorios de ejemplo:
- `Authorization: Bearer <access_token>`
- `Content-Type: application/json`

---

## Body Attributes

| Campo | Descripción | Requerido |
|---|---:|:---:|
| customerId | Identificador de usuario de MiCorreo. | ✔ |
| postalCodeOrigin | CP de origen del envío a cotizar. | ✔ |
| postalCodeDestination | CP de destino del envío a cotizar. | ✔ |
| deliveredType | `"D"` para entrega a domicilio, o `"S"` para entrega en sucursal. |  |
| dimensions.weight | Peso en gramos del envío (mínimo 1g y máximo 25000g). | ✔ |
| dimensions.height | Alto en centímetros del envío (máximo 150cm). | ✔ |
| dimensions.width | Ancho en centímetros del envío (máximo 150cm). | ✔ |
| dimensions.length | Largo en centímetros del envío (máximo 150cm). | ✔ |

Nota: Todos los campos del objeto `dimensions` son valores enteros (sin decimales).

---

## Ejemplo: Request - Envío a Domicilio

JSON:
```json
{
  "customerId": "0000550137",
  "postalCodeOrigin": "1757",
  "postalCodeDestination": "1704",
  "deliveredType": "D",
  "dimensions": {
    "weight": 2500,
    "height": 10,
    "width": 20,
    "length": 30
  }
}
```

curl:
```bash
curl -X POST "${BASE_URL}/rates" \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
  -H "Content-Type: application/json" \
  -d '{"customerId":"0090000025","postalCodeOrigin":"1757","postalCodeDestination":"1704","deliveredType":"D","dimensions":{"weight":2500,"height":10,"width":20,"length":30}}'
```

Response (200 OK):
```json
{
  "customerId": "0000550997",
  "validTo": "2022-06-07T10:31:27.881-03:00",
  "rates": [
    {
      "deliveredType": "D",
      "productType": "CP",
      "productName": "Correo Argentino Clasico",
      "price": 498.06,
      "deliveryTimeMin": "2",
      "deliveryTimeMax": "5"
    }
  ]
}
```

---

## Ejemplo: Request - Envío a Sucursal

JSON:
```json
{
  "customerId": "0000550997",
  "postalCodeOrigin": "1757",
  "postalCodeDestination": "1704",
  "deliveredType": "S",
  "dimensions": {
    "weight": 2500,
    "height": 10,
    "width": 20,
    "length": 30
  }
}
```

curl (igual que en el ejemplo anterior, cambiando `deliveredType` a `S`):
```bash
curl -X POST "${BASE_URL}/rates" \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"customerId":"0090000025","postalCodeOrigin":"1757","postalCodeDestination":"1704","deliveredType":"S","dimensions":{"weight":2500,"height":10,"width":20,"length":30}}'
```

Response (200 OK):
```json
{
  "customerId": "0000550997",
  "validTo": "2022-06-07T10:31:27.881-03:00",
  "rates": [
    {
      "deliveredType": "S",
      "productType": "CP",
      "productName": "Correo Argentino Clasico",
      "price": 398.06,
      "deliveryTimeMin": "2",
      "deliveryTimeMax": "5"
    }
  ]
}
```

---

## Ejemplo: Request - Ambas cotizaciones en un mismo request

Si no se especifica `deliveredType`, el servicio puede devolver ambas opciones (D y S).

JSON:
```json
{
  "customerId": "0000550997",
  "postalCodeOrigin": "1757",
  "postalCodeDestination": "1704",
  "dimensions": {
    "weight": 2500,
    "height": 10,
    "width": 20,
    "length": 30
  }
}
```

curl:
```bash
curl -X POST "${BASE_URL}/rates" \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"customerId":"0090000025","postalCodeOrigin":"1757","postalCodeDestination":"1704","dimensions":{"weight":2500,"height":10,"width":20,"length":30}}'
```

Response (200 OK) — ejemplo con ambas cotizaciones:
```json
{
  "customerId": "0000550997",
  "validTo": "2022-06-07T10:31:27.881-03:00",
  "rates": [
    {
      "deliveredType": "D",
      "productType": "CP",
      "productName": "Correo Argentino Clasico",
      "price": 498.06,
      "deliveryTimeMin": "2",
      "deliveryTimeMax": "5"
    },
    {
      "deliveredType": "S",
      "productType": "CP",
      "productName": "Correo Argentino Clasico",
      "price": 398.06,
      "deliveryTimeMin": "2",
      "deliveryTimeMax": "5"
    }
  ]
}
```

---

## Response (402 ERROR) — ejemplo
```json
{
  "code": "402",
  "message": "Cliente FAP no identificado {customerId}"
}
```

---

## Notas y recomendaciones
- Reemplaza `${BASE_URL}` y `<ACCESS_TOKEN>` por tus valores reales. Para obtener el token usa el endpoint `/token` con las credenciales del cliente.
- Usa comillas simples alrededor de la contraseña en curl si contiene caracteres especiales.
- Para depuración agrega `-v` a curl y verifica el body y las cabeceras.
- Asegúrate de que `dimensions.*` sean enteros dentro de los rangos indicados.