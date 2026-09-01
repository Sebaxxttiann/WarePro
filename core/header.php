<?php
require_once 'config.php';
verificarLogin();
$user_cargo = $_SESSION['cargo'] ?? 'operador';

$notificaciones_pendientes = 0;
$notificaciones = [];

if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("
        SELECT 
            grupo_registro,
            fecha,
            actividad,
            cumplimiento_general,
            DATEDIFF(CURDATE(), fecha) as dias_transcurridos
        FROM reempaque1
        WHERE auxiliar_id = ?
        AND operacion_id = ?
        AND cumple_meta = 0
        AND estado_ciclo = 'pendiente'
        AND fecha < CURDATE()
        GROUP BY grupo_registro
        ORDER BY fecha DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id'], getOperacionActiva()]);
    $notificaciones = $stmt->fetchAll();
    $notificaciones_pendientes = count($notificaciones);
}

$operacionesDisponibles = [];
if ($user_cargo === 'super_admin') {
    $operacionesDisponibles = $pdo->query("SELECT id, nombre FROM operaciones WHERE activo = 1 ORDER BY nombre ASC")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/public/img/logo.png">
    
    <link rel="manifest" href="<?php echo BASE_URL; ?>/public/manifest.json">
    <meta name="theme-color" content="#FFD700">
    
    <meta name="application-name" content="Ware-Pro">
    <meta name="apple-mobile-web-app-title" content="Ware-Pro">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <meta name="description" content="Ware-Pro - Sistema de Gestión de Almacén">
    <meta name="author" content="Ware-Pro">
    
    <title>Ware-Pro - Sistema de Gestión</title>
</head>
<body>

<header class="main-header">
    <div class="header-content">
        <div class="logo-section">
            <div class="logo-container">
                <a href="<?php echo BASE_URL; ?>/modules/reportes/dashboard.php" class="">
                <img src="<?php echo BASE_URL; ?>/public/img/logo_blancoo.png" alt="Ware Pro Logo" class="main-logo" id="mainLogo">
                </a>
                <div class="logo-fallback" id="logoFallback">
                    <div class="fallback-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div class="fallback-text">
                        <span class="brand-name">WARE PRO</span>
                        <span class="brand-subtitle">Sistema de Gestión</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="user-section">



            <div class="notifications-section">
                <button class="btn-notifications" onclick="toggleNotifications()" <?php echo $notificaciones_pendientes > 0 ? 'data-has-notifications="true"' : ''; ?>>
                    <i class="fas fa-bell"></i>
                    <?php if ($notificaciones_pendientes > 0): ?>
                        <span class="notification-badge"><?php echo $notificaciones_pendientes; ?></span>
                    <?php endif; ?>
                </button>
                
                <div class="notifications-dropdown" id="notificationsDropdown">
                    <div class="notifications-header">
                        <h3>Notificaciones</h3>
                        <?php if ($notificaciones_pendientes > 0): ?>
                            <span class="notifications-count"><?php echo $notificaciones_pendientes; ?> pendientes</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notifications-content">
                        <?php if (empty($notificaciones)): ?>
                            <div class="notification-empty">
                                <i class="fas fa-check-circle"></i>
                                <p>No tienes notificaciones pendientes</p>
                                <span>Todos tus registros están completos</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notificaciones as $notif): ?>
                                <div class="notification-item">
                                    <div class="notification-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-title">Registro Pendiente - <?php echo $notif['actividad']; ?></div>
                                        <div class="notification-message">
                                            Tu productividad del <?php echo date('d/m/Y', strtotime($notif['fecha'])); ?> quedó pendiente. 
                                            Cumplimiento: <?php echo number_format($notif['cumplimiento_general'], 2); ?> unidades/hora
                                        </div>
                                        <div class="notification-time">
                                            Hace <?php echo $notif['dias_transcurridos']; ?> día<?php echo $notif['dias_transcurridos'] > 1 ? 's' : ''; ?>
                                        </div>
                                        <div class="notification-action">
                                            <button class="btn-complete-cycle" onclick="goToProductivity()">
                                                Completar Ciclo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="notifications-footer">
                                <button class="btn-view-all" onclick="goToProductivity()">
                                    Ver todas las productividades
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($user_cargo === 'super_admin'): ?>
            <select id="selectorOperacion" class="btn-notifications" style="cursor:pointer;" onchange="cambiarOperacionActiva(this.value)">
                <?php foreach ($operacionesDisponibles as $op): ?>
                    <option value="<?php echo $op['id']; ?>" <?php echo (int)$op['id'] === (int)getOperacionActiva() ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($op['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo $_SESSION['nombre']; ?></span>
                    <span class="user-role"><?php echo ucfirst($_SESSION['cargo']); ?></span>
                </div>
            </div>
            
            <div class="user-actions">
                <button class="btn-menu" onclick="toggleUserMenu()">
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="user-menu" id="userMenu">
                    <div class="menu-header">
                        <div class="menu-user-info">
                            <div class="menu-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="menu-user-name"><?php echo $_SESSION['nombre']; ?></div>
                                <div class="menu-user-role"><?php echo ucfirst($_SESSION['cargo']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="menu-divider"></div>
                    <a href="<?php echo BASE_URL; ?>/modules/personal/perfil.php" class="menu-item">
                        <i class="fas fa-user-edit"></i>
                        <span>Mi Perfil</span>
                    </a>

                     <a href="<?php echo BASE_URL; ?>/modules/insumos/consulta_sku.php" class="menu-item">
                        <i class="fas fa-search"></i>
                        <span>Consulta el SKU</span>
                    </a>


                    <?php if ($user_cargo === 'admin' || $user_cargo === 'super_admin'): ?>
                        <div class="menu-divider"></div>

                        <div class="menu-item-container">
                            <a href="#" class="menu-item" onclick="toggleSubmenu(event, 'metasSubmenu')">
                                <i class="fas fa-bullseye"></i>
                                <span>Almacen</span>
                                <i class="fas fa-chevron-down submenu-icon" style="margin-left: auto;"></i>
                            </a>
                            <div class="submenu" id="metasSubmenu">
                                <a href="<?php echo BASE_URL; ?>/modules/reportes/metas.php" class="menu-item sub-item">
                                    <i class="fas fa-bullseye"></i>
                                    <span>metas</span>
                                </a>
                                 <a href="<?php echo BASE_URL; ?>/modules/reportes/tablero_admin.php" class="menu-item sub-item">
                                    <i class="fas fa-bullseye"></i>
                                    <span>Tableros Digitales</span>
                                </a>




                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="menu-divider"></div>
                    <a href="#" onclick="logout()" class="menu-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<style>

.submenu {
    display: none;
    background: rgba(0, 0, 0, 0.02);
    border-left: 2px solid #FFD700;
    margin: 0 0 5px 25px;
    border-radius: 0 0 10px 0;
    overflow: hidden;
}

.submenu.active {
    display: block;
    animation: slideDownSubmenu 0.3s ease-out;
}

.menu-item.sub-item {
    padding: 12px 20px;
    font-size: 0.9rem;
}

.menu-item.sub-item:hover {
    padding-left: 28px;
    background: rgba(255, 215, 0, 0.05);
}

.submenu-icon {
    transition: transform 0.3s ease;
    font-size: 0.9rem !important;
}

.submenu-icon.active {
    transform: rotate(180deg);
}

@keyframes slideDownSubmenu {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}


.main-header {
    background: #000000;
    color: white;
    padding: 0;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
        background-color: #000000;
    border-bottom: 3px solid #FFD700;
    backdrop-filter: blur(10px);
}

.header-content {
    display: flex;
    justify-content: space-between;
    background-color: #000000;
    align-items: center;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    height: 75px;
}

.logo-section {
    display: flex;
    align-items: center;
    min-width: 200px;
}

.logo-container {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.logo-container:hover {
    transform: scale(1.02);
}

.main-logo {
    height: 55px;
    width: auto;
    max-width: 280px;
    object-fit: contain;
    transition: all 0.3s ease;
}

.logo-fallback {
    display: none;
    align-items: center;
    gap: 15px;
}

.fallback-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.fallback-icon i {
    font-size: 1.5rem;
    color: #1a1a1a;
}

.fallback-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-size: 1.6rem;
    font-weight: 800;
    margin: 0;
    color: #FFD700;
    letter-spacing: 1px;
    line-height: 1;
}

.brand-subtitle {
    font-size: 0.75rem;
    color: #ccc;
    font-weight: 400;
    margin-top: 2px;
}

.user-section {
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    min-width: 320px;
    justify-content: flex-end;
}


.notifications-section {
    position: relative;
}

.btn-notifications {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.3);
    color: #ccc;
    padding: 12px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.btn-notifications:hover {
    background: rgba(255, 215, 0, 0.2);
    border-color: rgba(255, 215, 0, 0.5);
    color: #FFD700;
    transform: scale(1.05);
}

.btn-notifications[data-has-notifications="true"] {
    color: #FFD700;
    border-color: rgba(255, 215, 0, 0.5);
    animation: bellShake 2s ease-in-out infinite;
}

@keyframes bellShake {
    0%, 50%, 100% { transform: rotate(0deg); }
    10% { transform: rotate(10deg); }
    20% { transform: rotate(-8deg); }
    30% { transform: rotate(6deg); }
    40% { transform: rotate(-4deg); }
}

.notification-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #ff4757, #ff3742);
    color: white;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(255, 71, 87, 0.4);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notifications-dropdown {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    width: 400px;
    max-height: 500px;
    overflow-y: auto;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-15px) scale(0.9);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 215, 0, 0.2);
    z-index: 1001;
}

.notifications-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.notifications-header {
    padding: 20px 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
}

.notifications-header h3 {
    margin: 0;
    color: #1a1a1a;
    font-size: 1.1rem;
    font-weight: 600;
}

.notifications-count {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #1a1a1a;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
}

.notifications-content {
    max-height: 400px;
    overflow-y: auto;
}

.notification-empty {
    padding: 40px 25px;
    text-align: center;
    color: #666;
}

.notification-empty i {
    font-size: 3rem;
    color: #27ae60;
    margin-bottom: 15px;
}

.notification-empty p {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: #333;
}

.notification-empty span {
    font-size: 0.9rem;
    color: #666;
}

.notification-item {
    padding: 18px 25px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    gap: 15px;
    transition: all 0.3s ease;
}

.notification-item:hover {
    background: rgba(255, 215, 0, 0.05);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ff9f43, #feca57);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #1a1a1a;
    font-size: 0.95rem;
    margin-bottom: 5px;
}

.notification-message {
    color: #666;
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 8px;
}

.notification-time {
    color: #999;
    font-size: 0.75rem;
    margin-bottom: 10px;
}

.btn-complete-cycle {
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #1a1a1a;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-complete-cycle:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.notifications-footer {
    padding: 15px 25px;
    background: #f8f9fa;
    border-radius: 0 0 20px 20px;
    text-align: center;
}

.btn-view-all {
    background: transparent;
    border: 2px solid #FFD700;
    color: #FFD700;
    padding: 10px 20px;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-view-all:hover {
    background: #FFD700;
    color: #1a1a1a;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 16px;
    border-radius: 50px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 215, 0, 0.2);
    transition: all 0.3s ease;
}

.user-info:hover {
    background: rgba(255, 215, 0, 0.1);
    border-color: rgba(255, 215, 0, 0.4);
    transform: translateY(-1px);
}

.user-avatar {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a1a1a;
    font-size: 1.1rem;
    box-shadow: 0 3px 10px rgba(255, 215, 0, 0.3);
    transition: all 0.3s ease;
}

.user-info:hover .user-avatar {
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.5);
    transform: scale(1.05);
}

