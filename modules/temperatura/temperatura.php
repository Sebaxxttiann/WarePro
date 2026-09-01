<?php
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/header.php';

date_default_timezone_set('America/Bogota');

$mes_filtro = $_GET['mes'] ?? date('Y-m');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        try {
            $fecha = $_POST['fecha'];
            $hora = $_POST['hora'];
            $hora_personalizada = $_POST['hora_personalizada'] ?? '';
            $lugar = $_POST['lugar'];
            $temperatura = floatval($_POST['temperatura']);
            $nombre_persona = $_POST['nombre_persona'];

            if (empty($fecha) || empty($lugar) || empty($nombre_persona)) {
                throw new Exception('Todos los campos son obligatorios');
            }

            $hora_final = ($hora === 'otra') ? $hora_personalizada : $hora;

            if (empty($hora_final)) {
                throw new Exception('Debe seleccionar una hora o ingresar una personalizada');
            }

            if ($temperatura < -50 || $temperatura > 100) {
                throw new Exception('La temperatura debe estar entre -50°C y 100°C');
            }

            $stmt = $pdo->prepare("INSERT INTO temperaturas (fecha, hora, lugar, temperatura, nombre_persona, operacion_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$fecha, $hora_final, $lugar, $temperatura, $nombre_persona, getOperacionActiva()]);

            $mensaje = "Temperatura registrada exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    if ($accion === 'editar') {
        try {
            $id = intval($_POST['id']);
            $fecha = $_POST['fecha'];
            $hora = $_POST['hora'];
            $hora_personalizada = $_POST['hora_personalizada'] ?? '';
            $lugar = $_POST['lugar'];
            $temperatura = floatval($_POST['temperatura']);
            $nombre_persona = $_POST['nombre_persona'];

            if (empty($fecha) || empty($lugar) || empty($nombre_persona)) {
                throw new Exception('Todos los campos son obligatorios');
            }

            $hora_final = ($hora === 'otra') ? $hora_personalizada : $hora;

            if (empty($hora_final)) {
                throw new Exception('Debe seleccionar una hora o ingresar una personalizada');
            }

            if ($temperatura < -50 || $temperatura > 100) {
                throw new Exception('La temperatura debe estar entre -50°C y 100°C');
            }

            $stmt = $pdo->prepare("UPDATE temperaturas SET fecha = ?, hora = ?, lugar = ?, temperatura = ?, nombre_persona = ? WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$fecha, $hora_final, $lugar, $temperatura, $nombre_persona, $id, getOperacionActiva()]);

            $mensaje = "Temperatura actualizada exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }

    if ($accion === 'eliminar') {
        try {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("DELETE FROM temperaturas WHERE id = ? AND operacion_id = ?");
            $stmt->execute([$id, getOperacionActiva()]);

            $mensaje = "Registro eliminado exitosamente";
            $tipo_mensaje = "success";
        } catch (Exception $e) {
            $mensaje = "Error: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}

$user_cargo = $_SESSION['cargo'] ?? 'operador';

$stmt = $pdo->prepare("SELECT * FROM temperaturas WHERE DATE_FORMAT(fecha, '%Y-%m') = ? AND operacion_id = ? ORDER BY fecha ASC, hora ASC");
$stmt->execute([$mes_filtro, getOperacionActiva()]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

function calcularMTD($pdo, $lugar, $mes_filtro) {
    $stmt = $pdo->prepare("SELECT temperatura FROM temperaturas WHERE lugar = ? AND DATE_FORMAT(fecha, '%Y-%m') = ? AND operacion_id = ?");
    $stmt->execute([$lugar, $mes_filtro, getOperacionActiva()]);
    $temperaturas = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($temperaturas)) {
        return 0;
    }

    $suma_temperaturas = array_sum($temperaturas);
    $cantidad_registros = count($temperaturas);
    $promedio = $suma_temperaturas / $cantidad_registros;

    return round($promedio, 1);
}

function obtenerCantidadRegistros($pdo, $lugar, $mes_filtro) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM temperaturas WHERE lugar = ? AND DATE_FORMAT(fecha, '%Y-%m') = ? AND operacion_id = ?");
    $stmt->execute([$lugar, $mes_filtro, getOperacionActiva()]);
    return $stmt->fetchColumn();
}

$lugares = ['Bodega Inside', 'Bodega POSM', 'Carpa'];
$mtd_data = [];
$cantidad_registros = [];

foreach ($lugares as $lugar) {
    $mtd_data[$lugar] = calcularMTD($pdo, $lugar, $mes_filtro);
    $cantidad_registros[$lugar] = obtenerCantidadRegistros($pdo, $lugar, $mes_filtro);
}

$stmt = $pdo->prepare("SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') as mes FROM temperaturas WHERE operacion_id = ? ORDER BY mes DESC");
$stmt->execute([getOperacionActiva()]);
$meses_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);

$fecha_actual = date('Y-m-d');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

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
    }

    body {
        background: var(--pure-white);
        font-family: 'Poppins', sans-serif;
        color: var(--primary-black);
        line-height: 1.6;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        min-height: 100vh;
    }

    .main-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .page-header {
        background: var(--black-gradient);
        color: var(--pure-white);
        padding: 2.5rem;
        margin-bottom: 2rem;
        border-radius: 20px;
        box-shadow: var(--shadow-heavy);
        position: relative;
        overflow: hidden;
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .page-icon {
        background: var(--gold-gradient);
        color: var(--primary-black);
        padding: 1.2rem;
        border-radius: 16px;
        font-size: 2rem;
        box-shadow: var(--shadow-medium);
    }

    .page-title h1 {
        font-size: 3rem;
        font-weight: 800;
        color: var(--primary-gold);
        margin: 0;
    }

    .page-description {
        color: #cbd5e1;
        font-size: 1.2rem;
        margin-left: 5.7rem;
    }

    .mtd-dashboard {
        background: var(--pure-white);
        padding: 2rem;
        border-radius: 20px;
        box-shadow: var(--shadow-medium);
        border: 1px solid var(--border-gray);
        margin-bottom: 2rem;
        position: relative;
    }

    .mtd-dashboard::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gold-gradient);
    }

    .mtd-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
    }

    .mtd-title h2 {
        color: var(--primary-black);
        font-size: 1.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 0;
    }

    .month-filter {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .month-filter select {
        padding: 0.75rem 1rem;
        border: 2px solid var(--border-gray);
        border-radius: 8px;
        background: var(--light-gray);
        font-family: inherit;
        font-weight: 500;
        cursor: pointer;
    }

    .mtd-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .mtd-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 1.5rem;
        border-radius: 16px;
        border: 2px solid var(--border-gray);
        text-align: center;
        position: relative;
        transition: all 0.3s ease;
    }

    .mtd-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-medium);
    }

    .mtd-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--gold-gradient);
    }

    .mtd-percentage {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary-gold);
        margin-bottom: 0.5rem;
    }

    .main-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    .form-section, .records-section {
        background: var(--pure-white);
        padding: 2.5rem;
        border-radius: 20px;
        box-shadow: var(--shadow-medium);
        border: 1px solid var(--border-gray);
        position: relative;
    }

    .form-section::before, .records-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gold-gradient);
    }

    .form-title, .records-title {
        color: var(--primary-black);
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .form-group {
        margin-bottom: 1.8rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 600;
        color: var(--primary-black);
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-input, .form-select {
        width: 100%;
        padding: 1rem 1.25rem;
        border: 2px solid var(--border-gray);
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: var(--light-gray);
        font-family: inherit;
    }

    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: var(--primary-gold);
        background-color: var(--pure-white);
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
    }

    .hora-personalizada {
        margin-top: 0.75rem;
        display: none;
    }

    .btn {
        padding: 1rem 2rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
    }

    .btn-primary {
        background: var(--gold-gradient);
        color: var(--primary-black);
        box-shadow: var(--shadow-light);
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-medium);
    }

    .btn-secondary {
        background: var(--text-gray);
        color: var(--pure-white);
    }

    .btn-sm {
        padding: 0.6rem 1.2rem;
        font-size: 0.875rem;
        border-radius: 8px;
    }

    .btn-view { background: var(--success-green); color: var(--pure-white); }
    .btn-edit { background: var(--info-blue); color: var(--pure-white); }
    .btn-delete { background: var(--danger-red); color: var(--pure-white); }

    .dataTables_wrapper {
        margin-top: 1rem;
    }

    .dataTables_filter input {
        border: 2px solid var(--border-gray);
        border-radius: 8px;
        padding: 0.5rem 1rem;
        margin-left: 0.5rem;
    }

    .dataTables_length select {
        border: 2px solid var(--border-gray);
        border-radius: 8px;
        padding: 0.5rem;
        margin: 0 0.5rem;
    }

    .page-link {
        color: var(--primary-black);
        background-color: var(--light-gray);
        border: 1px solid var(--border-gray);
    }

    .page-link:hover {
        color: var(--primary-black);
        background-color: var(--primary-gold);
        border-color: var(--primary-gold);
    }

    .page-item.active .page-link {
        background-color: var(--primary-gold);
        border-color: var(--primary-gold);
        color: var(--primary-black);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        background: var(--pure-white);
        margin: 0;
    }

    .table th {
        background: var(--black-gradient);
        color: var(--primary-gold);
        padding: 1.25rem 1rem;
        text-align: left;
        font-weight: 700;
        font-size: 0.95rem;
        border: none;
    }

    .table td {
        padding: 1.25rem 1rem;
        border-bottom: 1px solid var(--border-gray);
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background-color: rgba(255, 215, 0, 0.05);
    }

    .temperature-value {
        font-weight: 800;
        font-size: 1.2rem;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .temp-normal {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success-green);
        border: 2px solid rgba(40, 167, 69, 0.2);
    }

    .temp-high {
        background: rgba(255, 193, 7, 0.1);
        color: #e6a800;
        border: 2px solid rgba(255, 193, 7, 0.2);
    }

    .temp-very-high {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger-red);
        border: 2px solid rgba(220, 53, 69, 0.2);
    }

    .actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--text-gray);
    }

    .empty-state i {
        font-size: 5rem;
        color: var(--border-gray);
        margin-bottom: 1.5rem;
        opacity: 0.5;
    }

    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--primary-black);
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
    }

    .modal-content {
        background-color: var(--pure-white);
        margin: 5% auto;
        padding: 2.5rem;
        border-radius: 20px;
        width: 90%;
        max-width: 600px;
        box-shadow: var(--shadow-heavy);
        position: relative;
        max-height: 80vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid var(--primary-gold);
    }

    .modal-title {
        color: var(--primary-black);
        font-size: 1.8rem;
        font-weight: 700;
    }

    .close {
        background: none;
        border: none;
        font-size: 1.8rem;
        cursor: pointer;
        color: var(--text-gray);
        padding: 0.5rem;
        border-radius: 50%;
        transition: all 0.3s ease;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close:hover {
        background-color: var(--light-gray);
        color: var(--danger-red);
    }

    @media (max-width: 1200px) {
        .main-content { grid-template-columns: 1fr; }
        .mtd-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    }

    @media (max-width: 768px) {
        .main-container { padding: 1rem; }
        .page-header { padding: 2rem; }
        .page-title { flex-direction: column; text-align: center; gap: 1rem; }
        .page-title h1 { font-size: 2.5rem; }
        .page-description { margin-left: 0; }
        .form-section, .records-section, .mtd-dashboard { padding: 1.5rem; }
        .mtd-title { flex-direction: column; align-items: stretch; gap: 1rem; }
        .actions { flex-direction: column; }
        input, select, textarea { font-size: 16px !important; }
    }
</style>

<div class="main-container">
    <div class="page-header">
        <div class="page-title">
            <div class="page-icon">
                <i class="fas fa-thermometer-half"></i>
            </div>
            <h1>Control de Temperaturas</h1>
        </div>
        <p class="page-description">Sistema profesional de monitoreo y registro de temperaturas en tiempo real</p>
    </div>

    <div class="mtd-dashboard">
        <div class="mtd-title">
            <h2>
                <i class="fas fa-chart-line"></i>
                Promedio Mensual de Temperaturas por Bodega
            </h2>
            <div class="month-filter">
                <label for="mes_filtro"><i class="fas fa-calendar"></i> Filtrar por mes:</label>
                <select id="mes_filtro" name="mes_filtro" onchange="filtrarPorMes()">
                    <?php if (empty($meses_disponibles)): ?>
                        <option value="<?php echo date('Y-m'); ?>"><?php echo date('Y-m'); ?></option>
                    <?php else: ?>
                        <?php foreach ($meses_disponibles as $mes): ?>
                            <option value="<?php echo $mes; ?>" <?php echo ($mes === $mes_filtro) ? 'selected' : ''; ?>>
                                <?php 
                                $fecha = DateTime::createFromFormat('Y-m', $mes);
                                echo $fecha->format('F Y');
                                ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        
        <div class="mtd-grid">
            <?php foreach ($lugares as $lugar): ?>
                <div class="mtd-card">
                    <h3><?php echo $lugar; ?></h3>
                    <div class="mtd-percentage"><?php echo $mtd_data[$lugar]; ?>°C</div>
                    <div class="mtd-label">Promedio mensual</div>
                    <div class="mtd-label" style="font-size: 0.8rem; margin-top: 0.5rem; color: var(--text-gray);">
                        <?php echo $cantidad_registros[$lugar]; ?> registro(s)
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="form-section">
            <h2 class="form-title">
                <i class="fas fa-plus-circle"></i>
                <span id="form-title-text">Nuevo Registro</span>
            </h2>

            <form id="temperaturaForm" method="POST">
                <input type="hidden" name="accion" id="accion" value="agregar">
                <input type="hidden" name="id" id="registro_id">

                <div class="form-group">
                    <label class="form-label" for="fecha">
                        <i class="fas fa-calendar-alt"></i> Fecha
                    </label>
                    <input type="date" id="fecha" name="fecha" class="form-input" value="<?php echo $fecha_actual; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="hora">
                        <i class="fas fa-clock"></i> Hora
                    </label>
                    <select id="hora" name="hora" class="form-select" required onchange="toggleHoraPersonalizada()">
                        <option value="">Seleccionar hora</option>
                        <option value="14:00">14:00 (2:00 PM)</option>
                        <option value="10:00">10:00 (10:00 AM)</option>
                        
                    </select>
                    <div class="hora-personalizada" id="hora-personalizada">
                        <input type="time" id="hora_personalizada" name="hora_personalizada" class="form-input">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="lugar">
                        <i class="fas fa-map-marker-alt"></i> Lugar
                    </label>
                    <select id="lugar" name="lugar" class="form-select" required>
                        <option value="">Seleccionar bodega</option>
                        <option value="Bodega Inside">Bodega Inside</option>
                        <option value="Bodega B">Bodega B</option>
                        <option value="Reempaque">Reempaque</option>
                        <option value="Bodega POSM">Bodega POSM</option>
                        <option value="Carpa">Carpa</option>
                    <option value="MKP">MKP</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="temperatura">
                        <i class="fas fa-thermometer-half"></i> Temperatura (°C)
                    </label>
                    <input type="number" id="temperatura" name="temperatura" class="form-input" step="0.1" min="-50" max="100" placeholder="Ej: 25.5" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nombre_persona">
                        <i class="fas fa-user"></i> Nombre de quien tomó la temperatura
                    </label>
                    <select id="nombre_persona" name="nombre_persona" class="form-select" required>
                        <option value="">Seleccionar persona</option>
                        <option value="Luis Ballesteros">Luis Ballesteros</option>
                        <option value="marlyn castellanos">Marlyn Castellanos</option>
                        <option value="Yarlin Quintero">Yarlin Quintero</option>
                        <option value="Yeison Jaimes">Yeison Jaimes</option>
                        <option value="Jesus Buitrago">Jesus Buitrago</option>

                    </select>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <span id="btn-text">Registrar Temperatura</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()" id="btn-cancel" style="display: none;">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

        <div class="records-section">
            <h2 class="records-title">
                <i class="fas fa-list-alt"></i>
                Registros de Temperaturas - <?php 
                $fecha_filtro = DateTime::createFromFormat('Y-m', $mes_filtro);
                echo $fecha_filtro->format('F Y'); 
                ?>
            </h2>

            <?php if (empty($registros)): ?>
                <div class="empty-state">
                    <i class="fas fa-thermometer-empty"></i>
                    <h3>No hay registros</h3>
                    <p>No se encontraron registros para <?php echo $fecha_filtro->format('F Y'); ?></p>
                </div>
            <?php else: ?>
                <table id="temperaturas-table" class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Fecha</th>
                            <th><i class="fas fa-clock"></i> Hora</th>
                            <th><i class="fas fa-map-marker-alt"></i> Lugar</th>
                            <th><i class="fas fa-thermometer-half"></i> Temperatura</th>
                            <th><i class="fas fa-user"></i> Registrado por</th>
                            <th><i class="fas fa-cogs"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $registro): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($registro['fecha'])); ?></td>
                                <td><?php echo $registro['hora']; ?></td>
                                <td><?php echo htmlspecialchars($registro['lugar']); ?></td>
                                <td>
                                    <span class="temperature-value <?php
                                        if ($registro['temperatura'] >= 37.5) echo 'temp-very-high';
                                        elseif ($registro['temperatura'] >= 37) echo 'temp-high';
                                        else echo 'temp-normal';
                                    ?>">
                                        <i class="fas fa-<?php
                                            if ($registro['temperatura'] >= 37.5) echo 'fire';
                                            elseif ($registro['temperatura'] >= 37) echo 'exclamation-triangle';
                                            else echo 'snowflake';
                                        ?>"></i>
                                        <?php echo number_format($registro['temperatura'], 1); ?>°C
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($registro['nombre_persona']); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn btn-view btn-sm" onclick="viewRecord(<?php echo $registro['id']; ?>)" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($user_cargo === 'admin'): ?>
                                        <button class="btn btn-edit btn-sm" onclick="editRecord(<?php echo $registro['id']; ?>)" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-delete btn-sm" onclick="deleteRecord(<?php echo $registro['id']; ?>)" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-info-circle"></i>
                Detalles del Registro
            </h2>
            <button class="close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div id="modalContent"></div>
    </div>
