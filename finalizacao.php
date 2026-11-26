<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finalizacao do Pedido | Drop Zone</title>
  <link rel="stylesheet" href="css/finalizacao.css">
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
