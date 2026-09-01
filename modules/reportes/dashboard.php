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
    <title>Dashboard - Ware Pro</title>
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
            background: #f8f9fa;
            min-height: 100vh;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .welcome-section h2 {
            font-size: 2.5rem;
            color: #1a1a1a;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .welcome-section p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
        }

        
        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 100;
        }

        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 16px 20px 16px 55px;
            border-radius: 30px;
            border: 2px solid #fff;
            font-size: 1.1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            background: white;
            color: #333;
        }

        .search-input-wrapper input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 8px 30px rgba(255, 215, 0, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 22px;
            color: #888;
            font-size: 1.3rem;
            transition: color 0.3s ease;
        }

        .search-input-wrapper input:focus + .search-icon,
        .search-input-wrapper input:focus ~ .search-icon {
            color: #FFD700;
        }

        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 15px;
            margin-top: 10px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            max-height: 350px;
            overflow-y: auto;
            display: none;
            text-align: left;
            border: 1px solid #eee;
        }

        .search-results.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .search-result-item {
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            cursor: pointer;
            transition: background 0.2s ease;
            border-bottom: 1px solid #f5f5f5;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover {
            background: #fffdf5;
        }

        .search-result-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
            font-size: 1.1rem;
        }

        .search-result-item:hover .search-result-icon {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
        }

        .search-result-info {
            display: flex;
            flex-direction: column;
        }

        .search-result-title {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
        }

        .search-result-category {
            font-size: 0.8rem;
            color: #888;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #888;
            font-style: italic;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .dashboard-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            border: 2px solid transparent;
        }

        .dashboard-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }

        .dashboard-card.disabled:hover {
            transform: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(135deg, #faeb9bff 0%, #e5b966ff 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .dashboard-card:not(.disabled):hover::before {
            transform: scaleX(1);
        }

        .dashboard-card:not(.disabled):hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(229, 220, 165, 0.3);
            border-color: #f0dc06ff;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 25px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #1a1a1a;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .dashboard-card.disabled .card-icon {
            background: #ccc;
            color: #888;
        }

        .dashboard-card:not(.disabled):hover .card-icon {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(238, 224, 149, 0.5);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
        }

        .dashboard-card.disabled .card-title {
            color: #888;
        }

        .card-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .dashboard-card.disabled .card-description {
            color: #aaa;
        }

        .card-stats {
            display: flex;
            justify-content: space-around;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FFD700;
            display: block;
        }

        .dashboard-card.disabled .stat-number {
            color: #aaa;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #888;
            margin-top: 5px;
        }

        .dashboard-card.disabled .stat-label {
            color: #bbb;
        }

        
        .supervisores-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            color: white;
        }

        .supervisores-card.disabled {
            background: #f5f5f5;
            color: #888;
        }

        .supervisores-card .card-icon {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
        }

        .supervisores-card .card-title,
        .supervisores-card .card-description {
            color: white;
        }

        .supervisores-card.disabled .card-title,
        .supervisores-card.disabled .card-description {
            color: #888;
        }

        .supervisores-card .card-stats {
            border-top-color: rgba(255, 255, 255, 0.2);
        }

        .supervisores-card .stat-number {
            color: #FFD700;
        }

        .supervisores-card .stat-label {
            color: rgba(255, 255, 255, 0.8);
        }

        .supervisores-card:not(.disabled):hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border-color: #FFD700;
        }

        
        .verificador-card .card-icon { background: linear-gradient(135deg, #FFEB3B 0%, #FFC107 100%); color: #1a1a1a; }
        .auxiliar-card .card-icon { background: linear-gradient(135deg, #FFF176 0%, #FFD54F 100%); color: #1a1a1a; }
        .operadores-card .card-icon { background: linear-gradient(135deg, #FFCC02 0%, #FF8F00 100%); color: #1a1a1a; }

        .access-denied {
            position: relative;
        }

        .access-denied::after {
            content: '🔒 Sin Acceso';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 0.9rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .access-denied:hover::after {
            opacity: 1;
        }

        @media (max-width: 768px) {
            .dashboard-container { padding: 20px 15px; }
            .welcome-section h2 { font-size: 2rem; }
            .cards-grid { grid-template-columns: 1fr; gap: 20px; }
            .dashboard-card { padding: 30px 20px; }
        }

        
        .loading-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(26, 26, 26, 0.95); display: flex; flex-direction: column;
            align-items: center; justify-content: center; z-index: 9999;
            opacity: 0; visibility: hidden; transition: all 0.3s ease;
        }
        .loading-overlay.active { opacity: 1; visibility: visible; }
        .loading-container { display: flex; flex-direction: column; align-items: center; gap: 30px; }
        .logo-container { position: relative; width: 120px; height: 120px; }
        .logo-image {
            width: 100%; height: 100%; object-fit: contain;
            filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.5));
            animation: logoFloat 2s ease-in-out infinite alternate;
        }
        .loading-ring {
            position: absolute; top: -20px; left: -20px; width: 160px; height: 160px;
            border: 3px solid transparent; border-top: 3px solid #FFD700; border-right: 3px solid #FFA500;
            border-radius: 50%; animation: rotate 1.5s linear infinite;
        }
        .loading-ring::before {
            content: ''; position: absolute; top: -8px; left: -8px; width: 160px; height: 160px;
            border: 2px solid transparent; border-bottom: 2px solid rgba(255, 215, 0, 0.3);
            border-left: 2px solid rgba(255, 165, 0, 0.3); border-radius: 50%;
            animation: rotateReverse 2s linear infinite;
        }
        .loading-text { color: white; font-size: 1.2rem; font-weight: 500; text-align: center; margin-top: 10px; }
        .loading-dots { display: flex; gap: 8px; margin-top: 15px; }
        .loading-dot {
            width: 8px; height: 8px; background: #FFD700; border-radius: 50%;
            animation: dotBounce 1.4s infinite ease-in-out both;
        }
        .loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .loading-dot:nth-child(2) { animation-delay: -0.16s; }
        .loading-dot:nth-child(3) { animation-delay: 0s; }

        @keyframes logoFloat {
            0% { transform: translateY(0px) scale(1); filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.5)); }
            100% { transform: translateY(-10px) scale(1.05); filter: drop-shadow(0 5px 25px rgba(255, 215, 0, 0.7)); }
        }
        @keyframes rotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes rotateReverse { 0% { transform: rotate(360deg); } 100% { transform: rotate(0deg); } }
        @keyframes dotBounce {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1.2); opacity: 1; }
        }
    </style>
