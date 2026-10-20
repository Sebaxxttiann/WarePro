<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/metas.php';
require_once '../../core/compensacion.php';

$puede = in_array($_SESSION['cargo'] ?? '', ROLES_GESTION_COMPENSACION, true);
$operacion_id = (int)getOperacionActiva();

// ---------- Guardado (JSON) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$puede) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No tienes permisos para configurar la compensación.']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $cargo = $data['cargo'] ?? '';
    if (!isset(COMPENSACION_DEFAULTS[$cargo]) || $operacion_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cargo u operación inválidos.']);
        exit;
    }
    $variable = $data['monto_variable'] ?? null;
    $base = $cargo === 'operador' ? ($data['monto_base'] ?? 0) : 0;
    $dias = $data['dias_habiles'] ?? null;
    if (!is_numeric($variable) || $variable < 0 || $variable > 100000000 || !is_numeric($base) || $base < 0 || $base > 100000000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Los montos deben ser números positivos.']);
        exit;
    }
    if (!is_numeric($dias) || (int)$dias < 1 || (int)$dias > 31) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Los días hábiles deben estar entre 1 y 31.']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO compensacion_config (operacion_id, cargo, monto_variable, monto_base, dias_habiles, actualizado_por)
                               VALUES (?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE monto_variable = VALUES(monto_variable), monto_base = VALUES(monto_base),
                                   dias_habiles = VALUES(dias_habiles), actualizado_por = VALUES(actualizado_por)");
        $stmt->execute([$operacion_id, $cargo, round((float)$variable, 2), round((float)$base, 2), (int)$dias, (int)$_SESSION['usuario_id']]);
        echo json_encode(['success' => true, 'message' => 'Compensación actualizada.']);
    } catch (Throwable $e) {
        error_log('[compensacion_config] ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar.']);
    }
    exit;
}

if (!$puede) http_response_code(403);
require_once '../../core/header.php';

