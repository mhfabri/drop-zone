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

    $snapshot = [];

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
            continue;
        }

        $snapshot[(int) $row['sku']] = [
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
           'INSERT INTO compra (fk_cliente_id_cliente, nome_cliente, fk_produto_sku, data, valor, quantidade)
            VALUES (:cliente, :nome_cliente, :sku, :data, :valor, :quantidade)'
    );

    $today = (new DateTimeImmutable('today'))->format('Y-m-d');

    foreach ($items as $sku => $qtyRequested) {
        $updateStmt->execute([
            ':sku' => $sku,
            ':qty' => $qtyRequested,
            ':qty_check' => $qtyRequested,
        ]);

        if ($updateStmt->rowCount() !== 1) {
            throw new RuntimeException('Nao foi possivel atualizar o estoque.');
        }

        if (!isset($snapshot[(int) $sku])) {
            throw new RuntimeException('Snapshot de produto nao encontrado para salvar compra.');
        }

        $valorTotalItem = $snapshot[(int) $sku]['preco'] * $snapshot[(int) $sku]['qty'];

        $insertCompraStmt->execute([
            ':cliente' => $clienteId,
            ':nome_cliente' => $clienteNome,
            ':sku' => (int) $sku,
            ':data' => $today,
            ':valor' => $valorTotalItem,
            ':quantidade' => (int) $qtyRequested,
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
