<?php
require_once __DIR__ . '/../../core/config.php';
require_once '../../core/header.php';

$cargoUsuario = $_SESSION['cargo'] ?? '';
if (!in_array($cargoUsuario, ['admin', 'lider', 'supervisor'])) {
    die("Acceso denegado. Se requieren permisos de administrador.");
}

$errores = [];
$exito = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'guardar_modulo') {
            $id = $_POST['modulo_id'] ?? '';
            $nombre = trim($_POST['nombre'] ?? '');
            $criterios = $_POST['criterios'] ?? '';

            if ($nombre === '') $errores[] = "El nombre es obligatorio.";

            if (!$errores) {
                if (empty($id)) {
                    $stmt = $pdo->prepare("INSERT INTO auditorias (nombre, criterios, created_at) VALUES (:n, :c, NOW())");
                    $stmt->execute([':n' => $nombre, ':c' => $criterios]);
                    $exito = "Módulo creado exitosamente.";
                } else {
                    $stmt = $pdo->prepare("UPDATE auditorias SET nombre = :n, criterios = :c WHERE id = :id");
                    $stmt->execute([':n' => $nombre, ':c' => $criterios, ':id' => $id]);
                    $exito = "Módulo actualizado exitosamente.";
                }
            }
        }

        
        elseif ($accion === 'eliminar_modulo') {
            $id = $_POST['modulo_id'] ?? '';
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM auditorias WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $exito = "Módulo y su contenido fueron eliminados.";
            }
        }

        
        elseif ($accion === 'subir_media') {
            $id = $_POST['modulo_id'] ?? '';
            $tipo_subida = $_POST['tipo_subida'] ?? 'archivo';

            if ($id) {
                if ($tipo_subida === 'enlace') {
                    $enlace = filter_var($_POST['enlace'] ?? '', FILTER_SANITIZE_URL);
                    $nombre_enlace = trim($_POST['nombre_enlace'] ?? 'Enlace externo');
                    
                    if (!empty($enlace)) {
                        $stmt = $pdo->prepare("INSERT INTO auditoria_media (auditoria_id, nombre_archivo, ruta, tipo) VALUES (?, ?, ?, 'link')");
                        $stmt->execute([$id, $nombre_enlace, $enlace]);
                        $exito = "Enlace guardado correctamente.";
                    } else {
                        $errores[] = "La URL proporcionada no es válida.";
                    }
                } else {
                    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                        $permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
                        $mime = mime_content_type($_FILES['archivo']['tmp_name']);
                        
                        if (!in_array($mime, $permitidos)) {
                            $errores[] = "Formato no permitido. Solo imágenes y PDFs.";
                        } else {
                            $dir = __DIR__ . '/../../uploads/auditoria_media';
                            if (!is_dir($dir)) mkdir($dir, 0755, true);
                            
                            $ext = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
                            $filename = date('Ymd_His') . '_' . uniqid() . '.' . $ext;
                            $rutaFinal = '../../uploads/auditoria_media/' . $filename;
                            
                            if (move_uploaded_file($_FILES['archivo']['tmp_name'], $dir . '/' . $filename)) {
                                $tipoDB = (strpos($mime, 'image') !== false) ? 'imagen' : 'pdf';
                                $stmt = $pdo->prepare("INSERT INTO auditoria_media (auditoria_id, nombre_archivo, ruta, tipo) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$id, $_FILES['archivo']['name'], $rutaFinal, $tipoDB]);
                                $exito = "Archivo cargado correctamente.";
                            } else {
                                $errores[] = "Error al mover el archivo al servidor.";
                            }
                        }
                    } else {
                        $errores[] = "Por favor selecciona un archivo válido.";
                    }
                }
            }
        }

        
        elseif ($accion === 'eliminar_media') {
            $media_id = $_POST['media_id'] ?? '';
            if ($media_id) {
                
                $stmt = $pdo->prepare("SELECT ruta, tipo FROM auditoria_media WHERE id = ?");
                $stmt->execute([$media_id]);
                $media = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($media) {
                    if ($media['tipo'] !== 'link') {
                        $ruta_fisica = __DIR__ . '/' . $media['ruta'];
                        if (file_exists($ruta_fisica) && !is_dir($ruta_fisica)) {
                            unlink($ruta_fisica); 
                        }
                    }
                    $stmt = $pdo->prepare("DELETE FROM auditoria_media WHERE id = ?");
                    $stmt->execute([$media_id]);
                    $exito = "Archivo/Enlace eliminado correctamente.";
                }
            }
        }

        
        elseif ($accion === 'guardar_checklist') {
            $id = $_POST['modulo_id'] ?? '';
            $checklist_id = $_POST['checklist_id'] ?? '';
            $titulo = trim($_POST['titulo_checklist'] ?? 'Checklist de Auditoría');
            $preguntas = $_POST['preguntas'] ?? [];
            $tipos = $_POST['tipos'] ?? [];

            if ($id && !empty($preguntas)) {
                $pdo->beginTransaction();
                
                if (empty($checklist_id)) {
                    $stmt = $pdo->prepare("INSERT INTO auditoria_checklists (auditoria_id, titulo) VALUES (?, ?)");
                    $stmt->execute([$id, $titulo]);
                    $checklist_id = $pdo->lastInsertId();
                } else {
                    $stmt = $pdo->prepare("UPDATE auditoria_checklists SET titulo = ? WHERE id = ?");
                    $stmt->execute([$titulo, $checklist_id]);
                    $pdo->prepare("DELETE FROM auditoria_checklist_preguntas WHERE checklist_id = ?")->execute([$checklist_id]);
                }

                $stmt_preg = $pdo->prepare("INSERT INTO auditoria_checklist_preguntas (checklist_id, pregunta, tipo_respuesta, orden) VALUES (?, ?, ?, ?)");
                foreach ($preguntas as $index => $pregunta) {
                    if (trim($pregunta) !== '') {
                        $tipo = $tipos[$index] ?? 'si_no';
                        $stmt_preg->execute([$checklist_id, trim($pregunta), $tipo, $index]);
                    }
                }
                $pdo->commit();
                $exito = "Checklist guardado correctamente.";
            }
        }
        
        
        elseif ($accion === 'eliminar_checklist') {
            $checklist_id = $_POST['checklist_id'] ?? '';
            if ($checklist_id) {
                $stmt = $pdo->prepare("DELETE FROM auditoria_checklists WHERE id = ?");
                $stmt->execute([$checklist_id]);
                $exito = "Checklist eliminado correctamente.";
            }
        }

        
        elseif ($accion === 'guardar_zona') {
            $zona_id = $_POST['zona_id'] ?? '';
            $nombre_zona = trim($_POST['nombre_zona'] ?? '');
            
            if ($nombre_zona !== '') {
                if (empty($zona_id)) {
                    $stmt = $pdo->prepare("INSERT INTO auditoria_zonas (nombre, operacion_id) VALUES (?, ?)");
                    $stmt->execute([$nombre_zona, getOperacionActiva()]);
                    $exito = "Zona '$nombre_zona' creada exitosamente.";
                } else {
                    $stmt = $pdo->prepare("UPDATE auditoria_zonas SET nombre = ? WHERE id = ? AND operacion_id = ?");
                    $stmt->execute([$nombre_zona, $zona_id, getOperacionActiva()]);
                    $exito = "Zona actualizada exitosamente.";
                }
            }
        }

        
        elseif ($accion === 'eliminar_zona') {
            $zona_id = $_POST['zona_id'] ?? '';
            if ($zona_id) {
                $stmt = $pdo->prepare("DELETE FROM auditoria_zonas WHERE id = ? AND operacion_id = ?");
                $stmt->execute([$zona_id, getOperacionActiva()]);
                $exito = "Zona eliminada correctamente.";
            }
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $errores[] = "Error en base de datos: " . $e->getMessage();
    }
}