</div>

<script>
    <?php if (isset($mensaje)): ?>
        Swal.fire({
            title: '<?php echo $tipo_mensaje === "success" ? "¡Éxito!" : "Error"; ?>',
            text: '<?php echo addslashes($mensaje); ?>',
            icon: '<?php echo $tipo_mensaje === "success" ? "success" : "error"; ?>',
            confirmButtonColor: '#FFD700',
            confirmButtonText: 'Entendido'
        });
    <?php endif; ?>

    $(document).ready(function() {
        <?php if (!empty($registros)): ?>
        $('#temperaturas-table').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            responsive: true,
            order: [[0, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            columnDefs: [
                { targets: -1, orderable: false },
                { targets: [3], type: 'num', render: function(data) {
                    return data;
                }}
            ]
        });
        <?php endif; ?>
    });

    function filtrarPorMes() {
        const mesSeleccionado = document.getElementById('mes_filtro').value;
        window.location.href = `?mes=${mesSeleccionado}`;
    }

    function toggleHoraPersonalizada() {
        const select = document.getElementById('hora');
        const div = document.getElementById('hora-personalizada');
        const input = document.getElementById('hora_personalizada');

        if (select.value === 'otra') {
            div.style.display = 'block';
            input.required = true;
        } else {
            div.style.display = 'none';
            input.required = false;
            input.value = '';
        }
    }

    function resetForm() {
        document.getElementById('temperaturaForm').reset();
        document.getElementById('accion').value = 'agregar';
        document.getElementById('registro_id').value = '';
        document.getElementById('form-title-text').textContent = 'Nuevo Registro';
        document.getElementById('btn-text').textContent = 'Registrar Temperatura';
        document.getElementById('btn-cancel').style.display = 'none';
        document.getElementById('fecha').value = '<?php echo $fecha_actual; ?>';
        toggleHoraPersonalizada();
    }

    function viewRecord(id) {
        fetch(`../../api/temperatura/get_temperatura.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const registro = data.record;
                    const fecha = new Date(registro.fecha).toLocaleDateString('es-CO', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    let tempClass = 'temp-normal';
                    let tempIcon = 'fas fa-snowflake';
                    let tempStatus = 'Normal';

                    if (registro.temperatura >= 37.5) {
                        tempClass = 'temp-very-high';
                        tempIcon = 'fas fa-fire';
                        tempStatus = 'Muy Alta';
                    } else if (registro.temperatura >= 37) {
                        tempClass = 'temp-high';
                        tempIcon = 'fas fa-exclamation-triangle';
                        tempStatus = 'Alta';
                    }

                    document.getElementById('modalContent').innerHTML = `
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                            <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar-alt" style="color: var(--primary-gold);"></i>
                                    <strong>Fecha:</strong>
                                </div>
                                <span style="font-size: 1.1rem; color: var(--primary-black);">${fecha}</span>
                            </div>
                            <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 12px;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-clock" style="color: var(--primary-gold);"></i>
                                    <strong>Hora:</strong>
                                </div>
                                <span style="font-size: 1.1rem; color: var(--primary-black);">${registro.hora}</span>
                            </div>
                        </div>
                        
                        <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-map-marker-alt" style="color: var(--primary-gold);"></i>
                                <strong>Lugar:</strong>
                            </div>
                            <span style="font-size: 1.1rem; color: var(--primary-black);">${registro.lugar}</span>
                        </div>
                        
                        <div style="text-align: center; padding: 2rem; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 16px; margin-bottom: 2rem; border: 2px solid var(--border-gray);">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1rem;">
                                <i class="fas fa-thermometer-half" style="color: var(--primary-gold); font-size: 1.5rem;"></i>
                                <strong style="font-size: 1.2rem;">Temperatura Registrada:</strong>
                            </div>
                            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1rem;">
                                <i class="${tempIcon}" style="font-size: 3rem; color: ${tempClass === 'temp-very-high' ? 'var(--danger-red)' : tempClass === 'temp-high' ? '#e6a800' : 'var(--success-green)'};"></i>
                                <span class="temperature-value ${tempClass}" style="font-size: 3rem; padding: 1rem 2rem;">
                                    ${parseFloat(registro.temperatura).toFixed(1)}°C
                                </span>
                            </div>
                            <div style="background: rgba(255, 215, 0, 0.1); padding: 0.75rem 1.5rem; border-radius: 20px; display: inline-block;">
                                <strong style="color: var(--primary-black);">Estado: ${tempStatus}</strong>
                            </div>
                        </div>
                        
                        <div style="background: var(--light-gray); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-user" style="color: var(--primary-gold);"></i>
                                <strong>Registrado por:</strong>
                            </div>
                            <span style="font-size: 1.1rem; color: var(--primary-black);">${registro.nombre_persona}</span>
                        </div>
                        
                        <div style="text-align: center; padding: 1rem; background: rgba(108, 117, 125, 0.1); border-radius: 8px; font-size: 0.9rem; color: var(--text-gray);">
                            <i class="fas fa-info-circle"></i>
                            Registro creado el ${new Date(registro.fecha_creacion || registro.fecha).toLocaleString('es-CO')}
                        </div>
                    `;
                    document.getElementById('viewModal').style.display = 'block';
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo cargar el registro',
                        icon: 'error',
                        confirmButtonColor: '#FFD700'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo cargar el registro',
                    icon: 'error',
                    confirmButtonColor: '#FFD700'
                });
            });
    }

    function editRecord(id) {
        fetch(`../../api/temperatura/get_temperatura.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const registro = data.record;

                    document.getElementById('accion').value = 'editar';
                    document.getElementById('registro_id').value = registro.id;
                    document.getElementById('fecha').value = registro.fecha;

                    const horaSelect = document.getElementById('hora');
                    if (registro.hora === '14:00' || registro.hora === '10:00') {
                        horaSelect.value = registro.hora;
                    } else {
                        horaSelect.value = 'otra';
                        document.getElementById('hora_personalizada').value = registro.hora;
                    }
                    toggleHoraPersonalizada();

                    document.getElementById('lugar').value = registro.lugar;
                    document.getElementById('temperatura').value = registro.temperatura;
                    document.getElementById('nombre_persona').value = registro.nombre_persona;

                    document.getElementById('form-title-text').innerHTML = '<i class="fas fa-edit"></i> Editar Registro';
                    document.getElementById('btn-text').textContent = 'Actualizar Temperatura';
                    document.getElementById('btn-cancel').style.display = 'inline-flex';

                    document.querySelector('.form-section').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: data.message || 'No se pudo cargar el registro para editar',
                        icon: 'error',
                        confirmButtonColor: '#FFD700'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'No se pudo cargar el registro para editar',
                    icon: 'error',
                    confirmButtonColor: '#FFD700'
                });
            });
    }

    function deleteRecord(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción eliminará permanentemente el registro de temperatura',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
            cancelButtonText: '<i class="fas fa-times"></i> Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Eliminando...',
                    text: 'Por favor espera',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    window.onclick = function(event) {
        const viewModal = document.getElementById('viewModal');
        if (event.target === viewModal) {
            closeModal('viewModal');
        }
    }

    document.getElementById('temperatura').addEventListener('input', function() {
        const value = parseFloat(this.value);

        if (this.value && (value < -50 || value > 100)) {
            this.style.borderColor = 'var(--danger-red)';
            this.style.backgroundColor = 'rgba(220, 53, 69, 0.05)';
        } else if (this.value && value >= 37.5) {
            this.style.borderColor = 'var(--warning-yellow)';
            this.style.backgroundColor = 'rgba(255, 193, 7, 0.05)';
        } else if (this.value) {
            this.style.borderColor = 'var(--success-green)';
            this.style.backgroundColor = 'rgba(40, 167, 69, 0.05)';
        } else {
            this.style.borderColor = 'var(--border-gray)';
            this.style.backgroundColor = 'var(--light-gray)';
        }
    });

    document.getElementById('temperaturaForm').addEventListener('submit', function(e) {
        const temperatura = parseFloat(document.getElementById('temperatura').value);

        if (temperatura >= 40) {
            e.preventDefault();
            Swal.fire({
                title: 'Temperatura muy alta',
                text: `La temperatura registrada (${temperatura}°C) es extremadamente alta. ¿Estás seguro de que es correcta?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#FFD700',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, es correcta',
                cancelButtonText: 'Revisar'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.removeEventListener('submit', arguments.callee);
                    this.submit();
                }
            });
        }
    });
</script>