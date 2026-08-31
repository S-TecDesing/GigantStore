// index.js
// Muestra en el icono del carrito la cantidad guardada por productos.html

        // Muestra en el ícono del carrito la cantidad guardada por PaginaProductos.html
        (function(){
            try{
                var cart = JSON.parse(localStorage.getItem('mundialCart') || '[]');
                var total = cart.reduce(function(s,i){ return s + i.qty; }, 0);
                var badge = document.getElementById('cartBadge');
                if(total > 0){
                    badge.textContent = total;
                    badge.classList.add('show');
                }
            }catch(e){}
        })();

/* =========================================================
   RASTREA TU PEDIDO
   Consulta php/api/rastreo.php con número de guía + cédula
   y pinta la línea de tiempo de envío en pantalla.
   ========================================================= */
function formatMoneyCOP(valor) {
    return '$' + Number(valor || 0).toLocaleString('es-CO') + ' COP';
}

async function rastrearPedido() {
    var guiaInput = document.getElementById('track-guia');
    var cedulaInput = document.getElementById('track-cedula');
    var errorBox = document.getElementById('tracking-error');
    var resultBox = document.getElementById('tracking-result');
    var btn = document.getElementById('track-btn');

    var guia = (guiaInput.value || '').trim();
    var cedula = (cedulaInput.value || '').trim();

    errorBox.classList.add('hidden');
    resultBox.classList.add('hidden');

    if (!guia || !cedula) {
        errorBox.textContent = 'Ingresa tu número de guía y tu cédula.';
        errorBox.classList.remove('hidden');
        return;
    }

    var originalBtnText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Buscando...';

    try {
        var url = '../php/api/rastreo.php?guia=' + encodeURIComponent(guia) + '&cedula=' + encodeURIComponent(cedula);
        var res = await fetch(url);
        var data = await res.json();

        if (!res.ok || !data.ok) {
            errorBox.textContent = data.error || 'No encontramos tu pedido. Verifica los datos.';
            errorBox.classList.remove('hidden');
            return;
        }

        // Resumen
        document.getElementById('tr-guia').textContent = data.numero_guia;
        document.getElementById('tr-estado').textContent = data.estado_actual;
        document.getElementById('tr-ciudad').textContent = data.ciudad_actual;
        document.getElementById('tr-destino').textContent = data.ciudad_destino;
        document.getElementById('tr-total').textContent = formatMoneyCOP(data.total);

        // Línea de tiempo
        var timeline = document.getElementById('tracking-timeline');
        timeline.innerHTML = '';
        (data.timeline || []).forEach(function(step){
            var li = document.createElement('li');
            li.className = 'timeline-step' + (step.completado ? ' completed' : '');
            li.innerHTML =
                '<div class="timeline-dot"><i class="fa-solid ' + (step.completado ? 'fa-check' : 'fa-circle') + '"></i></div>' +
                '<div class="timeline-content">' +
                    '<div class="timeline-top-row">' +
                        '<strong>' + step.titulo + '</strong>' +
                        '<span class="timeline-fecha">' + step.fecha + '</span>' +
                    '</div>' +
                    '<span class="timeline-ciudad"><i class="fa-solid fa-location-dot"></i> ' + step.ciudad + '</span>' +
                    '<p class="timeline-desc">' + step.descripcion + '</p>' +
                '</div>';
            timeline.appendChild(li);
        });

        // Ítems del pedido
        var itemsBody = document.getElementById('tr-items-body');
        itemsBody.innerHTML = '';
        (data.items || []).forEach(function(item){
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + item.nombre_producto + '</td>' +
                '<td>' + item.talla + '</td>' +
                '<td>' + item.cantidad + '</td>' +
                '<td>' + formatMoneyCOP(item.precio_unitario) + '</td>';
            itemsBody.appendChild(tr);
        });

        resultBox.classList.remove('hidden');

    } catch (e) {
        errorBox.textContent = 'Hubo un problema de conexión. Intenta de nuevo en unos segundos.';
        errorBox.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalBtnText;
    }
}

