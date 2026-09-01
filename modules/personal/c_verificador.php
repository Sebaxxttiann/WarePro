<?php
include '../../core/config.php';
include '../../core/header.php';

verificarLogin();

$nombre_usuario = $_SESSION['nombre']; 
$cargoUsuario = $_SESSION['cargo'] ?? '';
$mes_actual = date('m');
$anio_actual = date('Y');
$mes_anio_str = $anio_actual . '-' . $mes_actual;


$dia_actual = (int)date('j'); 
$dias_habiles_total = 25;
$dias_transcurridos = min($dia_actual, $dias_habiles_total);

$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_texto = $meses[(int)$mes_actual - 1] . ' ' . $anio_actual;


$total_compensacion = 252000;
$diario_checkin = ($total_compensacion * 0.30) / $dias_habiles_total;  
$diario_despacho = ($total_compensacion * 0.40) / $dias_habiles_total; 
$diario_bloqueo = ($total_compensacion * 0.30) / $dias_habiles_total;  


$pdo->exec("CREATE TABLE IF NOT EXISTS config_bloqueo (
    mes_anio VARCHAR(7) PRIMARY KEY,
    cumple TINYINT(1) DEFAULT 1
)");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_bloqueo']) && ($cargoUsuario === 'admin' || $cargoUsuario === 'lider')) {
    $nuevo_estado = (int)$_POST['estado_bloqueo'];
    $stmt = $pdo->prepare("INSERT INTO config_bloqueo (mes_anio, cumple, operacion_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE cumple = ?");
    $stmt->execute([$mes_anio_str, $nuevo_estado, getOperacionActiva(), $nuevo_estado]);
    header("Location: c_verificador.php");
    exit();
}

$stmt = $pdo->prepare("SELECT cumple FROM config_bloqueo WHERE mes_anio = ? AND operacion_id = ?");
$stmt->execute([$mes_anio_str, getOperacionActiva()]);
$resultado_bloqueo = $stmt->fetch();
$cumple_bloqueo = $resultado_bloqueo ? (int)$resultado_bloqueo['cumple'] : 1;



