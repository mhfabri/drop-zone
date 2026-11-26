<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['auth']) || ($_SESSION['auth']['type'] ?? '') !== 'admin') {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/includes/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$notice = null;

if (isset($_GET['ok'])) {
  $noticeMap = [
    '1' => 'Produto inserido com sucesso no banco.',
    'updated' => 'Estoque atualizado com sucesso.',
    'deleted' => 'Produto excluido com sucesso.',
    'size_updated' => 'Tamanho e estoque atualizados com sucesso.',
    'size_deleted' => 'Tamanho removido do catalogo.',
    'status_updated' => 'Status do produto atualizado.',
  ];

  $notice = $noticeMap[(string) $_GET['ok']] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = (string) ($_POST['action'] ?? 'create');

  try {
    $pdo = getPDO();
  } catch (Throwable $e) {
    $errors[] = 'Nao foi possivel conectar ao banco de dados.';
    $action = '';
  }

  if ($action === 'create' && !$errors) {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $preco = trim((string) ($_POST['preco'] ?? ''));
    $categoria = trim((string) ($_POST['categoria'] ?? ''));
    $descricao = trim((string) ($_POST['descricao'] ?? ''));
    $tamanho = trim((string) ($_POST['tamanho'] ?? ''));
    $estoque = '0';
    $imagem = trim((string) ($_POST['imagem'] ?? ''));
    $status = 0;

    if ($nome === '') {
      $errors[] = 'Informe o nome do produto.';
    }

    if (!is_numeric($preco) || (float) $preco <= 0) {
      $errors[] = 'Informe um preco valido.';
    }

    if ($categoria !== 'tenis' && $categoria !== 'roupas') {
      $errors[] = 'Escolha uma categoria valida.';
    }

    if ($imagem === '') {
      $errors[] = 'Informe a URL da imagem.';
    }

    if ($status !== 0 && $status !== 1) {
      $errors[] = 'Status invalido.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare(
          'INSERT INTO produto (nome, preco, descricao, tamanho, categoria, estoque, status, imagem)
           VALUES (:nome, :preco, :descricao, :tamanho, :categoria, :estoque, :status, :imagem)'
        );

        $stmt->execute([
          ':nome' => $nome,
          ':preco' => (float) $preco,
          ':descricao' => $descricao,
          ':tamanho' => $tamanho,
          ':categoria' => $categoria,
          ':estoque' => (int) $estoque,
          ':status' => $status,
          ':imagem' => $imagem,
        ]);

        $sku = (int) $pdo->lastInsertId();
        $sizes = array_values(array_filter(array_map('trim', explode(',', $tamanho))));

        if ($sizes !== []) {
          $sizeStmt = $pdo->prepare(
            'INSERT INTO produto_tamanho (fk_produto_sku, tamanho, estoque)
             VALUES (:sku, :tamanho, :estoque)
             ON DUPLICATE KEY UPDATE estoque = VALUES(estoque)'
          );

          foreach ($sizes as $sizeName) {
            $sizeStmt->execute([
              ':sku' => $sku,
              ':tamanho' => mb_substr($sizeName, 0, 20),
              ':estoque' => 0,
            ]);
          }
        }

        header('Location: admin_produtos.php?ok=1');
        exit;
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel inserir o produto. Verifique a conexao e a estrutura do banco.';
      }
    }
  } elseif ($action === 'delete' && !$errors) {
    $sku = trim((string) ($_POST['sku'] ?? ''));

    if (!ctype_digit($sku) || (int) $sku <= 0) {
      $errors[] = 'SKU invalido para exclusao.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare('DELETE FROM produto WHERE sku = :sku');
        $stmt->execute([':sku' => (int) $sku]);

        if ($stmt->rowCount() > 0) {
          header('Location: admin_produtos.php?ok=deleted');
          exit;
        }

        $errors[] = 'Produto nao encontrado para exclusao.';
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel excluir o produto. Ele pode estar vinculado a compras.';
      }
    }
  } elseif ($action === 'toggle_status' && !$errors) {
    $sku = trim((string) ($_POST['sku'] ?? ''));

    if (!ctype_digit($sku) || (int) $sku <= 0) {
      $errors[] = 'SKU invalido para alterar o status.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare(
          'UPDATE produto
           SET status = CASE
             WHEN estoque <= 0 THEN 0
             ELSE 1 - status
           END
           WHERE sku = :sku'
        );
        $stmt->execute([':sku' => (int) $sku]);

        if ($stmt->rowCount() > 0) {
          header('Location: admin_produtos.php?ok=status_updated');
          exit;
        }

        $errors[] = 'Produto nao encontrado para alterar status.';
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel alterar o status do produto.';
      }
    }
  } elseif ($action === 'update_size' && !$errors) {
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $sizeId = trim((string) ($_POST['size_id'] ?? ''));
    $sizeStock = trim((string) ($_POST['size_stock'] ?? ''));

    if (!ctype_digit($sku) || (int) $sku <= 0 || !ctype_digit($sizeId) || (int) $sizeId <= 0) {
      $errors[] = 'Dados invalidos para atualizar o tamanho.';
    }

    if (!ctype_digit($sizeStock) || (int) $sizeStock < 0) {
      $errors[] = 'Informe um estoque valido para o tamanho.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare(
          'UPDATE produto_tamanho
           SET estoque = :estoque
           WHERE id_tamanho = :id_tamanho AND fk_produto_sku = :sku'
        );
        $stmt->execute([
          ':estoque' => (int) $sizeStock,
          ':id_tamanho' => (int) $sizeId,
          ':sku' => (int) $sku,
        ]);

        $pdo->exec("UPDATE produto SET estoque = (SELECT COALESCE(SUM(estoque), 0) FROM produto_tamanho WHERE fk_produto_sku = $sku) WHERE sku = $sku");
        $pdo->exec("UPDATE produto SET status = CASE WHEN estoque > 0 THEN status ELSE 0 END WHERE sku = $sku");

        header('Location: admin_produtos.php?ok=size_updated');
        exit;
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel atualizar o estoque desse tamanho.';
      }
    }
  } elseif ($action === 'delete_size' && !$errors) {
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $sizeId = trim((string) ($_POST['size_id'] ?? ''));

    if (!ctype_digit($sku) || (int) $sku <= 0 || !ctype_digit($sizeId) || (int) $sizeId <= 0) {
      $errors[] = 'Dados invalidos para remover o tamanho.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare(
          'DELETE FROM produto_tamanho
           WHERE id_tamanho = :id_tamanho AND fk_produto_sku = :sku'
        );
        $stmt->execute([
          ':id_tamanho' => (int) $sizeId,
          ':sku' => (int) $sku,
        ]);

        $pdo->exec("UPDATE produto SET estoque = (SELECT COALESCE(SUM(estoque), 0) FROM produto_tamanho WHERE fk_produto_sku = $sku) WHERE sku = $sku");
        $pdo->exec("UPDATE produto SET status = CASE WHEN estoque > 0 THEN status ELSE 0 END WHERE sku = $sku");

        header('Location: admin_produtos.php?ok=size_deleted');
        exit;
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel remover o tamanho.';
      }
    }
  } elseif ($action === 'add_size' && !$errors) {
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $sizeName = mb_substr(trim((string) ($_POST['size_name'] ?? '')), 0, 20);
    $sizeStock = trim((string) ($_POST['size_stock'] ?? ''));

    if (!ctype_digit($sku) || (int) $sku <= 0) {
      $errors[] = 'SKU invalido para adicionar tamanho.';
    }

    if ($sizeName === '') {
      $errors[] = 'Informe o nome do tamanho.';
    }

    if (!ctype_digit($sizeStock) || (int) $sizeStock < 0) {
      $errors[] = 'Informe um estoque valido para o tamanho.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare(
          'INSERT INTO produto_tamanho (fk_produto_sku, tamanho, estoque)
           VALUES (:sku, :tamanho, :estoque)
           ON DUPLICATE KEY UPDATE estoque = :estoque_update'
        );
        $stmt->execute([
          ':sku' => (int) $sku,
          ':tamanho' => $sizeName,
          ':estoque' => (int) $sizeStock,
          ':estoque_update' => (int) $sizeStock,
        ]);

        $pdo->exec("UPDATE produto SET estoque = (SELECT COALESCE(SUM(estoque), 0) FROM produto_tamanho WHERE fk_produto_sku = $sku) WHERE sku = $sku");
        $pdo->exec("UPDATE produto SET status = CASE WHEN estoque > 0 THEN status ELSE 0 END WHERE sku = $sku");

        header('Location: admin_produtos.php?ok=size_updated');
        exit;
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel adicionar ou atualizar o tamanho.';
      }
    }
   }
}

