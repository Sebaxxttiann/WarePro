/**
 * DpoMedia: prepara la foto en el navegador ANTES de subirla, para no gastar
 * almacenamiento ni ancho de banda del hosting.
 *
 *  comprimirImagen(file): redimensiona a máx. 1600 px y guarda en JPG (≈ 150–400 KB) + miniatura de 360 px.
 */
(function () {
    'use strict';

    var MAX_LADO = 1600, CALIDAD = 0.78, LADO_MINI = 360, CALIDAD_MINI = 0.7;

    function canvasABlob(canvas, calidad) {
        return new Promise(function (ok, mal) {
            canvas.toBlob(function (b) { b ? ok(b) : mal(new Error('No se pudo procesar la imagen.')); }, 'image/jpeg', calidad);
        });
    }

    function dibujar(fuente, ancho, alto, lado) {
        var esc = Math.min(1, lado / Math.max(ancho, alto));
        var c = document.createElement('canvas');
        c.width = Math.round(ancho * esc);
        c.height = Math.round(alto * esc);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(fuente, 0, 0, c.width, c.height);
        return c;
    }

    function cargarImagen(file) {
        return new Promise(function (ok, mal) {
            var url = URL.createObjectURL(file);
            var img = new Image();
            img.onload = function () { ok({ img: img, url: url }); };
            img.onerror = function () {
                URL.revokeObjectURL(url);
                mal(new Error('No se pudo leer la foto. Si es HEIC, tómala con la cámara desde aquí o envíala como JPG.'));
            };
            img.src = url; // el navegador ya respeta la orientación EXIF al dibujar
        });
    }

    function comprimirImagen(file) {
        if (!file.type || file.type.indexOf('image/') !== 0) return Promise.reject(new Error('Solo se permiten fotos (JPG, PNG o WebP).'));
        return cargarImagen(file).then(function (r) {
            var w = r.img.naturalWidth, h = r.img.naturalHeight;
            var grande = dibujar(r.img, w, h, MAX_LADO);
            var mini = dibujar(r.img, w, h, LADO_MINI);
            URL.revokeObjectURL(r.url);
            return Promise.all([canvasABlob(grande, CALIDAD), canvasABlob(mini, CALIDAD_MINI)]).then(function (b) {
                // Si la original ya era más liviana (foto pequeña), se conserva
                var archivo = (file.type === 'image/jpeg' && file.size <= b[0].size) ? file : b[0];
                return { archivo: archivo, miniatura: b[1], original: file.size, ancho: grande.width, alto: grande.height };
            });
        });
    }

    function mb(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    window.DpoMedia = { comprimirImagen: comprimirImagen, mb: mb };
})();
