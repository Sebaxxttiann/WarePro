<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';

$es_admin = isset($_SESSION['cargo']) && $_SESSION['cargo'] === 'admin';

$stmt = $pdo->prepare("
    SELECT s.*, u.nombre as colaborador_nombre
    FROM sorting s
    INNER JOIN usuarios u ON s.colaborador_id = u.id
    WHERE s.operacion_id = ?
    ORDER BY s.fecha DESC, s.hora_inicio DESC
");
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();

date_default_timezone_set('America/Bogota');
$fecha_hoy = date('Y-m-d');
$nombre_usuario = $_SESSION['nombre'] ?? 'Usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sorting</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1400px;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #FFD700;
        }

        .header-section h1 {
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-section h1 i {
            color: #FFD700;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
        }

        th i {
            color: #FFD700;
            margin-right: 5px;
        }

        tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        td {
            padding: 15px;
            font-size: 14px;
            color: #495057;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-turno-a {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-turno-b {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-turno-c {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .btn-action {
            background: none;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 14px;
        }

        .btn-edit {
            color: #FFD700;
        }

        .btn-edit:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .btn-delete {
            color: #dc3545;
        }

        .btn-delete:hover {
            background: #ffebee;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFD700;
        }

        .modal-header h2 {
            color: #1a1a1a;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 i {
            color: #FFD700;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6c757d;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .btn-close:hover {
            color: #dc3545;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1a1a1a;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group label i {
            color: #FFD700;
            margin-right: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FFD700;
        }

        .form-group input:disabled {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert.show {
            display: flex;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #FFD700;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .header-section {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <h1>
                <i class="fas fa-sort-amount-down"></i>
                Sorting
            </h1>
            <button class="btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus"></i>
                Nuevo Registro
            </button>
        </div>

        <div id="alertContainer"></div>

        <div class="table-container">
            <?php if (count($registros) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Fecha</th>
                        <th><i class="fas fa-user"></i> Colaborador</th>
                        <th><i class="fas fa-clock"></i> Turno</th>
                        <th><i class="fas fa-hourglass-start"></i> Hora Inicio</th>
                        <th><i class="fas fa-hourglass-end"></i> Hora Fin</th>
                        <?php if ($es_admin): ?>
                        <th><i class="fas fa-cog"></i> Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $registro): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></td>
                        <td><?php echo htmlspecialchars($registro['colaborador_nombre']); ?></td>
                        <td>
                            <span class="badge badge-turno-<?php echo strtolower($registro['turno']); ?>">
                                Turno <?php echo htmlspecialchars($registro['turno']); ?>
                            </span>
                        </td>
                        <td><?php echo date('h:i A', strtotime($registro['hora_inicio'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($registro['hora_fin'])); ?></td>
                        <?php if ($es_admin): ?>
                        <td>
                            <button class="btn-action btn-edit" onclick="editarRegistro(<?php echo $registro['id']; ?>, '<?php echo $registro['turno']; ?>', '<?php echo $registro['hora_inicio']; ?>', '<?php echo $registro['hora_fin']; ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-delete" onclick="eliminarRegistro(<?php echo $registro['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No hay registros disponibles</p>
                <small>Comienza agregando tu primer registro de sorting</small>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal" id="modalRegistro">
        <div class="modal-content">
            <div class="modal-header">
                <h2>
                    <i class="fas fa-plus-circle"></i>
                    <span id="modalTitle">Nuevo Registro</span>
                </h2>
                <button class="btn-close" onclick="cerrarModal()">&times;</button>
            </div>
            <form id="formRegistro">
                <input type="hidden" name="accion" id="accion" value="guardar">
                <input type="hidden" name="id" id="registro_id">
                
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Fecha</label>
                    <input type="date" name="fecha" id="fecha" value="<?php echo $fecha_hoy; ?>" disabled required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Colaborador</label>
                    <input type="text" value="<?php echo htmlspecialchars($nombre_usuario); ?>" disabled>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-clock"></i> Turno</label>
                    <select name="turno" id="turno" required>
                        <option value="">Seleccione un turno</option>
                        <option value="A">Turno A</option>
                        <option value="B">Turno B</option>
                        <option value="C">Turno C</option>
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-hourglass-start"></i> Hora de Inicio</label>
                    <input type="time" name="hora_inicio" id="hora_inicio" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-hourglass-end"></i> Hora de Fin</label>
                    <input type="time" name="hora_fin" id="hora_fin" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalRegistro').classList.add('active');
            document.getElementById('formRegistro').reset();
            document.getElementById('accion').value = 'guardar';
            document.getElementById('fecha').value = '<?php echo $fecha_hoy; ?>';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Nuevo Registro';
        }

        function cerrarModal() {
            document.getElementById('modalRegistro').classList.remove('active');
        }

        function editarRegistro(id, turno, horaInicio, horaFin) {
            document.getElementById('modalRegistro').classList.add('active');
            document.getElementById('accion').value = 'editar';
            document.getElementById('registro_id').value = id;
            document.getElementById('turno').value = turno;
            document.getElementById('hora_inicio').value = horaInicio;
            document.getElementById('hora_fin').value = horaFin;
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Registro';
        }

        function eliminarRegistro(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este registro?')) {
                const formData = new FormData();
                formData.append('accion', 'eliminar');
                formData.append('id', id);

                fetch('../../api/sorting/sorting_ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarAlerta(data.message || 'Registro eliminado exitosamente', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarAlerta(data.message || 'Error al eliminar el registro', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarAlerta('Error en la solicitud', 'error');
                });
            }
        }

        document.getElementById('formRegistro').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('fecha', document.getElementById('fecha').value);

            fetch('../../api/sorting/sorting_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarAlerta(data.message || 'Registro guardado exitosamente', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    mostrarAlerta(data.message || 'Error al guardar el registro', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarAlerta('Error en la solicitud', 'error');
            });
        });

        function mostrarAlerta(mensaje, tipo) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${tipo} show`;
            alert.innerHTML = `
                <i class="fas fa-${tipo === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${mensaje}
            `;
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalRegistro');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>
</html>