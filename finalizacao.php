<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finalizacao do Pedido | Drop Zone</title>
  <style>
    :root {
      --bg: #070807;
      --card: #121612;
      --line: rgba(185, 190, 196, 0.22);
      --text: #f2f5f2;
      --muted: #95a095;
      --green: #45ff00;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: radial-gradient(circle at 20% 10%, #1a231a, #070807 55%);
      color: var(--text);
      min-height: 100vh;
      padding: 24px;
      display: grid;
      place-items: center;
    }

    .box {
      width: min(760px, 100%);
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 22px;
    }

    h1 {
      margin: 0 0 8px;
      font-size: 28px;
    }

    .hint {
      color: var(--muted);
      margin: 0 0 18px;
    }

    .items {
      display: grid;
      gap: 10px;
      margin-bottom: 14px;
    }

    .item {
      display: grid;
      grid-template-columns: 72px 1fr auto;
      gap: 12px;
      align-items: center;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 8px;
      background: #0b0d0c;
    }

    .item img {
      width: 72px;
      height: 72px;
      border-radius: 8px;
      object-fit: cover;
    }

    .item h3 {
      margin: 0 0 4px;
      font-size: 15px;
    }

    .item p {
      margin: 0;
      color: var(--muted);
      font-size: 13px;
    }

    .price {
      font-weight: 700;
      white-space: nowrap;
    }

    .total {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--line);
      padding-top: 12px;
      margin-top: 6px;
      font-size: 18px;
      font-weight: 700;
    }

    .actions {
      margin-top: 18px;
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .btn {
      border: 0;
      border-radius: 8px;
      padding: 12px 16px;
      font: inherit;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: .2s;
    }

    .btn-primary {
      background: var(--green);
      color: #000;
    }

    .btn-ghost {
      background: #0d120e;
      color: var(--text);
      border: 1px solid var(--line);
    }

    .empty {
      border: 1px dashed var(--line);
      border-radius: 10px;
      padding: 14px;
      color: var(--muted);
    }

    .feedback {
      margin: 10px 0 4px;
      border: 1px solid rgba(255, 107, 107, 0.45);
      background: rgba(255, 107, 107, 0.12);
      color: #ffd8d8;
      border-radius: 10px;
      padding: 10px;
      display: none;
    }

    .feedback.show {
      display: block;
    }
  </style>
</head>
<body>
  <section class="box">
    <h1>Finalizacao do pedido</h1>
    <p class="hint">Confira os itens antes de concluir com o atendimento.</p>

    <div id="checkoutItems" class="items"></div>

    <div class="total">
      <span>Total</span>
      <strong id="checkoutTotal">R$ 0,00</strong>
    </div>

    <div id="feedback" class="feedback"></div>

    <div class="actions">
      <a class="btn btn-ghost" href="index.php">Continuar comprando</a>
      <button class="btn btn-primary" id="finishBtn" type="button">Confirmar pedido</button>
    </div>
  </section>

  <script>
    const money = value => value.toLocaleString('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    });

    const checkoutData = JSON.parse(localStorage.getItem('dropzone-checkout') || '[]');
    const box = document.querySelector('#checkoutItems');
    const totalEl = document.querySelector('#checkoutTotal');
    const feedback = document.querySelector('#feedback');
    const finishBtn = document.querySelector('#finishBtn');

    function showFeedback(message) {
      feedback.textContent = message;
      feedback.classList.add('show');
    }

    function hideFeedback() {
      feedback.textContent = '';
      feedback.classList.remove('show');
    }

    function renderCheckout() {
      if (!checkoutData.length) {
        box.innerHTML = '<p class="empty">Nenhum item para finalizar. Volte para a loja e adicione produtos ao carrinho.</p>';
        totalEl.textContent = money(0);
        finishBtn.disabled = true;
        finishBtn.style.opacity = '.5';
        finishBtn.style.cursor = 'not-allowed';
        return;
      }

      box.innerHTML = checkoutData.map(item => `
        <article class="item">
          <img src="${item.image}" alt="${item.name}">
          <div>
            <h3>${item.name}</h3>
            <p>${item.qty}x • ${money(item.price)}</p>
          </div>
          <strong class="price">${money(item.price * item.qty)}</strong>
        </article>
      `).join('');

      const total = checkoutData.reduce((sum, item) => sum + (item.price * item.qty), 0);
      totalEl.textContent = money(total);
    }

    finishBtn.addEventListener('click', async () => {
      if (!checkoutData.length) {
        return;
      }

      hideFeedback();
      finishBtn.disabled = true;
      finishBtn.textContent = 'Processando...';

      try {
        const response = await fetch('api/finalizar_compra.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            items: checkoutData.map(item => ({
              id: item.id,
              qty: item.qty
            }))
          })
        });

        const rawBody = await response.text();
        let data = {};

        try {
          data = rawBody ? JSON.parse(rawBody) : {};
        } catch (parseError) {
          data = {
            error: 'Resposta invalida do servidor ao finalizar pedido.'
          };
        }

        if (!response.ok) {
          if (response.status === 409 && Array.isArray(data.items)) {
            const details = data.items
              .map(item => `${item.name}: solicitado ${item.requested}, disponivel ${item.available}`)
              .join(' | ');

            showFeedback(`Estoque insuficiente. ${details}`);
          } else {
            showFeedback(data.error || `Nao foi possivel finalizar o pedido. HTTP ${response.status}.`);
          }

          finishBtn.disabled = false;
          finishBtn.textContent = 'Confirmar pedido';
          return;
        }

        localStorage.removeItem('dropzone-cart');
        localStorage.removeItem('dropzone-checkout');
        window.location.href = 'index.php?pedido=sucesso';
      } catch (error) {
        showFeedback('Falha de comunicacao ao finalizar pedido.');
        finishBtn.disabled = false;
        finishBtn.textContent = 'Confirmar pedido';
      }
    });

    renderCheckout();
  </script>
</body>
</html>
