<?php
/**
 * Gestión del cierre de sesión.
 */

/**
 * Cierra la sesión del usuario autenticado.
 */
function logout(): void
{
    try {
        $pdo = getPDO();

        // Comprueba el token y lo revoca.
        revokeCurrentToken($pdo);

        response(200, [
            'status' => 'ok',
            'message' => 'Sesión cerrada correctamente'
        ]);

    } catch (PDOException $e) {
        error_log('Error de base de datos en logout: ' . $e->getMessage());

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al cerrar la sesión'
        ]);
    }
}
