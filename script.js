// ============================================================
// LISTA DE PRODUTOS
// ============================================================

// Array contendo todos os produtos disponíveis na loja
const products = [
  {
    id: 1, // Identificador único do produto
    name: "DZ Shadow Runner", // Nome do produto
    category: "Tênis", // Categoria exibida para o usuário
    type: "tenis", // Tipo usado nos filtros
    price: 399.90, // Preço do produto
    badge: "NOVO", // Etiqueta exibida no card
    image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 2,
    name: "DZ Urban Silver",
    category: "Tênis",
    type: "tenis",
    price: 459.90,
    badge: "DROP",
    image: "https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 3,
    name: "DZ Street Black",
    category: "Tênis",
    type: "tenis",
    price: 349.90,
    badge: "BEST",
    image: "https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 4,
    name: "DZ Classic Low",
    category: "Tênis",
    type: "tenis",
    price: 299.90,
    badge: "",
    image: "https://images.unsplash.com/photo-1495555961986-6d4c1ecb7be3?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 5,
    name: "Oversized DZ Logo",
    category: "Camiseta",
    type: "roupas",
    price: 119.90,
    badge: "NOVO",
    image: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 6,
    name: "Moletom Drop Zone",
    category: "Moletom",
    type: "roupas",
    price: 219.90,
    badge: "DROP",
    image: "https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 7,
    name: "Cargo DZ Utility",
    category: "Calça",
    type: "roupas",
    price: 189.90,
    badge: "",
    image: "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=85"
  },

  {
    id: 8,
    name: "DZ Essential Tee",
    category: "Camiseta",
    type: "roupas",
    price: 99.90,
    badge: "BEST",
    image: "https://images.unsplash.com/photo-1503341504253-dff4815485f1?auto=format&fit=crop&w=900&q=85"
  }
];


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
// Exemplo:
// 399.90 → R$ 399,90
const money = value => value.toLocaleString("pt-BR", {
  style: "currency",
  currency: "BRL"
});


// ============================================================
// CRIAÇÃO DO CARD DO PRODUTO
// ============================================================

// Essa função recebe um produto e cria o HTML
// que será utilizado para mostrar esse produto na página.
function productCard(p) {
  return `
    <article class="product-card">

      <div class="product-image">

        <!-- Imagem do produto -->
        <img src="${p.image}" alt="${p.name}" loading="lazy">

        <!--
          Mostra a badge somente se o produto possuir uma.
          Exemplo: NOVO, DROP ou BEST.
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

        <div class="product-row">

          <!-- Preço formatado -->
          <strong class="price">${money(p.price)}</strong>

          <!--
            Ao clicar nesse botão, chama a função addToCart()
            passando o ID do produto.
          -->
          <button
            class="add"
            onclick="addToCart(${p.id})"
            aria-label="Adicionar ao carrinho"
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

  document.querySelector("#products").innerHTML =
    list.map(productCard).join("");

}


// ============================================================
// MOSTRAR PRODUTOS POR TIPO
// ============================================================

// Filtra os produtos pelo tipo.
//
// Por exemplo:
// "tenis" → mostra apenas tênis
// "roupas" → mostra apenas roupas
//
// id indica onde os produtos serão exibidos.
function renderByType(type, id) {

  document.querySelector(id).innerHTML =
    products
      .filter(p => p.type === type)
      .map(productCard)
      .join("");

}


// ============================================================
// ADICIONAR PRODUTO AO CARRINHO
// ============================================================

function addToCart(id) {

  // Procura o produto dentro do array products
  const item = products.find(p => p.id === id);

  // Verifica se esse produto já está no carrinho
  const existing = cart.find(p => p.id === id);

  // Se já existir, aumenta a quantidade
  if (existing) {
    existing.qty++;
  }

  // Caso ainda não esteja no carrinho,
  // adiciona o produto com quantidade 1.
  else {
    cart.push({
      ...item,
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

function removeFromCart(id) {

  // Cria um novo array contendo apenas os produtos
  // cujo ID seja diferente do produto removido.
  cart = cart.filter(p => p.id !== id);

  // Salva o novo carrinho
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
  // Exemplo:
  // 2 tênis + 3 camisetas = 5 produtos
  const count = cart.reduce(
    (sum, p) => sum + p.qty,
    0
  );

  // Calcula o preço total do carrinho.
  //
  // Exemplo:
  // R$ 100 × 2 = R$ 200
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

        <!-- Quantidade e preço -->
        <p>
          ${p.qty}x • ${money(p.price)}
        </p>

      </div>

      <!-- Botão para remover o produto -->
      <button
        class="remove"
        onclick="removeFromCart(${p.id})"
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
    // Exemplo:
    // data-filter="tenis"
    // data-filter="roupas"
    // data-filter="todos"
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

    // ========================================================
    // INSTAGRAM DA LOJA
    // ========================================================

    // Coloque aqui o @ da loja SEM o símbolo @
    const instagram = "drop_zonekz";


    // ========================================================
    // MONTAR MENSAGEM DO PEDIDO
    // ========================================================

    let mensagem = `Olá, Drop Zone! 👋

Gostaria de fazer um pedido:

`;


    // Adiciona cada produto do carrinho na mensagem
    cart.forEach((produto) => {

      mensagem += `👟 ${produto.name}
Quantidade: ${produto.qty}
Preço: ${money(produto.price)}
Subtotal: ${money(produto.price * produto.qty)}

`;

    });


    // ========================================================
    // CALCULAR TOTAL
    // ========================================================

    const total = cart.reduce((sum, produto) => {
      return sum + (produto.price * produto.qty);
    }, 0);


    // Adiciona o total no final da mensagem
    mensagem += `💰 TOTAL: ${money(total)}

Aguardo as informações para finalizar a compra.`;



    // ========================================================
    // COPIAR PEDIDO
    // ========================================================

    navigator.clipboard.writeText(mensagem)

      .then(() => {

        // Avisa o cliente que o pedido foi copiado
        showToast("Pedido copiado! Abrindo Instagram...");


        // Espera um pouco para o usuário visualizar a mensagem
        setTimeout(() => {

          // Abre a DM do Instagram da loja
          window.location.href = `https://ig.me/m/${instagram}`;

        }, 1000);

      })


      // ======================================================
      // CASO NÃO CONSIGA COPIAR
      // ======================================================

      // .catch(() => {

      //   // Mostra a mensagem caso o navegador
      //   // não permita copiar automaticamente
      //   window.alert(
      //     "Não foi possível copiar o pedido automaticamente.\n\n" +
      //     mensagem
      //   );


      //   // Mesmo assim, abre o Instagram
      //   window.location.href = `https://ig.me/m/${instagram}`;

      // });

  });

// ============================================================
// INICIALIZAÇÃO DA PÁGINA
// ============================================================

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