<?php include '../../core/header.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auxiliar - Ware Pro</title>
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
            background: #ff6b6b;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }

        .back-button:hover {
            background: #ee5a24;
            transform: translateX(-5px);
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        .module-card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
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
            height: 4px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
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
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa726 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            transition: all 0.3s ease;
        }

        .module-card:hover .card-icon {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }

        .card-title {
            font-size: 1.25rem;
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

        .status-maintenance {
            background: #f8d7da;
            color: #721c24;
        }

        .card-action {
            color: #ff6b6b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        
        .owd-clasificacion .card-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .lavado-reempaque .card-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .reempaque .card-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .owd-vertimiento .card-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .vertimiento .card-icon { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .owd-sorting .card-icon { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .sorting .card-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .temperatura .card-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .pi-desechados .card-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .tableros .card-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .informativo .card-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }

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
            <h2>Módulos Auxiliares</h2>
            <p>Operaciones de clasificación, procesamiento y gestión de materiales</p>
        </div>

        <div class="cards-grid">
            

            <div class="module-card lavado-reempaque" onclick="window.location.href='../reempaque.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="card-title">Maquila</h3>
                </div>
                <p class="card-description">Proceso integral de lavado y reempaque de productos. Control de higiene y calidad certificada.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

            <div class="module-card reempaque" onclick="window.location.href='../ows/ows_reempaque.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3 class="card-title"> OWS Maquila</h3>
                </div>
                <p class="card-description">Gestión especializada de reempaque de productos. Optimización de espacios y materiales.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

        

            <div class="module-card vertimiento" onclick="window.location.href='../vertimiento.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="card-title">Vertimiento</h3>
                </div>
                <p class="card-description">Gestión de vertimientos y residuos líquidos. Control ambiental y tratamiento especializado.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>
            
                <div class="module-card owd-vertimiento" onclick="window.location.href='../ows/ows_vertimiento.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tint"></i>
                    </div>
                    <h3 class="card-title">OWD Vertimiento</h3>
                </div>
                <p class="card-description">Control optimizado de procesos de vertimiento. Monitoreo ambiental y cumplimiento normativo.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>
            
            
            <div class="module-card vertimiento" onclick="window.location.href='../revision/revision.php'">
    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-shower"></i>
        </div>
        <h3 class="card-title">Revisión</h3>
    </div>
    <p class="card-description">Registro y seguimiento de inspecciones: hallazgos, no conformidades, acciones correctivas y estado de cumplimiento.</p>
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Ver detalles →</span>
    </div>
</div>

<div class="module-card vertimiento" onclick="window.location.href='../ows/ows_revision.php'">
    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-shower"></i>
        </div>
        <h3 class="card-title">OWS Revisión</h3>
    </div>
    <p class="card-description">Registro de revisiones (OWS): unidades inspeccionadas, cantidades reportadas y resultados por inspección.</p>
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Ver detalles →</span>
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
   
    <div class="card-footer">
        <span class="status-badge status-active">Activo</span>
        <span class="card-action">Acceder →</span>
    </div>
</div>
           
            <div class="module-card sorting" onclick="window.location.href='../sorting/sorting.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <h3 class="card-title">Sorting</h3>
                </div>
                <p class="card-description">Proceso de sorting manual y automático. Control de calidad y optimización de flujos.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Ver detalles →</span>
                </div>
            </div>

          
            
        
            
             <div class="module-card vertimiento" onclick="window.location.href='../picking/pi_reabastecimiento.php'">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <h3 class="card-title">PI reabastecimiento</h3>
                </div>
                <p class="card-description">Gestión de vertimientos y residuos líquidos. Control ambiental y tratamiento especializado.</p>
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
                confirmButtonColor: '#ff6b6b',
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
                }, index * 80);
            });
        });
    </script>
</body>
</html>