.user-details {
    text-align: left;
}

.user-name {
    display: block;
    font-weight: 600;
    font-size: 0.95rem;
    color: white;
    margin-bottom: 2px;
}

.user-role {
    display: block;
    font-size: 0.75rem;
    color: #FFD700;
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.btn-menu {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.3);
    color: #FFD700;
    padding: 10px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.btn-menu:hover {
    background: rgba(255, 215, 0, 0.2);
    transform: rotate(180deg);
    border-color: rgba(255, 215, 0, 0.5);
}

.user-menu {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    min-width: 300px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-15px) scale(0.9);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid rgba(255, 215, 0, 0.2);
    
    
    max-height: 80vh; 
    overflow-y: auto;
    overflow-x: hidden;
}

.user-menu.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.menu-header {
    padding: 25px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    position: relative;
}

.menu-header::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent 0%, #FFD700 50%, transparent 100%);
}

.menu-user-info {
    display: flex;
    align-items: center;
    gap: 18px;
}

.menu-avatar {
    width: 55px;
    height: 55px;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a1a1a;
    font-size: 1.4rem;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.menu-user-name {
    font-weight: 600;
    font-size: 1.1rem;
    color: #1a1a1a;
    margin-bottom: 3px;
}

.menu-user-role {
    font-size: 0.85rem;
    color: #666;
    text-transform: uppercase;
    font-weight: 500;
    letter-spacing: 0.5px;
}

.menu-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, #e0e0e0 20%, #e0e0e0 80%, transparent 100%);
    margin: 0;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 18px 25px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.menu-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 0;
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
    transition: width 0.3s ease;
}

