<?php
/**
 * Funciones de autenticación y autorización de la API.
 */

/**
 * Obtiene el token Bearer de la cabecera Authorization.
 */
function getBearerToken(): string
{
    $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // Alternativa por compatibilidad con Apache.
    if ($authorization === '' && function_exists('getallheaders')) {
        $headers = getallheaders();
        $authorization = $headers['Authorization']
            ?? $headers['authorization']
            ?? '';
    }

    if (!preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $authorization, $matches)) {
        response(401, [
            'status' => 'error',
            'message' => 'Token de autenticación no válido o ausente'
        ]);
    }

    return $matches[1];
}

/**
 * Genera un token de acceso y guarda su hash en MariaDB.
 *
 * @return array Token original y fecha de expiración.
 */
function createAccessToken(PDO $pdo, int $idPeluquero): array
{
    // Generar 32 bytes aleatorios (64 caracteres hexadecimales).
    $token = bin2hex(random_bytes(32));

    // Guardar únicamente el hash SHA-256.
    $tokenHash = hash('sha256', $token);

    // El token tendrá una duración de 8 horas.
    $sql = "
        INSERT INTO TOKENS_ACCESO (
            FK_Peluquero,
            TokenHash,
            FechaExpiracion
        )
        VALUES (
            :id,
            :tokenHash,
            DATE_ADD(NOW(), INTERVAL 8 HOUR)
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'id' => $idPeluquero,
        'tokenHash' => $tokenHash
    ]);

    // Recuperar la fecha de expiración registrada.
    $stmt = $pdo->prepare("
        SELECT FechaExpiracion
        FROM TOKENS_ACCESO
        WHERE IDToken = :id
    ");

    $stmt->execute([
        'id' => $pdo->lastInsertId()
    ]);

    $fechaExpiracion = $stmt->fetchColumn();

    return [
        'token' => $token,
        'expires_at' => $fechaExpiracion
    ];
}

/**
 * Comprueba el token y devuelve los datos del peluquero.
 *
 * @return array Datos del usuario autenticado.
 */
function authenticateUser(PDO $pdo): array
{
    $token = getBearerToken();

    // Calcular el hash del token recibido.
    $tokenHash = hash('sha256', $token);

    $sql = "
        SELECT
            p.IDPeluquero,
            p.Usuario,
            p.Nombre,
            p.Apellido1,
            p.Apellido2,
            p.Email,
            p.FK_rol
        FROM TOKENS_ACCESO t
        INNER JOIN PELUQUEROS p
            ON p.IDPeluquero = t.FK_Peluquero
        WHERE
            t.TokenHash = :tokenHash
            AND t.Revocado = 0
            AND t.FechaExpiracion > NOW()
            AND p.baja = 0
            AND p.FechaBaja IS NULL
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'tokenHash' => $tokenHash
    ]);

    $usuario = $stmt->fetch();

    if (!$usuario) {
        response(401, [
            'status' => 'error',
            'message' => 'La sesión ha caducado o no es válida'
        ]);
    }

    return $usuario;
}

/**
 * Comprueba que el usuario autenticado sea administrador.
 *
 * El rol 1 corresponde a Administrador.
 *
 * @return array Datos del administrador autenticado.
 */
function requireAdmin(PDO $pdo): array
{
    $usuario = authenticateUser($pdo);

    if ((int) $usuario['FK_rol'] !== 1) {
        response(403, [
            'status' => 'error',
            'message' => 'No tienes permisos para realizar esta operación'
        ]);
    }

    return $usuario;
}

/**
 * Revoca el token de la sesión actual.
 */
function revokeCurrentToken(PDO $pdo): void
{
    // Comprobar primero que la sesión sea válida.
    authenticateUser($pdo);

    $token = getBearerToken();
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare("
        UPDATE TOKENS_ACCESO
        SET Revocado = 1
        WHERE TokenHash = :tokenHash
    ");

    $stmt->execute([
        'tokenHash' => $tokenHash
    ]);
}
