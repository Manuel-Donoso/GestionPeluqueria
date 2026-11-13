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

/**
 * Cambia la contraseña del usuario autenticado.
 *
 * Al cambiarla, revoca todas sus sesiones activas.
 */
function actualizarPasswordPerfil(): void
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

        $passwordActual = $datos['password_actual'] ?? null;
        $passwordNueva = $datos['password_nueva'] ?? null;
        $confirmarPassword = $datos['confirmar_password'] ?? null;

        // Comprobar que se hayan enviado los tres campos.
        if (
            !is_string($passwordActual) || $passwordActual === ''
            || !is_string($passwordNueva) || $passwordNueva === ''
            || !is_string($confirmarPassword) || $confirmarPassword === ''
        ) {
            response(400, [
                'status' => 'error',
                'message' => 'Debes indicar la contraseña actual, la nueva y su confirmación'
            ]);
        }

        // Comprobar la longitud de la nueva contraseña.
        if (mb_strlen($passwordNueva) < 8) {
            response(400, [
                'status' => 'error',
                'message' => 'La nueva contraseña debe tener al menos 8 caracteres'
            ]);
        }

        // PASSWORD_DEFAULT utiliza actualmente bcrypt, que admite
        // un máximo de 72 bytes de contraseña.
        if (strlen($passwordNueva) > 72) {
            response(400, [
                'status' => 'error',
                'message' => 'La nueva contraseña no puede superar los 72 bytes'
            ]);
        }

        // Comprobar que ambas contraseñas nuevas coincidan.
        if ($passwordNueva !== $confirmarPassword) {
            response(400, [
                'status' => 'error',
                'message' => 'La nueva contraseña y su confirmación no coinciden'
            ]);
        }

        // Recuperar el hash de la contraseña actual.
        $stmt = $pdo->prepare("
            SELECT PW
            FROM PELUQUEROS
            WHERE IDPeluquero = :id
            LIMIT 1
        ");

        $stmt->execute([
            'id' => $usuario['IDPeluquero']
        ]);

        $hashActual = $stmt->fetchColumn();

        if (!$hashActual || !password_verify($passwordActual, $hashActual)) {
            response(403, [
                'status' => 'error',
                'message' => 'La contraseña actual no es correcta'
            ]);
        }

        // Evitar cambiar la contraseña por la misma.
        if (password_verify($passwordNueva, $hashActual)) {
            response(400, [
                'status' => 'error',
                'message' => 'La nueva contraseña debe ser diferente de la actual'
            ]);
        }

        // Generar el hash de la nueva contraseña.
        $nuevoHash = password_hash($passwordNueva, PASSWORD_DEFAULT);

        if ($nuevoHash === false) {
            response(500, [
                'status' => 'error',
                'message' => 'No se ha podido generar la nueva contraseña'
            ]);
        }

        // Actualizar contraseña y revocar sesiones en una transacción.
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            UPDATE PELUQUEROS
            SET PW = :password
            WHERE IDPeluquero = :id
        ");

        $stmt->execute([
            'password' => $nuevoHash,
            'id' => $usuario['IDPeluquero']
        ]);

        // Revocar todas las sesiones del usuario.
        $stmt = $pdo->prepare("
            UPDATE TOKENS_ACCESO
            SET Revocado = 1
            WHERE FK_Peluquero = :id
              AND Revocado = 0
        ");

        $stmt->execute([
            'id' => $usuario['IDPeluquero']
        ]);

        $pdo->commit();

        response(200, [
            'status' => 'success',
            'message' => 'Contraseña actualizada correctamente. Debes iniciar sesión de nuevo.'
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Error al cambiar la contraseña del perfil: ' . $e->getMessage());

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al cambiar la contraseña'
        ]);
    }
}



/**
 * Restablece la contraseña de un usuario mediante un token temporal.
 *
 * El token se genera durante el inicio de sesión cuando el administrador
 * ha solicitado el restablecimiento de la contraseña.
 *
 * No requiere un token de acceso normal.
 */