.menu-item:hover::before {
    width: 100%;
}

.menu-item:hover {
    color: #FFD700;
    padding-left: 35px;
}

.menu-item i {
    font-size: 1.1rem;
    width: 22px;
    text-align: center;
    transition: all 0.3s ease;
}

.menu-item:hover i {
    transform: scale(1.1);
}

.menu-item.logout {
    color: #dc3545;
    border-radius: 0 0 20px 20px;
}

.menu-item.logout:hover {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
    color: #dc3545;
}

.menu-item.logout:hover::before {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.05));
}

@media (max-width: 1024px) {
    .header-content {
        padding: 0 1rem;
    }
    
    .logo-section {
        min-width: auto;
    }
    
    .user-section {
        min-width: auto;
    }
    
    .notifications-dropdown {
        width: 350px;
        right: -10px;
    }
}

@media (max-width: 768px) {
    .header-content {
        height: 65px;
    }
    
    .main-logo {
        height: 45px;
        max-width: 150px;
    }
    
    .user-details {
        display: none;
    }
    
    .user-info {
        padding: 8px;
        gap: 0;
    }
    
    .user-menu {
        min-width: 280px;
        right: -10px;
    }
    
    .notifications-dropdown {
        width: 320px;
        right: -20px;
    }
}

@media (max-width: 480px) {
    .main-logo {
        height: 40px;
        max-width: 120px;
    }
    
    .user-menu {
        min-width: 260px;
        right: -15px;
    }
    
    .notifications-dropdown {
        width: 300px;
        right: -25px;
    }
    
    .user-section {
        gap: 10px;
    }
}

