<?php
require_once '../../core/config.php';
verificarLogin();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_visual') {
    header('Content-Type: application/json');
    
    $fecha_rango = $_POST['fecha'] ?? '';
    $busqueda = $_POST['busqueda'] ?? ''; 
    $cargo = $_POST['cargo'] ?? '';

    
    $sql = "SELECT d.*, u.nombre, u.cargo, u.cedula
            FROM descansos d
            JOIN usuarios u ON d.usuario_id = u.id
            WHERE d.operacion_id = ?";
    $params = [getOperacionActiva()];

    
    if (!empty($fecha_rango)) {
        if (strpos($fecha_rango, ' a ') !== false) {
            $fechas = explode(' a ', $fecha_rango);
            $sql .= " AND DATE(d.hora_inicio) BETWEEN ? AND ?";
            $params[] = $fechas[0];
            $params[] = $fechas[1];
        } else {
            
            $sql .= " AND DATE(d.hora_inicio) = ?";
            $params[] = $fecha_rango;
        }
    }

    
    if (!empty($busqueda)) {
        $sql .= " AND (u.nombre LIKE ? OR u.cedula LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }

    
    if (!empty($cargo)) {
        $sql .= " AND u.cargo = ?";
        $params[] = $cargo;
    }

    $sql .= " ORDER BY d.hora_inicio DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $resultados]);
    exit;
}


$stmtCargos = $pdo->query("SELECT DISTINCT cargo FROM usuarios WHERE cargo IS NOT NULL AND cargo != ''");
$listaCargos = $stmtCargos->fetchAll(PDO::FETCH_COLUMN);

