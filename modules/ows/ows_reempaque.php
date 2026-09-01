<?php
require_once '../../core/config.php';
verificarLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($action) {
            case 'crear':
                $fecha = limpiarDatos($_POST['fecha']);
                $hora = limpiarDatos($_POST['hora']);
                $actividad = limpiarDatos($_POST['actividad']);
                $unidades = intval($_POST['unidades']);
                $usuario_id = $_SESSION['usuario_id'];
                
                $stmt = $pdo->prepare("INSERT INTO ows_reempaque (fecha, hora, actividad, unidades, usuario_id, operacion_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$fecha, $hora, $actividad, $unidades, $usuario_id, getOperacionActiva()]);
                
                $response['success'] = true;
                $response['message'] = 'Registro creado exitosamente';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $fecha = limpiarDatos($_POST['fecha']);
                $hora = limpiarDatos($_POST['hora']);
                $actividad = limpiarDatos($_POST['actividad']);
                $unidades = intval($_POST['unidades']);
                
                $stmt = $pdo->prepare("UPDATE ows_reempaque SET fecha = ?, hora = ?, actividad = ?, unidades = ?, updated_at = NOW() WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$fecha, $hora, $actividad, $unidades, $id, getOperacionActiva()]);
                
                $response['success'] = true;
                $response['message'] = 'Registro actualizado exitosamente';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                $stmt = $pdo->prepare("DELETE FROM ows_reempaque WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                
                $response['success'] = true;
                $response['message'] = 'Registro eliminado exitosamente';
                break;
                
            case 'obtener':
                $id = intval($_POST['id']);
                
                $stmt = $pdo->prepare("SELECT * FROM ows_reempaque WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                $registro = $stmt->fetch();
                
                if ($registro) {
                    $response['success'] = true;
                    $response['data'] = $registro;
                } else {
                    $response['message'] = 'Registro no encontrado';
                }
                break;
                
            case 'listar':
                $stmt = $pdo->prepare("
                    SELECT o.*, u.nombre as usuario_nombre
                    FROM ows_reempaque o
                    LEFT JOIN usuarios u ON o.usuario_id = u.id
                    WHERE o.operacion_id = ?
                    ORDER BY o.fecha DESC, o.hora DESC
                ");
                $stmt->execute([getOperacionActiva()]);
                $registros = $stmt->fetchAll();
                
                $response['success'] = true;
                $response['data'] = $registros;
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

include '../../core/header.php';

$user_cargo = $_SESSION['cargo'] ?? 'operador';
$actividades = ['CLASIFICACION', 'LAVADO', 'REEMPAQUE'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../public/img/logo.png">
    <title>OWS Reempaque - Ware-Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.21/js/jquery.dataTables.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #FFD700;
            --primary-dark: #FFA500;
            --background: #f8f9fa;
            --text-dark: #1a1a1a;
            --text-light: #666;
            --border-color: #e0e0e0;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #FFD700;
            --primary-dark: #FFA500;
            --background: #f8f9fa;
            --text-dark: #1a1a1a;
            --text-light: #666;
            --border-color: #e0e0e0;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #3498db;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            color: #333;
            line-height: 1.6;
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .header-content-main {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .title-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .title-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-dark);
            font-size: 1.5rem;
        }

        .title-text h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .title-text p {
            color: var(--text-light);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--text-dark);
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: var(--text-light);
            border: 2px solid var(--border-color);
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid rgba(255, 215, 0, 0.1);
        }

        .table-container {
            padding: 2rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .table-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #owsTable {
            width: 100% !important;
            border-collapse: collapse;
            font-size: 0.9rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
        }

        #owsTable thead th {
            background: linear-gradient(135deg, var(--text-dark), #2d2d2d) !important;
            color: white !important;
            font-weight: 600 !important;
            padding: 1rem !important;
            text-align: left !important;
            font-size: 0.9rem !important;
            white-space: nowrap;
        }

        #owsTable thead th i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        #owsTable tbody td {
            padding: 1rem !important;
            border-bottom: 1px solid #f0f0f0 !important;
            vertical-align: middle !important;
            background: white !important;
        }

        #owsTable tbody tr:hover td {
            background-color: rgba(255, 215, 0, 0.05) !important;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .btn-edit, .btn-delete {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-edit {
            background: rgba(52, 152, 219, 0.1);
            color: var(--info);
            border: 1px solid rgba(52, 152, 219, 0.2);
        }

        .btn-edit:hover {
            background: var(--info);
            color: white;
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .btn-delete:hover {
            background: var(--danger);
            color: white;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            backdrop-filter: blur(5px);
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 2rem 2rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-close {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.2);
            color: var(--danger);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            background: var(--danger);
            color: white;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-group input:disabled {
            background: #f8f9fa;
            color: var(--text-light);
            cursor: not-allowed;
        }

        .modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .dataTables_wrapper {
            font-family: 'Poppins', sans-serif !important;
        }

        .dataTables_filter {
            margin-bottom: 1rem !important;
        }

        .dataTables_filter input {
            border: 2px solid var(--border-color) !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 0.9rem !important;
            margin-left: 0.5rem !important;
        }

        .dataTables_filter input:focus {
            border-color: var(--primary-color) !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1) !important;
        }

        .dataTables_length select {
            border: 2px solid var(--border-color) !important;
            border-radius: 8px !important;
            padding: 4px 8px !important;
            margin: 0 0.5rem !important;
        }

        .dataTables_paginate .paginate_button {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
            border: 1px solid #cbd5e1 !important;
            color: #475569 !important;
            margin: 2px !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-weight: 500 !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
            color: var(--text-dark) !important;
            border-color: var(--primary-color) !important;
        }

        .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%) !important;
            color: var(--text-dark) !important;
            border-color: var(--primary-color) !important;
        }

        .dataTables_info {
            color: var(--text-light) !important;
            font-weight: 500 !important;
        }

        .swal2-container {
            z-index: 20000 !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .header-content-main {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .table-container {
                padding: 1rem;
            }

            .modal-container {
                width: 95%;
                margin: 1rem;
            }

            .modal-footer {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="page-header">
        <div class="header-content-main">
            <div class="title-section">
                <div class="title-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="title-text">
                    <h1>OWS Reempaque</h1>
                    <p>Gestión y control de actividades de reempaque</p>
                </div>
            </div>
            <button class="btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus"></i>
                Nuevo Registro
            </button>
        </div>
    </div>

    <div class="content-container">
        <div class="table-container">
            <div class="table-header">
                <h2>
                    <i class="fas fa-list"></i>
                    Registros de Reempaque
                </h2>
            </div>
            
            <table id="owsTable" class="display">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Fecha</th>
                        <th><i class="fas fa-clock"></i> Hora</th>
                        <th><i class="fas fa-tasks"></i> Actividad</th>
                        <th><i class="fas fa-cube"></i> Unidades</th>
                        <th><i class="fas fa-user"></i> Usuario</th>
                        <th><i class="fas fa-cogs"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 id="modalTitle">
                <i class="fas fa-plus-circle"></i>
                Nuevo Registro de Reempaque
            </h3>
            <button class="btn-close" onclick="cerrarModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="formRegistro" onsubmit="guardarRegistro(event)">
            <div class="modal-body">
                <input type="hidden" id="registroId" name="id">
                
                <div class="form-group">
                    <label for="fecha">
                        <i class="fas fa-calendar"></i>
                        Fecha
                    </label>
                    <input type="date" id="fecha" name="fecha" required>
                </div>
                
                <div class="form-group">
                    <label for="hora">
                        <i class="fas fa-clock"></i>
                        Hora
                    </label>
                    <input type="time" id="hora" name="hora" required>
                </div>
                
                <div class="form-group">
                    <label for="actividad">
                        <i class="fas fa-tasks"></i>
                        Actividad
                    </label>
                    <select id="actividad" name="actividad" required>
                        <option value="">Seleccionar actividad</option>
                        <?php foreach ($actividades as $act): ?>
                            <option value="<?php echo $act; ?>"><?php echo $act; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="unidades">
                        <i class="fas fa-cube"></i>
                        Unidades
                    </label>
                    <input type="number" id="unidades" name="unidades" min="1" required>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i>
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let dataTable;
    const userCargo = '<?php echo $user_cargo; ?>';

    function obtenerFechaHoraColombia() {
        const ahora = new Date();
        const colombia = new Date(ahora.toLocaleString("en-US", {timeZone: "America/Bogota"}));
        
        const fecha = colombia.toISOString().split('T')[0];
        const hora = colombia.toTimeString().split(' ')[0].substring(0, 5);
        
        return { fecha, hora };
    }

    function makeRequest(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        return fetch('ows_reempaque.php', {
            method: 'POST',
            body: formData
        }).then(response => response.json());
    }

    function initDataTable() {
        dataTable = $('#owsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            },
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: [5] },
                { searchable: false, targets: [5] }
            ],
            dom: 'lBfrtip',
            buttons: [],
            responsive: true,
            ajax: {
                url: 'ows_reempaque.php',
                type: 'POST',
                data: { action: 'listar' },
                dataSrc: function(json) {
                    if (json.success) {
                        return json.data;
                    } else {
                        console.error('Error loading data:', json.message);
                        return [];
                    }
                }
            },
            columns: [
                { 
                    data: 'fecha',
                    render: function(data) {
                        const date = new Date(data + 'T00:00:00');
                        return date.toLocaleDateString('es-CO', {
                            year: 'numeric',
                            month: 'short',
                            day: '2-digit',
                            weekday: 'short'
                        });
                    }
                },
                { 
                    data: 'hora',
                    render: function(data) {
                        return `<div style="font-family: monospace; font-weight: 600;">
                                    ${data.substring(0, 5)}
                                </div>`;
                    }
                },
                { 
                    data: 'actividad',
                    render: function(data) {
                        let badgeColor = '#3498db';
                        if (data === 'REEMPAQUE') badgeColor = '#27ae60';
                        if (data === 'LAVADO') badgeColor = '#f39c12';
                        if (data === 'CLASIFICACION') badgeColor = '#9b59b6';
                        
                        return `<span style="background: ${badgeColor}; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                    ${data}
                                </span>`;
                    }
                },
                { 
                    data: 'unidades',
                    render: function(data) {
                        return `<div style="text-align: center; font-weight: 600; color: #2c3e50;">
                                    ${parseInt(data).toLocaleString()}
                                </div>`;
                    }
                },
                { 
                    data: 'usuario_nombre',
                    render: function(data) {
                        return `<div style="display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-user" style="color: #95a5a6; font-size: 0.8rem;"></i>
                                    ${data || 'N/A'}
                                </div>`;
                    }
                },
                { 
                    data: 'id',
                    render: function(data) {
                        if (userCargo === 'admin') {
                            return `<div class="action-buttons">
                                        <button class="btn-edit" onclick="editarRegistro(${data})" title="Editar registro">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" onclick="eliminarRegistro(${data})" title="Eliminar registro">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>`;
                        } else {
                            return '<div style="text-align: center; color: #95a5a6;">-</div>';
                        }
                    }
                }
            ]
        });
    }

    function abrirModal() {
        const { fecha, hora } = obtenerFechaHoraColombia();
        
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nuevo Registro de Reempaque';
        document.getElementById('formRegistro').reset();
        document.getElementById('registroId').value = '';
        document.getElementById('fecha').value = fecha;
        document.getElementById('fecha').readonly = true;
        document.getElementById('hora').value = hora;
        document.getElementById('actividad').value = 'REEMPAQUE';
        document.getElementById('modalOverlay').classList.add('active');
    }

    function cerrarModal() {
        document.getElementById('modalOverlay').classList.remove('active');
        document.getElementById('fecha').disabled = false;
    }

    function editarRegistro(id) {
        makeRequest('obtener', { id })
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Registro';
                document.getElementById('registroId').value = data.data.id;
                document.getElementById('fecha').value = data.data.fecha;
                document.getElementById('fecha').disabled = false;
                document.getElementById('hora').value = data.data.hora;
                document.getElementById('actividad').value = data.data.actividad;
                document.getElementById('unidades').value = data.data.unidades;
                document.getElementById('modalOverlay').classList.add('active');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#FFD700'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al obtener el registro',
                confirmButtonColor: '#FFD700'
            });
        });
    }

    function eliminarRegistro(id) {
        Swal.fire({
            title: '¿Eliminar registro?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#95a5a6'
        }).then((result) => {
            if (result.isConfirmed) {
                makeRequest('eliminar', { id })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: data.message,
                            confirmButtonColor: '#FFD700',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            dataTable.ajax.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#FFD700'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al eliminar el registro',
                        confirmButtonColor: '#FFD700'
                    });
                });
            }
        });
    }

    function guardarRegistro(event) {
        event.preventDefault();
        
        const formData = new FormData(document.getElementById('formRegistro'));
        const id = document.getElementById('registroId').value;
        const action = id ? 'editar' : 'crear';
        
        const data = {
            fecha: formData.get('fecha'),
            hora: formData.get('hora'),
            actividad: formData.get('actividad'),
            unidades: formData.get('unidades')
        };
        
        if (id) data.id = id;

        Swal.fire({
            title: 'Guardando...',
            html: 'Por favor espera mientras se procesa la información',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        makeRequest(action, data)
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: data.message,
                    confirmButtonColor: '#FFD700',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    cerrarModal();
                    dataTable.ajax.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#FFD700'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al guardar el registro',
                confirmButtonColor: '#FFD700'
            });
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target === document.getElementById('modalOverlay')) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        initDataTable();
    });
</script>
</body>
</html>