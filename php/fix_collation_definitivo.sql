-- =============================================================================
-- fix_collation_definitivo.sql
-- Solución de raíz para el error #1267 (mezcla de collations).
--
-- En vez de forzar COLLATE en cada consulta que una `productos` con
-- `pedido_items`, esto normaliza la collation de las columnas involucradas
-- para que TODAS las consultas futuras (la vista, el rastreo, o cualquier
-- reporte nuevo que hagas) funcionen sin necesidad de trucos adicionales.
--
-- Es seguro ejecutarlo: no borra datos, solo ajusta la configuración de
-- comparación de texto de esas columnas puntuales.
-- =============================================================================

USE camicool;

-- 1) Diagnóstico: confirma qué collation tiene cada columna ANTES del fix
--    (puedes correr esto primero para ver la diferencia, es solo informativo)
SELECT table_name, column_name, character_set_name, collation_name
FROM information_schema.columns
WHERE table_schema = 'camicool'
  AND ((table_name = 'productos'     AND column_name = 'id')
    OR (table_name = 'pedido_items'  AND column_name = 'producto_id'));

-- 2) Normalizar ambas columnas a la misma collation (utf8mb4_unicode_ci)
ALTER TABLE productos     MODIFY id          VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci;
ALTER TABLE pedido_items  MODIFY producto_id VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci;

-- 3) Confirmación: ya deberían coincidir
SELECT table_name, column_name, character_set_name, collation_name
FROM information_schema.columns
WHERE table_schema = 'camicool'
  AND ((table_name = 'productos'     AND column_name = 'id')
    OR (table_name = 'pedido_items'  AND column_name = 'producto_id'));

-- 4) Ahora la vista puede quedar simple, sin necesidad de COLLATE explícito
--    (esto es opcional, la versión con COLLATE también seguirá funcionando)
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
INNER JOIN pedido_items pi ON pi.pedido_id = p.id
INNER JOIN productos pr    ON pr.id = pi.producto_id
ORDER BY p.creado_en DESC;

-- 5) Verificación final: ya debería mostrar el nombre real de la camiseta
--    en la columna "camiseta" (no el código "brasil"/"italia"/"usa")
SELECT * FROM vista_pedidos_completos ORDER BY pedido_id DESC LIMIT 10;
