/**
 * PersonSelect: convierte un <select data-person-select> en un buscador de
 * usuarios de la operación activa (tabla usuarios, vía api/personal/usuarios_operacion.php).
 *
 * El <select> original sigue siendo la fuente de verdad: los formularios, FormData
 * y el código existente que hace `select.value = 'NOMBRE'` siguen funcionando.
 * Si se asigna un valor que ya no existe en la lista (registros antiguos), se agrega
 * como opción "histórica" para no perder el dato al editar.
 *
 * Atributos:
 *   data-person-select            activa el componente
 *   data-cargos="auxiliar,lider"  cargos preferidos (salen primero en la lista)
 *   data-solo-cargos              muestra SOLO los cargos indicados
 *   data-value-field="nombre"     qué se guarda: nombre (default) | id | cedula
 *   data-placeholder="..."        texto cuando no hay selección
 *   multiple                      permite varias personas
 *
 * API JS: PersonSelect.init(root?), PersonSelect.refresh(select), PersonSelect.setValue(select, v)
 */
(function () {
    'use strict';

    var scriptSrc = (document.currentScript && document.currentScript.src) || '';
    var API_URL = (window.WAREPRO_BASE_URL !== undefined)
        ? window.WAREPRO_BASE_URL + '/api/personal/usuarios_operacion.php'
        : scriptSrc.replace(/\/public\/js\/person-select\.js.*$/, '') + '/api/personal/usuarios_operacion.php';

    var usersPromise = null;
    var nativeValue = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');

    var CARGO_LABEL = {
        auxiliar: 'Auxiliar', verificador: 'Verificador', operador: 'Operador', supervisor: 'Supervisor',
        lider: 'Líder', coplas: 'Coplas', admin: 'Admin', super_admin: 'Super admin'
    };

    function loadUsers() {
        if (!usersPromise) {
            usersPromise = fetch(API_URL, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (j) { return (j && j.success) ? j.data : []; })
                .catch(function () { usersPromise = null; return []; });
        }
        return usersPromise;
    }

    function norm(s) {
        return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function injectCss() {
        if (document.getElementById('ps-styles')) return;
        var css = '' +
            '.ps-wrap{position:relative;width:100%}' +
            '.ps-native{position:absolute!important;left:0;bottom:0;width:100%!important;height:1px!important;opacity:0!important;pointer-events:none!important;border:0!important;padding:0!important;margin:0!important}' +
            '.ps-trigger{display:flex!important;align-items:center;gap:.4rem;flex-wrap:wrap;min-height:2.5rem;cursor:pointer;text-align:left;width:100%;box-sizing:border-box}' +
            '.ps-trigger.ps-disabled{cursor:not-allowed;opacity:.65}' +
            '.ps-trigger .ps-text{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}' +
            '.ps-trigger .ps-placeholder{color:#9ca3af}' +
            '.ps-trigger .ps-caret{margin-left:auto;color:#9ca3af;font-size:.75rem;transition:transform .15s}' +
            '.ps-open .ps-caret{transform:rotate(180deg)}' +
            '.ps-chip{display:inline-flex;align-items:center;gap:.3rem;background:#fff7cc;border:1px solid #f5d000;color:#3d3300;border-radius:999px;padding:.05rem .55rem;font-size:.78rem;font-weight:600;max-width:100%}' +
            '.ps-chip button{border:0;background:none;color:#8a6d00;cursor:pointer;font-size:.9rem;line-height:1;padding:0}' +
            '.ps-panel{position:absolute;z-index:9999;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e5e7eb;border-radius:.75rem;box-shadow:0 12px 32px rgba(0,0,0,.16);overflow:hidden;min-width:240px}' +
            '.ps-panel.ps-up{top:auto;bottom:calc(100% + 4px)}' +
            '.ps-trigger:focus{outline:none;border-color:#FFD700!important;box-shadow:0 0 0 3px rgba(255,215,0,.25)!important}' +
            '.ps-search{display:flex;align-items:center;gap:.5rem;padding:.55rem .75rem;border-bottom:1px solid #f1f1f1;background:#fafafa}' +
            '.ps-panel .ps-search input{flex:1;width:auto!important;min-width:0;height:auto!important;border:0!important;outline:0!important;box-shadow:none!important;background:transparent!important;padding:0!important;margin:0!important;font:inherit;font-size:.9rem;color:#111;text-transform:none}' +
            '.ps-list{max-height:260px;overflow-y:auto;padding:.25rem 0;margin:0;list-style:none}' +
            '.ps-item{display:flex;align-items:center;gap:.6rem;padding:.5rem .75rem;cursor:pointer;font-size:.88rem;color:#1f2937}' +
            '.ps-item.ps-active{background:#fff7cc}' +
            '.ps-item.ps-selected{font-weight:600}' +
            '.ps-avatar{flex:0 0 1.9rem;height:1.9rem;border-radius:50%;background:#111;color:#FFD700;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700}' +
            '.ps-name{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}' +
            '.ps-cargo{font-size:.68rem;text-transform:uppercase;letter-spacing:.03em;color:#6b7280;background:#f3f4f6;border-radius:999px;padding:.1rem .5rem;white-space:nowrap}' +
            '.ps-check{color:#16a34a;font-size:.85rem}' +
            '.ps-group{padding:.4rem .75rem .2rem;font-size:.68rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em}' +
            '.ps-empty{padding:1rem .75rem;text-align:center;color:#9ca3af;font-size:.85rem}' +
            '.ps-clear{border:0;background:none;color:#9ca3af;cursor:pointer;font-size:.8rem;padding:0 .25rem}' +
            '.ps-clear:hover{color:#ef4444}';
        var st = document.createElement('style');
        st.id = 'ps-styles';
        st.textContent = css;
        document.head.appendChild(st);
    }

    function initials(name) {
        var p = String(name || '').trim().split(/\s+/);
        return ((p[0] || '')[0] || '').concat((p[1] || '')[0] || '').toUpperCase() || '?';
    }

    function ensureOption(select, value, label) {
        if (value === null || value === undefined || value === '') return;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value == value) return;
        }
        var opt = document.createElement('option');
        opt.value = value;
        opt.textContent = label || value;
        opt.dataset.legacy = '1';
        select.appendChild(opt);
    }

    // Estilos que se copian del <select> original cuando no trae clases (CSS por selector, p.ej. ".form-group select")
    var COPY_STYLES = ['borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'borderLeftWidth',
        'borderTopStyle', 'borderRightStyle', 'borderBottomStyle', 'borderLeftStyle',
        'borderTopColor', 'borderRightColor', 'borderBottomColor', 'borderLeftColor',
        'borderTopLeftRadius', 'borderTopRightRadius', 'borderBottomLeftRadius', 'borderBottomRightRadius',
        'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'fontSize', 'fontFamily', 'color', 'backgroundColor'];

    // Si el select fue clonado (cloneNode) junto con su envoltura, deshace la envoltura muerta
    function unwrapClone(select) {
        var wrap = select.parentNode;
        if (wrap && wrap.classList && wrap.classList.contains('ps-wrap')) {
            Array.prototype.slice.call(wrap.children).forEach(function (c) { if (c !== select) c.remove(); });
            wrap.parentNode.insertBefore(select, wrap);
            wrap.remove();
        }
        select.classList.remove('ps-native');
        select.removeAttribute('tabindex');
        delete select.dataset.psReady;
    }

    var instances = [];

    function enhance(select) {
        if (select.dataset.psReady) {
            if (select._psRefresh) return;
            unwrapClone(select);
        }
        select.dataset.psReady = '1';
        injectCss();

        var copied = null;
        if (!select.className.trim()) {
            var cs = window.getComputedStyle(select);
            copied = {};
            COPY_STYLES.forEach(function (p) { copied[p] = cs[p]; });
        }

        var multiple = select.multiple;
        var valueField = select.dataset.valueField || 'nombre';
        var preferred = (select.dataset.cargos || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        var onlyPreferred = select.hasAttribute('data-solo-cargos');
        var placeholder = select.dataset.placeholder || (multiple ? 'Seleccionar personas...' : 'Buscar persona...');
        var users = [];

        var wrap = document.createElement('div');
        wrap.className = 'ps-wrap';
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        var trigger = document.createElement('div');
        trigger.className = (select.className || '') + ' ps-trigger';
        trigger.tabIndex = 0;
        trigger.setAttribute('role', 'combobox');
        if (copied) Object.keys(copied).forEach(function (p) { if (copied[p]) trigger.style[p] = copied[p]; });
        wrap.insertBefore(trigger, select);
        select.classList.add('ps-native');
        select.tabIndex = -1;

        var panel = null, input = null, list = null, activeIndex = -1, visible = [], lastKey = null;

        function userValue(u) { return String(u[valueField] != null ? u[valueField] : u.nombre); }
        function userByValue(v) {
            for (var i = 0; i < users.length; i++) if (userValue(users[i]) == v) return users[i];
            return null;
        }
        // Valor guardado en otro formato (MAYÚSCULAS, sin tildes, espacios dobles) -> valor del usuario real
        function canonical(v) {
            if (v === null || v === undefined || v === '') return v;
            if (userByValue(v)) return v;
            var nv = norm(v);
            for (var i = 0; i < users.length; i++) if (norm(userValue(users[i])) === nv) return userValue(users[i]);
            return v;
        }

        function syncOptions() {
            // Conserva valores seleccionados (incluye históricos) y reconstruye la lista
            var selected = Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; })
                .map(function (o) { var c = canonical(o.value); return { value: c, label: c === o.value ? o.textContent : labelFor(c) }; });
            // Valores por defecto (<option selected> del HTML) para que form.reset() vuelva a ellos
            var defaults = Array.prototype.filter.call(select.options, function (o) { return o.defaultSelected && o.value !== ''; })
                .map(function (o) { return canonical(o.value); });
            var keepEmpty = !multiple;
            select.innerHTML = '';
            if (keepEmpty) {
                var empty = document.createElement('option');
                empty.value = '';
                empty.textContent = placeholder;
                select.appendChild(empty);
            }
            users.forEach(function (u) {
                var o = document.createElement('option');
                o.value = userValue(u);
                o.textContent = u.nombre;
                select.appendChild(o);
            });
            defaults.forEach(function (v) {
                ensureOption(select, v);
                Array.prototype.forEach.call(select.options, function (o) { if (o.value == v) o.defaultSelected = true; });
            });
            Array.prototype.forEach.call(select.options, function (o) { o.selected = false; });
            selected.forEach(function (s) {
                ensureOption(select, s.value, s.label);
                Array.prototype.forEach.call(select.options, function (o) { if (o.value == s.value) o.selected = true; });
            });
            if (keepEmpty && !selected.length) nativeValue.set.call(select, '');
            render();
        }

        function selectedValues() {
            return Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; })
                .map(function (o) { return o.value; });
        }

        function labelFor(v) {
            var u = userByValue(v);
            if (u) return u.nombre;
            for (var i = 0; i < select.options.length; i++) if (select.options[i].value == v) return select.options[i].textContent;
            return v;
        }

        function stateKey() { return selectedValues().join('\u0001') + '|' + select.disabled; }

        function render() {
            var vals = selectedValues();
            var disabled = select.disabled;
            lastKey = stateKey();
            trigger.classList.toggle('ps-disabled', disabled);
            if (!vals.length) {
                trigger.innerHTML = '<span class="ps-text ps-placeholder">' + escapeHtml(placeholder) + '</span><span class="ps-caret">&#9660;</span>';
                return;
            }
            if (multiple) {
                trigger.innerHTML = vals.map(function (v) {
                    return '<span class="ps-chip">' + escapeHtml(labelFor(v)) +
                        (disabled ? '' : '<button type="button" data-remove="' + escapeHtml(v) + '" aria-label="Quitar">&times;</button>') + '</span>';
                }).join('') + '<span class="ps-caret">&#9660;</span>';
            } else {
                trigger.innerHTML = '<span class="ps-text">' + escapeHtml(labelFor(vals[0])) + '</span>' +
                    (disabled || select.required ? '' : '<button type="button" class="ps-clear" data-clear="1" aria-label="Limpiar">&times;</button>') +
                    '<span class="ps-caret">&#9660;</span>';
            }
        }

        function fire() {
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function setSelected(v, on) {
            Array.prototype.forEach.call(select.options, function (o) {
                if (o.value == v) o.selected = on;
                else if (!multiple && on) o.selected = false;
            });
            render();
            fire();
        }

        function buildList(q) {
            var nq = norm(q);
            var vals = selectedValues();
            var pool = users.filter(function (u) {
                if (onlyPreferred && preferred.length && preferred.indexOf(u.cargo) === -1) return false;
                return !nq || norm(u.nombre).indexOf(nq) !== -1 || String(u.cedula || '').indexOf(nq) !== -1 || norm(CARGO_LABEL[u.cargo] || u.cargo).indexOf(nq) !== -1;
            });
            var first = [], rest = [];
            pool.forEach(function (u) { (preferred.length && preferred.indexOf(u.cargo) !== -1 ? first : rest).push(u); });
            visible = first.concat(rest);

            var html = '';
            var legacy = Array.prototype.filter.call(select.options, function (o) {
                return o.dataset.legacy && (!nq || norm(o.textContent).indexOf(nq) !== -1);
            });
            if (!visible.length && !legacy.length) {
                html = '<li class="ps-empty">' + (users.length ? 'Sin coincidencias' : 'No hay usuarios activos en esta operación') + '</li>';
            } else {
                var idx = 0;
                function item(u) {
                    var v = userValue(u);
                    var sel = vals.indexOf(v) !== -1;
                    return '<li class="ps-item' + (sel ? ' ps-selected' : '') + '" data-index="' + (idx++) + '" data-value="' + escapeHtml(v) + '">' +
                        '<span class="ps-avatar">' + escapeHtml(initials(u.nombre)) + '</span>' +
                        '<span class="ps-name">' + escapeHtml(u.nombre) + '</span>' +
                        '<span class="ps-cargo">' + escapeHtml(CARGO_LABEL[u.cargo] || u.cargo || '') + '</span>' +
                        (sel ? '<span class="ps-check">&#10003;</span>' : '') + '</li>';
                }
                if (first.length && rest.length) {
                    html += '<li class="ps-group">Sugeridos</li>' + first.map(item).join('') + '<li class="ps-group">Otros</li>' + rest.map(item).join('');
                } else {
                    html += visible.map(item).join('');
                }
                if (legacy.length) {
                    html += '<li class="ps-group">Registros anteriores</li>';
                    legacy.forEach(function (o) {
                        visible.push({ nombre: o.textContent, _legacy: o.value });
                        var sel = vals.indexOf(o.value) !== -1;
                        html += '<li class="ps-item' + (sel ? ' ps-selected' : '') + '" data-index="' + (idx++) + '" data-value="' + escapeHtml(o.value) + '">' +
                            '<span class="ps-avatar" style="background:#9ca3af;color:#fff">?</span><span class="ps-name">' + escapeHtml(o.textContent) + '</span>' +
                            (sel ? '<span class="ps-check">&#10003;</span>' : '') + '</li>';
                    });
                }
            }
            list.innerHTML = html;
            activeIndex = visible.length ? 0 : -1;
            highlight();
        }

        function highlight() {
            Array.prototype.forEach.call(list.querySelectorAll('.ps-item'), function (li) {
                var on = +li.dataset.index === activeIndex;
                li.classList.toggle('ps-active', on);
                if (on) li.scrollIntoView({ block: 'nearest' });
            });
        }

        function open() {
            if (select.disabled || panel) return;
            panel = document.createElement('div');
            panel.className = 'ps-panel';
            panel.innerHTML = '<div class="ps-search"><span style="color:#9ca3af">&#128269;</span><input type="text" placeholder="Escribe nombre, cédula o cargo..." autocomplete="off"></div><ul class="ps-list"></ul>';
            wrap.appendChild(panel);
            wrap.classList.add('ps-open');
            input = panel.querySelector('input');
            list = panel.querySelector('ul');
            var rect = wrap.getBoundingClientRect();
            if (window.innerHeight - rect.bottom < 320 && rect.top > 320) panel.classList.add('ps-up');
            list.innerHTML = '<li class="ps-empty">Cargando...</li>';
            loadUsers().then(function () { if (panel) buildList(input.value); });
            input.addEventListener('input', function () { buildList(input.value); });
            input.addEventListener('keydown', onKey);
            list.addEventListener('mousedown', function (e) { e.preventDefault(); });
            list.addEventListener('click', function (e) {
                var li = e.target.closest('.ps-item');
                if (li) choose(li.dataset.value);
            });
            setTimeout(function () { input.focus(); }, 0);
        }

        function close() {
            if (!panel) return;
            panel.remove();
            panel = input = list = null;
            wrap.classList.remove('ps-open');
        }

        function choose(v) {
            if (multiple) {
                var on = selectedValues().indexOf(v) === -1;
                setSelected(v, on);
                buildList(input ? input.value : '');
                if (input) input.focus();
            } else {
                setSelected(v, true);
                close();
                trigger.focus();
            }
        }

        function onKey(e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); if (activeIndex < visible.length - 1) activeIndex++; highlight(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); if (activeIndex > 0) activeIndex--; highlight(); }
            else if (e.key === 'Enter') {
                e.preventDefault();
                var li = list.querySelector('.ps-item[data-index="' + activeIndex + '"]');
                if (li) choose(li.dataset.value);
            }
            else if (e.key === 'Escape') { close(); trigger.focus(); }
            else if (e.key === 'Tab') { close(); }
        }

        trigger.addEventListener('click', function (e) {
            var rm = e.target.closest('[data-remove]');
            if (rm) { e.stopPropagation(); setSelected(rm.dataset.remove, false); return; }
            if (e.target.closest('[data-clear]')) { e.stopPropagation(); setSelected('', true); return; }
            panel ? close() : open();
        });
        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') { e.preventDefault(); open(); }
        });
        document.addEventListener('mousedown', function (e) { if (panel && !wrap.contains(e.target)) close(); });

        // Mantener la UI sincronizada con cambios hechos por código existente
        Object.defineProperty(select, 'value', {
            configurable: true,
            get: function () { return nativeValue.get.call(select); },
            set: function (v) {
                v = canonical(v);
                if (v !== '' && v != null) ensureOption(select, v);
                nativeValue.set.call(select, v == null ? '' : v);
                render();
            }
        });
        select.addEventListener('change', render);
        if (select.form) select.form.addEventListener('reset', function () { setTimeout(render, 0); });
        new MutationObserver(render).observe(select, { attributes: true, attributeFilter: ['disabled'] });

        select._psRefresh = render;
        // Cambios por código que no pasan por .value (jQuery .val(), selectedIndex, option.selected)
        instances.push(function () {
            if (!select.isConnected) return false;
            if (stateKey() !== lastKey) render();
            return true;
        });
        startPolling();

        // Si el select ya traía opciones fijas (nombres antiguos), se conservan como histórico
        Array.prototype.forEach.call(select.options, function (o) { if (o.value !== '' && !o.selected) o.dataset.legacy = '1'; });

        loadUsers().then(function (data) {
            users = data;
            // Quitar opciones fijas que no son usuarios ni están seleccionadas
            Array.prototype.slice.call(select.options).forEach(function (o) {
                if (o.dataset.legacy && !o.selected && !userByValue(o.value)) o.remove();
            });
            syncOptions();
        });
        render();
    }

    var pollTimer = null;
    function startPolling() {
        if (pollTimer) return;
        pollTimer = setInterval(function () {
            instances = instances.filter(function (check) { return check(); });
            if (!instances.length) { clearInterval(pollTimer); pollTimer = null; }
        }, 300);
    }

    var PersonSelect = {
        init: function (root) {
            (root || document).querySelectorAll('select[data-person-select]').forEach(enhance);
        },
        refresh: function (select) { if (select && select._psRefresh) select._psRefresh(); },
        setValue: function (select, v) {
            if (Array.isArray(v)) {
                v.forEach(function (x) { ensureOption(select, x); });
                Array.prototype.forEach.call(select.options, function (o) { o.selected = v.indexOf(o.value) !== -1; });
                PersonSelect.refresh(select);
            } else {
                select.value = v;
            }
        },
        getValues: function (select) {
            return Array.prototype.filter.call(select.options, function (o) { return o.selected && o.value !== ''; }).map(function (o) { return o.value; });
        },
        loadUsers: loadUsers
    };
    window.PersonSelect = PersonSelect;

    function boot() {
        PersonSelect.init();
        new MutationObserver(function (muts) {
            muts.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType !== 1) return;
                    if (n.matches && n.matches('select[data-person-select]')) enhance(n);
                    else if (n.querySelectorAll) PersonSelect.init(n);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
