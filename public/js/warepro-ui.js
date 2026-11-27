/**
 * Ware-Pro UI (se carga desde core/header.php).
 *
 *  1. Fechas y horas: todo <input type="date|time|datetime-local"> se convierte en Flatpickr (en español).
 *     El valor que se envía sigue en el formato nativo (YYYY-MM-DD, HH:MM, YYYY-MM-DDTHH:MM): el backend no cambia.
 *     La librería solo se descarga si la página tiene campos de fecha.
 *     - data-fp="off"            deja el campo nativo
 *     - data-fp-rango="#desde,#hasta"  en un input de texto: selector de rango que llena dos campos
 *  2. WP.modal.abrir(id) / WP.modal.cerrar(id): modales .wp-modal con Esc, clic afuera y bloqueo de scroll.
 *  3. WP.fecha(el, opciones): crea un Flatpickr con el tema y el idioma del sistema.
 */
(function () {
    'use strict';
    var FP_VER = '4.6.13';
    var FP_BASE = 'https://cdn.jsdelivr.net/npm/flatpickr@' + FP_VER + '/dist/';
    var cargaFp = null;

    function cargarScript(src) {
        return new Promise(function (ok, mal) {
            var s = document.createElement('script');
            s.src = src; s.onload = ok; s.onerror = mal;
            document.head.appendChild(s);
        });
    }

    function cargarFlatpickr() {
        if (cargaFp) return cargaFp;
        if (!document.querySelector('link[href*="flatpickr"]')) {
            var l = document.createElement('link');
            l.rel = 'stylesheet'; l.href = FP_BASE + 'flatpickr.min.css';
            // antes de nuestra hoja para que el tema dorado gane
            var nuestra = document.querySelector('link[href*="warepro-ui.css"]');
            if (nuestra && nuestra.parentNode) nuestra.parentNode.insertBefore(l, nuestra);
            else document.head.appendChild(l);
        }
        cargaFp = (window.flatpickr ? Promise.resolve() : cargarScript(FP_BASE + 'flatpickr.min.js'))
            .then(function () {
                return (window.flatpickr.l10ns && window.flatpickr.l10ns.es) ? null : cargarScript(FP_BASE + 'l10n/es.js');
            })
            .then(function () { return window.flatpickr; });
        return cargaFp;
    }

    function opcionesBase(extra) {
        var o = {
            locale: (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.es) ? 'es' : 'default',
            dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y', allowInput: false, disableMobile: true
        };
        for (var k in extra) if (Object.prototype.hasOwnProperty.call(extra, k)) o[k] = extra[k];
        return o;
    }

    var nativo = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    // Formatos por tipo de campo: [formato del valor enviado, formato visible, opciones]
    var TIPOS = {
        'date': ['Y-m-d', 'd/m/Y', {}],
        'datetime-local': ['Y-m-d\\TH:i', 'd/m/Y H:i', { enableTime: true, time_24hr: true }],
        'time': ['H:i', 'H:i', { enableTime: true, noCalendar: true, time_24hr: true }]
    };

    /** Convierte un input date/time/datetime-local en Flatpickr conservando name, valor, min/max, required y eventos change. */
    function convertirFecha(el) {
        if (el._flatpickr || el.dataset.fp === 'off' || el.dataset.fpListo) return;
        var tipo = TIPOS[el.type] ? el.type : 'date';
        var cfg = TIPOS[tipo];
        el.dataset.fpListo = tipo;
        var clases = el.className.trim();
        var valor = el.value;
        if (tipo === 'time' && valor.length > 5) valor = valor.slice(0, 5);
        el.type = 'text';
        var extra = {
            dateFormat: cfg[0], altFormat: cfg[1],
            defaultDate: valor || null,
            minDate: tipo === 'time' ? null : (el.min || null),
            maxDate: tipo === 'time' ? null : (el.max || null),
            altInputClass: (clases ? clases + ' ' : 'wp-fp-auto ') + 'flatpickr-input',
            onChange: function () { el.dispatchEvent(new Event('input', { bubbles: true })); }
        };
        for (var k in cfg[2]) extra[k] = cfg[2][k];
        if (tipo === 'time' && el.min) extra.minTime = el.min;
        if (tipo === 'time' && el.max) extra.maxTime = el.max;
        var fp = window.flatpickr(el, opcionesBase(extra));
        if (fp.altInput) {
            fp.altInput.placeholder = el.placeholder || { date: 'dd/mm/aaaa', time: 'hh:mm', 'datetime-local': 'dd/mm/aaaa hh:mm' }[tipo];
            if (el.required) { fp.altInput.required = true; el.required = false; }
            if (el.disabled) fp.altInput.disabled = true;
            if (el.id) fp.altInput.setAttribute('aria-labelledby', el.id);
            fp.altInput.style.cssText = el.style.cssText;
        }
        // Si el código de la página asigna el valor (el.value = '2026-01-31'), el calendario lo refleja
        var ocupado = false;
        Object.defineProperty(el, 'value', {
            configurable: true,
            get: function () { return nativo.get.call(el); },
            set: function (v) {
                nativo.set.call(el, v);
                if (ocupado) return;
                ocupado = true;
                try { fp.setDate(v || null, false); } finally { ocupado = false; }
            }
        });
        if (el.form) el.form.addEventListener('reset', function () { setTimeout(function () { fp.setDate(nativo.get.call(el) || null, false); }, 0); });
        // min/max cambiados por JS (p.ej. "hasta" depende de "desde")
        new MutationObserver(function () {
            if (tipo !== 'time') {
                fp.set('minDate', el.getAttribute('min') || null);
                fp.set('maxDate', el.getAttribute('max') || null);
            }
            if (fp.altInput) fp.altInput.disabled = el.disabled;
        }).observe(el, { attributes: true, attributeFilter: ['min', 'max', 'disabled'] });
    }

    /** Input de texto con data-fp-rango="#desde,#hasta": un solo selector de rango que llena dos campos ocultos. */
    function convertirRango(el) {
        if (el._flatpickr) return;
        var ids = el.dataset.fpRango.split(',');
        var desde = document.querySelector(ids[0]), hasta = document.querySelector(ids[1]);
        var ini = [desde && desde.value, hasta && hasta.value].filter(Boolean)
            .map(function (v) { return window.flatpickr.parseDate(v, 'Y-m-d'); });
        window.flatpickr(el, {
            locale: opcionesBase({}).locale, mode: 'range', dateFormat: 'd/m/Y', defaultDate: ini, disableMobile: true,
            conjunction: ' a ',
            onChange: function (sel, _s, inst) {
                if (sel.length === 1) return;
                if (desde) desde.value = sel[0] ? inst.formatDate(sel[0], 'Y-m-d') : '';
                if (hasta) hasta.value = sel[1] ? inst.formatDate(sel[1], 'Y-m-d') : (sel[0] ? inst.formatDate(sel[0], 'Y-m-d') : '');
                el.dispatchEvent(new CustomEvent('wp-rango', { bubbles: true, detail: { desde: desde && desde.value, hasta: hasta && hasta.value } }));
            }
        });
    }

    var SEL_FECHAS = 'input[type="date"]:not([data-fp="off"]),input[type="time"]:not([data-fp="off"]),input[type="datetime-local"]:not([data-fp="off"])';

    function procesar(raiz) {
        raiz = raiz || document;
        var fechas = raiz.querySelectorAll ? raiz.querySelectorAll(SEL_FECHAS) : [];
        var rangos = raiz.querySelectorAll ? raiz.querySelectorAll('input[data-fp-rango]') : [];
        if (!fechas.length && !rangos.length) return;
        cargarFlatpickr().then(function () {
            Array.prototype.forEach.call(fechas, convertirFecha);
            Array.prototype.forEach.call(rangos, convertirRango);
        }).catch(function () { /* sin red: quedan los campos nativos */ });
    }

    // Campos de fecha que se agregan después (modales armados con JS, filas nuevas...)
    var pendiente = null;
    function observar() {
        new MutationObserver(function (muts) {
            for (var i = 0; i < muts.length; i++) {
                for (var j = 0; j < muts[i].addedNodes.length; j++) {
                    var n = muts[i].addedNodes[j];
                    if (n.nodeType === 1 && (n.matches(SEL_FECHAS + ',input[data-fp-rango]') || n.querySelector(SEL_FECHAS + ',input[data-fp-rango]'))) {
                        clearTimeout(pendiente);
                        pendiente = setTimeout(function () { procesar(document); }, 50);
                        return;
                    }
                }
            }
        }).observe(document.body, { childList: true, subtree: true });
    }

    // ---------- Modales ----------
    function abrir(id) {
        var m = typeof id === 'string' ? document.getElementById(id) : id;
        if (!m) return;
        m.hidden = false;
        document.body.classList.add('wp-modal-abierto');
        var foco = m.querySelector('[autofocus], input:not([type=hidden]), select, textarea');
        if (foco) setTimeout(function () { try { foco.focus({ preventScroll: true }); } catch (e) {} }, 60);
    }
    function cerrar(id) {
        var m = typeof id === 'string' ? document.getElementById(id) : id;
        if (!m) return;
        m.hidden = true;
        m.dispatchEvent(new CustomEvent('wp-cerrar'));
        if (!document.querySelector('.wp-modal:not([hidden])')) document.body.classList.remove('wp-modal-abierto');
    }
    document.addEventListener('click', function (e) {
        var t = e.target;
        if (t.classList && t.classList.contains('wp-modal') && !t.hasAttribute('data-estatico')) return cerrar(t);
        var b = t.closest && t.closest('[data-wp-cerrar]');
        if (b) cerrar(b.closest('.wp-modal'));
        var a = t.closest && t.closest('[data-wp-abrir]');
        if (a) { e.preventDefault(); abrir(a.getAttribute('data-wp-abrir')); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || document.querySelector('.swal2-container, .flatpickr-calendar.open')) return;
        var abiertos = document.querySelectorAll('.wp-modal:not([hidden]):not([data-estatico])');
        if (abiertos.length) cerrar(abiertos[abiertos.length - 1]);
    });

    // ---------- Normalizador: modales, inputs y botones de cualquier página al estilo de rotura ----------
    var EXCLUIR = '.main-header, .user-menu, .notifications-dropdown, .swal2-container, .flatpickr-calendar, .ps-wrap, .ps-panel,' +
        ' .dataTables_filter, .dataTables_length, .dataTables_paginate, .wp-modal, [data-wpx="off"]';
    var hechos = typeof WeakSet === 'function' ? new WeakSet() : null;
    function visto(el) { if (!hechos) return false; if (hechos.has(el)) return true; hechos.add(el); return false; }
    function alfa(c) {
        if (!c || c === 'transparent') return 0;
        var m = c.match(/rgba?\(([^)]+)\)/); if (!m) return 1;
        var p = m[1].split(','); return p.length > 3 ? parseFloat(p[3]) : 1;
    }
    function texto(el) { return (el.textContent || el.value || '').replace(/\s+/g, ' ').trim(); }
    function esCerrar(b) {
        var t = texto(b), c = (b.className && b.className.baseVal === undefined ? b.className : '') + ' ' + (b.id || '');
        return /^(×|✕|✖|x|X)$/.test(t) || (!t && b.querySelector('.fa-times, .fa-xmark, .fa-close, .fa-times-circle, .fa-circle-xmark')) || (/close|cerrar/i.test(c) && t.length < 3);
    }

    function normalizarModal(m) {
        if (visto(m) || m.closest(EXCLUIR)) return;
        if (getComputedStyle(m).position !== 'fixed') return;
        // la caja: primer descendiente con fondo opaco que no sea un fondo oscuro de pantalla completa
        var caja = null, cola = [].slice.call(m.children), n = 0;
        while (cola.length && n++ < 40) {
            var el = cola.shift(), cs = getComputedStyle(el);
            var fondoPantalla = /\binset-0\b/.test(el.className) || (cs.position === 'absolute' && cs.top === '0px' && cs.bottom === '0px');
            if (!fondoPantalla && alfa(cs.backgroundColor) >= 0.95 && el.tagName !== 'BUTTON') { caja = el; break; }
            cola = cola.concat([].slice.call(el.children));
        }
        if (!caja) return;
        if (alfa(getComputedStyle(m).backgroundColor) > 0) m.classList.add('wpx-bg');
        m.classList.add('wpx-modal');
        caja.classList.add('wpx-box');
        if (parseFloat(getComputedStyle(caja).paddingTop) >= 16) caja.classList.add('wpx-pad');

        // cabecera: primer hijo con un título y sin campos
        var cab = null, h = null;
        var hijos = [].slice.call(caja.children);
        for (var i = 0; i < Math.min(hijos.length, 3) && !cab; i++) {
            var c = hijos[i];
            if (/^H[1-5]$/.test(c.tagName)) { h = c; break; }
            var t = c.querySelector('h1, h2, h3, h4, h5, .modal-title');
            if (t && !c.querySelector('input:not([type=hidden]), select, textarea, table') && texto(c).length < 200) { cab = c; h = t; }
        }
        if (!h) {
            var t1 = caja.querySelector('h1, h2, h3, h4, .modal-title'), c1 = caja.querySelector('input:not([type=hidden]), select, textarea');
            if (t1 && (!c1 || (t1.compareDocumentPosition(c1) & Node.DOCUMENT_POSITION_FOLLOWING))) h = t1;
        }
        if (cab) cab.classList.add('wpx-head');
        if (h) h.classList.add('wpx-title');
        var zona = caja;
        [].forEach.call(zona.querySelectorAll('button, a, span, i, div[onclick]'), function (b) {
            if (b.closest('.wpx-close')) return;
            if (esCerrar(b) && ((cab && cab.contains(b)) || b.parentElement === caja || (b.parentElement && b.parentElement.parentElement === caja))) b.classList.add('wpx-close');
        });
        // pie: el contenedor del botón de guardar si solo tiene botones
        var enviar = caja.querySelectorAll('button[type=submit], input[type=submit]');
        var ultimo = enviar[enviar.length - 1];
        if (ultimo && ultimo.parentElement && ultimo.parentElement !== caja && ultimo.parentElement.tagName !== 'FORM') {
            var p = ultimo.parentElement;
            var soloBotones = [].every.call(p.children, function (x) { return /^(BUTTON|A|INPUT)$/.test(x.tagName); });
            if (soloBotones && p.children.length >= 2) p.classList.add('wpx-foot');
        }
    }

    var TIPOS_NO = /^(checkbox|radio|hidden|range|color|submit|button|reset|image)$/;
    function normalizarCampo(el) {
        if (visto(el)) return;
        if (el.tagName === 'INPUT' && TIPOS_NO.test(el.type)) return;
        if (el.closest(EXCLUIR) || /\b(wp-input|wp-select|wp-textarea|wp-fp-auto|ps-native)\b/.test(el.className)) return;
        var cs = getComputedStyle(el);
        // campos embebidos en un contenedor ya diseñado (sin fondo ni borde propios): se dejan
        if (alfa(cs.backgroundColor) < 0.05 && parseFloat(cs.borderTopWidth) === 0 && parseFloat(cs.borderBottomWidth) === 0) return;
        el.classList.add('wpx-in');
        if (el.closest('td, .dataTable') || parseFloat(cs.fontSize) < 13) el.classList.add('wpx-in-sm');
    }

    var ACCIONES = [
        ['wpx-danger', /\b(eliminar|borrar|rechazar|anular|quitar)\b/],
        ['wpx-secondary', /\b(cancelar|cerrar|volver|limpiar|regresar|atr[aá]s|salir)\b/],
        ['wpx-success', /\b(excel|exportar|descargar)\b/],
        ['wpx-primary', /\b(guardar|registrar|agregar|añadir|nuevo|nueva|crear|enviar|subir|actualizar|confirmar|filtrar|buscar|aplicar|iniciar|finalizar|aceptar|continuar|consultar|generar|cargar|asignar|aprobar|editar|programar)\b/]
    ];
    function normalizarBoton(b) {
        if (visto(b)) return;
        if (b.closest(EXCLUIR) || b.closest('[role=tablist], nav, .tabs, .tab-container') || b.getAttribute('role') === 'tab') return;
        var c = typeof b.className === 'string' ? b.className : '';
        if (/\b(wp-btn|wpx-close|wp-modal-close|paginate_button|page-link|ps-)/.test(c) || /tab|toggle|chip|pill|filtro|filter|dropdown|menu|seg-|day|dia/i.test(c)) return;
        var t = texto(b).toLowerCase();
        if (!t || t.length > 40) return;
        for (var i = 0; i < ACCIONES.length; i++) {
            if (ACCIONES[i][1].test(t)) {
                b.classList.add('wpx-btn', ACCIONES[i][0]);
                if (b.closest('td') || (b.offsetHeight && b.offsetHeight < 34)) b.classList.add('wpx-btn-sm');
                return;
            }
        }
    }

    function normalizar(raiz) {
        raiz = raiz && raiz.querySelectorAll ? raiz : document;
        try {
            [].forEach.call(raiz.querySelectorAll('.modal, .m-bg, .modal-overlay, .mt-modal, .modal-wrap, [id*="modal" i], [id*="Modal"]'), normalizarModal);
            [].forEach.call(raiz.querySelectorAll('input, select, textarea'), normalizarCampo);
            [].forEach.call(raiz.querySelectorAll('button, a[class*="btn"], input[type=submit], input[type=button]'), normalizarBoton);
        } catch (e) { /* nunca romper la página por estética */ }
    }
    var pendienteN = null;
    function observarNormalizar() {
        new MutationObserver(function (muts) {
            for (var i = 0; i < muts.length; i++) if (muts[i].addedNodes.length) {
                clearTimeout(pendienteN);
                pendienteN = setTimeout(function () { normalizar(document); }, 120);
                return;
            }
        }).observe(document.body, { childList: true, subtree: true });
    }

    window.WP = {
        normalizar: normalizar,
        modal: { abrir: abrir, cerrar: cerrar },
        fecha: function (el, opciones) {
            return cargarFlatpickr().then(function (fp) { return fp(el, opcionesBase(opciones || {})); });
        },
        cargarFlatpickr: cargarFlatpickr,
        procesarFechas: procesar
    };

    // ---------- DataTables: traducción local en vez de pedirla al CDN en cada carga ----------
    var RAIZ_UI = (function () {
        var s = document.currentScript || document.querySelector('script[src*="warepro-ui.js"]');
        return s ? s.src.replace(/\/public\/js\/warepro-ui\.js.*$/, '') : '';
    })();
    function idiomaTablas() {
        if (!window.jQuery || !jQuery.ajaxPrefilter || jQuery._wpI18n) return;
        jQuery._wpI18n = true;
        jQuery.ajaxPrefilter(function (o) {
            if (o.url && /cdn\.datatables\.net\/plug-ins\/[^/]+\/i18n\/es/i.test(o.url)) {
                o.url = RAIZ_UI + '/public/js/datatables-es.json';
                o.crossDomain = false;
            }
        });
    }

    function iniciar() {
        idiomaTablas(); procesar(document); observar();
        if (document.body.getAttribute('data-wpx') !== 'off') { normalizar(document); observarNormalizar(); }
    }
    idiomaTablas(); // jQuery ya cargado antes que este script (defer): se registra antes de que las páginas creen sus tablas
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})();