</head>

<body>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-container">
            <div class="logo-container">
                <div class="loading-ring"></div>
                <img src="../../public/img/logotipo.png" alt="Ware Pro Logo" class="logo-image" id="logoImage">
            </div>
            <div class="loading-text">Cargando...</div>
            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="welcome-section">
            <h2>Panel de Control</h2>
            <p>Gestiona todas las operaciones de tu almacén desde un solo lugar</p>
            
            
            <div class="search-container">
                <div class="search-input-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="globalSearch" placeholder="Busca un módulo, turno, insumo o sección...">
                </div>
                <div class="search-results" id="searchResults">
                    
                </div>
            </div>
        </div>

        <div class="cards-grid">
            
            <div class="dashboard-card supervisores-card <?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'super_admin' || $cargoUsuario === 'lider' || $cargoUsuario === 'supervisor') ? '' : 'disabled access-denied'; ?>"
                onclick="<?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'lider'|| $cargoUsuario === 'super_admin' || $cargoUsuario === 'supervisor') ? 'navigateTo(\'../personal/supervisores.php\')' : 'showAccessDenied()'; ?>">
                <div class="card-icon"><i class="fas fa-user-tie"></i></div>
                <h3 class="card-title">Supervisores</h3>
                <p class="card-description">Gestión completa de supervisión de turnos, insumos, temperatura y control de calidad</p>
                <div class="card-stats">
                    <div class="stat-item"><span class="stat-number">11</span><span class="stat-label">Módulos</span></div>
                    <div class="stat-item"><span class="stat-number">3</span><span class="stat-label">Turnos</span></div>
                </div>
            </div>

            <div class="dashboard-card verificador-card <?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'super_admin' ||   $cargoUsuario === 'lider' || $cargoUsuario === 'verificador') ? '' : 'disabled access-denied'; ?>"
                onclick="<?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'lider'  || $cargoUsuario === 'super_admin' || $cargoUsuario === 'verificador') ? 'navigateTo(\'../personal/verificador.php\')' : 'showAccessDenied()'; ?>">
                <div class="card-icon"><i class="fas fa-clipboard-check"></i></div>
                <h3 class="card-title">Verificador</h3>
                <p class="card-description">Control de verificación, armado, devoluciones y recargue de productos</p>
                <div class="card-stats">
                    <div class="stat-item"><span class="stat-number">4</span><span class="stat-label">Módulos</span></div>
                    <div class="stat-item"><span class="stat-number">3</span><span class="stat-label">Turnos</span></div>
                </div>
            </div>

            <div class="dashboard-card auxiliar-card <?php echo ($cargoUsuario === 'admin'  || $cargoUsuario === 'super_admin' || $cargoUsuario === 'lider' || $cargoUsuario === 'auxiliar') ? '' : 'disabled access-denied'; ?>"
                onclick="<?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'lider' || $cargoUsuario === 'super_admin' || $cargoUsuario === 'auxiliar') ? 'navigateTo(\'../personal/auxiliar.php\')' : 'showAccessDenied()'; ?>">
                <div class="card-icon"><i class="fas fa-boxes"></i></div>
                <h3 class="card-title">Auxiliar</h3>
                <p class="card-description">Operaciones de clasificación, lavado, reempaque, vertimiento y sorting</p>
                <div class="card-stats">
                    <div class="stat-item"><span class="stat-number">10</span><span class="stat-label">Módulos</span></div>
                    <div class="stat-item"><span class="stat-number">24/7</span><span class="stat-label">Activo</span></div>
                </div>
            </div>

            <div class="dashboard-card operadores-card <?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'super_admin'  || $cargoUsuario === 'lider' || $cargoUsuario === 'operador') ? '' : 'disabled access-denied'; ?>"
                onclick="<?php echo ($cargoUsuario === 'admin' || $cargoUsuario === 'lider'  || $cargoUsuario === 'super_admin' || $cargoUsuario === 'operador') ? 'navigateTo(\'../personal/operadores.php\')' : 'showAccessDenied()'; ?>">
                <div class="card-icon"><i class="fas fa-hard-hat"></i></div>
                <h3 class="card-title">Operadores</h3>
                <p class="card-description">Control de rotación de botellas, latas y operación de montacargas</p>
                <div class="card-stats">
                    <div class="stat-item"><span class="stat-number">2</span><span class="stat-label">Módulos</span></div>
                </div>
            </div>
            
           
            
        </div>
    </div>
    
    <script>
        
        const currentRole = "<?php echo $cargoUsuario; ?>";
        
        let isNavigating = false;
        let loadingTimeout;

        function showLoading() {
            if (isNavigating) return;
            isNavigating = true;
            document.getElementById('loadingOverlay').classList.add('active');
            loadingTimeout = setTimeout(() => { hideLoading(); }, 10000); 
        }

        function hideLoading() {
            clearTimeout(loadingTimeout);
            isNavigating = false;
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        function navigateTo(page) {
            if (isNavigating) return; 
            showLoading();
            setTimeout(() => { window.location.href = page; }, 800);
        }

        function showAccessDenied() {
            Swal.fire({
                icon: 'warning',
                title: 'Acceso Denegado',
                text: 'No puedes acceder a esta sección.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#FFD700',
                background: '#fff',
                customClass: { popup: 'swal-popup' }
            });
        }

        
        
       
        const systemModules = [
            
            { title: "Turno A", url: "../turnos/turnoa.php", category: "Supervisores", icon: "fa-sun", roles: ["admin", "lider", "supervisor"] },
            { title: "Turno B", url: "../turnos/turnob.php", category: "Supervisores", icon: "fa-cloud-sun", roles: ["admin", "lider", "supervisor"] },
            { title: "Turno C", url: "../turnos/turnoc.php", category: "Supervisores", icon: "fa-moon", roles: ["admin", "lider", "supervisor"] },
            { title: "Insumos", url: "../insumos/insumos.php", category: "Supervisores", icon: "fa-boxes", roles: ["admin", "lider", "supervisor"] },
            { title: "Temperatura", url: "../temperatura/temperatura.php", category: "Supervisores", icon: "fa-thermometer-half", roles: ["admin", "lider", "supervisor"] },
            { title: "Rotura", url: "../rotura.php", category: "Supervisores", icon: "fa-exclamation-triangle", roles: ["admin", "lider", "supervisor"] },
            { title: "Sider Certificado", url: "../sider_certificado.php", category: "Supervisores", icon: "fa-certificate", roles: ["admin", "lider", "supervisor"] },
            { title: "Error de Verificación", url: "../revision/error_verificacion.php", category: "Supervisores", icon: "fa-bug", roles: ["admin", "lider", "supervisor"] },
            { title: "OWs Cargue", url: "../ows/ows_cargue.php", category: "Supervisores", icon: "fa-truck-loading", roles: ["admin", "lider", "supervisor"] },
            { title: "Tableros Digitales", url: "tablero.php", category: "Supervisores", icon: "fa-desktop", roles: ["admin", "lider", "supervisor"] },
            
            
            { title: "Pasaje de T1", url: "../pasaje.php", category: "Verificador", icon: "fa-exchange-alt", roles: ["admin", "lider", "verificador"] },
            { title: "Error de Armado", url: "../revision/error_armado.php", category: "Verificador", icon: "fa-tools", roles: ["admin", "lider", "verificador"] },
            { title: "Control Devoluciones", url: "../devoluciones/control_devoluciones.php", category: "Verificador", icon: "fa-undo-alt", roles: ["admin", "lider", "verificador"] },
            { title: "Recargue T2", url: "../turnos/recargue_t2.php", category: "Verificador", icon: "fa-sync-alt", roles: ["admin", "lider", "verificador"] },
            { title: "Check-List del WIP", url: "../auditoria/checklist_wip.php", category: "Verificador", icon: "fa-mobile-alt", roles: ["admin", "lider", "verificador"] },
            { title: "Tableros Digitales", url: "tablero.php", category: "Verificador", icon: "fa-mobile-alt", roles: ["admin", "lider", "verificador"] },
            { title: "Sorting Portería", url: "../sorting/sorting_porteria.php", category: "Verificador", icon: "fa-warehouse", roles: ["admin", "lider", "verificador"] },
            { title: "Sorting", url: "../sorting/sortiing.php", category: "Verificador", icon: "fa-clipboard-list", roles: ["admin", "lider", "verificador"] },

            
            { title: "Maquila", url: "../reempaque.php", category: "Auxiliar", icon: "fa-box-open", roles: ["admin", "lider", "auxiliar"] },
            { title: "OWS Maquila", url: "../ows/ows_reempaque.php", category: "Auxiliar", icon: "fa-box-open", roles: ["admin", "lider", "auxiliar"] },
            { title: "Vertimiento", url: "../vertimiento.php", category: "Auxiliar", icon: "fa-tint", roles: ["admin", "lider", "auxiliar"] },
            { title: "OWD Vertimiento", url: "../ows/ows_vertimiento.php", category: "Auxiliar", icon: "fa-tint", roles: ["admin", "lider", "auxiliar"] },
            { title: "Revisión", url: "../revision/revision.php", category: "Auxiliar", icon: "fa-shower", roles: ["admin", "lider", "auxiliar"] },
            { title: "OWS Revisión", url: "../ows/ows_revision.php", category: "Auxiliar", icon: "fa-shower", roles: ["admin", "lider", "auxiliar"] },
            { title: "Check-List del WIP", url: "../auditoria/checklist_wip.php", category: "Auxiliar", icon: "fa-mobile-alt", roles: ["admin", "lider", "auxiliar"] },
            { title: "Tableros Digitales", url: "tablero.php", category: "Auxiliar", icon: "fa-mobile-alt", roles: ["admin", "lider", "auxiliar"] },
            { title: "Sorting", url: "../sorting/sorting.php", category: "Auxiliar", icon: "fa-tasks", roles: ["admin", "lider", "auxiliar"] },
            { title: "Temperatura", url: "../temperatura/temperatura_au.php", category: "Auxiliar", icon: "fa-thermometer-half", roles: ["admin", "lider", "auxiliar"] },
            { title: "PI Desechados", url: "../picking/pi_despachados.php", category: "Auxiliar", icon: "fa-trash-alt", roles: ["admin", "lider", "auxiliar"] },
            { title: "PI Reabastecimiento", url: "../picking/pi_reabastecimiento.php", category: "Auxiliar", icon: "fa-recycle", roles: ["admin", "lider", "auxiliar"] },

            
            { title: "Módulo Supervisores", url: "../personal/supervisores.php", category: "Panel Principal", icon: "fa-user-tie", roles: ["admin", "lider", "supervisor"] },
            { title: "Módulo Verificador", url: "../personal/verificador.php", category: "Panel Principal", icon: "fa-clipboard-check", roles: ["admin", "lider", "verificador"] },
            { title: "Módulo Auxiliar", url: "../personal/auxiliar.php", category: "Panel Principal", icon: "fa-boxes", roles: ["admin", "lider", "auxiliar"] },
            { title: "Módulo Operadores", url: "../personal/operadores.php", category: "Panel Principal", icon: "fa-hard-hat", roles: ["admin", "lider", "operador"] }
        ];

        const searchInput = document.getElementById('globalSearch');
        const searchResultsContainer = document.getElementById('searchResults');

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            
            
            if (query.length === 0) {
                searchResultsContainer.classList.remove('active');
                searchResultsContainer.innerHTML = '';
                return;
            }

            
            const filteredResults = systemModules.filter(module => {
                const matchesText = module.title.toLowerCase().includes(query) || module.category.toLowerCase().includes(query);
                
                
                const hasAccess = currentRole === 'admin' || module.roles.includes(currentRole);
                
                return matchesText && hasAccess;
            });

            
            renderSearchResults(filteredResults);
        });

        function renderSearchResults(results) {
            searchResultsContainer.innerHTML = '';
            
            if (results.length === 0) {
                searchResultsContainer.innerHTML = '<div class="no-results">No se encontraron módulos disponibles para ti.</div>';
            } else {
                results.forEach(result => {
                    const div = document.createElement('div');
                    div.className = 'search-result-item';
                    div.innerHTML = `
                        <div class="search-result-icon"><i class="fas ${result.icon}"></i></div>
                        <div class="search-result-info">
                            <span class="search-result-title">${result.title}</span>
                            <span class="search-result-category">${result.category}</span>
                        </div>
                    `;
                    
                    div.onclick = () => navigateTo(result.url);
                    searchResultsContainer.appendChild(div);
                });
            }
            
            searchResultsContainer.classList.add('active');
        }

        
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResultsContainer.contains(e.target)) {
                searchResultsContainer.classList.remove('active');
            }
        });

        
        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                searchResultsContainer.classList.add('active');
            }
        });

        
        
        document.getElementById('logoImage').addEventListener('error', function() {
            this.style.display = 'none';
            const fallbackIcon = document.createElement('div');
            fallbackIcon.innerHTML = '<i class="fas fa-box" style="font-size: 4rem; color: #FFD700;"></i>';
            fallbackIcon.style.display = 'flex';
            fallbackIcon.style.alignItems = 'center';
            fallbackIcon.style.justifyContent = 'center';
            fallbackIcon.style.width = '100%';
            fallbackIcon.style.height = '100%';
            this.parentNode.appendChild(fallbackIcon);
        });

        window.addEventListener('pageshow', function(event) { if (event.persisted) hideLoading(); });
        window.addEventListener('load', function() { hideLoading(); });
        window.addEventListener('beforeunload', function() { hideLoading(); });

        window.addEventListener('blur', function() {
            setTimeout(() => { if (!document.hasFocus()) hideLoading(); }, 1000);
        });

        document.addEventListener('DOMContentLoaded', function() {
            hideLoading();
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });

        window.addEventListener('popstate', function(event) { hideLoading(); });
    </script>
</body>
</html>