<?php
/**
 * config/database.php
 * Conexion centralizada a la base de datos MySQL (PDO) para toda la capa PHP
 * (API en /api y cliente web en /cliente).
 *
 * Ajusta estas 4 constantes segun tu servidor/hosting antes de desplegar.
 * Puedes tambien definirlas como variables de entorno del servidor en vez
 * de dejarlas escritas aqui (mas seguro para produccion).
 * 
 * Cliente web / Catálogo PHP:
    * http://localhost:8080/php/cliente/index.php

    *API de productos en formato JSON:
    * http://localhost:8080/php/api/productos.php

    *Versión HTML estática:
    * http://localhost:8080/html/carrito.html
 * 
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'camicool');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Devuelve una conexion PDO reutilizable a la base de datos.
 * Lanza una excepcion clara si la conexion falla (se captura en cada
 * endpoint para responder con un JSON de error controlado).
 */
function obtenerConexion(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
    }

    return $pdo;
}
