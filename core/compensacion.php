<?php
/**
 * Configuración de compensación variable por operación y cargo.
 * Los valores por defecto son los que estaban fijos en c_auxiliar / c_verificador / c_opm.
 */

const COMPENSACION_DEFAULTS = [
    // cargo => [etiqueta, monto_variable, monto_base, dias_habiles]
    'auxiliar'    => ['Auxiliar',               111000, 0,      25],
    'verificador' => ['Verificador',            252000, 0,      25],
    'operador'    => ['Operador de montacarga', 166162, 166162, 25],
];

function asegurarCompensacionBase(PDO $pdo, $operacionId): void {
    $operacionId = (int)$operacionId;
    if ($operacionId <= 0) return;
    $ins = $pdo->prepare("INSERT IGNORE INTO compensacion_config (operacion_id, cargo, monto_variable, monto_base, dias_habiles) VALUES (?, ?, ?, ?, ?)");
    foreach (COMPENSACION_DEFAULTS as $cargo => $d) {
        $ins->execute([$operacionId, $cargo, $d[1], $d[2], $d[3]]);
    }
}

/** Devuelve ['monto_variable','monto_base','dias_habiles'] del cargo en la operación. */
function obtenerCompensacion(PDO $pdo, $operacionId, string $cargo): array {
    $stmt = $pdo->prepare("SELECT monto_variable, monto_base, dias_habiles FROM compensacion_config WHERE operacion_id = ? AND cargo = ?");
    $stmt->execute([(int)$operacionId, $cargo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        asegurarCompensacionBase($pdo, $operacionId);
        $d = COMPENSACION_DEFAULTS[$cargo] ?? ['', 0, 0, 25];
        $row = ['monto_variable' => $d[1], 'monto_base' => $d[2], 'dias_habiles' => $d[3]];
    }
    return [
        'monto_variable' => (float)$row['monto_variable'],
        'monto_base'     => (float)$row['monto_base'],
        'dias_habiles'   => max(1, (int)$row['dias_habiles']),
    ];
}
