<?php
/**
 * API de Entrenamientos · DPO. Todas las respuestas: {success, ...} o {success:false, message}.
 *
 * Líder (registrar):   subir, mis_registros
 * Consulta:            evidencias
 * Administración:      lideres, agregar_lider, quitar_lider, toggle_dia, toggle_celda,
 *                      registros_celda, cambiar_fecha, eliminar
 */
require_once '../../core/config.php';
verificarLogin();
require_once '../../core/dpo.php';
date_default_timezone_set('America/Bogota');
header('Content-Type: application/json; charset=utf-8');

$op = (int)getOperacionActiva();
$yo = (int)$_SESSION['usuario_id'];
$accion = $_POST['action'] ?? $_GET['action'] ?? '';
if (in_array($accion, ['subir', 'evidencias', 'registros_celda'], true)) dpoAsegurarPilares($pdo);

function responder(array $data, int $codigo = 200): void {
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function fallar(string $mensaje, int $codigo = 400): void {
    responder(['success' => false, 'message' => $mensaje], $codigo);
}
function exigirAdmin(): void {
    if (!dpoEsAdmin()) fallar('Solo administradores y supervisores pueden hacer esto.', 403);
}
function fechaValida(string $f): bool {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return $d && $d->format('Y-m-d') === $f;
}
/** Registro de esta operación o error. */
function registroDeOperacion(PDO $pdo, int $op, int $id): array {
    $stmt = $pdo->prepare("SELECT * FROM dpo_registros WHERE id = ? AND operacion_id = ?");
    $stmt->execute([$id, $op]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$r) fallar('El registro no existe en esta operación.', 404);
    return $r;
}

if (!$op) fallar('No hay una operación activa.');

// CSRF en toda escritura (el header.php inyecta X-CSRF-Token en fetch; el registro lo envía en su XHR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && function_exists('csrfToken')) {
    $token = (string)($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        fallar('La sesión de seguridad expiró. Recarga la página e intenta de nuevo.', 403);
    }
}
$pdo->exec("SET time_zone = '-05:00'");

