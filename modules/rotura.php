<?php
date_default_timezone_set('America/Bogota');
require_once '../core/config.php';

verificarLogin();

$current_user = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : '';

try {
    $stmt = $pdo->prepare("SELECT id, nombre, cargo FROM usuarios WHERE activo = 1 AND operacion_id = ? ORDER BY nombre ASC");
    $stmt->execute([getOperacionActiva()]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}

$operadores = array_values(array_filter($usuarios, fn($u) => strtolower($u['cargo']) === 'operador'));

if ($_POST) {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'agregar') {
            $imagen_path = null;
            if (isset($_FILES['registro_fotografico']) && $_FILES['registro_fotografico']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/roturas/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['registro_fotografico']['name'], PATHINFO_EXTENSION);
                $filename = 'rotura_' . date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
                $imagen_path = $upload_dir . $filename;

                if (!move_uploaded_file($_FILES['registro_fotografico']['tmp_name'], $imagen_path)) {
                    $imagen_path = null;
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO roturas (
                    supervisor_turno, turno, persona_rotura, placa_montacarga, placa_camion, canal, 
                    cargo_persona, tipo_producto, codigo_producto, descripcion_material, unidades, 
                    zona, casual, precio_rotura, registro_fotografico, observaciones, 
                    primer_porque, segundo_porque, tercer_porque, operacion_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_POST['supervisor_turno'],
                $_POST['turno'],
                $_POST['persona_rotura'],
                $_POST['placa_montacarga'] ?: null,
                $_POST['placa_camion'] ?: null,
                $_POST['canal'] ?: null,
                $_POST['cargo_persona'],
                $_POST['tipo_producto'],
                $_POST['codigo_producto'],
                $_POST['descripcion_material'],
                $_POST['unidades'],
                $_POST['zona'],
                $_POST['casual'],
                $_POST['precio_rotura'],
                $imagen_path,
                $_POST['observaciones'] ?: null,
                $_POST['primer_porque'],
                $_POST['segundo_porque'],
                $_POST['tercer_porque'],
                getOperacionActiva()
            ]);

            $mensaje = "Rotura registrada exitosamente";
            $tipo_mensaje = "success";
        } elseif ($accion === 'editar') {
            $imagen_path = $_POST['imagen_actual'] ?? null;
            if (isset($_FILES['registro_fotografico']) && $_FILES['registro_fotografico']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/roturas/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['registro_fotografico']['name'], PATHINFO_EXTENSION);
                $filename = 'rotura_' . date('YmdHis') . '_' . uniqid() . '.' . $file_extension;
                $nueva_imagen = $upload_dir . $filename;

                if (move_uploaded_file($_FILES['registro_fotografico']['tmp_name'], $nueva_imagen)) {
                    if ($imagen_path && file_exists($imagen_path)) {
                        unlink($imagen_path);
                    }
                    $imagen_path = $nueva_imagen;
                }
            }

            $stmt = $pdo->prepare("
                UPDATE roturas SET 
                    supervisor_turno = ?, turno = ?, persona_rotura = ?, 
                    placa_montacarga = ?, placa_camion = ?, canal = ?, cargo_persona = ?, tipo_producto = ?,
                    codigo_producto = ?, descripcion_material = ?, unidades = ?, zona = ?, casual = ?,
                    precio_rotura = ?, registro_fotografico = ?, observaciones = ?, primer_porque = ?, 
                    segundo_porque = ?, tercer_porque = ?
                WHERE id = ? AND operacion_id = ?
            ");

            $stmt->execute([
                $_POST['supervisor_turno'],
                $_POST['turno'],
                $_POST['persona_rotura'],
                $_POST['placa_montacarga'] ?: null,
                $_POST['placa_camion'] ?: null,
                $_POST['canal'] ?: null,
                $_POST['cargo_persona'],
                $_POST['tipo_producto'],
                $_POST['codigo_producto'],
                $_POST['descripcion_material'],
                $_POST['unidades'],
                $_POST['zona'],
                $_POST['casual'],
                $_POST['precio_rotura'],
                $imagen_path,
                $_POST['observaciones'] ?: null,
                $_POST['primer_porque'],
                $_POST['segundo_porque'],
                $_POST['tercer_porque'],
                $_POST['id'],
                getOperacionActiva()
            ]);

            $mensaje = "Rotura actualizada exitosamente";
            $tipo_mensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

$stmt = $pdo->prepare("SELECT * FROM roturas WHERE operacion_id = ? ORDER BY fecha_registro DESC");
$stmt->execute([getOperacionActiva()]);
$roturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_productos = $pdo->query("SELECT * FROM productos ORDER BY material");
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

$stmt_precios = $pdo->query("SELECT * FROM precios");
$precios = $stmt_precios->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Roturas - WARE PRO</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-black': '#1a1a1a',
                        'secondary-black': '#2d2d2d',
                        'primary-gold': '#FFD700',
                        'secondary-gold': '#FFA500',
                        'pure-white': '#ffffff',
                        'light-gray': '#f8f9fa',
                        'border-gray': '#e2e8f0',
                        'text-gray': '#6c757d',
                        'success-green': '#28a745',
                        'warning-yellow': '#ffc107',
                        'danger-red': '#dc3545',
                        'info-blue': '#17a2b8',
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            color: #1a1a1a;
            line-height: 1.6;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.8rem;
            margin: 0 0.2rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            color: #1a1a1a !important;
            transition: all 0.2s ease;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #FFD700 !important;
            border-color: #FFD700 !important;
            color: #1a1a1a !important;
        }
        
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%) !important;
            border-color: #FFD700 !important;
            color: #1a1a1a !important;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
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
            background-color: #ffffff;
            margin: 5% auto;
            padding: 2.5rem;
            border-radius: 20px;
            width: 90%;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        
        @media (max-width: 768px) {
            .modal-content {
                margin: 10% auto;
                padding: 1.5rem;
                width: 95%;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }
        
        .file-upload-container.drag-over .file-upload-label {
            border-color: #FFD700;
            background: rgba(255, 215, 0, 0.1);
            transform: scale(1.02);
        }
        
        .select-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #ffffff;
            border: 2px solid #FFD700;
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        
        .select-dropdown.show {
            display: block;
        }
        
        .select-option {
            padding: 0.8rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        
        .select-option:last-child {
            border-bottom: none;
        }
        
        .select-option:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .supervisor-field {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: not-allowed;
        }

        .supervisor-field .supervisor-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .supervisor-field .supervisor-name {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 0.95rem;
        }

        .supervisor-field .supervisor-badge {
            margin-left: auto;
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
    </style>
</head>

<body class="bg-white text-primary-black antialiased">
    <?php include '../core/header.php'; ?>

    <div class="p-4 md:p-8 max-w-[1400px] mx-auto">
        <div class="bg-gradient-to-br from-primary-black to-secondary-black text-white p-8 mb-8 rounded-[20px] shadow-[0_20px_40px_rgba(0,0,0,0.15)] relative overflow-hidden animate-fade-in">
            <div class="absolute inset-0 opacity-30" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><defs><pattern id=%22grain%22 width=%22100%22 height=%22100%22 patternUnits=%22userSpaceOnUse%22><circle cx=%2225%22 cy=%2225%22 r=%221%22 fill=%22rgba(255,215,0,0.1)%22/><circle cx=%2275%22 cy=%2275%22 r=%221%22 fill=%22rgba(255,215,0,0.1)%22/><circle cx=%2250%22 cy=%2210%22 r=%220.5%22 fill=%22rgba(255,215,0,0.05)%22/></pattern></defs><rect width=%22100%22 height=%22100%22 fill=%22url(%23grain)%22/></svg>');"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-primary-gold mb-2">Control de Roturas</h1>
                    <p class="text-gray-300 text-lg">Gestión y seguimiento de incidentes</p>
                </div>
                <button onclick="openAddModal()" class="bg-gradient-to-br from-primary-gold to-secondary-gold text-primary-black px-6 py-3 rounded-xl font-semibold transition-all duration-300 flex items-center gap-3 shadow-[0_6px_20px_rgba(255,215,0,0.3)] hover:-translate-y-0.5 hover:shadow-[0_8px_25px_rgba(255,215,0,0.4)]">
                    <i class="fas fa-plus"></i>
                    Agregar Rotura
                </button>
            </div>
        </div>

        <div class="bg-white rounded-[20px] shadow-[0_8px_25px_rgba(0,0,0,0.1)] border border-border-gray overflow-hidden animate-fade-in">
            <div class="bg-light-gray p-6 border-b-[3px] border-primary-gold flex flex-col md:flex-row justify-between items-center gap-4">
                <h2 class="flex items-center gap-3 text-2xl font-bold text-primary-black">
                    <i class="fas fa-list-alt text-primary-gold text-xl"></i>
                    Registros de Roturas
                </h2>
                
                <div class="relative w-full md:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-calendar-alt text-text-gray"></i>
                    </div>
                    <input type="text" id="rangoFechas" class="w-full pl-10 p-2 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-2 focus:ring-primary-gold/20 outline-none bg-white text-sm" placeholder="Filtrar por rango de fechas...">
                    <button id="clearDates" class="absolute inset-y-0 right-0 pr-3 flex items-center text-text-gray hover:text-danger-red transition-colors hidden">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-8">
                <table id="tablaRoturas" class="display responsive nowrap w-full">
                    <thead>
                        <tr class="bg-gradient-to-br from-primary-black to-secondary-black text-primary-gold">
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-calendar mr-2"></i>Fecha</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-user-tie mr-2"></i>Supervisor</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-clock mr-2"></i>Turno</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-user mr-2"></i>Persona</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-box mr-2"></i>Producto</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-sort-numeric-up mr-2"></i>Unidades</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-dollar-sign mr-2"></i>Precio</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-map-marker-alt mr-2"></i>Zona</th>
                            <th class="p-5 text-left font-bold text-sm uppercase tracking-wider"><i class="fas fa-cogs mr-2"></i>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-gray">
                        <?php if (empty($roturas)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-12 text-text-gray">
                                    <i class="fas fa-inbox text-6xl mb-4 block opacity-50"></i>
                                    <h5 class="text-xl font-semibold text-primary-black mb-2">No hay registros disponibles</h5>
                                    <p>Comienza agregando tu primera rotura</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($roturas as $rotura): ?>
                                <tr class="hover:bg-light-gray transition-all duration-200 hover:scale-[1.01]">
                                    <td class="p-4" data-order="<?php echo strtotime($rotura['fecha_registro']); ?>">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-primary-black"><?php echo date('d/m/Y', strtotime($rotura['fecha_registro'])); ?></span>
                                            <small class="text-text-gray"><?php echo date('H:i', strtotime($rotura['fecha_registro'])); ?></small>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-br from-[#d1ecf1] to-[#bee5eb] text-[#0c5460] text-xs font-semibold uppercase tracking-wide">
                                            <?php echo htmlspecialchars($rotura['supervisor_turno']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-br from-[#fff3cd] to-[#ffeaa7] text-[#856404] text-xs font-semibold uppercase tracking-wide">
                                            <?php echo htmlspecialchars($rotura['turno']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4"><?php echo htmlspecialchars($rotura['persona_rotura']); ?></td>
                                    <td class="p-4">
                                        <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-br from-[#d4edda] to-[#c3e6cb] text-[#155724] text-xs font-semibold uppercase tracking-wide">
                                            <?php echo htmlspecialchars($rotura['tipo_producto']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4">
                                        <strong><?php echo number_format($rotura['unidades']); ?></strong>
                                    </td>
                                    <td class="p-4">
                                        <?php 
                                        $precio = floatval($rotura['precio_rotura'] ?? 0);
                                        $class = $precio >= 20000 
                                            ? 'bg-gradient-to-br from-[#f8d7da] to-[#f1aeb5] text-[#721c24] border-2 border-[#f5c6cb]' 
                                            : 'bg-gradient-to-br from-[#d4edda] to-[#c3e6cb] text-[#155724] border-2 border-[#b8dabd]';
                                        ?>
                                        <span class="inline-block px-4 py-2 rounded-xl font-semibold text-sm whitespace-nowrap <?php echo $class; ?>">
                                            $<?php echo number_format($precio, 0, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td class="p-4"><?php echo htmlspecialchars($rotura['zona']); ?></td>
                                    <td class="p-4">
                                        <div class="flex gap-2 flex-wrap justify-center">
                                            <button onclick="viewRecord(<?php echo $rotura['id']; ?>)" class="px-4 py-2 rounded-lg bg-gradient-to-br from-info-blue to-[#20c997] text-white text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5 flex items-center gap-2 shadow-[0_3px_10px_rgba(23,162,184,0.3)] hover:shadow-[0_5px_15px_rgba(23,162,184,0.4)]" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                                Ver
                                            </button>
                                            <button onclick="editRecord(<?php echo $rotura['id']; ?>)" class="px-4 py-2 rounded-lg bg-gradient-to-br from-primary-gold to-secondary-gold text-primary-black text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5 flex items-center gap-2 shadow-[0_3px_10px_rgba(255,193,7,0.3)] hover:shadow-[0_5px_15px_rgba(255,193,7,0.4)]" title="Editar registro">
                                                <i class="fas fa-edit"></i>
                                                Editar
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

    <div id="formModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-8 pb-4 border-b-[3px] border-primary-gold">
                <h2 class="text-3xl font-bold text-primary-black flex items-center gap-3">
                    <i class="fas fa-plus-circle text-primary-gold"></i>
                    <span id="form-title-text">Registrar Nueva Rotura</span>
                </h2>
                <button onclick="closeModal('formModal')" class="w-10 h-10 flex items-center justify-center rounded-full text-text-gray hover:bg-light-gray hover:text-danger-red transition-all duration-300 text-3xl">
                    &times;
                </button>
            </div>

            <form method="POST" id="roturaForm" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="agregar" id="form-action">
                <input type="hidden" name="id" id="rotura-id">
                <input type="hidden" name="imagen_actual" id="imagen-actual">
                <input type="hidden" name="codigo_producto" id="codigo-producto">
                <input type="hidden" name="descripcion_material" id="descripcion-material">
                <input type="hidden" name="precio_rotura" id="precio-rotura">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-user-tie text-primary-gold"></i>
                            Supervisor de Turno *
                        </label>
                        <div class="supervisor-field">
                            <div class="supervisor-avatar">
                                <i class="fas fa-user text-primary-black text-sm"></i>
                            </div>
                            <span class="supervisor-name"><?php echo htmlspecialchars($current_user); ?></span>
                            <span class="supervisor-badge"><i class="fas fa-lock mr-1"></i>Sesión activa</span>
                        </div>
                        <input type="hidden" name="supervisor_turno" value="<?php echo htmlspecialchars($current_user); ?>">
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-clock text-primary-gold"></i>
                            Turno *
                        </label>
                        <select name="turno" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" required>
                            <option value="">Seleccionar turno</option>
                            <option value="Turno A">Turno A</option>
                            <option value="Turno B">Turno B</option>
                            <option value="Turno C">Turno C</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-user text-primary-gold"></i>
                            Persona que realizó la rotura *
                        </label>
                        <input type="hidden" name="persona_rotura" id="persona-rotura-value">
                        <div class="relative">
                            <div class="relative">
                                <input type="text"
                                       id="persona-search"
                                       class="w-full p-4 pr-12 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white"
                                       placeholder="Buscar operador..."
                                       autocomplete="off">
                                <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <i class="fas fa-search text-text-gray text-sm" id="persona-search-icon"></i>
                                </div>
                            </div>
                            <div class="select-dropdown" id="persona-dropdown"></div>
                        </div>
                        <p class="text-xs text-text-gray mt-2 flex items-center gap-1">
                            <i class="fas fa-info-circle text-primary-gold"></i>
                            Solo se muestran personas con cargo Operador
                        </p>
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-id-badge text-primary-gold"></i>
                            Cargo de la persona *
                        </label>
                        <select name="cargo_persona" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" id="cargo-select" required>
                            <option value="">Seleccionar cargo</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-truck text-primary-gold"></i>
                            Placa Montacarga (OPM)
                        </label>
                        <select name="placa_montacarga" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white">
                            <option value="">Seleccionar placa</option>
                            <option value="640">640</option>
                            <option value="687">687</option>
                            <option value="748">748</option>
                            <option value="754">754</option>
                            <option value="760">760</option>
                            <option value="759">759</option>
                            <option value="564">564</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-truck-moving text-primary-gold"></i>
                            Placa de Camión
                        </label>
                        <input type="text" name="placa_camion" id="placa_camion" maxlength="6" oninput="this.value = this.value.toUpperCase()" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" placeholder="Ej: ABC123">
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-route text-primary-gold"></i>
                            Canal *
                        </label>
                        <select name="canal" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" required>
                            <option value="">Seleccionar canal</option>
                            <option value="T1">T1</option>
                            <option value="T2">T2</option>
                            <option value="T4">T4</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-box text-primary-gold" ></i>
                            Tipo de producto *
                        </label>
                        <select name="tipo_producto" id="tipo-producto" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="Envase">Envase</option>
                            <option value="Lata">Lata</option>
                            <option value="Ret">Ret</option>
                            <option value="N Ret">N Ret</option>
                            <option value="Pet">Pet</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-box text-primary-gold" required></i>
                            Producto *
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   id="producto-search" 
                                   class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" 
                                   placeholder="Buscar producto por código o nombre..." 
                                   autocomplete="off" 
                                   required>
                            <div class="select-dropdown" id="producto-dropdown"></div>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-sort-numeric-up text-primary-gold"></i>
                            Unidades *
                        </label>
                        <input type="number" name="unidades" id="unidades-input" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" min="1" required placeholder="Cantidad de unidades">
                    </div>

                    <div>
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-primary-gold"></i>
                            Zona *
                        </label>
                        <select name="zona" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" required>
                            <option value="">Seleccionar zona</option>
                            <option value="Bahia T1">Bahia T1</option>
                            <option value="Bahia T2">Bahia T2</option>
                            <option value="Bahia T4">Bahia T4</option>
                            <option value="Bahia MKp">Bahia MKp</option>
                            <option value="Carpa">Carpa</option>
                            <option value="Bodega POSM">Bodega POSM</option>
                            <option value="Picking lata">Picking lata</option>
                            <option value="Picking Botella">Picking Botella</option>
                            <option value="Bodega B">Bodega B</option>
                            <option value="Bodega C">Bodega C</option>
                            <option value="Bodega D">Bodega D</option>
                            <option value="Bodega E">Bodega E</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <div class="hidden bg-gradient-to-br from-primary-gold/10 to-secondary-gold/5 p-6 rounded-2xl border-2 border-primary-gold text-center" id="precio-display">
                            <div class="text-sm text-text-gray font-medium mb-2">Precio Total de Rotura</div>
                            <div class="text-3xl font-bold text-primary-black" id="precio-valor">$0</div>
                            <small class="text-text-gray mt-2 block">Precio unitario × Unidades</small>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-question-circle text-primary-gold"></i>
                            Casual *
                        </label>
                        <select name="casual" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" required>
                            <option value="">Seleccionar causa</option>
                            <option value="Parales en mal estado T1">Parales en mal estado T1</option>
                            <option value="Estibas en mal estado T1">Estibas en mal estado T1</option>
                            <option value="Parales en mal estado T2">Parales en mal estado T2</option>
                            <option value="Estibas en mal estado T2">Estibas en mal estado T2</option>
                            <option value="Mala maniobra">Mala maniobra</option>
                            <option value="Incorrecto Staking">Incorrecto Staking</option>
                            <option value="Carga corrida">Carga corrida</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-camera text-primary-gold"></i>
                            Registro fotográfico
                        </label>
                        <div class="file-upload-container relative">
                            <input type="file" name="registro_fotografico" class="file-upload-input absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*" id="file-input">
                            <label for="file-input" class="flex items-center justify-center gap-3 p-8 border-2 border-dashed border-border-gray rounded-xl bg-light-gray cursor-pointer transition-all duration-300 hover:border-primary-gold hover:bg-primary-gold/10 text-text-gray font-medium">
                                <i class="fas fa-cloud-upload-alt text-primary-gold text-3xl"></i>
                                <div>
                                    <div class="font-bold text-primary-black" id="file-label-text">Seleccionar imagen</div>
                                    <small class="text-text-gray">Arrastra y suelta o haz clic para seleccionar</small>
                                </div>
                            </label>
                        </div>
                        <div id="current-image" class="hidden mt-4">
                            <img id="current-image-preview" class="max-w-full max-h-[300px] rounded-xl shadow-md cursor-pointer hover:scale-105 transition-transform duration-300" alt="Vista previa">
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-sticky-note text-primary-gold"></i>
                            Observaciones
                        </label>
                        <textarea name="observaciones" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" rows="3" placeholder="Observaciones adicionales (opcional)"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-search text-primary-gold"></i>
                            Primer porqué de la rotura *
                        </label>
                        <textarea name="primer_porque" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" rows="3" required placeholder="¿Por qué ocurrió esta rotura?"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-search text-primary-gold"></i>
                            Segundo porqué de la rotura *
                        </label>
                        <textarea name="segundo_porque" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" rows="3" required placeholder="¿Por qué ocurrió la causa anterior?"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block mb-3 font-semibold text-primary-black flex items-center gap-2">
                            <i class="fas fa-search text-primary-gold"></i>
                            Tercer porqué de la rotura *
                        </label>
                        <textarea name="tercer_porque" class="w-full p-4 border-2 border-border-gray rounded-xl focus:border-primary-gold focus:ring-4 focus:ring-primary-gold/10 outline-none transition-all bg-light-gray focus:bg-white" rows="3" required placeholder="¿Por qué ocurrió la causa raíz?"></textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-6 border-t border-border-gray">
                    <button type="button" onclick="closeModal('formModal')" class="px-6 py-3 rounded-xl bg-text-gray text-white font-semibold transition-all duration-300 hover:bg-primary-black flex items-center gap-2">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-br from-primary-gold to-secondary-gold text-primary-black font-semibold transition-all duration-300 hover:-translate-y-0.5 shadow-[0_4px_15px_rgba(255,215,0,0.3)] hover:shadow-[0_6px_20px_rgba(255,215,0,0.4)] flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        <span id="btn-text">Registrar Rotura</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-8 pb-4 border-b-[3px] border-primary-gold">
                <h2 class="text-3xl font-bold text-primary-black flex items-center gap-3">
                    <i class="fas fa-eye text-primary-gold"></i>
                    Detalles de la Rotura
                </h2>
                <button onclick="closeModal('viewModal')" class="w-10 h-10 flex items-center justify-center rounded-full text-text-gray hover:bg-light-gray hover:text-danger-red transition-all duration-300 text-3xl">
                    &times;
                </button>
            </div>
            <div id="modal-body-content"></div>
        </div>
    </div>

    <script>
        const productos = <?php echo json_encode($productos); ?>;
        const precios = <?php echo json_encode($precios); ?>;
        const operadoresData = <?php echo json_encode($operadores); ?>;

        const preciosMap = {};
        precios.forEach(p => {
            preciosMap[p.Codigo] = {
                envase: parseFloat(p.ENVASE || 0),
                total: parseFloat(p.TOTAL || 0)
            };
        });

        const productosData = productos.map(p => ({
            codigo: p.id_material,
            nombre: p.material,
            text: `${p.id_material} - ${p.material}`
        }));

        let personalData = { personas: [], cargos: [] };
        let precioUnitario = 0;
        let fpInstance = null;

        document.addEventListener('DOMContentLoaded', function() {
            loadPersonalData();

            
            fpInstance = flatpickr("#rangoFechas", {
                mode: "range",
                locale: "es", 
                dateFormat: "d/m/Y",
                onChange: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        $('#clearDates').removeClass('hidden');
                        $('#tablaRoturas').DataTable().draw();
                    } else if (selectedDates.length === 0) {
                        $('#clearDates').addClass('hidden');
                        $('#tablaRoturas').DataTable().draw();
                    }
                }
            });

            
            $('#clearDates').on('click', function() {
                fpInstance.clear();
                $(this).addClass('hidden');
            });

            
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex, rowData, counter) {
                    if (!fpInstance || fpInstance.selectedDates.length !== 2) {
                        return true; 
                    }

                    let startDate = fpInstance.selectedDates[0];
                    let endDate = new Date(fpInstance.selectedDates[1]);
                    
                    endDate.setHours(23, 59, 59, 999); 

                    
                    let tdDate = $(settings.aoData[dataIndex].anCells[0]).attr('data-order');
                    if(!tdDate) return true;

                    
                    let rowDate = new Date(tdDate * 1000);

                    if (rowDate >= startDate && rowDate <= endDate) {
                        return true;
                    }
                    return false;
                }
            );

            
            $('#tablaRoturas').DataTable({
                responsive: true,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                order: [[0, 'desc']],
                pageLength: 10,
                columnDefs: [
                    { targets: 0, type: 'num', orderSequence: ['desc', 'asc'] }
                ]
            });
        });

        <?php if (isset($mensaje)): ?>
            Swal.fire({
                title: '<?php echo $tipo_mensaje === "success" ? "¡Éxito!" : "Error"; ?>',
                text: '<?php echo $mensaje; ?>',
                icon: '<?php echo $tipo_mensaje; ?>',
                confirmButtonColor: '#FFD700',
                confirmButtonText: 'Aceptar',
                background: '#ffffff',
                color: '#1a1a1a'
            });
        <?php endif; ?>

        function loadPersonalData() {
            fetch('../api/rotura/get_personal1.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        personalData = data;
                        populateCargoSelect();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function populateCargoSelect() {
            const cargoSelect = document.getElementById('cargo-select');
            cargoSelect.innerHTML = '<option value="">Seleccionar cargo</option>';
            personalData.cargos.forEach(cargo => {
                const option = document.createElement('option');
                option.value = cargo;
                option.textContent = cargo;
                cargoSelect.appendChild(option);
            });
        }

        
        document.getElementById('persona-search').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const dropdown = document.getElementById('persona-dropdown');

            document.getElementById('persona-rotura-value').value = '';

            if (searchValue.length === 0) {
                dropdown.classList.remove('show');
                return;
            }

            const filtered = operadoresData.filter(p =>
                p.nombre.toLowerCase().includes(searchValue)
            );

            if (filtered.length > 0) {
                dropdown.innerHTML = filtered.map(p =>
                    `<div class="select-option" data-nombre="${p.nombre}">
                        <i class="fas fa-hard-hat text-primary-gold mr-2 text-xs"></i>
                        <span class="font-semibold">${p.nombre}</span>
                        <span class="text-text-gray text-xs ml-2">(${p.cargo})</span>
                    </div>`
                ).join('');
            } else {
                dropdown.innerHTML = `<div class="select-option text-text-gray italic">
                    <i class="fas fa-exclamation-circle text-warning-yellow mr-2"></i>
                    No se encontraron operadores
                </div>`;
            }
            dropdown.classList.add('show');
        });

        document.addEventListener('click', function(e) {
            
            const personaOpt = e.target.closest('#persona-dropdown .select-option');
            if (personaOpt && personaOpt.dataset.nombre) {
                document.getElementById('persona-search').value = personaOpt.dataset.nombre;
                document.getElementById('persona-rotura-value').value = personaOpt.dataset.nombre;
                document.getElementById('persona-dropdown').classList.remove('show');
            }

            
            if (e.target.closest('.select-option') && e.target.closest('.select-option').dataset.codigo) {
                const option = e.target.closest('.select-option');
                const codigo = option.dataset.codigo;
                const nombre = option.dataset.nombre;

                document.getElementById('producto-search').value = option.textContent.trim();
                document.getElementById('codigo-producto').value = codigo;
                document.getElementById('descripcion-material').value = nombre;
                document.getElementById('producto-dropdown').classList.remove('show');

                calcularPrecio();
            }

            if (!e.target.closest('#persona-search') && !e.target.closest('#persona-dropdown')) {
                document.getElementById('persona-dropdown').classList.remove('show');
            }

            if (!e.target.closest('#producto-search') && !e.target.closest('#producto-dropdown')) {
                document.getElementById('producto-dropdown').classList.remove('show');
            }
        });

        
        document.getElementById('producto-search').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const dropdown = document.getElementById('producto-dropdown');

            if (searchValue.length === 0) {
                dropdown.classList.remove('show');
                return;
            }

            const filtered = productosData.filter(p =>
                p.text.toLowerCase().includes(searchValue)
            );

            if (filtered.length > 0) {
                dropdown.innerHTML = filtered.map(p =>
                    `<div class="select-option" data-codigo="${p.codigo}" data-nombre="${p.nombre}">
                        <span class="font-semibold text-primary-gold mr-2">${p.codigo}</span> - ${p.nombre}
                    </div>`
                ).join('');
                dropdown.classList.add('show');
            } else {
                dropdown.innerHTML = '<div class="select-option">No se encontraron productos</div>';
                dropdown.classList.add('show');
            }
        });

        document.getElementById('tipo-producto').addEventListener('change', calcularPrecio);
        document.getElementById('unidades-input').addEventListener('input', calcularPrecio);

        function calcularPrecio() {
            const codigo = document.getElementById('codigo-producto').value;
            const tipo = document.getElementById('tipo-producto').value;
            const unidades = parseInt(document.getElementById('unidades-input').value) || 0;

            if (codigo && tipo && preciosMap[codigo] && unidades > 0) {
                if (tipo === 'Envase') {
                    precioUnitario = preciosMap[codigo].envase;
                } else if (tipo === 'Ret') {
                    precioUnitario = preciosMap[codigo].total;
                } else {
                    precioUnitario = 0;
                }

                const precioTotal = precioUnitario * unidades;

                document.getElementById('precio-rotura').value = precioTotal;
                document.getElementById('precio-valor').textContent = '$' + precioTotal.toLocaleString('es-CO');
                document.getElementById('precio-display').classList.remove('hidden');

                const precioValor = document.getElementById('precio-valor');
                precioValor.style.color = precioTotal >= 20000 ? '#721c24' : '#155724';
            } else {
                document.getElementById('precio-display').classList.add('hidden');
            }
        }

        
        function openAddModal() {
            document.getElementById('roturaForm').reset();
            document.getElementById('form-action').value = 'agregar';
            document.getElementById('rotura-id').value = '';
            document.getElementById('imagen-actual').value = '';
            document.getElementById('form-title-text').textContent = 'Registrar Nueva Rotura';
            document.getElementById('btn-text').textContent = 'Registrar Rotura';
            document.getElementById('file-label-text').textContent = 'Seleccionar imagen';
            document.getElementById('current-image').classList.add('hidden');
            document.getElementById('precio-display').classList.add('hidden');
            document.getElementById('persona-search').value = '';
            document.getElementById('persona-rotura-value').value = '';
            precioUnitario = 0;

            populateCargoSelect();
            document.getElementById('formModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        
        document.getElementById('file-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const label = document.getElementById('file-label-text');
            const currentImage = document.getElementById('current-image');
            const preview = document.getElementById('current-image-preview');

            if (file) {
                if (!file.type.startsWith('image/')) {
                    Swal.fire({ title: 'Archivo inválido', text: 'Por favor selecciona un archivo de imagen válido', icon: 'error', confirmButtonColor: '#FFD700' });
                    this.value = '';
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({ title: 'Archivo muy grande', text: 'El archivo debe ser menor a 5MB', icon: 'error', confirmButtonColor: '#FFD700' });
                    this.value = '';
                    return;
                }
                label.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    currentImage.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                label.textContent = 'Seleccionar imagen';
                currentImage.classList.add('hidden');
            }
        });

        const fileContainer = document.querySelector('.file-upload-container');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileContainer.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            fileContainer.addEventListener(eventName, () => fileContainer.classList.add('drag-over'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            fileContainer.addEventListener(eventName, () => fileContainer.classList.remove('drag-over'), false);
        });
        fileContainer.addEventListener('drop', function(e) {
            document.getElementById('file-input').files = e.dataTransfer.files;
            document.getElementById('file-input').dispatchEvent(new Event('change'));
        }, false);

        
        function editRecord(id) {
            fetch(`../api/rotura/get_rotura.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const r = data.record;

                        document.getElementById('form-action').value = 'editar';
                        document.getElementById('rotura-id').value = r.id;
                        document.getElementById('form-title-text').textContent = 'Editar Rotura';
                        document.getElementById('btn-text').textContent = 'Actualizar Rotura';
                        document.getElementById('imagen-actual').value = r.registro_fotografico || '';

                        document.querySelector('[name="turno"]').value = r.turno;
                        document.querySelector('[name="placa_montacarga"]').value = r.placa_montacarga || '';
                        
                        
                        document.querySelector('[name="placa_camion"]').value = r.placa_camion || '';
                        document.querySelector('[name="canal"]').value = r.canal || '';

                        
                        document.getElementById('persona-search').value = r.persona_rotura;
                        document.getElementById('persona-rotura-value').value = r.persona_rotura;

                        const cargoSelect = document.getElementById('cargo-select');
                        cargoSelect.innerHTML = `<option value="${r.cargo_persona}" selected>${r.cargo_persona}</option>`;

                        document.getElementById('tipo-producto').value = r.tipo_producto;
                        document.getElementById('codigo-producto').value = r.codigo_producto || '';
                        document.getElementById('descripcion-material').value = r.descripcion_material;
                        document.getElementById('producto-search').value = r.descripcion_material;
                        document.getElementById('precio-rotura').value = r.precio_rotura || 0;
                        document.getElementById('unidades-input').value = r.unidades;
                        document.querySelector('[name="zona"]').value = r.zona;
                        document.querySelector('[name="casual"]').value = r.casual;
                        document.querySelector('[name="observaciones"]').value = r.observaciones || '';
                        document.querySelector('[name="primer_porque"]').value = r.primer_porque;
                        document.querySelector('[name="segundo_porque"]').value = r.segundo_porque;
                        document.querySelector('[name="tercer_porque"]').value = r.tercer_porque;

                        if (r.registro_fotografico) {
                            document.getElementById('current-image-preview').src = r.registro_fotografico;
                            document.getElementById('current-image').classList.remove('hidden');
                        }

                        if (r.precio_rotura) {
                            const precioVal = parseFloat(r.precio_rotura);
                            document.getElementById('precio-valor').textContent = '$' + precioVal.toLocaleString('es-CO');
                            document.getElementById('precio-display').classList.remove('hidden');
                            document.getElementById('precio-valor').style.color = precioVal >= 20000 ? '#721c24' : '#155724';
                        }

                        populateCargoSelect();
                        document.getElementById('formModal').style.display = 'block';
                    }
                });
        }

        
        function viewRecord(id) {
            document.getElementById('modal-body-content').innerHTML = `
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-gold"></div>
                    <p class="mt-4 text-text-gray">Cargando detalles...</p>
                </div>
            `;
            document.getElementById('viewModal').style.display = 'block';

            fetch(`../api/rotura/get_rotura.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const r = data.record;
                        const precio = parseFloat(r.precio_rotura || 0);
                        const precioClass = precio >= 20000
                            ? 'bg-gradient-to-br from-[#f8d7da] to-[#f1aeb5] text-[#721c24] border-2 border-[#f5c6cb]'
                            : 'bg-gradient-to-br from-[#d4edda] to-[#c3e6cb] text-[#155724] border-2 border-[#b8dabd]';

                        let imageHtml = '';
                        if (r.registro_fotografico) {
                            imageHtml = `
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold md:col-span-2">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-camera"></i>
                                        Registro Fotográfico
                                    </div>
                                    <div class="text-text-gray">
                                        <img src="${r.registro_fotografico}" 
                                             class="max-w-full max-h-[300px] rounded-xl shadow-md cursor-pointer hover:scale-105 transition-transform duration-300" 
                                             alt="Registro fotográfico" 
                                             onclick="window.open('${r.registro_fotografico}', '_blank')"
                                             title="Clic para ampliar">
                                    </div>
                                </div>
                            `;
                        }

                        document.getElementById('modal-body-content').innerHTML = `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-user-tie"></i> Supervisor de Turno
                                    </div>
                                    <div class="text-text-gray text-lg">${r.supervisor_turno}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-clock"></i> Turno
                                    </div>
                                    <div class="text-text-gray">
                                        <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-br from-[#fff3cd] to-[#ffeaa7] text-[#856404] text-xs font-semibold uppercase tracking-wide">${r.turno}</span>
                                    </div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-user"></i> Persona que realizó la rotura
                                    </div>
                                    <div class="text-text-gray text-lg">${r.persona_rotura}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-truck"></i> Placa Montacarga
                                    </div>
                                    <div class="text-text-gray text-lg">${r.placa_montacarga || '<span class="opacity-50">N/A</span>'}</div>
                                </div>
                                
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-truck-moving"></i> Placa de Camión
                                    </div>
                                    <div class="text-text-gray text-lg">${r.placa_camion || '<span class="opacity-50">N/A</span>'}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-route"></i> Canal
                                    </div>
                                    <div class="text-text-gray text-lg">${r.canal || '<span class="opacity-50">N/A</span>'}</div>
                                </div>
                                
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-id-badge"></i> Cargo de la persona
                                    </div>
                                    <div class="text-text-gray text-lg">${r.cargo_persona}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-box"></i> Tipo de producto
                                    </div>
                                    <div class="text-text-gray">
                                        <span class="inline-block px-3 py-1 rounded-full bg-gradient-to-br from-[#d4edda] to-[#c3e6cb] text-[#155724] text-xs font-semibold uppercase tracking-wide">${r.tipo_producto}</span>
                                    </div>
                                </div>
                                <div class="md:col-span-2 bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-clipboard-list"></i> Descripción del material
                                    </div>
                                    <div class="text-text-gray text-lg">${r.descripcion_material}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-sort-numeric-up"></i> Unidades
                                    </div>
                                    <div class="text-text-gray text-xl font-bold">${r.unidades}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-dollar-sign"></i> Precio de Rotura
                                    </div>
                                    <div class="text-text-gray">
                                        <span class="inline-block px-4 py-2 rounded-xl font-semibold text-lg ${precioClass}">$${precio.toLocaleString('es-CO')}</span>
                                    </div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-map-marker-alt"></i> Zona
                                    </div>
                                    <div class="text-text-gray text-lg">${r.zona}</div>
                                </div>
                                <div class="bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-question-circle"></i> Casual
                                    </div>
                                    <div class="text-text-gray text-lg">${r.casual}</div>
                                </div>
                                ${r.observaciones ? `
                                <div class="md:col-span-2 bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-sticky-note"></i> Observaciones
                                    </div>
                                    <div class="text-text-gray text-lg">${r.observaciones}</div>
                                </div>
                                ` : ''}
                                <div class="md:col-span-2 bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-search"></i> Primer porqué de la rotura
                                    </div>
                                    <div class="text-text-gray text-lg leading-relaxed">${r.primer_porque}</div>
                                </div>
                                <div class="md:col-span-2 bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-search"></i> Segundo porqué de la rotura
                                    </div>
                                    <div class="text-text-gray text-lg leading-relaxed">${r.segundo_porque}</div>
                                </div>
                                <div class="md:col-span-2 bg-light-gray p-6 rounded-xl border-l-4 border-primary-gold">
                                    <div class="font-bold text-primary-black text-sm uppercase tracking-wide mb-3 flex items-center gap-2">
                                        <i class="fas fa-search"></i> Tercer porqué de la rotura
                                    </div>
                                    <div class="text-text-gray text-lg leading-relaxed">${r.tercer_porque}</div>
                                </div>
                                ${imageHtml}
                            </div>
                        `;
                    }
                });
        }
    </script>
</body>
</html>