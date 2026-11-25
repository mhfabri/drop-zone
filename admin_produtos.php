<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $pdo = getPDO();

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
  <style>
    :root {
      --bg: #0f1210;
      --card: #181d1a;
      --line: #2a312c;
      --text: #ebf1ed;
      --muted: #a4b0a8;
      --green: #45ff00;
      --danger: #ff6b6b;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(180deg, #0b0d0c, #131914 45%, #0f1210);
      color: var(--text);
      padding: 24px;
    }

    .wrap {
      max-width: 1100px;
      margin: 0 auto;
      display: grid;
      gap: 20px;
    }

    .top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    .top a {
      color: #000;
      background: var(--green);
      padding: 10px 14px;
      text-decoration: none;
      font-weight: 700;
      border-radius: 8px;
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 18px;
    }

    h1, h2 {
      margin: 0 0 14px;
    }

    .grid {
      display: grid;
      gap: 12px;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    }

    label {
      display: grid;
      gap: 6px;
      font-weight: 700;
      color: var(--muted);
      font-size: 14px;
    }

    input, select, textarea {
      border: 1px solid var(--line);
      background: #0f1311;
      color: var(--text);
      border-radius: 8px;
      padding: 10px;
      font: inherit;
    }

    textarea {
      min-height: 88px;
      resize: vertical;
    }

    button {
      margin-top: 10px;
      border: 0;
      border-radius: 8px;
      padding: 12px 16px;
      font: inherit;
      font-weight: 700;
      cursor: pointer;
      background: var(--green);
      color: #000;
    }

    .ok {
      background: rgba(69, 255, 0, 0.12);
      border: 1px solid rgba(69, 255, 0, 0.45);
      padding: 10px;
      border-radius: 8px;
    }

    .errors {
      background: rgba(255, 107, 107, 0.1);
      border: 1px solid rgba(255, 107, 107, 0.4);
      padding: 10px;
      border-radius: 8px;
      color: #ffd4d4;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }

    th, td {
      text-align: left;
      border-bottom: 1px solid var(--line);
      padding: 10px 8px;
    }

    .status-on { color: var(--green); font-weight: 700; }
    .status-off { color: var(--danger); font-weight: 700; }

    @media (max-width: 700px) {
      body { padding: 14px; }
      table { display: block; overflow-x: auto; white-space: nowrap; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1>Painel SQL de Produtos</h1>
      <a href="index.php">Voltar para loja</a>
    </div>

    <section class="card">
      <h2>Inserir produto</h2>

      <?php if (isset($_GET['ok'])): ?>
        <p class="ok">Produto inserido com sucesso no banco.</p>
      <?php endif; ?>

      <?php if ($errors): ?>
        <div class="errors">
          <?php foreach ($errors as $error): ?>
            <div><?= h($error) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post">
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

        <button type="submit">Salvar no MySQL</button>
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
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= (int) $product['sku'] ?></td>
                <td><?= h($product['nome']) ?></td>
                <td><?= h($product['categoria']) ?></td>
                <td>R$ <?= number_format((float) $product['preco'], 2, ',', '.') ?></td>
                <td><?= (int) $product['estoque'] ?></td>
                <td class="<?= ((int) $product['status'] === 1) ? 'status-on' : 'status-off' ?>">
                  <?= ((int) $product['status'] === 1) ? 'Ativo' : 'Inativo' ?>
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
