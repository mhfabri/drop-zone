<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/includes/db.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$error = null;
$success = null;

if (isset($_GET['cadastro']) && $_GET['cadastro'] === 'ok') {
    $success = 'Cadastro realizado com sucesso. Agora faca login.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $error = 'Preencha e-mail e senha.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Informe um e-mail valido.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare('SELECT id_cliente, nome, email, senha, admin FROM cliente WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);

            $cliente = $stmt->fetch();

            if (!$cliente) {
                $error = 'Credenciais invalidas.';
            } else {
                $hash = (string) $cliente['senha'];
                $senhaValida = password_verify($senha, $hash) || hash_equals($hash, $senha);

                if ($senhaValida) {
                    $isAdmin = ((int) ($cliente['admin'] ?? 0)) === 1;

                    $_SESSION['auth'] = [
                        'type' => $isAdmin ? 'admin' : 'cliente',
                        'id' => (int) $cliente['id_cliente'],
                        'email' => (string) $cliente['email'],
                        'nome' => (string) $cliente['nome'],
                    ];

                    if ($isAdmin) {
                        header('Location: admin_produtos.php');
                        exit;
                    }

                    header('Location: index.php');
                    exit;
                }

                $error = 'Credenciais invalidas.';
            }
        } catch (Throwable $e) {
            $error = 'Nao foi possivel validar o login agora. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Drop Zone</title>

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
            FACA LOGIN<br>
            <b>E ENTRE PARA O NOSSO TIME</b>
        </h1>

        <p>
            Acesse sua conta e continue explorando
            o mundo Drop Zone.
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
                        BEM-VINDO
                    </h2>

                    <p>
                        Entre na sua conta e continue explorando.
                    </p>

                </div>

                <?php if ($error !== null): ?>
                    <div class="auth-message auth-error"><?= h($error) ?></div>
                <?php endif; ?>

                <?php if ($success !== null): ?>
                    <div class="auth-message auth-success"><?= h($success) ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="login-form">

                    <div class="input-group">

                        <label for="email">
                            E-MAIL
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Digite seu e-mail"
                            autocomplete="username"
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
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                            required>

                    </div>


                    <div class="login-options">
                        <a href="cadastro.php">
                            Criar uma conta
                        </a>
                    </div>


                    <button type="submit" class="login-button">
                        ENTRAR
                    </button>

                </form>

            </div>

        </section>

    </main>

</body>

</html>