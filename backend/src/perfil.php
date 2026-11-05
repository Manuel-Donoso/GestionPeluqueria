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


/**
 * Actualiza los datos personales del usuario autenticado.
 */
function actualizarPerfil(): void
{
    try {
        $pdo = getPDO();

        // Identificar al usuario mediante su token.
        $usuario = authenticateUser($pdo);

        // Obtener los datos enviados.
        $datos = json_decode(file_get_contents('php://input'), true);

        if (!is_array($datos)) {
            response(400, [
                'status' => 'error',
                'message' => 'El cuerpo de la petición debe ser un JSON válido'
            ]);
        }

        // Campos obligatorios.
        $obligatorios = ['Nombre', 'Apellido1'];

        foreach ($obligatorios as $campo) {
            if (
                !isset($datos[$campo])
                || !is_string($datos[$campo])
                || trim($datos[$campo]) === ''
            ) {
                response(400, [
                    'status' => 'error',
                    'message' => "El campo {$campo} es obligatorio"
                ]);
            }
        }

        // Campos personales permitidos.
        $campos = [
            'Nombre' => 50,
            'Apellido1' => 50,
            'Apellido2' => 50,
            'Telefono' => 20,
            'Direccion' => 255,
            'Email' => 100,
        ];

        $valores = [];

        foreach ($campos as $campo => $longitudMaxima) {
            $valor = $datos[$campo] ?? null;

            if ($valor !== null && !is_string($valor)) {
                response(400, [
                    'status' => 'error',
                    'message' => "El campo {$campo} debe ser texto o null"
                ]);
            }

            if ($valor !== null) {
                $valor = trim($valor);

                if (mb_strlen($valor) > $longitudMaxima) {
                    response(400, [
                        'status' => 'error',
                        'message' => "El campo {$campo} supera los {$longitudMaxima} caracteres"
                    ]);
                }
            }

            $valores[$campo] = $valor === '' ? null : $valor;
        }

        // El nombre y primer apellido no pueden quedar vacíos.
        if ($valores['Nombre'] === null || $valores['Apellido1'] === null) {
            response(400, [
                'status' => 'error',
                'message' => 'El nombre y el primer apellido no pueden estar vacíos'
            ]);
        }

        // Validar formato del email.
        if (
            $valores['Email'] !== null
            && !filter_var($valores['Email'], FILTER_VALIDATE_EMAIL)
        ) {
            response(400, [
                'status' => 'error',
                'message' => 'El formato del email no es válido'
            ]);
        }

        // Validar longitud de las notas.
        $notas = $datos['Notas'] ?? null;

        if ($notas !== null && !is_string($notas)) {
            response(400, [
                'status' => 'error',
                'message' => 'El campo Notas debe ser texto o null'
            ]);
        }

        // Comprobar que el email no pertenezca a otro usuario.
        if ($valores['Email'] !== null) {
            $stmt = $pdo->prepare("
                SELECT IDPeluquero
                FROM PELUQUEROS
                WHERE Email = :email
                  AND IDPeluquero != :id
                LIMIT 1
            ");

            $stmt->execute([
                'email' => $valores['Email'],
                'id' => $usuario['IDPeluquero']
            ]);

            if ($stmt->fetch()) {
                response(409, [
                    'status' => 'error',
                    'message' => 'El email ya está registrado por otro usuario'
                ]);
            }
        }

        // Actualizar únicamente los campos permitidos.
        $sql = "
            UPDATE PELUQUEROS
            SET
                Nombre = :nombre,
                Apellido1 = :apellido1,
                Apellido2 = :apellido2,
                Telefono = :telefono,
                Direccion = :direccion,
                Email = :email,
                Notas = :notas
            WHERE IDPeluquero = :id
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'nombre' => $valores['Nombre'],
            'apellido1' => $valores['Apellido1'],
            'apellido2' => $valores['Apellido2'],
            'telefono' => $valores['Telefono'],
            'direccion' => $valores['Direccion'],
            'email' => $valores['Email'],
            'notas' => $notas === '' ? null : $notas,
            'id' => $usuario['IDPeluquero']
        ]);

        // Recuperar el perfil actualizado.
        obtenerPerfil();

    } catch (PDOException $e) {
        error_log('Error de base de datos al actualizar perfil: ' . $e->getMessage());

        // El índice UNIQUE del email también protege frente a
        // actualizaciones simultáneas.
        if ($e->getCode() === '23000') {
            response(409, [
                'status' => 'error',
                'message' => 'No se ha podido actualizar el perfil por un conflicto de datos'
            ]);
        }

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al actualizar el perfil'
        ]);
    }
}
