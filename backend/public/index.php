<?php
/**
 * Peluquería API - Micro-framework minimalista en PHP nativo.
 * Este script actúa como un punto de entrada único (Front Controller básico)
 * para gestionar rutas de una API REST orientada a un sistema de gestión de peluquería.
 */

// ======================================================
// CONFIGURACIÓN GENERAL
// ======================================================

// Define el encabezado HTTP para indicar que todas las respuestas serán en formato JSON con codificación UTF-8.
header('Content-Type: application/json; charset=utf-8');

//frontend está en http://localhost:8080 o http://127.0.0.1:8080:
header('Access-Control-Allow-Origin: http://localhost:8080'); // O '*' para permitir cualquier origen (útil en desarrollo)
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
// Configura la zona horaria por defecto de la aplicación.
// Intenta leer la variable de entorno 'APP_TIMEZONE'; si no existe, por defecto usa 'Europe/Madrid'.
date_default_timezone_set(
    getenv('APP_TIMEZONE') ?: 'Europe/Madrid'
);

// ======================================================
// CONFIGURACIÓN DE BASE DE DATOS
// ======================================================

// Parámetros de conexión recuperados de variables de entorno (ideales para Docker o entornos seguros)
// con valores por defecto orientados a desarrollo local.
$dbHost = getenv('DB_HOST') ?: 'database';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'peluqueria';
$dbUser = getenv('DB_USER') ?: 'peluqueria_app';
$dbPassword = getenv('DB_PASSWORD') ?: '';

// ======================================================
// FUNCIÓN PARA DEVOLVER RESPUESTAS JSON
// ======================================================

/**
 * Envía una respuesta HTTP en formato JSON y detiene la ejecución del script.
 *
 * @param int   $statusCode Código de estado HTTP (ej. 200, 404, 503)
 * @param array $data       Datos a convertir en JSON para el cliente
 * @return void
 */
function response(int $statusCode, array $data): void
{
    // Asigna el código de estado HTTP correspondiente a la respuesta
    http_response_code($statusCode);

    // Convierte el array asociativo a JSON.
    // JSON_PRETTY_PRINT: Formatea el JSON con sangría para facilitar su lectura humana.
    // JSON_UNESCAPED_UNICODE: Evita que tildes y caracteres especiales se escapen como secuencias Unicode (ej. \u00e1).
    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    // Finaliza inmediatamente la ejecución del script PHP
    exit;
}

// ======================================================
// OBTENER PETICIÓN (ENRUTAMIENTO BÁSICO)
// ======================================================

// Extrae exclusivamente la ruta solicitada de la URL (omitiendo dominio y parámetros GET).
$path = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

// Captura el método HTTP utilizado en la petición (GET, POST, etc.).
$method = $_SERVER['REQUEST_METHOD'];

// ======================================================
// ENDPOINT: GET /
// ======================================================

// Comprueba si la petición es un GET a la ruta raíz '/'
if ($method === 'GET' && $path === '/') {

    response(200, [
        'application' => 'Peluqueria API',
        'status' => 'running',
        'version' => '1.0.0'
    ]);
}

// ======================================================
// ENDPOINT: GET /api
// ======================================================

// Comprueba si la petición es un GET a la ruta base de la API '/api'
if ($method === 'GET' && $path === '/api') {

    response(200, [
        'name' => 'Peluqueria API',
        'version' => '1.0.0',
        'status' => 'running'
    ]);
}

// ======================================================
// ENDPOINT: GET /api/health (COMPROBACIÓN DE SALUD)
// ======================================================

// Comprueba si la petición solicita el estado del sistema y de la base de datos
if ($method === 'GET' && $path === '/api/health') {

    try {
        // Construye el Data Source Name (DSN) necesario para conectar con MySQL/MariaDB vía PDO
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

        // Instancia la conexión PDO utilizando las credenciales configuradas
        $pdo = new PDO(
            $dsn,
            $dbUser,
            $dbPassword,
            [
                // Lanza excepciones en caso de error de base de datos
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Devuelve los resultados como arrays asociativos por defecto
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // Ejecuta una consulta ligera para verificar que el motor de base de datos responde de verdad
        $pdo->query('SELECT 1');

        // Si todo va bien, devuelve un estado HTTP 200 confirmando que API y DB están operativas
        response(200, [
            'status' => 'ok',
            'api' => 'running',
            'database' => 'connected'
        ]);

    } catch (PDOException $e) {
        // En caso de fallo de conexión o consulta, captura la excepción

        // Registra el error detallado de forma interna en los logs del servidor (seguridad: no se expone al cliente)
        error_log($e->getMessage());

        // Devuelve una respuesta HTTP 503 (Servicio no disponible) informando que la base de datos está desconectada
        response(503, [
            'status' => 'error',
            'api' => 'running',
            'database' => 'disconnected'
        ]);
    }
}

// ======================================================
// ENDPOINT NO ENCONTRADO (FALLBACK 404)
// ======================================================

// Si ninguna de las condiciones anteriores se cumple, significa que la ruta o método no existe.
// Se responde por defecto con un código HTTP 404.
response(404, [
    'status' => 'error',
    'message' => 'Endpoint not found'
]);

