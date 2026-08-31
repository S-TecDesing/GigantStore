// productos.js
// Logica de la pagina de catalogo (productos.html): filtros, tallas,
// vista frente/atras, carrito lateral y checkout hacia carrito.html.
// Extraido del <script> inline original de PaginaProductos.html.

function goProducts(){
    document.getElementById("productos").scrollIntoView({behavior:"smooth"});
}

function size(btn, s){
    const row = btn.closest(".product-row");
    const m = row.querySelector(".measure");
    const images = row.querySelectorAll(".shirt-img");

    const data = {
        S:{ text: "50cm x 70cm", scale: "scale(0.95)" },
        M:{ text: "53cm x 72cm", scale: "scale(1)" },
        L:{ text: "56cm x 74cm", scale: "scale(1.05)" },
        XL:{ text: "59cm x 76cm", scale: "scale(1.1)" }
    };

    m.innerText = s + ": " + data[s].text;

    images.forEach(img => {
        img.style.transform = data[s].scale;
    });

    row.querySelectorAll(".sizes button").forEach(b=>b.classList.remove("active"));
    btn.classList.add("active");
}

function changeView(btn, viewType) {
    const centerDiv = btn.closest(".center");
    const frontImg = centerDiv.querySelector(".front-view");
    const backImg = centerDiv.querySelector(".back-view");
    const buttons = centerDiv.querySelectorAll(".view-btn");

    buttons.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");

    if (viewType === 'front') {
        frontImg.classList.add("active");
        backImg.classList.remove("active");
    } else {
        backImg.classList.add("active");
        frontImg.classList.remove("active");
    }
}

/* ---- FILTROS Y ORDEN ALFABÉTICO ---- */
const productsGrid = document.getElementById("productsGrid");
const originalOrder = Array.from(productsGrid.children);
let azActive = false;

function filterProducts(value){
    const rows = productsGrid.querySelectorAll(".product-row");
    rows.forEach(row => {
        const cats = row.dataset.categories.split(" ");
        row.style.display = (value === "todas" || cats.includes(value)) ? "" : "none";
    });
}

function toggleAlphabetical(btn){
    azActive = !azActive;
    btn.classList.toggle("active", azActive);

    if (azActive){
        const sorted = Array.from(productsGrid.children).sort((a, b) =>
            a.dataset.name.localeCompare(b.dataset.name, "es")
        );
        sorted.forEach(row => productsGrid.appendChild(row));
        btn.textContent = "🏆 Por relevancia";
    } else {
        originalOrder.forEach(row => productsGrid.appendChild(row));
        btn.textContent = "A-Z Alfabético";
    }
}

/* ---- CANTIDAD ---- */
function changeQty(btn, delta){
    const row = btn.closest(".product-row");
    const qtyEl = row.querySelector(".qty-value");
    let qty = parseInt(qtyEl.textContent);
    qty = Math.max(1, qty + delta);
    qtyEl.textContent = qty;
}

/* ---- CARRITO ---- */
// URL de la Landing Page (finalización de compra). Si ambos proyectos se
// despliegan como carpetas hermanas (pagePrincipal/ y ladingpage/) esta ruta
// relativa funciona tal cual. Si se despliegan en dominios distintos,
// reemplaza este valor por la URL completa, ej: "https://tudominio.com/ladingpage/"
const LANDING_URL = 'carrito.html';

let cart = [];
try {
    cart = JSON.parse(localStorage.getItem('mundialCart') || '[]');
} catch(e){ cart = []; }

function saveCart(){
    localStorage.setItem('mundialCart', JSON.stringify(cart));
    updateCartBadge();
}

function updateCartBadge(){
    const badge = document.getElementById('cartBadge');
    const totalQty = cart.reduce((sum,i)=>sum+i.qty,0);
    if(totalQty > 0){
        badge.textContent = totalQty;
        badge.classList.add('show');
    } else {
        badge.classList.remove('show');
    }
}

