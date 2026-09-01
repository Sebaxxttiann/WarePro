<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';


$stmt = $pdo->prepare("SELECT * FROM temperatura_au WHERE operacion_id = ? ORDER BY fecha DESC, hora DESC");
$stmt->execute([getOperacionActiva()]);
$registros = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Temperatura - WARE PRO</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            color: #333;
            line-height: 1.6;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border: 3px solid #FFD700;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 1rem;
        }

        .page-title i {
            font-size: 2rem;
            color: #FFD700;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
        }

        .page-subtitle {
            color: #ccc;
            font-size: 1rem;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #FFD700;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3);
        }

        .search-box {
            position: relative;
            max-width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
            border: 1px solid #e0e0e0;
            -webkit-overflow-scrolling: touch;
        }

        .table-wrapper {
            min-width: 800px;
        }

        .table-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: white;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 60px 120px 120px 150px 120px 180px 120px;
            gap: 1rem;
            align-items: center;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .table-row {
            display: grid;
            grid-template-columns: 60px 120px 120px 150px 120px 180px 120px;
            gap: 1rem;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .table-row:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .row-number {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            color: #1a1a1a;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .fecha-badge {
            background: #f8f9fa;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #e0e0e0;
        }

        .hora-badge {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .lugar-badge {
            background: linear-gradient(135deg, #fff3e0 0%, #ffcc02 100%);
            color: #e65100;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .temperatura-badge {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .persona-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .persona-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .actions-cell {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-view {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn-view:hover {
            background: #1976d2;
            color: white;
            transform: scale(1.1);
        }

        .btn-edit {
            background: #fff3e0;
            color: #f57c00;
        }

        .btn-edit:hover {
            background: #f57c00;
            color: white;
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal-content {
            background: white;
            margin: 20px auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
            position: relative;
            z-index: 10000;
            max-height: calc(100vh - 40px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
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
            flex-shrink: 0;
        }

        .modal-header h2 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-header i {
            color: #FFD700;
        }

        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: #ccc;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10001;
        }

        .close:hover {
            color: #FFD700;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 2rem;
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px !important;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
        }

        .custom-hora-input {
            display: none;
            margin-top: 10px;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        
        .swal2-container {
            z-index: 10002 !important;
        }

        
        @media (max-width: 768px) {
            html, body {
                overflow-y: scroll !important;
                height: auto !important;
                min-height: 100vh;
            }

            .container {
                padding: 1rem;
            }

            .actions-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .search-box {
                max-width: 100%;
            }

            .modal {
                padding: 10px;
            }

            .modal-content {
                margin: 10px auto;
                width: calc(100% - 20px);
                max-height: calc(100vh - 20px);
            }

            .modal-header {
                padding: 1.5rem;
            }

            .modal-body {
                padding: 1.5rem;
            }

            .modal-footer {
                padding: 1rem 1.5rem;
                flex-direction: column-reverse;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-title {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .page-title h1 {
                font-size: 1.5rem;
            }

            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .table-wrapper {
                min-width: 900px;
            }

            .table-header,
            .table-row {
                grid-template-columns: 50px 100px 100px 130px 100px 160px 100px;
                gap: 0.5rem;
                padding: 1rem 0.5rem;
                font-size: 0.8rem;
            }

            .persona-info {
                gap: 5px;
            }

            .persona-avatar {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .actions-cell {
                gap: 4px;
            }

            .btn-action {
                width: 28px;
                height: 28px;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .table-header,
            .table-row {
                grid-template-columns: 40px 90px 90px 120px 90px 140px 90px;
                gap: 0.25rem;
                padding: 0.75rem 0.25rem;
                font-size: 0.75rem;
            }

            .row-number {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .fecha-badge,
            .hora-badge,
            .lugar-badge,
            .temperatura-badge {
                padding: 4px 8px;
                font-size: 0.75rem;
            }

            .modal-content {
                border-radius: 15px;
            }
        }

        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #FFA500;
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-thermometer-half"></i>
                <div>
                    <h1>Control de Temperatura</h1>
                    <p class="page-subtitle">Registro y monitoreo de temperaturas por ubicación</p>
                </div>
            </div>
        </div>

        
        <div class="actions-bar">
            <button class="btn-primary" onclick="abrirModal()">
                <i class="fas fa-plus"></i>
                Nuevo Registro
            </button>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Buscar registros..." onkeyup="filtrarTabla()">
                <i class="fas fa-search"></i>
            </div>
        </div>

        
        <div class="table-container">
            <div class="table-header">
                <div>#</div>
                <div>Fecha</div>
                <div>Hora</div>
                <div>Lugar</div>
                <div>Temperatura</div>
                <div>Persona</div>
                <div>Acciones</div>
            </div>

            <div id="tableBody">
                <?php if (empty($registros)): ?>
                    <div class="empty-state">
                        <i class="fas fa-thermometer-empty"></i>
                        <h3>No hay registros</h3>
                        <p>Comienza agregando tu primer registro de temperatura</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($registros as $index => $registro): ?>
                        <div class="table-row" data-id="<?= $registro['id'] ?>">
                            <div class="row-number"><?= $index + 1 ?></div>
                            <div>
                                <span class="fecha-badge">
                                    <?= date('d/m/Y', strtotime($registro['fecha'])) ?>
                                </span>
                            </div>
                            <div>
                                <span class="hora-badge">
                                    <?= htmlspecialchars($registro['hora']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="lugar-badge">
                                    <?= htmlspecialchars($registro['lugar']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="temperatura-badge">
                                    <?= number_format($registro['temperatura'], 1) ?>°C
                                </span>
                            </div>
                            <div class="persona-info">
                                <div class="persona-avatar">
                                    <?= strtoupper(substr($registro['persona'], 0, 1)) ?>
                                </div>
                                <span><?= htmlspecialchars($registro['persona']) ?></span>
                            </div>
                            <div class="actions-cell">
                                <button class="btn-action btn-view" onclick="verRegistro(<?= $registro['id'] ?>)" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn-action btn-edit" onclick="editarRegistro(<?= $registro['id'] ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?= $registro['id'] ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    
    <div id="temperaturaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-thermometer-half"></i> <span id="modalTitle">Nuevo Registro</span></h2>
                <span class="close" onclick="cerrarModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="temperaturaForm">
                    <input type="hidden" id="registroId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha">Fecha *</label>
                            <input type="date" id="fecha" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="hora">Hora *</label>
                            <select id="hora" class="form-control" required onchange="toggleCustomHora()">
                                <option value="">Seleccionar hora</option>
                                <option value="10:00 AM">10:00 AM</option>
                                <option value="14:00 PM">14:00 PM</option>
                                <option value="otro">Otro</option>
                            </select>
                            <input type="text" id="horaCustom" class="form-control custom-hora-input" placeholder="Ingrese la hora (ej: 15:30)">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lugar">Lugar *</label>
                            <select id="lugar" class="form-control" required>
                                <option value="">Seleccionar lugar</option>
                                <option value="bodega b">Bodega B</option>
                                <option value="bodega e">Bodega E</option>
                                <option value="reempaque">Reempaque</option>
                                <option value="carpa">Carpa</option>
                                <option value="bodega posm">Bodega POSM</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="temperatura">Temperatura (°C) *</label>
                            <input type="number" id="temperatura" class="form-control" step="0.1" placeholder="25.5" required>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="persona">Persona que tomó la temperatura *</label>
                        <input type="text" id="persona" class="form-control" placeholder="Nombre completo" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button type="button" class="btn-primary" onclick="guardarRegistro()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <script>
        let editandoId = null;

        function abrirModal(id = null) {
            editandoId = id;
            const modal = document.getElementById('temperaturaModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('temperaturaForm');
            
            if (id) {
                modalTitle.textContent = 'Editar Registro';
                cargarDatosRegistro(id);
            } else {
                modalTitle.textContent = 'Nuevo Registro';
                form.reset();
                document.getElementById('registroId').value = '';
                document.getElementById('horaCustom').style.display = 'none';
                
                const hoy = new Date();
                document.getElementById('fecha').value = hoy.toISOString().split('T')[0];
            }
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function cerrarModal() {
            const modal = document.getElementById('temperaturaModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            editandoId = null;
        }

        function toggleCustomHora() {
            const horaSelect = document.getElementById('hora');
            const horaCustom = document.getElementById('horaCustom');
            
            if (horaSelect.value === 'otro') {
                horaCustom.style.display = 'block';
                horaCustom.required = true;
            } else {
                horaCustom.style.display = 'none';
                horaCustom.required = false;
                horaCustom.value = '';
            }
        }

        function cargarDatosRegistro(id) {
            const formData = new FormData();
            formData.append('accion', 'obtener');
            formData.append('id', id);

            fetch('../../api/temperatura/get_temperatura_au.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const registro = data.data;
                    document.getElementById('registroId').value = registro.id;
                    document.getElementById('fecha').value = registro.fecha;
                    
                    
                    const horaSelect = document.getElementById('hora');
                    const horaCustom = document.getElementById('horaCustom');
                    
                    if (['10:00 AM', '14:00 PM'].includes(registro.hora)) {
                        horaSelect.value = registro.hora;
                        horaCustom.style.display = 'none';
                    } else {
                        horaSelect.value = 'otro';
                        horaCustom.style.display = 'block';
                        horaCustom.value = registro.hora;
                    }
                    
                    document.getElementById('lugar').value = registro.lugar;
                    document.getElementById('temperatura').value = registro.temperatura;
                    document.getElementById('persona').value = registro.persona;
                } else {
                    mostrarError('Error al cargar los datos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos del registro');
            });
        }

        function guardarRegistro() {
            const form = document.getElementById('temperaturaForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData();
            const registroId = document.getElementById('registroId').value;
            
            
            const horaSelect = document.getElementById('hora').value;
            const horaFinal = horaSelect === 'otro' ? document.getElementById('horaCustom').value : horaSelect;
            
            if (horaSelect === 'otro' && !document.getElementById('horaCustom').value.trim()) {
                mostrarError('Por favor ingrese la hora personalizada');
                return;
            }
            
            formData.append('accion', registroId ? 'editar' : 'crear');
            if (registroId) formData.append('id', registroId);
            formData.append('fecha', document.getElementById('fecha').value);
            formData.append('hora', horaFinal);
            formData.append('lugar', document.getElementById('lugar').value);
            formData.append('temperatura', document.getElementById('temperatura').value);
            formData.append('persona', document.getElementById('persona').value);

            
            cerrarModal();

            
            Swal.fire({
                title: 'Guardando...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../../api/temperatura/get_temperatura_au.php', {
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
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    mostrarError(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al guardar el registro');
            });
        }

        function verRegistro(id) {
            const formData = new FormData();
            formData.append('accion', 'obtener');
            formData.append('id', id);

            fetch('../../api/temperatura/get_temperatura_au.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const registro = data.data;
                    Swal.fire({
                        title: 'Detalles del Registro',
                        html: `
                            <div style="text-align: left; padding: 1rem; background: #f8f9fa; border-radius: 10px; margin: 1rem 0;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div>
                                        <p style="margin: 0.5rem 0;"><strong style="color: #1a1a1a;">📅 Fecha:</strong> <span style="color: #666;">${new Date(registro.fecha).toLocaleDateString('es-ES')}</span></p>
                                        <p style="margin: 0.5rem 0;"><strong style="color: #1a1a1a;">🕒 Hora:</strong> <span style="color: #666;">${registro.hora}</span></p>
                                        <p style="margin: 0.5rem 0;"><strong style="color: #1a1a1a;">📍 Lugar:</strong> <span style="color: #666; text-transform: uppercase;">${registro.lugar}</span></p>
                                    </div>
                                    <div>
                                        <p style="margin: 0.5rem 0;"><strong style="color: #1a1a1a;">🌡️ Temperatura:</strong> <span style="color: #1976d2; font-weight: 600;">${parseFloat(registro.temperatura).toFixed(1)}°C</span></p>
                                        <p style="margin: 0.5rem 0;"><strong style="color: #1a1a1a;">👤 Persona:</strong> <span style="color: #666;">${registro.persona}</span></p>
                                        <p style="margin: 0.5rem 0;"><strong style="color: #1a1a1a;">📝 Registrado:</strong> <span style="color: #666; font-size: 0.9rem;">${new Date(registro.fecha_creacion).toLocaleString('es-ES')}</span></p>
                                    </div>
                                </div>
                            </div>
                        `,
                        confirmButtonColor: '#FFD700',
                        confirmButtonText: 'Cerrar',
                        width: '600px'
                    });
                } else {
                    mostrarError('Error al cargar los datos: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar los datos del registro');
            });
        }

        function editarRegistro(id) {
            abrirModal(id);
        }

        function eliminarRegistro(id) {
            Swal.fire({
                title: '¿Eliminar registro?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('accion', 'eliminar');
                    formData.append('id', id);

                    
                    Swal.fire({
                        title: 'Eliminando...',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch('../../api/temperatura/get_temperatura_au.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: data.message,
                                confirmButtonColor: '#FFD700'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            mostrarError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        mostrarError('Error al eliminar el registro');
                    });
                }
            });
        }

        function mostrarError(mensaje) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: mensaje,
                confirmButtonColor: '#FFD700'
            });
        }

        function filtrarTabla() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.table-row');

            rows.forEach(row => {
                if (row.classList.contains('empty-state')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        }

        
        window.onclick = function(event) {
            const modal = document.getElementById('temperaturaModal');
            if (event.target === modal) {
                cerrarModal();
            }
        }

        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                cerrarModal();
            }
        });

        
        document.getElementById('temperatura').addEventListener('input', function() {
            const valor = parseFloat(this.value);
            if (valor && (valor < -50 || valor > 100)) {
                this.style.borderColor = '#d32f2f';
                this.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
            } else {
                this.style.borderColor = '#e0e0e0';
                this.style.boxShadow = 'none';
            }
        });

        
        document.getElementById('horaCustom').addEventListener('input', function() {
            const valor = this.value;
            const formatoHora = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]( (AM|PM))?$/i;
            
            if (valor && !formatoHora.test(valor)) {
                this.style.borderColor = '#d32f2f';
                this.style.boxShadow = '0 0 0 3px rgba(211, 47, 47, 0.1)';
            } else {
                this.style.borderColor = '#e0e0e0';
                this.style.boxShadow = 'none';
            }
        });

        
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.table-row');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    row.style.transition = 'all 0.5s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
