<?php require_once '../../core/header.php';
$user_cargo = $_SESSION['cargo'] ?? 'operador';

?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="main-container">
    <div class="content-header">
        <div class="header-left">
            <h1 class="page-title">
                <i class="fas fa-truck-loading"></i>
                OWS - Control de Cargue
            </h1>
            <p class="page-subtitle">Gestión y seguimiento de vehículos cargados</p>
        </div>
        <div class="header-right">
            <button class="btn-primary" onclick="openModal()">
                <i class="fas fa-plus"></i>
                Nuevo Registro
            </button>
        </div>
    </div>

    <div class="data-card">
        <div class="card-header">
            <h3><i class="fas fa-table"></i> Registros de Cargue</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="cargueTable" class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-calendar"></i> Fecha</th>
                            <th><i class="fas fa-clock"></i> Hora</th>
                            <th><i class="fas fa-clipboard-list"></i> Planeados</th>
                            <th><i class="fas fa-truck"></i> Cargados</th>
                            <th><i class="fas fa-layer-group"></i> Franja</th>
                            <th><i class="fas fa-user"></i> Usuario</th>
                            <?php if ($user_cargo === 'admin'): ?>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cargueModal" tabindex="-1" aria-labelledby="cargueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cargueModalLabel">
                    <i class="fas fa-plus-circle"></i>
                    <span id="modalTitle">Nuevo Registro de Cargue</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cargueForm">
                <div class="modal-body">
                    <input type="hidden" id="recordId" name="id">
                    
                    <div class="mb-3">
                        <label for="fecha" class="form-label">
                            <i class="fas fa-calendar-alt"></i>
                            Fecha
                        </label>
                        <input type="date" class="form-control" id="fecha" name="fecha" required>
                    </div>

                    <div class="mb-3">
                        <label for="hora" class="form-label">
                            <i class="fas fa-clock"></i>
                            Hora
                        </label>
                        <input type="time" class="form-control" id="hora" name="hora" required>
                    </div>

                    <div class="mb-3">
                        <label for="vehiculos_planeados" class="form-label">
                            <i class="fas fa-clipboard-list"></i>
                            Vehículos Planeados
                        </label>
                        <input type="number" class="form-control" id="vehiculos_planeados" name="vehiculos_planeados" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="vehiculos_cargados" class="form-label">
                            <i class="fas fa-truck"></i>
                            Vehículos Cargados
                        </label>
                        <input type="number" class="form-control" id="vehiculos_cargados" name="vehiculos_cargados" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label for="franja" class="form-label">
                            <i class="fas fa-layer-group"></i>
                            Franja
                        </label>
                        <input type="number" class="form-control" id="franja" name="franja" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span id="submitText">Guardar Registro</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
* {
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f8f9fa;
}

.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem;
    min-height: 100vh;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    background: white;
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 215, 0, 0.1);
}

.header-left {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.page-title i {
    color: #FFD700;
    font-size: 1.8rem;
}

.page-subtitle {
    color: #666;
    margin: 8px 0 0 0;
    font-size: 1rem;
    font-weight: 400;
}

.btn-primary {
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border: none;
    color: #1a1a1a !important;
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
    color: #1a1a1a !important;
}

.btn-secondary {
    background: #6c757d !important;
    border: none;
    color: white !important;
}

.data-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 215, 0, 0.1);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
}

.card-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 12px;
}

.card-header i {
    color: #FFD700;
}

.card-body {
    padding: 2rem;
}

#cargueTable {
    width: 100% !important;
}

#cargueTable thead th {
    background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
    color: white !important;
    font-weight: 600;
    padding: 18px 15px;
    border: none !important;
    font-size: 0.9rem;
    position: relative;
}

#cargueTable thead th:first-child {
    border-radius: 12px 0 0 0;
}

#cargueTable thead th:last-child {
    border-radius: 0 12px 0 0;
}

#cargueTable thead th i {
    color: #FFD700;
    margin-right: 8px;
}

#cargueTable tbody td {
    padding: 15px;
    color: #333;
    font-weight: 500;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
}

#cargueTable tbody tr:hover {
    background: rgba(255, 215, 0, 0.05) !important;
}

.action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-edit, .btn-delete {
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.85rem;
    font-weight: 500;
    color: white;
}

