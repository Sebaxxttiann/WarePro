<?php
// Página 404 personalizada. No usa sesión ni base de datos: una ruta inexistente no debe costar consultas.
http_response_code(404);
header('Cache-Control: no-store');

$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$inicio = $base . '/modules/reportes/dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Página no encontrada · Ware-Pro</title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($base) ?>/public/img/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --amarillo: #FFD700; --naranja: #FFA500; --negro: #0d0d0d; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at 20% 10%, #1f1f1f 0%, var(--negro) 60%); color: #fff; overflow: hidden; }
        .wrap { position: relative; text-align: center; max-width: 560px; width: 100%; }
        .num { font-size: clamp(6rem, 26vw, 11rem); font-weight: 800; line-height: .9; letter-spacing: -.04em; margin: 0;
            background: linear-gradient(135deg, var(--amarillo), var(--naranja)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .caja { display: inline-block; animation: caer 2.6s ease-in-out infinite; transform-origin: 50% 100%; }
        .caja:nth-child(2) { animation-delay: .25s; }
        .caja:nth-child(3) { animation-delay: .5s; }
        @keyframes caer { 0%, 100% { transform: translateY(0) rotate(0); } 45% { transform: translateY(-14px) rotate(-3deg); } 60% { transform: translateY(0) rotate(2deg); } }
        h1 { font-size: 1.5rem; margin: 18px 0 8px; }
        p { color: #b5b5b5; margin: 0 auto 28px; line-height: 1.6; max-width: 420px; }
        .acciones { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        a, button { font: inherit; font-weight: 600; text-decoration: none; padding: 12px 22px; border-radius: 12px; cursor: pointer; transition: transform .15s, background .15s; }
        .primario { background: var(--amarillo); color: #000; border: 0; }
        .primario:hover { transform: translateY(-2px); }
        .secundario { background: transparent; color: var(--amarillo); border: 2px solid rgba(255, 215, 0, .45); }
        .secundario:hover { background: rgba(255, 215, 0, .1); }
        .cinta { position: absolute; left: 50%; top: -60px; width: 140%; height: 34px; transform: translateX(-50%) rotate(-4deg);
            background: repeating-linear-gradient(45deg, var(--amarillo) 0 22px, #000 22px 44px); opacity: .12; pointer-events: none; }
        @media (prefers-reduced-motion: reduce) { .caja { animation: none; } }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="cinta" aria-hidden="true"></div>
        <p class="num" aria-label="404"><span class="caja">4</span><span class="caja">0</span><span class="caja">4</span></p>
        <h1>Esta ruta no está en el inventario</h1>
        <p>La página que buscas no existe, se movió o el enlace está mal escrito. Revisa la dirección o vuelve al inicio.</p>
        <div class="acciones">
            <a class="primario" href="<?= htmlspecialchars($inicio) ?>">Ir al inicio</a>
            <button class="secundario" type="button" onclick="history.length > 1 ? history.back() : location.href='<?= htmlspecialchars($inicio, ENT_QUOTES) ?>'">Volver atrás</button>
        </div>
    </main>
</body>
</html>
