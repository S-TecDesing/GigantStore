<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = obtenerConexion();
    
    // Filtrar por categoría si viene en la URL (?categoria=slug)
    $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
    
    if (!empty($categoria)) {
        $sql = "
            SELECT p.* 
            FROM productos p
            INNER JOIN producto_categoria pc ON p.id = pc.producto_id
            INNER JOIN categorias c ON pc.categoria_id = c.id
            WHERE c.slug = :categoria
            ORDER BY p.id ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':categoria' => $categoria]);
    } else {
        $sql = "SELECT * FROM productos ORDER BY id ASC";
        $stmt = $pdo->query($sql);
    }
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categorías disponibles para el menú de filtros
    $stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY id ASC");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CamiCool - Tienda Oficial & Catálogo</title>
    <link rel="stylesheet" href="../../css/principal.css">
    <link rel="stylesheet" href="../../css/productos.css">
    <link rel="stylesheet" href="../../css/carrito.css">
    <style>
        /* Ajustes específicos para unificar la vista PHP */
        .catalogo-grid-php {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .tarjeta-camisa {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .tarjeta-camisa:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .tarjeta-img-box {
            width: 100%;
            height: 220px;
            background: radial-gradient(circle, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 1rem;
            position: relative;
        }

        .tarjeta-img-box img {
            max-width: 85%;
            max-height: 85%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .tarjeta-camisa:hover .tarjeta-img-box img {
            transform: scale(1.08);
        }

        .stock-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(15, 23, 42, 0.85);
            color: #ffffff;
            font-size: 0.72rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .filtros-categorias {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .btn-filtro {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-filtro.active, .btn-filtro:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        /* Carrito Flotante Lateral */
        .cart-toggle-btn {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 0.85rem 1.4rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
            z-index: 99;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .drawer-carrito {
            position: fixed;
            top: 0;
            right: -420px;
            width: 100%;
            max-width: 400px;
            height: 100%;
            background: #ffffff;
            box-shadow: -4px 0 30px rgba(0,0,0,0.15);
            z-index: 100;
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
        }

        .drawer-carrito.open {
            right: 0;
        }

        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.4);
            z-index: 99;
            display: none;
        }

        .drawer-overlay.active {
            display: block;
        }

        .cart-items-list {
            flex: 1;
            overflow-y: auto;
            margin: 1rem 0;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .cart-item-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.75rem;
        }

        .cart-item-row img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            background: #f8fafc;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <a href="index.php">
                <img src="../../img/comunes/logo.png" alt="Logo CamiCool" class="logo">
            </a>
            <h1>CamiCool</h1>
        </div>
        <nav class="nav">
            <a href="../../html/index.html">Inicio</a>
            <a href="index.php" class="active">Catálogo MySQL</a>
            <a href="javascript:void(0)" onclick="toggleCarrito(true)">Carrito (<span id="cartCount">0</span>)</a>
        </nav>
    </header>

    <main class="catalogo-main">
        <section class="catalogo-header">
            <h2>Catálogo de Camisetas Oficiales</h2>
            <p>Modelos sincronizados en tiempo real con la base de datos MySQL.</p>
        </section>

        <!-- Filtros de categoría desde la BD -->
        <div class="filtros-categorias">
            <a href="index.php" class="btn-filtro <?= empty($categoria) ? 'active' : '' ?>">Todas</a>
            <?php foreach ($categorias as $c): ?>
                <a href="index.php?categoria=<?= urlencode($c['slug']) ?>" 
                   class="btn-filtro <?= $categoria === $c['slug'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($c['nombre']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Grid de Productos -->
        <div class="catalogo-grid-php">
            <?php foreach ($productos as $p): ?>
                <?php 
                    $rutaImagen = "../../" . ltrim($p['img_frente'], '/');
                ?>
                <article class="tarjeta-camisa">
                    <div class="tarjeta-img-box">
                        <span class="stock-badge">Stock: <?= (int)$p['stock'] ?></span>
                        <img src="<?= htmlspecialchars($rutaImagen) ?>" 
                             alt="Camiseta <?= htmlspecialchars($p['nombre']) ?>"
                             onerror="this.src='../../img/comunes/logo.png'">
                    </div>
                    
                    <div class="tarjeta-info">
                        <span class="tag-calidad"><?= htmlspecialchars($p['tela_titulo'] ?? 'TELA PREMIUM') ?></span>
                        <h3 style="font-size: 1.1rem; color: #0f172a; margin: 0.4rem 0;"><?= htmlspecialchars($p['nombre']) ?></h3>
                        
                        <div class="precio-box" style="margin-bottom: 0.8rem;">
                            <span class="precio-actual">$<?= number_format($p['precio'], 0, ',', '.') ?> COP</span>
                            <?php if (!empty($p['precio_anterior'])): ?>
                                <span class="precio-antes">$<?= number_format($p['precio_anterior'], 0, ',', '.') ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Selector de talla y género -->
                        <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                            <select id="talla_<?= $p['id'] ?>" style="flex: 1; padding: 0.35rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <option value="S">Talla S</option>
                                <option value="M" selected>Talla M</option>
                                <option value="L">Talla L</option>
                                <option value="XL">Talla XL</option>
                            </select>
                            <select id="genero_<?= $p['id'] ?>" style="flex: 1; padding: 0.35rem; border-radius: 6px; border: 1px solid #cbd5e1;">
                                <option value="Masculino" selected>Hombre</option>
                                <option value="Femenino">Mujer</option>
                            </select>
                        </div>

                        <button class="btn-accion btn-agregar" 
                                onclick="agregarAlCarrito(<?= htmlspecialchars(json_encode($p)) ?>)">
                            Añadir al Carrito
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- Botón flotante para abrir carrito -->
    <button class="cart-toggle-btn" onclick="toggleCarrito(true)">
        🛒 Ver Carrito (<span id="btnCartQty">0</span>)
    </button>

    <!-- Drawer Lateral del Carrito -->
    <div class="drawer-overlay" id="drawerOverlay" onclick="toggleCarrito(false)"></div>
    <aside class="drawer-carrito" id="drawerCarrito">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.2rem; color: #0f172a; margin: 0;">Tu Carrito</h3>
            <button onclick="toggleCarrito(false)" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>

        <div class="cart-items-list" id="cartItemsList">
            <!-- Renderizado por JS -->
        </div>

        <div style="border-top: 1px solid #f1f5f9; padding-top: 1rem;">
            <div style="display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem;">
                <span>Total:</span>
                <span id="cartTotal">$0 COP</span>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <input type="text" id="clienteNombre" placeholder="Tu Nombre y Apellido" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 0.5rem; box-sizing: border-box;">
                <input type="text" inputmode="numeric" id="clienteCedula" placeholder="Número de cédula" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 0.5rem; box-sizing: border-box;">
                <input type="tel" id="clienteTelefono" placeholder="Celular (10 dígitos)" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 0.5rem; box-sizing: border-box;">
                <input type="email" id="clienteEmail" placeholder="Correo electrónico" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 0.5rem; box-sizing: border-box;">
                <input type="text" id="clienteCiudad" placeholder="Ciudad de destino" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 0.5rem; box-sizing: border-box;">
                <input type="text" id="clienteDireccion" placeholder="Dirección de entrega" style="width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
                <p style="font-size: 0.72rem; color: #94a3b8; margin-top: 0.4rem;">🔒 Tu cédula solo se usa para identificar tu pedido y permitirte rastrearlo después.</p>
            </div>

            <button class="btn-accion btn-ir-comprar" onclick="procesarPedido()">Finalizar Pedido</button>
        </div>
    </aside>

    <footer class="footer">
        <p>&copy; 2026 CamiCool. Todos los derechos reservados.</p>
    </footer>

    <script>
        let carrito = [];

        function toggleCarrito(abrir) {
            document.getElementById('drawerCarrito').classList.toggle('open', abrir);
            document.getElementById('drawerOverlay').classList.toggle('active', abrir);
        }

        function agregarAlCarrito(producto) {
            const talla = document.getElementById(`talla_${producto.id}`).value;
            const genero = document.getElementById(`genero_${producto.id}`).value;
            
            const itemExistente = carrito.find(item => item.id === producto.id && item.talla === talla && item.genero === genero);
            
            if (itemExistente) {
                itemExistente.cantidad += 1;
            } else {
                carrito.push({
                    id: producto.id,
                    nombre: producto.nombre,
                    precio: parseFloat(producto.precio),
                    img: producto.img_frente,
                    talla: talla,
                    genero: genero,
                    cantidad: 1
                });
            }
            
            actualizarUI();
            toggleCarrito(true);
        }

        function cambiarCantidad(index, delta) {
            carrito[index].cantidad += delta;
            if (carrito[index].cantidad <= 0) {
                carrito.splice(index, 1);
            }
            actualizarUI();
        }

        function actualizarUI() {
            const lista = document.getElementById('cartItemsList');
            const totalEl = document.getElementById('cartTotal');
            const countEl = document.getElementById('cartCount');
            const btnQtyEl = document.getElementById('btnCartQty');

            lista.innerHTML = '';
            let total = 0;
            let totalItems = 0;

            if (carrito.length === 0) {
                lista.innerHTML = '<p style="text-align: center; color: #94a3b8; margin-top: 2rem;">El carrito está vacío.</p>';
            } else {
                carrito.forEach((item, idx) => {
                    const sub = item.precio * item.cantidad;
                    total += sub;
                    totalItems += item.cantidad;

                    const row = document.createElement('div');
                    row.className = 'cart-item-row';
                    row.innerHTML = `
                        <img src="../../${item.img.replace(/^\\//, '')}" onerror="this.src='../../img/comunes/logo.png'">
                        <div style="flex: 1;">
                            <strong style="font-size: 0.85rem; color: #1e293b; display: block;">${item.nombre}</strong>
                            <span style="font-size: 0.75rem; color: #64748b;">${item.talla} | ${item.genero}</span>
                            <div style="display: flex; align-items: center; gap: 0.4rem; margin-top: 0.3rem;">
                                <button class="btn-qty" onclick="cambiarCantidad(${idx}, -1)">-</button>
                                <span style="font-size: 0.85rem; font-weight: 700;">${item.cantidad}</span>
                                <button class="btn-qty" onclick="cambiarCantidad(${idx}, 1)">+</button>
                            </div>
                        </div>
                        <span style="font-size: 0.85rem; font-weight: 700; color: #0f172a;">$${sub.toLocaleString('es-CO')}</span>
                    `;
                    lista.appendChild(row);
                });
            }

            totalEl.textContent = `$${total.toLocaleString('es-CO')} COP`;
            countEl.textContent = totalItems;
            btnQtyEl.textContent = totalItems;
        }

        async function procesarPedido() {
            if (carrito.length === 0) {
                alert("Agrega al menos una camiseta antes de finalizar.");
                return;
            }

            const nombre = document.getElementById('clienteNombre').value.trim();
            const cedula = document.getElementById('clienteCedula').value.trim();
            const telefono = document.getElementById('clienteTelefono').value.trim();
            const email = document.getElementById('clienteEmail').value.trim();
            const ciudad = document.getElementById('clienteCiudad').value.trim();
            const direccion = document.getElementById('clienteDireccion').value.trim();

            if (!nombre || !cedula || !telefono || !ciudad || !direccion) {
                alert("Por favor completa nombre, cédula, celular, ciudad y dirección.");
                return;
            }

            const payload = {
                nombre_cliente: nombre,
                cedula: cedula,
                telefono: telefono,
                email: email,
                ciudad: ciudad,
                direccion: direccion,
                items: carrito.map(item => ({
                    producto_id: item.id,
                    talla: item.talla,
                    genero: item.genero,
                    cantidad: item.cantidad
                }))
            };

            try {
                const res = await fetch('../api/pedido.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (res.ok && data.ok) {
                    alert(`🎉 ¡Pedido realizado con éxito!\n\nTu número de pedido / guía es:\n${data.numero_guia}\n\n(Guárdalo para rastrear tu envío)\n\nTotal: $${data.total.toLocaleString('es-CO')} COP\nSe ha descontado del stock en la base de datos.`);
                    carrito = [];
                    actualizarUI();
                    toggleCarrito(false);
                    location.reload(); // Recargar para ver el nuevo stock
                } else {
                    alert(`Error al procesar el pedido: ${data.error || 'Verifica el stock disponible'}`);
                }
            } catch (err) {
                alert("Hubo un problema de conexión con el servidor.");
            }
        }
    </script>
</body>
</html>