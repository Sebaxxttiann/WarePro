<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/dpo.php';

$op = (int)getOperacionActiva();
$esLider = dpoEsLider($pdo, $op, (int)$_SESSION['usuario_id']);
$esAdmin = dpoEsAdmin();

$hoyRegistros = 0;
if ($esLider) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dpo_registros WHERE operacion_id = ? AND lider_id = ? AND fecha = CURDATE()");
    $stmt->execute([$op, (int)$_SESSION['usuario_id']]);
    $hoyRegistros = (int)$stmt->fetchColumn();
}

require_once '../../core/header.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/css/entrenamientos.css">
<style>
    .en-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 18px; }
    .en-mod { background: #fff; border-radius: 18px; padding: 22px; box-shadow: 0 6px 22px rgba(0,0,0,.06); border-top: 4px solid #FFD700; text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: 10px; transition: transform .2s, box-shadow .2s; }
    a.en-mod:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(0,0,0,.12); }
    .en-mod .ico { width: 52px; height: 52px; border-radius: 14px; background: #111; color: #FFD700; display: grid; place-items: center; font-size: 1.35rem; }
    .en-mod h3 { margin: 0; font-size: 1.1rem; }
    .en-mod p { margin: 0; color: #666; font-size: .88rem; line-height: 1.5; flex: 1; }
    .en-mod .pie { font-size: .8rem; font-weight: 600; color: #b38f00; display: flex; align-items: center; gap: 6px; }
    .en-mod.bloq { opacity: .6; border-top-color: #ddd; }
    .en-mod.bloq .ico { background: #e5e7eb; color: #9ca3af; }
    .en-seccion { font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #888; margin: 26px 0 12px; }
</style>

<div class="en-wrap">
    <section class="en-hero">
        <div class="en-hero-top">
            <div>
                <h1><i class="fas fa-chalkboard-teacher"></i> Entrenamientos</h1>
                <p>Registro y seguimiento de los entrenamientos de la operación. Empezamos con DPO; aquí irán los demás programas de formación.</p>
            </div>
            <?php if ($esLider): ?>
                <span class="en-chip"><i class="fas fa-check-circle"></i> Hoy llevas <?= $hoyRegistros ?> registro<?= $hoyRegistros === 1 ? '' : 's' ?></span>
            <?php endif; ?>
        </div>
    </section>

    <div class="en-seccion">DPO · Entrenamiento en el puesto</div>
    <div class="en-grid">
        <?php if ($esLider): ?>
            <a class="en-mod" href="dpo_registro.php">
                <div class="ico"><i class="fas fa-camera"></i></div>
                <h3>Registrar entrenamiento</h3>
                <p>Busca a la persona que entrenaste y sube la foto o el video como evidencia.</p>
                <span class="pie"><i class="fas fa-arrow-right"></i> Registrar ahora</span>
            </a>
        <?php else: ?>
            <div class="en-mod bloq">
                <div class="ico"><i class="fas fa-lock"></i></div>
                <h3>Registrar entrenamiento</h3>
                <p>Aún no estás habilitado como líder DPO. <?= $esAdmin ? 'Agrégate desde el calendario en "Líderes".' : 'Pídele a tu supervisor que te habilite.' ?></p>
            </div>
        <?php endif; ?>

        <a class="en-mod" href="dpo_evidencias.php">
            <div class="ico"><i class="fas fa-images"></i></div>
            <h3><?= $esAdmin ? 'Evidencias' : 'Mis evidencias' ?></h3>
            <p><?= $esAdmin ? 'Todas las fotos y videos de la operación, con filtros por líder, persona y fecha.' : 'Las fotos y videos que has registrado.' ?></p>
            <span class="pie"><i class="fas fa-arrow-right"></i> Ver evidencias</span>
        </a>

        <?php if ($esAdmin): ?>
            <a class="en-mod" href="dpo_calendario.php">
                <div class="ico"><i class="fas fa-calendar-check"></i></div>
                <h3>Calendario de cumplimiento</h3>
                <p>Cumplimiento diario, semanal y del mes por líder. Elige quiénes son los líderes y gestiona días no laborables.</p>
                <span class="pie"><i class="fas fa-arrow-right"></i> Abrir calendario</span>
            </a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
