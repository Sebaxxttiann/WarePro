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

    window.WP = {
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

    function iniciar() { idiomaTablas(); procesar(document); observar(); }
    idiomaTablas(); // jQuery ya cargado antes que este script (defer): se registra antes de que las páginas creen sus tablas
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar);
    else iniciar();
})();
