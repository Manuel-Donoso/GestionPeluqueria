<?php
/**
 * Endpoint: GET /api/health
 *
 * Comprueba que la API puede conectarse a MariaDB.
 */

function health(): void
{
    try {
        // Obtener conexión con MariaDB.
        $pdo = getPDO();

        // Comprobar que la base de datos responde.
        $pdo->query('SELECT 1');

        response(200, [
            'status' => 'ok',
            'api' => 'running',
            'database' => 'connected'
        ]);

    } catch (PDOException $e) {

        // Registrar el error internamente.
        error_log($e->getMessage());

        response(503, [
            'status' => 'error',
            'api' => 'running',
            'database' => 'disconnected'
        ]);
    }
}