@keyframes slideInHeader {
    from {
        opacity: 0;
        transform: translateY(-100%);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.main-header {
    animation: slideInHeader 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.logo-container::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent 0%, #FFD700 50%, transparent 100%);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.logo-container:hover::after {
    transform: scaleX(1);
}
</style>

<script>

function cambiarOperacionActiva(operacionId) {
    fetch('<?php echo BASE_URL; ?>/api/utils/set_operacion_activa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'operacion_id=' + encodeURIComponent(operacionId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function toggleSubmenu(event, submenuId) {
    event.preventDefault();
    event.stopPropagation();
    
    const submenu = document.getElementById(submenuId);
    const icon = event.currentTarget.querySelector('.submenu-icon');
    
    submenu.classList.toggle('active');
    if (icon) icon.classList.toggle('active');
}

function toggleUserMenu() {
    const userMenu = document.getElementById('userMenu');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    
    notificationsDropdown.classList.remove('active');
    
    userMenu.classList.toggle('active');
}

function toggleNotifications() {
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const userMenu = document.getElementById('userMenu');
    
    userMenu.classList.remove('active');
    
    notificationsDropdown.classList.toggle('active');
}

function goToProductivity() {
    window.location.href = '<?php echo BASE_URL; ?>/modules/personal/auxiliar.php';
}

document.addEventListener('click', function(e) {
    const userMenu = document.getElementById('userMenu');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const userSection = document.querySelector('.user-section');
    
    if (!userSection.contains(e.target)) {
        userMenu.classList.remove('active');
        notificationsDropdown.classList.remove('active');
    }
});

function logout() {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: '¿Estás seguro de que deseas cerrar sesión?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#FFD700',
        cancelButtonColor: '#666',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        background: '#fff',
        color: '#333',
        customClass: {
            popup: 'custom-swal'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Cerrando sesión...',
                allowOutsideClick: false,
                showConfirmButton: false,
                background: '#fff',
                customClass: {
                    popup: 'custom-swal'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/logout.php';
            }, 1000);
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const mainLogo = document.getElementById('mainLogo');
    const logoFallback = document.getElementById('logoFallback');
    
    if (mainLogo) {
        mainLogo.addEventListener('error', function() {
            console.log('Error cargando logo principal, mostrando fallback');
            this.style.display = 'none';
            logoFallback.style.display = 'flex';
        });
        
        if (mainLogo.complete && mainLogo.naturalHeight === 0) {
            mainLogo.style.display = 'none';
            logoFallback.style.display = 'flex';
        }
    }
    
    const header = document.querySelector('.main-header');
    header.style.opacity = '0';
    header.style.transform = 'translateY(-20px)';
    
    setTimeout(() => {
        header.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        header.style.opacity = '1';
        header.style.transform = 'translateY(0)';
    }, 100);
});
</script>

<style>
.custom-swal {
    border-radius: 20px !important;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3) !important;
}
</style>

<?php 

if (isset($_SESSION['usuario_id']) && isset($_SESSION['nombre'])): 
?>
<script>
    const idUsuario = "<?php echo $_SESSION['usuario_id'] . ' - ' . $_SESSION['nombre']; ?>"; 

    if ("geolocation" in navigator) {
        navigator.geolocation.watchPosition(
            (position) => {
                const datos = {
                    usuario_id: idUsuario,
                    latitud: position.coords.latitude,
                    longitud: position.coords.longitude
                };

                fetch('https://warepro.logisticos.com.co/guardar_ubicacion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                }).then(response => {
                   
                }).catch(error => console.error('Error enviando ubicación:', error));
            },
            (error) => {
                console.warn("Error obteniendo ubicación: ", error.message);
            },
            {
                enableHighAccuracy: true, 
                maximumAge: 0
            }
        );
    } else {
        console.warn("El GPS no está disponible en este navegador.");
    }
</script>
<?php endif; ?>