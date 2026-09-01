<?php
include '../../core/config.php';


verificarLogin();
$cargoUsuario = $_SESSION['cargo'] ?? '';

$mes_actual = date('m');
$anio_actual = date('Y');
$mes_anio_str = $anio_actual . '-' . $mes_actual;


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_fecha']) && ($cargoUsuario === 'admin' || $cargoUsuario === 'lider')) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare("SELECT * FROM tiempos_atencion WHERE fecha = ? AND operacion_id = ?");
    $stmt->execute([$_GET['check_fecha'], getOperacionActiva()]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['existe' => $data !== false, 'datos' => $data]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_tiempos']) && ($cargoUsuario === 'admin' || $cargoUsuario === 'lider')) {
    header('Content-Type: application/json');
    try {
        $fecha_ingreso = $_POST['fecha_tiempos'];
        $t1 = (int)$_POST['t1'];
        $t2 = (int)$_POST['t2'];
        $t4 = (int)$_POST['t4'];
        
        
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM tiempos_atencion WHERE fecha = ? AND operacion_id = ?");
        $stmtCheck->execute([$fecha_ingreso, getOperacionActiva()]);
        $ya_existia = $stmtCheck->fetchColumn() > 0;


        $cumple_tiempos = ($t1 <= 60 && $t2 <= 50 && $t4 <= 45) ? 1 : 0;

        $stmt = $pdo->prepare("INSERT INTO tiempos_atencion (fecha, t1, t2, t4, cumple, operacion_id) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE t1 = ?, t2 = ?, t4 = ?, cumple = ?");
        $stmt->execute([$fecha_ingreso, $t1, $t2, $t4, $cumple_tiempos, getOperacionActiva(), $t1, $t2, $t4, $cumple_tiempos]);
        
        echo json_encode([
            'success' => true, 
            'mensaje' => $ya_existia ? 'Tiempos sobreescritos y actualizados correctamente.' : 'Tiempos nuevos registrados con éxito.',
            'cumple' => $cumple_tiempos
        ]);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => 'Error en base de datos: ' . $e->getMessage()]);
    }
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_tiempos']) && ($cargoUsuario === 'admin' || $cargoUsuario === 'lider')) {
    
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS config_opm_global (
        mes_anio VARCHAR(7) PRIMARY KEY,
        cumple_adherencia TINYINT(1) DEFAULT 1,
        penalidad_cargue DECIMAL(5,2) DEFAULT 0
    )");

    if (isset($_POST['cambiar_adherencia'])) {
        $nuevo_estado = (int)$_POST['estado_adherencia'];
        $stmt = $pdo->prepare("INSERT INTO config_opm_global (mes_anio, cumple_adherencia) VALUES (?, ?) ON DUPLICATE KEY UPDATE cumple_adherencia = ?");
        $stmt->execute([$mes_anio_str, $nuevo_estado, $nuevo_estado]);
        header("Location: c_opm.php?msg=adherencia_ok");
        exit();
    }
    
    if (isset($_POST['guardar_penalidad_cargue'])) {
        $penalidad = min(20, max(0, (float)$_POST['valor_penalidad']));
        $stmt = $pdo->prepare("INSERT INTO config_opm_global (mes_anio, penalidad_cargue) VALUES (?, ?) ON DUPLICATE KEY UPDATE penalidad_cargue = ?");
        $stmt->execute([$mes_anio_str, $penalidad, $penalidad]);
        header("Location: c_opm.php?msg=cargue_ok");
        exit();
    }
}


include '../../core/header.php';

$nombre_usuario = $_SESSION['nombre']; 


$dia_actual = (int)date('j'); 
$dias_habiles_total = 25;
$dias_transcurridos = min($dia_actual, $dias_habiles_total);

$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_texto = $meses[(int)$mes_actual - 1] . ' ' . $anio_actual;


