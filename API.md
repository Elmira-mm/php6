# API каталогу товарів (`resource=products`)

Єдина точка входу: `api.php`. Усі запити йдуть на цей файл з параметрами
в рядку запиту (`resource`, `id`, `action`).

Узгоджений формат відповіді:
- Успіх: `{"success": true, "data": ...}`
- Помилка: `{"success": false, "error": "..."}`

---

## GET /api.php?resource=products

Повертає список усіх товарів.

**Код відповіді:** `200 OK`

```json
{
  "success": true,
  "data": [
    { "id": 1, "name": "Ceremonial Matcha Kyoto", "price": "899.00", "sku": "MAT-003", "stock": 14 },
    { "id": 2, "name": "Resistance Bands Set", "price": "699.00", "sku": "GEAR-014", "stock": 18 }
  ]
}
```

---

## GET /api.php?resource=products&id=1

Повертає один товар за `id`.

**Коди відповіді:**
- `200 OK` — товар знайдено
- `400 Bad Request` — `id` не є цілим числом
- `404 Not Found` — товару з таким `id` немає в таблиці

```json
{ "success": true, "data": { "id": 1, "name": "Ceremonial Matcha Kyoto", "price": "899.00", "sku": "MAT-003", "stock": 14 } }
```

```json
{ "success": false, "error": "Товар з id 999 не знайдено." }
```

---

## POST /api.php?resource=products

Створює новий товар. Тіло запиту — JSON (`Content-Type: application/json`)
або звичайна форма (`$_POST`). Обов'язкові поля: `name`, `price`, `sku`, `stock`.

**Приклад тіла запиту:**
```json
{ "name": "Matcha Latte Blend 150g", "price": 489, "sku": "MAT-004", "stock": 20 }
```

**Коди відповіді:**
- `201 Created` — товар створено
- `400 Bad Request` — не вистачає обов'язкового поля або поле некоректного формату (у відповіді є `fields` з переліком помилок за кожним полем)
- `409 Conflict` — товар з таким `sku` вже існує

```json
{ "success": true, "data": { "id": 7, "name": "Matcha Latte Blend 150g", "price": "489.00", "sku": "MAT-004", "stock": 20 } }
```

```json
{ "success": false, "error": "Некоректні дані.", "fields": { "price": "Поле price має бути числом більшим за 0." } }
```

---

## POST /api.php?resource=products&action=restock

Доменна дія — поповнює залишок (`stock`) наявного товару на вказану кількість.

**Приклад тіла запиту:**
```json
{ "id": 2, "quantity": 10 }
```

**Коди відповіді:**
- `200 OK` — залишок оновлено, повертається товар з новим `stock`
- `400 Bad Request` — `id` або `quantity` відсутні/некоректні (quantity має бути цілим і `> 0`)
- `404 Not Found` — товару з таким `id` не існує

```json
{ "success": true, "data": { "id": 2, "name": "Resistance Bands Set", "price": "699.00", "sku": "GEAR-014", "stock": 28 } }
```

---

## Інші випадки

- Будь-який ресурс, відмінний від `products` → `404 Not Found`
- Метод, не підтримуваний для `products` (напр. `DELETE`, `PUT`) → `405 Method Not Allowed`
- Невідоме значення `action` у POST-запиті → `400 Bad Request`
