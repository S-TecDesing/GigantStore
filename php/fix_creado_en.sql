-- =============================================================================
-- fix_creado_en.sql
-- Corrección puntual para el error:
-- #1054 - La columna 'p.creado_en' en field list es desconocida
--
-- Esto significa que tu tabla `pedidos` (la que ya existía en producción)
-- no tiene una columna para guardar la fecha del pedido. La agregamos ahora.
-- =============================================================================

USE camicool;

-- 1) Agregar la columna de fecha que falta.
--    DEFAULT CURRENT_TIMESTAMP hace que los pedidos NUEVOS guarden la fecha
--    automáticamente. Los pedidos VIEJOS que ya tenías quedarán con la fecha
--    del momento en que ejecutes este script (no hay forma de recuperar la
--    fecha real si nunca se guardó).
ALTER TABLE pedidos ADD COLUMN creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Si al ejecutar la línea de arriba te sale el error "Duplicate column name
-- 'creado_en'", significa que la columna SÍ existe pero con mayúsculas u
-- otro nombre parecido -> avísame el nombre exacto (con DESCRIBE pedidos;)
-- y ajusto el script para usar ese nombre en vez de crear uno nuevo.

-- -----------------------------------------------------------------------
-- 2) Recrear la vista, ahora que la columna ya existe.
--    (Si ya la habías creado antes, CREATE OR REPLACE la actualiza sin problema)
-- -----------------------------------------------------------------------
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

-- -----------------------------------------------------------------------
-- 3) Verificación: ¿ya se ve todo junto?
-- -----------------------------------------------------------------------
SELECT * FROM vista_pedidos_completos LIMIT 20;
