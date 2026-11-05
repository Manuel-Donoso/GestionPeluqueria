<?php
/**
 * Funciones para consultar y modificar el perfil del usuario autenticado.
 */

/**
 * Obtiene los datos del perfil del usuario actual.
 */
function obtenerPerfil(): void
{
    try {
        $pdo = getPDO();

        // Identificar al usuario mediante su token.
        $usuario = authenticateUser($pdo);

        $sql = "
            SELECT
                IDPeluquero,
                Usuario,
                Nombre,
                Apellido1,
                Apellido2,
                Telefono,
                Direccion,
                Email,
                Notas,
                FechaAlta
            FROM PELUQUEROS
            WHERE IDPeluquero = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'id' => $usuario['IDPeluquero']
        ]);

        $perfil = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$perfil) {
            response(404, [
                'status' => 'error',
                'message' => 'No se ha encontrado el perfil del usuario'
            ]);
        }

        response(200, [
            'status' => 'success',
            'data' => $perfil
        ]);

    } catch (PDOException $e) {
        error_log('Error de base de datos en perfil: ' . $e->getMessage());

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al consultar el perfil'
        ]);
    }
}
