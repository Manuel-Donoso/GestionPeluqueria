<?php
/**
 * Gestión del inicio de sesión de peluqueros.
 *
 * Permite:
 * - Acceder mediante Usuario o Email.
 * - Solicitar restablecimiento de contraseña autorizado.
 * - Generar un token de acceso al iniciar sesión.
 */

/**
 * Procesa el inicio de sesión.
 */
function login(): void
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

    // Obtener identificador y contraseña.
    $identificador = trim($data['identificador'] ?? '');
    $password = $data['password'] ?? '';

    if ($identificador === '') {
        response(400, [
            'status' => 'error',
            'message' => 'Debes introducir tu usuario o correo electrónico'
        ]);
    }

    try {
        $pdo = getPDO();

        // Buscar al peluquero por usuario o email.
        $sql = "
            SELECT
                IDPeluquero,
                Usuario,
                Nombre,
                Apellido1,
                Apellido2,
                Email,
                PW,
                Restarurar,
                FK_rol
            FROM PELUQUEROS
            WHERE
                (Usuario = :usuario OR Email = :email)
                AND baja = 0
                AND FechaBaja IS NULL
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'usuario' => $identificador,
            'email' => $identificador
        ]);

        $usuario = $stmt->fetch();

        // No revelar si el usuario existe.
        if (!$usuario) {
            response(401, [
                'status' => 'error',
                'message' => 'Credenciales incorrectas'
            ]);
        }

        // Comprobar si el administrador ha autorizado
        // el restablecimiento de contraseña.
        if ((bool) $usuario['Restarurar']) {

            // Para restablecer la contraseña se exige
            // introducir el nombre de usuario.
            if ($identificador !== $usuario['Usuario']) {
                response(403, [
                    'status' => 'error',
                    'message' => 'Debes introducir tu nombre de usuario para restablecer la contraseña',
                    'action' => 'password_reset_requires_username'
                ]);
            }

            // Generar un token temporal de 32 bytes.
            $token = bin2hex(random_bytes(32));

            // Guardar únicamente el hash del token.
            $tokenHash = password_hash(
                $token,
                PASSWORD_DEFAULT
            );

            $pdo->beginTransaction();

            try {
                // Invalidar tokens anteriores del peluquero.
                $invalidate = $pdo->prepare("
                    UPDATE RESTABLECIMIENTOS
                    SET Utilizado = 1
                    WHERE FK_Peluquero = :id
                    AND Utilizado = 0
                ");

                $invalidate->execute([
                    'id' => $usuario['IDPeluquero']
                ]);

                // Crear un nuevo token temporal de 15 minutos.
                $insert = $pdo->prepare("
                    INSERT INTO RESTABLECIMIENTOS (
                        FK_Peluquero,
                        Token,
                        FechaExpiracion,
                        Utilizado
                    )
                    VALUES (
                        :id,
                        :token,
                        DATE_ADD(NOW(), INTERVAL 15 MINUTE),
                        0
                    )
                ");

                $insert->execute([
                    'id' => $usuario['IDPeluquero'],
                    'token' => $tokenHash
                ]);

                $pdo->commit();

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                throw $e;
            }

            response(200, [
                'status' => 'ok',
                'action' => 'password_reset_required',
                'message' => 'Debes establecer una nueva contraseña',
                'token' => $token,
                'expires_in' => 900
            ]);
        }

        // Login normal: comprobar que se ha enviado contraseña.
        if (!is_string($password) || $password === '') {
            response(400, [
                'status' => 'error',
                'message' => 'Debes introducir la contraseña'
            ]);
        }

        // Comprobar la contraseña.
        if (!password_verify($password, $usuario['PW'])) {
            response(401, [
                'status' => 'error',
                'message' => 'Credenciales incorrectas'
            ]);
        }

        // Generar token de acceso de 8 horas.
        $accessToken = createAccessToken(
            $pdo,
            (int) $usuario['IDPeluquero']
        );

        // Devolver los datos del usuario y el token.
        response(200, [
            'status' => 'ok',
            'message' => 'Inicio de sesión correcto',
            'token' => $accessToken['token'],
            'expires_at' => $accessToken['expires_at'],
            'usuario' => [
                'id' => (int) $usuario['IDPeluquero'],
                'usuario' => $usuario['Usuario'],
                'nombre' => $usuario['Nombre'],
                'apellido1' => $usuario['Apellido1'],
                'apellido2' => $usuario['Apellido2'],
                'rol' => (int) $usuario['FK_rol']
            ]
        ]);

    } catch (PDOException $e) {
        error_log('Error de base de datos en login: ' . $e->getMessage());

        response(500, [
            'status' => 'error',
            'message' => 'Error interno al procesar el inicio de sesión'
        ]);

    } catch (Throwable $e) {
        error_log('Error en login: ' . $e->getMessage());

        response(500, [
            'status' => 'error',
            'message' => 'No se ha podido procesar la petición'
        ]);
    }
}
