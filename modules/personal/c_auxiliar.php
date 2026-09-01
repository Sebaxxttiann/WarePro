<?php
include '../../core/config.php';
include '../../core/header.php';

verificarLogin();

$usuario_id = $_SESSION['usuario_id'];
$mes_actual = date('m');
$anio_actual = date('Y');


$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_texto = $meses[(int)$mes_actual - 1] . ' ' . $anio_actual;


$bono_mensual_total = 111000;
$dias_habiles = 25;
$bono_diario = $bono_mensual_total / $dias_habiles; 


$stmt = $pdo->prepare("
    SELECT 
        fecha,
        MIN(CAST(cumple_meta AS UNSIGNED)) as dia_cumplido,
        COUNT(*) as total_actividades
    FROM (
        SELECT fecha, cumple_meta, auxiliar_id FROM reempaque1
        UNION ALL
        SELECT fecha, cumple_meta, auxiliar_id FROM revision
        UNION ALL
        SELECT fecha, cumple_meta, auxiliar_id FROM vertimiento
        UNION ALL
        SELECT fecha, 1 as cumple_meta, usuario_id as auxiliar_id FROM ows_reempaque
        UNION ALL
        SELECT fecha, 1 as cumple_meta, usuario_id as auxiliar_id FROM ows_vertimiento
    ) as registros_mes
    WHERE auxiliar_id = ? 
    AND MONTH(fecha) = ? 
    AND YEAR(fecha) = ?
    GROUP BY fecha
    ORDER BY fecha DESC
");

$stmt->execute([$usuario_id, $mes_actual, $anio_actual]);
$registros_diarios = $stmt->fetchAll();

$dias_logrados = 0;
$dias_fallados = 0;
$dinero_acumulado = 0;

foreach ($registros_diarios as $dia) {
    if ($dia['dia_cumplido'] == 1) {
        $dias_logrados++;
        $dinero_acumulado += $bono_diario;
    } else {
        $dias_fallados++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compensación Variable - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .welcome-section {
            margin-bottom: 40px;
        }

        .welcome-section h2 {
            font-size: 2.2rem;
            color: #1a1a1a;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .welcome-section p {
            font-size: 1.1rem;
            color: #666;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }

        @media (max-width: 900px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }

        
        .dashboard-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .premium-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            color: white;
            border: 2px solid transparent;
            transition: all 0.4s ease;
        }

        .premium-card:hover {
            border-color: #FFD700;
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.15);
            transform: translateY(-5px);
        }

        .premium-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        }

        .card-content {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #1a1a1a;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
            flex-shrink: 0;
        }

        .card-data h3 {
            font-size: 1rem;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .monto-gigante {
            font-size: 3rem;
            font-weight: 700;
            color: #FFD700;
            line-height: 1;
            margin-bottom: 15px;
        }

        .monto-gigante small {
            font-size: 1.2rem;
            color: #fff;
        }

        .progreso-texto {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #aaa;
            margin-bottom: 8px;
        }

        .barra-progreso {
            height: 6px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .progreso-fill {
            height: 100%;
            background: linear-gradient(90deg, #FFD700, #FFA500);
            border-radius: 10px;
            width: <?php echo min(100, ($dinero_acumulado / $bono_mensual_total) * 100); ?>%;
        }

        
        .stats-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon-small {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .success-stat .stat-icon-small {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
        }

        .danger-stat .stat-icon-small {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .stat-info .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-info .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a1a1a;
            line-height: 1.2;
            display: block;
        }

        .success-stat .stat-desc { color: #27ae60; font-size: 0.85rem; font-weight: 600;}
        .danger-stat .stat-desc { color: #888; font-size: 0.85rem; }

        
        .history-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .history-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .history-header h3 {
            font-size: 1.3rem;
            color: #1a1a1a;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #1a1a1a;
            vertical-align: middle;
        }

        tr:hover {
            background: #fdfdfd;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .badge-danger { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        .badge-neutral { background: #f1f3f5; color: #495057; }

        .monto-ganado {
            font-weight: 700;
            color: #cca300;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #666;
            font-size: 1rem;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <div class="welcome-section">
        <h2>Compensación Variable</h2>
        <p>Resumen de productividad de <strong><?php echo $mes_texto; ?></strong></p>
    </div>

    <div class="cards-grid">
        <div class="dashboard-card premium-card">
            <div class="card-content">
                <div class="card-icon">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="card-data">
                    <h3>Acumulado Actual</h3>
                    <div class="monto-gigante">$<?php echo number_format($dinero_acumulado, 0, ',', '.'); ?> <small>COP</small></div>
                    <div class="progreso-texto">
                        <span>Meta mensual: $111.000</span>
                        <span><?php echo round(($dinero_acumulado / $bono_mensual_total) * 100); ?>%</span>
                    </div>
                    <div class="barra-progreso">
                        <div class="progreso-fill"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card success-stat">
                <div class="stat-icon-small"><i class="fas fa-check"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Días Cumplidos</span>
                    <span class="stat-value"><?php echo $dias_logrados; ?></span>
                    <span class="stat-desc">+$<?php echo number_format($dias_logrados * $bono_diario, 0, ',', '.'); ?> ganados</span>
                </div>
            </div>

            <div class="stat-card danger-stat">
                <div class="stat-icon-small"><i class="fas fa-times"></i></div>
                <div class="stat-info">
                    <span class="stat-label">Días Fallados</span>
                    <span class="stat-value"><?php echo $dias_fallados; ?></span>
                    <span class="stat-desc">No generaron bono</span>
                </div>
            </div>
        </div>
    </div>

    <div class="history-section">
        <div class="history-header">
            <h3><i class="fas fa-calendar-alt" style="color: #FFD700; margin-right: 10px;"></i> Historial Diario de Registros</h3>
        </div>
        
        <?php if (empty($registros_diarios)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Aún no hay productividades registradas para calcular tu compensación este mes.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Volumen</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Bono Diario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros_diarios as $dia): ?>
                            <tr>
                                <td>
                                    <strong><?php echo date('d/m/Y', strtotime($dia['fecha'])); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-neutral">
                                        <i class="fas fa-list-ul"></i> <?php echo $dia['total_actividades']; ?> registro(s)
                                    </span>
                                </td>
                                <td>
                                    <?php if ($dia['dia_cumplido'] == 1): ?>
                                        <span class="badge badge-success"><i class="fas fa-check-circle"></i> Cumplió</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-times-circle"></i> No Cumplió</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($dia['dia_cumplido'] == 1): ?>
                                        <span class="monto-ganado">+$<?php echo number_format($bono_diario, 0, ',', '.'); ?></span>
                                    <?php else: ?>
                                        <span style="color: #aaa;">$0</span>
                                    <?php endif; ?>
                                </td>
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