.btn-edit {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.btn-edit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-delete {
    background: linear-gradient(135deg, #dc3545, #c82333);
}

.btn-delete:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid rgba(255, 215, 0, 0.1);
    padding: 1.5rem 2rem;
}

.modal-title {
    font-weight: 600;
    color: #1a1a1a;
    display: flex;
    align-items: center;
    gap: 12px;
}

.modal-title i {
    color: #FFD700;
}

.modal-body {
    padding: 2rem;
}

.form-label {
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.form-label i {
    color: #FFD700;
    width: 16px;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #FFD700;
    box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
}

.modal-footer {
    background: #f8f9fa;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e9ecef;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.dataTables_wrapper .dataTables_filter input {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 8px 12px;
    margin-left: 8px;
}

.dataTables_wrapper .dataTables_filter input:focus {
    outline: none;
    border-color: #FFD700;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    border: none !important;
    border-radius: 8px !important;
    margin: 0 2px !important;
    padding: 8px 12px !important;
    background: #f8f9fa !important;
    color: #333 !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #FFD700 !important;
    color: #1a1a1a !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #FFD700 !important;
    color: #1a1a1a !important;
}

@media (max-width: 768px) {
    .content-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .main-container {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

<script>
let table;
let editingId = null;
let modalInstance;

$(document).ready(function() {
    modalInstance = new bootstrap.Modal(document.getElementById('cargueModal'));
    setColombianDateTime();
    initializeDataTable();
    loadData();
    $('#cargueForm').on('submit', handleFormSubmit);
    $('#cargueModal').on('hidden.bs.modal', resetForm);
});

function setColombianDateTime() {
    const now = new Date();
    const colombianTime = new Date(now.toLocaleString("en-US", {timeZone: "America/Bogota"}));
    
    const year = colombianTime.getFullYear();
    const month = String(colombianTime.getMonth() + 1).padStart(2, '0');
    const day = String(colombianTime.getDate()).padStart(2, '0');
    $('#fecha').val(`${year}-${month}-${day}`);
    
    const hours = String(colombianTime.getHours()).padStart(2, '0');
    const minutes = String(colombianTime.getMinutes()).padStart(2, '0');
    $('#hora').val(`${hours}:${minutes}`);
}

function initializeDataTable() {
    table = $('#cargueTable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        pageLength: 25,
        dom: 'frtip',
        columnDefs: [
            { targets: -1, orderable: false, searchable: false }
        ]
    });
}

function openModal() {
    editingId = null;
    $('#modalTitle').text('Nuevo Registro de Cargue');
    $('#submitText').text('Guardar Registro');
    modalInstance.show();
    setColombianDateTime();
}

function editRecord(id) {
    editingId = id;
    $('#modalTitle').text('Editar Registro de Cargue');
    $('#submitText').text('Actualizar Registro');
    
    $.ajax({
        url: '../../api/ows/ows_cargue_api.php',
        method: 'POST',
        data: { action: 'get', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#recordId').val(response.data.id);
                $('#fecha').val(response.data.fecha);
                $('#hora').val(response.data.hora);
                $('#vehiculos_planeados').val(response.data.vehiculos_planeados);
                $('#vehiculos_cargados').val(response.data.vehiculos_cargados);
                $('#franja').val(response.data.franja);
                modalInstance.show();
            } else {
                showError('Error al cargar los datos del registro');
            }
        },
        error: function() {
            showError('Error de conexión al cargar los datos');
        }
    });
}

function deleteRecord(id) {
    iziToast.question({
        timeout: 20000,
        close: false,
        overlay: true,
        displayMode: 'once',
        id: 'question',
        zindex: 999,
        title: '¿Confirmar eliminación?',
        message: '¿Estás seguro de que deseas eliminar este registro?',
        position: 'center',
        buttons: [
            ['<button class="btn btn-danger"><b>Sí, eliminar</b></button>', function (instance, toast) {
                instance.hide({ transitionOut: 'fadeOut' }, toast, 'confirm');
                
                $.ajax({
                    url: '../../api/ows/ows_cargue_api.php',
                    method: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showSuccess('Registro eliminado correctamente');
                            loadData();
                        } else {
                            showError(response.message || 'Error al eliminar el registro');
                        }
                    },
                    error: function() {
                        showError('Error de conexión al eliminar el registro');
                    }
                });
            }, true],
            ['<button class="btn btn-secondary">Cancelar</button>', function (instance, toast) {
                instance.hide({ transitionOut: 'fadeOut' }, toast, 'cancel');
            }]
        ]
    });
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', editingId ? 'update' : 'create');
    
    if (editingId) {
        formData.append('id', editingId);
    }
    
    $.ajax({
        url: '../../api/ows/ows_cargue_api.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(editingId ? 'Registro actualizado correctamente' : 'Registro creado correctamente');
                modalInstance.hide();
                loadData();
            } else {
                showError(response.message || 'Error al procesar el registro');
            }
        },
        error: function() {
            showError('Error de conexión al procesar el registro');
        }
    });
}

function loadData() {
    $.ajax({
        url: '../../api/ows/ows_cargue_api.php',
        method: 'POST',
        data: { action: 'get_all' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateTable(response.data);
            } else {
                showError('Error al cargar los datos: ' + (response.message || 'Error desconocido'));
            }
        },
        error: function(xhr, status, error) {
            showError('Error de conexión al cargar los datos. Revisa la consola para más detalles.');
        }
    });
}

function updateTable(data) {
    table.clear();
    
    if (data && data.length > 0) {
        data.forEach(function(row) {
            const actions = `
                <div class="action-buttons">
                    <button class="btn-edit" onclick="editRecord(${row.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-delete" onclick="deleteRecord(${row.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            
            table.row.add([
                row.id,
                formatDate(row.fecha),
                row.hora,
                row.vehiculos_planeados || 0,
                row.vehiculos_cargados,
                row.franja,
                row.usuario_nombre || 'Sin asignar',
                actions
            ]);
        });
    }
    
    table.draw();
}

function formatDate(dateString) {
    if (!dateString) return '';
    const parts = dateString.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateString;
}

function resetForm() {
    $('#cargueForm')[0].reset();
    $('#recordId').val('');
    editingId = null;
    setColombianDateTime();
}

function showSuccess(message) {
    iziToast.success({
        title: 'Éxito',
        message: message,
        position: 'topRight',
        timeout: 4000,
        progressBar: true,
        transitionIn: 'bounceInLeft',
        transitionOut: 'fadeOutRight'
    });
}

function showError(message) {
    iziToast.error({
        title: 'Error',
        message: message,
        position: 'topRight',
        timeout: 5000,
        progressBar: true,
        transitionIn: 'bounceInLeft',
        transitionOut: 'fadeOutRight'
    });
}
</script>