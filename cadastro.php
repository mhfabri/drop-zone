<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim((string) ($_POST['nome'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmarSenha = (string) ($_POST['confirmar_senha'] ?? '');

    if ($nome === '' || $email === '' || $senha === '' || $confirmarSenha === '') {
        $error = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail valido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif (!hash_equals($senha, $confirmarSenha)) {
        $error = 'As senhas nao conferem.';
    } else {
        try {
            $pdo = getPDO();

            $check = $pdo->prepare('SELECT id_cliente FROM cliente WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);

            if ($check->fetch()) {
                $error = 'Ja existe uma conta cadastrada com este e-mail.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO cliente (nome, email, senha, admin) VALUES (:nome, :email, :senha, :admin)');
                $stmt->execute([
                    ':nome' => $nome,
                    ':email' => $email,
                    ':senha' => password_hash($senha, PASSWORD_DEFAULT),
                    ':admin' => 0,
                ]);

                header('Location: login.php?cadastro=ok');
                exit;
            }
        } catch (Throwable $e) {
            $error = 'Nao foi possivel concluir o cadastro agora. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro | Drop Zone</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Orbitron:wght@500;600;700;800;900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/login.css">

</head>

<body class="login-page">

    <main class="login-container">
    <section class="login-image">
        <div class="login-image-overlay"></div>

        <div class="login-image-text">

        <div class="login-brand-tagline">

        <div class="login-logo">
            <img src="img/logo.jpg" alt="Drop Zone">
        </div>

            <span>DROP ZONE • STREETWEAR</span>

        </div>

        <h1>
            CRIE SUA CONTA<br>
            <b>E FACA PARTE DA DROP ZONE</b>
        </h1>

        <p>
            Cadastre-se para acompanhar pedidos e
            finalizar compras de forma mais rapida.
        </p>

    </div>
    </section>

        <section class="login-content">

            <div class="login-box">

                <div class="login-header">

                    <span class="login-eyebrow">
                        DROP ZONE
                    </span>

                    <h2>
                        CADASTRO
                    </h2>

                    <p>
                        Crie sua conta para acessar a loja.
                    </p>

                </div>

                <?php if ($error !== null): ?>
                    <div class="auth-message auth-error"><?= h($error) ?></div>
                <?php endif; ?>

                <form action="cadastro.php" method="POST" class="login-form">

                    <div class="input-group">

                        <label for="nome">
                            NOME
                        </label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            placeholder="Digite seu nome"
                            value="<?= h($_POST['nome'] ?? '') ?>"
                            required>

                    </div>

                    <div class="input-group">

                        <label for="email">
                            E-MAIL
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Digite seu e-mail"
                            autocomplete="email"
                            value="<?= h($_POST['email'] ?? '') ?>"
                            required>

                    </div>

                    <div class="input-group">

                        <label for="senha">
                            SENHA
                        </label>

                        <input
                            type="password"
                            id="senha"
                            name="senha"
                            placeholder="Crie uma senha"
                            minlength="6"
                            required>

                    </div>

                    <div class="input-group">

                        <label for="confirmar_senha">
                            CONFIRMAR SENHA
                        </label>

                        <input
                            type="password"
                            id="confirmar_senha"
                            name="confirmar_senha"
                            placeholder="Repita sua senha"
                            minlength="6"
                            required>

                    </div>

                    <div class="login-options">
                        <a href="login.php">
                            Já tenho conta
                        </a>
                    </div>

                    <button type="submit" class="login-button">
                        CADASTRAR
                    </button>

                </form>

            </div>

        </section>

    </main>

</body>

</html>