$registros = [];
$checklistsJS = [];
$zonas = [];

try {
    $modulos = $pdo->query("SELECT * FROM auditorias ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($modulos as $mod) {
        $id = $mod['id'];
        
        $media = $pdo->prepare("SELECT * FROM auditoria_media WHERE auditoria_id = ? ORDER BY created_at DESC");
        $media->execute([$id]);
        $mod['archivos'] = $media->fetchAll(PDO::FETCH_ASSOC);

        $chk = $pdo->prepare("SELECT id, titulo, created_at FROM auditoria_checklists WHERE auditoria_id = ? ORDER BY created_at ASC");
        $chk->execute([$id]);
        $checklists = $chk->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($checklists as &$c) {
            $preg = $pdo->prepare("SELECT id, pregunta, tipo_respuesta FROM auditoria_checklist_preguntas WHERE checklist_id = ? ORDER BY orden ASC");
            $preg->execute([$c['id']]);
            $c['preguntas'] = $preg->fetchAll(PDO::FETCH_ASSOC);

            
            $ejec = $pdo->prepare("
                SELECT e.id, e.usuario, e.fecha_ejecucion, z.nombre as zona_nombre
                FROM auditoria_ejecuciones e
                LEFT JOIN auditoria_zonas z ON e.zona_id = z.id
                WHERE e.checklist_id = ? AND e.operacion_id = ?
                ORDER BY e.fecha_ejecucion DESC
            ");
            $ejec->execute([$c['id'], getOperacionActiva()]);
            $ejecuciones = $ejec->fetchAll(PDO::FETCH_ASSOC);

            foreach ($ejecuciones as &$ej) {
                $resp = $pdo->prepare("
                    SELECT r.respuesta, p.pregunta, p.tipo_respuesta
                    FROM auditoria_ejecucion_respuestas r
                    JOIN auditoria_checklist_preguntas p ON r.pregunta_id = p.id
                    WHERE r.ejecucion_id = ? AND r.operacion_id = ?
                    ORDER BY p.orden ASC
                ");
                $resp->execute([$ej['id'], getOperacionActiva()]);
                $ej['respuestas'] = $resp->fetchAll(PDO::FETCH_ASSOC);
            }
            $c['ejecuciones'] = $ejecuciones;
        }
        
        $mod['checklists'] = $checklists;
        $checklistsJS[$id] = $checklists; 
        
        $registros[] = $mod;
    }

    $zonasStmt = $pdo->prepare("SELECT * FROM auditoria_zonas WHERE operacion_id = ? ORDER BY nombre ASC");
    $zonasStmt->execute([getOperacionActiva()]);
    $zonas = $zonasStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $errores[] = "Error al cargar los datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Auditoría - WARE PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    
    <style>
        :root {
            --primary-black: #0F0F10;
            --accent-yellow: #FFC107;
            --accent-yellow-dark: #E0A800;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --bg-primary: #FFFFFF;
            --bg-secondary: #f4f6f8;
            --border-light: #e5e7eb;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-secondary); }
        
        .page-header { background: var(--bg-primary); padding: 24px; border-radius: var(--radius-lg); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .page-title { font-weight: 900; color: var(--primary-black); letter-spacing: -0.5px;}
        
        .btn-primary-custom { background: var(--accent-yellow); color: var(--primary-black); font-weight: 700; border: none; padding: 10px 20px; border-radius: var(--radius-md); transition: all 0.2s ease; }
        .btn-primary-custom:hover { background: var(--accent-yellow-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3); }
        
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; align-items: stretch;}
        
        .audit-card { background: var(--bg-primary); border: 1px solid var(--border-light); border-radius: var(--radius-lg); display: flex; flex-direction: column; height: 100%; position: relative; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .audit-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .audit-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--accent-yellow); border-top-left-radius: var(--radius-lg); border-top-right-radius: var(--radius-lg); }
        
        .card-header-custom { padding: 20px 20px 0; display: flex; justify-content: space-between; align-items: flex-start; }
        .card-title { font-size: 1.25rem; font-weight: 800; color: var(--primary-black); margin: 0; padding-right: 15px;}
        .card-content { padding: 15px 20px; flex-grow: 1; display: flex; flex-direction: column; }
        .card-footer-custom { padding: 0 20px 20px; margin-top: auto; }

        .dropdown-toggle-custom { background: transparent; border: none; color: var(--text-secondary); font-size: 1.2rem; padding: 0 5px; cursor: pointer; transition: color 0.2s; }
        .dropdown-toggle-custom:hover { color: var(--primary-black); }
        .dropdown-toggle-custom::after { display: none; }
        .dropdown-menu { border-radius: var(--radius-md); border: 1px solid var(--border-light); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .dropdown-item { font-weight: 500; padding: 8px 16px; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
        .dropdown-item:hover { background-color: var(--bg-secondary); }
        .dropdown-item.text-danger:hover { background-color: #fee2e2; }

        .descripcion-modulo { font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; overflow-x: auto; }
        .descripcion-modulo table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .descripcion-modulo table, .descripcion-modulo th, .descripcion-modulo td { border: 1px solid var(--border-light); padding: 5px; }

        .media-container { display: flex; gap: 12px; flex-wrap: wrap; margin-top: auto; padding-top: 15px; border-top: 1px dashed var(--border-light); }
        
        
        .media-wrapper { position: relative; display: inline-block; }
        .btn-delete-media { 
            position: absolute; top: -6px; right: -6px; background: #ef4444; color: white; border: none; 
            border-radius: 50%; width: 22px; height: 22px; font-size: 11px; display: flex; 
            align-items: center; justify-content: center; cursor: pointer; opacity: 0; 
            transition: all 0.2s ease; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .media-wrapper:hover .btn-delete-media { opacity: 1; transform: scale(1.1); }
        .btn-delete-media:hover { background: #b91c1c; }

        .media-item { width: 55px; height: 55px; border-radius: 8px; border: 1px solid var(--border-light); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; background: #f3f4f6; transition: 0.2s; text-decoration: none;}
        .media-item:hover { border-color: var(--accent-yellow); transform: scale(1.05); }
        .media-item img { width: 100%; height: 100%; object-fit: cover; }
        .media-item .fa-file-pdf { font-size: 24px; color: #dc2626; }
        .media-item .fa-link { font-size: 22px; color: #2563eb; }
        
        .checklist-item { background: var(--bg-secondary); border: 1px solid var(--border-light); border-radius: var(--radius-md); padding: 15px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .pregunta-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid var(--border-light);}
        .modal-image-view { max-width: 100%; max-height: 80vh; object-fit: contain; }
        
        .accordion-button:not(.collapsed) { background-color: #fffbf0; color: var(--primary-black); box-shadow: inset 0 -1px 0 rgba(0,0,0,.125); }
        .accordion-button:focus { box-shadow: none; border-color: rgba(0,0,0,.125); }

        .tox-promotion { display: none !important; }
    </style>
</head>
<body>
    <script>
        const moduleChecklists = <?php echo json_encode($checklistsJS, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>

    <div class="container py-4">
        <a href="../reportes/dashboard.php" class="btn btn-dark mb-4 rounded-pill px-4 fw-bold">
            <i class="fa-solid fa-arrow-left me-2"></i> Volver al Dashboard
        </a>
        
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="page-title">Centro de Control: Auditoría</h1>
                <p class="text-secondary mb-0"><i class="fa-solid fa-shield-halved me-1"></i> Gestión de Módulos, Zonas y Checklists</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-dark fw-bold border-2" onclick="abrirModalZonas()">
                    <i class="fa-solid fa-map-location-dot me-2 text-primary"></i> Gestionar Zonas
                </button>
                <button class="btn-primary-custom" onclick="abrirModalModulo()">
                    <i class="fa-solid fa-plus me-2"></i> Crear Nuevo Módulo
                </button>
            </div>
        </div>

        <div class="cards-grid">
            <?php foreach ($registros as $r): ?>
                <div id="html_criterios_<?php echo $r['id']; ?>" style="display: none;"><?php echo htmlspecialchars($r['criterios']); ?></div>

                <article class="audit-card">
                    <div class="card-header-custom">
                        <h3 class="card-title"><?php echo htmlspecialchars($r['nombre']); ?></h3>
                        
                        <div class="dropdown">
                            <button class="dropdown-toggle-custom" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" onclick="abrirModalModulo('<?php echo $r['id']; ?>', '<?php echo htmlspecialchars(addslashes($r['nombre'])); ?>')">
                                        <i class="fa-solid fa-pen text-primary"></i> Editar Módulo
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="abrirModalMedia('<?php echo $r['id']; ?>')">
                                        <i class="fa-solid fa-cloud-arrow-up text-success"></i> Subir Archivo / Link
                                    </button>
                                </li>
                                <li>
                                    <button class="dropdown-item" onclick="abrirGestionChecklists('<?php echo $r['id']; ?>')">
                                        <i class="fa-solid fa-list-check text-warning"></i> Gestionar Checklists
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button class="dropdown-item text-danger" onclick="confirmarAccion('eliminar_modulo', 'modulo_id', '<?php echo $r['id']; ?>', '¿Eliminar módulo?', 'Se borrará todo su contenido.')">
                                        <i class="fa-solid fa-trash-can"></i> Eliminar Módulo
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="card-content">
                        <div class="descripcion-modulo">
                            <?php echo $r['criterios']; ?>
                        </div>
                        
                        <?php if (!empty($r['archivos'])): ?>
                            <div class="media-container">
                                <?php foreach ($r['archivos'] as $archivo): ?>
                                    <div class="media-wrapper">
                                        
                                        <button class="btn-delete-media" onclick="event.stopPropagation(); event.preventDefault(); confirmarAccion('eliminar_media', 'media_id', '<?php echo $archivo['id']; ?>', '¿Eliminar Anexo?', 'Se borrará permanentemente de este módulo.')" title="Eliminar">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>

                                        <?php if ($archivo['tipo'] === 'imagen'): ?>
                                            <div class="media-item" onclick="verImagen('<?php echo htmlspecialchars($archivo['ruta']); ?>')" title="<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>">
                                                <img src="<?php echo htmlspecialchars($archivo['ruta']); ?>" alt="Img">
                                            </div>
                                        <?php elseif ($archivo['tipo'] === 'link'): ?>
                                            <a href="<?php echo htmlspecialchars($archivo['ruta']); ?>" target="_blank" class="media-item" style="background: #e0f2fe; border-color: #bae6fd;" title="<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>">
                                                <i class="fa-solid fa-link"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($archivo['ruta']); ?>" target="_blank" class="media-item" title="<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>">
                                                <i class="fa-solid fa-file-pdf"></i>
                                            </a>
                                        <?php endif; ?>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer-custom">
                        <?php $cantChecklists = count($r['checklists']); ?>
                        <?php if($cantChecklists > 0): ?>
                            <button class="btn w-100 fw-bold border" style="background: var(--bg-secondary); color: var(--primary-black);" onclick="abrirGestionChecklists('<?php echo $r['id']; ?>')">
                                <i class="fa-solid fa-list-check me-2 text-warning"></i> Gestionar Checklists (<?php echo $cantChecklists; ?>)
                            </button>
                        <?php else: ?>
                            <button class="btn btn-light w-100 fw-bold text-muted border" onclick="abrirModalChecklist('<?php echo $r['id']; ?>')">
                                <i class="fa-solid fa-plus me-2"></i> Crear Primer Checklist
                            </button>
                        <?php endif; ?>
                    </div>
                    
                </article>
            <?php endforeach; ?>
        </div>
    </div>

    <form id="formAcciones" method="POST" style="display: none;">
        <input type="hidden" name="accion" id="form_accion_global">
        <input type="hidden" name="modulo_id" id="form_modulo_id_global">
        <input type="hidden" name="checklist_id" id="form_checklist_id_global">
        <input type="hidden" name="zona_id" id="form_zona_id_global">
        <input type="hidden" name="media_id" id="form_media_id_global">
    </form>

    <div class="modal fade" id="modalZonas" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 bg-light rounded-top">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-map-location-dot text-primary me-2"></i>Gestor de Zonas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <form method="POST" class="mb-4 bg-white border rounded-3 p-3 shadow-sm" id="formZona">
                        <input type="hidden" name="accion" value="guardar_zona">
                        <input type="hidden" name="zona_id" id="modal_zona_id">
                        <label class="form-label fw-bold text-sm text-secondary" id="lblFormZona">Nueva Zona</label>
                        <div class="input-group">
                            <input type="text" name="nombre_zona" id="modal_zona_nombre" class="form-control" placeholder="Ej: Pasillo Principal" required>
                            <button type="submit" class="btn btn-dark fw-bold" id="btnGuardarZona">Guardar</button>
                            <button type="button" class="btn btn-light border" id="btnCancelarZona" style="display:none;" onclick="resetFormZona()">X</button>
                        </div>
                    </form>

                    <h6 class="fw-bold text-muted mb-2 text-sm uppercase">Zonas Existentes</h6>
                    <div class="list-group list-group-flush rounded-3 border overflow-auto" style="max-height: 300px;">
                        <?php if (empty($zonas)): ?>
                            <div class="p-4 text-center text-muted bg-light">
                                <i class="fa-solid fa-map text-2xl mb-2 d-block"></i> No hay zonas creadas aún.
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach ($zonas as $z): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-medium text-dark"><i class="fa-solid fa-location-dot text-warning me-2"></i><?= htmlspecialchars($z['nombre']) ?></span>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light border" onclick="editarZona(<?= $z['id'] ?>, '<?= htmlspecialchars(addslashes($z['nombre'])) ?>')">
                                        <i class="fa-solid fa-pen text-primary"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="confirmarAccion('eliminar_zona', 'zona_id', <?= $z['id'] ?>, '¿Eliminar Zona?', 'Se borrará permanentemente.')">
                                        <i class="fa-solid fa-trash-can text-danger"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light w-100 fw-bold" data-bs-dismiss="modal">Cerrar Panel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalModulo" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content border-0 shadow-lg" method="POST">
                <input type="hidden" name="accion" value="guardar_modulo">
                <input type="hidden" name="modulo_id" id="form_modulo_id">
                
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold" id="modalModuloTitulo"><i class="fa-solid fa-cube me-2"></i>Crear Módulo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-0">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre del Módulo</label>
                        <input type="text" name="nombre" id="form_modulo_nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Criterios / Descripción / Tablas</label>
                        <textarea name="criterios" id="form_modulo_criterios" class="form-control" rows="8"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-primary-custom"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalMedia" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content border-0 shadow-lg" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="subir_media">
                <input type="hidden" name="modulo_id" id="media_modulo_id">
                
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-arrow-up me-2"></i>Cargar Archivo o Enlace</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-0">
                    <div class="mb-3 bg-light p-3 rounded border">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_subida" id="radioArchivo" value="archivo" checked onchange="toggleUploadType()">
                            <label class="form-check-label fw-medium" for="radioArchivo">Subir Archivo</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="tipo_subida" id="radioEnlace" value="enlace" onchange="toggleUploadType()">
                            <label class="form-check-label fw-medium" for="radioEnlace">Añadir Enlace Web</label>
                        </div>
                    </div>

                    <div id="containerArchivo" class="mb-3">
                        <label class="form-label fw-bold">Seleccionar PDF o Imagen</label>
                        <input type="file" id="inputArchivo" name="archivo" class="form-control" accept=".pdf, image/jpeg, image/png, image/webp" required>
                    </div>

                    <div id="containerEnlace" class="mb-3" style="display: none;">
                        <label class="form-label fw-bold">Nombre del Enlace</label>
                        <input type="text" id="inputNombreEnlace" name="nombre_enlace" class="form-control mb-2" placeholder="Ej: Protocolo de Seguridad Externo">
                        <label class="form-label fw-bold">URL (Link)</label>
                        <input type="url" id="inputUrlEnlace" name="enlace" class="form-control" placeholder="https://ejemplo.com/archivo">
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-primary-custom"><i class="fa-solid fa-upload me-2"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalVisor" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pt-0">
                    <img id="visorImagenSrc" src="" class="modal-image-view rounded shadow-lg">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalListarChecklists" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-list-check me-2"></i>Checklists del Módulo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-0">
                    <button type="button" class="btn-primary-custom w-100 mb-3" id="btnNuevoChecklist">
                        <i class="fa-solid fa-plus me-2"></i> Crear Nuevo Checklist
                    </button>
                    <div id="contenedorListaChecklists"></div>
                </div>
                <div class="modal-footer border-top-0 mt-2">
                    <button type="button" class="btn btn-light w-100" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalBuilderChecklist" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content border-0 shadow-lg" method="POST">
                <input type="hidden" name="accion" value="guardar_checklist">
                <input type="hidden" name="modulo_id" id="builder_modulo_id">
                <input type="hidden" name="checklist_id" id="builder_checklist_id">
                
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title fw-bold" id="builderTituloModal">Constructor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-0">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Título del Checklist</label>
                        <input type="text" name="titulo_checklist" id="builder_titulo" class="form-control" required>
                    </div>
                    <h6 class="fw-bold mb-3 border-bottom pb-2">Preguntas del Checklist</h6>
                    <div id="contenedorPreguntas"></div>
                    <button type="button" class="btn btn-outline-dark mt-2 w-100 fw-bold border-dashed" onclick="agregarPregunta()" style="border-style: dashed;">
                        <i class="fa-solid fa-plus me-2"></i> Añadir Pregunta
                    </button>
                </div>
                <div class="modal-footer border-top-0 mt-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-primary-custom"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar Checklist</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalRegistrosChecklist" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 bg-light rounded-top">
                    <h5 class="modal-title fw-bold" id="registrosTituloModal">
                        <i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Registros
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div id="contenedorRegistros" class="accordion shadow-sm rounded-3"></div>
                </div>
                <div class="modal-footer border-top-0 bg-white rounded-bottom">
                    <button type="button" class="btn btn-secondary px-4 fw-bold" onclick="volverAListaChecklists()">
                        <i class="fa-solid fa-arrow-left me-2"></i>Volver
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        
        document.addEventListener('focusin', (e) => {
            if (e.target.closest(".tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                e.stopImmediatePropagation();
            }
        });

        
        tinymce.init({
            selector: '#form_modulo_criterios',
            plugins: 'table lists link code',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | table | link',
            menubar: false,
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save(); 
                });
            }
        });

        function toggleUploadType() {
            const isFile = document.getElementById('radioArchivo').checked;
            const containerArchivo = document.getElementById('containerArchivo');
            const containerEnlace = document.getElementById('containerEnlace');
            const inputArchivo = document.getElementById('inputArchivo');
            const inputUrl = document.getElementById('inputUrlEnlace');

            if (isFile) {
                containerArchivo.style.display = 'block';
                containerEnlace.style.display = 'none';
                inputArchivo.setAttribute('required', 'required');
                inputUrl.removeAttribute('required');
            } else {
                containerArchivo.style.display = 'none';
                containerEnlace.style.display = 'block';
                inputArchivo.removeAttribute('required');
                inputUrl.setAttribute('required', 'required');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($exito): ?>
                Swal.fire({ icon: 'success', title: '¡Éxito!', text: '<?php echo $exito; ?>', confirmButtonColor: '#FFC107', timer: 3000, timerProgressBar: true });
            <?php endif; ?>
            <?php if (!empty($errores)): ?>
                Swal.fire({ icon: 'error', title: 'Ocurrió un problema', html: '<ul style="text-align: left;"><?php foreach($errores as $e) echo "<li>".addslashes($e)."</li>"; ?></ul>', confirmButtonColor: '#dc2626' });
            <?php endif; ?>
        });

        function confirmarAccion(accion, param_name, param_value, titulo, texto) {
            Swal.fire({
                title: titulo, text: texto, icon: 'warning', showCancelButton: true,
                confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fa-solid fa-trash-can me-1"></i> Sí, eliminar', cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form_accion_global').value = accion;
                    document.getElementById('form_modulo_id_global').value = '';
                    document.getElementById('form_checklist_id_global').value = '';
                    document.getElementById('form_zona_id_global').value = '';
                    document.getElementById('form_media_id_global').value = ''; 
                    
                    document.getElementById('form_' + param_name + '_global').value = param_value;
                    document.getElementById('formAcciones').submit();
                }
            })
        }

        const modalZonasIns = new bootstrap.Modal(document.getElementById('modalZonas'));
        function abrirModalZonas() { resetFormZona(); modalZonasIns.show(); }

        function editarZona(id, nombre) {
            document.getElementById('modal_zona_id').value = id;
            document.getElementById('modal_zona_nombre').value = nombre;
            document.getElementById('lblFormZona').innerHTML = '<i class="fa-solid fa-pen text-primary me-1"></i> Editando Zona';
            document.getElementById('btnGuardarZona').innerText = 'Actualizar';
            document.getElementById('btnCancelarZona').style.display = 'block';
        }

        function resetFormZona() {
            document.getElementById('modal_zona_id').value = '';
            document.getElementById('modal_zona_nombre').value = '';
            document.getElementById('lblFormZona').innerText = 'Nueva Zona';
            document.getElementById('btnGuardarZona').innerText = 'Guardar';
            document.getElementById('btnCancelarZona').style.display = 'none';
        }

        const formModal = new bootstrap.Modal(document.getElementById('modalModulo'));
        function abrirModalModulo(id = '', nombre = '') {
            document.getElementById('form_modulo_id').value = id;
            document.getElementById('form_modulo_nombre').value = nombre;
            
            let criteriosHtml = '';
            if (id !== '') {
                criteriosHtml = document.getElementById('html_criterios_' + id).innerText || document.getElementById('html_criterios_' + id).textContent;
            }

            document.getElementById('form_modulo_criterios').value = criteriosHtml;
            if (tinymce.get('form_modulo_criterios')) {
                tinymce.get('form_modulo_criterios').setContent(criteriosHtml);
            }

            document.getElementById('modalModuloTitulo').innerHTML = id ? '<i class="fa-solid fa-pen me-2"></i>Editar Módulo' : '<i class="fa-solid fa-cube me-2"></i>Crear Módulo';
            formModal.show();
        }

        const mediaModal = new bootstrap.Modal(document.getElementById('modalMedia'));
        function abrirModalMedia(id) { 
            document.getElementById('media_modulo_id').value = id; 
            document.getElementById('radioArchivo').click();
            document.getElementById('inputArchivo').value = '';
            document.getElementById('inputNombreEnlace').value = '';
            document.getElementById('inputUrlEnlace').value = '';
            mediaModal.show(); 
        }

        const visorModal = new bootstrap.Modal(document.getElementById('modalVisor'));
        function verImagen(ruta) { document.getElementById('visorImagenSrc').src = ruta; visorModal.show(); }

        const listModal = new bootstrap.Modal(document.getElementById('modalListarChecklists'));
        const builderModal = new bootstrap.Modal(document.getElementById('modalBuilderChecklist'));
        const registrosModal = new bootstrap.Modal(document.getElementById('modalRegistrosChecklist'));
        
        let moduloActivoParaRegistros = null;

        function abrirGestionChecklists(modulo_id) {
            moduloActivoParaRegistros = modulo_id;
            const checklists = moduleChecklists[modulo_id] || [];
            const contenedorLista = document.getElementById('contenedorListaChecklists');
            contenedorLista.innerHTML = '';

            if (checklists.length === 0) {
                contenedorLista.innerHTML = '<p class="text-muted text-center mt-3"><i class="fa-solid fa-folder-open mb-2 d-block fs-3"></i>No hay checklists configurados.</p>';
            } else {
                checklists.forEach(chk => {
                    const cantRegistros = chk.ejecuciones ? chk.ejecuciones.length : 0;
                    const chkDiv = document.createElement('div');
                    chkDiv.className = 'checklist-item';
                    chkDiv.innerHTML = `
                        <div>
                            <strong class="d-block text-dark">${chk.titulo}</strong>
                            <small class="text-muted"><i class="fa-solid fa-question-circle me-1"></i>${chk.preguntas.length} preguntas</small>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-info text-white shadow-sm" title="Ver Registros" onclick="abrirVisorRegistros('${modulo_id}', '${chk.id}')">
                                <i class="fa-solid fa-clipboard-list"></i> <span class="badge bg-white text-info ms-1">${cantRegistros}</span>
                            </button>
                            <button class="btn btn-sm btn-light border" title="Editar" onclick="abrirModalChecklist('${modulo_id}', '${chk.id}')">
                                <i class="fa-solid fa-pen text-primary"></i>
                            </button>
                            <button class="btn btn-sm btn-light border" title="Eliminar" onclick="confirmarAccion('eliminar_checklist', 'checklist_id', '${chk.id}', '¿Eliminar Checklist?', 'Se borrará el checklist y sus preguntas permanentemente.')">
                                <i class="fa-solid fa-trash-can text-danger"></i>
                            </button>
                        </div>
                    `;
                    contenedorLista.appendChild(chkDiv);
                });
            }

            document.getElementById('btnNuevoChecklist').onclick = () => {
                listModal.hide();
                abrirModalChecklist(modulo_id, null);
            };

            listModal.show();
        }

        function abrirVisorRegistros(moduloId, checklistId) {
            listModal.hide();
            
            const checklists = moduleChecklists[moduloId] || [];
            const chk = checklists.find(c => c.id == checklistId);
            if(!chk) return;

            document.getElementById('registrosTituloModal').innerHTML = `<i class="fa-solid fa-clipboard-list me-2 text-primary"></i>Registros: ${chk.titulo}`;
            const cont = document.getElementById('contenedorRegistros');
            cont.innerHTML = '';

            if (!chk.ejecuciones || chk.ejecuciones.length === 0) {
                cont.innerHTML = '<div class="text-center text-muted py-5 bg-white rounded"><i class="fa-solid fa-folder-open text-4xl mb-3 d-block text-secondary"></i><p>Aún no hay auditorías registradas para este checklist.</p></div>';
            } else {
                chk.ejecuciones.forEach((ej, index) => {
                    let respuestasHtml = '<ul class="list-group list-group-flush rounded-bottom">';
                    ej.respuestas.forEach(r => {
                        let color = 'text-muted';
                        let icon = 'fa-comment';
                        if(r.tipo_respuesta === 'si_no') {
                            if(r.respuesta === 'Si') { color = 'text-success'; icon = 'fa-check'; }
                            else if(r.respuesta === 'No') { color = 'text-danger'; icon = 'fa-xmark'; }
                        }
                        respuestasHtml += `
                            <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-light py-3">
                                <span class="text-dark fw-medium pe-3 text-sm">${r.pregunta}</span>
                                <span class="${color} fw-bold text-sm text-nowrap"><i class="fa-solid ${icon} me-1"></i>${r.respuesta}</span>
                            </li>
                        `;
                    });
                    respuestasHtml += '</ul>';

                    
                    const zonaText = ej.zona_nombre ? ej.zona_nombre : 'Zona sin especificar';
                    
                    cont.innerHTML += `
                        <div class="accordion-item border-0 mb-3 shadow-sm rounded">
                            <h2 class="accordion-header" id="headingEjec_${ej.id}">
                                <button class="accordion-button collapsed rounded fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEjec_${ej.id}">
                                    <div class="d-flex justify-content-between w-100 pe-3 align-items-center flex-wrap gap-2">
                                        <div>
                                            <span class="text-dark fw-bold"><i class="fa-solid fa-user-check text-primary me-2"></i>${ej.usuario || 'Anónimo'}</span>
                                            <span class="badge bg-light text-dark border ms-2"><i class="fa-solid fa-location-dot text-danger me-1"></i>${zonaText}</span>
                                        </div>
                                        <span class="text-secondary fw-normal text-sm"><i class="fa-regular fa-clock me-1"></i>${ej.fecha_ejecucion}</span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapseEjec_${ej.id}" class="accordion-collapse collapse" data-bs-parent="#contenedorRegistros">
                                <div class="accordion-body bg-white p-0">
                                    ${respuestasHtml}
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            registrosModal.show();
        }

        function volverAListaChecklists() {
            registrosModal.hide();
            if(moduloActivoParaRegistros) {
                abrirGestionChecklists(moduloActivoParaRegistros);
            }
        }

        function abrirModalChecklist(modulo_id, checklist_id = null) {
            if(listModal) listModal.hide(); 
            
            document.getElementById('builder_modulo_id').value = modulo_id;
            const contenedorPreguntas = document.getElementById('contenedorPreguntas');
            contenedorPreguntas.innerHTML = '';

            if (checklist_id) {
                const checklists = moduleChecklists[modulo_id] || [];
                const chk = checklists.find(c => c.id == checklist_id);
                
                document.getElementById('builder_checklist_id').value = chk.id;
                document.getElementById('builder_titulo').value = chk.titulo;
                document.getElementById('builderTituloModal').innerHTML = '<i class="fa-solid fa-pen-to-square me-2"></i>Editar Checklist';
                
                chk.preguntas.forEach(p => agregarPregunta(p.pregunta, p.tipo_respuesta));
            } else {
                document.getElementById('builder_checklist_id').value = '';
                document.getElementById('builder_titulo').value = '';
                document.getElementById('builderTituloModal').innerHTML = '<i class="fa-solid fa-hammer me-2"></i>Nuevo Checklist';
                agregarPregunta();
            }

            builderModal.show();
        }

        function agregarPregunta(texto = '', tipo = 'si_no') {
            const div = document.createElement('div');
            div.className = 'pregunta-row';
            div.innerHTML = `
                <div class="flex-grow-1">
                    <input type="text" name="preguntas[]" class="form-control border-0 bg-transparent shadow-none" placeholder="Escribe la pregunta aquí..." value="${texto}" required>
                </div>
                <div style="width: 180px;">
                    <select name="tipos[]" class="form-select border-0 bg-white">
                        <option value="si_no" ${tipo === 'si_no' ? 'selected' : ''}>Sí / No</option>
                        <option value="comentario" ${tipo === 'comentario' ? 'selected' : ''}>Comentario Abierto</option>
                    </select>
                </div>
                <button type="button" class="btn btn-outline-danger border-0" onclick="this.parentElement.remove()" title="Eliminar pregunta">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            `;
            document.getElementById('contenedorPreguntas').appendChild(div);
        }
    </script>
</body>
</html>