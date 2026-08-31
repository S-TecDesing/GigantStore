from flask import Flask, request, jsonify
from flask_cors import CORS
import os
from groq import Groq
from dotenv import load_dotenv

# Configuración precisa de directorios
PY_DIR = os.path.dirname(os.path.abspath(__file__))
BASE_DIR = os.path.abspath(os.path.join(PY_DIR, '..'))

# Cargar .env buscando tanto en la raíz como en /py
load_dotenv(os.path.join(BASE_DIR, '.env'))
load_dotenv(os.path.join(PY_DIR, '.env'))

app = Flask(__name__, static_folder=None)
CORS(app, resources={r"/*": {"origins": "*"}})

# La API key SOLO se lee de la variable de entorno GROQ_API_KEY.
# NUNCA escribas la clave real aquí en el código (eso es lo que causó
# que quedara expuesta antes). Se configura en el archivo py/.env
# (que nunca se sube a git) o como variable de entorno del servidor.
api_key = os.getenv("GROQ_API_KEY")
if not api_key:
    raise RuntimeError(
        "Falta GROQ_API_KEY. Crea el archivo py/.env (copia py/.env.example) "
        "y coloca ahí tu clave real de Groq."
    )
client = Groq(api_key=api_key)

# ---------------------------------------------------------------------------
# CATÁLOGO DE SELECCIONES
# ---------------------------------------------------------------------------
CATALOGO = """
- Brasil 🇧🇷 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 34 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología Aeroready: Tejido ultra ligero que absorbe el sudor al instante. | Historia breve: Con 5 estrellas en el pecho, Brasil es la única selección presente en todos los Mundiales. Cuna de Pelé, Ronaldo y Neymar, su camiseta amarilla es sinónimo de espectáculo y magia.
- Alemania 🇩🇪 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 28 unidades | Tela: TELA PREMIUM - Secado rápido profesional. Tecnología Heat.RDY: Mantiene la frescura en los momentos de máxima exigencia. | Historia breve: La Mannschaft es sinónimo de eficacia y carácter. Cuatro títulos mundiales avalan una escuela de fútbol basada en la disciplina táctica y el trabajo colectivo.
- Italia 🇮🇹 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 22 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología DryCell: Expulsa el sudor de la piel para mayor comodidad. | Historia breve: La Squadra Azzurra ha levantado 4 Copas del Mundo gracias a una defensa histórica y un orgullo competitivo que la caracteriza desde siempre.
- Argentina 🇦🇷 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 41 unidades | Tela: TELA PREMIUM - Secado rápido. Tecnología Aeroready: Tejido transpirable de alto rendimiento. | Historia breve: Con Maradona y Messi como máximos ídolos, Argentina es una de las selecciones más ganadoras y pasionales del planeta, con 3 estrellas mundiales.
- Francia 🇫🇷 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 30 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología Dri-FIT ADV: Combina tejido antihumedad con ingeniería avanzada. | Historia breve: Les Bleus combinan talento individual y fuerza de equipo. Campeones del mundo en 1998 y 2018, hoy siguen entre las selecciones más temidas.
- Uruguay 🇺🇾 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 19 unidades | Tela: TELA PREMIUM - Secado rápido. Tecnología Ultraweave: Tejido ultraligero diseñado para velocidad y confort. | Historia breve: La Celeste fue campeona en los dos primeros Mundiales de la historia (1930 y 1950). Un país pequeño con un gigante legado futbolístico.
- Inglaterra 🏴 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 25 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología Dri-FIT: Control total de la transpiración corporal. | Historia breve: Cuna del fútbol moderno y campeona del mundo en 1950. Los Three Lions representan la casa original del deporte rey.
- España 🇪🇸 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 27 unidades | Tela: TELA PREMIUM - Secado rápido. Tecnología Aeroready: Máxima transpirabilidad en cada movimiento. | Historia breve: La Furia Roja deslumbró al mundo con el tiki-taka, coronándose campeona mundial en 2010 y bicampeona de Eurocopa en la misma década dorada.
- Colombia 🇨🇴 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 52 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología Climacool: Ventilación estratégica en zonas de alta transpiración. | Historia breve: La tricolor cafetera es una de las selecciones más queridas de Suramérica, símbolo de garra, alegría y una hinchada inigualable.
- Estados Unidos 🇺🇸 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 18 unidades | Tela: TELA PREMIUM - Secado rápido. Tecnología Vaporknit: Elasticidad multidireccional para máxima libertad de movimiento. | Historia breve: El fútbol de Estados Unidos crece a pasos agigantados, con una base cada vez más sólida y una afición que no para de crecer de cara al Mundial 2026.
- México 🇲🇽 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 36 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología HeatGear: Regula la temperatura corporal durante el esfuerzo intenso. | Historia breve: El Tri es historia pura en Concacaf, con presencia en 17 Mundiales y un fervor por la camiseta verde que atraviesa generaciones.
- Costa Rica 🇨🇷 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 14 unidades | Tela: TELA PREMIUM - Secado rápido. Tecnología Ultraweave: Tejido ultraligero diseñado para velocidad y confort. | Historia breve: Los Ticos han dado sorpresas históricas en el Mundial, incluyendo un inolvidable cuartos de final en Brasil 2014.
- Canadá 🇨🇦 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 12 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología Dri-FIT ADV: Combina tejido antihumedad con ingeniería avanzada. | Historia breve: La selección canadiense vive un momento histórico, consolidándose como potencia emergente de Concacaf rumbo al Mundial que coorganiza en 2026.
- Portugal 🇵🇹 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 33 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología HeatGear: Regula la temperatura corporal durante el esfuerzo intenso. | Historia breve: Con Cristiano Ronaldo como máximo referente, Portugal se coronó campeón de Eurocopa 2016 y es hoy una potencia mundial.
- Países Bajos 🇳🇱 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 20 unidades | Tela: TELA PREMIUM - Secado rápido. Tecnología Dri-FIT: Control total de la transpiración corporal. | Historia breve: La Naranja Mecánica revolucionó el fútbol con el 'fútbol total'. Tres finales de Mundial avalan su enorme historia.
- Chile 🇨🇱 | Precio: $59.500 COP (antes $85.000) | Stock en bodega: 24 unidades | Tela: TELA PREMIUM - 100% poliéster transpirable. Tecnología Climacool: Ventilación estratégica en zonas de alta transpiración. | Historia breve: La Roja vivió su época dorada con dos títulos consecutivos de Copa América (2015-2016), marcando una generación irrepetible.
""".strip()