include '../../core/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Descansos - Ware Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f8fafc; min-height: 100vh; color: #333; }
        .page-container { max-width: 1300px; margin: 0 auto; padding: 40px 20px; }
        
        
        .back-button { 
            display: inline-flex; align-items: center; gap: 8px; 
            background: #ffffff; color: #1e3c72; 
            padding: 10px 20px; border-radius: 50px; 
            text-decoration: none; font-weight: 600; font-size: 0.95rem;
            border: 2px solid #1e3c72; transition: all 0.3s ease; 
            margin-bottom: 30px; 
        }
        .back-button:hover { background: #1e3c72; color: white; transform: translateX(-5px); }

        
        .page-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end;}
        .page-header h2 { font-size: 2.2rem; color: #1e3c72; font-weight: 700; display: flex; align-items: center; gap: 15px;}
        .page-header h2 i { color: #11998e; }
        
        
        .filters-container {
            background: white; border-radius: 20px; padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03); margin-bottom: 30px;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;
        }

        .filter-group { position: relative; }
        .filter-group i { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.1rem;}
        
        .modern-input {
            width: 100%; padding: 15px 15px 15px 45px;
            border: 2px solid #e2e8f0; border-radius: 12px;
            font-family: 'Poppins', sans-serif; font-size: 0.95rem; color: #475569;
            transition: all 0.3s ease; background: #f8fafc; appearance: none;
        }
        .modern-input:focus { border-color: #11998e; background: white; outline: none; box-shadow: 0 0 0 4px rgba(17, 153, 142, 0.1); }
        .modern-input::placeholder { color: #94a3b8; }

        
        .table-wrapper {
            background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            overflow: hidden; 
        }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f1f5f9; color: #475569; font-weight: 600; padding: 20px; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 20px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .user-info { display: flex; flex-direction: column; }
        .user-name { font-weight: 600; color: #0f172a; }
        .user-cargo { font-size: 0.85rem; color: #64748b; }

        
        .badge { padding: 6px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        
        .status-ok { background: #dcfce7; color: #166534; } 
        .status-danger { background: #fee2e2; color: #991b1b; } 
        .status-warning { background: #fef3c7; color: #92400e; } 

        
        .text-excedido { color: #dc2626 !important; font-weight: 700; }
        
        
        .loader { text-align: center; padding: 40px; color: #94a3b8; display: none; }
        .loader i { font-size: 2rem; animation: spin 1s linear infinite; margin-bottom: 10px; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="page-container">
    <a href="people.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Volver a People
    </a>

    <div class="page-header">
        <h2><i class="fas fa-chart-pie"></i> Visual de Descansos</h2>
    </div>

    <div class="filters-container">
        <div class="filter-group">
            <i class="fas fa-calendar-alt"></i>
            <input type="text" id="filtro-fecha" class="modern-input" placeholder="Rango de Fechas (Clic para elegir)">
        </div>

        <div class="filter-group">
            <i class="fas fa-search"></i>
            <input type="text" id="filtro-busqueda" class="modern-input" placeholder="Buscar por Nombre o Cédula...">
        </div>

        <div class="filter-group">
            <i class="fas fa-briefcase"></i>
            <select id="filtro-cargo" class="modern-input">
                <option value="">Todos los cargos</option>
                <?php foreach($listaCargos as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Colaborador</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Duración</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tabla-body">
                </tbody>
        </table>
        <div class="loader" id="loader">
            <i class="fas fa-circle-notch"></i>
            <p>Cargando datos...</p>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        
        flatpickr("#filtro-fecha", {
            mode: "range",
            locale: "es", 
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y", 
            onChange: function(selectedDates, dateStr, instance) {
                
                if(selectedDates.length === 1 || selectedDates.length === 2) {
                    cargarDatos();
                }
            }
        });

        
        document.getElementById('filtro-busqueda').addEventListener('input', debounce(cargarDatos, 500));
        document.getElementById('filtro-cargo').addEventListener('change', cargarDatos);

        
        cargarDatos();

        
        function cargarDatos() {
            const tbody = document.getElementById('tabla-body');
            const loader = document.getElementById('loader');
            
            tbody.innerHTML = '';
            loader.style.display = 'block';

            const fd = new FormData();
            fd.append('action', 'get_visual');
            fd.append('fecha', document.getElementById('filtro-fecha').value);
            fd.append('busqueda', document.getElementById('filtro-busqueda').value);
            fd.append('cargo', document.getElementById('filtro-cargo').value);

            fetch('descanso_visual.php', { method: 'POST', body: fd })
            .then(res => res.json())
            .then(data => {
                loader.style.display = 'none';
                
                if(!data.success || data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color:#64748b;">No se encontraron registros.</td></tr>';
                    return;
                }

                data.data.forEach(row => {
                    let tr = document.createElement('tr');
                    
                    
                    let horaInicio = new Date(row.hora_inicio.replace(/-/g, '/')).toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'});
                    let horaFin = row.hora_fin ? new Date(row.hora_fin.replace(/-/g, '/')).toLocaleTimeString('es-ES', {hour: '2-digit', minute:'2-digit'}) : '--:--';
                    
                    
                    let duracionDisplay = '';
                    let estadoHtml = '';
                    let claseDuracion = '';

                    if (row.estado === 'activo') {
                        
                        duracionDisplay = '<span style="color:#94a3b8;">En proceso...</span>';
                        estadoHtml = '<span class="badge status-warning"><i class="fas fa-exclamation-triangle"></i> Sin cerrar</span>';
                    } else {
                        
                        let minutos = parseInt(row.duracion_minutos);
                        duracionDisplay = `${minutos} min`;
                        
                        
                        if (minutos > 35) {
                            claseDuracion = 'text-excedido';
                            estadoHtml = '<span class="badge status-danger"><i class="fas fa-clock"></i> Excedido</span>';
                        } else {
                            estadoHtml = '<span class="badge status-ok"><i class="fas fa-check-circle"></i> Correcto</span>';
                        }
                    }

                    tr.innerHTML = `
                        <td>
                            <div class="user-info">
                                <span class="user-name">${row.nombre}</span>
                                <span class="user-cargo">${row.cargo} | CC: ${row.cedula}</span>
                            </div>
                        </td>
                        <td><i class="far fa-clock" style="color:#94a3b8; margin-right:5px;"></i> ${horaInicio}</td>
                        <td>${horaFin !== '--:--' ? `<i class="far fa-check-circle" style="color:#11998e; margin-right:5px;"></i> ${horaFin}` : '--:--'}</td>
                        <td class="${claseDuracion}" style="font-size: 1.1rem;">${duracionDisplay}</td>
                        <td>${estadoHtml}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(err => {
                loader.style.display = 'none';
                console.error("Error cargando visual:", err);
            });
        }

        // Función para no saturar el servidor al teclear rápido
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => { clearTimeout(timeout); func(...args); };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    });
</script>

</body>
</html>