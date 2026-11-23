<?php
require_once 'core/config.php';

if (isset($_POST['action']) && $_POST['action'] == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $cedula = trim((string)($_POST['cedula'] ?? ''));
    $passwordPlano = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, nombre, cedula, cargo, operacion_id, password FROM usuarios WHERE cedula = ? AND activo = 1");
    $stmt->execute([$cedula]);
    $usuario = $stmt->fetch();

    if ($usuario && verificarPassword($passwordPlano, $usuario['password'])) {
        // Nueva sesión al autenticarse y token único: una sesión activa por usuario
        session_regenerate_id(true);
        $sessionToken = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE usuarios SET session_token = ? WHERE id = ?')->execute([$sessionToken, $usuario['id']]);
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['cedula'] = $usuario['cedula'];
        $_SESSION['cargo'] = $usuario['cargo'];
        $_SESSION['operacion_id'] = $usuario['operacion_id'];
        $_SESSION['operacion_activa_id'] = $usuario['operacion_id'];
        $_SESSION['session_token'] = $sessionToken;
        // Migra contraseñas MD5 antiguas a hash seguro en el primer ingreso
        if (!password_verify($passwordPlano, $usuario['password'])) {
            $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?')->execute([hashPassword($passwordPlano), $usuario['id']]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cédula o contraseña incorrectas']);
    }
    exit();
}

