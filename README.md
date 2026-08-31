# CamiCool — Tienda de camisetas de selecciones

Proyecto reestructurado y unificado. Antes existían dos carpetas
independientes (`pagePrincipal/` y `ladingpage/`) con imágenes y estilos
duplicados; ahora todo vive en una sola estructura organizada:

```
camicool/
├── html/
│   ├── index.html        Landing / home
│   ├── productos.html    Catálogo grande (16 camisetas, filtros, A-Z)
│   └── carrito.html      Catálogo + carrito + checkout por WhatsApp
├── css/
│   ├── principal.css
│   ├── productos.css
│   └── carrito.css
├── js/
│   ├── index.js
│   ├── productos.js          (lógica de productos.html)
│   ├── productos-data.js     (fuente única de datos: 16 camisetas)
│   └── carrito.js            (lógica de carrito.html + chatbot)
├── img/
│   ├── productos/    (PNG usadas en index.html y productos.html)
│   ├── carrito/      (WEBP usadas en carrito.html)
│   └── comunes/      (logo, fondo de estadio)
├── py/
│   ├── app.py             Chatbot (Flask + Groq) usado por carrito.html
│   ├── requirements.txt
│   └── .env.example
└── php/
    ├── config/database.php   Conexión PDO a MySQL
    ├── database.sql          Esquema + los 16 productos + categorías
    ├── api/
    │   ├── productos.php     GET  -> catálogo en JSON
    │   └── pedido.php        POST -> crea un pedido (valida contra la BD)
    └── cliente/
        └── index.php         Cliente web en PHP (lee de MySQL, cotiza y pide)
```