try {
    switch ($accion) {

    // ------------------------------------------------------------------ Registrar
    case 'subir':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') fallar('Método no permitido.', 405);
        if (!dpoEsLider($pdo, $op, $yo)) fallar('No estás habilitado como líder DPO en esta operación.', 403);

        $colaborador = (int)($_POST['colaborador_id'] ?? 0);
        if ($colaborador === $yo) fallar('No puedes registrarte un entrenamiento a ti mismo.');
        $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ? AND operacion_id = ? AND activo = 1");
        $stmt->execute([$colaborador, $op]);
        $nombreColaborador = $stmt->fetchColumn();
        if (!$nombreColaborador) fallar('Selecciona a la persona que recibió el entrenamiento.');

        // El líder registra hoy o ayer (turnos que cierran después de medianoche); el administrador corrige fechas en el calendario.
        $hoy = date('Y-m-d');
        $fecha = $_POST['fecha'] ?? $hoy;
        if (!in_array($fecha, [$hoy, date('Y-m-d', strtotime('-1 day'))], true)) $fecha = $hoy;

        // Pilar(es) impactados: al menos uno de la lista
        $pilares = $_POST['pilares'] ?? [];
        if (!is_array($pilares)) $pilares = explode(',', (string)$pilares);
        $pilares = array_values(array_intersect(array_keys(DPO_PILARES), array_map('trim', $pilares)));
        if (!$pilares) fallar('Selecciona al menos un pilar impactado.');

        $arch = $_FILES['archivo'] ?? null;
        if (!$arch || $arch['error'] !== UPLOAD_ERR_OK) {
            $err = $arch['error'] ?? UPLOAD_ERR_NO_FILE;
            fallar(in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? 'El archivo es demasiado pesado para el servidor.' : 'No se recibió la evidencia. Intenta de nuevo.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arch['tmp_name']);
        if (!isset(DPO_MIME[$mime])) fallar('Solo se permiten fotos (JPG, PNG o WebP).');
        [$tipo, $ext] = DPO_MIME[$mime];
        if ($arch['size'] > DPO_MAX_IMAGEN) fallar('La foto supera el máximo de ' . round(DPO_MAX_IMAGEN / 1048576) . ' MB.');
        if (!@getimagesize($arch['tmp_name'])) fallar('La imagen está dañada.');

        // Carpeta por operación y mes: uploads/dpo/{op}/{YYYY}/{MM}/
        $rel = 'uploads/dpo/' . $op . '/' . date('Y/m', strtotime($fecha));
        $abs = dirname(__DIR__, 2) . '/' . $rel;
        if (!is_dir($abs) && !mkdir($abs, 0755, true)) fallar('No se pudo preparar la carpeta de evidencias.', 500);
        $base = $fecha . '_' . $yo . '_' . bin2hex(random_bytes(6));
        $archivoRel = "$rel/$base.$ext";
        if (!move_uploaded_file($arch['tmp_name'], dirname(__DIR__, 2) . '/' . $archivoRel)) fallar('No se pudo guardar la evidencia.', 500);

        // Miniatura generada en el navegador (JPG pequeño); es la que se muestra en listados.
        $miniRel = null;
        $mini = $_FILES['miniatura'] ?? null;
        if ($mini && $mini['error'] === UPLOAD_ERR_OK && $mini['size'] <= DPO_MAX_MINIATURA
            && (new finfo(FILEINFO_MIME_TYPE))->file($mini['tmp_name']) === 'image/jpeg' && @getimagesize($mini['tmp_name'])) {
            $miniRel = "$rel/{$base}_t.jpg";
            if (!move_uploaded_file($mini['tmp_name'], dirname(__DIR__, 2) . '/' . $miniRel)) $miniRel = null;
        }

        $obs = trim(mb_substr((string)($_POST['observacion'] ?? ''), 0, 255, 'UTF-8'));

        $stmt = $pdo->prepare("INSERT INTO dpo_registros (operacion_id, lider_id, colaborador_id, fecha, pilares, tipo, archivo, miniatura, peso_bytes, duracion_seg, observacion)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$op, $yo, $colaborador, $fecha, implode(',', $pilares), $tipo, $archivoRel, $miniRel, (int)$arch['size'], null, $obs !== '' ? $obs : null]);
        responder(['success' => true, 'message' => "Entrenamiento de $nombreColaborador registrado.", 'id' => (int)$pdo->lastInsertId()]);

    case 'mis_registros':
        $stmt = $pdo->prepare("SELECT r.id, r.fecha, r.tipo, r.archivo, r.miniatura, r.created_at, u.nombre AS colaborador
                               FROM dpo_registros r JOIN usuarios u ON u.id = r.colaborador_id
                               WHERE r.operacion_id = ? AND r.lider_id = ? AND r.fecha >= CURDATE() - INTERVAL 6 DAY
                               ORDER BY r.created_at DESC LIMIT 30");
        $stmt->execute([$op, $yo]);
        responder(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    // ------------------------------------------------------------------ Evidencias (galería)
    case 'evidencias':
        $porPagina = 12;
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $where = ['r.operacion_id = ?'];
        $params = [$op];
        // Quien no es administrador solo ve lo que registró
        if (!dpoEsAdmin()) { $where[] = 'r.lider_id = ?'; $params[] = $yo; }
        elseif (!empty($_GET['lider'])) { $where[] = 'r.lider_id = ?'; $params[] = (int)$_GET['lider']; }
        if (!empty($_GET['colaborador'])) { $where[] = 'r.colaborador_id = ?'; $params[] = (int)$_GET['colaborador']; }
        elseif (!empty($_GET['q'])) { $where[] = 'c.nombre LIKE ?'; $params[] = '%' . $_GET['q'] . '%'; }
        if (!empty($_GET['desde']) && fechaValida($_GET['desde'])) { $where[] = 'r.fecha >= ?'; $params[] = $_GET['desde']; }
        if (!empty($_GET['hasta']) && fechaValida($_GET['hasta'])) { $where[] = 'r.fecha <= ?'; $params[] = $_GET['hasta']; }
        $w = implode(' AND ', $where);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM dpo_registros r JOIN usuarios c ON c.id = r.colaborador_id WHERE $w");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT r.id, r.fecha, r.pilares, r.tipo, r.archivo, r.miniatura, r.duracion_seg, r.observacion, r.peso_bytes,
                                      c.nombre AS colaborador, l.nombre AS lider
                               FROM dpo_registros r
                               JOIN usuarios c ON c.id = r.colaborador_id
                               JOIN usuarios l ON l.id = r.lider_id
                               WHERE $w ORDER BY r.fecha DESC, r.id DESC
                               LIMIT $porPagina OFFSET " . (($pagina - 1) * $porPagina));
        $stmt->execute($params);
        responder(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total,
                   'pagina' => $pagina, 'paginas' => max(1, (int)ceil($total / $porPagina))]);

    // ------------------------------------------------------------------ Líderes
    case 'lideres':
        exigirAdmin();
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.cedula, u.cargo, l.activo
                               FROM dpo_lideres l JOIN usuarios u ON u.id = l.usuario_id
                               WHERE l.operacion_id = ? AND l.activo = 1 ORDER BY u.nombre");
        $stmt->execute([$op]);
        responder(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    case 'agregar_lider':
        exigirAdmin();
        $uid = (int)($_POST['usuario_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ? AND operacion_id = ? AND activo = 1");
        $stmt->execute([$uid, $op]);
        $nombre = $stmt->fetchColumn();
        if (!$nombre) fallar('Ese usuario no pertenece a la operación activa.');
        $pdo->prepare("INSERT INTO dpo_lideres (operacion_id, usuario_id, activo, creado_por) VALUES (?, ?, 1, ?)
                       ON DUPLICATE KEY UPDATE activo = 1")->execute([$op, $uid, $yo]);
        responder(['success' => true, 'message' => "$nombre ahora puede registrar entrenamientos."]);

    case 'quitar_lider':
        exigirAdmin();
        // Se desactiva (no se borra) para conservar el historial de sus registros
        $pdo->prepare("UPDATE dpo_lideres SET activo = 0 WHERE operacion_id = ? AND usuario_id = ?")
            ->execute([$op, (int)($_POST['usuario_id'] ?? 0)]);
        responder(['success' => true, 'message' => 'Líder retirado del calendario.']);

    // ------------------------------------------------------------------ Calendario
    case 'toggle_dia':
        exigirAdmin();
        $fecha = $_POST['fecha'] ?? '';
        if (!fechaValida($fecha)) fallar('Fecha inválida.');
        $del = $pdo->prepare("DELETE FROM dpo_dias_bloqueados WHERE operacion_id = ? AND fecha = ?");
        $del->execute([$op, $fecha]);
        if ($del->rowCount() === 0) {
            $pdo->prepare("INSERT INTO dpo_dias_bloqueados (operacion_id, fecha) VALUES (?, ?)")->execute([$op, $fecha]);
            responder(['success' => true, 'bloqueado' => true]);
        }
        responder(['success' => true, 'bloqueado' => false]);

    case 'toggle_celda':
        exigirAdmin();
        $fecha = $_POST['fecha'] ?? '';
        $lider = (int)($_POST['lider_id'] ?? 0);
        if (!fechaValida($fecha)) fallar('Fecha inválida.');
        $del = $pdo->prepare("DELETE FROM dpo_celdas_bloqueadas WHERE operacion_id = ? AND lider_id = ? AND fecha = ?");
        $del->execute([$op, $lider, $fecha]);
        if ($del->rowCount() === 0) {
            if (!dpoEsLider($pdo, $op, $lider)) fallar('Ese líder no está en el calendario de esta operación.');
            $pdo->prepare("INSERT INTO dpo_celdas_bloqueadas (operacion_id, lider_id, fecha) VALUES (?, ?, ?)")->execute([$op, $lider, $fecha]);
            responder(['success' => true, 'bloqueado' => true]);
        }
        responder(['success' => true, 'bloqueado' => false]);

    case 'registros_celda':
        exigirAdmin();
        $fecha = $_GET['fecha'] ?? '';
        if (!fechaValida($fecha)) fallar('Fecha inválida.');
        $stmt = $pdo->prepare("SELECT r.id, r.fecha, r.pilares, r.tipo, r.archivo, r.miniatura, r.duracion_seg, r.observacion, r.created_at, c.nombre AS colaborador
                               FROM dpo_registros r JOIN usuarios c ON c.id = r.colaborador_id
                               WHERE r.operacion_id = ? AND r.lider_id = ? AND r.fecha = ? ORDER BY r.created_at");
        $stmt->execute([$op, (int)($_GET['lider_id'] ?? 0), $fecha]);
        responder(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    case 'cambiar_fecha':
        exigirAdmin();
        $r = registroDeOperacion($pdo, $op, (int)($_POST['id'] ?? 0));
        $nueva = $_POST['fecha'] ?? '';
        if (!fechaValida($nueva) || $nueva > date('Y-m-d')) fallar('La nueva fecha no es válida.');
        $pdo->prepare("UPDATE dpo_registros SET fecha = ? WHERE id = ?")->execute([$nueva, $r['id']]);
        responder(['success' => true, 'message' => 'Registro movido al ' . date('d/m/Y', strtotime($nueva)) . '.']);

    case 'eliminar':
        exigirAdmin();
        $r = registroDeOperacion($pdo, $op, (int)($_POST['id'] ?? 0));
        $pdo->prepare("DELETE FROM dpo_registros WHERE id = ?")->execute([$r['id']]);
        dpoBorrarArchivos($r);
        responder(['success' => true, 'message' => 'Registro y evidencia eliminados.']);

    default:
        fallar('Acción no válida.');
    }
} catch (PDOException $e) {
    error_log('DPO: ' . $e->getMessage());
    fallar('Error de base de datos. Intenta de nuevo.', 500);
}
