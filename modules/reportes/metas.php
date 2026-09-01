<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';


if ($_POST) {
    if (isset($_POST['action']) && $_POST['action'] == 'update_meta') {
        try {
            $stmt = $pdo->prepare("UPDATE metas SET meta_minima = ?, disparador = ? WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$_POST['meta_minima'], $_POST['disparador'], $_POST['id'], getOperacionActiva()]);
            echo "<script>
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Meta actualizada correctamente',
                    icon: 'success',
                    confirmButtonColor: '#FFD700'
                });
            </script>";
        } catch (Exception $e) {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Error al actualizar la meta',
                    icon: 'error',
                    confirmButtonColor: '#FFD700'
                });
            </script>";
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] == 'delete_meta') {
        try {
            $stmt = $pdo->prepare("UPDATE metas SET activo = 0 WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$_POST['id'], getOperacionActiva()]);
            echo "<script>
                Swal.fire({
                    title: '¡Eliminado!',
                    text: 'Meta eliminada correctamente',
                    icon: 'success',
                    confirmButtonColor: '#FFD700'
                });
            </script>";
        } catch (Exception $e) {
            echo "<script>
                Swal.fire({
                    title: 'Error',
                    text: 'Error al eliminar la meta',
                    icon: 'error',
                    confirmButtonColor: '#FFD700'
                });
            </script>";
        }
    }
}


$stmt = $pdo->prepare("SELECT * FROM metas WHERE activo = 1 AND operacion_id = ? ORDER BY actividad");
$stmt->execute([getOperacionActiva()]);
$metas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Metas - Ware-Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 5px solid #FFD700;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .table-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border-left: 5px solid #FFD700;
        }

        .table-header {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .table-wrapper {
            overflow-x: auto;
            max-width: 100%;
        }

        .metas-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .metas-table th {
            background: #f8f9fa;
            color: #2c3e50;
            padding: 1.2rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #ecf0f1;
            white-space: nowrap;
        }

        .metas-table td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: middle;
        }

        .metas-table tbody tr {
            transition: all 0.3s ease;
        }

        .metas-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .activity-cell {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 180px;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .activity-info {
            flex-grow: 1;
        }

        .activity-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .activity-unit {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .meta-value {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
            text-align: center;
        }

        .actions-cell {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
        }

        .btn-action {
            padding: 0.5rem 0.8rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1.5rem;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #FFD700;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #ecf0f1;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-cancel {
            background: #95a5a6;
            color: white;
        }

        .btn-cancel:hover {
            background: #7f8c8d;
        }

        .btn-save {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: white;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .table-header {
                padding: 1rem 1.5rem;
            }
            
            .metas-table th,
            .metas-table td {
                padding: 1rem 0.8rem;
            }
            
            .activity-cell {
                min-width: 160px;
            }
            
            .activity-icon {
                width: 35px;
                height: 35px;
            }
            
            .btn-action {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
            
            .modal-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-bullseye"></i>
                Gestión de Metas
            </h1>
            <p>Configura y actualiza las metas de productividad para cada actividad</p>
        </div>

        <div class="table-container">
            <div class="table-header">
                <i class="fas fa-table"></i> Metas de Productividad
            </div>
            <div class="table-wrapper">
                <table class="metas-table">
                    <thead>
                        <tr>
                            <th>Actividad</th>
                            <th>Meta Mínima</th>
                            <th>Disparador</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metas as $meta): ?>
                            <tr>
                                <td>
                                    <div class="activity-cell">
                                        <div class="activity-icon">
                                            <?php 
                                            $icons = [
                                                'Clasificación' => 'fas fa-sort',
                                                'Lavado' => 'fas fa-soap',
                                                'Reempaque' => 'fas fa-box',
                                                'Revisión NR' => 'fas fa-search',
                                                'Revisión' => 'fas fa-clipboard-check',
                                                'Tiempo T1' => 'fas fa-clock',
                                                'Tiempo T2' => 'fas fa-clock',
                                                'Tiempo T4' => 'fas fa-clock',
                                                'Picking' => 'fas fa-hand-paper'
                                            ];
                                            echo '<i class="' . ($icons[$meta['actividad']] ?? 'fas fa-tasks') . '"></i>';
                                            ?>
                                        </div>
                                        <div class="activity-info">
                                            <div class="activity-name"><?php echo $meta['actividad']; ?></div>
                                            <div class="activity-unit"><?php echo $meta['unidad_medida']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="meta-value"><?php echo number_format($meta['meta_minima'], 0); ?></div>
                                </td>
                                <td>
                                    <div class="meta-value"><?php echo number_format($meta['disparador'], 0); ?></div>
                                </td>
                                <td>
                                    <div class="actions-cell">
                                        <button class="btn-action btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($meta)); ?>)">
                                            <i class="fas fa-edit"></i>
                                            Editar
                                        </button>
                                        <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $meta['id']; ?>, '<?php echo $meta['actividad']; ?>')">
                                            <i class="fas fa-trash"></i>
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Meta</h3>
                <button class="modal-close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update_meta">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Actividad</label>
                        <input type="text" id="edit_actividad" class="form-input" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meta Mínima</label>
                        <input type="number" name="meta_minima" id="edit_meta_minima" class="form-input" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Disparador</label>
                        <input type="number" name="disparador" id="edit_disparador" class="form-input" 
                               step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-modal btn-save">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(meta) {
            document.getElementById('edit_id').value = meta.id;
            document.getElementById('edit_actividad').value = meta.actividad;
            document.getElementById('edit_meta_minima').value = meta.meta_minima;
            document.getElementById('edit_disparador').value = meta.disparador;
            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function confirmDelete(id, actividad) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar la meta para "${actividad}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#95a5a6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete_meta">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }

        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeEditModal();
            }
        });
    </script>
</body>
</html>