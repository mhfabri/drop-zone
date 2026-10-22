/* logico_drop_zone: */

CREATE TABLE usuario (
    id_usuario int PRIMARY KEY,
    nome varchar(255),
    email varchar(255),
    cpf char(11),
    telefone char(11),
    senha varchar(100),
    admin boolean
);

CREATE TABLE cliente (
    id_cliente int PRIMARY KEY,
    nome varchar(255),
    email varchar(255),
    senha varchar(100)
);

CREATE TABLE produto (
    preco decimal(10,2),
    descricao varchar(250),
    tamanho varchar(100),
    categoria varchar(100),
    estoque int,
    status boolean,
    imagem varchar(255),
    sku int PRIMARY KEY,
    nome varchar(255)
);

CREATE TABLE compra (
    fk_cliente_id_cliente int,
    fk_produto_sku int,
    id_compra int PRIMARY KEY,
    data date,
    valor decimal(10,2),
    quantidade int
);
 
ALTER TABLE compra ADD CONSTRAINT FK_compra_2
    FOREIGN KEY (fk_cliente_id_cliente)
    REFERENCES cliente (id_cliente)
    ON DELETE RESTRICT;
 
ALTER TABLE compra ADD CONSTRAINT FK_compra_3
    FOREIGN KEY (fk_produto_sku)
    REFERENCES produto (sku)
    ON DELETE RESTRICT;