<?php
declare(strict_types=1);

function getAllProducts(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();
}

function getProductById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

function validateCreateInput(array $input): array
{
    $errors = [];

    $name  = trim((string) ($input['name'] ?? ''));
    $price = trim((string) ($input['price'] ?? ''));
    $sku   = trim((string) ($input['sku'] ?? ''));
    $stock = trim((string) ($input['stock'] ?? ''));

    if ($name === '') {
        $errors['name'] = 'Поле name обов\'язкове.';
    }

    if ($price === '' || !is_numeric($price) || (float) $price <= 0) {
        $errors['price'] = 'Поле price має бути числом більшим за 0.';
    }

    if ($sku === '' || !preg_match('/^[A-Za-z]+-\d+$/', $sku)) {
        $errors['sku'] = 'Поле sku має бути формату ABC-123.';
    }

    if ($stock === '' || filter_var($stock, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errors['stock'] = 'Поле stock має бути цілим невід\'ємним числом.';
    }

    return $errors;
}

function createProduct(PDO $pdo, array $input): array
{
    $errors = validateCreateInput($input);

    if (!empty($errors)) {
        return [
            'status' => 400,
            'body' => ['success' => false, 'error' => 'Некоректні дані.', 'fields' => $errors],
        ];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO products (name, price, sku, stock) VALUES (:name, :price, :sku, :stock)'
    );

    try {
        $stmt->execute([
            ':name'  => trim((string) $input['name']),
            ':price' => (float) $input['price'],
            ':sku'   => strtoupper(trim((string) $input['sku'])),
            ':stock' => (int) $input['stock'],
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return [
                'status' => 409,
                'body' => ['success' => false, 'error' => 'Товар з таким SKU вже існує.'],
            ];
        }

        throw $e;
    }

    $newId = (int) $pdo->lastInsertId();
    $product = getProductById($pdo, $newId);

    return [
        'status' => 201,
        'body' => ['success' => true, 'data' => $product],
    ];
}

function restockProduct(PDO $pdo, array $input): array
{
    $id = $input['id'] ?? null;
    $quantity = $input['quantity'] ?? null;

    if ($id === null || filter_var($id, FILTER_VALIDATE_INT) === false) {
        return [
            'status' => 400,
            'body' => ['success' => false, 'error' => 'Поле id обов\'язкове і має бути цілим числом.'],
        ];
    }

    if ($quantity === null || filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        return [
            'status' => 400,
            'body' => ['success' => false, 'error' => 'Поле quantity має бути цілим числом більшим за 0.'],
        ];
    }

    $id = (int) $id;
    $quantity = (int) $quantity;

    $existing = getProductById($pdo, $id);

    if ($existing === null) {
        return [
            'status' => 404,
            'body' => ['success' => false, 'error' => "Товар з id {$id} не знайдено."],
        ];
    }

    $stmt = $pdo->prepare('UPDATE products SET stock = stock + :quantity WHERE id = :id');
    $stmt->execute([':quantity' => $quantity, ':id' => $id]);

    $updated = getProductById($pdo, $id);

    return [
        'status' => 200,
        'body' => ['success' => true, 'data' => $updated],
    ];
}
function parseRequestInput(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    return $_POST;
}
