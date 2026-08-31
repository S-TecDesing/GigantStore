# Instalar el chatbot en el servidor (Azure/Oracle VM)

Estos pasos van DESPUÉS de que ya tengas Apache + PHP + MySQL funcionando
y el proyecto clonado en /var/www/camicool (Fase 4/5 del laboratorio).

## 1. Instalar Python y crear el entorno virtual

```
sudo apt install -y python3-pip python3-venv
cd /var/www/camicool/py
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
deactivate
```

## 2. Crear el archivo .env con tu clave REAL de Groq

```
cd /var/www/camicool/py
cp .env.example .env
nano .env
```
Reemplaza el valor de `GROQ_API_KEY` por tu clave real (la nueva, regenerada).
Guarda con Ctrl+O, Enter, y sal con Ctrl+X.

## 3. Instalar el servicio systemd (para que arranque solo, sin comandos manuales)

```
sudo cp /var/www/camicool/deploy/camicool-chat.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable camicool-chat
sudo systemctl start camicool-chat
sudo systemctl status camicool-chat
```
Si ves "active (running)" en verde, el chatbot ya quedó corriendo en segundo
plano, y arrancará automáticamente cada vez que el servidor se reinicie.

## 4. Activar el proxy en Apache

```
sudo a2enmod proxy proxy_http
sudo cp /var/www/camicool/deploy/camicool-apache.conf /etc/apache2/sites-available/camicool.conf
sudo a2ensite camicool.conf
sudo systemctl reload apache2
```

## 5. Probar

Abre http://TU-IP/carrito.html en el navegador, haz clic en el botón de
chat flotante, y escribe algo como "¿qué tallas tienen de Brasil?".

**Nota de seguridad:** el puerto 5000 (donde corre Flask internamente)
NUNCA debe abrirse en el Grupo de Seguridad de Red (NSG) de Azure. Solo
deben estar abiertos 22, 80 y 443. Apache es el único que habla con
Flask, y lo hace internamente dentro del propio servidor.
