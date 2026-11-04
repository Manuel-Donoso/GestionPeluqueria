<?php
/**
 * Devuelve los datos del usuario autenticado.
 */
function me(): void
{
    try {
        $pdo = getPDO();

        // Comprueba el token y obtiene el usuario autenticado.
        $usuario = authenticateUser($pdo);

        response(200, [
            'status' => 'ok',
            'message' => 'Usuario autenticado correctamente',
            'usuario' => [
                'id' => (int) $usuario['IDPeluquero'],
                'usuario' => $usuario['Usuario'],
                'nombre' => $usuario['Nombre'],
                'apellido1' => $usuario['Apellido1'],
                'apellido2' => $usuario['Apellido2'],
                'email' => $usuario['Email'],
                'rol' => (int) $usuario['FK_rol']
            ]
        ]);

    } catch (PDOException $e) {
        error_log('Error de base de datos en me: ' . $e->getMessage());

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al consultar el usuario'
        ]);
    }
}
