<?php include '../../core/header.php'; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificador - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .page-header p {
            font-size: 1.1rem;
            color: #666;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #11998e;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-button:hover {
            background: #0e8478;
            transform: translateX(-5px);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .module-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .module-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .module-card:hover::before {
            transform: scaleX(1);
        }

        .module-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .card-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            transition: all 0.3s ease;
        }

        .module-card:hover .card-icon {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(17, 153, 142, 0.3);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
        }

        .card-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .card-features {
            list-style: none;
            margin-bottom: 25px;
        }

        .card-features li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: #555;
            font-size: 0.9rem;
        }

        .card-features i {
            color: #11998e;
            font-size: 0.8rem;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .card-action {
            color: #11998e;
            font-size: 0.95rem;
            font-weight: 600;
        }

        
        .pasaje-t1 .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .error-armado .card-icon {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }

        .control-devoluciones .card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .recargue-t2 .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .links-verificador .card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .page-header h2 {
                font-size: 2rem;
            }

            .module-card {
                padding: 25px;
            }
        }
    </style>
</head>

<body>
    <div class="page-container">
        <a href="../reportes/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Volver al Dashboard
        </a>

        <div class="page-header">
            <h2>Módulos de Verificación</h2>
            <p>Control de calidad y verificación de procesos operativos</p>
        </div>

        <div class="cards-grid ">
            <div class="module-card pasaje-t1" onclick="window.location.href='../pasaje.php'" style="cursor: pointer;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3 class="card-title">Pasaje de T1</h3>
                </div>
                <p class="card-description">Control y gestión del pasaje de productos en la línea T1. Verificación de flujo y calidad en tiempo real.</p>
                <ul class="card-features">
                    <li><i class="fas fa-check-circle"></i> Monitoreo en tiempo real</li>
                    <li><i class="fas fa-check-circle"></i> Control de calidad automático</li>
                    <li><i class="fas fa-check-circle"></i> Alertas de anomalías</li>
                </ul>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder →</span>
                </div>
            </div>


            <div class="module-card error-armado" onclick="window.location.href='../revision/error_armado.php'" style="cursor: pointer;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="card-title">Error de Armado</h3>
                </div>
                <p class="card-description">Detección y corrección de errores en procesos de armado. Sistema inteligente de identificación de fallas.</p>
                <ul class="card-features">
                    <li><i class="fas fa-check-circle"></i> Detección automática</li>
                    <li><i class="fas fa-check-circle"></i> Registro de incidencias</li>
                    <li><i class="fas fa-check-circle"></i> Análisis de causas</li>
                </ul>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder →</span>
                </div>
            </div>

            <div class="module-card control-devoluciones" onclick="window.location.href='../devoluciones/control_devoluciones.php'" style="cursor: pointer;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <h3 class="card-title">Control Devoluciones</h3>
                </div>
                <p class="card-description">Gestión completa de devoluciones de productos. Seguimiento y procesamiento de productos retornados.</p>
                <ul class="card-features">
                    <li><i class="fas fa-check-circle"></i> Seguimiento de devoluciones</li>
                    <li><i class="fas fa-check-circle"></i> Clasificación automática</li>
                    <li><i class="fas fa-check-circle"></i> Reportes detallados</li>
                </ul>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder →</span>
                </div>
            </div>

            <div class="module-card recargue-t2" onclick="window.location.href='../turnos/recargue_t2.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3 class="card-title">Recargue T2</h3>
                </div>
                <p class="card-description">Sistema de recargue automatizado para la línea T2. Control de inventario y reposición inteligente.</p>
                <ul class="card-features">
                    <li><i class="fas fa-check-circle"></i> Recargue automático</li>
                    <li><i class="fas fa-check-circle"></i> Control de stock</li>
                    <li><i class="fas fa-check-circle"></i> Optimización de tiempos</li>
                </ul>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder →</span>
                </div>
            </div>
            
            <div class="module-card recargue-t2" onclick="window.location.href='../auditoria/checklist_wip.php'">
    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <h3 class="card-title">Check-List del WIP</h3>
    </div>
    <p class="card-description">Verificación operativa del teléfono WIP. Control de estado, funcionamiento y reporte de novedades.</p>
    <ul class="card-features">
        <li><i class="fas fa-check-circle"></i> Revisión de funcionamiento</li>
        <li><i class="fas fa-check-circle"></i> Validación de conectividad</li>
        <li><i class="fas fa-check-circle"></i> Reporte de fallas</li>
    </ul>
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Acceder →</span>
    </div>
</div>


 <div class="module-card recargue-t2" onclick="window.location.href='../reportes/tablero.php'">
    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <h3 class="card-title">Tableros Digitales</h3>
    </div>
    <p class="card-description">Verificación operativa del teléfono WIP. Control de estado, funcionamiento y reporte de novedades.</p>
    <ul class="card-features">
        <li><i class="fas fa-check-circle"></i> Revisión de funcionamiento</li>
        <li><i class="fas fa-check-circle"></i> Validación de conectividad</li>
        <li><i class="fas fa-check-circle"></i> Reporte de fallas</li>
    </ul>
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Acceder →</span>
    </div>
</div>

<div class="module-card recargue-t2" onclick="window.location.href='../sorting/sorting_porteria.php'">
    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-warehouse"></i>
        </div>
        <h3 class="card-title">Sorting Portería</h3>
    </div>
    <p class="card-description">Ingreso y control de vehículos pendientes para checking y proceso de descargue.</p>
    <ul class="card-features">
        <li><i class="fas fa-check-circle"></i> Registro de ingreso de vehículos</li>
        <li><i class="fas fa-check-circle"></i> Validación del estado en portería</li>
        <li><i class="fas fa-check-circle"></i> Gestión para checking y descargue</li>
    </ul>
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Acceder →</span>
    </div>
</div>

<div class="module-card recargue-t2" onclick="window.location.href='../sorting/sortiing.php'">
    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <h3 class="card-title">Sorting</h3>
    </div>
    <p class="card-description">Conteo y verificación de las cajas del vehículo para el proceso de sorting.</p>
    <ul class="card-features">
        <li><i class="fas fa-check-circle"></i> Registro del número de cajas</li>
        <li><i class="fas fa-check-circle"></i> Validación del conteo realizado por el verificador</li>
        <li><i class="fas fa-check-circle"></i> Envío de información al proceso de sorting</li>
    </ul>
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Acceder →</span>
    </div>
</div>
           
        </div>
    </div>

    <script>
        function openModule(moduleName) {
            Swal.fire({
                title: moduleName,
                text: 'Módulo en desarrollo. Próximamente disponible.',
                icon: 'info',
                confirmButtonColor: '#11998e',
                confirmButtonText: 'Entendido'
            });
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.module-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
        });
    </script>
</body>

</html>