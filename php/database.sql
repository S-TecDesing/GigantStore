-- database.sql
-- Esquema y datos iniciales (seed) de la tienda CamiCool.
-- Genera la base de datos completa: productos, categorias, clientes y pedidos.
-- Uso: mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS camicool CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE camicool;

-- ---------------------------------------------------------------------------
-- Tabla: productos
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
    id              VARCHAR(20)     NOT NULL PRIMARY KEY,
    nombre          VARCHAR(60)     NOT NULL,
    bandera_emoji   VARCHAR(10)     NOT NULL,
    ranking         VARCHAR(60)     NOT NULL,
    titulos         VARCHAR(120)    NOT NULL,
    descripcion     TEXT            NOT NULL,
    tecnologia      TEXT            NOT NULL,
    tela_titulo     VARCHAR(60)     NOT NULL,
    tela_subtitulo  VARCHAR(60)     NOT NULL,
    precio          INT UNSIGNED    NOT NULL,
    precio_anterior INT UNSIGNED    NOT NULL,
    descuento       VARCHAR(10)     NOT NULL,
    stock           INT UNSIGNED    NOT NULL DEFAULT 0,
    historia        TEXT            NOT NULL,
    img_frente      VARCHAR(160)    NOT NULL,
    img_atras       VARCHAR(160)    NOT NULL,
    creado_en       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Tabla: categorias  (mundial / copa / eurocopa)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    slug   VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS producto_categoria (
    producto_id   VARCHAR(20) NOT NULL,
    categoria_id  INT         NOT NULL,
    PRIMARY KEY (producto_id, categoria_id),
    FOREIGN KEY (producto_id)  REFERENCES productos(id)  ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Tabla: clientes
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clientes (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(120) NOT NULL,
    telefono  VARCHAR(30)  NOT NULL,
    email     VARCHAR(150) NULL,
    ciudad    VARCHAR(80)  NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    pais      VARCHAR(60)  NOT NULL DEFAULT 'Colombia',
    creado_en TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Tabla: pedidos
-- NOTA: esta tabla guarda directamente los datos de envío del cliente
-- (así es como los usa realmente php/api/pedido.php), incluyendo ahora la
-- cédula del comprador y el número de guía/pedido que se genera al crear
-- el pedido y que el cliente usa después para rastrear su envío.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    numero_guia     VARCHAR(30)     NULL,
    nombre_cliente  VARCHAR(120)    NOT NULL,
    cedula          VARCHAR(20)     NOT NULL,
    telefono        VARCHAR(30)     NOT NULL,
    email           VARCHAR(150)    NULL,
    ciudad          VARCHAR(80)     NOT NULL,
    direccion       VARCHAR(200)    NOT NULL,
    metodo_pago     ENUM('Nequi','Daviplata','Tarjeta','Contra Entrega') NOT NULL,
    total           INT UNSIGNED    NOT NULL,
    estado          ENUM('pendiente','confirmado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    creado_en       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_numero_guia (numero_guia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- MIGRACIÓN: si tu base de datos "camicool" ya existía en phpMyAdmin antes
-- de esta actualización (la tabla `pedidos` ya tenía datos), el CREATE TABLE
-- de arriba no modifica nada porque la tabla ya existe. Ejecuta estas líneas
-- una sola vez para añadir las columnas nuevas sin perder la información
-- que ya tenías registrada:
--
--   ALTER TABLE pedidos ADD COLUMN numero_guia VARCHAR(30) NULL AFTER id;
--   ALTER TABLE pedidos ADD COLUMN cedula VARCHAR(20) NOT NULL DEFAULT '' AFTER nombre_cliente;
--   ALTER TABLE pedidos ADD UNIQUE KEY uq_numero_guia (numero_guia);
--
-- Si alguna de esas columnas ya existe, MySQL mostrará un error de
-- "Duplicate column name": es normal, significa que esa línea en particular
-- ya no hace falta y puedes omitirla.
-- ---------------------------------------------------------------------------

-- ---------------------------------------------------------------------------
-- Tabla: pedido_items
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- Datos iniciales: categorias
-- ---------------------------------------------------------------------------
INSERT INTO categorias (slug, nombre) VALUES
  ('mundial', 'Ganadoras de Mundial'),
  ('copa', 'Ganadoras de Copa America'),
  ('eurocopa', 'Ganadoras de Eurocopa')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ---------------------------------------------------------------------------
-- Datos iniciales: las 16 camisetas del catalogo
-- ---------------------------------------------------------------------------
INSERT INTO productos
  (id, nombre, bandera_emoji, ranking, titulos, descripcion, tecnologia, tela_titulo, tela_subtitulo, precio, precio_anterior, descuento, stock, historia, img_frente, img_atras)
VALUES
  ('brasil', 'Brasil', '🇧🇷', '1° 🏆', '5 TÍTULOS MUNDIALES', 'La Canarinha, magia, jogo bonito y el máximo referente del fútbol mundial.', 'Tecnología Aeroready: Tejido ultra ligero que absorbe el sudor al instante.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 34, 'Con 5 estrellas en el pecho, Brasil es la única selección presente en todos los Mundiales. Cuna de Pelé, Ronaldo y Neymar, su camiseta amarilla es sinónimo de espectáculo y magia.', 'img/productos/brasil-frente.png', 'img/productos/brasil-atras.png'),
  ('alemania', 'Alemania', '🇩🇪', '2° 🏆', '4 TÍTULOS MUNDIALES', 'Disciplina táctica, jerarquía inquebrantable y mentalidad ganadora histórica.', 'Tecnología Heat.RDY: Mantiene la frescura en los momentos de máxima exigencia.', 'TELA PREMIUM', 'Secado rápido profesional', 59500, 85000, '-30%', 28, 'La Mannschaft es sinónimo de eficacia y carácter. Cuatro títulos mundiales avalan una escuela de fútbol basada en la disciplina táctica y el trabajo colectivo.', 'img/productos/alemania-frente.png', 'img/productos/alemania-atras.png'),
  ('italia', 'Italia', '🇮🇹', '3° 🏆', '4 TÍTULOS MUNDIALES', 'La Squadra Azzurra, elegancia defensiva y orgullo competitivo.', 'Tecnología DryCell: Expulsa el sudor de la piel para mayor comodidad.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 22, 'La Squadra Azzurra ha levantado 4 Copas del Mundo gracias a una defensa histórica y un orgullo competitivo que la caracteriza desde siempre.', 'img/productos/italia-frente.png', 'img/productos/italia-atras.png'),
  ('argentina', 'Argentina', '🇦🇷', '4° 🏆', '3 TÍTULOS MUNDIALES', 'Pasión albiceleste, talento puro y gloria eterna en el firmamento futbolístico.', 'Tecnología Aeroready: Tejido transpirable de alto rendimiento.', 'TELA PREMIUM', 'Secado rápido', 59500, 85000, '-30%', 41, 'Con Maradona y Messi como máximos ídolos, Argentina es una de las selecciones más ganadoras y pasionales del planeta, con 3 estrellas mundiales.', 'img/productos/argentina-frente.png', 'img/productos/argentina-atras.png'),
  ('francia', 'Francia', '🇫🇷', '5° 🏆', '2 TÍTULOS MUNDIALES', 'Los Bleus, potencia atlética, modernidad y talento desbordante.', 'Tecnología Dri-FIT ADV: Combina tejido antihumedad con ingeniería avanzada.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 30, 'Les Bleus combinan talento individual y fuerza de equipo. Campeones del mundo en 1998 y 2018, hoy siguen entre las selecciones más temidas.', 'img/productos/francia-frente.png', 'img/productos/francia-atras.png'),
  ('uruguay', 'Uruguay', '🇺🇾', '6° 🏆', '2 TÍTULOS MUNDIALES', 'La garra charrúa, pioneros y leyenda viviente de las copas del mundo.', 'Tecnología Ultraweave: Tejido ultraligero diseñado para velocidad y confort.', 'TELA PREMIUM', 'Secado rápido', 59500, 85000, '-30%', 19, 'La Celeste fue campeona en los dos primeros Mundiales de la historia (1930 y 1950). Un país pequeño con un gigante legado futbolístico.', 'img/productos/uruguay-frente.png', 'img/productos/uruguay-atras.png'),
  ('inglaterra', 'Inglaterra', '🏴', '7° 🏆', '1 TÍTULO MUNDIAL', 'La cuna del fútbol, tradición, honor y juego directo.', 'Tecnología Dri-FIT: Control total de la transpiración corporal.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 25, 'Cuna del fútbol moderno y campeona del mundo en 1950. Los Three Lions representan la casa original del deporte rey.', 'img/productos/inglaterra-frente.png', 'img/productos/inglaterra-atras.png'),
  ('espana', 'España', '🇪🇸', '8° 🏆', '1 TÍTULO MUNDIAL', 'La Furia Roja, tiki-taka, control absoluto y magia técnica.', 'Tecnología Aeroready: Máxima transpirabilidad en cada movimiento.', 'TELA PREMIUM', 'Secado rápido', 59500, 85000, '-30%', 27, 'La Furia Roja deslumbró al mundo con el tiki-taka, coronándose campeona mundial en 2010 y bicampeona de Eurocopa en la misma década dorada.', 'img/productos/espana-frente.png', 'img/productos/espana-atras.png'),
  ('colombia', 'Colombia', '🇨🇴', '🥇 COPA AMÉRICA', '1 TÍTULO CONTINENTAL (2001)', 'La tricolor cafetera: velocidad, gambeta y una hinchada que vibra en cada mundial.', 'Tecnología Climacool: Ventilación estratégica en zonas de alta transpiración.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 52, 'La tricolor cafetera es una de las selecciones más queridas de Suramérica, símbolo de garra, alegría y una hinchada inigualable.', 'img/productos/colombia-frente.png', 'img/productos/colombia-atras.png'),
  ('usa', 'Estados Unidos', '🇺🇸', '🔥 EN ASCENSO', 'SIN TÍTULOS FIFA / CONCACAF MAYOR', 'El fútbol que crece más rápido en el continente, anfitrión del Mundial 2026 y en plena expansión global.', 'Tecnología Vaporknit: Elasticidad multidireccional para máxima libertad de movimiento.', 'TELA PREMIUM', 'Secado rápido', 59500, 85000, '-30%', 18, 'El fútbol de Estados Unidos crece a pasos agigantados, con una base cada vez más sólida y una afición que no para de crecer de cara al Mundial 2026.', 'img/productos/usa-frente.png', 'img/productos/usa-atras.png'),
  ('mexico', 'México', '🇲🇽', '🔥 SIN TÍTULOS MUNDIALES', 'MÁXIMO CAMPEÓN DE CONCACAF (SIN TÍTULO MUNDIAL)', 'El Tri: una de las hinchadas más fieles del mundo y presencia constante en los mundiales.', 'Tecnología HeatGear: Regula la temperatura corporal durante el esfuerzo intenso.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 36, 'El Tri es historia pura en Concacaf, con presencia en 17 Mundiales y un fervor por la camiseta verde que atraviesa generaciones.', 'img/productos/mexico-frente.png', 'img/productos/mexico-atras.png'),
  ('costarica', 'Costa Rica', '🇨🇷', '🔥 SORPRESA MUNDIALISTA', 'CUARTOS DE FINAL BRASIL 2014 (SIN TÍTULO MUNDIAL)', 'La Sele: orden defensivo, garra centroamericana y una de las mayores sorpresas mundialistas de la historia.', 'Tecnología Ultraweave: Tejido ultraligero diseñado para velocidad y confort.', 'TELA PREMIUM', 'Secado rápido', 59500, 85000, '-30%', 14, 'Los Ticos han dado sorpresas históricas en el Mundial, incluyendo un inolvidable cuartos de final en Brasil 2014.', 'img/productos/costarica-frente.png', 'img/productos/costarica-atras.png'),
  ('canada', 'Canadá', '🇨🇦', '🔥 EN CRECIMIENTO', 'CO-ANFITRIÓN MUNDIAL 2026 (SIN TÍTULO MUNDIAL)', 'Los Canucks: velocidad, juventud y un fútbol en plena consolidación de cara al Mundial de casa.', 'Tecnología Dri-FIT ADV: Combina tejido antihumedad con ingeniería avanzada.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 12, 'La selección canadiense vive un momento histórico, consolidándose como potencia emergente de Concacaf rumbo al Mundial que coorganiza en 2026.', 'img/productos/canada-frente.png', 'img/productos/canada-atras.png'),
  ('portugal', 'Portugal', '🇵🇹', '⭐ EUROCOPA', '1 TÍTULO CONTINENTAL (2016)', 'Elegancia y talento individual de primer nivel, cuna de generaciones históricas del fútbol europeo.', 'Tecnología HeatGear: Regula la temperatura corporal durante el esfuerzo intenso.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 33, 'Con Cristiano Ronaldo como máximo referente, Portugal se coronó campeón de Eurocopa 2016 y es hoy una potencia mundial.', 'img/productos/portugal-frente.png', 'img/productos/portugal-atras.png'),
  ('paisesbajos', 'Países Bajos', '🇳🇱', '⭐ EUROCOPA', '1 TÍTULO CONTINENTAL (1988)', 'La "Naranja Mecánica": fútbol total, 3 finales de mundial y una escuela táctica admirada en todo el mundo.', 'Tecnología Dri-FIT: Control total de la transpiración corporal.', 'TELA PREMIUM', 'Secado rápido', 59500, 85000, '-30%', 20, 'La Naranja Mecánica revolucionó el fútbol con el \'fútbol total\'. Tres finales de Mundial avalan su enorme historia.', 'img/productos/paisesbajos-frente.png', 'img/productos/paisesbajos-atras.png'),
  ('chile', 'Chile', '🇨🇱', '🥇 COPA AMÉRICA', '2 TÍTULOS CONTINENTALES (2015, 2016)', 'La Roja: intensidad, presión alta y el bicampeonato continental más reciente de Sudamérica.', 'Tecnología Climacool: Ventilación estratégica en zonas de alta transpiración.', 'TELA PREMIUM', '100% poliéster transpirable', 59500, 85000, '-30%', 24, 'La Roja vivió su época dorada con dos títulos consecutivos de Copa América (2015-2016), marcando una generación irrepetible.', 'img/productos/chile-frente.png', 'img/productos/chile-atras.png')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ---------------------------------------------------------------------------
-- Relacion producto <-> categoria
-- ---------------------------------------------------------------------------
INSERT IGNORE INTO producto_categoria (producto_id, categoria_id) VALUES
  ('brasil', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('brasil', (SELECT id FROM categorias WHERE slug = 'copa')),
  ('alemania', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('alemania', (SELECT id FROM categorias WHERE slug = 'eurocopa')),
  ('italia', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('italia', (SELECT id FROM categorias WHERE slug = 'eurocopa')),
  ('argentina', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('argentina', (SELECT id FROM categorias WHERE slug = 'copa')),
  ('francia', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('francia', (SELECT id FROM categorias WHERE slug = 'eurocopa')),
  ('uruguay', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('uruguay', (SELECT id FROM categorias WHERE slug = 'copa')),
  ('inglaterra', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('espana', (SELECT id FROM categorias WHERE slug = 'mundial')),
  ('espana', (SELECT id FROM categorias WHERE slug = 'eurocopa')),
  ('colombia', (SELECT id FROM categorias WHERE slug = 'copa')),
  ('mexico', (SELECT id FROM categorias WHERE slug = 'copa')),
  ('portugal', (SELECT id FROM categorias WHERE slug = 'eurocopa')),
  ('chile', (SELECT id FROM categorias WHERE slug = 'copa'));