if (isset($_SESSION['usuario_id'], $_SESSION['nombre'], $_SESSION['cargo'])) {
    header('Location: modules/reportes/dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0d0d0d">
    <title>Ware Pro - Iniciar sesión</title>
    <link rel="icon" type="image/png" href="public/img/logo.png">
    <link rel="manifest" href="public/manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --amarillo: #FFD700; --naranja: #FFA500; --negro: #0d0d0d; --gris: #6b7280; --borde: #e5e7eb; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; min-height: 100vh; background: #fff; color: #111; }
        .layout { display: grid; grid-template-columns: 1.15fr 1fr; min-height: 100vh; }

        /* ---------- Panel animado ---------- */
        .escena { position: relative; overflow: hidden; background: radial-gradient(circle at 30% 20%, #232323 0%, var(--negro) 65%); color: #fff;
            display: flex; flex-direction: column; justify-content: space-between; padding: 40px 48px; }
        .escena::after { content: ''; position: absolute; inset: auto 0 0 0; height: 10px;
            background: repeating-linear-gradient(45deg, var(--amarillo) 0 20px, #000 20px 40px); opacity: .9; }
        .escena-top img { height: 46px; width: auto; }
        .escena-texto h2 { font-size: clamp(1.9rem, 3.2vw, 2.8rem); font-weight: 800; line-height: 1.1; letter-spacing: -.02em; }
        .escena-texto h2 span { color: var(--amarillo); display: inline-block; min-width: 6ch; }
        .escena-texto p { color: #a3a3a3; margin-top: 10px; max-width: 420px; line-height: 1.6; }
        .escena svg { width: 100%; height: auto; max-height: 52vh; display: block; margin: 12px 0 18px; }

        .cinta-mov { animation: cinta .8s linear infinite; }
        @keyframes cinta { to { stroke-dashoffset: -24; } }
        .caja-mov { animation: viaje 6s linear infinite; }
        .caja-mov.c2 { animation-delay: -2s; }
        .caja-mov.c3 { animation-delay: -4s; }
        @keyframes viaje { from { transform: translateX(-140px); } to { transform: translateX(560px); } }
        .montacarga { animation: ir 7s ease-in-out infinite; }
        @keyframes ir { 0%, 100% { transform: translateX(0); } 45%, 55% { transform: translateX(150px); } }
        .horquilla { animation: subir 7s ease-in-out infinite; }
        @keyframes subir { 0%, 40% { transform: translateY(0); } 50% { transform: translateY(-34px); } 60%, 100% { transform: translateY(0); } }
        .rueda { animation: girar 1s linear infinite; transform-box: fill-box; transform-origin: center; }
        @keyframes girar { to { transform: rotate(360deg); } }
        .luz { animation: parpadeo 2.4s ease-in-out infinite; }
        .luz.l2 { animation-delay: .8s; } .luz.l3 { animation-delay: 1.6s; }
        @keyframes parpadeo { 0%, 100% { opacity: .25; } 50% { opacity: 1; } }
        .faro { animation: faro 7s ease-in-out infinite; transform-box: fill-box; transform-origin: left center; }
        @keyframes faro { 0%, 100% { opacity: .35; } 50% { opacity: .7; } }

        /* ---------- Formulario ---------- */
        .acceso { display: flex; align-items: center; justify-content: center; padding: 40px 24px; background: #fafafa; }
        .tarjeta { width: 100%; max-width: 400px; }
        .tarjeta .logo { height: 70px; width: auto; display: block; margin-bottom: 26px; }
        .tarjeta h1 { font-size: 1.75rem; font-weight: 700; letter-spacing: -.01em; }
        .tarjeta .sub { color: var(--gris); font-size: .92rem; margin: 6px 0 28px; }
        .pasos { display: flex; gap: 6px; margin-bottom: 22px; }
        .pasos i { height: 4px; flex: 1; border-radius: 4px; background: var(--borde); transition: background .3s; }
        .pasos i.on { background: linear-gradient(90deg, var(--amarillo), var(--naranja)); }
        .campo { position: relative; margin-bottom: 14px; }
        .campo label { display: block; font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .campo input { width: 100%; font: inherit; font-size: 1rem; padding: 14px 46px 14px 44px; border: 2px solid var(--borde); border-radius: 14px; background: #fff; outline: none; transition: border-color .2s, box-shadow .2s; }
        .campo input:focus { border-color: var(--amarillo); box-shadow: 0 0 0 4px rgba(255, 215, 0, .18); }
        .campo .ico { position: absolute; left: 14px; bottom: 15px; width: 20px; height: 20px; color: #9ca3af; pointer-events: none; }
        .campo .ver { position: absolute; right: 10px; bottom: 9px; width: 34px; height: 34px; border: 0; background: none; color: #9ca3af; cursor: pointer; border-radius: 8px; }
        .campo .ver:hover { color: #111; background: #f3f4f6; }
        .clave { display: none; }
        .clave.visible { display: block; animation: aparecer .3s ease; }
        @keyframes aparecer { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
        .cedula-fija { display: none; align-items: center; justify-content: space-between; gap: 10px; background: #fff; border: 2px solid var(--borde); border-radius: 14px; padding: 10px 14px; margin-bottom: 14px; font-weight: 600; }
        .cedula-fija.visible { display: flex; }
        .cedula-fija button { font: inherit; font-size: .8rem; font-weight: 600; border: 0; background: none; color: #b38f00; cursor: pointer; }
        .btn { width: 100%; font: inherit; font-weight: 700; font-size: 1rem; padding: 15px; border: 0; border-radius: 14px; cursor: pointer;
            background: #111; color: var(--amarillo); display: flex; align-items: center; justify-content: center; gap: 10px; transition: transform .15s, box-shadow .15s, opacity .15s; }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 10px 24px -10px rgba(0, 0, 0, .6); }
        .btn:disabled { opacity: .7; cursor: wait; transform: none; }
        .spin { width: 18px; height: 18px; border: 3px solid rgba(255, 215, 0, .3); border-top-color: var(--amarillo); border-radius: 50%; animation: girar .8s linear infinite; display: none; }
        .btn.cargando .spin { display: inline-block; }
        .msg { min-height: 22px; margin-top: 12px; font-size: .85rem; font-weight: 500; text-align: center; }
        .msg.error { color: #dc2626; }
        .msg.ok { color: #15803d; }
        .seguro { margin-top: 26px; display: flex; align-items: center; justify-content: center; gap: 8px; color: #9ca3af; font-size: .78rem; }
        .seguro svg { width: 15px; height: 15px; color: #16a34a; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .escena { padding: 24px 20px 30px; min-height: auto; }
            .escena-texto, .escena-top { display: none; }
            .escena svg { max-height: 190px; margin: 0; }
            .acceso { padding: 28px 16px 40px; align-items: flex-start; }
        }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; } }
    </style>
</head>
<body>
<div class="layout">
    <section class="escena" aria-hidden="true">
        <div class="escena-top"><img src="public/img/logo_header.webp" alt="" onerror="this.src='public/img/logo_header.png'"></div>

        <svg viewBox="0 0 560 300" xmlns="http://www.w3.org/2000/svg">
            <!-- Rack -->
            <g stroke="#3a3a3a" stroke-width="4" fill="none">
                <path d="M370 40 V240 M540 40 V240 M370 90 H540 M370 140 H540 M370 190 H540"/>
            </g>
            <g>
                <rect x="382" y="58" width="40" height="30" rx="3" fill="#FFD700"/>
                <rect x="430" y="64" width="34" height="24" rx="3" fill="#FFA500"/>
                <rect x="478" y="56" width="50" height="32" rx="3" fill="#FFD700" opacity=".85"/>
                <rect x="390" y="110" width="52" height="28" rx="3" fill="#FFA500" opacity=".9"/>
                <rect x="452" y="114" width="38" height="24" rx="3" fill="#FFD700"/>
                <rect x="384" y="160" width="36" height="28" rx="3" fill="#FFD700" opacity=".8"/>
                <rect x="490" y="158" width="40" height="30" rx="3" fill="#FFA500"/>
            </g>
            <circle class="luz" cx="380" cy="48" r="4" fill="#FFD700"/>
            <circle class="luz l2" cx="455" cy="48" r="4" fill="#FFD700"/>
            <circle class="luz l3" cx="530" cy="48" r="4" fill="#FFD700"/>

            <!-- Piso -->
            <path d="M0 262 H560" stroke="#2a2a2a" stroke-width="3"/>

            <!-- Banda transportadora -->
            <rect x="0" y="214" width="340" height="14" rx="7" fill="#1c1c1c" stroke="#3a3a3a" stroke-width="2"/>
            <line class="cinta-mov" x1="6" y1="221" x2="334" y2="221" stroke="#FFD700" stroke-width="2" stroke-dasharray="12 12" opacity=".5"/>
            <g fill="#2a2a2a"><rect x="30" y="228" width="6" height="34"/><rect x="170" y="228" width="6" height="34"/><rect x="304" y="228" width="6" height="34"/></g>
            <clipPath id="clipBanda"><rect x="0" y="150" width="340" height="66"/></clipPath>
            <g clip-path="url(#clipBanda)">
                <g class="caja-mov"><rect x="0" y="186" width="34" height="28" rx="3" fill="#FFD700"/><path d="M0 196 H34" stroke="#000" stroke-opacity=".25" stroke-width="2"/></g>
                <g class="caja-mov c2"><rect x="0" y="180" width="44" height="34" rx="3" fill="#FFA500"/><path d="M22 180 V214" stroke="#000" stroke-opacity=".25" stroke-width="2"/></g>
                <g class="caja-mov c3"><rect x="0" y="190" width="28" height="24" rx="3" fill="#FFD700"/></g>
            </g>

            <!-- Montacargas -->
            <g class="montacarga">
                <path class="faro" d="M168 214 L240 196 L240 232 Z" fill="#FFD700" opacity=".35"/>
                <g class="horquilla">
                    <rect x="150" y="150" width="6" height="80" fill="#9ca3af"/>
                    <rect x="150" y="226" width="34" height="5" fill="#9ca3af"/>
                    <rect x="152" y="196" width="30" height="28" rx="3" fill="#FFD700"/>
                </g>
                <rect x="96" y="196" width="56" height="44" rx="6" fill="#FFD700"/>
                <path d="M100 196 V160 H138 L146 196" fill="none" stroke="#111" stroke-width="5" stroke-linejoin="round"/>
                <rect x="74" y="206" width="24" height="34" rx="4" fill="#1a1a1a"/>
                <circle class="rueda" cx="108" cy="246" r="13" fill="#111" stroke="#444" stroke-width="3" stroke-dasharray="6 4"/>
                <circle class="rueda" cx="142" cy="248" r="11" fill="#111" stroke="#444" stroke-width="3" stroke-dasharray="6 4"/>
            </g>
        </svg>

        <div class="escena-texto">
            <h2>Tu operación,<br><span id="palabra">organizada</span>.</h2>
            <p>Productividad, indicadores, compensación y entrenamientos de tu almacén en un solo lugar.</p>
        </div>
    </section>

    <main class="acceso">
        <div class="tarjeta">
            <img src="public/img/logotipo.png" alt="Ware Pro" class="logo" width="62" height="70">
            <h1>Iniciar sesión</h1>
            <p class="sub" id="sub">Ingresa tu número de cédula para continuar.</p>

            <div class="pasos" aria-hidden="true"><i class="on" id="p1"></i><i id="p2"></i></div>

            <form id="loginForm" autocomplete="on" novalidate>
                <div class="campo" id="campoCedula">
                    <label for="cedula">Número de cédula</label>
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="12" r="2.5"/><path d="M14 10h4M14 14h3"/></svg>
                    <input type="text" id="cedula" name="cedula" inputmode="numeric" autocomplete="username" maxlength="10" placeholder="Ej: 1090123456" required autofocus>
                </div>

                <div class="cedula-fija" id="cedulaFija">
                    <span id="cedulaTxt"></span>
                    <button type="button" id="cambiar">Cambiar</button>
                </div>

                <div class="campo clave" id="campoClave">
                    <label for="password">Contraseña</label>
                    <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <input type="password" id="password" name="password" autocomplete="current-password" placeholder="Tu contraseña">
                    <button type="button" class="ver" id="ver" aria-label="Mostrar contraseña">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>

                <button type="submit" class="btn" id="btn"><span class="spin"></span><span id="btnTxt">Continuar</span></button>
                <p class="msg<?= isset($_GET['sesion_expirada']) ? ' error' : '' ?>" id="msg" role="status" aria-live="polite"><?= isset($_GET['sesion_expirada']) ? 'Tu sesión se cerró porque se inició en otro dispositivo.' : '' ?></p>
            </form>

            <p class="seguro">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
                Conexión segura · Ware-Pro
            </p>
        </div>
    </main>
</div>

<script>
(function () {
    const form = document.getElementById('loginForm');
    const cedula = document.getElementById('cedula');
    const password = document.getElementById('password');
    const btn = document.getElementById('btn');
    const msg = document.getElementById('msg');
    let paso = 1;

    function mensaje(texto, tipo) { msg.textContent = texto || ''; msg.className = 'msg' + (tipo ? ' ' + tipo : ''); }

    function irPaso(n) {
        paso = n;
        document.getElementById('p2').classList.toggle('on', n === 2);
        document.getElementById('campoCedula').style.display = n === 1 ? '' : 'none';
        document.getElementById('cedulaFija').classList.toggle('visible', n === 2);
        document.getElementById('campoClave').classList.toggle('visible', n === 2);
        document.getElementById('cedulaTxt').textContent = 'C.C. ' + cedula.value;
        document.getElementById('btnTxt').textContent = n === 1 ? 'Continuar' : 'Ingresar';
        document.getElementById('sub').textContent = n === 1 ? 'Ingresa tu número de cédula para continuar.' : 'Ahora escribe tu contraseña.';
        mensaje('');
        (n === 1 ? cedula : password).focus();
    }

    cedula.addEventListener('input', function () { this.value = this.value.replace(/\D/g, '').slice(0, 10); });
    document.getElementById('cambiar').addEventListener('click', function () { password.value = ''; irPaso(1); });
    document.getElementById('ver').addEventListener('click', function () {
        password.type = password.type === 'password' ? 'text' : 'password';
        password.focus();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        if (paso === 1) {
            if (cedula.value.length < 5) { mensaje('Escribe un número de cédula válido.', 'error'); cedula.focus(); return; }
            irPaso(2);
            return;
        }
        if (!password.value) { mensaje('Escribe tu contraseña.', 'error'); password.focus(); return; }

        btn.disabled = true; btn.classList.add('cargando'); mensaje('');
        const datos = new FormData(form);
        datos.append('action', 'login');
        try {
            const r = await fetch('index.php', { method: 'POST', body: datos, credentials: 'same-origin' });
            const j = await r.json();
            if (j.success) {
                mensaje('¡Bienvenido! Entrando...', 'ok');
                window.location.href = 'modules/reportes/dashboard.php';
                return;
            }
            mensaje(j.message || 'Cédula o contraseña incorrectas', 'error');
            password.select();
        } catch (err) {
            mensaje('No se pudo conectar con el servidor. Intenta de nuevo.', 'error');
        }
        btn.disabled = false; btn.classList.remove('cargando');
    });

    // Palabra rotativa del panel
    const palabras = ['organizada', 'medida', 'productiva', 'segura'];
    const el = document.getElementById('palabra');
    let i = 0;
    if (el && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
        setInterval(function () {
            if (document.hidden) return;
            i = (i + 1) % palabras.length;
            el.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateY(6px)' }], { duration: 180 }).onfinish = function () {
                el.textContent = palabras[i];
                el.animate([{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }], { duration: 220 });
            };
        }, 2800);
    }
})();
</script>
</body>
</html>
