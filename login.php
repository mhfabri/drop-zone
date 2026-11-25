<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | Drop Zone</title>

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Orbitron:wght@500;600;700;800;900&display=swap"
        rel="stylesheet">

    <!-- Seu CSS principal -->
    <link rel="stylesheet" href="login.css">

</head>

<body class="login-page">

    <main class="login-container">

        <!-- LADO DA IMAGEM -->
        <!-- LADO DA IMAGEM -->
    <section class="login-image">

        <!-- LOGO DROP ZONE -->
        
            

        <div class="login-image-overlay"></div>

        <div class="login-image-text">

        <div class="login-brand-tagline">

        <div class="login-logo">
            
            <img src="img/logo.jpg" alt="Drop Zone">
        </div> 

            <span>DROP ZONE • STREETWEAR</span>

        </div>

        <h1>
            FAÇA LOGIN<br>
            <b>E ENTRE PARA O NOSSO TIME</b>
        </h1>

        <p>
            Acesse sua conta e continue explorando
            o mundo Drop Zone.
        </p>

    </div>
    </section>

        <!-- LADO DO LOGIN -->
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


                <form action="#" method="POST" class="login-form">

                    <div class="input-group">

                        <label for="usuario">
                            USUÁRIO
                        </label>

                        <input
                            type="text"
                            id="usuario"
                            name="usuario"
                            placeholder="Digite seu usuário"
                            autocomplete="username"
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

                        <label class="remember">

                            <input
                                type="checkbox"
                                name="lembrar">

                            <span>
                                Lembrar de mim
                            </span>

                        </label>

                        <a href="#">
                            Esqueceu a senha?
                        </a>

                    </div>


                    <button type="submit" class="login-button">
                        ENTRAR
                    </button>

                </form>


                <div class="login-divider">
                    <span>OU</span>
                </div>


                <div class="register">

                    <p>
                        Ainda não possui uma conta?
                    </p>

                    <a href="#">
                        CRIAR CONTA
                    </a>

                </div>


                <div class="login-footer">

                    <span>DROP ZONE</span>

                    <small>
                        STREETWEAR • YOUR STYLE • YOUR DROP
                    </small>

                </div>

            </div>

        </section>

    </main>

</body>

</html>