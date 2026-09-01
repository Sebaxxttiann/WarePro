<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar Informativo - WARE PRO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 5px solid #FFD700;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #666;
            font-size: 1rem;
        }

        .action-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
        }

        .content-grid {
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        }

        .info-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.15);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
        }

        .card-content {
            padding: 1.5rem;
        }

        .card-text {
            font-size: 1rem;
            line-height: 1.6;
            color: #333;
            margin-bottom: 1rem;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e0e0e0;
            font-size: 0.85rem;
            color: #666;
        }

        .card-actions {
            display: flex;
            gap: 10px;
            margin-top: 1rem;
        }

        .btn-action {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-view:hover {
            background: #bbdefb;
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
        }

        .btn-edit:hover {
            background: #ffe0b2;
        }

        .btn-delete {
            background: #ffebee;
            color: #d32f2f;
        }

        .btn-delete:hover {
            background: #ffcdd2;
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 2rem;
            border-radius: 20px 20px 0 0;
            position: relative;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .close {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #FFD700;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close:hover {
            transform: translateY(-50%) scale(1.1);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-display {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            border: 2px dashed #e0e0e0;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-input-display:hover {
            border-color: #FFD700;
            background: #fffbf0;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
        }

        .empty-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #999;
        }

        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .action-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .modal-content {
                width: 95%;
                margin: 2% auto;
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
            <h1 class="page-title">
                <i class="fas fa-info-circle" style="color: #FFD700; margin-right: 10px;"></i>
                Cargar Informativo
            </h1>
            <p class="page-subtitle">Gestiona la información importante del sistema</p>
        </div>

        
        <div class="action-bar">
            <div>
                <span style="color: #666; font-weight: 500;">
                    <i class="fas fa-list"></i>
                    Total de informativos: <span id="totalCount">0</span>
                </span>
            </div>
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i>
                Agregar Informativo
            </button>
        </div>

        
        <div class="content-grid" id="contentGrid">
            
        </div>

        
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <h3 class="empty-title">No hay informativos</h3>
            <p class="empty-text">Comienza agregando tu primer informativo haciendo clic en "Agregar Informativo"</p>
        </div>
    </div>

    
    <div id="informativoModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Agregar Informativo</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="informativoForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="informativoId" name="id">

                    <div class="form-group">
                        <label class="form-label" for="texto">
                            <i class="fas fa-align-left" style="color: #FFD700; margin-right: 5px;"></i>
                            Texto del Informativo
                        </label>
                        <textarea class="form-control" id="texto" name="texto" placeholder="Escribe aquí el contenido del informativo..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="imagen">
                            <i class="fas fa-image" style="color: #FFD700; margin-right: 5px;"></i>
                            Imagen (Opcional)
                        </label>
                        <div class="file-input-wrapper">
                            <input type="file" class="file-input" id="imagen" name="imagen" accept="image/*" onchange="updateFileName(this)">
                            <div class="file-input-display">
                                <i class="fas fa-cloud-upload-alt" style="color: #FFD700;"></i>
                                <span id="fileName">Seleccionar imagen...</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        <span id="submitText">Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Ver Informativo</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="viewContent">
                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeViewModal()">Cerrar</button>
            </div>
        </div>
    </div>

    <script>
        let isEditing = false;
        let currentId = null;
        const userRole = '<?php echo $_SESSION['cargo']; ?>';
        const isAdmin = userRole === 'admin';

        
        document.addEventListener('DOMContentLoaded', function() {
            loadInformativos();
        });

        
        function openModal(id = null) {
            const modal = document.getElementById('informativoModal');
            const form = document.getElementById('informativoForm');
            const title = document.getElementById('modalTitle');
            const submitText = document.getElementById('submitText');

            if (id) {
                isEditing = true;
                currentId = id;
                title.textContent = 'Editar Informativo';
                submitText.textContent = 'Actualizar';
                loadInformativoData(id);
            } else {
                isEditing = false;
                currentId = null;
                title.textContent = 'Agregar Informativo';
                submitText.textContent = 'Guardar';
                form.reset();
                document.getElementById('fileName').textContent = 'Seleccionar imagen...';
            }

            modal.style.display = 'block';
        }

        
        function closeModal() {
            document.getElementById('informativoModal').style.display = 'none';
        }

        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        
        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files.length > 0) {
                fileName.textContent = input.files[0].name;
            } else {
                fileName.textContent = 'Seleccionar imagen...';
            }
        }

        
        document.getElementById('informativoForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const url = isEditing ? '../../api/planeacion/get_cargar_informativo.php?action=update' : '../../api/planeacion/get_cargar_informativo.php?action=create';

            
            Swal.fire({
                title: isEditing ? 'Actualizando...' : 'Guardando...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: data.message,
                            confirmButtonColor: '#FFD700'
                        });
                        closeModal();
                        loadInformativos();
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
                        text: 'Ocurrió un error inesperado',
                        confirmButtonColor: '#FFD700'
                    });
                });
        });

        
        function loadInformativos() {
            fetch('../../api/planeacion/get_cargar_informativo.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('contentGrid');
                    const emptyState = document.getElementById('emptyState');
                    const totalCount = document.getElementById('totalCount');

                    if (data.success && data.data.length > 0) {
                        grid.innerHTML = '';
                        emptyState.style.display = 'none';
                        grid.style.display = 'grid';

                        totalCount.textContent = data.data.length;

                        data.data.forEach(item => {
                            const card = createInfoCard(item);
                            grid.appendChild(card);
                        });
                    } else {
                        grid.style.display = 'none';
                        emptyState.style.display = 'block';
                        totalCount.textContent = '0';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        
        function createInfoCard(item) {
            const card = document.createElement('div');
            card.className = 'info-card';

            const imageHtml = item.imagen ?
                `<img src="../../uploads/informativos/${item.imagen}" alt="Imagen" class="card-image">` :
                `<div class="card-image" style="display: flex; align-items: center; justify-content: center; background: #f8f9fa;">
                    <i class="fas fa-image" style="font-size: 3rem; color: #ddd;"></i>
                </div>`;

            const actionsHtml = isAdmin ?
                `<div class="card-actions">
                    <button class="btn-action btn-view" onclick="viewInformativo(${item.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                    <button class="btn-action btn-edit" onclick="openModal(${item.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn-action btn-delete" onclick="deleteInformativo(${item.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </div>` :
                `<div class="card-actions">
                    <button class="btn-action btn-view" onclick="viewInformativo(${item.id})">
                        <i class="fas fa-eye"></i> Ver
                    </button>
                </div>`;

            card.innerHTML = `
                ${imageHtml}
                <div class="card-content">
                    <div class="card-text">${item.texto.substring(0, 150)}${item.texto.length > 150 ? '...' : ''}</div>
                    <div class="card-meta">
                        <span><i class="fas fa-user"></i> ${item.usuario_nombre}</span>
                        <span><i class="fas fa-calendar"></i> ${formatDate(item.fecha_creacion)}</span>
                    </div>
                    ${actionsHtml}
                </div>
            `;

            return card;
        }

        
        function viewInformativo(id) {
            fetch(`../../api/planeacion/get_cargar_informativo.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        const viewContent = document.getElementById('viewContent');

                        const imageHtml = item.imagen ?
                            `<img src="../../uploads/informativos/${item.imagen}" alt="Imagen" style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 10px; margin-bottom: 1rem;">` : '';

                        viewContent.innerHTML = `
                            ${imageHtml}
                            <div style="margin-bottom: 1rem;">
                                <h4 style="color: #333; margin-bottom: 0.5rem;">Contenido:</h4>
                                <p style="line-height: 1.6; color: #555;">${item.texto}</p>
                            </div>
                            <div style="border-top: 1px solid #e0e0e0; padding-top: 1rem; display: flex; justify-content: space-between; font-size: 0.9rem; color: #666;">
                                <span><i class="fas fa-user"></i> ${item.usuario_nombre}</span>
                                <span><i class="fas fa-calendar"></i> ${formatDate(item.fecha_creacion)}</span>
                            </div>
                        `;

                        document.getElementById('viewModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        
        function loadInformativoData(id) {
            fetch(`../../api/planeacion/get_cargar_informativo.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const item = data.data;
                        document.getElementById('informativoId').value = item.id;
                        document.getElementById('texto').value = item.texto;
                        document.getElementById('fileName').textContent = item.imagen || 'Seleccionar imagen...';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        
        function deleteInformativo(id) {
            Swal.fire({
                title: '¿Eliminar informativo?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`../../api/planeacion/get_cargar_informativo.php?action=delete&id=${id}`, {
                            method: 'DELETE'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: '¡Eliminado!',
                                    text: data.message,
                                    confirmButtonColor: '#FFD700'
                                });
                                loadInformativos();
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
                        });
                }
            });
        }

        
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        
        window.onclick = function(event) {
            const modal = document.getElementById('informativoModal');
            const viewModal = document.getElementById('viewModal');
            if (event.target == modal) {
                closeModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>

</html>