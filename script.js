// ============================================================
// LISTA DE PRODUTOS
// ============================================================

// Array preenchido dinamicamente via API SQL
let products = [];


// ============================================================
// CARRINHO
// ============================================================

// Tenta recuperar o carrinho salvo no navegador.
//
// localStorage permite guardar informações mesmo depois
// que o usuário fecha ou atualiza a página.
//
// Caso não exista nenhum carrinho salvo, utiliza um array vazio.
let cart = JSON.parse(localStorage.getItem("dropzone-cart") || "[]");


// ============================================================
// FORMATAÇÃO DE PREÇO
// ============================================================

// Função responsável por transformar um número em formato
// de moeda brasileira.
//

const money = value => value.toLocaleString("pt-BR", {
  style: "currency",
  currency: "BRL"
});


// ============================================================
// CARREGAR PRODUTOS DA API
// ============================================================

async function loadProducts() {

  try {
    const response = await fetch("api/produtos.php");

    if (!response.ok) {
      throw new Error("Nao foi possivel carregar os produtos.");
    }

    products = await response.json();
  }

  catch (error) {
    products = [];
    showToast("Falha ao carregar produtos do banco.");
    console.error(error);
  }

}


// ============================================================
// CRIAÇÃO DO CARD DO PRODUTO
// ============================================================

// Essa função recebe um produto e cria o HTML
// que será utilizado para mostrar esse produto na página.
function productCard(p) {
  const stock = Number(p.stock || 0);
  const canAdd = stock > 0;
  const sizes = Array.isArray(p.sizes) ? p.sizes : [];
  const sizeOptions = sizes.map(size =>
    `<option value="${size.name}" ${size.stock <= 0 ? 'disabled' : ''}>${size.name}</option>`
  ).join('');

  return `
    <article class="product-card">

      <div class="product-image">

        <!-- Imagem do produto -->
        <img src="${p.image}" alt="${p.name}" loading="lazy">

        <!--
          Mostra a badge somente se o produto possuir uma.
        -->
        ${p.badge ? `<span class="badge">${p.badge}</span>` : ""}

        <!-- Botão de favoritar -->
        <button
          class="heart"
          onclick="favorite(this)"
          aria-label="Favoritar"
        >
          ♡
        </button>

      </div>

      <div class="product-info">

        <!-- Categoria do produto -->
        <span class="product-category">${p.category}</span>

        <!-- Nome do produto -->
        <h3 class="product-name">${p.name}</h3>

        ${sizes.length ? `
          <label class="product-size">
            <span>Tamanho</span>
            <select id="size-${p.id}" aria-label="Escolha o tamanho de ${p.name}">
              <option value="">Selecione</option>
              ${sizeOptions}
            </select>
          </label>
        ` : ''}

        <!--
          Ao clicar nesse botão, chama a função addToCart()
          passando o ID do produto.
        -->

        <div class="product-cart-action">
          <strong class="price">${money(p.price)}</strong>

          <button
            class="add"
            onclick="addToCart(${p.id})"
            aria-label="Adicionar ao carrinho"
            ${canAdd ? "" : "disabled"}
          >
            +
          </button>
        </div>

      </div>
    </article>`;
}


// ============================================================
// MOSTRAR PRODUTOS
// ============================================================

// Mostra os produtos dentro do elemento #products.
//
// Se nenhuma lista for enviada para a função,
// ela utiliza todos os produtos.
function renderProducts(list = products) {

  if (!list.length) {
    document.querySelector("#products").innerHTML = `
      <p class="empty">Nenhum produto cadastrado no banco.</p>
    `;
    return;
  }

  document.querySelector("#products").innerHTML =
    list.map(productCard).join("");

}


// ============================================================
// MOSTRAR PRODUTOS POR TIPO
// ============================================================

// Filtra os produtos pelo tipo.
//
//
// id indica onde os produtos serão exibidos.
function renderByType(type, id) {

  const filtered = products.filter(p => p.type === type);

  if (!filtered.length) {
    document.querySelector(id).innerHTML = `
      <p class="empty">Nenhum produto nessa categoria.</p>
    `;
    return;
  }

  document.querySelector(id).innerHTML =
    filtered
      .map(productCard)
      .join("");

}


// ============================================================
// ADICIONAR PRODUTO AO CARRINHO
// ============================================================

