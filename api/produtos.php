<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

function normalizeType(string $category): string
{
    $value = mb_strtolower(trim($category));

    if (str_contains($value, 'tenis') || str_contains($value, 'tênis') || str_contains($value, 'sneaker')) {
        return 'tenis';
    }

    return 'roupas';
}

function normalizeCategory(string $category): string
{
    $type = normalizeType($category);

    if ($type === 'tenis') {
        return 'Tenis';
    }

    if ($type === 'roupas') {
        return 'Roupas';
    }

    return ucfirst($category);
}

try {
    $pdo = getPDO();

    $stmt = $pdo->query(
        'SELECT sku, nome, preco, categoria, imagem, status, estoque
         FROM produto
         WHERE status = 1
         ORDER BY sku DESC'
    );

    $products = [];

    foreach ($stmt as $row) {
        $type = normalizeType((string) $row['categoria']);

        $products[] = [
            'id' => (int) $row['sku'],
            'name' => (string) $row['nome'],
            'category' => normalizeCategory((string) $row['categoria']),
            'type' => $type,
            'price' => (float) $row['preco'],
            'badge' => '',
            'image' => (string) $row['imagem'],
            'stock' => (int) $row['estoque'],
        ];
    }

    echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar produtos.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
