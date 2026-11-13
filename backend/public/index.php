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

require_once __DIR__ . '/../src/login.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/logout.php';
require_once __DIR__ . '/../src/me.php';

require_once __DIR__ . '/../src/peluqueros.php';
require_once __DIR__ . '/../src/perfil.php';
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


// POST /api/login
if ($method === 'POST' && $path === '/api/login') {

    login();
}

// POST /api/logout
if ($method === 'POST' && $path === '/api/logout') {

    logout();
}
// GET /api/me
if ($method === 'GET' && $path === '/api/me') {
    me();
}

// GET /api/peluqueros
if ($method === 'GET' && $path === '/api/peluqueros') {
    listarPeluqueros();
}

// GET /api/peluqueros/{id}
if (
    $method === 'GET'
    && preg_match('#^/api/peluqueros/(\d+)$#', $path, $matches)
) {
    obtenerPeluquero((int) $matches[1]);
}

// POST /api/peluqueros
if ($method === 'POST' && $path === '/api/peluqueros') {
    crearPeluquero();
}

// PUT /api/peluqueros/{id}
if ($method === 'PUT' && preg_match('#^/api/peluqueros/(\d+)$#', $path, $matches)) {
    actualizarPeluquero((int)$matches[1]);
}

// PATCH /api/peluqueros/{id}/baja
if ($method === 'PATCH' && preg_match('#^/api/peluqueros/(\d+)/baja$#', $path, $matches)) {
    darDeBajaPeluquero((int) $matches[1]);
}

// GET /api/perfil
if ($method === 'GET' && $path === '/api/perfil') {
    obtenerPerfil();
}

// PUT /api/perfil
if ($method === 'PUT' && $path === '/api/perfil') {
    actualizarPerfil();
}

// PUT /api/perfil/password
if ($method === 'PUT' && $path === '/api/perfil/password') {
    actualizarPasswordPerfil();
}

// PATCH /api/peluqueros/{id}/reactivar
if (
    $method === 'PATCH'
    && preg_match('#^/api/peluqueros/(\d+)/reactivar$#', $path, $matches)
) {
    reactivarPeluquero((int) $matches[1]);
}

// PATCH /api/peluqueros/{id}/reset-password
if (
    $method === 'PATCH'
    && preg_match('#^/api/peluqueros/(\d+)/reset-password$#', $path, $matches)
) {
    resetearPasswordPeluquero((int) $matches[1]);
}

// Obtener los roles disponibles.
if ($method === 'GET' && $path === '/api/roles') {
    listarRoles();
}

// PUT /api/password-reset
// Restablecer contraseña mediante un token temporal.
if ($method === 'PUT' && $path === '/api/password-reset') {
    cambiarPasswordRestablecimiento();
}

    
// ======================================================
// ENDPOINT NO ENCONTRADO
// ======================================================

response(404, [
    'status' => 'error',
    'message' => 'Endpoint not found'
]);