function cambiarPasswordRestablecimiento(): void
{
    // Leer los datos JSON enviados por el frontend.
    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        response(400, [
            'status' => 'error',
            'message' => 'La petición no contiene un JSON válido'
        ]);
    }

    // Obtener los datos recibidos.
    $token = $data['token'] ?? '';
    $passwordNueva = $data['password_nueva'] ?? '';
    $confirmarPassword = $data['confirmar_password'] ?? '';

    // Comprobar que se ha recibido un token válido.
    if (
        !is_string($token)
        || !preg_match('/^[a-f0-9]{64}$/i', $token)
    ) {
        response(400, [
            'status' => 'error',
            'message' => 'El token de restablecimiento no es válido'
        ]);
    }

    // Comprobar que se han introducido ambas contraseñas.
    if (
        !is_string($passwordNueva)
        || !is_string($confirmarPassword)
        || $passwordNueva === ''
        || $confirmarPassword === ''
    ) {
        response(400, [
            'status' => 'error',
            'message' => 'Debes introducir y confirmar la nueva contraseña'
        ]);
    }

    // Validar la longitud de la nueva contraseña.
    if (strlen($passwordNueva) < 8 || strlen($passwordNueva) > 72) {
        response(400, [
            'status' => 'error',
            'message' => 'La contraseña debe tener entre 8 y 72 bytes'
        ]);
    }

    // Comprobar que ambas contraseñas coinciden.
    if ($passwordNueva !== $confirmarPassword) {
        response(400, [
            'status' => 'error',
            'message' => 'Las contraseñas no coinciden'
        ]);
    }

    try {
        $pdo = getPDO();

        /*
         * Buscar los tokens temporales que todavía pueden utilizarse.
         *
         * IMPORTANTE:
         * La columna Token contiene un hash generado con password_hash().
         * Por ese motivo no podemos buscarlo directamente mediante SQL.
         * Tenemos que recuperar los tokens pendientes y comprobarlos
         * mediante password_verify().
         */
        $stmt = $pdo->prepare("
            SELECT
                IDRestablecimiento,
                FK_Peluquero,
                Token
            FROM RESTABLECIMIENTOS
            WHERE Utilizado = 0
              AND FechaExpiracion > NOW()
        ");

        $stmt->execute();

        $restablecimiento = null;

        // Comprobar el token recibido frente a los hashes almacenados.
        while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {

            if (password_verify($token, $registro['Token'])) {
                $restablecimiento = $registro;
                break;
            }
        }

        // Si no coincide con ningún token pendiente, rechazar la petición.
        if (!$restablecimiento) {
            response(400, [
                'status' => 'error',
                'message' => 'El token ha caducado, ya se ha utilizado o no es válido'
            ]);
        }

        // Generar el hash seguro de la nueva contraseña.
        $passwordHash = password_hash(
            $passwordNueva,
            PASSWORD_DEFAULT
        );

        // Iniciar la transacción para garantizar la integridad de los cambios.
        $pdo->beginTransaction();

        /*
         * Volver a comprobar el registro dentro de la transacción.
         * FOR UPDATE evita que dos peticiones consuman simultáneamente
         * el mismo token.
         */
        $check = $pdo->prepare("
            SELECT
                r.IDRestablecimiento,
                r.FK_Peluquero,
                r.Token
            FROM RESTABLECIMIENTOS r
            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = r.FK_Peluquero
            WHERE r.IDRestablecimiento = :id
              AND r.Utilizado = 0
              AND r.FechaExpiracion > NOW()
              AND p.Restaurar = 1
              AND p.baja = 0
              AND p.FechaBaja IS NULL
            FOR UPDATE
        ");

        $check->execute([
            'id' => $restablecimiento['IDRestablecimiento']
        ]);

        $registroValido = $check->fetch(PDO::FETCH_ASSOC);

        // Comprobar que el token sigue disponible y pertenece a un usuario activo.
        if (
            !$registroValido
            || !password_verify($token, $registroValido['Token'])
        ) {
            $pdo->rollBack();

            response(400, [
                'status' => 'error',
                'message' => 'El token ya no es válido o ha sido utilizado'
            ]);
        }

        $idPeluquero = (int) $registroValido['FK_Peluquero'];

        // Actualizar la contraseña y quitar la obligación de restablecerla.
        $updatePassword = $pdo->prepare("
            UPDATE PELUQUEROS
            SET
                PW = :password,
                Restaurar = 0
            WHERE IDPeluquero = :id
              AND Restaurar = 1
              AND baja = 0
              AND FechaBaja IS NULL
        ");

        $updatePassword->execute([
            'password' => $passwordHash,
            'id' => $idPeluquero
        ]);

        // Comprobar que se ha actualizado exactamente el usuario esperado.
        if ($updatePassword->rowCount() !== 1) {
            $pdo->rollBack();

            response(400, [
                'status' => 'error',
                'message' => 'No se ha podido actualizar la contraseña del usuario'
            ]);
        }

        // Marcar como utilizados todos los tokens pendientes del usuario.
        $invalidateResetTokens = $pdo->prepare("
            UPDATE RESTABLECIMIENTOS
            SET Utilizado = 1
            WHERE FK_Peluquero = :id
              AND Utilizado = 0
        ");

        $invalidateResetTokens->execute([
            'id' => $idPeluquero
        ]);

        // Revocar posibles sesiones anteriores del usuario.
        $revokeAccessTokens = $pdo->prepare("
            UPDATE TOKENS_ACCESO
            SET Revocado = 1
            WHERE FK_Peluquero = :id
              AND Revocado = 0
        ");

        $revokeAccessTokens->execute([
            'id' => $idPeluquero
        ]);

        // Confirmar todos los cambios.
        $pdo->commit();

        response(200, [
            'status' => 'ok',
            'message' => 'La contraseña se ha restablecido correctamente. Ya puedes iniciar sesión.'
        ]);

    } catch (PDOException $e) {

        // Deshacer los cambios si se produce un error de base de datos.
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error de base de datos al restablecer contraseña: '
            . $e->getMessage()
        );

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al restablecer la contraseña'
        ]);

    } catch (Throwable $e) {

        // Deshacer los cambios ante cualquier otro error.
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al restablecer contraseña: '
            . $e->getMessage()
        );

        response(500, [
            'status' => 'error',
            'message' => 'No se ha podido completar el restablecimiento'
        ]);
    }
}
