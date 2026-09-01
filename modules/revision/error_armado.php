<?php
require_once '../../core/config.php';
require_once '../../core/header.php';

verificarLogin();

date_default_timezone_set('America/Bogota');

$user_cargo = $_SESSION['cargo'] ?? 'operador';

try {
    $stmt = $pdo->prepare("SELECT * FROM error_armado WHERE operacion_id = ? ORDER BY fecha DESC, fecha_registro DESC");
    $stmt->execute([getOperacionActiva()]);
    $errores = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al cargar los errores de armado: " . $e->getMessage();
    $errores = [];
}
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-black: #1a1a1a;
        --secondary-black: #2d2d2d;
        --primary-gold: #FFD700;
        --secondary-gold: #FFA500;
        --pure-white: #ffffff;
        --light-gray: #f8f9fa;
        --border-gray: #e2e8f0;
        --text-gray: #6c757d;
        --success-green: #28a745;
        --warning-yellow: #ffc107;
        --danger-red: #dc3545;
        --info-blue: #17a2b8;
        --shadow-light: 0 4px 6px rgba(0, 0, 0, 0.05);
        --shadow-medium: 0 8px 25px rgba(0, 0, 0, 0.1);
        --shadow-heavy: 0 20px 40px rgba(0, 0, 0, 0.15);
        --gold-gradient: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
        --black-gradient: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        --border-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--pure-white);
        color: var(--primary-black);
        line-height: 1.6;
        min-height: 100vh;
    }

    .main-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 2rem;
    }

    .page-header {
        background: var(--black-gradient);
        color: var(--pure-white);
        padding: 3rem 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-heavy);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
        animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-20px) rotate(180deg); }
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 1rem;
        position: relative;
        z-index: 2;
    }

    .page-icon {
        background: var(--gold-gradient);
        color: var(--primary-black);
        padding: 1.5rem;
        border-radius: var(--border-radius);
        font-size: 2.5rem;
        box-shadow: var(--shadow-medium);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 215, 0, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0); }
    }

    .page-title h1 {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary-gold);
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        margin: 0;
    }

    .page-subtitle {
        color: #cbd5e1;
        font-size: 1.2rem;
        margin-left: 6.5rem;
        position: relative;
        z-index: 2;
    }

    .action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        background: var(--pure-white);
        padding: 1.5rem 2rem;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-medium);
        border: 1px solid var(--border-gray);
    }

    .search-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex: 1;
        max-width: 400px;
    }

    .search-input {
        flex: 1;
        padding: 1rem 1.25rem;
        border: 2px solid var(--border-gray);
        border-radius: 12px;
        font-size: 1rem;
        transition: var(--transition);
        background: var(--pure-white);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
    }

    .btn-primary {
        background: var(--gold-gradient);
        color: var(--primary-black);
        border: none;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1rem;
        text-decoration: none;
        box-shadow: var(--shadow-light);
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-primary:hover::before { left: 100%; }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
    }

    .badge {
        background: var(--light-gray);
        color: var(--primary-black);
        padding: 0.75rem 1.5rem;
        border-radius: 20px;
        font-size: 0.95rem;
        font-weight: 700;
        border: 2px solid var(--primary-gold);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .badge i {
        color: var(--primary-gold);
    }

    .table-container {
        background: var(--pure-white);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-medium);
        border: 1px solid var(--border-gray);
        position: relative;
        overflow: hidden;
    }

    .table-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gold-gradient);
    }

    .table-header {
        background: var(--black-gradient);
        color: var(--pure-white);
        padding: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-title {
        font-size: 1.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--primary-gold);
    }

    .table-title i {
        font-size: 1.5rem;
    }

    .table-wrapper {
        overflow-x: auto;
        max-height: 70vh;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .data-table th {
        background: var(--light-gray);
        color: var(--primary-black);
        padding: 1rem 0.75rem;
        text-align: left;
        font-weight: 700;
        border-bottom: 2px solid var(--primary-gold);
        position: sticky;
        top: 0;
        z-index: 10;
        white-space: nowrap;
    }

    .data-table td {
        padding: 0.75rem;
        border-bottom: 1px solid var(--border-gray);
        vertical-align: middle;
    }

    .data-table tr:hover {
        background-color: rgba(255, 215, 0, 0.05);
    }

    .turno-badge {
        display: inline-block;
        background: var(--gold-gradient);
        color: var(--primary-black);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .cc-badge {
        background: rgba(23, 162, 184, 0.1);
        color: var(--info-blue);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        border: 1px solid var(--info-blue);
    }

    .errors-summary {
        max-width: 280px;
        min-width: 200px;
    }

    .error-count-badge {
        background: var(--danger-red);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.5rem;
    }

    .errors-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }

    .error-pill {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger-red);
        border: 1px solid rgba(220, 53, 69, 0.3);
        padding: 0.125rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .collaborator-tag {
        background: rgba(255, 193, 7, 0.1);
        color: var(--warning-yellow);
        border: 1px solid rgba(255, 193, 7, 0.3);
        padding: 0.125rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
        white-space: nowrap;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }

    .btn-action {
        background: transparent;
        border: 2px solid;
        padding: 0.5rem;
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
    }

    .btn-view {
        border-color: var(--info-blue);
        color: var(--info-blue);
    }

    .btn-view:hover {
        background: var(--info-blue);
        color: white;
        transform: translateY(-2px);
    }

    .btn-edit {
        border-color: var(--success-green);
        color: var(--success-green);
    }

    .btn-edit:hover {
        background: var(--success-green);
        color: white;
        transform: translateY(-2px);
    }

    .no-data {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-gray);
    }

    .no-data i {
        font-size: 4rem;
        color: var(--border-gray);
        margin-bottom: 1.5rem;
        display: block;
    }

    .no-data h3 {
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
        color: var(--primary-black);
        font-weight: 700;
    }

    .no-data p {
        font-size: 1.1rem;
        color: var(--text-gray);
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background-color: var(--pure-white);
        margin: 1% auto;
        border-radius: var(--border-radius);
        width: 95%;
        max-width: 1200px;
        max-height: 95vh;
        overflow-y: auto;
        box-shadow: var(--shadow-heavy);
        border: 1px solid var(--border-gray);
        animation: slideUp 0.3s ease-out;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(50px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        background: var(--black-gradient);
        color: var(--pure-white);
        padding: 2rem;
        border-radius: var(--border-radius) var(--border-radius) 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: var(--shadow-light);
    }

    .modal-title {
        font-size: 1.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        color: var(--primary-gold);
    }

    .modal-title i {
        font-size: 1.5rem;
    }

    .close {
        color: var(--pure-white);
        font-size: 2rem;
        font-weight: bold;
        cursor: pointer;
        transition: var(--transition);
        padding: 0.5rem;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close:hover {
        background: rgba(255, 215, 0, 0.2);
        color: var(--primary-gold);
        transform: rotate(90deg);
    }

    .modal-body {
        padding: 2.5rem;
    }

    .form-section {
        background: var(--light-gray);
        padding: 2rem;
        border-radius: var(--border-radius);
        margin-bottom: 2rem;
        border: 1px solid var(--border-gray);
        position: relative;
        overflow: hidden;
    }

    .form-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--gold-gradient);
    }

    .form-section-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary-black);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--border-gray);
    }

    .form-section-title i {
        color: var(--primary-gold);
        font-size: 1.2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 700;
        color: var(--primary-black);
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group label i {
        color: var(--primary-gold);
        width: 16px;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid var(--border-gray);
        border-radius: 12px;
        font-size: 1rem;
        transition: var(--transition);
        background: var(--pure-white);
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary-gold);
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
        transform: translateY(-2px);
    }

    .btn-submit {
        background: var(--gold-gradient);
        color: var(--primary-black);
        border: none;
        padding: 1.25rem 2.5rem;
        border-radius: var(--border-radius);
        font-weight: 800;
        cursor: pointer;
        transition: var(--transition);
        font-size: 1.1rem;
        width: 300px;
        margin-top: 2rem;
        box-shadow: var(--shadow-medium);
        position: relative;
        overflow: hidden;
    }

    .btn-submit::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }

    .btn-submit:hover::before { left: 100%; }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-heavy);
    }

    .btn-secondary {
        background: var(--text-gray);
        color: var(--pure-white);
        border: none;
        padding: 1rem 2rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    .modal-footer {
        padding: 2rem;
        background: var(--light-gray);
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        .main-container {
            padding: 1rem;
        }

        .page-title {
            flex-direction: column;
            text-align: center;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 2rem;
        }

        .page-subtitle {
            margin-left: 0;
        }

        .action-bar {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .search-container {
            max-width: none;
        }

        .btn-primary {
            width: 100%;
            justify-content: center;
        }

        .table-wrapper {
            font-size: 0.8rem;
        }

        .data-table th,
        .data-table td {
            padding: 0.5rem 0.25rem;
        }

        .errors-summary {
            max-width: 150px;
            min-width: 120px;
        }

        .modal-content {
            width: 98%;
            margin: 2% auto;
        }

        .form-grid {
            grid-template-columns: 1fr;
        }

        .modal-footer {
            flex-direction: column;
        }
    }

    .swal2-popup {
        border-radius: var(--border-radius) !important;
        font-family: 'Inter', sans-serif !important;
        border: 2px solid var(--primary-gold) !important;
    }

    .swal2-title {
        color: var(--primary-black) !important;
        font-weight: 700 !important;
    }

    .swal2-confirm {
        background: var(--gold-gradient) !important;
        color: var(--primary-black) !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
        border: none !important;
    }

    .swal2-confirm:hover {
        transform: translateY(-2px) !important;
        box-shadow: var(--shadow-medium) !important;
    }

    .swal2-cancel {
        background: var(--text-gray) !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        border: none !important;
    }
</style>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">
            <div class="page-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h1>Errores de Armado</h1>
                <div class="page-subtitle">
                    Control y seguimiento de errores en el proceso de armado
                </div>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <div class="search-container">
            <input type="text"
                class="search-input"
                id="searchInput"
                placeholder="Buscar por fecha, turno, CC, verificador..."
                onkeyup="filtrarErrores()">
        </div>
        <button class="btn-primary" onclick="abrirModal()">
            <i class="fas fa-plus-circle"></i>
            Nuevo Error de Armado
        </button>
        <div class="badge">
            <i class="fas fa-database"></i>
            <?php echo count($errores); ?> registros
        </div>
    </div>

    <?php if (empty($errores)): ?>
        <div class="no-data">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>No hay errores registrados</h3>
            <p>Comienza agregando tu primer reporte de error</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i>
                    Registro de Errores de Armado
                </div>
            </div>
            
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar"></i> Fecha</th>
                            <th><i class="fas fa-clock"></i> Turno</th>
                            <th><i class="fas fa-id-card"></i> CC</th>
                            <th><i class="fas fa-user-check"></i> Verificador</th>
                            <th><i class="fas fa-exclamation-circle"></i> Errores</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errores as $error): ?>
                            <tr class="error-row" data-search="<?php echo strtolower($error['fecha'] . ' ' . $error['turno'] . ' ' . $error['cc'] . ' ' . $error['verificador_reporta']); ?>">
                                <td>
                                    <?php echo date('d/m/Y', strtotime($error['fecha'])); ?>
                                </td>
                                <td>
                                    <span class="turno-badge">
                                        <?php echo htmlspecialchars($error['turno']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($error['cc']): ?>
                                        <span class="cc-badge"><?php echo htmlspecialchars($error['cc']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #999; font-style: italic;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($error['verificador_reporta'] ?: 'N/A'); ?>
                                </td>
                                <td class="errors-summary">
                                    <?php
                                    $errores_encontrados = 0;
                                    $colaboradores = [];
                                    $tipos_error = [];
                                    
                                    for ($i = 1; $i <= 4; $i++):
                                        if (!empty($error["colaborador_error_$i"]) || !empty($error["tipo_error_$i"])):
                                            $errores_encontrados++;
                                            if (!empty($error["colaborador_error_$i"])) {
                                                $colaboradores[] = $error["colaborador_error_$i"];
                                            }
                                            if (!empty($error["tipo_error_$i"])) {
                                                $tipos_error[] = $error["tipo_error_$i"];
                                            }
                                        endif;
                                    endfor;

                                    if ($errores_encontrados > 0):
                                    ?>
                                        <div class="error-count-badge">
                                            <i class="fas fa-exclamation-circle"></i>
                                            <?php echo $errores_encontrados; ?> error<?php echo $errores_encontrados > 1 ? 'es' : ''; ?>
                                        </div>
                                        <div class="errors-list">
                                            <?php 
                                            $tipos_unicos = array_unique($tipos_error);
                                            foreach ($tipos_unicos as $tipo): ?>
                                                <span class="error-pill"><?php echo htmlspecialchars($tipo); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="errors-list" style="margin-top: 0.25rem;">
                                            <?php 
                                            $colaboradores_unicos = array_unique($colaboradores);
                                            foreach ($colaboradores_unicos as $colaborador): ?>
                                                <span class="collaborator-tag" title="<?php echo htmlspecialchars($colaborador); ?>">
                                                    <?php echo htmlspecialchars($colaborador); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="text-align: center; color: #999; font-style: italic; font-size: 0.8rem;">
                                            Sin detalles registrados
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="verError(<?php echo $error['id']; ?>)" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($user_cargo === 'admin'): ?>
                                            <button class="btn-action btn-edit" onclick="editarError(<?php echo $error['id']; ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="errorModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="modalTitle">Agregar Nuevo Error de Armado</span>
            </h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>

        <form id="errorForm">
            <div class="modal-body">
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-info-circle"></i>
                        Información General
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fecha">
                                <i class="fas fa-calendar-alt"></i>
                                Fecha
                            </label>
                            <input type="date" id="fecha" name="fecha" required>
                        </div>

                        <div class="form-group">
                            <label for="turno">
                                <i class="fas fa-clock"></i>
                                Turno
                            </label>
                            <select id="turno" name="turno" required>
                                <option value="">Seleccionar turno</option>
                                <option value="Turno A">Turno A</option>
                                <option value="Turno B">Turno B</option>
                                <option value="Turno C">Turno C</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="cc">
                                <i class="fas fa-id-card"></i>
                                CC
                            </label>
                            <input type="text" id="cc" name="cc" placeholder="Número de CC">
                        </div>

                        <div class="form-group">
                            <label for="verificador_reporta">
                                <i class="fas fa-user-check"></i>
                                Verificador que Reporta
                            </label>
                            <input type="text" id="verificador_reporta" name="verificador_reporta" placeholder="Nombre del verificador" required>
                        </div>
                    </div>
                </div>

                <?php for ($i = 1; $i <= 4; $i++): ?>
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-exclamation-circle"></i>
                        Error <?php echo $i; ?>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="colaborador_error_<?php echo $i; ?>">
                                <i class="fas fa-user"></i>
                                Colaborador que Comete el Error
                            </label>
                            <input type="text" id="colaborador_error_<?php echo $i; ?>" name="colaborador_error_<?php echo $i; ?>" placeholder="Nombre del colaborador">
                        </div>

                        <div class="form-group">
                            <label for="cantidad_<?php echo $i; ?>">
                                <i class="fas fa-hashtag"></i>
                                Cantidad
                            </label>
                            <input type="text" id="cantidad_<?php echo $i; ?>" name="cantidad_<?php echo $i; ?>" placeholder="Cantidad del error">
                        </div>

                        <div class="form-group">
                            <label for="descripcion_producto_<?php echo $i; ?>">
                                <i class="fas fa-box"></i>
                                Descripción del Producto
                            </label>
                            <input type="text" id="descripcion_producto_<?php echo $i; ?>" name="descripcion_producto_<?php echo $i; ?>" placeholder="Descripción del producto">
                        </div>

                        <div class="form-group">
                            <label for="tipo_error_<?php echo $i; ?>">
                                <i class="fas fa-exclamation-triangle"></i>
                                Tipo de Error
                            </label>
                            <select id="tipo_error_<?php echo $i; ?>" name="tipo_error_<?php echo $i; ?>">
                                <option value="">Seleccionar tipo</option>
                                <option value="Sobrante">Sobrante</option>
                                <option value="Faltante">Faltante</option>
                                <option value="Trocado">Trocado</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="placa_<?php echo $i; ?>">
                                <i class="fas fa-car"></i>
                                Placa
                            </label>
                            <input type="text" id="placa_<?php echo $i; ?>" name="placa_<?php echo $i; ?>" placeholder="Placa del vehículo" style="text-transform: uppercase;">
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i>
                    <span id="btnSubmitText">Guardar Error</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="verModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-eye"></i>
                Detalles del Error de Armado
            </h2>
            <span class="close" onclick="cerrarVerModal()">&times;</span>
        </div>
        <div class="modal-body" id="verModalBody">
        </div>

        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="cerrarVerModal()">
                <i class="fas fa-times"></i>
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
    let modoEdicion = false;
    let errorEditando = null;

    function filtrarErrores() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.error-row');

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            if (searchData.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function cerrarTodosLosModales() {
        document.getElementById('errorModal').style.display = 'none';
        document.getElementById('verModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function abrirModal() {
        modoEdicion = false;
        errorEditando = null;
        document.getElementById('modalTitle').textContent = 'Agregar Nuevo Error de Armado';
        document.getElementById('btnSubmitText').textContent = 'Guardar Error';
        document.getElementById('errorForm').reset();

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('fecha').value = today;

        document.getElementById('errorModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('errorModal').style.display = 'none';
        document.getElementById('errorForm').reset();
        document.body.style.overflow = 'auto';
    }

    function cerrarVerModal() {
        document.getElementById('verModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function editarError(id) {
        modoEdicion = true;
        errorEditando = id;
        document.getElementById('modalTitle').textContent = 'Editar Error de Armado';
        document.getElementById('btnSubmitText').textContent = 'Actualizar Error';

        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo datos del error',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('../../api/revision/get_errores_armado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=obtener&id=' + id
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                Swal.close();

                if (data && !data.error) {
                    document.getElementById('fecha').value = data.fecha;
                    document.getElementById('turno').value = data.turno;
                    document.getElementById('cc').value = data.cc || '';
                    document.getElementById('verificador_reporta').value = data.verificador_reporta || '';

                    for (let i = 1; i <= 4; i++) {
                        document.getElementById(`colaborador_error_${i}`).value = data[`colaborador_error_${i}`] || '';
                        document.getElementById(`cantidad_${i}`).value = data[`cantidad_${i}`] || '';
                        document.getElementById(`descripcion_producto_${i}`).value = data[`descripcion_producto_${i}`] || '';
                        document.getElementById(`tipo_error_${i}`).value = data[`tipo_error_${i}`] || '';
                        document.getElementById(`placa_${i}`).value = data[`placa_${i}`] || '';
                    }

                    document.getElementById('errorModal').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.error || 'Error al cargar los datos del error',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#1a1a1a'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error de conexión',
                    text: 'No se pudieron cargar los datos del error. Verifique su conexión a internet.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#1a1a1a'
                });
            });
    }

    function verError(id) {
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo detalles del error',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('../../api/revision/get_errores_armado.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=obtener&id=' + id
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                Swal.close();

                if (data && !data.error) {
                    const fecha = new Date(data.fecha);
                    const fechaFormateada = fecha.toLocaleDateString('es-CO', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    let erroresHtml = '';
                    for (let i = 1; i <= 4; i++) {
                        if (data[`colaborador_error_${i}`] || data[`tipo_error_${i}`]) {
                            erroresHtml += `
                            <div style="background: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <span style="font-weight: 600; color: var(--primary-black);">
                                        Error ${i}: ${data[`colaborador_error_${i}`] || 'Sin especificar'}
                                    </span>
                                    <span style="background: var(--danger-red); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                        ${data[`tipo_error_${i}`] || 'N/A'}
                                    </span>
                                </div>
                                <div style="color: var(--text-gray); font-size: 0.9rem; line-height: 1.4;">
                                    <strong>Cantidad:</strong> ${data[`cantidad_${i}`] || 'N/A'} |
                                    <strong>Producto:</strong> ${data[`descripcion_producto_${i}`] || 'N/A'} |
                                    <strong>Placa:</strong> ${data[`placa_${i}`] || 'N/A'}
                                </div>
                            </div>
                        `;
                        }
                    }

                    if (!erroresHtml) {
                        erroresHtml = `
                        <div style="text-align: center; color: #666; padding: 2rem; background: var(--light-gray); border-radius: 12px;">
                            No se registraron detalles de errores
                        </div>
                    `;
                    }

                    document.getElementById('verModalBody').innerHTML = `
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-info-circle"></i>
                            Información General
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                            <div style="text-align: center; padding: 1rem; background: var(--light-gray); border-radius: 10px; border: 1px solid var(--border-gray);">
                                <div style="font-size: 1rem; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">${fechaFormateada}</div>
                                <div style="font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;">Fecha</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: var(--light-gray); border-radius: 10px; border: 1px solid var(--border-gray);">
                                <div style="font-size: 1rem; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">${data.turno}</div>
                                <div style="font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;">Turno</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: var(--light-gray); border-radius: 10px; border: 1px solid var(--border-gray);">
                                <div style="font-size: 1rem; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">${data.cc || 'N/A'}</div>
                                <div style="font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;">CC</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: var(--light-gray); border-radius: 10px; border: 1px solid var(--border-gray);">
                                <div style="font-size: 1rem; font-weight: 700; color: var(--primary-black); margin-bottom: 0.5rem;">${data.verificador_reporta || 'N/A'}</div>
                                <div style="font-size: 0.8rem; color: var(--text-gray); text-transform: uppercase; letter-spacing: 0.5px;">Verificador</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Errores Reportados
                        </div>
                        <div>
                            ${erroresHtml}
                        </div>
                    </div>
                `;

                    document.getElementById('verModal').style.display = 'block';
                    document.body.style.overflow = 'hidden';
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.error || 'Error al cargar los detalles del error',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#1a1a1a'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error de conexión',
                    text: 'No se pudieron cargar los detalles del error. Verifique su conexión a internet.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#1a1a1a'
                });
            });
    }

    document.getElementById('errorForm').addEventListener('submit', function(e) {
        e.preventDefault();

        cerrarTodosLosModales();

        Swal.fire({
            title: 'Procesando...',
            text: 'Guardando información del error',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const formData = new FormData(this);
        const action = modoEdicion ? 'editar' : 'agregar';
        formData.append('action', action);

        if (modoEdicion) {
            formData.append('id', errorEditando);
        }

        fetch('../../api/revision/get_errores_armado.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#1a1a1a',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || data.error,
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#1a1a1a'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error de conexión',
                    text: 'No se pudo procesar la solicitud. Verifique su conexión a internet.',
                    icon: 'error',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#1a1a1a'
                });
            });
    });

    window.onclick = function(event) {
        const errorModal = document.getElementById('errorModal');
        const verModal = document.getElementById('verModal');

        if (event.target === errorModal) {
            cerrarModal();
        }
        if (event.target === verModal) {
            cerrarVerModal();
        }
    }

    for (let i = 1; i <= 4; i++) {
        document.getElementById(`placa_${i}`).addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    }
</script>