SYSTEM_PROMPT = f"""
Eres el vendedor estrella de CamiCool ⚽. Tu personalidad es carismática, amable y experta en fútbol.
Tu objetivo es cerrar ventas y guiar al cliente con calidez, sin presionar de más.

CATÁLOGO COMPLETO:
{CATALOGO}

REGLAS DE ORO DE VENTA:
1. RESPONDE SIEMPRE LO QUE TE PREGUNTEN, de forma concreta y con datos reales del catálogo de arriba. Nunca respondas solo con un saludo genérico: si preguntan por talla, cantidad, tela, calidad, stock, precio unitario o al por mayor, contesta ESE dato puntual primero, y luego puedes añadir contexto de venta.
2. DETECCIÓN DE SELECCIÓN: Identifica de qué selección habla el cliente. Si no queda claro, pregúntale amablemente cuál selección le interesa antes de dar el dato.
3. DATOS QUE DEBES DAR CUANDO LOS PIDAN (usando el catálogo, nunca inventados):
   - TALLAS disponibles: S, M, L y XL, para hombre y mujer (corte Slim Fit).
   - CANTIDAD / STOCK: la cantidad exacta en bodega para esa selección.
   - TELA / CALIDAD: el tipo de tela y la tecnología textil de esa selección.
   - PRECIO UNITARIO: $59.500 COP (antes $85.000, 30% de descuento).
   - VENTA AL POR MAYOR: si preguntan por cantidades grandes (docena, mayoreo, varias unidades), confirma que sí se puede, sugiere escribir por WhatsApp para cotización especial de mayoreo, y no inventes descuentos adicionales que no estén aquí.
   - HISTORIA: breve reseña futbolística de la selección si la piden.
4. CATÁLOGO COMPLETO: Recuerda que en la sección "Catálogo de Selecciones" de esta misma página pueden ver, tocar y elegir las 16 camisetas disponibles.
5. CONTACTO HUMANO: WhatsApp de atención: https://wa.me/573227517207
6. TONO Y LÍMITES: Sé amigable, cercano y apasionado por el fútbol, pero SIEMPRE respetuoso y profesional. Nunca uses groserías, vulgaridades, insultos ni doble sentido, aunque el cliente las use; en ese caso responde con calidez y sin corresponder ese tono, y si insiste, redirígelo amablemente al WhatsApp humano.
7. Si te preguntan algo fuera del catálogo (tallas, envíos, otras dudas del negocio), respóndelo con la información disponible en este mensaje; si de verdad no lo sabes, dilo con honestidad y ofrece el WhatsApp.

AYUDA CON EL FORMULARIO DE ENVÍO:
- NOMBRE: Solo primer nombre y apellido principal.
- CELULAR: 10 dígitos sin el (+57).
- RESTRICCIÓN: Solo envíos nacionales en COLOMBIA 🇨🇴.
- El pedido se finaliza en WhatsApp tras llenar los datos.

TONO DE VOZ: Amigable, hincha apasionado, respuestas concretas y breves (máximo 5-6 líneas), pero SIEMPRE respondiendo exactamente lo que se preguntó.
"""

