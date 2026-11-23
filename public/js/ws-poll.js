/**
 * Refresco de tableros (workstations) con bajo consumo.
 *  - Corre cada `ms` milisegundos, pero se detiene cuando la pestaña o la TV no está visible.
 *  - Al volver a ser visible refresca de inmediato.
 *  - No encadena peticiones: si una sigue en curso, la siguiente espera.
 * El servidor responde 304 cuando nada cambió (ver wsCacheAjax), así casi no se transfieren datos.
 */
window.wsPoll = function (fn, ms) {
    var timer = null, enCurso = false;

    function tick() {
        if (document.hidden || enCurso) return;
        enCurso = true;
        Promise.resolve()
            .then(fn)
            .catch(function () { /* sin conexión: se reintenta en el próximo ciclo */ })
            .then(function () { enCurso = false; });
    }

    function iniciar() {
        if (timer) return;
        timer = setInterval(tick, ms);
    }

    function detener() {
        clearInterval(timer);
        timer = null;
    }

    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            detener();
        } else {
            tick();
            iniciar();
        }
    });

    if (!document.hidden) iniciar();
};
