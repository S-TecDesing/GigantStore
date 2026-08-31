-- =============================================================================
-- fix_collation_view.sql
-- Corrección puntual para el error:
-- #1267 - Ilegal mezcla de collations (utf8mb4_general_ci,IMPLICIT) y
--         (utf8mb4_unicode_ci,IMPLICIT) para operación '='
--
-- Causa: la columna `productos.id` y la columna `pedido_items.producto_id`
-- se crearon en momentos distintos con una configuración de texto (collation)
-- diferente entre sí. Para compararlas en el JOIN de la vista, forzamos
-- explícitamente que ambas se comparen con la misma collation.
--
-- Esto NO modifica tus tablas ni tus datos, solo ajusta cómo se comparan
-- al momento de leer la vista.
-- =============================================================================

USE camicool;

CREATE OR REPLACE VIEW vista_pedidos_completos AS
SELECT
    p.id                AS pedido_id,
    p.numero_guia,
    p.cedula,
    p.nombre_cliente,
    p.telefono,
    p.email,
    p.ciudad            AS ciudad_destino,
    p.direccion,
    p.metodo_pago,
    p.estado,
    p.creado_en          AS fecha_pedido,
    pi.talla,
    pi.genero,
    pi.cantidad,
    pi.precio_unitario,
    pi.subtotal,
    pr.nombre           AS camiseta
FROM pedidos p
INNER JOIN pedido_items pi
    ON pi.pedido_id = p.id
INNER JOIN productos pr
    ON pr.id COLLATE utf8mb4_unicode_ci = pi.producto_id COLLATE utf8mb4_unicode_ci
ORDER BY p.creado_en DESC;

-- Verificación
SELECT * FROM vista_pedidos_completos LIMIT 20;
