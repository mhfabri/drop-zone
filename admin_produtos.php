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
    $estoque = trim((string) ($_POST['estoque'] ?? ''));
    $imagem = trim((string) ($_POST['imagem'] ?? ''));
    $status = (int) ($_POST['status'] ?? 1);

    if ($nome === '') {
      $errors[] = 'Informe o nome do produto.';
    }

    if (!is_numeric($preco) || (float) $preco <= 0) {
      $errors[] = 'Informe um preco valido.';
    }

    if ($categoria !== 'tenis' && $categoria !== 'roupas') {
      $errors[] = 'Escolha uma categoria valida.';
    }

    if (!ctype_digit($estoque) || (int) $estoque < 0) {
      $errors[] = 'Informe um estoque valido.';
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

        header('Location: admin_produtos.php?ok=1');
        exit;
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel inserir o produto. Verifique a conexao e a estrutura do banco.';
      }
    }
  } elseif ($action === 'update_stock' && !$errors) {
    $sku = trim((string) ($_POST['sku'] ?? ''));
    $estoque = trim((string) ($_POST['estoque'] ?? ''));

    if (!ctype_digit($sku) || (int) $sku <= 0) {
      $errors[] = 'SKU invalido para atualizar estoque.';
    }

    if (!ctype_digit($estoque) || (int) $estoque < 0) {
      $errors[] = 'Informe um estoque valido.';
    }

    if (!$errors) {
      try {
        $stmt = $pdo->prepare('UPDATE produto SET estoque = :estoque WHERE sku = :sku');
        $stmt->execute([
          ':estoque' => (int) $estoque,
          ':sku' => (int) $sku,
        ]);

        if ($stmt->rowCount() > 0) {
          header('Location: admin_produtos.php?ok=updated');
          exit;
        }

        $errors[] = 'Produto nao encontrado para atualizar.';
      } catch (Throwable $e) {
        $errors[] = 'Nao foi possivel atualizar o estoque desse produto.';
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
    }
}

$products = [];
$listError = null;

try {
    $pdo = getPDO();
    $products = $pdo->query('SELECT sku, nome, categoria, preco, estoque, status FROM produto ORDER BY sku DESC')->fetchAll();
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
            Tamanho
            <input type="text" name="tamanho" value="<?= h($_POST['tamanho'] ?? '') ?>" placeholder="Ex: 40 ou M">
          </label>

          <label>
            Estoque
            <input type="number" min="0" name="estoque" required value="<?= h($_POST['estoque'] ?? '0') ?>">
          </label>

          <label>
            Status
            <select name="status" required>
              <option value="1" <?= (($_POST['status'] ?? '1') === '1') ? 'selected' : '' ?>>Ativo</option>
              <option value="0" <?= (($_POST['status'] ?? '1') === '0') ? 'selected' : '' ?>>Inativo</option>
            </select>
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
              <th>Estoque</th>
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
                <td>R$ <?= number_format((float) $product['preco'], 2, ',', '.') ?></td>
                <td>
                  <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                    <input type="number" name="estoque" min="0" value="<?= (int) $product['estoque'] ?>" required>
                    <button type="submit" class="btn-small">Atualizar</button>
                  </form>
                </td>
                <td class="<?= ((int) $product['status'] === 1) ? 'status-on' : 'status-off' ?>">
                  <?= ((int) $product['status'] === 1) ? 'Ativo' : 'Inativo' ?>
                </td>
                <td>
                  <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="sku" value="<?= (int) $product['sku'] ?>">
                    <button type="submit" class="btn-danger">Excluir</button>
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