# ---------------------------------------------------------------------------
# Este servicio Flask es un microservicio dedicado SOLO al chatbot.
# El resto del sitio (index.html, carrito.html, css/, js/, img/, y todo
# el backend PHP) lo sirve Apache directamente. Apache reenvía únicamente
# las peticiones a /chat hacia este proceso mediante un reverse proxy
# (ver apache/camicool.conf), por eso aquí no hace falta duplicar rutas
# estáticas.
# ---------------------------------------------------------------------------
@app.route('/chat/health')
def health():
    return jsonify({"status": "ok"})

# ---------------------------------------------------------------------------
# CHATBOT ENDPOINT
# ---------------------------------------------------------------------------
@app.route('/chat', methods=['POST'])
def chat():
    data = request.json or {}
    user_message = data.get("message", "").strip()

    if not user_message:
        return jsonify({"response": "¡Cuéntame qué necesitas saber, hincha! Tallas, tela, stock, precio... pregunta lo que quieras. ⚽"})

    try:
        completion = client.chat.completions.create(
            # NOTA: "llama-3.3-70b-versatile" fue retirado por Groq (shutdown 16-ago-2026).
            # Se usa el reemplazo oficial recomendado por Groq para ese modelo.
            model="openai/gpt-oss-120b",
            messages=[
                {"role": "system", "content": SYSTEM_PROMPT},
                {"role": "user", "content": user_message}
            ],
            temperature=0.7,
            max_tokens=450
        )
        response_text = completion.choices[0].message.content
        return jsonify({"response": response_text})
    except Exception as e:
        # Log detallado en consola del servidor para poder diagnosticar (API key inválida,
        # modelo incorrecto, rate limit, etc.) sin que el usuario final vea un error técnico.
        import traceback
        print("=" * 60)
        print(f"ERROR EN LA API DE GROQ: {e}")
        traceback.print_exc()
        print("=" * 60)
        return jsonify({
            "response": "¡Uy, crack! Se me cruzó el cable por un segundo ⚽. Intenta de nuevo en unos segundos, o escríbenos directo a WhatsApp: https://wa.me/573227517207"
        }), 500

if __name__ == '__main__':
    # Este modo (servidor de desarrollo de Flask) solo es para pruebas locales.
    # En el servidor de Azure, este proceso se ejecuta con gunicorn a través
    # del servicio systemd camicool-chat.service (ver carpeta /deploy).
    app.run(host='0.0.0.0', port=5000, debug=False)