function addToCart(id) {

  // Procura o produto dentro do array products
  const item = products.find(p => p.id === id);

  if (!item) {
    return showToast("Produto nao encontrado.");
  }

  const sizeSelect = document.querySelector(`#size-${id}`);
  const size = sizeSelect ? sizeSelect.value : '';

  if (Array.isArray(item.sizes) && item.sizes.length && !size) {
    return showToast("Selecione o tamanho antes de adicionar.");
  }

  const selectedSize = item.sizes?.find(entry => entry.name === size);
  const stock = selectedSize ? Number(selectedSize.stock || 0) : Number(item.stock || 0);

  if (stock <= 0) {
    return showToast("Tamanho sem estoque.");
  }

  // Verifica se esse SKU com esse tamanho ja esta no carrinho
  const existing = cart.find(p => p.id === id && (p.size || '') === size);

  if (existing) {
    if (existing.qty >= stock) {
      return showToast("Voce atingiu o limite de estoque desse tamanho.");
    }

    existing.qty++;
  }

  else {
    cart.push({
      ...item,
      size,
      qty: 1
    });
  }

  // Salva o carrinho
  saveCart();

  // Mostra uma mensagem na tela
  showToast(`${item.name} foi adicionado ao carrinho.`);
}


// ============================================================
// REMOVER PRODUTO DO CARRINHO
// ============================================================

function removeFromCart(id, size) {
  cart = cart.filter(p => !(p.id === id && (p.size || '') === (size || '')));
  saveCart();
}


// ============================================================
// SALVAR CARRINHO
// ============================================================

function saveCart() {

  // Converte o array cart para texto JSON
  // e salva no localStorage.
  localStorage.setItem(
    "dropzone-cart",
    JSON.stringify(cart)
  );

  // Atualiza visualmente o carrinho
  renderCart();
}


// ============================================================
// RENDERIZAR CARRINHO
// ============================================================

function renderCart() {

  // Localiza a área onde os produtos do carrinho serão exibidos
  const box = document.querySelector("#cartItems");

  // Calcula a quantidade total de produtos.
  //
  const count = cart.reduce(
    (sum, p) => sum + p.qty,
    0
  );

  // Calcula o preço total do carrinho.
  //
  const total = cart.reduce(
    (sum, p) => sum + p.price * p.qty,
    0
  );

  // Atualiza o número de produtos no ícone do carrinho
  document.querySelector("#cartCount").textContent = count;

  // Atualiza o valor total
  document.querySelector("#cartTotal").textContent = money(total);


  // ----------------------------------------------------------
  // CARRINHO VAZIO
  // ----------------------------------------------------------

  if (!cart.length) {

    box.innerHTML = `
      <p class="empty">
        Seu carrinho está vazio.<br>
        Adicione seu próximo drop.
      </p>
    `;

    return;
  }


  // ----------------------------------------------------------
  // PRODUTOS DO CARRINHO
  // ----------------------------------------------------------

  // Cria o HTML de cada produto que está no carrinho
  box.innerHTML = cart.map(p => `
    
    <div class="cart-item">

      <!-- Imagem do produto -->
      <img src="${p.image}" alt="${p.name}">

      <div>

        <!-- Nome do produto -->
        <h4>${p.name}</h4>

        ${p.size ? `<p>Tamanho: ${p.size}</p>` : ''}

        <!-- Quantidade e preço -->
        <p>
          ${p.qty}x • ${money(p.price)}
        </p>

      </div>

      <!-- Botão para remover o produto -->
      <button
        class="remove"
        onclick="removeFromCart(${p.id}, '${(p.size || '').replace(/'/g, "\\'")}')"
      >
        ✕
      </button>

    </div>

  `).join("");
}


// ============================================================
// FAVORITAR PRODUTO
// ============================================================

function favorite(button) {

  // Se o botão estiver vazio (♡), transforma em coração cheio (♥).
  //
  // Se estiver cheio, volta para vazio.
  button.textContent =
    button.textContent === "♡" ? "♥" : "♡";


  // Altera a cor do coração.
  //
  // Coração cheio → verde neon
  // Coração vazio → branco
  button.style.color =
    button.textContent === "♥"
      ? "#45ff00"
      : "#fff";
}


// ============================================================
// SISTEMA DE TOAST
// ============================================================

// Mostra pequenas mensagens temporárias na tela.
function showToast(message) {

  // Localiza o elemento do toast
  const toast = document.querySelector("#toast");

  // Define o texto da mensagem
  toast.textContent = message;

  // Adiciona a classe "show",
  // fazendo o toast aparecer.
  toast.classList.add("show");

  // Cancela o timer anterior, caso exista.
  clearTimeout(window.toastTimer);

  // Depois de 2,2 segundos,
  // remove a classe e esconde o toast.
  window.toastTimer = setTimeout(() => {
    toast.classList.remove("show");
  }, 2200);
}

function showOrderStatusFromURL() {
  const params = new URLSearchParams(window.location.search);

  if (params.get("pedido") !== "sucesso") {
    return;
  }

  showToast("Pedido finalizado com sucesso!");

  // Limpa o parametro da URL sem recarregar a pagina.
  window.history.replaceState({}, "", window.location.pathname);
}

