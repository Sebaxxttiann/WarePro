<?php
include '../../core/config.php';
include '../../core/header.php';

verificarLogin();

$cargoUsuario = $_SESSION['cargo'] ?? '';


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>People - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #ffffff; 
            min-height: 100vh; 
            color: #333;
        }
        
        .page-container { max-width: 1400px; margin: 0 auto; padding: 40px 20px; }
        
        
        .page-header { text-align: center; margin-bottom: 50px; }
        .page-header h2 { font-size: 2.8rem; color: #1a1a1a; margin-bottom: 12px; font-weight: 700; letter-spacing: -0.5px; }
        .page-header p { font-size: 1.15rem; color: #666; max-width: 600px; margin: 0 auto; }
        
        
        .back-button { 
            display: inline-flex; align-items: center; gap: 8px; 
            background: #ffffff; color: #11998e; 
            padding: 10px 20px; border-radius: 50px; 
            text-decoration: none; font-weight: 600; font-size: 0.95rem;
            border: 2px solid #11998e;
            transition: all 0.3s ease; 
            margin-bottom: 30px; 
        }
        .back-button:hover { 
            background: #11998e; color: white; 
            transform: translateX(-5px); 
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.2);
        }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 35px; }
        
        
        .module-card { 
            background: #ffffff; 
            border-radius: 24px; 
            padding: 40px 30px; 
            border: 1px solid #f0f0f0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
            cursor: pointer; 
            position: relative; 
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        
        .module-card:hover { 
            transform: translateY(-12px); 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
            border-color: transparent;
        }

        .card-header { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
        
        
        .card-icon { 
            width: 65px; height: 65px; 
            border-radius: 18px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 1.6rem; color: white; 
            transition: transform 0.3s ease;
        }
        .module-card:hover .card-icon { transform: scale(1.1) rotate(-5deg); }
        
        .card-title { font-size: 1.35rem; font-weight: 700; color: #2d3748; line-height: 1.2; }
        .card-description { color: #64748b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 30px; flex-grow: 1; }
        
        .card-footer { 
            display: flex; justify-content: space-between; align-items: center; 
            padding-top: 20px; border-top: 1px solid #f1f5f9; 
            margin-top: auto;
        }
        
        .status-badge { 
            padding: 6px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase;
        }
        .status-active { background: #ecfdf5; color: #059669; }
        
        .card-action { 
            color: #11998e; font-size: 0.95rem; font-weight: 600; 
            display: flex; align-items: center; gap: 5px;
            transition: gap 0.3s ease;
        }
        .module-card:hover .card-action { gap: 10px; color: #0e8478; } 

        
        .people-academy .card-icon { 
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
            box-shadow: 0 8px 20px rgba(0, 242, 254, 0.3);
        }
        .plan-padrino .card-icon { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            box-shadow: 0 8px 20px rgba(118, 75, 162, 0.3);
        }
        .dpo .card-icon { 
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); 
            box-shadow: 0 8px 20px rgba(238, 90, 36, 0.3);
        }
        
        .people-sistem .card-icon { 
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%); 
            box-shadow: 0 8px 20px rgba(253, 160, 133, 0.3);
        }
        .compensacion .card-icon { 
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); 
            box-shadow: 0 8px 20px rgba(56, 249, 215, 0.3);
        }
        
        
        .descanso .card-icon { 
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); 
            box-shadow: 0 8px 20px rgba(132, 250, 176, 0.3);
        }
        
        
        .module-card.is-admin {
            cursor: default; 
            overflow: hidden; 
        }

        
        .admin-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); 
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 15px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.4s ease;
            z-index: 10;
        }

        
        .module-card.is-admin:hover .admin-overlay {
            opacity: 1;
            visibility: visible;
        }

        
        .admin-btn {
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            width: 75%;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-acceder {
            background: #ffffff;
            color: #1e3c72;
        }

        .btn-visual {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border: 2px solid #ffffff;
        }

        .admin-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
    </style>
</head>

<body>
    <div class="page-container">
        <a href="../reportes/dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div class="page-header">
            <h2>People</h2>
            <p>Plataformas de aprendizaje, desarrollo y beneficios exclusivos para ti</p>
        </div>

        <div class="cards-grid">

            <div class="module-card people-academy" onclick="window.location.href='https://peopleacademy.logisticos.com.co/login_home.php'">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-graduation-cap"></i></div>
                    <h3 class="card-title">People Academy</h3>
                </div>
                <p class="card-description">Gana puntos aprendiendo, completa cursos y canjéalos por recompensas increíbles.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>

            <div class="module-card plan-padrino" onclick="window.location.href='https://planpadrino.logisticos.com.co/login.php'">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-user-friends"></i></div>
                    <h3 class="card-title">Plan Padrino</h3>
                </div>
                <p class="card-description">Consulta tu padrino asignado, sigue tu ruta de aprendizaje y alcanza tus metas.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>

            <div class="module-card dpo" onclick="window.location.href='https://quienquiereserdpocd.logisticos.com.co/'">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3 class="card-title">¿Quién quiere ser DPO?</h3>
                </div>
                <p class="card-description">Diviértete con este juego interactivo y aprende todo lo necesario sobre DPO.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>
            
            <div class="module-card people-sistem" onclick="window.location.href='https://peoplesistem.logisticos.com.co/'">
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-users"></i></div>
                    <h3 class="card-title">People Sistem</h3>
                </div>
                <p class="card-description">Encuentra nuestro buzón de sugerencias, solicitudes de servicios generales y Engagement.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>

            <div class="module-card compensacion" onclick="window.location.href='https://lookerstudio.google.com/u/0/reporting/15bc2cb0-9a1e-4c7b-88d1-859e97bccf95/page/p_lb8ptxzayd'" >
                <div class="card-header">
                    <div class="card-icon"><i class="fas fa-coins"></i></div>
                    <h3 class="card-title">Compensación Variable</h3>
                </div>
                <p class="card-description">Consulta fácilmente tu información y lleva el control de tus avances y compensaciones.</p>
                <div class="card-footer">
                    <span class="status-badge status-active">Activo</span>
                    <span class="card-action">Acceder <i class="fas fa-arrow-right"></i></span>
                </div>
            </div>
            
          <?php 
            
            
            $esAdmin = ($cargoUsuario === 'admin'); 
            ?>

            <?php if ($esAdmin): ?>
                <div class="module-card descanso is-admin">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-mug-hot"></i></div>
                        <h3 class="card-title">30 Min de Descanso</h3>
                    </div>
                    <p class="card-description">Programa, gestiona y disfruta de tu tiempo de pausa para recargar energías.</p>
                    <div class="card-footer">
                        <span class="status-badge status-active">Activo</span>
                        <span class="card-action">Opciones Admin <i class="fas fa-cogs"></i></span>
                    </div>

                    <div class="admin-overlay">
                        <a href="descanso.php" class="admin-btn btn-acceder">
                            <i class="fas fa-sign-in-alt"></i> Acceder
                        </a>
                        <a href="descanso_visual.php" class="admin-btn btn-visual">
                            <i class="fas fa-chart-line"></i> Ver visual
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="module-card descanso" onclick="window.location.href='descanso.php'">
                    <div class="card-header">
                        <div class="card-icon"><i class="fas fa-mug-hot"></i></div>
                        <h3 class="card-title">30 Min de Descanso</h3>
                    </div>
                    <p class="card-description">Programa, gestiona y disfruta de tu tiempo de pausa para recargar energías.</p>
                    <div class="card-footer">
                        <span class="status-badge status-active">Activo</span>
                        <span class="card-action">Acceder <i class="fas fa-arrow-right"></i></span>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>