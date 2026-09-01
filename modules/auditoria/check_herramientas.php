<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';


$stmt_user = $pdo->prepare("SELECT cargo FROM usuarios WHERE id = ?");
$stmt_user->execute([$_SESSION['usuario_id']]);
$usuario_actual = $stmt_user->fetch();
$es_admin = ($usuario_actual['cargo'] === 'admin');


$stmt = $pdo->prepare("
    SELECT c.*, u.nombre, u.cargo
    FROM check_herramientas c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    WHERE c.operacion_id = ?
    ORDER BY c.fecha_registro DESC
");
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();


date_default_timezone_set('America/Bogota');
$fecha_actual = date('Y-m-d H:i:s'); 
$fecha_mostrar = date('d/m/Y H:i:s');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check de Herramientas - WARE PRO</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 5px solid #FFD700;
        }

        .page-title {
            color: #1a1a1a;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-subtitle {
            color: #666;
            font-size: 1rem;
        }

        .action-button {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 1% auto;
            padding: 0;
            border-radius: 15px;
            width: 95%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        .modal-content.large {
            max-width: 800px;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-30px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .modal-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header.edit {
            background: linear-gradient(135deg, #FFA500 0%, #FFD700 100%);
            color: #1a1a1a;
        }

        .modal-header.view {
            background: linear-gradient(135deg, #000000 0%, #000000 100%);
        }

        .modal-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #FFA500;
        }

        .close {
            color: #FFD700;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-header.edit .close,
        .modal-header.view .close {
            color: white;
        }

        .close:hover {
            color: white;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.9rem;
            line-height: 1.3;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-control:disabled {
            background: #e9ecef;
            color: #6c757d;
        }

        .radio-group {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s ease;
            flex: 1;
            justify-content: center;
            font-size: 0.9rem;
        }

        .radio-option:hover {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
        }

        .radio-option input[type="radio"]:checked + span {
            color: #1a1a1a;
            font-weight: 600;
        }

        .radio-option input[type="radio"]:checked {
            accent-color: #FFD700;
        }

        .radio-option.yes:has(input:checked) {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }

        .radio-option.no:has(input:checked) {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        
        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 20px 25px;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .records-table {
            width: 100%;
            border-collapse: collapse;
        }

        .records-table th {
            background: #f8f9fa;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            color: #1a1a1a;
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .records-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .records-table tbody tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-aprobado {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-rechazado {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar-small {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .user-details-small {
            display: flex;
            flex-direction: column;
        }

        .user-name-small {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.85rem;
        }

        .user-role-small {
            font-size: 0.75rem;
            color: #666;
            text-transform: capitalize;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view {
            background: #007bff;
            color: white;
        }

        .btn-view:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #ffc107;
            color: #1a1a1a;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .no-records {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #FFD700;
        }

        .detail-label {
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #495057;
            font-size: 0.95rem;
        }

        .detail-value.highlight {
            font-weight: 600;
            font-size: 1.1rem;
        }

        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #1a1a1a;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .modal-content {
                width: 98%;
                margin: 2% auto;
            }
            
            .radio-group {
                flex-direction: column;
                gap: 8px;
            }

            .modal-body {
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .records-table {
                font-size: 0.8rem;
            }

            .records-table th,
            .records-table td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-tools"></i>
                Check de Herramientas
            </div>
            <div class="page-subtitle">
                Verificación y control de herramientas de trabajo
            </div>
        </div>

        <button class="action-button" onclick="openCheckModal()">
            <i class="fas fa-plus-circle"></i>
            Nuevo Check - Pistola de Calor
        </button>

        
        <div id="checkModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>
                        <i class="fas fa-temperature-high"></i>
                        Check - Pistola de Calor
                    </h2>
                    <span class="close" onclick="closeCheckModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="checkForm">
                        <div class="form-group">
                            <label for="marca_temporal">Marca temporal *</label>
                            <input type="hidden" id="marca_temporal" name="marca_temporal" value="<?php echo $fecha_actual; ?>">
                            <input type="text" class="form-control" value="<?php echo $fecha_mostrar; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>1. ¿La pistola de calor se encuentra en buen estado físico (sin cables pelados, grietas, etc)?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="estado_fisico" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="estado_fisico" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>2. ¿El enchufe y los conectores están en buen estado y limpios?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="enchuf_conectores" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="enchuf_conectores" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>3. ¿El operador está utilizando los Elementos de Protección Personal (EPP) necesarios?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="epp_operador" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="epp_operador" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>4. ¿Se almacenó la pistola en un lugar seguro y seco después del uso?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="almacenamiento" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="almacenamiento" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>5. ¿El operador recibió capacitación sobre el uso correcto de la pistola de calor?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="capacitacion" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="capacitacion" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="submitBtn">
                            <i class="fas fa-check-circle"></i>
                            Realizar Check
                        </button>
                    </form>
                </div>
            </div>
        </div>

        
        <div id="viewModal" class="modal">
            <div class="modal-content large">
                <div class="modal-header view">
                    <h2>
                        <i class="fas fa-eye"></i>
                        Detalles del Check
                    </h2>
                    <span class="close" onclick="closeViewModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <div id="viewContent" class="detail-grid">
                        
                    </div>
                </div>
            </div>
        </div>

        
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header edit">
                    <h2>
                        <i class="fas fa-edit"></i>
                        Editar Check
                    </h2>
                    <span class="close" onclick="closeEditModal()">&times;</span>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="edit_id" name="id">
                        
                        <div class="form-group">
                            <label for="edit_marca_temporal">Marca temporal *</label>
                            <input type="text" id="edit_marca_temporal" name="marca_temporal" class="form-control" readonly>
                        </div>

                        <div class="form-group">
                            <label>1. ¿La pistola de calor se encuentra en buen estado físico (sin cables pelados, grietas, etc)?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="estado_fisico" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="estado_fisico" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>2. ¿El enchufe y los conectores están en buen estado y limpios?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="enchuf_conectores" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="enchuf_conectores" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>3. ¿El operador está utilizando los Elementos de Protección Personal (EPP) necesarios?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="epp_operador" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="epp_operador" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>4. ¿Se almacenó la pistola en un lugar seguro y seco después del uso?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="almacenamiento" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="almacenamiento" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>5. ¿El operador recibió capacitación sobre el uso correcto de la pistola de calor?</label>
                            <div class="radio-group">
                                <label class="radio-option yes">
                                    <input type="radio" name="capacitacion" value="SI" required>
                                    <span>SÍ</span>
                                </label>
                                <label class="radio-option no">
                                    <input type="radio" name="capacitacion" value="NO">
                                    <span>NO</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="editSubmitBtn">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>

        
        <div class="table-container">
            <div class="table-header">
                <i class="fas fa-history"></i>
                Historial de Checks
            </div>
            <div class="table-responsive">
                <?php if (count($registros) > 0): ?>
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Herramienta</th>
                                <th>Fecha Check</th>
                                <th>Estado Físico</th>
                                <th>Conectores</th>
                                <th>EPP</th>
                                <th>Almacenamiento</th>
                                <th>Capacitación</th>
                                <th>Resultado</th>
                                <th>Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar-small">
                                                <?php echo strtoupper(substr($registro['nombre'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details-small">
                                                <div class="user-name-small"><?php echo htmlspecialchars($registro['nombre']); ?></div>
                                                <div class="user-role-small"><?php echo ucfirst($registro['cargo']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($registro['herramienta']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($registro['marca_temporal'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $registro['estado_fisico'] === 'SI' ? 'aprobado' : 'rechazado'; ?>">
                                            <?php echo $registro['estado_fisico']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $registro['enchuf_conectores'] === 'SI' ? 'aprobado' : 'rechazado'; ?>">
                                            <?php echo $registro['enchuf_conectores']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $registro['epp_operador'] === 'SI' ? 'aprobado' : 'rechazado'; ?>">
                                            <?php echo $registro['epp_operador']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $registro['almacenamiento'] === 'SI' ? 'aprobado' : 'rechazado'; ?>">
                                            <?php echo $registro['almacenamiento']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $registro['capacitacion'] === 'SI' ? 'aprobado' : 'rechazado'; ?>">
                                            <?php echo $registro['capacitacion']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($registro['resultado']) === 'aprobado' ? 'aprobado' : 'rechazado'; ?>">
                                            <?php echo $registro['resultado']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($registro['fecha_registro'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewCheck(<?php echo $registro['id']; ?>)" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($es_admin): ?>
                                            <button class="btn-action btn-edit" onclick="editCheck(<?php echo $registro['id']; ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($es_admin): ?>
                                                <button class="btn-action btn-delete" onclick="deleteCheck(<?php echo $registro['id']; ?>)" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-records">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                        <p>No hay registros de checks disponibles.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        
        let currentCheckId = null;

        
        function openCheckModal() {
            document.getElementById('checkModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeCheckModal() {
            document.getElementById('checkModal').style.display = 'none';
            document.getElementById('checkForm').reset();
            document.body.style.overflow = 'auto';
        }

        
        function openViewModal() {
            document.getElementById('viewModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.getElementById('editForm').reset();
            document.body.style.overflow = 'auto';
        }

        
        window.onclick = function(event) {
            const checkModal = document.getElementById('checkModal');
            const viewModal = document.getElementById('viewModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target == checkModal) {
                closeCheckModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        
        async function viewCheck(id) {
            try {
                const response = await fetch('../../api/auditoria/get_check.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                if (!response.ok) {
                    throw new Error('Error al obtener los datos');
                }

                const data = await response.json();

                if (data.success) {
                    const check = data.check;
                    const viewContent = document.getElementById('viewContent');
                    
                    viewContent.innerHTML = `
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-user"></i> Usuario
                            </div>
                            <div class="detail-value highlight">${check.nombre} (${check.cargo})</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-tools"></i> Herramienta
                            </div>
                            <div class="detail-value">${check.herramienta}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-clock"></i> Marca Temporal
                            </div>
                            <div class="detail-value">${new Date(check.marca_temporal).toLocaleString('es-ES')}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-shield-alt"></i> Estado Físico
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-${check.estado_fisico === 'SI' ? 'aprobado' : 'rechazado'}">
                                    ${check.estado_fisico}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-plug"></i> Enchufes y Conectores
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-${check.enchuf_conectores === 'SI' ? 'aprobado' : 'rechazado'}">
                                    ${check.enchuf_conectores}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-hard-hat"></i> EPP Operador
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-${check.epp_operador === 'SI' ? 'aprobado' : 'rechazado'}">
                                    ${check.epp_operador}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-warehouse"></i> Almacenamiento
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-${check.almacenamiento === 'SI' ? 'aprobado' : 'rechazado'}">
                                    ${check.almacenamiento}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-graduation-cap"></i> Capacitación
                            </div>
                            <div class="detail-value">
                                <span class="status-badge status-${check.capacitacion === 'SI' ? 'aprobado' : 'rechazado'}">
                                    ${check.capacitacion}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-clipboard-check"></i> Resultado Final
                            </div>
                            <div class="detail-value highlight">
                                <span class="status-badge status-${check.resultado.toLowerCase() === 'aprobado' ? 'aprobado' : 'rechazado'}">
                                    ${check.resultado}
                                </span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">
                                <i class="fas fa-calendar"></i> Fecha de Registro
                            </div>
                            <div class="detail-value">${new Date(check.fecha_registro).toLocaleString('es-ES')}</div>
                        </div>
                    `;
                    
                    openViewModal();
                } else {
                    throw new Error(data.message || 'Error al cargar los datos');
                }
            } catch (error) {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al cargar los detalles: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        }

        
        async function editCheck(id) {
            try {
                const response = await fetch('../../api/auditoria/get_check.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });

                if (!response.ok) {
                    throw new Error('Error al obtener los datos');
                }

                const data = await response.json();

                if (data.success) {
                    const check = data.check;
                    currentCheckId = id;
                    
                    
                    document.getElementById('edit_id').value = check.id;
                    document.getElementById('edit_marca_temporal').value = new Date(check.marca_temporal).toLocaleString('es-ES');
                    
                    
                    document.querySelector(`#editForm input[name="estado_fisico"][value="${check.estado_fisico}"]`).checked = true;
                    document.querySelector(`#editForm input[name="enchuf_conectores"][value="${check.enchuf_conectores}"]`).checked = true;
                    document.querySelector(`#editForm input[name="epp_operador"][value="${check.epp_operador}"]`).checked = true;
                    document.querySelector(`#editForm input[name="almacenamiento"][value="${check.almacenamiento}"]`).checked = true;
                    document.querySelector(`#editForm input[name="capacitacion"][value="${check.capacitacion}"]`).checked = true;
                    
                    openEditModal();
                } else {
                    throw new Error(data.message || 'Error al cargar los datos');
                }
            } catch (error) {
                Swal.fire({
                    title: 'Error',
                    text: 'Error al cargar los datos para edición: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        }

        
        function deleteCheck(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('../../api/auditoria/delete_check.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ id: id })
                        });

                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: 'El registro ha sido eliminado correctamente',
                                icon: 'success',
                                confirmButtonColor: '#28a745'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            throw new Error(data.message || 'Error al eliminar');
                        }
                    } catch (error) {
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al eliminar el registro: ' + error.message,
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                }
            });
        }

        
        document.getElementById('checkForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const originalContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loading-spinner"></div> Procesando...';
            
            const formData = new FormData(this);
            formData.append('action', 'guardar_check');
            
            fetch('../../api/auditoria/procesar_check.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const isApproved = data.resultado === 'APROBADO';
                    
                    closeCheckModal();
                    
                    Swal.fire({
                        title: isApproved ? '¡Éxito!' : '¡Atención!',
                        icon: isApproved ? 'success' : 'error',
                        text: data.message,
                        confirmButtonColor: '#FFD700',
                        confirmButtonText: 'Entendido'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
                
                Swal.fire({
                    title: 'Error',
                    text: 'Error al procesar la solicitud: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        });

        
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('editSubmitBtn');
            const originalContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="loading-spinner"></div> Guardando...';
            
            const formData = new FormData(this);
            formData.append('action', 'editar_check');
            
            fetch('../../api/auditoria/procesar_check.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeEditModal();
                    
                    Swal.fire({
                        title: '¡Actualizado!',
                        text: 'El check ha sido actualizado correctamente',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Error desconocido');
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalContent;
                
                Swal.fire({
                    title: 'Error',
                    text: 'Error al actualizar: ' + error.message,
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            });
        });
    </script>
</body>
</html>