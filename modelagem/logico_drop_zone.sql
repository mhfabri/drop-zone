/* logico_drop_zone - script MySQL executavel */

CREATE DATABASE IF NOT EXISTS drop_zone
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE drop_zone;

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    cpf CHAR(11) NOT NULL UNIQUE,
    telefone CHAR(11) NOT NULL,
    senha VARCHAR(100) NOT NULL,
    admin TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(100) NOT NULL,
    admin TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS produto (
    sku INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    descricao VARCHAR(250) NULL,
    tamanho VARCHAR(100) NULL,
    categoria VARCHAR(100) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    imagem VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS compra (
    id_compra INT AUTO_INCREMENT PRIMARY KEY,
    fk_cliente_id_cliente INT NOT NULL,
    fk_produto_sku INT NOT NULL,
    data DATE NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    CONSTRAINT FK_compra_2 FOREIGN KEY (fk_cliente_id_cliente)
        REFERENCES cliente (id_cliente)
        ON DELETE RESTRICT,
    CONSTRAINT FK_compra_3 FOREIGN KEY (fk_produto_sku)
        REFERENCES produto (sku)
        ON DELETE RESTRICT
);