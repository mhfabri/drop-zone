<?php
$base_url = '/drop-zone/';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Drop Zone — tênis, roupas e acessórios com estilo urbano.">
  <title>Drop Zone | Streetwear & Sneakers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Orbitron:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header class="header">
    <a class="brand" href="#inicio" aria-label="Drop Zone">
      <img src="img/logo.jpg" alt="Drop Zone">
    </a>

    <nav class="nav" id="nav">
      <a href="#inicio">Início</a>
      <a href="#tenis">Tênis</a>
      <a href="#roupas">Roupas</a>
      <a href="#destaques">Destaques</a>
      <a href="#sobre">Sobre</a>
    </nav>

    <div class="header-actions">
      <button class="icon-btn" id="searchBtn" aria-label="Pesquisar">⌕</button>
      <button class="icon-btn cart-btn" id="cartBtn" aria-label="Carrinho">🛒<span id="cartCount">0</span></button>
      <button class="menu-btn" id="menuBtn" aria-label="Abrir menu">☰</button>
    </div>
  </header>