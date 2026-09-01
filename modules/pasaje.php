<?php
require_once '../core/config.php';
verificarLogin();


date_default_timezone_set('America/Bogota');


try {
    $stmt = $pdo->prepare("SELECT * FROM pasajes WHERE operacion_id = ? ORDER BY marca_temporal DESC");
    $stmt->execute([getOperacionActiva()]);
    $pasajes = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error al cargar los pasajes: " . $e->getMessage();
    $pasajes = [];
}


$user_cargo = $_SESSION['cargo'] ?? 'operador';


try {
    $stmt = $pdo->query("SELECT id_material, material FROM productos ORDER BY material");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    $productos = [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pasajes - WARE PRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 5px solid #FFD700;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .page-title i {
            font-size: 2rem;
            color: #FFD700;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(26, 26, 26, 0.2);
        }

        .page-title h1 {
            font-size: 2.2rem;
            color: #1a1a1a;
            font-weight: 700;
        }

        .page-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-left: 4rem;
        }

        .controls-section {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .search-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-width: 300px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(26, 26, 26, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(26, 26, 26, 0.3);
            background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%);
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .table-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #FFD700;
        }

        .table-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .table-title i {
            font-size: 1.5rem;
            color: #FFD700;
        }

        .records-count {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .pasajes-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .pasajes-table th,
        .pasajes-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        .pasajes-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #1a1a1a;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .pasajes-table tbody tr {
            transition: all 0.3s ease;
        }

        .pasajes-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .fecha-cell {
            font-weight: 600;
            color: #1a1a1a;
            white-space: nowrap;
        }

        .placa-cell {
            font-weight: 700;
            color: #1a1a1a;
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
            border: 2px solid #FFD700;
        }

        .material-cell {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            padding: 8px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cantidad-cell {
            font-weight: 600;
            color: #2e7d32;
            text-align: center;
        }

        .peso-cell {
            font-weight: 600;
            color: #1976d2;
            text-align: center;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-no-ok {
            background: #ffebee;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }

        .status-ok {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }

        .actions-cell {
            white-space: nowrap;
        }

        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #1976d2;
        }

        .btn-view:hover {
            background: #1976d2;
            color: white;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
            border: 1px solid #f57c00;
        }

        .btn-edit:hover {
            background: #f57c00;
            color: white;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
            border: 1px solid #d32f2f;
        }

        .btn-delete:hover {
            background: #d32f2f;
            color: white;
            transform: translateY(-1px);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-icon {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .empty-subtitle {
            color: #999;
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000 !important;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
            z-index: 2001 !important;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .modal-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #FFD700;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-title i {
            font-size: 1.8rem;
            color: #FFD700;
        }

        .close {
            color: #ccc;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
        }

        .close:hover {
            color: #FFD700;
            background: rgba(255, 215, 0, 0.2);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-grid {
            display: grid;
            gap: 2rem;
        }

        .form-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            font-size: 1.1rem;
        }

        .section-header i {
            color: #FFD700;
            background: #1a1a1a;
            padding: 8px;
            border-radius: 8px;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .readonly-field {
            background: #f5f5f5 !important;
            color: #666;
            cursor: not-allowed;
        }

        
        .select-search-container {
            position: relative;
        }

        .select-search-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        .select-search-input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .select-option {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s ease;
        }

        .select-option:hover,
        .select-option.highlighted {
            background: #f8f9fa;
        }

        .select-option:last-child {
            border-bottom: none;
        }

        .select-option-text {
            font-weight: 600;
            color: #1a1a1a;
        }

        .select-option-sub {
            font-size: 0.9rem;
            color: #666;
            margin-top: 2px;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        
        .swal2-container {
            z-index: 10000 !important;
        }

        .swal2-popup {
            z-index: 10001 !important;
        }

        .swal2-backdrop-show {
            z-index: 9999 !important;
        }

        
        .swal-high-zindex {
            z-index: 10000 !important;
        }

        .swal-high-zindex .swal2-popup {
            z-index: 10001 !important;
        }

        .swal-high-zindex .swal2-backdrop {
            z-index: 9999 !important;
        }

        
        body.modal-open {
            overflow: hidden !important;
        }

        
        @media (max-width: 1200px) {
            .pasajes-table {
                font-size: 0.8rem;
            }
            
            .pasajes-table th,
            .pasajes-table td {
                padding: 0.8rem 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title h1 {
                font-size: 1.8rem;
            }

            .page-subtitle {
                margin-left: 0;
                margin-top: 0.5rem;
            }

            .controls-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-container {
                min-width: auto;
            }

            .table-container {
                margin: 0 -1rem;
                border-radius: 0;
            }

            .pasajes-table {
                font-size: 0.75rem;
            }

            .pasajes-table th,
            .pasajes-table td {
                padding: 0.5rem 0.3rem;
            }

            .btn-action {
                padding: 6px 8px;
                font-size: 0.7rem;
            }

            .btn-action i {
                font-size: 0.8rem;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }

            .modal-header {
                padding: 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-footer {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .page-title i {
                font-size: 1.5rem;
                padding: 10px;
            }

            .table-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .pasajes-table th,
            .pasajes-table td {
                padding: 0.4rem 0.2rem;
            }

            .actions-cell {
                white-space: normal;
            }

            .btn-action {
                display: block;
                margin: 2px 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <?php include '../core/header.php'; ?>

    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-truck-loading"></i>
                <h1>Gestión de Pasajes</h1>
            </div>
            <p class="page-subtitle">Control y seguimiento de pasajes de materiales</p>
        </div>

        <div class="controls-section">
            <div class="search-container">
                <input type="text"
                    class="search-input"
                    id="searchInput"
                    placeholder="Buscar por placa, origen, verificador..."
                    onkeyup="filtrarPasajes()">
            </div>
            <button class="btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus"></i>
                Nuevo Pasaje
            </button>
        </div>

        <?php if (empty($pasajes)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <h3 class="empty-title">No hay pasajes registrados</h3>
                <p class="empty-subtitle">Comienza agregando tu primer pasaje</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <div class="table-header">
                    <div class="table-title">
                        <i class="fas fa-table"></i>
                        Registro de Pasajes
                    </div>
                    <div class="records-count">
                        <?php echo count($pasajes); ?> registros
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="pasajes-table" id="pasajesTable">
                        <thead>
                            <tr>
                                <th>FECHA</th>
                                <th>PLACA</th>
                                <th>ORIGEN</th>
                                <th>VERIFICADOR</th>
                                <th>MATERIAL</th>
                                <th>CAJAS/BANDEJAS</th>
                                <th>PESO (KG)</th>
                                <th>STATUS</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pasajes as $pasaje): ?>
                                <tr data-search="<?php echo strtolower($pasaje['placa_sider'] . ' ' . $pasaje['origen'] . ' ' . $pasaje['verificador'] . ' ' . $pasaje['descripcion_material']); ?>">
                                    <td class="fecha-cell">
                                        <?php echo date('d/m/Y H:i', strtotime($pasaje['marca_temporal'])); ?>
                                    </td>
                                    <td>
                                        <span class="placa-cell"><?php echo htmlspecialchars($pasaje['placa_sider']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($pasaje['origen']); ?></td>
                                    <td><?php echo htmlspecialchars($pasaje['verificador']); ?></td>
                                    <td>
                                        <span class="material-cell" title="<?php echo htmlspecialchars($pasaje['descripcion_material']); ?>">
                                            <?php echo htmlspecialchars($pasaje['descripcion_material']); ?>
                                        </span>
                                    </td>
                                    <td class="cantidad-cell"><?php echo number_format($pasaje['cantidad_cajas']); ?></td>
                                    <td class="peso-cell"><?php echo number_format($pasaje['peso_kg'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $pasaje['observaciones'] === 'OK' ? 'status-ok' : 'status-no-ok'; ?>">
                                            <?php echo htmlspecialchars($pasaje['observaciones']); ?>
                                        </span>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn-action btn-view" onclick="verPasaje(<?php echo $pasaje['id']; ?>)" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($user_cargo === 'admin'): ?>
                                        <button class="btn-action btn-edit" onclick="editarPasaje(<?php echo $pasaje['id']; ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php endif; ?>                                        
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    
    <div id="pasajeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">
                    <i class="fas fa-truck-loading"></i>
                    <span id="modalTitle">Agregar Nuevo Pasaje</span>
                </h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>

            <form id="pasajeForm">
                <div class="modal-body">
                    <div class="form-grid">
                        
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-info-circle"></i>
                                Información General
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input type="date"
                                        id="fecha"
                                        name="fecha"
                                        required>
                                </div>

                                <div class="form-group">
                                    <label for="hora">Hora</label>
                                    <input type="time"
                                        id="hora"
                                        name="hora"
                                        required
                                        step="1">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="placa_sider">Placa Sider</label>
                                    <input type="text"
                                        id="placa_sider"
                                        name="placa_sider"
                                        required
                                        style="text-transform: uppercase;"
                                        placeholder="Ej: ABC123">
                                </div>

                                <div class="form-group">
                                    <label for="origen">Origen</label>
                                    <select id="origen" name="origen" required>
                                        <option value="">Seleccionar origen</option>
                                        <option value="Bucaramanga">Bucaramanga</option>
                                        <option value="Tocancipa">Tocancipa</option>
                                        <option value="Tibasosa">Tibasosa</option>
                                        <option value="Barranquilla">Barranquilla</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="verificador">Verificador</label>
                                    <select id="verificador" name="verificador" required>
                                        <option value="">Seleccionar verificador</option>
                                        <option value="BREHITER JOSE JAIMES CAMPOS">BREHITER JOSE JAIMES CAMPOS</option>
                                        <option value="EGLY JOSE GONZALEZ ROLON">EGLY JOSE GONZALEZ ROLON</option>
                                        <option value="JAIDER ALBERTO TARAZONA BALAGUERA">JAIDER ALBERTO TARAZONA BALAGUERA</option>
                                        <option value="JESUS DAVID CARVAJAL NAVAS">JESUS DAVID CARVAJAL NAVAS</option>
                                        <option value="JESUS STEVENSON LEON SIERRA">JESUS STEVENSON LEON SIERRA</option>
                                        <option value="JOSE ALBERTO RIVERO CONTRERAS">JOSE ALBERTO RIVERO CONTRERAS</option>
                                        <option value="JUAN FRAN PEREZ QUINTERO">JUAN FRAN PEREZ QUINTERO</option>
                                        <option value="MARCOS ALEJANDRO ESPAÑA LARA">MARCOS ALEJANDRO ESPAÑA LARA</option>
                                        <option value="ROVIN ENRIQUE JIMENEZ OROZCO">ROVIN ENRIQUE JIMENEZ OROZCO</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-boxes"></i>
                                Material y Cantidades
                            </div>

                            <div class="form-group">
                                <label for="descripcion_material">Descripción de Material</label>
                                <div class="select-search-container">
                                    <input type="text" 
                                           id="descripcion_material_input" 
                                           class="select-search-input" 
                                           placeholder="Buscar por SKU o nombre del material..." 
                                           autocomplete="off"
                                           onclick="toggleMaterialDropdown()"
                                           onkeyup="filterMaterials()"
                                           readonly>
                                    <input type="hidden" id="descripcion_material" name="descripcion_material" required>
                                    <div id="material_dropdown" class="select-dropdown">
                                        <?php foreach ($productos as $producto): ?>
                                            <div class="select-option" onclick="selectMaterial('<?php echo htmlspecialchars($producto['id_material']); ?>', '<?php echo htmlspecialchars($producto['material']); ?>')">
                                                <div class="select-option-text">SKU: <?php echo htmlspecialchars($producto['id_material']); ?></div>
                                                <div class="select-option-sub"><?php echo htmlspecialchars($producto['material']); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cantidad_cajas">Cantidad en Cajas o Bandejas</label>
                                    <input type="number"
                                        id="cantidad_cajas"
                                        name="cantidad_cajas"
                                        required
                                        min="0"
                                        value="0">
                                </div>

                                <div class="form-group">
                                    <label for="peso_kg">Peso (kg)</label>
                                    <input type="number"
                                        id="peso_kg"
                                        name="peso_kg"
                                        required
                                        min="0"
                                        step="0.01"
                                        value="0.00">
                                </div>
                            </div>
                        </div>

                        
                        <div class="form-section">
                            <div class="section-header">
                                <i class="fas fa-comment-alt"></i>
                                Observaciones
                            </div>

                            <div class="form-group">
                                <label for="observaciones">Estado</label>
                                <input type="text"
                                    id="observaciones"
                                    name="observaciones"
                                    value="NO OK"
                                    readonly
                                    class="readonly-field">
                            </div>

                            <div class="form-group">
                                <label for="observaciones2">Observaciones Adicionales</label>
                                <textarea id="observaciones2"
                                    name="observaciones2"
                                    placeholder="Observaciones adicionales (opcional)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        <span id="btnSubmitText">Guardar Pasaje</span>
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
                    Detalles del Pasaje
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
        let pasajeEditando = null;
        let materialSelected = false;

        
        const productos = <?php echo json_encode($productos); ?>;

        
        function filtrarPasajes() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#pasajesTable tbody tr');

            let visibleCount = 0;
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                if (searchData.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            
            const recordsCount = document.querySelector('.records-count');
            if (recordsCount) {
                recordsCount.textContent = visibleCount + ' registros';
            }
        }

        
        function toggleMaterialDropdown() {
            const dropdown = document.getElementById('material_dropdown');
            const isVisible = dropdown.style.display === 'block';
            
            
            document.querySelectorAll('.select-dropdown').forEach(dd => {
                dd.style.display = 'none';
            });
            
            dropdown.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                document.getElementById('descripcion_material_input').focus();
            }
        }

        function filterMaterials() {
            const input = document.getElementById('descripcion_material_input');
            const filter = input.value.toLowerCase();
            const dropdown = document.getElementById('material_dropdown');
            const options = dropdown.querySelectorAll('.select-option');

            dropdown.style.display = 'block';

            options.forEach(option => {
                const sku = option.querySelector('.select-option-text').textContent.toLowerCase();
                const material = option.querySelector('.select-option-sub').textContent.toLowerCase();
                
                if (sku.includes(filter) || material.includes(filter)) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        }

        function selectMaterial(sku, material) {
            document.getElementById('descripcion_material_input').value = `SKU: ${sku} - ${material}`;
            document.getElementById('descripcion_material').value = material;
            document.getElementById('material_dropdown').style.display = 'none';
            materialSelected = true;
        }

        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.select-search-container')) {
                document.querySelectorAll('.select-dropdown').forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            }
        });

        
        function cerrarTodosLosModales() {
            document.getElementById('pasajeModal').style.display = 'none';
            document.getElementById('verModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        
        function abrirModal() {
            modoEdicion = false;
            pasajeEditando = null;
            materialSelected = false;
            document.getElementById('modalTitle').textContent = 'Agregar Nuevo Pasaje';
            document.getElementById('btnSubmitText').textContent = 'Guardar Pasaje';
            document.getElementById('pasajeForm').reset();

            
            document.getElementById('descripcion_material_input').value = '';
            document.getElementById('descripcion_material').value = '';

            
            const now = new Date();
            const colombiaTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000) + (-5 * 3600000));

            const fechaStr = colombiaTime.toISOString().split('T')[0];
            const horaStr = colombiaTime.toTimeString().split(' ')[0];

            document.getElementById('fecha').value = fechaStr;
            document.getElementById('hora').value = horaStr;
            document.getElementById('cantidad_cajas').value = '0';
            document.getElementById('peso_kg').value = '0.00';
            document.getElementById('observaciones').value = 'NO OK';

            document.getElementById('pasajeModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        
        function cerrarModal() {
            document.getElementById('pasajeModal').style.display = 'none';
            document.getElementById('pasajeForm').reset();
            document.getElementById('descripcion_material_input').value = '';
            document.getElementById('descripcion_material').value = '';
            materialSelected = false;
            document.body.style.overflow = 'auto';
        }

        
        function cerrarVerModal() {
            document.getElementById('verModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        
        function editarPasaje(id) {
            modoEdicion = true;
            pasajeEditando = id;
            materialSelected = false;
            document.getElementById('modalTitle').textContent = 'Editar Pasaje';
            document.getElementById('btnSubmitText').textContent = 'Actualizar Pasaje';

            Swal.fire({
                title: 'Cargando...',
                text: 'Obteniendo datos del pasaje',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    container: 'swal-high-zindex'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../api/pasaje/get_pasajes.php', {
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
                        const fechaHora = new Date(data.marca_temporal);
                        const fechaStr = fechaHora.toISOString().split('T')[0];
                        const horaStr = fechaHora.toTimeString().split(' ')[0];

                        document.getElementById('fecha').value = fechaStr;
                        document.getElementById('hora').value = horaStr;
                        document.getElementById('placa_sider').value = data.placa_sider;
                        document.getElementById('origen').value = data.origen;
                        document.getElementById('verificador').value = data.verificador;
                        
                        document.getElementById('descripcion_material').value = data.descripcion_material;
                        const producto = productos.find(p => p.material === data.descripcion_material);
                        if (producto) {
                            document.getElementById('descripcion_material_input').value = `SKU: ${producto.id_material} - ${producto.material}`;
                        } else {
                            document.getElementById('descripcion_material_input').value = data.descripcion_material;
                        }
                        materialSelected = true;
                        
                        document.getElementById('cantidad_cajas').value = data.cantidad_cajas;
                        document.getElementById('peso_kg').value = data.peso_kg;
                        document.getElementById('observaciones').value = data.observaciones;
                        document.getElementById('observaciones2').value = data.observaciones2 || '';

                        document.getElementById('pasajeModal').style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Error al cargar los datos del pasaje',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#1a1a1a',
                            customClass: {
                                container: 'swal-high-zindex'
                            }
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error de conexión',
                        text: 'No se pudieron cargar los datos del pasaje. Verifique su conexión a internet.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#1a1a1a',
                        customClass: {
                            container: 'swal-high-zindex'
                        }
                    });
                });
        }

        
        function verPasaje(id) {
            Swal.fire({
                title: 'Cargando...',
                text: 'Obteniendo detalles del pasaje',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    container: 'swal-high-zindex'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../api/pasaje/get_pasajes.php', {
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
                        const fecha = new Date(data.marca_temporal);
                        const fechaFormateada = fecha.toLocaleDateString('es-CO', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });

                        document.getElementById('verModalBody').innerHTML = `
                        <div class="form-grid">
                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-info-circle"></i>
                                    Información General
                                </div>
                                <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div class="info-item" style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <div class="info-value" style="font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem;">${fechaFormateada}</div>
                                        <div class="info-label" style="font-size: 0.9rem; color: #666;">Fecha y Hora</div>
                                    </div>
                                    <div class="info-item" style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <div class="info-value" style="font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem;">${data.placa_sider}</div>
                                        <div class="info-label" style="font-size: 0.9rem; color: #666;">Placa Sider</div>
                                    </div>
                                    <div class="info-item" style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <div class="info-value" style="font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem;">${data.origen}</div>
                                        <div class="info-label" style="font-size: 0.9rem; color: #666;">Origen</div>
                                    </div>
                                    <div class="info-item" style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <div class="info-value" style="font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem;">${data.verificador}</div>
                                        <div class="info-label" style="font-size: 0.9rem; color: #666;">Verificador</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-boxes"></i>
                                    Material y Cantidades
                                </div>
                                <div class="material-badge" style="display: block; text-align: center; margin-bottom: 1rem; padding: 1rem; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: #1a1a1a; border-radius: 15px; font-weight: 600;">
                                    ${data.descripcion_material}
                                </div>
                                <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                                    <div class="info-item" style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <div class="info-value" style="font-size: 1.3rem; font-weight: 600; color: #2e7d32; margin-bottom: 0.5rem;">${parseInt(data.cantidad_cajas).toLocaleString()}</div>
                                        <div class="info-label" style="font-size: 0.9rem; color: #666;">Cajas/Bandejas</div>
                                    </div>
                                    <div class="info-item" style="text-align: center; padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <div class="info-value" style="font-size: 1.3rem; font-weight: 600; color: #1976d2; margin-bottom: 0.5rem;">${parseFloat(data.peso_kg).toFixed(2)} kg</div>
                                        <div class="info-label" style="font-size: 0.9rem; color: #666;">Peso</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <i class="fas fa-comment-alt"></i>
                                    Observaciones
                                </div>
                                <div class="observaciones-section" style="margin-bottom: 1rem;">
                                    <div class="observaciones-title" style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem; font-weight: 600; color: #1a1a1a;">
                                        <i class="fas fa-flag"></i>
                                        Estado
                                    </div>
                                    <div class="observaciones-content" style="padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <span class="status-badge ${data.observaciones === 'OK' ? 'status-ok' : 'status-no-ok'}">${data.observaciones}</span>
                                    </div>
                                </div>
                                ${data.observaciones2 ? `
                                <div class="observaciones-section">
                                    <div class="observaciones-title" style="display: flex; align-items: center; gap: 8px; margin-bottom: 0.5rem; font-weight: 600; color: #1a1a1a;">
                                        <i class="fas fa-comment-dots"></i>
                                        Observaciones Adicionales
                                    </div>
                                    <div class="observaciones-content" style="padding: 1rem; background: white; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        ${data.observaciones2}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    `;

                        document.getElementById('verModal').style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.error || 'Error al cargar los detalles del pasaje',
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#1a1a1a',
                            customClass: {
                                container: 'swal-high-zindex'
                            }
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error de conexión',
                        text: 'No se pudieron cargar los detalles del pasaje. Verifique su conexión a internet.',
                        icon: 'error',
                        confirmButtonText: 'Aceptar',
                        confirmButtonColor: '#1a1a1a',
                        customClass: {
                            container: 'swal-high-zindex'
                        }
                    });
                });
        }

        
        document.getElementById('pasajeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (!materialSelected || !document.getElementById('descripcion_material').value) {
                Swal.fire({
                    title: 'Material requerido',
                    text: 'Por favor selecciona un material de la lista.',
                    icon: 'warning',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#1a1a1a',
                    customClass: {
                        container: 'swal-high-zindex'
                    }
                });
                return;
            }

            cerrarTodosLosModales();

            Swal.fire({
                title: 'Procesando...',
                text: 'Guardando información del pasaje',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                customClass: {
                    container: 'swal-high-zindex'
                },
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(this);

            const fecha = document.getElementById('fecha').value;
            const hora = document.getElementById('hora').value;
            const marcaTemporal = fecha + ' ' + hora;

            formData.append('marca_temporal', marcaTemporal);

            const action = modoEdicion ? 'editar' : 'agregar';
            formData.append('action', action);

            if (modoEdicion) {
                formData.append('id', pasajeEditando);
            }

            fetch('../api/pasaje/get_pasajes.php', {
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
                            timerProgressBar: true,
                            customClass: {
                                container: 'swal-high-zindex'
                            }
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || data.error,
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                            confirmButtonColor: '#1a1a1a',
                            customClass: {
                                container: 'swal-high-zindex'
                            }
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
                        confirmButtonColor: '#1a1a1a',
                        customClass: {
                            container: 'swal-high-zindex'
                        }
                    });
                });
        });

        
        window.onclick = function(event) {
            const pasajeModal = document.getElementById('pasajeModal');
            const verModal = document.getElementById('verModal');

            if (event.target === pasajeModal) {
                cerrarModal();
            }
            if (event.target === verModal) {
                cerrarVerModal();
            }
        }

        
        document.getElementById('placa_sider').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });

        
        document.getElementById('descripcion_material_input').addEventListener('input', function() {
            this.removeAttribute('readonly');
            filterMaterials();
        });

        document.getElementById('descripcion_material_input').addEventListener('focus', function() {
            this.removeAttribute('readonly');
            toggleMaterialDropdown();
        });
    </script>
</body>

</html>