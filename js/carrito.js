const WHATSAPP_NUMBER = "573227517207";
let userPreference = "";
let itemGender = "Masculino";
let cart = [];

// Estado individual de cada tarjeta del catálogo (talla, cantidad, vista frente/atrás)
const cardState = {};
PRODUCTS.forEach(p => {
    cardState[p.id] = { size: 'M', qty: 1, view: 'front' };
});

/* =========================================================
   GÉNERO / PREFERENCIA DE COMPRA
   ========================================================= */
function setGender(gender) {
    userPreference = gender;
    itemGender = gender;
    document.getElementById('gender-modal').classList.add('hidden');
    updateNavGenderUI();
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function updateNavGenderUI() {
    const btnMasc = document.getElementById('btn-nav-masc');
    const btnFem = document.getElementById('btn-nav-fem');
    if (!btnMasc || !btnFem) return;
    btnMasc.classList.toggle('item-gender-active-masc', itemGender === 'Masculino');
    btnFem.classList.toggle('item-gender-active-fem', itemGender === 'Femenino');
}

/* =========================================================
   MODAL PERSONALIZADO (confirmaciones / avisos)
   ========================================================= */
function showCustomModal({ title, message, showInput = false, defaultValue = 1, onConfirm }) {
    const modal = document.getElementById('custom-modal');
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-message').textContent = message;
    const inputCont = document.getElementById('modal-input-container');
    const input = document.getElementById('modal-input');
    const btnCancel = document.getElementById('modal-cancel');

    if (showInput) {
        inputCont.classList.remove('hidden');
        input.value = defaultValue;
        btnCancel.classList.remove('hidden');
    } else {
        inputCont.classList.add('hidden');
        btnCancel.classList.add('hidden');
    }

    modal.classList.remove('hidden');
    document.getElementById('modal-confirm').onclick = () => {
        modal.classList.add('hidden');
        if (onConfirm) onConfirm(input.value);
    };
    btnCancel.onclick = () => modal.classList.add('hidden');
}

/* =========================================================
   CATÁLOGO — Render dinámico de las 16 selecciones
   ========================================================= */
function money(n) {
    return '$' + n.toLocaleString('es-CO');
}

function matchesFilter(product, filterValue, searchValue) {
    const filterOk = filterValue === 'todas' || (CATEGORY_MAP[product.id] || []).includes(filterValue);
    const searchOk = !searchValue || product.name.toLowerCase().includes(searchValue.toLowerCase());
    return filterOk && searchOk;
}

// Mapa de categorías (mismo criterio usado en pagePrincipal)
const CATEGORY_MAP = {
    brasil: ['mundial', 'copa'], alemania: ['mundial', 'eurocopa'], italia: ['mundial', 'eurocopa'],
    argentina: ['mundial', 'copa'], francia: ['mundial', 'eurocopa'], uruguay: ['mundial', 'copa'],
    inglaterra: ['mundial'], espana: ['mundial', 'eurocopa'], colombia: ['copa'],
    usa: [], mexico: ['copa'], costarica: [], canada: [],
    portugal: ['eurocopa'], paisesbajos: [], chile: ['copa']
};

function renderCatalog() {
    const grid = document.getElementById('catalog-grid');
    const filterValue = document.getElementById('catalog-filter')?.value || 'todas';
    const searchValue = document.getElementById('catalog-search')?.value || '';

    grid.innerHTML = PRODUCTS
        .filter(p => matchesFilter(p, filterValue, searchValue))
        .map(p => cardTemplate(p))
        .join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();
    observeCards();
}

function cardTemplate(p) {
    const st = cardState[p.id];
    return `
    <article class="team-card" data-id="${p.id}">
        <div class="team-card-imgwrap" title="Click para ver frente / espalda">
            <span class="team-flag-badge">${p.flag}</span>
            <span class="team-discount-badge">${p.discount}</span>
            <img id="img-${p.id}" class="team-card-img" src="${st.view === 'front' ? p.imgFront : p.imgBack}"
                 alt="Camiseta selección ${p.name}" loading="lazy" width="700" height="875"
                 onclick="toggleCardView('${p.id}')">
            <button type="button" class="team-zoom-btn" title="Ver en grande / calidad de la tela"
                    onclick="event.stopPropagation(); openZoom(document.getElementById('img-${p.id}').src)">
                <i data-lucide="zoom-in" class="w-4 h-4"></i>
            </button>
            <span class="team-flip-hint"><i data-lucide="repeat" class="w-3 h-3"></i> Girar</span>
        </div>
        <div class="p-4 space-y-2.5">
            <div class="flex items-start justify-between gap-2">
                <h3 class="font-heading text-2xl text-white leading-none">${p.name.toUpperCase()}</h3>
                <span class="team-stock">${p.stock} disp.</span>
            </div>
            <p class="text-[11px] text-white/50 uppercase tracking-wide">${p.titles}</p>

            <div class="flex items-end gap-2">
                <span class="text-white/40 line-through text-xs font-bold">${money(p.oldPrice)}</span>
                <span class="font-heading text-2xl text-[#FCD116]">${money(p.price)}</span>
            </div>

            <details class="team-details">
                <summary>Tela &amp; historia</summary>
                <p class="mb-1"><strong class="text-white/80">Tela:</strong> ${p.fabricTitle} — ${p.fabricSub}. ${p.tech}</p>
                <p>${p.historia}</p>
            </details>

            <div class="flex flex-wrap gap-1.5" id="sizes-${p.id}">
                ${['S', 'M', 'L', 'XL'].map(sz => `
                    <button class="team-size-btn ${st.size === sz ? 'active' : ''}" onclick="selectCardSize('${p.id}', this, '${sz}')">${sz}</button>
                `).join('')}
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 bg-black/30 rounded-xl px-2 py-1">
                    <button class="team-qty-btn" onclick="changeCardQty('${p.id}', -1)">−</button>
                    <span id="qty-${p.id}" class="text-sm font-bold w-5 text-center">${st.qty}</span>
                    <button class="team-qty-btn" onclick="changeCardQty('${p.id}', 1)">+</button>
                </div>
            </div>

            <button class="team-add-btn" onclick="addToCart('${p.id}')">
                AÑADIR AL CARRITO
            </button>
        </div>
    </article>`;
}

function toggleCardView(id) {
    const st = cardState[id];
    const p = PRODUCTS.find(x => x.id === id);
    st.view = st.view === 'front' ? 'back' : 'front';
    const img = document.getElementById(`img-${id}`);
    img.style.opacity = 0;
    setTimeout(() => {
        img.src = st.view === 'front' ? p.imgFront : p.imgBack;
        img.style.opacity = 1;
    }, 120);
}

function selectCardSize(id, btn, size) {
    cardState[id].size = size;
    document.querySelectorAll(`#sizes-${id} .team-size-btn`).forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

function changeCardQty(id, delta) {
    const st = cardState[id];
    st.qty = Math.max(1, st.qty + delta);
    document.getElementById(`qty-${id}`).textContent = st.qty;
}

/* Animación de aparición al hacer scroll */
let cardObserver;
function observeCards() {
    if (!cardObserver) {
        cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                    cardObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
    }
    document.querySelectorAll('.team-card:not(.in-view)').forEach(card => cardObserver.observe(card));
}

/* =========================================================
   CARRITO
   ========================================================= */
function addToCart(id, sizeOverride, qtyOverride, silent) {
    const p = PRODUCTS.find(x => x.id === id);
    if (!p) return;
    const st = cardState[id] || { size: 'M', qty: 1 };
    const size = sizeOverride || st.size;
    const qty = qtyOverride || st.qty;

    const existing = cart.find(i => i.id === id && i.size === size && i.gender === itemGender);
    if (existing) existing.qty += qty;
    else cart.push({ id, name: p.name, price: p.price, size, qty, gender: itemGender, img: p.imgFront });

    updateCartUI();
    if (!silent) {
        showToast(`${p.name} añadido al pedido (${itemGender})`);
        setTimeout(() => scrollToCart(), 500);
        cardState[id].qty = 1;
        const qtyEl = document.getElementById(`qty-${id}`);
        if (qtyEl) qtyEl.textContent = 1;
    }
}

function removeFromCart(index) {
    const item = cart[index];
    if (item.qty > 1) {
        showCustomModal({
            title: "CANTIDAD",
            message: `¿Cuántas de "${item.name}" quitar? (Tienes ${item.qty})`,
            showInput: true,
            onConfirm: (val) => {
                let n = parseInt(val);
                if (n >= item.qty) cart.splice(index, 1);
                else if (n > 0) item.qty -= n;
                updateCartUI();
            }
        });
    } else {
        cart.splice(index, 1);
        updateCartUI();
    }
}

function updateCartUI() {
    const container = document.getElementById('cart-items');
    const countEl = document.getElementById('cart-count');
    const cartSection = document.getElementById('cart-section');
    if (cart.length > 0) cartSection.classList.remove('hidden');
    else cartSection.classList.add('hidden');

    container.innerHTML = '';
    let total = 0; let totalQty = 0;

    cart.forEach((item, index) => {
        const sub = item.price * item.qty;
        total += sub; totalQty += item.qty;
        container.innerHTML += `
            <div class="flex items-center justify-between glass-morphism p-4 rounded-2xl border border-white/10">
                <div class="flex items-center gap-4">
                    <img src="${item.img}" alt="${item.name}" class="w-12 h-14 object-contain bg-black/20 rounded-lg" loading="lazy">
                    <div>
                        <p class="font-bold text-white text-sm">${item.name} (${item.gender})</p>
                        <p class="text-xs text-[#FCD116] font-bold">Talla: ${item.size} · Cant: ${item.qty} · ${money(sub)}</p>
                    </div>
                </div>
                <button onclick="removeFromCart(${index})" class="text-red-500 p-2"><i data-lucide="trash-2"></i></button>
            </div>`;
    });
    document.getElementById('cart-total').textContent = money(total);
    countEl.textContent = totalQty;
    countEl.classList.toggle('hidden', totalQty === 0);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

/* =========================================================
   FINALIZAR PEDIDO POR WHATSAPP
   ========================================================= */
/* =========================================================
   FINALIZAR PEDIDO (Guarda en MySQL y abre WhatsApp)
   ========================================================= */
// Guarda temporalmente el mensaje de WhatsApp ya armado, para abrirlo
// solo cuando el usuario confirme desde el modal de "número de pedido".
let pendingWhatsAppMessage = null;

async function checkoutWhatsApp() {
    const name = document.getElementById('cust-name').value.trim();
    const cedula = document.getElementById('cust-cedula').value.trim();
    const phone = document.getElementById('cust-phone').value.trim();
    const email = document.getElementById('cust-email').value.trim();
    const city = document.getElementById('cust-city').value.trim();
    const address = document.getElementById('cust-address').value.trim();
    const payMethod = document.querySelector('input[name="pay-method"]:checked')?.value || 'Nequi';

    if (cart.length === 0) {
        showCustomModal({ title: "CARRITO VACÍO", message: "Agrega al menos una camiseta antes de finalizar tu pedido." });
        return;
    }

    if (!name || !cedula || !phone || !city || !address) {
        showCustomModal({ title: "DATOS FALTANTES", message: "Por favor completa tu nombre, cédula, celular, ciudad y dirección." });
        return;
    }

    // Estructura de datos para enviar a la API de PHP
    const payload = {
        nombre_cliente: name,
        cedula: cedula,
        telefono: phone,
        email: email,
        ciudad: city,
        direccion: address,
        metodo_pago: payMethod,
        items: cart.map(i => ({
            producto_id: i.id,
            talla: i.size,
            genero: i.gender || itemGender,
            cantidad: i.qty
        }))
    };

    try {
        // Enviar petición POST al backend PHP para registrar en base de datos y descontar stock
        const response = await fetch('/php/api/pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (response.ok && data.ok) {
            // Generar el mensaje de WhatsApp con el número de pedido registrado
            let itemsMsg = "";
            let totalValue = 0;

            cart.forEach(i => {
                const sub = i.price * i.qty;
                totalValue += sub;
                itemsMsg += `✅ JERSEY: ${i.name} (${i.gender})\n   Talla: ${i.size} | Cant: *${i.qty}*\n   Precio Un: *${money(i.price)}* | Subtotal: *${money(sub)}*\n\n`;
            });

            const whatsappMessage = `¡Hola CamiCool! ⚽\n` +
                `He confirmado mi pedido #${data.pedido_id} (Guía: *${data.numero_guia}*) desde la web:\n\n` +
                `👤 *CLIENTE:* ${name}\n` +
                `🪪 *CÉDULA:* ${cedula}\n` +
                `📞 *CELULAR:* ${phone}\n` +
                `📧 *EMAIL:* ${email}\n` +
                `📍 *CIUDAD:* ${city}, Colombia\n` +
                `🏠 *DIRECCIÓN:* *${address}*\n\n` +
                `🛒 *DETALLE DEL PEDIDO:*\n${itemsMsg}` +
                `🔥 *TOTAL FINAL A PAGAR:* *${money(totalValue)}*\n` +
                `💳 *MÉTODO DE PAGO:* ${payMethod}\n\n` +
                `Quedo a la espera para realizar el pago.`;

            // Guardamos el mensaje para abrirlo solo cuando el usuario confirme en el modal
            pendingWhatsAppMessage = whatsappMessage;

            // Limpiar carrito y formulario (el pedido ya quedó guardado en la base de datos)
            cart = [];
            updateCartUI();
            document.getElementById('checkout-form').reset();

            // Mostrar el número de pedido/guía ANTES de ir a WhatsApp, para que el cliente lo copie
            showOrderConfirmModal(data.numero_guia);

        } else {
            showCustomModal({ title: "ERROR EN EL PEDIDO", message: data.error || "No se pudo procesar el inventario." });
        }

    } catch (error) {
        console.error("Error al conectar con PHP:", error);
        showCustomModal({ title: "ERROR DE CONEXIÓN", message: "No se pudo registrar el pedido en la base de datos." });
    }
}

/* =========================================================
   MODAL DE CONFIRMACIÓN DE PEDIDO (número de guía copiable)
   ========================================================= */
function showOrderConfirmModal(numeroGuia) {
    const modal = document.getElementById('order-confirm-modal');
    document.getElementById('order-guia-display').textContent = numeroGuia;
    document.getElementById('order-copy-feedback').classList.add('hidden');
    modal.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function copyGuiaNumber() {
    const text = document.getElementById('order-guia-display').textContent.trim();
    const feedback = document.getElementById('order-copy-feedback');

    const showFeedback = () => {
        feedback.classList.remove('hidden');
        clearTimeout(window._copyFeedbackTimer);
        window._copyFeedbackTimer = setTimeout(() => feedback.classList.add('hidden'), 2500);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showFeedback).catch(() => fallbackCopy(text, showFeedback));
    } else {
        fallbackCopy(text, showFeedback);
    }
}

function fallbackCopy(text, onDone) {
    const tmp = document.createElement('textarea');
    tmp.value = text;
    tmp.style.position = 'fixed';
    tmp.style.opacity = '0';
    document.body.appendChild(tmp);
    tmp.focus();
    tmp.select();
    try { document.execCommand('copy'); } catch (e) { /* noop */ }
    document.body.removeChild(tmp);
    if (onDone) onDone();
}

function proceedToWhatsAppAfterConfirm() {
    document.getElementById('order-confirm-modal').classList.add('hidden');
    if (pendingWhatsAppMessage) {
        window.open(`https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(pendingWhatsAppMessage)}`, '_blank');
        pendingWhatsAppMessage = null;
    }
}
/* =========================================================
   CONEXIÓN CON pagePrincipal/PaginaProductos.html
   Si el usuario compró desde el catálogo principal, su carrito
   viaja en localStorage bajo la llave 'mundialCheckout'.
   ========================================================= */
function importCheckoutFromMainCatalog() {
    let incoming = [];
    try {
        incoming = JSON.parse(localStorage.getItem('mundialCheckout') || '[]');
    } catch (e) { incoming = []; }

    if (!incoming.length) return;

    incoming.forEach(item => {
        const normalized = normalizeTeamName(item.name);
        const product = PRODUCTS.find(p => normalizeTeamName(p.name) === normalized);
        if (product) {
            addToCart(product.id, item.size, item.qty, true);
        }
    });

    localStorage.removeItem('mundialCheckout');
    updateCartUI();
    if (cart.length) {
        showToast('Tu pedido de la tienda principal fue cargado aquí 🛒');
        setTimeout(() => scrollToCart(), 700);
    }
}

function normalizeTeamName(name) {
    return (name || '')
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // quita tildes
        .replace(/estados unidos/g, 'usa')
        .replace(/[^a-z]/g, '');
}

/* =========================================================
   UTILIDADES
   ========================================================= */
function openZoom(src) {
    const m = document.getElementById('zoom-modal');
    document.getElementById('zoom-img').src = src;
    m.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeZoom() {
    document.getElementById('zoom-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
function showToast(msg) {
    const t = document.getElementById('toast');
    document.getElementById('toast-msg').textContent = msg;
    t.classList.remove('hidden');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.classList.add('hidden'), 3500);
}
function scrollToCart() {
    const sec = document.getElementById('cart-section');
    if (sec && cart.length > 0) sec.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

/* =========================================================
   CHATBOT — conectado al backend Flask (app.py)
   ========================================================= */
function toggleChat() {
    const chatWin = document.getElementById('chat-window');
    chatWin.classList.toggle('hidden');
    if (!chatWin.classList.contains('hidden')) {
        document.getElementById('chat-input').focus();
    }
}

async function handleChat() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;

    addChatMessage(message, true);
    input.value = "";

    const typingId = "typing-" + Date.now();
    addChatMessage("Analizando jugada...", false, typingId);

    try {
        const response = await fetch('/chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: message })
        });

        const data = await response.json();
        const typingBubble = document.getElementById(typingId);
        if (typingBubble) typingBubble.remove();

        addChatMessage(data.response, false);

    } catch (error) {
        console.error("Error:", error);
        const typingBubble = document.getElementById(typingId);
        if (typingBubble) typingBubble.remove();
        addChatMessage("¡Uy! Se cortó la señal en el estadio. Porfa, intenta de nuevo o escríbenos al WhatsApp directamente. ⚽");
    }
}

function addChatMessage(text, isUser = false, id = null) {
    const container = document.getElementById('chat-messages');
    const div = document.createElement('div');
    if (id) div.id = id;

    div.className = `p-3 rounded-2xl max-w-[85%] animate-pop shadow-lg mb-2 ${isUser
        ? "bg-[#003893] self-end ml-auto rounded-tr-none text-white border border-white/10"
        : "bg-white/10 self-start mr-auto rounded-tl-none border border-white/20 text-gray-100"}`;

    div.style.whiteSpace = "pre-wrap";
    div.style.wordBreak = "break-word";

    const urlRegex = /(https?:\/\/[^\s]+)/g;
    const formattedText = text.replace(urlRegex, (url) => {
        return `<a href="${url}" target="_blank" class="text-[#FCD116] underline font-bold hover:text-white transition-colors">${url}</a>`;
    });

    div.innerHTML = formattedText;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

document.getElementById('chat-input')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') handleChat();
});

/* =========================================================
   INICIALIZACIÓN
   ========================================================= */
document.addEventListener('DOMContentLoaded', () => {
    renderCatalog();
    updateCartUI();
    updateNavGenderUI();
    importCheckoutFromMainCatalog();
    if (typeof lucide !== 'undefined') lucide.createIcons();
});