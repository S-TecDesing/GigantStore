-- =============================================================================
-- migracion_2026.sql
-- Migración para bases de datos "camicool" QUE YA EXISTÍAN en phpMyAdmin
-- antes de las nuevas funciones (rastreo de pedido + cédula).
--
-- Es seguro ejecutar este archivo completo de una sola vez desde la pestaña
-- "SQL" de phpMyAdmin. No borra ni modifica los pedidos que ya tienes.
-- =============================================================================

USE camicool;

-- -----------------------------------------------------------------------
-- 1) Asegurar que exista la tabla pedido_items (por si tu base es antigua
--    y no la tenía). Aquí se relacionan: pedido -> camiseta -> talla ->
--    cantidad -> precio. Si ya existe, esta línea no hace nada.
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedido_items (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id        INT NOT NULL,
    producto_id      VARCHAR(20) NOT NULL,
    talla            ENUM('S','M','L','XL') NOT NULL,
    genero           ENUM('Masculino','Femenino') NOT NULL,
    cantidad         INT UNSIGNED NOT NULL,
    precio_unitario  INT UNSIGNED NOT NULL,
    subtotal         INT UNSIGNED NOT NULL,
    FOREIGN KEY (pedido_id)   REFERENCES pedidos(id)   ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------
-- 2) Agregar las columnas nuevas a la tabla `pedidos` YA EXISTENTE.
--    - numero_guia: el código que el cliente usará para rastrear su pedido
--    - cedula: queda GUARDADA EN LA MISMA FILA del pedido, por lo tanto
--      numero_guia y cedula quedan relacionados automáticamente entre sí
--      y con la compra (mismo id de pedido, mismos items, misma ciudad).
--
-- NOTA: si tu servidor de MySQL/MariaDB es antiguo y estas líneas fallan
-- con "Duplicate column name", significa que esa columna ya existe:
-- simplemente ignora ese error puntual y sigue con la siguiente línea.
-- -----------------------------------------------------------------------
ALTER TABLE pedidos ADD COLUMN numero_guia VARCHAR(30) NULL AFTER id;
ALTER TABLE pedidos ADD COLUMN cedula VARCHAR(20) NOT NULL DEFAULT '' AFTER nombre_cliente;

-- Índice único: evita que se generen dos pedidos con el mismo número de guía
-- (NULL no cuenta como duplicado, así que los pedidos viejos sin guía no dan error)
ALTER TABLE pedidos ADD UNIQUE KEY uq_numero_guia (numero_guia);

-- Índice de búsqueda rápida por guía + cédula (lo que usa la página de rastreo)
ALTER TABLE pedidos ADD INDEX idx_guia_cedula (numero_guia, cedula);

-- -----------------------------------------------------------------------
-- 3) (Opcional pero recomendado) Generar un número de guía retroactivo
--    para los pedidos ANTIGUOS que ya tenías, para que también se puedan
--    buscar por número de guía. Esto NO les asigna cédula (no la tenías
--    guardada antes), así que esos pedidos viejos solo podrán rastrearse
--    si además editas manualmente la cédula de esos pedidos (paso 4).
-- -----------------------------------------------------------------------
UPDATE pedidos
SET numero_guia = CONCAT('CMC-', LPAD(id, 6, '0'), '-', UPPER(SUBSTRING(MD5(RAND()), 1, 4)))
WHERE numero_guia IS NULL;

-- -----------------------------------------------------------------------
-- 4) VISTA para visualizar fácilmente TODO relacionado en una sola tabla
--    desde phpMyAdmin: pedido, cédula, guía, camiseta, talla, cantidad,
--    precio y ciudad de destino. Entra a phpMyAdmin -> camicool -> busca
--    la tabla "vista_pedidos_completos" en el listado y dale clic en
--    "Examinar" para ver todo junto, sin escribir SQL.
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
-- 5) Verificación rápida: corre estas dos consultas para confirmar que
--    todo quedó bien (puedes borrarlas después, son solo de chequeo).
-- -----------------------------------------------------------------------
-- ¿Quedaron las columnas nuevas en pedidos?
-- DESCRIBE pedidos;

-- ¿Se ve el detalle completo por pedido?
-- SELECT * FROM vista_pedidos_completos LIMIT 20;
