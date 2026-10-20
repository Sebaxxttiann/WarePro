<?php
/**
 * Proxy de las hojas de cálculo de productividad.
 *
 * La API key de Google vive en el servidor (env WAREPRO_GOOGLE_API_KEY o
 * core/google_api_key.php, gitignored) y nunca se expone en el navegador.
 * El cliente solo nombra el reporte; las URLs completas quedan aquí.
 */
require_once '../../core/config.php';

verificarLogin();

header('Content-Type: application/json; charset=utf-8');

$reportes = [
    'productividad2' => [
        'spreadsheet' => '1vARa8PyXHJSKXRtzTRTfstSqbmD7hRD5nigPK1KiHjQ',
        'rango'       => 'DATOS',
    ],
    'productividades' => [
        'spreadsheet' => '14nmey9lC9GEe3zf9ncPDdA0-1UXyVB3r92X5rxemugU',
        'rango'       => 'CIRCUITO',
    ],
];

$nombre = $_GET['reporte'] ?? '';
if (!isset($reportes[$nombre])) {
    http_response_code(400);
    echo json_encode(['error' => ['message' => 'Reporte no válido.']]);
    exit;
}

$apiKey = getenv('WAREPRO_GOOGLE_API_KEY');
if (!$apiKey) {
    $archivoSecreto = __DIR__ . '/../../core/google_api_key.php';
    if (is_readable($archivoSecreto)) {
        // @ suprime el warning si el archivo deja de ser legible a mitad de
        // camino; el error real se registra abajo sin exponer la ruta al cliente.
        $apiKey = @include $archivoSecreto;
    }
}
if (!is_string($apiKey) || $apiKey === '') {
    error_log('WarePro Google API key not configured.');
    http_response_code(500);
    echo json_encode(['error' => ['message' => 'Servicio de reportes no configurado.']]);
    exit;
}

$conf  = $reportes[$nombre];
$url   = 'https://sheets.googleapis.com/v4/spreadsheets/' . rawurlencode($conf['spreadsheet'])
       . '/values/' . rawurlencode($conf['rango'])
       . '?key=' . rawurlencode($apiKey);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_FOLLOWLOCATION => true,
]);
$respuesta = curl_exec($ch);
$error     = curl_error($ch);
curl_close($ch);

if ($respuesta === false) {
    error_log('WarePro sheet proxy failed: ' . $error);
    http_response_code(502);
    echo json_encode(['error' => ['message' => 'No se pudo consultar la hoja de cálculo.']]);
    exit;
}

// Se reenvía la respuesta tal cual (incluye los errores de Google, que el
// front-end ya interpreta mediante data.error.message).
echo $respuesta;
