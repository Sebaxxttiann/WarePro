/**
 * DpoMedia: prepara las evidencias en el navegador ANTES de subirlas, para no gastar
 * almacenamiento ni ancho de banda del hosting.
 *
 *  - comprimirImagen(file): redimensiona a máx. 1600 px y guarda en JPG (≈ 150–400 KB) + miniatura 360 px.
 *  - prepararVideo(file):   valida duración/peso y saca una miniatura del primer segundo.
 *  - Grabador:              graba video dentro de la app a 720p y ~1 Mbps (≈ 7 MB por minuto).
 */
(function () {
    'use strict';

    var MAX_LADO = 1600, CALIDAD = 0.78, LADO_MINI = 360, CALIDAD_MINI = 0.7;
    var MAX_VIDEO_BYTES = 40 * 1024 * 1024, MAX_VIDEO_SEG = 180, MAX_GRABACION_SEG = 90;

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
        return cargarImagen(file).then(function (r) {
            var w = r.img.naturalWidth, h = r.img.naturalHeight;
            var grande = dibujar(r.img, w, h, MAX_LADO);
            var mini = dibujar(r.img, w, h, LADO_MINI);
            URL.revokeObjectURL(r.url);
            return Promise.all([canvasABlob(grande, CALIDAD), canvasABlob(mini, CALIDAD_MINI)]).then(function (b) {
                // Si la original ya era más liviana (foto pequeña), se conserva
                var archivo = (file.type === 'image/jpeg' && file.size <= b[0].size) ? file : b[0];
                return { tipo: 'imagen', archivo: archivo, miniatura: b[1], original: file.size, ancho: grande.width, alto: grande.height };
            });
        });
    }

    function prepararVideo(blob) {
        if (blob.size > MAX_VIDEO_BYTES) {
            return Promise.reject(new Error('El video pesa ' + mb(blob.size) + '. Máximo ' + mb(MAX_VIDEO_BYTES) + ': usa "Grabar video" aquí mismo, queda mucho más liviano.'));
        }
        return new Promise(function (ok, mal) {
            var url = URL.createObjectURL(blob);
            var v = document.createElement('video');
            v.muted = true; v.playsInline = true; v.preload = 'metadata'; v.src = url;
            var listo = false;
            function terminar(miniatura) {
                if (listo) return; listo = true;
                var dur = isFinite(v.duration) ? Math.round(v.duration) : null;
                URL.revokeObjectURL(url);
                if (dur && dur > MAX_VIDEO_SEG) return mal(new Error('El video dura ' + dur + ' s. Máximo ' + MAX_VIDEO_SEG + ' s.'));
                ok({ tipo: 'video', archivo: blob, miniatura: miniatura, original: blob.size, duracion: dur });
            }
            v.onloadedmetadata = function () {
                // Los videos grabados con MediaRecorder a veces no traen duración hasta recorrerlos
                v.currentTime = Math.min(1, (isFinite(v.duration) ? v.duration : 2) / 2);
            };
            v.onseeked = function () {
                try {
                    canvasABlob(dibujar(v, v.videoWidth, v.videoHeight, LADO_MINI), CALIDAD_MINI).then(terminar, function () { terminar(null); });
                } catch (e) { terminar(null); }
            };
            v.onerror = function () { terminar(null); }; // formato que el navegador no reproduce: se sube sin miniatura
            setTimeout(function () { terminar(null); }, 8000);
        });
    }

    function mb(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    /** Graba video en el navegador con resolución y bitrate controlados. */
    function Grabador(videoEl, alCambiar) {
        this.video = videoEl;
        this.alCambiar = alCambiar || function () {};
        this.stream = null; this.rec = null; this.partes = []; this.inicio = 0; this.timer = null;
    }
    Grabador.soportado = function () {
        return !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && window.MediaRecorder);
    };
    Grabador.prototype.abrir = function () {
        var self = this;
        return navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 }, frameRate: { ideal: 24, max: 30 } },
            audio: true
        }).then(function (s) {
            self.stream = s;
            self.video.srcObject = s;
            self.video.muted = true;
            return self.video.play();
        });
    };
    Grabador.prototype.iniciar = function () {
        var self = this;
        var tipos = ['video/mp4;codecs=avc1', 'video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];
        var mime = tipos.find(function (t) { return MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t); }) || '';
        this.partes = [];
        this.rec = new MediaRecorder(this.stream, { mimeType: mime || undefined, videoBitsPerSecond: 1000000, audioBitsPerSecond: 64000 });
        this.rec.ondataavailable = function (e) { if (e.data && e.data.size) self.partes.push(e.data); };
        this.rec.start(1000);
        this.inicio = Date.now();
        this.timer = setInterval(function () {
            var seg = Math.floor((Date.now() - self.inicio) / 1000);
            self.alCambiar(seg, MAX_GRABACION_SEG);
            if (seg >= MAX_GRABACION_SEG) self.detener();
        }, 250);
    };
    Grabador.prototype.detener = function () {
        var self = this;
        clearInterval(this.timer);
        return new Promise(function (ok) {
            if (!self.rec || self.rec.state === 'inactive') return ok(null);
            self.rec.onstop = function () {
                var tipo = (self.rec.mimeType || 'video/webm').split(';')[0];
                ok(new Blob(self.partes, { type: tipo }));
            };
            self.rec.stop();
        });
    };
    Grabador.prototype.cerrar = function () {
        clearInterval(this.timer);
        if (this.rec && this.rec.state !== 'inactive') try { this.rec.stop(); } catch (e) {}
        if (this.stream) this.stream.getTracks().forEach(function (t) { t.stop(); });
        this.video.srcObject = null;
        this.stream = null;
    };

    window.DpoMedia = { comprimirImagen: comprimirImagen, prepararVideo: prepararVideo, Grabador: Grabador, mb: mb, MAX_GRABACION_SEG: MAX_GRABACION_SEG };
})();