function syncCartWithStock() {
  const stockById = new Map(
    products.map(product => [product.id, Number(product.stock || 0)])
  );

  let changed = false;

  cart = cart.filter(item => {
    const stock = stockById.get(item.id);

    if (typeof stock === "undefined" || stock <= 0) {
      changed = true;
      return false;
    }

    if (item.qty > stock) {
      item.qty = stock;
      changed = true;
    }

    return item.qty > 0;
  });

  if (changed) {
    localStorage.setItem("dropzone-cart", JSON.stringify(cart));
    showToast("Carrinho ajustado conforme o estoque atual.");
  }
}


// ============================================================
// ELEMENTOS DO CARRINHO
// ============================================================

// Seleciona o elemento principal do carrinho
const cartEl = document.querySelector("#cart");

// Seleciona o fundo escuro que aparece atrás do carrinho
const overlay = document.querySelector("#overlay");


// ============================================================
// ABRIR E FECHAR CARRINHO
// ============================================================

// Função para abrir o carrinho
const openCart = () => {

  cartEl.classList.add("open");
  overlay.classList.add("open");

};


// Função para fechar o carrinho
const closeCart = () => {

  cartEl.classList.remove("open");
  overlay.classList.remove("open");

};


// ============================================================
// EVENTOS DO CARRINHO
// ============================================================

// Quando clicar no botão do carrinho,
// abre o carrinho.
document
  .querySelector("#cartBtn")
  .addEventListener("click", openCart);


// Quando clicar no X,
// fecha o carrinho.
document
  .querySelector("#closeCart")
  .addEventListener("click", closeCart);


// Quando clicar no fundo escuro,
// também fecha o carrinho.
overlay.addEventListener("click", closeCart);


// ============================================================
// MENU MOBILE
// ============================================================

// Quando clicar no botão do menu,
// adiciona ou remove a classe "open".
//
// Isso permite abrir e fechar o menu em telas menores.
document
  .querySelector("#menuBtn")
  .addEventListener("click", () => {

    document
      .querySelector("#nav")
      .classList.toggle("open");

  });


// ============================================================
// SISTEMA DE BUSCA
// ============================================================

// Quando o usuário digitar alguma coisa no campo de pesquisa
document
  .querySelector("#searchInput")
  .addEventListener("input", e => {

    // Pega o texto digitado
    // e transforma tudo para letras minúsculas.
    const term = e.target.value.trim().toLowerCase();


    // Procura produtos cujo nome ou categoria
    // contenha o texto digitado.
    const filtered = products.filter(p =>
      p.name.toLowerCase().includes(term) ||
      p.category.toLowerCase().includes(term)
    );


    // Mostra somente os produtos encontrados
    renderProducts(filtered);

  });


// ============================================================
// FILTROS DE PRODUTOS
// ============================================================

// Seleciona todos os botões que possuem a classe .filter
document.querySelectorAll(".filter").forEach(button => {

  // Adiciona um evento de clique em cada botão
  button.addEventListener("click", () => {

    // Remove a classe "active" de todos os filtros
    document
      .querySelectorAll(".filter")
      .forEach(b => b.classList.remove("active"));


    // Adiciona "active" somente ao botão clicado
    button.classList.add("active");


    // Pega o valor definido no atributo data-filter
    //
    const filter = button.dataset.filter;


    // Se o filtro for "todos",
    // mostra todos os produtos.
    //
    // Caso contrário,
    // mostra apenas os produtos daquele tipo.
    renderProducts(
      filter === "todos"
        ? products
        : products.filter(p => p.type === filter)
    );

  });

});


// ============================================================
// LINKS DO MENU
// ============================================================

// Seleciona todos os links do menu
document.querySelectorAll(".nav a").forEach(link => {

  // Quando clicar em um link,
  // fecha o menu mobile.
  link.addEventListener("click", () => {

    document
      .querySelector("#nav")
      .classList.remove("open");

  });

});


// ============================================================
// FINALIZAR PEDIDO
// ============================================================

// Quando o cliente clicar no botão de checkout
document
  .querySelector("#checkout")
  .addEventListener("click", () => {

    // Verifica se o carrinho está vazio
    if (!cart.length) {
      return showToast("Seu carrinho está vazio.");
    }

    // Salva uma copia do carrinho para a pagina de finalizacao
    localStorage.setItem(
      "dropzone-checkout",
      JSON.stringify(cart)
    );

    // Redireciona para a pagina de finalizacao
    window.location.href = "finalizacao.php";

  });

// ============================================================
// INICIALIZAÇÃO DA PÁGINA
// ============================================================

async function initPage() {

  await loadProducts();
  syncCartWithStock();

  // Mostra todos os produtos na seção principal
  renderProducts();


  // Mostra somente os produtos do tipo "tenis"
  // dentro da seção #shoeProducts
  renderByType("tenis", "#shoeProducts");


  // Mostra somente os produtos do tipo "roupas"
  // dentro da seção #clothingProducts
  renderByType("roupas", "#clothingProducts");


  // Carrega e mostra o carrinho salvo
  renderCart();
}

initPage();
showOrderStatusFromURL();