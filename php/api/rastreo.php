<?php
/**
 * api/rastreo.php
 * Endpoint de solo lectura para que un cliente rastree su pedido.
 *
 * Por seguridad, exige DOS datos que solo el dueño del pedido conoce:
 * el número de guía (entregado al finalizar la compra) y su cédula.
 * Así evitamos que cualquiera pueda consultar pedidos ajenos solo con
 * probar números de guía consecutivos.
 *
 * GET/POST /api/rastreo.php?guia=CMC-000123-A2F9&cedula=1020304050
 *
 * NOTA IMPORTANTE: CamiCool no tiene integración con una transportadora
 * real (Servientrega, Coordinadora, etc.). El estado de envío que se
 * devuelve aquí es una LÍNEA DE TIEMPO SIMULADA calculada a partir de la
 * hora en que se registró el pedido, pensada para dar información
 * orientativa al cliente con el mismo formato visual que usan las
 * transportadoras (ciudad, hora, estado). Si en el futuro se integra una
 * transportadora real, este archivo es el único que debe cambiar.
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
header("Content-Type: application/json; charset=UTF-8");

try {
    require_once __DIR__ . '/../config/database.php';

    $guia   = trim($_GET['guia']   ?? $_POST['guia']   ?? '');
    $cedula = trim($_GET['cedula'] ?? $_POST['cedula'] ?? '');
    $cedula = preg_replace('/\D/', '', $cedula);

    if (empty($guia) || empty($cedula)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Debes ingresar el número de guía y la cédula con la que hiciste el pedido."]);
        exit;
    }

    $pdo = obtenerConexion();

    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE numero_guia = ? AND cedula = ? LIMIT 1");
    $stmt->execute([$guia, $cedula]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(["ok" => false, "error" => "No encontramos ningún pedido con ese número de guía y esa cédula. Verifica los datos e intenta de nuevo."]);
        exit;
    }

    // Ítems del pedido: nombre de la camiseta (join con productos), talla, cantidad y precio
    // NOTA: se fuerza la misma collation en ambos lados del JOIN porque las tablas
    // `productos` y `pedido_items` quedaron con collations de texto distintas
    // (utf8mb4_general_ci vs utf8mb4_unicode_ci), igual que se corrigió en la vista
    // vista_pedidos_completos de phpMyAdmin.
    $stmtItems = $pdo->prepare("
        SELECT pi.talla, pi.genero, pi.cantidad, pi.precio_unitario, pi.subtotal, p.nombre AS nombre_producto
        FROM pedido_items pi
        INNER JOIN productos p ON p.id COLLATE utf8mb4_unicode_ci = pi.producto_id COLLATE utf8mb4_unicode_ci
        WHERE pi.pedido_id = ?
        ORDER BY pi.id ASC
    ");
    $stmtItems->execute([$pedido['id']]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // ------------------------------------------------------------------
    // Línea de tiempo simulada de envío, estilo transportadora oficial.
    // Se calcula según las horas transcurridas desde que se creó el pedido.
    // ------------------------------------------------------------------
    $creadoTs = strtotime($pedido['creado_en']);
    $horasTranscurridas = (time() - $creadoTs) / 3600;
    $ciudadOrigen  = 'Bogotá D.C. (Bodega CamiCool)';
    $ciudadDestino = $pedido['ciudad'];

    $etapas = [
        [
            'titulo'      => 'Pedido confirmado',
            'ciudad'      => $ciudadOrigen,
            'desde_horas' => 0,
            'descripcion' => 'Registramos tu pedido y confirmamos el pago/método de entrega.',
        ],
        [
            'titulo'      => 'En bodega de despacho',
            'ciudad'      => $ciudadOrigen,
            'desde_horas' => 3,
            'descripcion' => 'Tu(s) camiseta(s) fueron verificadas, empacadas con cuidado y quedaron listas para salir.',
        ],
        [
            'titulo'      => 'En tránsito',
            'ciudad'      => 'Centro de distribución regional',
            'desde_horas' => 20,
            'descripcion' => 'Tu paquete va en camino hacia ' . $ciudadDestino . '. El empaque se encuentra en buen estado.',
        ],
        [
            'titulo'      => 'En reparto',
            'ciudad'      => $ciudadDestino,
            'desde_horas' => 48,
            'descripcion' => 'Un mensajero local tiene tu paquete y lo entregará hoy en la dirección registrada.',
        ],
        [
            'titulo'      => 'Entregado',
            'ciudad'      => $ciudadDestino,
            'desde_horas' => 72,
            'descripcion' => 'Tu pedido fue entregado en buen estado en la dirección registrada. ¡Gracias por comprar en CamiCool!',
        ],
    ];

    $timeline = [];
    $etapaActual = $etapas[0];

    foreach ($etapas as $etapa) {
        $completado = $horasTranscurridas >= $etapa['desde_horas'];
        if ($completado) {
            $etapaActual = $etapa;
        }
        $timeline[] = [
            'titulo'      => $etapa['titulo'],
            'ciudad'      => $etapa['ciudad'],
            'descripcion' => $etapa['descripcion'],
            'fecha'       => date('d/m/Y h:i A', $creadoTs + ($etapa['desde_horas'] * 3600)),
            'completado'  => $completado,
        ];
    }

    echo json_encode([
        "ok" => true,
        "numero_guia"    => $pedido['numero_guia'],
        "estado_actual"  => $etapaActual['titulo'],
        "ciudad_actual"  => $etapaActual['ciudad'],
        "ciudad_destino" => $ciudadDestino,
        "fecha_pedido"   => date('d/m/Y h:i A', $creadoTs),
        "total"          => (int)$pedido['total'],
        "cantidad_items" => (int)array_sum(array_column($items, 'cantidad')),
        "items"          => $items,
        "timeline"       => $timeline,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => "Error al consultar el pedido."]);
}
