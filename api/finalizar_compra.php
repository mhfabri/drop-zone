<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

$pdo = null;

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput ?: '', true);

if (!is_array($payload) || !isset($payload['items']) || !is_array($payload['items']) || $payload['items'] === []) {
    http_response_code(422);
    echo json_encode([
        'error' => 'Carrinho invalido para finalizacao.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$items = [];

foreach ($payload['items'] as $item) {
    $id = isset($item['id']) ? (int) $item['id'] : 0;
    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;

    if ($id <= 0 || $qty <= 0) {
        http_response_code(422);
        echo json_encode([
            'error' => 'Item de carrinho invalido.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (!isset($items[$id])) {
        $items[$id] = 0;
    }

    $items[$id] += $qty;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    $selectStmt = $pdo->prepare(
        'SELECT sku, nome, estoque, status
         FROM produto
         WHERE sku = :sku
         FOR UPDATE'
    );

    $insufficient = [];

    foreach ($items as $sku => $qtyRequested) {
        $selectStmt->execute([':sku' => $sku]);
        $row = $selectStmt->fetch();

        if (!$row) {
            $insufficient[] = [
                'id' => (int) $sku,
                'name' => 'Produto nao encontrado',
                'requested' => (int) $qtyRequested,
                'available' => 0,
            ];
            continue;
        }

        $available = (int) $row['estoque'];
        $isActive = ((int) $row['status']) === 1;

        if (!$isActive || $available < $qtyRequested) {
            $insufficient[] = [
                'id' => (int) $row['sku'],
                'name' => (string) $row['nome'],
                'requested' => (int) $qtyRequested,
                'available' => max(0, $available),
            ];
        }
    }

    if ($insufficient !== []) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'error' => 'Estoque insuficiente para um ou mais produtos.',
            'items' => $insufficient,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $updateStmt = $pdo->prepare(
        'UPDATE produto
                 SET estoque = estoque - :qty
         WHERE sku = :sku
                     AND estoque >= :qty_check'
    );

    foreach ($items as $sku => $qtyRequested) {
        $updateStmt->execute([
            ':sku' => $sku,
            ':qty' => $qtyRequested,
            ':qty_check' => $qtyRequested,
        ]);

        if ($updateStmt->rowCount() !== 1) {
            throw new RuntimeException('Nao foi possivel atualizar o estoque.');
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido finalizado e estoque atualizado.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('[finalizar_compra] ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'Falha ao finalizar pedido. Verifique conexao com banco e estrutura da tabela produto.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
