<?php
declare(strict_types=1);

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'drop_zone';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Mantem compatibilidade com bancos antigos sem a coluna de permissao.
    $pdo->exec('ALTER TABLE cliente ADD COLUMN IF NOT EXISTS admin TINYINT(1) NOT NULL DEFAULT 0');
    $pdo->exec("ALTER TABLE compra ADD COLUMN IF NOT EXISTS nome_cliente VARCHAR(255) NOT NULL DEFAULT '' AFTER fk_cliente_id_cliente");
    $pdo->exec("ALTER TABLE compra ADD COLUMN IF NOT EXISTS tamanho VARCHAR(20) NULL AFTER fk_produto_sku");
    $pdo->exec("CREATE TABLE IF NOT EXISTS produto_tamanho (
        id_tamanho INT AUTO_INCREMENT PRIMARY KEY,
        fk_produto_sku INT NOT NULL,
        tamanho VARCHAR(20) NOT NULL,
        estoque INT NOT NULL DEFAULT 0,
        UNIQUE KEY uk_produto_tamanho (fk_produto_sku, tamanho),
        CONSTRAINT FK_produto_tamanho_produto FOREIGN KEY (fk_produto_sku)
            REFERENCES produto (sku) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    return $pdo;
}
