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
                $actividad = 'REVISION';
                $unidades = intval($_POST['unidades']);
                $usuario_id = $_SESSION['usuario_id'];
                
                $stmt = $pdo->prepare("INSERT INTO ows_revision (fecha, hora, actividad, unidades, usuario_id, operacion_id, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$fecha, $hora, $actividad, $unidades, $usuario_id, getOperacionActiva()]);
                
                $response['success'] = true;
                $response['message'] = 'Registro creado exitosamente';
                break;
                
            case 'editar':
                $id = intval($_POST['id']);
                $fecha = limpiarDatos($_POST['fecha']);
                $hora = limpiarDatos($_POST['hora']);
                $actividad = 'REVISION';
                $unidades = intval($_POST['unidades']);
                
                $stmt = $pdo->prepare("UPDATE ows_revision SET fecha = ?, hora = ?, actividad = ?, unidades = ?, updated_at = NOW() WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$fecha, $hora, $actividad, $unidades, $id, getOperacionActiva()]);
                
                $response['success'] = true;
                $response['message'] = 'Registro actualizado exitosamente';
                break;
                
            case 'eliminar':
                $id = intval($_POST['id']);
                
                $stmt = $pdo->prepare("DELETE FROM ows_revision WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                
                $response['success'] = true;
                $response['message'] = 'Registro eliminado exitosamente';
                break;
                
            case 'obtener':
                $id = intval($_POST['id']);
                
                $stmt = $pdo->prepare("SELECT * FROM ows_revision WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$id, getOperacionActiva()]);
                $registro = $stmt->fetch();
                
                if ($registro) {
                    $response['success'] = true;
                    $response['data'] = $registro;
                } else {
                    $response['message'] = 'Registro no encontrado';
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, u.nombre as usuario_nombre
    FROM ows_revision o
    LEFT JOIN usuarios u ON o.usuario_id = u.id
    WHERE o.operacion_id = ?
    ORDER BY o.fecha DESC, o.hora DESC
");
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();

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
        AND cumple_meta = 0 
        AND estado_ciclo = 'pendiente'
        AND fecha < CURDATE()
        GROUP BY grupo_registro
        ORDER BY fecha DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $notificaciones = $stmt->fetchAll();
    $notificaciones_pendientes = count($notificaciones);
}
require_once '../../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OWS Revisión - Ware-Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            color: #333;
            line-height: 1.6;
        }

        .main-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: white;
            padding: 0;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid #FFD700;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 75px;
        }

        .logo-section {
            display: flex;
            align-items: center;
        }

        .logo-container {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .main-logo {
            height: 75px;
            width: auto;
            max-width: 280px;
            object-fit: contain;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.05);
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
        }

        .user-details {
            text-align: left;
        }

        .user-name {
            display: block;
            font-weight: 600;
            color: white;
        }

        .user-role {
            display: block;
            font-size: 0.75rem;
            color: #FFD700;
            text-transform: uppercase;
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
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a1a1a;
            font-size: 1.5rem;
        }

        .title-text h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .title-text p {
            color: #666;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
        }

        .content-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
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
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .record-count {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a1a;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            min-width: 700px;
        }

        .data-table thead {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            color: white;
        }

        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table tbody tr:hover {
            background: rgba(255, 215, 0, 0.05);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-edit, .btn-delete {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
        }

        .btn-edit:hover {
            background: #3498db;
            color: white;
            transform: scale(1.1);
        }

        .btn-delete {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }

        .btn-delete:hover {
            background: #e74c3c;
            color: white;
            transform: scale(1.1);
        }

        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(0px);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .modal-overlay.show {
            display: flex;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 215, 0, 0.2);
            transform: scale(0.7) translateY(-50px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .modal-overlay.show .modal-container {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        .modal-header {
            padding: 2rem 2rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px 20px 0 0;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .modal-header h3 i {
            color: #FFD700;
        }

        .btn-close {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            width: 40px;
            height: 40px;
            border: 1px solid rgba(231, 76, 60, 0.2);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .btn-close:hover {
            background: #e74c3c;
            color: white;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group label i {
            color: #FFD700;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .form-group input:disabled {
            background: #f8f9fa;
            color: #666;
            cursor: not-allowed;
        }

        .modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            background: #f8f9fa;
            border-radius: 0 0 20px 20px;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #666;
            border: 2px solid #e0e0e0;
            padding: 12px 24px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #ccc;
            transform: translateY(-1px);
        }

        
        .swal2-container {
            z-index: 99999 !important;
        }

        .swal2-popup {
            border-radius: 20px !important;
            z-index: 100000 !important;
        }

        .swal2-confirm {
            background: linear-gradient(135deg, #FFD700, #FFA500) !important;
            color: #1a1a1a !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            font-family: 'Poppins', sans-serif !important;
        }

        .swal2-cancel {
            background: #f8f9fa !important;
            color: #666 !important;
            border: 2px solid #e0e0e0 !important;
            border-radius: 12px !important;
            font-weight: 600 !important;
            font-family: 'Poppins', sans-serif !important;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                gap: 1.5rem;
                padding: 1.5rem;
            }

            .title-section {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
<!-- 
<header class="main-header">
    <div class="header-content">
        <div class="logo-section">
            <div class="logo-container">
                <a href="../reportes/dashboard.php">
                    <img src="../../public/img/logo_blanco.png" alt="Ware Pro Logo" class="main-logo">
                </a>
            </div>
        </div>
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo $_SESSION['nombre']; ?></span>
                    <span class="user-role"><?php echo ucfirst($_SESSION['cargo']); ?></span>
                </div>
            </div>
        </div>
    </div>
</header> -->

<div class="main-container">
    <div class="page-header">
        <div class="title-section">
            <div class="title-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="title-text">
                <h1>OWS Revisión</h1>
                <p>Gestión y control de actividades de revisión</p>
            </div>
        </div>
        <button class="btn-primary" onclick="abrirModal()">
            <i class="fas fa-plus"></i>
            Nuevo Registro
        </button>
    </div>

    <div class="content-container">
        <div class="table-header">
            <h2>
                <i class="fas fa-list"></i>
                Registros de Revisión
            </h2>
            <div class="record-count"><?php echo count($registros); ?> registros</div>
        </div>
        
        <div class="table-wrapper">
            <table class="data-table">
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
                    <?php if (empty($registros)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No hay registros disponibles</p>
                                    <span>Comienza creando tu primer registro</span>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registros as $registro): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></td>
                                <td><?php echo date('H:i', strtotime($registro['hora'])); ?></td>
                                <td><?php echo htmlspecialchars($registro['actividad']); ?></td>
                                <td><?php echo number_format($registro['unidades']); ?></td>
                                <td><?php echo htmlspecialchars($registro['usuario_nombre'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-edit" onclick="editarRegistro(<?php echo $registro['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-delete" onclick="eliminarRegistro(<?php echo $registro['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
                Nuevo Registro de Revisión
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
                    <input type="date" id="fecha" name="fecha" required readonly>
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
                    <input type="text" id="actividad" name="actividad" value="REVISION" disabled>
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

function obtenerFechaHoraColombia() {
    const ahora = new Date();
    const colombia = new Date(ahora.toLocaleString("en-US", {timeZone: "America/Bogota"}));
    const fecha = colombia.toISOString().split('T')[0];
    const hora = colombia.toTimeString().split(' ')[0].substring(0, 5);
    return { fecha, hora };
}


function abrirModal() {
    const { fecha, hora } = obtenerFechaHoraColombia();
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nuevo Registro de Revisión';
    document.getElementById('formRegistro').reset();
    document.getElementById('registroId').value = '';
    document.getElementById('fecha').value = fecha;
    document.getElementById('hora').value = hora;
    document.getElementById('actividad').value = 'REVISION';
    
    const modal = document.getElementById('modalOverlay');
    modal.classList.add('show');
}


function cerrarModal() {
    const modal = document.getElementById('modalOverlay');
    const container = modal.querySelector('.modal-container');
    
    container.style.transform = 'scale(0.7) translateY(-50px)';
    container.style.opacity = '0';
    modal.style.background = 'rgba(0, 0, 0, 0)';
    modal.style.backdropFilter = 'blur(0px)';
    
    setTimeout(() => {
        modal.classList.remove('show');
        container.style.transform = '';
        container.style.opacity = '';
        modal.style.background = '';
        modal.style.backdropFilter = '';
    }, 400);
}

function editarRegistro(id) {
    const formData = new FormData();
    formData.append('action', 'obtener');
    formData.append('id', id);

    fetch('ows_revision.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Registro de Revisión';
            document.getElementById('registroId').value = data.data.id;
            document.getElementById('fecha').value = data.data.fecha;
            document.getElementById('hora').value = data.data.hora;
            document.getElementById('actividad').value = data.data.actividad;
            document.getElementById('unidades').value = data.data.unidades;
            
            const modal = document.getElementById('modalOverlay');
            modal.classList.add('show');
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al obtener el registro', 'error');
    });
}

function eliminarRegistro(id) {
    Swal.fire({
        title: '¿Eliminar registro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'eliminar');
            formData.append('id', id);

            fetch('ows_revision.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Eliminado', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al eliminar el registro', 'error');
            });
        }
    });
}

function guardarRegistro(event) {
    event.preventDefault();
    
    const formData = new FormData(document.getElementById('formRegistro'));
    const id = document.getElementById('registroId').value;
    const action = id ? 'editar' : 'crear';
    
    formData.append('action', action);
    formData.append('actividad', 'REVISION');
    
    if (id) {
        formData.append('id', id);
    }

    fetch('ows_revision.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', data.message, 'success').then(() => {
                cerrarModal();
                setTimeout(() => location.reload(), 500);
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al guardar el registro', 'error');
    });
}


document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});


document.addEventListener('DOMContentLoaded', function() {
    
    const rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach((row, index) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(20px)';
        setTimeout(() => {
            row.style.transition = 'all 0.3s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, index * 100);
    });
});
</script>

</body>
</html>