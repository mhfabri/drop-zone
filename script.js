const products = [
  {
    id: 1, name: "DZ Shadow Runner", category: "Tênis", type: "tenis",
    price: 399.90, badge: "NOVO",
    image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 2, name: "DZ Urban Silver", category: "Tênis", type: "tenis",
    price: 459.90, badge: "DROP",
    image: "https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 3, name: "DZ Street Black", category: "Tênis", type: "tenis",
    price: 349.90, badge: "BEST",
    image: "https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 4, name: "DZ Classic Low", category: "Tênis", type: "tenis",
    price: 299.90, badge: "",
    image: "https://images.unsplash.com/photo-1495555961986-6d4c1ecb7be3?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 5, name: "Oversized DZ Logo", category: "Camiseta", type: "roupas",
    price: 119.90, badge: "NOVO",
    image: "https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 6, name: "Moletom Drop Zone", category: "Moletom", type: "roupas",
    price: 219.90, badge: "DROP",
    image: "https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 7, name: "Cargo DZ Utility", category: "Calça", type: "roupas",
    price: 189.90, badge: "",
    image: "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&w=900&q=85"
  },
  {
    id: 8, name: "DZ Essential Tee", category: "Camiseta", type: "roupas",
    price: 99.90, badge: "BEST",
    image: "https://images.unsplash.com/photo-1503341504253-dff4815485f1?auto=format&fit=crop&w=900&q=85"
  }
];

let cart = JSON.parse(localStorage.getItem("dropzone-cart") || "[]");

const money = value => value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });

function productCard(p) {
  return `
    <article class="product-card">
      <div class="product-image">
        <img src="${p.image}" alt="${p.name}" loading="lazy">
        ${p.badge ? `<span class="badge">${p.badge}</span>` : ""}
        <button class="heart" onclick="favorite(this)" aria-label="Favoritar">♡</button>
      </div>
      <div class="product-info">
        <span class="product-category">${p.category}</span>
        <h3 class="product-name">${p.name}</h3>
        <div class="product-row">
          <strong class="price">${money(p.price)}</strong>
          <button class="add" onclick="addToCart(${p.id})" aria-label="Adicionar ao carrinho">+</button>
        </div>
      </div>
    </article>`;
}

function renderProducts(list = products) {
  document.querySelector("#products").innerHTML = list.map(productCard).join("");
}

function renderByType(type, id) {
  document.querySelector(id).innerHTML = products.filter(p => p.type === type).map(productCard).join("");
}

function addToCart(id) {
  const item = products.find(p => p.id === id);
  const existing = cart.find(p => p.id === id);
  if (existing) existing.qty++;
  else cart.push({ ...item, qty: 1 });
  saveCart();
  showToast(`${item.name} foi adicionado ao carrinho.`);
}

function removeFromCart(id) {
  cart = cart.filter(p => p.id !== id);
  saveCart();
}

function saveCart() {
  localStorage.setItem("dropzone-cart", JSON.stringify(cart));
  renderCart();
}

function renderCart() {
  const box = document.querySelector("#cartItems");
  const count = cart.reduce((sum, p) => sum + p.qty, 0);
  const total = cart.reduce((sum, p) => sum + p.price * p.qty, 0);

  document.querySelector("#cartCount").textContent = count;
  document.querySelector("#cartTotal").textContent = money(total);

  if (!cart.length) {
    box.innerHTML = `<p class="empty">Seu carrinho está vazio.<br>Adicione seu próximo drop.</p>`;
    return;
  }

  box.innerHTML = cart.map(p => `
    <div class="cart-item">
      <img src="${p.image}" alt="${p.name}">
      <div>
        <h4>${p.name}</h4>
        <p>${p.qty}x • ${money(p.price)}</p>
      </div>
      <button class="remove" onclick="removeFromCart(${p.id})">✕</button>
    </div>
  `).join("");
}

function favorite(button) {
  button.textContent = button.textContent === "♡" ? "♥" : "♡";
  button.style.color = button.textContent === "♥" ? "#45ff00" : "#fff";
}

function showToast(message) {
  const toast = document.querySelector("#toast");
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(window.toastTimer);
  window.toastTimer = setTimeout(() => toast.classList.remove("show"), 2200);
}

const cartEl = document.querySelector("#cart");
const overlay = document.querySelector("#overlay");
const openCart = () => { cartEl.classList.add("open"); overlay.classList.add("open"); };
const closeCart = () => { cartEl.classList.remove("open"); overlay.classList.remove("open"); };

document.querySelector("#cartBtn").addEventListener("click", openCart);
document.querySelector("#closeCart").addEventListener("click", closeCart);
overlay.addEventListener("click", closeCart);

document.querySelector("#menuBtn").addEventListener("click", () => {
  document.querySelector("#nav").classList.toggle("open");
});

document.querySelector("#searchBtn").addEventListener("click", () => {
  const panel = document.querySelector("#searchPanel");
  panel.classList.toggle("open");
  if (panel.classList.contains("open")) document.querySelector("#searchInput").focus();
});

document.querySelector("#searchInput").addEventListener("input", e => {
  const term = e.target.value.trim().toLowerCase();
  const filtered = products.filter(p =>
    p.name.toLowerCase().includes(term) ||
    p.category.toLowerCase().includes(term)
  );
  renderProducts(filtered);
});

document.querySelectorAll(".filter").forEach(button => {
  button.addEventListener("click", () => {
    document.querySelectorAll(".filter").forEach(b => b.classList.remove("active"));
    button.classList.add("active");
    const filter = button.dataset.filter;
    renderProducts(filter === "todos" ? products : products.filter(p => p.type === filter));
  });
});

document.querySelectorAll(".nav a").forEach(link => {
  link.addEventListener("click", () => document.querySelector("#nav").classList.remove("open"));
});

document.querySelector("#checkout").addEventListener("click", () => {
  if (!cart.length) return showToast("Seu carrinho está vazio.");
  showToast("Checkout de demonstração — pronto para integrar pagamento.");
});

renderProducts();
renderByType("tenis", "#shoeProducts");
renderByType("roupas", "#clothingProducts");
renderCart();