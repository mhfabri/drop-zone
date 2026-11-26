<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

session_start();

require_once __DIR__ . '/../includes/db.php';

$pdo = null;

$auth = $_SESSION['auth'] ?? null;
$clienteId = is_array($auth) ? (int) ($auth['id'] ?? 0) : 0;
$clienteNome = is_array($auth) ? trim((string) ($auth['nome'] ?? '')) : '';

if ($clienteId <= 0) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Voce precisa estar logado para finalizar a compra.',
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

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
$itemsSize = [];

foreach ($payload['items'] as $item) {
    $id = isset($item['id']) ? (int) $item['id'] : 0;
    $qty = isset($item['qty']) ? (int) $item['qty'] : 0;
    $size = trim((string) ($item['size'] ?? ''));

    if ($id <= 0 || $qty <= 0 || $size === '') {
        http_response_code(422);
        echo json_encode([
            'error' => 'Item de carrinho ou tamanho invalido.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $key = $id . '|' . $size;

    if (!isset($items[$key])) {
        $items[$key] = 0;
    }

    $items[$key] += $qty;
    $itemsSize[$key] = $size;
}

try {
    $pdo = getPDO();

    if ($clienteNome === '') {
        $clienteStmt = $pdo->prepare('SELECT nome FROM cliente WHERE id_cliente = :id LIMIT 1');
        $clienteStmt->execute([':id' => $clienteId]);
        $cliente = $clienteStmt->fetch();

        if (!$cliente) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Cliente nao encontrado para registrar a compra.',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $clienteNome = trim((string) $cliente['nome']);
    }

    $pdo->beginTransaction();

    $selectStmt = $pdo->prepare(
        'SELECT sku, nome, preco, estoque, status
         FROM produto
         WHERE sku = :sku
         FOR UPDATE'
    );

    $selectSizeStmt = $pdo->prepare(
        'SELECT estoque
         FROM produto_tamanho
         WHERE fk_produto_sku = :sku AND tamanho = :tamanho
         FOR UPDATE'
    );

    $updateSizeStmt = $pdo->prepare(
        'UPDATE produto_tamanho
         SET estoque = estoque - :qty
         WHERE fk_produto_sku = :sku AND tamanho = :tamanho AND estoque >= :qty_check'
    );

    $snapshot = [];

    $insufficient = [];

    foreach ($items as $itemKey => $qtyRequested) {
        [$sku, $size] = array_pad(explode('|', (string) $itemKey, 2), 2, '');
        $sku = (int) $sku;

        $selectStmt->execute([':sku' => $sku]);
        $row = $selectStmt->fetch();

        if (!$row) {
            $insufficient[] = [
                'id' => $sku,
                'name' => 'Produto nao encontrado',
                'requested' => (int) $qtyRequested,
                'available' => 0,
            ];
            continue;
        }

        $selectSizeStmt->execute([':sku' => $sku, ':tamanho' => $size]);
        $sizeRow = $selectSizeStmt->fetch();

        $available = (int) ($row['estoque'] ?? 0);
        $availableSize = $sizeRow ? (int) $sizeRow['estoque'] : 0;
        $isActive = ((int) $row['status']) === 1;

        if (!$isActive || $available < $qtyRequested || $availableSize < $qtyRequested) {
            $insufficient[] = [
                'id' => $sku,
                'name' => (string) $row['nome'],
                'requested' => (int) $qtyRequested,
                'available' => min(max(0, $available), max(0, $availableSize)),
            ];
            continue;
        }

        $snapshot[$itemKey] = [
            'sku' => $sku,
            'size' => $size,
            'preco' => (float) $row['preco'],
            'qty' => (int) $qtyRequested,
        ];
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

    $insertCompraStmt = $pdo->prepare(
           'INSERT INTO compra (fk_cliente_id_cliente, nome_cliente, fk_produto_sku, tamanho, data, valor, quantidade)
            VALUES (:cliente, :nome_cliente, :sku, :tamanho, :data, :valor, :quantidade)'
    );

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    foreach ($items as $skuSize => $qtyRequested) {
        [$sku, $size] = array_pad(explode('|', (string) $skuSize, 2), 2, '');
        $sku = (int) $sku;

        $updateStmt->execute([
            ':sku' => $sku,
            ':qty' => $qtyRequested,
            ':qty_check' => $qtyRequested,
        ]);

        if ($updateStmt->rowCount() !== 1) {
            throw new RuntimeException('Nao foi possivel atualizar o estoque.');
        }

        $updateSizeStmt->execute([
            ':sku' => $sku,
            ':tamanho' => $size,
            ':qty' => $qtyRequested,
            ':qty_check' => $qtyRequested,
        ]);

        if ($updateSizeStmt->rowCount() !== 1) {
            throw new RuntimeException('Nao foi possivel atualizar o estoque do tamanho.');
        }

        if (!isset($snapshot[(string) $skuSize])) {
            throw new RuntimeException('Snapshot de produto nao encontrado para salvar compra.');
        }

        $valorTotalItem = $snapshot[(string) $skuSize]['preco'] * $snapshot[(string) $skuSize]['qty'];

        $insertCompraStmt->execute([
            ':cliente' => $clienteId,
            ':nome_cliente' => $clienteNome,
            ':sku' => $sku,
            ':tamanho' => $size,
            ':data' => $today,
            ':valor' => $valorTotalItem,
            ':quantidade' => $qtyRequested,
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido finalizado, estoque atualizado e compra registrada.',
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
