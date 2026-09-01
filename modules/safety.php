<?php include '../core/header.php'; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operadores - Ware Pro</title>
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
            max-width: 1200px;
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
            background: #4facfe;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-button:hover {
            background: #00f2fe;
            transform: translateX(-5px);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 30px;
        }

        .module-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .module-card:hover::before {
            transform: scaleX(1);
        }

        .module-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            transition: all 0.3s ease;
        }

        .module-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 15px 30px rgba(79, 172, 254, 0.3);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .card-description {
            color: #666;
            font-size: 1.05rem;
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
            gap: 12px;
            margin-bottom: 10px;
            color: #555;
            font-size: 0.95rem;
        }

        .card-features i {
            color: #4facfe;
            font-size: 0.9rem;
        }

        .card-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #4facfe;
            display: block;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
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
            color: #4facfe;
            font-size: 1rem;
            font-weight: 600;
        }

        
        .rotacion-botella .card-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .rotacion-lata .card-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .links-montacargas .card-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        @media (max-width: 768px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }

            .page-header h2 {
                font-size: 2rem;
            }

            .module-card {
                padding: 30px;
            }
        }
    </style>
</head>

<body>
<div class="page-container">
    <a href="reportes/dashboard.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Volver al Dashboard
    </a>

    <div class="page-header">
        <h2>Módulos de Seguridad</h2>
        <p>Control de personal y operaciones especializadas</p>
    </div>

    <div class="cards-grid">
        <div class="module-card rotacion-botella" onclick="window.location.href='auditoria/check_herramientas.php'">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-tools"></i>
                </div>
                <h3 class="card-title">Check de Herramientas</h3>
            </div>
            <p class="card-description">Sistema automatizado de verificación de herramientas. Control de disponibilidad, estado y calidad en el proceso de inspección y gestión.</p>
            <ul class="card-features">
                <li><i class="fas fa-check-circle"></i> Verificación de disponibilidad</li>
                <li><i class="fas fa-check-circle"></i> Control del estado de uso</li>
                <li><i class="fas fa-check-circle"></i> Detección de desgastes o fallas</li>
                <li><i class="fas fa-check-circle"></i> Registro de inspecciones</li>
            </ul>
            
            <div class="card-footer">
                <span class="status-badge status-active">Activo</span>
                <span class="card-action">Acceder →</span>
            </div>
        </div>

        <div class="module-card pausa-activa" onclick="window.location.href='https://escueladeergonomia.logisticos.com.co/pausas_activas.php'">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-running"></i>
                </div>
                <h3 class="card-title">Pausa Activa</h3>
            </div>
            <p class="card-description">Programa de ejercicios y pausas para prevenir lesiones laborales. Promueve el bienestar físico y mental durante la jornada laboral.</p>
            <ul class="card-features">
                <li><i class="fas fa-check-circle"></i> Ejercicios de estiramiento</li>
                <li><i class="fas fa-check-circle"></i> Prevención de lesiones</li>
                <li><i class="fas fa-check-circle"></i> Mejora de postura corporal</li>
                <li><i class="fas fa-check-circle"></i> Seguimiento de participación</li>
            </ul>
            
            <div class="card-footer">
                <span class="status-badge status-active">Activo</span>
                <span class="card-action">Acceder →</span>
            </div>
        </div>

        <div class="module-card traslado-seguro" onclick="window.location.href='https://trasladoseguro.logisticos.com.co/'">
            <div class="card-header">
                <div class="card-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="card-title">Traslado Seguro</h3>
            </div>
            <p class="card-description">Sistema de control para movimiento seguro de materiales y personal. Garantiza el cumplimiento de protocolos de seguridad en traslados internos.</p>
            <ul class="card-features">
                <li><i class="fas fa-check-circle"></i> Protocolos de seguridad</li>
                <li><i class="fas fa-check-circle"></i> Verificación de rutas seguras</li>
                <li><i class="fas fa-check-circle"></i> Control de equipos de protección</li>
                <li><i class="fas fa-check-circle"></i> Registro de traslados</li>
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
                confirmButtonColor: '#4facfe',
                confirmButtonText: 'Entendido'
            });
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.module-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(40px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.8s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>

</html>