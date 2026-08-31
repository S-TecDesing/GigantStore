<?php
/**
 * api/productos.php
 * API REST (JSON) de solo lectura para el catalogo de camisetas.
 *
 * GET /api/productos.php                -> lista completa
 * GET /api/productos.php?id=brasil      -> un solo producto
 * GET /api/productos.php?categoria=copa -> filtra por categoria (mundial|copa|eurocopa)
 * GET /api/productos.php?buscar=brasil  -> filtra por nombre (like)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = obtenerConexion();

    $id        = $_GET['id'] ?? null;
    $categoria = $_GET['categoria'] ?? null;
    $buscar    = $_GET['buscar'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM productos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Producto no encontrado'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $producto['categorias'] = obtenerCategorias($pdo, $producto['id']);
        echo json_encode(['ok' => true, 'producto' => $producto], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = 'SELECT DISTINCT p.* FROM productos p';
    $params = [];

    if ($categoria && $categoria !== 'todas') {
        $sql .= ' INNER JOIN producto_categoria pc ON pc.producto_id = p.id
                  INNER JOIN categorias c ON c.id = pc.categoria_id
                  WHERE c.slug = :categoria';
        $params['categoria'] = $categoria;
    }

    if ($buscar) {
        $sql .= ($params ? ' AND' : ' WHERE') . ' p.nombre LIKE :buscar';
        $params['buscar'] = '%' . $buscar . '%';
    }

    $sql .= ' ORDER BY p.nombre ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    foreach ($productos as &$p) {
        $p['categorias'] = obtenerCategorias($pdo, $p['id']);
    }

    echo json_encode(['ok' => true, 'total' => count($productos), 'productos' => $productos], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error de base de datos', 'detalle' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

function obtenerCategorias(PDO $pdo, string $productoId): array
{
    $stmt = $pdo->prepare('
        SELECT c.slug FROM categorias c
        INNER JOIN producto_categoria pc ON pc.categoria_id = c.id
        WHERE pc.producto_id = :pid
    ');
    $stmt->execute(['pid' => $productoId]);
    return array_column($stmt->fetchAll(), 'slug');
}
