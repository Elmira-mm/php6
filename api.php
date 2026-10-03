<?php
declare(strict_types=1);

/**
 * api.php — єдина точка входу API.
 * Контракт описаний окремо в API.md.
 *
 * Маршрутизація: resource (яку сутність запитують) + метод HTTP +,
 * для POST, необов'язковий action (доменна дія, відмінна від звичайного CRUD).
 *
 * Узгоджений формат відповіді для БУДЬ-якого сценарію:
 *   успіх:  {"success": true,  "data": ...}
 *   помилка:{"success": false, "error": "..."}
 */

ob_start(); // страховка від стороннього виводу PHP перед json_encode()

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/products.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * Єдине місце виводу відповіді — гарантує однаковий формат і коректний
 * HTTP-код для будь-якої гілки маршрутизації.
 */
function respond(int $status, array $body): void
{
    ob_end_clean();
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $resource = $_GET['resource'] ?? null;
    $id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    $action = $_GET['action'] ?? null;

    // Крок 3. Маршрутизація за ресурсом.
    if ($resource !== 'products') {
        respond(404, ['success' => false, 'error' => 'Невідомий ресурс. Підтримується лише resource=products.']);
    }

    // Крок 4. GET — список або один запис.
    if ($method === 'GET') {
        if (!isset($_GET['id'])) {
            respond(200, ['success' => true, 'data' => getAllProducts($pdo)]);
        }

        if ($id === false) {
            respond(400, ['success' => false, 'error' => 'Параметр id має бути цілим числом.']);
        }

        $product = getProductById($pdo, $id);

        if ($product === null) {
            respond(404, ['success' => false, 'error' => "Товар з id {$id} не знайдено."]);
        }

        respond(200, ['success' => true, 'data' => $product]);
    }

    // Крок 5/6. POST — створення запису, або доменна дія restock.
    if ($method === 'POST') {
        $input = parseRequestInput();

        if ($action === null) {
            $result = createProduct($pdo, $input);
            respond($result['status'], $result['body']);
        }

        if ($action === 'restock') {
            $result = restockProduct($pdo, $input);
            respond($result['status'], $result['body']);
        }

        respond(400, ['success' => false, 'error' => "Невідома дія action={$action}."]);
    }

    // Крок 7. Метод, що не підтримується для цього ресурсу.
    respond(405, ['success' => false, 'error' => 'Метод не підтримується для ресурсу products.']);
} catch (Throwable $e) {
    respond(500, ['success' => false, 'error' => 'Внутрішня помилка сервера.']);
}