$operacion_nombre = 'Operación';
$config = [];
if ($puede && $operacion_id > 0) {
    $stmt = $pdo->prepare("SELECT nombre FROM operaciones WHERE id = ?");
    $stmt->execute([$operacion_id]);
    $operacion_nombre = $stmt->fetchColumn() ?: 'Operación';
    asegurarCompensacionBase($pdo, $operacion_id);
    $stmt = $pdo->prepare("SELECT c.*, u.nombre AS actualizado_nombre FROM compensacion_config c
                           LEFT JOIN usuarios u ON u.id = c.actualizado_por WHERE c.operacion_id = ?");
    $stmt->execute([$operacion_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) $config[$r['cargo']] = $r;
}

$tarjetas = [
    'auxiliar'    => ['icon' => 'fa-user-check',   'link' => '/modules/personal/c_auxiliar.php',    'desc' => 'Suma un día cuando cumple todas sus productividades e indicadores de turno.'],
    'verificador' => ['icon' => 'fa-user-shield',  'link' => '/modules/personal/c_verificador.php', 'desc' => 'Bolsa variable mensual repartida por días hábiles.'],
    'operador'    => ['icon' => 'fa-truck-loading', 'link' => '/modules/personal/c_opm.php',        'desc' => 'Base fija asegurada más una parte variable según tiempos T1/T2/T4.'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar compensación - WARE PRO</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Poppins', 'sans-serif'] },
            colors: { brand: { 400: '#FFD700', 500: '#FFA500' }, dark: '#1a1a1a' }
        } } };
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        .money { position: relative; }
        .money span { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-weight: 600; }
        .money input { padding-left: 1.8rem; }
        .comp-card { transition: transform .2s, box-shadow .2s; }
        .comp-card:hover { transform: translateY(-3px); box-shadow: 0 18px 38px -16px rgba(0,0,0,.25); }
    </style>
</head>
<body class="text-gray-800 antialiased min-h-screen pb-20">
<div class="max-w-6xl mx-auto px-4 py-6 md:px-8 md:py-8">
<?php if (!$puede): ?>
    <div class="bg-white rounded-2xl shadow-sm p-10 text-center max-w-lg mx-auto mt-10">
        <div class="w-20 h-20 rounded-full bg-red-50 text-red-500 grid place-items-center text-3xl mx-auto mb-4"><i class="fas fa-lock"></i></div>
        <h1 class="text-2xl font-bold mb-2">Acceso restringido</h1>
        <p class="text-gray-500 mb-6">Solo los administradores pueden configurar los montos de compensación variable.</p>
        <a href="<?= BASE_URL ?>/modules/reportes/dashboard.php" class="inline-block bg-dark text-brand-400 font-bold px-5 py-2 rounded-lg">Volver al inicio</a>
    </div>
<?php else: ?>
    <div class="bg-white p-5 md:p-6 rounded-2xl shadow-sm mb-6 border-l-[5px] border-brand-400 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-1 flex items-center gap-3"><i class="fas fa-sliders-h text-brand-400"></i> Compensación variable</h1>
            <p class="text-gray-500 text-sm">Define cuánto gana cada cargo en esta operación. El valor diario es lo que suma cada día cumplido.</p>
        </div>
        <div class="bg-dark text-white rounded-xl px-4 py-3 flex items-center gap-3">
            <i class="fas fa-warehouse text-brand-400 text-xl"></i>
            <div>
                <div class="text-[10px] uppercase tracking-widest text-gray-400">Operación</div>
                <div class="font-bold"><?= htmlspecialchars($operacion_nombre) ?></div>
                <?php if (($_SESSION['cargo'] ?? '') === 'super_admin'): ?>
                    <div class="text-[10px] text-gray-400">Cámbiala desde el selector del menú</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <?php foreach ($tarjetas as $cargo => $t):
        $c = $config[$cargo] ?? ['monto_variable' => COMPENSACION_DEFAULTS[$cargo][1], 'monto_base' => COMPENSACION_DEFAULTS[$cargo][2], 'dias_habiles' => COMPENSACION_DEFAULTS[$cargo][3]];
        $etiqueta = COMPENSACION_DEFAULTS[$cargo][0];
    ?>
        <form class="comp-card bg-white rounded-2xl shadow-sm overflow-hidden flex flex-col" data-cargo="<?= $cargo ?>">
            <div class="bg-gradient-to-br from-[#1a1a1a] to-black text-white p-5 relative">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-400 to-brand-500"></div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-brand-400 to-brand-500 text-dark grid place-items-center text-xl"><i class="fas <?= $t['icon'] ?>"></i></div>
                    <div>
                        <div class="font-bold text-lg leading-tight"><?= htmlspecialchars($etiqueta) ?></div>
                        <div class="text-xs text-gray-400"><?= htmlspecialchars($t['desc']) ?></div>
                    </div>
                </div>
                <div class="mt-5">
                    <div class="text-[10px] uppercase tracking-widest text-gray-400">Valor por día cumplido</div>
                    <div class="text-3xl font-extrabold text-brand-400" data-out="diario">$0</div>
                    <?php if ($cargo === 'operador'): ?>
                        <div class="text-xs text-gray-400 mt-1">Total mensual posible: <span class="text-white font-semibold" data-out="total">$0</span></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="p-5 space-y-4 flex-1">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Monto variable mensual</label>
                    <div class="money"><span>$</span><input type="number" min="0" step="1" name="monto_variable" value="<?= (float)$c['monto_variable'] ?>" class="w-full py-2 pr-3 border-2 border-gray-200 rounded-lg font-semibold outline-none focus:border-brand-400" required></div>
                </div>
                <?php if ($cargo === 'operador'): ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Base fija asegurada</label>
                    <div class="money"><span>$</span><input type="number" min="0" step="1" name="monto_base" value="<?= (float)$c['monto_base'] ?>" class="w-full py-2 pr-3 border-2 border-gray-200 rounded-lg font-semibold outline-none focus:border-brand-400" required></div>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Días hábiles del mes</label>
                    <input type="number" min="1" max="31" step="1" name="dias_habiles" value="<?= (int)$c['dias_habiles'] ?>" class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg font-semibold outline-none focus:border-brand-400" required>
                </div>
                <?php if (!empty($c['actualizado_nombre'])): ?>
                    <p class="text-[11px] text-gray-400"><i class="fas fa-history mr-1"></i>Última modificación: <?= htmlspecialchars($c['actualizado_nombre']) ?>, <?= date('d/m/Y H:i', strtotime($c['fecha_actualizacion'])) ?></p>
                <?php endif; ?>
            </div>
            <div class="px-5 pb-5 flex flex-col sm:flex-row gap-2">
                <button type="submit" class="flex-1 bg-dark text-brand-400 font-bold py-2.5 rounded-lg hover:opacity-90"><i class="fas fa-save mr-1"></i> Guardar</button>
                <a href="<?= BASE_URL . $t['link'] ?>" class="flex-1 text-center border-2 border-gray-200 font-semibold py-2 rounded-lg hover:border-brand-400 text-gray-700"><i class="fas fa-eye mr-1"></i> Ver compensación</a>
            </div>
        </form>
    <?php endforeach; ?>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow-sm p-5 text-sm text-gray-600 flex gap-3">
        <i class="fas fa-info-circle text-brand-500 text-lg mt-0.5"></i>
        <div>
            <p><b>¿Cómo se calcula?</b> Valor diario = monto variable ÷ días hábiles. A un auxiliar se le suma ese valor por cada día en que todas sus productividades
            y todos los indicadores de turno donde fue responsable cumplieron la meta; si alguno no cumple o cae en disparador, ese día se tacha.</p>
            <p class="mt-1">Las metas se configuran en <a class="underline font-semibold" href="<?= BASE_URL ?>/modules/reportes/metas.php">Metas</a> y los resultados diarios en
            <a class="underline font-semibold" href="<?= BASE_URL ?>/modules/reportes/indicadores_turno.php">Indicadores por turno</a>.</p>
        </div>
    </div>

<script>
(function () {
    const fmt = n => '$' + Math.round(n).toLocaleString('es-CO');
    document.querySelectorAll('form[data-cargo]').forEach(form => {
        const calc = () => {
            const v = Number(form.monto_variable.value) || 0, d = Math.max(1, Number(form.dias_habiles.value) || 1);
            form.querySelector('[data-out="diario"]').textContent = fmt(v / d);
            const t = form.querySelector('[data-out="total"]');
            if (t) t.textContent = fmt(v + (Number(form.monto_base ? form.monto_base.value : 0) || 0));
        };
        form.addEventListener('input', calc); calc();
        form.addEventListener('submit', async e => {
            e.preventDefault();
            const body = { cargo: form.dataset.cargo, monto_variable: form.monto_variable.value, dias_habiles: form.dias_habiles.value,
                           monto_base: form.monto_base ? form.monto_base.value : 0 };
            const btn = form.querySelector('button[type=submit]'); btn.disabled = true;
            try {
                const r = await fetch(location.pathname, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
                const j = await r.json();
                if (!j.success) throw new Error(j.message || 'Error');
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: j.message, showConfirmButton: false, timer: 2200 });
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: err.message });
            } finally { btn.disabled = false; }
        });
    });
})();
</script>
<?php endif; ?>
</div>
</body>
</html>
