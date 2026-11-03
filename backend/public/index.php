<?php
/**
 * Peluquería API
 *
 * Punto de entrada único (Front Controller).
 * Recibe las peticiones HTTP y las dirige a sus endpoints.
 */
// ======================================================
// CONFIGURACIÓN CORS
// ======================================================

// Orígenes autorizados para el frontend durante desarrollo.
$allowedOrigins = [
    'http://localhost:8080',
    'http://127.0.0.1:8080'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

// Todas las respuestas de la API serán JSON.
header('Content-Type: application/json; charset=utf-8');

// ======================================================
// CARGA DE ARCHIVOS
// ======================================================

require_once __DIR__ . '/../src/response.php';
require_once __DIR__ . '/../src/database.php';
require_once __DIR__ . '/../src/health.php';

// ======================================================
// CONFIGURACIÓN GENERAL
// ======================================================

// Zona horaria.
date_default_timezone_set(
    getenv('APP_TIMEZONE') ?: 'Europe/Madrid'
);


// ======================================================
// GESTIÓN DE PETICIONES PREFLIGHT (CORS)
// ======================================================

// El navegador puede enviar OPTIONS antes de algunas peticiones.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ======================================================
// OBTENER PETICIÓN
// ======================================================

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

// ======================================================
// ENRUTAMIENTO
// ======================================================

// GET /
if ($method === 'GET' && $path === '/') {

    response(200, [
        'application' => 'Peluqueria API',
        'status' => 'running',
        'version' => '1.0.0'
    ]);
}

// GET /api
if ($method === 'GET' && $path === '/api') {

    response(200, [
        'name' => 'Peluqueria API',
        'version' => '1.0.0',
        'status' => 'running'
    ]);
}

// GET /api/health
if ($method === 'GET' && $path === '/api/health') {

    health();
}

// ======================================================
// ENDPOINT NO ENCONTRADO
// ======================================================

response(404, [
    'status' => 'error',
    'message' => 'Endpoint not found'
]);