$pdo->exec("CREATE TABLE IF NOT EXISTS config_opm_global (
    mes_anio VARCHAR(7) PRIMARY KEY,
    cumple_adherencia TINYINT(1) DEFAULT 1,
    penalidad_cargue DECIMAL(5,2) DEFAULT 0
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS tiempos_atencion (
    fecha DATE PRIMARY KEY,
    t1 INT DEFAULT 0,
    t2 INT DEFAULT 0,
    t4 INT DEFAULT 0,
    cumple TINYINT(1) DEFAULT 0
)");



$stmt = $pdo->prepare("SELECT cumple_adherencia, penalidad_cargue FROM config_opm_global WHERE mes_anio = ?");
$stmt->execute([$mes_anio_str]);
$config_opm = $stmt->fetch();
$cumple_adherencia = $config_opm ? (int)$config_opm['cumple_adherencia'] : 1;
$penalidad_cargue = $config_opm ? (float)$config_opm['penalidad_cargue'] : 0;


$stmt = $pdo->prepare("SELECT COUNT(*) as dias_cumplidos FROM tiempos_atencion WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND cumple = 1 AND operacion_id = ?");
$stmt->execute([$mes_actual, $anio_actual, getOperacionActiva()]);
$dias_tiempos_cumplidos = $stmt->fetch()['dias_cumplidos'];


$stmt = $pdo->prepare("SELECT * FROM tiempos_atencion WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND operacion_id = ? ORDER BY fecha DESC");
$stmt->execute([$mes_actual, $anio_actual, getOperacionActiva()]);
$historial_tiempos = $stmt->fetchAll();


$stmt = $pdo->prepare("SELECT COUNT(*) as errores FROM roturas WHERE persona_rotura = ? AND MONTH(fecha_registro) = ? AND YEAR(fecha_registro) = ? AND operacion_id = ?");
$stmt->execute([$nombre_usuario, $mes_actual, $anio_actual, getOperacionActiva()]);
$errores_rotura = $stmt->fetch()['errores'];


$stmt = $pdo->prepare("SELECT fecha_registro, descripcion_material, casual FROM roturas WHERE persona_rotura = ? AND MONTH(fecha_registro) = ? AND YEAR(fecha_registro) = ? AND operacion_id = ? ORDER BY fecha_registro DESC");
$stmt->execute([$nombre_usuario, $mes_actual, $anio_actual, getOperacionActiva()]);
$lista_roturas = $stmt->fetchAll();


$base_fija_asegurada = 166162; 
$variable_juego = 166162; 

$diario_tiempos = ($variable_juego * 0.30) / $dias_habiles_total;   
$diario_adherencia = ($variable_juego * 0.20) / $dias_habiles_total; 
$diario_rotura = ($variable_juego * 0.30) / $dias_habiles_total;     

$porcentaje_cargue_real = 20 - $penalidad_cargue; 
$bolsa_cargue_real = $variable_juego * ($porcentaje_cargue_real / 100);
$diario_cargue = $bolsa_cargue_real / $dias_habiles_total;

$ganado_tiempos = $dias_tiempos_cumplidos * $diario_tiempos;
$ganado_adherencia = ($cumple_adherencia == 1) ? ($diario_adherencia * $dias_transcurridos) : 0;
$ganado_rotura = ($errores_rotura == 0) ? ($diario_rotura * $dias_transcurridos) : 0;
$ganado_cargue = $diario_cargue * $dias_transcurridos;

$dinero_acumulado_variable = $ganado_tiempos + $ganado_adherencia + $ganado_rotura + $ganado_cargue;
$total_cheque = $base_fija_asegurada + $dinero_acumulado_variable;

$maximo_variable_hoy = ($variable_juego / $dias_habiles_total) * $dias_transcurridos;
$porcentaje_salud_variable = ($maximo_variable_hoy > 0) ? ($dinero_acumulado_variable / $maximo_variable_hoy) * 100 : 100;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensación OPM - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; min-height: 100vh; }
        .dashboard-container { max-width: 1250px; margin: 0 auto; padding: 40px 20px; }
        
        .welcome-section { margin-bottom: 25px; }
        .welcome-section h2 { font-size: 2.2rem; color: #1a1a1a; margin-bottom: 5px; font-weight: 700; }
        .welcome-section p { font-size: 1.1rem; color: #666; }

        .info-bar { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .info-pill { background: white; padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .pill-yellow { border-left: 4px solid #FFD700; }
        .pill-green { border-left: 4px solid #27ae60; background: rgba(39, 174, 96, 0.05); color: #27ae60; }

        .admin-panel { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #FFD700; }
        .admin-panel h3 { font-size: 1.2rem; margin-bottom: 20px; color: #1a1a1a; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .admin-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        
        .admin-box { background: #f8f9fa; padding: 15px; border-radius: 12px; }
        .admin-box h4 { font-size: 0.95rem; margin-bottom: 15px; color: #333; }
        
        .btn-toggle { border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 0.9rem; }
        .btn-cumple { background: rgba(39, 174, 96, 0.1); color: #27ae60; border: 1px solid #27ae60; }
        .btn-cumple:hover { background: #27ae60; color: white; }
        .btn-nocumple { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid #e74c3c; }
        .btn-nocumple:hover { background: #e74c3c; color: white; }
        
        .form-inline { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .form-inline input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: 'Poppins'; outline: none; width: 80px; }
        .form-inline input[type="date"] { width: 140px; }
        .btn-save { background: #FFD700; color: #1a1a1a; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-save:hover { background: #e5c100; }

        .cards-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 30px; margin-bottom: 30px; }
        @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr; } }

        .premium-card { background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%); color: white; border-radius: 20px; padding: 35px 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); position: relative; overflow: hidden; border: 2px solid transparent; }
        .premium-card:hover { border-color: #FFD700; box-shadow: 0 20px 40px rgba(255, 215, 0, 0.15); transform: translateY(-5px); transition: all 0.4s; }
        .premium-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
        
        .monto-gigante { font-size: 2.8rem; font-weight: 700; color: #FFD700; margin: 15px 0; line-height: 1.1; }
        .monto-gigante small { font-size: 1.2rem; color: #fff; }
        .progreso-texto { display: flex; justify-content: space-between; font-size: 0.9rem; color: #aaa; margin-bottom: 8px; }
        .barra-progreso { height: 6px; background: rgba(255,255,255,0.1); border-radius: 10px; overflow: hidden; }
        .progreso-fill { height: 100%; background: linear-gradient(90deg, #FFD700, #FFA500); border-radius: 10px; width: <?php echo $porcentaje_salud_variable; ?>%; }

        .variables-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        @media (max-width: 600px) { .variables-grid { grid-template-columns: 1fr; } }
        
        .stat-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); border-top: 4px solid #ddd; display: flex; flex-direction: column; justify-content: space-between; }
        .stat-card.success { border-top-color: #27ae60; }
        .stat-card.danger { border-top-color: #e74c3c; opacity: 0.9; }
        .stat-card.warning { border-top-color: #f39c12; }
        
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .stat-header h4 { font-size: 0.9rem; color: #1a1a1a; margin-bottom: 5px; }
        .stat-header i { font-size: 1.5rem; color: #ddd; }
        .success .stat-header i { color: #27ae60; }
        .danger .stat-header i { color: #e74c3c; }
        .warning .stat-header i { color: #f39c12; }

        .stat-amount { font-size: 1.4rem; font-weight: 700; color: #1a1a1a; }
        .stat-desc { font-size: 0.8rem; color: #666; margin-top: 5px; }

        .history-section { background: white; border-radius: 20px; padding: 25px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); }
        .history-section h3 { font-size: 1.2rem; color: #1a1a1a; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 12px; text-align: left; font-size: 0.85rem; color: #666; font-weight: 600; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; color: #1a1a1a; }
        tr:hover { background: #fdfdfd; }
        .badge { padding: 4px 8px; border-radius: 5px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .badge-danger { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="welcome-section">
        <h2>Compensación OPM (Montacargas)</h2>
        <p>Resumen al día de hoy en <strong><?php echo $mes_texto; ?></strong></p>
    </div>

    
    <div class="info-bar">
        <div class="info-pill pill-green">
            <i class="fas fa-lock"></i> Base Fija (50%) Asegurada: $<?php echo number_format($base_fija_asegurada, 0, ',', '.'); ?>
        </div>
        <div class="info-pill pill-yellow">
            <i class="fas fa-calendar-day"></i> Días transcurridos: <?php echo $dias_transcurridos; ?> de <?php echo $dias_habiles_total; ?>
        </div>
    </div>

    
    <?php if ($cargoUsuario === 'admin' || $cargoUsuario === 'lider'): ?>
    <div class="admin-panel">
        <h3><i class="fas fa-user-shield" style="color: #FFD700; margin-right: 8px;"></i> Panel de Control Global (Aplica a todos los OPM)</h3>
        <div class="admin-grid">
            
            
            <div class="admin-box">
                <h4>1. Adherencia Layout / 5S (20%)</h4>
                <form method="POST">
                    <input type="hidden" name="cambiar_adherencia" value="1">
                    <?php if ($cumple_adherencia == 1): ?>
                        <button type="submit" name="estado_adherencia" value="0" class="btn-toggle btn-nocumple">
                            <i class="fas fa-times-circle"></i> Marcar Incumplimiento
                        </button>
                    <?php else: ?>
                        <button type="submit" name="estado_adherencia" value="1" class="btn-toggle btn-cumple">
                            <i class="fas fa-check-circle"></i> Marcar Cumplimiento
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            
            <div class="admin-box">
                <h4>2. Penalidad Cargue antes de 6 (Bolsa 20%)</h4>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="guardar_penalidad_cargue" value="1">
                    <input type="number" step="0.01" name="valor_penalidad" value="<?php echo $penalidad_cargue; ?>" min="0" max="20" required title="Cuánto quitar de la bolsa (Max 20%)">
                    <span style="font-weight:600; color:#666;">% Perdido</span>
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i></button>
                </form>
                <div style="margin-top: 5px; font-size: 0.8rem; color: #888;">Variable restante del cargue: <strong><?php echo 20 - $penalidad_cargue; ?>%</strong></div>
            </div>

            
            <div class="admin-box" style="grid-column: 1 / -1;">
                <h4>3. Registro Diario de Tiempos de Atención (Metas: T1=60, T2=50, T4=45)</h4>
                <form id="formTiempos" class="form-inline">
                    <input type="hidden" name="ajax_tiempos" value="1">
                    
                    <input type="date" id="fecha_tiempos" name="fecha_tiempos" max="<?php echo date('Y-m-d'); ?>" required onchange="comprobarFecha(this.value)">
                    <input type="number" id="t1" name="t1" placeholder="T1 (Min)" required>
                    <input type="number" id="t2" name="t2" placeholder="T2 (Min)" required>
                    <input type="number" id="t4" name="t4" placeholder="T4 (Min)" required>
                    <button type="submit" class="btn-save"><i class="fas fa-plus"></i> Guardar Día</button>
                </form>
                <div id="alerta-fecha" style="display:none; margin-top: 10px; font-size: 0.85rem; color: #e3a008; font-weight:600;">
                    <i class="fas fa-exclamation-triangle"></i> Esta fecha ya tiene tiempos registrados. Si guardas, los vas a sobreescribir.
                </div>
            </div>

        </div>
    </div>
    <?php endif; ?>

    
    <div class="cards-grid">
        <div class="dashboard-card premium-card">
            <h3>PAGO TOTAL PROYECTADO HOY</h3>
            <div class="monto-gigante">$<?php echo number_format($total_cheque, 0, ',', '.'); ?> <small>COP</small></div>
            
            <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 15px; margin-top: 15px;">
                <p style="color: #ccc; font-size: 0.9rem; margin-bottom: 5px;">De los cuales en la Variable Activa has ganado:</p>
                <h4 style="color: #FFD700; font-size: 1.4rem;">+$<?php echo number_format($dinero_acumulado_variable, 0, ',', '.'); ?></h4>
                
                <div class="progreso-texto" style="margin-top: 15px;">
                    <span>Salud de Variable (Max Hoy: $<?php echo number_format($maximo_variable_hoy, 0, ',', '.'); ?>)</span>
                    <span><?php echo round($porcentaje_salud_variable); ?>%</span>
                </div>
                <div class="barra-progreso">
                    <div class="progreso-fill"></div>
                </div>
            </div>
        </div>

        <div class="variables-grid">
            <div class="stat-card <?php echo ($dias_tiempos_cumplidos > 0) ? 'success' : 'danger'; ?>">
                <div class="stat-header">
                    <div>
                        <h4>Tiempos (30%)</h4>
                        <span class="badge <?php echo ($dias_tiempos_cumplidos > 0) ? 'badge-success' : 'badge-danger'; ?>"><?php echo $dias_tiempos_cumplidos; ?> días cumplidos</span>
                    </div>
                    <i class="fas fa-stopwatch"></i>
                </div>
                <div>
                    <div class="stat-amount">$<?php echo number_format($ganado_tiempos, 0, ',', '.'); ?></div>
                    <div class="stat-desc">Diario: $<?php echo number_format($diario_tiempos, 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="stat-card <?php echo ($cumple_adherencia == 1) ? 'success' : 'danger'; ?>">
                <div class="stat-header">
                    <div>
                        <h4>Adherencia Layout (20%)</h4>
                        <span class="badge <?php echo ($cumple_adherencia == 1) ? 'badge-success' : 'badge-danger'; ?>"><?php echo ($cumple_adherencia == 1) ? 'Cumpliendo' : 'Perdido'; ?></span>
                    </div>
                    <i class="fas fa-broom"></i>
                </div>
                <div>
                    <div class="stat-amount <?php echo ($cumple_adherencia == 0) ? 'text-danger' : ''; ?>">$<?php echo number_format($ganado_adherencia, 0, ',', '.'); ?></div>
                    <div class="stat-desc">Bolsa General</div>
                </div>
            </div>

            <div class="stat-card <?php echo ($errores_rotura == 0) ? 'success' : 'danger'; ?>">
                <div class="stat-header">
                    <div>
                        <h4>Roturas (30%)</h4>
                        <span class="badge <?php echo ($errores_rotura == 0) ? 'badge-success' : 'badge-danger'; ?>"><?php echo $errores_rotura; ?> Errores</span>
                    </div>
                    <i class="fas fa-wine-bottle"></i>
                </div>
                <div>
                    <div class="stat-amount <?php echo ($errores_rotura > 0) ? 'text-danger' : ''; ?>">$<?php echo number_format($ganado_rotura, 0, ',', '.'); ?></div>
                    <div class="stat-desc">Búsqueda Automática</div>
                </div>
            </div>

            <div class="stat-card <?php echo ($penalidad_cargue == 0) ? 'success' : ($penalidad_cargue < 20 ? 'warning' : 'danger'); ?>">
                <div class="stat-header">
                    <div>
                        <h4>Cargue Antes 6AM (<?php echo 20 - $penalidad_cargue; ?>%)</h4>
                        <span class="badge <?php echo ($penalidad_cargue == 0) ? 'badge-success' : 'badge-danger'; ?>">-<?php echo $penalidad_cargue; ?>% Penalidad</span>
                    </div>
                    <i class="fas fa-truck-loading"></i>
                </div>
                <div>
                    <div class="stat-amount">$<?php echo number_format($ganado_cargue, 0, ',', '.'); ?></div>
                    <div class="stat-desc">Diario Ajustado: $<?php echo number_format($diario_cargue, 0, ',', '.'); ?></div>
                </div>
            </div>
        </div>
    </div>

    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">
        <div class="history-section">
            <h3><i class="fas fa-clock" style="color: #FFD700;"></i> Historial Tiempos Atención</h3>
            <?php if (empty($historial_tiempos)): ?>
                <p style="color: #888; text-align: center; padding: 20px;">Aún no se han registrado tiempos este mes.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Fecha</th><th>T1</th><th>T2</th><th>T4</th><th>Estado</th></tr></thead>
                    <tbody>
                        <?php foreach($historial_tiempos as $tiempo): ?>
                        <tr>
                            <td><strong><?php echo date('d/m', strtotime($tiempo['fecha'])); ?></strong></td>
                            <td <?php echo ($tiempo['t1'] > 60) ? 'style="color:#e74c3c;font-weight:bold;"' : ''; ?>><?php echo $tiempo['t1']; ?>m</td>
                            <td <?php echo ($tiempo['t2'] > 50) ? 'style="color:#e74c3c;font-weight:bold;"' : ''; ?>><?php echo $tiempo['t2']; ?>m</td>
                            <td <?php echo ($tiempo['t4'] > 45) ? 'style="color:#e74c3c;font-weight:bold;"' : ''; ?>><?php echo $tiempo['t4']; ?>m</td>
                            <td>
                                <?php if($tiempo['cumple'] == 1): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Cumple</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> Falló</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="history-section">
            <h3><i class="fas fa-wine-bottle" style="color: #e74c3c;"></i> Mis Roturas Reportadas</h3>
            <?php if (empty($lista_roturas)): ?>
                <p style="color: #27ae60; text-align: center; padding: 20px; font-weight: 600;"><i class="fas fa-award" style="font-size:2rem;display:block;margin-bottom:10px;"></i> ¡0 Roturas! Bolsa intacta.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Fecha</th><th>Material</th><th>Causal</th></tr></thead>
                    <tbody>
                        <?php foreach($lista_roturas as $rot): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($rot['fecha_registro'])); ?></td>
                            <td><?php echo htmlspecialchars($rot['descripcion_material']); ?></td>
                            <td style="color:#e74c3c;"><?php echo htmlspecialchars($rot['casual']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
    
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('msg') === 'adherencia_ok') {
        Swal.fire({ icon: 'success', title: 'Adherencia Actualizada', confirmButtonColor: '#FFD700', confirmButtonText: 'Genial' });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if(urlParams.get('msg') === 'cargue_ok') {
        Swal.fire({ icon: 'success', title: 'Penalidad de Cargue Guardada', confirmButtonColor: '#FFD700', confirmButtonText: 'Listo' });
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    
    function comprobarFecha(fechaStr) {
        if(!fechaStr) return;
        
        fetch('c_opm.php?check_fecha=' + fechaStr)
        .then(response => response.json())
        .then(data => {
            const alerta = document.getElementById('alerta-fecha');
            if(data.existe) {
                
                alerta.style.display = 'block';
                document.getElementById('t1').value = data.datos.t1;
                document.getElementById('t2').value = data.datos.t2;
                document.getElementById('t4').value = data.datos.t4;
                
                
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
                });
                Toast.fire({ icon: 'warning', title: 'Fecha con registros previos cargados.' });
            } else {
                alerta.style.display = 'none';
                document.getElementById('t1').value = '';
                document.getElementById('t2').value = '';
                document.getElementById('t4').value = '';
            }
        })
        .catch(error => console.error('Error verificando fecha:', error));
    }

    
    document.getElementById('formTiempos').addEventListener('submit', function(e) {
        e.preventDefault(); 
        
        const formData = new FormData(this);
        
        Swal.fire({
            title: 'Guardando...',
            text: 'Procesando los tiempos de atención',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        fetch('c_opm.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: data.mensaje,
                    confirmButtonColor: '#FFD700',
                    confirmButtonText: 'Aceptar'
                }).then((result) => {
                    
                    
                    if (result.isConfirmed) {
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire('Error', data.mensaje, 'error');
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Hubo un problema de conexión al guardar.', 'error');
        });
    });
</script>

</body>
</html>