$products = [];
$listError = null;

try {
    $pdo = getPDO();
    $products = $pdo->query('SELECT sku, nome, categoria, preco, estoque, status FROM produto ORDER BY sku DESC')->fetchAll();

    $sizeStmt = $pdo->prepare(
        'SELECT id_tamanho, tamanho, estoque
         FROM produto_tamanho
         WHERE fk_produto_sku = :sku
         ORDER BY tamanho'
    );

    foreach ($products as &$product) {
        $sizeStmt->execute([':sku' => (int) $product['sku']]);
        $product['tamanhos'] = $sizeStmt->fetchAll();
    }
    unset($product);
} catch (Throwable $e) {
    $listError = 'Nao foi possivel listar os produtos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Produtos | Drop Zone</title>
  <link rel="stylesheet" href="css/admin.css">
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1>Painel de Produtos</h1>
      <div style="display:flex; gap: 10px;">
        <a href="index.php">Voltar para loja</a>
        <a href="logout.php">Sair</a>
      </div>
    </div>

    <section class="card">
      <h2>Inserir produto</h2>

      <?php if ($notice !== null): ?>
        <p class="ok"><?= h($notice) ?></p>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="errors">
          <?php foreach ($errors as $error): ?>
            <div><?= h($error) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="action" value="create">
        <div class="grid">
          <label>
            Nome
            <input type="text" name="nome" required value="<?= h($_POST['nome'] ?? '') ?>">
          </label>

          <label>
            Preco
            <input type="number" step="0.01" min="0.01" name="preco" required value="<?= h($_POST['preco'] ?? '') ?>">
          </label>

          <label>
            Categoria
            <select name="categoria" required>
              <option value="">Selecione</option>
              <option value="tenis" <?= (($_POST['categoria'] ?? '') === 'tenis') ? 'selected' : '' ?>>Tenis</option>
              <option value="roupas" <?= (($_POST['categoria'] ?? '') === 'roupas') ? 'selected' : '' ?>>Roupas</option>
            </select>
          </label>

          <label>
            Tamanhos disponíveis
            <input type="text" name="tamanho" value="<?= h($_POST['tamanho'] ?? '') ?>" placeholder="Ex: P, M, G ou 38, 39, 40">
          </label>

          <label style="grid-column: 1 / -1;">
            URL da imagem
            <input type="url" name="imagem" required value="<?= h($_POST['imagem'] ?? '') ?>">
          </label>

          <label style="grid-column: 1 / -1;">
            Descricao
            <textarea name="descricao"><?= h($_POST['descricao'] ?? '') ?></textarea>
          </label>
        </div>

        <button type="submit">Salvar</button>
      </form>
    </section>

    <section class="card">
      <h2>Produtos cadastrados</h2>

      <?php if ($listError): ?>
        <p class="errors"><?= h($listError) ?></p>
      <?php elseif (!$products): ?>
        <p>Nenhum produto cadastrado ainda.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>SKU</th>
              <th>Nome</th>
              <th>Categoria</th>
              <th>Preco</th>
              <th>Tamanhos</th>
              <th>Status</th>
              <th>Acoes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= (int) $product['sku'] ?></td>
                <td><?= h($product['nome']) ?></td>
                <td><?= h($product['categoria']) ?></td>
                <td data-label="Preco">R$ <?= number_format((float) $product['preco'], 2, ',', '.') ?></td>
                <td data-label="Tamanhos">
                  <div class="size-manager">
                    <?php foreach (($product['tamanhos'] ?? []) as $size): ?>
                      <div class="size-row">
                        <strong><?= h($size['tamanho']) ?></strong>
                        <form method="post" class="inline-form">
                          <input type="hidden" name="action" value="update_size">
                          <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                          <input type="hidden" name="size_id" value="<?= (int) $size['id_tamanho'] ?>">
                          <input type="number" name="size_stock" min="0" value="<?= (int) $size['estoque'] ?>" required>
                          <button type="submit" class="btn-small">Atualizar</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Remover este tamanho do catalogo?');">
                          <input type="hidden" name="action" value="delete_size">
                          <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                          <input type="hidden" name="size_id" value="<?= (int) $size['id_tamanho'] ?>">
                          <button type="submit" class="btn-danger">Excluir</button>
                        </form>
                      </div>
                    <?php endforeach; ?>

                    <form method="post" class="size-add inline-form">
                      <input type="hidden" name="action" value="add_size">
                      <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                      <input type="text" name="size_name" maxlength="20" placeholder="Novo tamanho" required>
                      <input type="number" name="size_stock" min="0" value="0" required>
                      <button type="submit" class="btn-small">Adicionar</button>
                    </form>
                  </div>
                </td>
                <td class="<?= ((int) $product['status'] === 1) ? 'status-on' : 'status-off' ?>">
                  <form method="post">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                    <button
                      type="submit"
                      class="<?= ((int) $product['status'] === 1) ? 'btn-status-off' : 'btn-status-on' ?>"
                      <?= ((int) $product['estoque'] <= 0) ? 'disabled title="Adicione estoque antes de ativar"' : '' ?>
                    >
                      <?= ((int) $product['status'] === 1) ? 'Desativar' : 'Ativar' ?>
                    </button>
                  </form>
                  <small><?= ((int) $product['estoque'] <= 0 || (int) $product['estoque'] == null) ? 'Sem estoque' : (((int) $product['status'] === 1) ? 'Ativo' : 'Inativo') ?></small>
                </td>
                <td>
                  <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir este produto inteiro?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                    <button type="submit" class="btn-danger">Excluir produto</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>