$stmt = $pdo->prepare("
    SELECT COUNT(*) as errores FROM error_verificacion
    WHERE verificador_responsable = ?
    AND MONTH(marca_temporal) = ? AND YEAR(marca_temporal) = ?
    AND LOWER(tipo_novedad) LIKE '%checkin%'
    AND operacion_id = ?
");
$stmt->execute([$nombre_usuario, $mes_actual, $anio_actual, getOperacionActiva()]);
$errores_checkin = $stmt->fetch()['errores'];


$stmt = $pdo->prepare("
    SELECT COUNT(*) as errores FROM error_verificacion
    WHERE verificador_responsable = ?
    AND MONTH(marca_temporal) = ? AND YEAR(marca_temporal) = ?
    AND LOWER(tipo_novedad) NOT LIKE '%checkin%'
    AND operacion_id = ?
");
$stmt->execute([$nombre_usuario, $mes_actual, $anio_actual, getOperacionActiva()]);
$errores_despacho = $stmt->fetch()['errores'];


$stmt = $pdo->prepare("
    SELECT * FROM error_verificacion
    WHERE verificador_responsable = ?
    AND MONTH(marca_temporal) = ? AND YEAR(marca_temporal) = ?
    AND operacion_id = ?
    ORDER BY marca_temporal DESC
");
$stmt->execute([$nombre_usuario, $mes_actual, $anio_actual, getOperacionActiva()]);
$lista_errores = $stmt->fetchAll();


$acumulado_checkin = ($errores_checkin == 0) ? ($diario_checkin * $dias_transcurridos) : 0;
$acumulado_despacho = ($errores_despacho == 0) ? ($diario_despacho * $dias_transcurridos) : 0;
$acumulado_bloqueo = ($cumple_bloqueo == 1) ? ($diario_bloqueo * $dias_transcurridos) : 0;

$dinero_acumulado = $acumulado_checkin + $acumulado_despacho + $acumulado_bloqueo;


$tasa_diaria_actual = 0;
if ($errores_checkin == 0) $tasa_diaria_actual += $diario_checkin;
if ($errores_despacho == 0) $tasa_diaria_actual += $diario_despacho;
if ($cumple_bloqueo == 1) $tasa_diaria_actual += $diario_bloqueo;


$maximo_posible_hoy = ($total_compensacion / $dias_habiles_total) * $dias_transcurridos;
$porcentaje_salud = ($maximo_posible_hoy > 0) ? ($dinero_acumulado / $maximo_posible_hoy) * 100 : 100;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensación Variable - Verificador</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; min-height: 100vh; }
        .dashboard-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        
        .welcome-section { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .welcome-section h2 { font-size: 2.2rem; color: #1a1a1a; margin-bottom: 5px; font-weight: 700; }
        .welcome-section p { font-size: 1.1rem; color: #666; }

        .admin-controls { background: white; padding: 15px 25px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid #FFD700; text-align: right; }
        .admin-controls h4 { font-size: 0.9rem; color: #666; margin-bottom: 10px; text-transform: uppercase; }
        .btn-toggle { border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-family: 'Poppins'; cursor: pointer; transition: all 0.3s; font-size: 0.95rem; }
        .btn-cumple { background: rgba(39, 174, 96, 0.1); color: #27ae60; border: 1px solid #27ae60; }
        .btn-cumple:hover { background: #27ae60; color: white; }
        .btn-nocumple { background: rgba(231, 76, 60, 0.1); color: #e74c3c; border: 1px solid #e74c3c; }
        .btn-nocumple:hover { background: #e74c3c; color: white; }

        .info-bar { display: flex; gap: 20px; margin-bottom: 25px; }
        .info-pill { background: white; padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 3px solid #FFD700; }

        .cards-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 30px; }
        @media (max-width: 900px) { .cards-grid { grid-template-columns: 1fr; } .welcome-section { flex-direction: column; align-items: flex-start; gap: 20px; } .info-bar { flex-direction: column; } }

        .premium-card { background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%); color: white; border-radius: 20px; padding: 40px 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); position: relative; overflow: hidden; border: 2px solid transparent; transition: all 0.4s ease; }
        .premium-card:hover { border-color: #FFD700; box-shadow: 0 20px 40px rgba(255, 215, 0, 0.15); transform: translateY(-5px); }
        .premium-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); }
        
        .card-content { display: flex; align-items: center; gap: 25px; }
        .card-icon { width: 80px; height: 80px; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #1a1a1a; flex-shrink: 0; }
        .card-data h3 { font-size: 1rem; color: #ccc; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .monto-gigante { font-size: 3rem; font-weight: 700; color: #FFD700; line-height: 1; margin-bottom: 15px; }
        .monto-gigante small { font-size: 1.2rem; color: #fff; }
        
        .progreso-texto { display: flex; justify-content: space-between; font-size: 0.9rem; color: #aaa; margin-bottom: 8px; }
        .barra-progreso { height: 6px; background: rgba(255,255,255,0.1); border-radius: 10px; overflow: hidden; }
        .progreso-fill { height: 100%; background: linear-gradient(90deg, #FFD700, #FFA500); border-radius: 10px; width: <?php echo $porcentaje_salud; ?>%; transition: width 1s ease; }

        .variables-container { display: grid; grid-template-columns: 1fr; gap: 15px; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05); transition: transform 0.3s ease; border-left: 5px solid #ddd; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card.success { border-left-color: #27ae60; }
        .stat-card.danger { border-left-color: #e74c3c; opacity: 0.85; }

        .stat-info h4 { font-size: 0.95rem; color: #1a1a1a; margin-bottom: 5px; }
        .stat-info p { font-size: 0.8rem; color: #666; margin-bottom: 0; }
        
        .stat-amount { text-align: right; }
        .stat-amount .monto { font-size: 1.4rem; font-weight: 700; display: block; }
        .stat-amount .estado { font-size: 0.8rem; font-weight: 600; padding: 3px 8px; border-radius: 5px; display: inline-block; margin-top: 5px; }
        
        .success .monto { color: #27ae60; }
        .success .estado { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .danger .monto { color: #e74c3c; text-decoration: line-through; }
        .danger .estado { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }

        .history-section { background: white; border-radius: 20px; padding: 30px; margin-top: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05); }
        .history-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .history-header h3 { font-size: 1.3rem; color: #1a1a1a; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-size: 0.9rem; color: #666; font-weight: 600; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #1a1a1a; vertical-align: middle; font-size: 0.9rem; }
        tr:hover { background: #fdfdfd; }
        
        .badge { padding: 5px 10px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; display: inline-block; }
        .badge-warning { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .badge-info { background: rgba(52, 152, 219, 0.1); color: #3498db; }

        .empty-state { text-align: center; padding: 40px 20px; }
        .empty-state i { font-size: 3rem; color: #27ae60; margin-bottom: 15px; }
        .empty-state p { color: #1a1a1a; font-size: 1.1rem; font-weight: 600; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="welcome-section">
        <div>
            <h2>Compensación Verificadores</h2>
            <p>Resumen al día de hoy en <strong><?php echo $mes_texto; ?></strong></p>
        </div>
        
        <?php if ($cargoUsuario === 'admin' || $cargoUsuario === 'lider'): ?>
        <div class="admin-controls">
            <h4><i class="fas fa-cog"></i> Control Política de Bloqueo</h4>
            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="cambiar_bloqueo" value="1">
                <?php if ($cumple_bloqueo == 1): ?>
                    <span style="font-size: 0.9rem; color: #666;">Estado: <strong style="color:#27ae60;">Cumpliendo</strong></span>
                    <button type="submit" name="estado_bloqueo" value="0" class="btn-toggle btn-nocumple">
                        <i class="fas fa-times-circle"></i> Marcar Incumplimiento
                    </button>
                <?php else: ?>
                    <span style="font-size: 0.9rem; color: #666;">Estado: <strong style="color:#e74c3c;">No Cumple</strong></span>
                    <button type="submit" name="estado_bloqueo" value="1" class="btn-toggle btn-cumple">
                        <i class="fas fa-check-circle"></i> Marcar Cumplimiento
                    </button>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <div class="info-bar">
        <div class="info-pill">
            <i class="fas fa-calendar-day" style="color: #FFD700; margin-right: 5px;"></i> 
            Días transcurridos: <?php echo $dias_transcurridos; ?> de <?php echo $dias_habiles_total; ?>
        </div>
        <div class="info-pill" style="<?php echo ($tasa_diaria_actual < ($total_compensacion/$dias_habiles_total)) ? 'border-color: #e74c3c; color: #e74c3c;' : ''; ?>">
            <i class="fas fa-chart-line" style="margin-right: 5px;"></i>
            Ritmo diario actual: $<?php echo number_format($tasa_diaria_actual, 0, ',', '.'); ?> / día
        </div>
    </div>

    <div class="cards-grid">
        <div class="dashboard-card premium-card">
            <div class="card-content">
                <div class="card-icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="card-data">
                    <h3>Acumulado al día de hoy</h3>
                    <div class="monto-gigante">$<?php echo number_format($dinero_acumulado, 0, ',', '.'); ?> <small>COP</small></div>
                    <div class="progreso-texto">
                        <span>Max posible hoy: $<?php echo number_format($maximo_posible_hoy, 0, ',', '.'); ?></span>
                        <span><?php echo round($porcentaje_salud); ?>% Salud Variable</span>
                    </div>
                    <div class="barra-progreso">
                        <div class="progreso-fill"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="variables-container">
            <div class="stat-card <?php echo ($errores_checkin == 0) ? 'success' : 'danger'; ?>">
                <div class="stat-info">
                    <h4>Error de Checkin (30%)</h4>
                    <p><i class="fas fa-clipboard-check"></i> <?php echo $errores_checkin; ?> errores. $<?php echo number_format($diario_checkin, 0, ',', '.'); ?> diarios.</p>
                </div>
                <div class="stat-amount">
                    <span class="monto">$<?php echo number_format(($diario_checkin * $dias_transcurridos), 0, ',', '.'); ?></span>
                    <span class="estado"><?php echo ($errores_checkin == 0) ? 'Sumando' : 'Bolsa Perdida'; ?></span>
                </div>
            </div>

            <div class="stat-card <?php echo ($errores_despacho == 0) ? 'success' : 'danger'; ?>">
                <div class="stat-info">
                    <h4>Error de Despacho (40%)</h4>
                    <p><i class="fas fa-truck-loading"></i> <?php echo $errores_despacho; ?> errores. $<?php echo number_format($diario_despacho, 0, ',', '.'); ?> diarios.</p>
                </div>
                <div class="stat-amount">
                    <span class="monto">$<?php echo number_format(($diario_despacho * $dias_transcurridos), 0, ',', '.'); ?></span>
                    <span class="estado"><?php echo ($errores_despacho == 0) ? 'Sumando' : 'Bolsa Perdida'; ?></span>
                </div>
            </div>

            <div class="stat-card <?php echo ($cumple_bloqueo == 1) ? 'success' : 'danger'; ?>">
                <div class="stat-info">
                    <h4>Política de Bloqueo (30%)</h4>
                    <p><i class="fas fa-shield-alt"></i> Medición Global. $<?php echo number_format($diario_bloqueo, 0, ',', '.'); ?> diarios.</p>
                </div>
                <div class="stat-amount">
                    <span class="monto">$<?php echo number_format(($diario_bloqueo * $dias_transcurridos), 0, ',', '.'); ?></span>
                    <span class="estado"><?php echo ($cumple_bloqueo == 1) ? 'Sumando' : 'Bolsa Perdida'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="history-section">
        <div class="history-header">
            <h3><i class="fas fa-exclamation-triangle" style="color: #FFD700; margin-right: 10px;"></i> Mis Errores del Mes</h3>
        </div>
        
        <?php if (empty($lista_errores)): ?>
            <div class="empty-state">
                <i class="fas fa-award"></i>
                <p>¡Excelente trabajo! No tienes ningún error reportado este mes.</p>
                <span style="color:#666; font-size:0.9rem;">Tus 3 bolsas de variable están sumando al 100%.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo de Novedad</th>
                            <th>Descripción</th>
                            <th>Placa</th>
                            <th>Turno</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_errores as $error): 
                            $es_checkin = (strpos(strtolower($error['tipo_novedad']), 'checkin') !== false);
                        ?>
                            <tr>
                                <td><strong><?php echo date('d/m/Y', strtotime($error['marca_temporal'])); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $es_checkin ? 'badge-warning' : 'badge-info'; ?>">
                                        <?php echo htmlspecialchars($error['tipo_novedad']); ?>
                                    </span>
                                </td>
                                <td style="max-width: 300px;"><?php echo htmlspecialchars($error['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($error['placa_completa']); ?></td>
                                <td style="text-transform: capitalize;"><?php echo htmlspecialchars($error['turno']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>