function addToCart(btn){
    const row = btn.closest('.product-row');
    const name = row.dataset.name;
    const activeSizeBtn = row.querySelector('.sizes button.active');
    const size = activeSizeBtn ? activeSizeBtn.textContent : 'M';
    const qty = parseInt(row.querySelector('.qty-value').textContent);
    const activeImg = row.querySelector('.shirt-img.active');
    const img = activeImg ? activeImg.src : '';
    const priceEl = row.querySelector('.price');
    const priceText = priceEl.childNodes[0].textContent.trim();
    const price = parseInt(priceText.replace(/[^\d]/g,''));

    const existing = cart.find(i => i.name === name && i.size === size);
    if(existing){
        existing.qty += qty;
    } else {
        cart.push({ name, size, qty, price, img });
    }

    saveCart();
    renderCart();
    showToast(name + " agregado al carrito");
    bumpBadge();

    // Reiniciar selector de cantidad de la fila
    row.querySelector('.qty-value').textContent = "1";
}

function bumpBadge(){
    const badge = document.getElementById('cartBadge');
    badge.classList.remove('bump');
    void badge.offsetWidth;
    badge.classList.add('bump');
}

function showToast(msg){
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(()=>toast.classList.remove('show'), 2200);
}

function renderCart(){
    const container = document.getElementById('cartItems');
    const totalEl = document.getElementById('cartTotal');

    if(cart.length === 0){
        container.innerHTML = '<p class="cart-empty">Tu carrito está vacío 🛒</p>';
        totalEl.textContent = '$0 COP';
        return;
    }

    container.innerHTML = cart.map((item, idx) => `
        <div class="cart-item">
            <img src="${item.img}" alt="${item.name}">
            <div class="cart-item-info">
                <h4>${item.name}</h4>
                <p>Talla: ${item.size} · $${item.price.toLocaleString('es-CO')} c/u</p>
                <div class="cart-item-qty">
                    <button onclick="updateCartQty(${idx},-1)">−</button>
                    <span>${item.qty}</span>
                    <button onclick="updateCartQty(${idx},1)">+</button>
                </div>
            </div>
            <button class="cart-item-remove" onclick="removeFromCart(${idx})" aria-label="Eliminar">✕</button>
        </div>
    `).join('');

    const total = cart.reduce((sum,i)=>sum + i.price * i.qty, 0);
    totalEl.textContent = '$' + total.toLocaleString('es-CO') + ' COP';
}

function updateCartQty(idx, delta){
    cart[idx].qty += delta;
    if(cart[idx].qty <= 0){
        cart.splice(idx,1);
    }
    saveCart();
    renderCart();
}

function removeFromCart(idx){
    cart.splice(idx,1);
    saveCart();
    renderCart();
}

function openCart(){
    renderCart();
    document.getElementById('cartOverlay').classList.add('open');
}

function closeCart(){
    document.getElementById('cartOverlay').classList.remove('open');
}

function checkout(){
    if(cart.length === 0){
        showToast('Tu carrito está vacío');
        return;
    }

    // Guarda los datos del pedido para la siguiente página
    localStorage.setItem('mundialCheckout', JSON.stringify(cart));

    closeCart();
    document.getElementById('thanksOverlay').classList.add('open');

    // Se cierra a los 5 segundos y redirige a la Landing Page para completar los datos de envío
    // Ajusta LANDING_URL si despliegas la landing page en otra ruta o dominio distinto.
    setTimeout(() => {
        document.getElementById('thanksOverlay').classList.remove('open');
        window.location.href = LANDING_URL;
    }, 5000);
}

updateCartBadge();

/* ---- ANIMACIÓN AL HACER SCROLL ---- */
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if(entry.isIntersecting){
            entry.target.classList.add('in-view');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.15 });

document.querySelectorAll('.product-row').forEach(row => {
    row.classList.add('reveal');
    revealObserver.observe(row);
});
