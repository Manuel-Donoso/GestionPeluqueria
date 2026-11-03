<?php
/**
 * Funciones comunes para generar respuestas JSON.
 */

/**
 * Envía una respuesta HTTP en formato JSON
 * y finaliza la ejecución.
 *
 * @param int   $statusCode Código HTTP.
 * @param array $data       Datos de respuesta.
 */
function response(int $statusCode, array $data): void
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
    );

    exit;
}
