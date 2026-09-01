<?php include '../../core/header.php'; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisores - Ware Pro</title>
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
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-button:hover {
            background: #5a6fd8;
            transform: translateX(-5px);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .module-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
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
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .module-card:hover::before {
            transform: scaleX(1);
        }

        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }

        .card-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
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
            color: #667eea;
            font-size: 0.9rem;
            font-weight: 500;
        }

        
        .turno-a .card-icon {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
        }

        .turno-b .card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .turno-c .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .insumos .card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .temperatura .card-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        .rotura .card-icon {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }

        .sider .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .error-verificacion .card-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .pi-reabastecimiento .card-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .cargar-informativo .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .ows-cargue .card-icon {
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
                padding: 20px;
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
            <h2>Módulos de Supervisión</h2>
            <p>Gestiona todos los aspectos de supervisión y control de calidad</p>
        </div>

        <div class="cards-grid">
            <a href="../turnos/turnoa.php" class="module-card turno-a" style="text-decoration: none; color: inherit;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-sun"></i>
                    </div>
                    <h3 class="card-title">Turno A</h3>
                </div>
                <p class="card-description">Control y supervisión del turno matutino. Gestión de personal y operaciones de 6:00 AM a 2:00 PM.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </a>


            <div class="module-card turno-b" onclick="navigateToTurnoB()" style="cursor: pointer;">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-cloud-sun"></i>
                    </div>
                    <h3 class="card-title">Turno B</h3>
                </div>
                <p class="card-description">Control y supervisión del turno vespertino. Gestión de personal y operaciones de 2:00 PM a 10:00 PM.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

            <script>
                function navigateToTurnoB() {
                    window.location.href = '../turnos/turnob.php';
                }
            </script>


            <div class="module-card turno-c" onclick="window.location.href='../turnos/turnoc.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-moon"></i>
                    </div>
                    <h3 class="card-title">Turno C</h3>
                </div>
                <p class="card-description">Control y supervisión del turno nocturno. Gestión de personal y operaciones de 10:00 PM a 6:00 AM.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

            <div class="module-card insumos" onclick="window.location.href='../insumos/insumos.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3 class="card-title">Insumos</h3>
                </div>
                <p class="card-description">Gestión y control de inventario de insumos. Seguimiento de stock y reposición automática.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

            <div class="module-card temperatura" onclick="window.location.href='../temperatura/temperatura.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-thermometer-half"></i>
                    </div>
                    <h3 class="card-title">Temperatura</h3>
                </div>
                <p class="card-description">Monitoreo continuo de temperatura en áreas críticas. Alertas automáticas y registro histórico.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>


            <div class="module-card rotura" onclick="window.location.href='../rotura.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 class="card-title">Rotura</h3>
                </div>
                <p class="card-description">Control de productos dañados y roturas. Registro de incidencias y análisis de causas.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

            <div class="module-card sider" onclick="window.location.href='../sider_certificado.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3 class="card-title">Sider Certificado</h3>
                </div>
                <p class="card-description">Sistema de certificación y validación de procesos. Control de calidad y cumplimiento normativo.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

            <div class="module-card error-verificacion"onclick="window.location.href='../revision/error_verificacion.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-bug"></i>
                    </div>
                    <h3 class="card-title">Error de Verificación</h3>
                </div>
                <p class="card-description">Detección y corrección de errores en procesos de verificación. Sistema de alertas y seguimiento.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>
            
            <div class="module-card error-verificacion"onclick="window.location.href='../ows/ows_cargue.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-bug"></i>
                    </div>
                    <h3 class="card-title">OWs Cargue</h3>
                </div>
                <p class="card-description">Detección y corrección de errores en procesos de verificación. Sistema de alertas y seguimiento.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>
             <div class="module-card error-verificacion"onclick="window.location.href='../reportes/tablero.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-bug"></i>
                    </div>
                    <h3 class="card-title">tableros digitales</h3>
                </div>
                <p class="card-description">Detección y corrección de errores en procesos de verificación. Sistema de alertas y seguimiento.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
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
                confirmButtonColor: '#667eea',
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
                }, index * 100);
            });
        });
    </script>
</body>

</html>