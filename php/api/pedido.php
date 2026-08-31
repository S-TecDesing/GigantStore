<?php
// Forzar reporte de errores para depuración interna sin romper el JSON
error_reporting(E_ALL);
ini_set('display_errors', '0');

header("Content-Type: application/json; charset=UTF-8");

try {
    require_once __DIR__ . '/../config/database.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["ok" => false, "error" => "Método no permitido"]);
        exit;
    }

    $rawInput = file_get_contents("php://input");
    $input = json_decode($rawInput, true);

    if (!$input || empty($input['items'])) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Datos de pedido incompletos o JSON inválido"]);
        exit;
    }

    $nombre    = trim($input['nombre_cliente'] ?? '');
    $cedula    = trim($input['cedula'] ?? '');
    $telefono  = trim($input['telefono'] ?? '');
    $email     = trim($input['email'] ?? '');
    $ciudad    = trim($input['ciudad'] ?? '');
    $direccion = trim($input['direccion'] ?? '');
    $pago      = trim($input['metodo_pago'] ?? 'Nequi');
    $items     = $input['items'];

    if (empty($nombre) || empty($cedula) || empty($telefono) || empty($ciudad) || empty($direccion)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "Faltan campos obligatorios de envío (nombre, cédula, celular, ciudad o dirección)"]);
        exit;
    }

    // La cédula solo debe contener números (se limpia cualquier punto o espacio que el usuario escriba)
    $cedula = preg_replace('/\D/', '', $cedula);
    if (strlen($cedula) < 5) {
        http_response_code(400);
        echo json_encode(["ok" => false, "error" => "La cédula ingresada no es válida"]);
        exit;
    }

    $pdo = obtenerConexion();
    $pdo->beginTransaction();

    $totalCalculado = 0;
    $detallesValidados = [];

    foreach ($items as $item) {
        $productoId = $item['producto_id'] ?? '';
        $cantidad   = (int)($item['cantidad'] ?? 1);
        $talla      = $item['talla'] ?? 'M';
        $genero     = $item['genero'] ?? 'Masculino';

        $stmt = $pdo->prepare("SELECT id, precio, stock FROM productos WHERE id = ?");
        $stmt->execute([$productoId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            throw new Exception("El producto con ID '$productoId' no existe en la base de datos.");
        }

        if ((int)$prod['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente para el producto: " . $prod['id']);
        }

        $precioUnitario = (float)$prod['precio'];
        $subtotal = $precioUnitario * $cantidad;
        $totalCalculado += $subtotal;

        $detallesValidados[] = [
            'producto_id' => $productoId,
            'talla' => $talla,
            'genero' => $genero,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal
        ];
    }

    // Insertar pedido principal (verificando que existan las columnas en la tabla pedidos)
    $stmtPedido = $pdo->prepare("
        INSERT INTO pedidos (nombre_cliente, cedula, telefono, email, ciudad, direccion, metodo_pago, total, estado)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')
    ");
    $stmtPedido->execute([$nombre, $cedula, $telefono, $email, $ciudad, $direccion, $pago, $totalCalculado]);
    $pedidoId = $pdo->lastInsertId();

    // Generar el número de guía / número de pedido único que el cliente
    // usará después para rastrear su envío (ej: CMC-000123-A2F9)
    $numeroGuia = 'CMC-' . str_pad((string)$pedidoId, 6, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));
    $stmtGuia = $pdo->prepare("UPDATE pedidos SET numero_guia = ? WHERE id = ?");
    $stmtGuia->execute([$numeroGuia, $pedidoId]);

    // Insertar ítems y descontar stock
    foreach ($detallesValidados as $val) {
        $stmtItem = $pdo->prepare("
            INSERT INTO pedido_items (pedido_id, producto_id, talla, genero, cantidad, precio_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtItem->execute([
            $pedidoId,
            $val['producto_id'],
            $val['talla'],
            $val['genero'],
            $val['cantidad'],
            $val['precio_unitario'],
            $val['subtotal']
        ]);

        $stmtStock = $pdo->prepare("UPDATE productos SET stock = GREATEST(0, stock - ?) WHERE id = ?");
        $stmtStock->execute([$val['cantidad'], $val['producto_id']]);
    }

    $pdo->commit();

    echo json_encode([
        "ok" => true,
        "pedido_id" => (int)$pedidoId,
        "numero_guia" => $numeroGuia,
        "total" => $totalCalculado,
        "mensaje" => "Pedido registrado exitosamente."
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        "ok" => false, 
        "error" => $e->getMessage()
    ]);
}