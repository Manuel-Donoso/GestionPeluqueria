<?php
/**
 * ==========================================================
 * FUNCIONES DE AUDITORÍA
 * ==========================================================
 *
 * Centraliza las funciones utilizadas para identificar
 * al usuario que realiza operaciones registradas
 * mediante los triggers de auditoría de MariaDB.
 */


/**
 * Establece el usuario que realiza la operación.
 *
 * Los triggers utilizan la variable de sesión
 * @usuario_actual para guardar el ID del peluquero
 * que ha realizado la modificación.
 *
 * IMPORTANTE:
 * Se debe utilizar la misma conexión PDO que posteriormente
 * ejecutará el INSERT, UPDATE o DELETE.
 */
function establecerUsuarioAuditoria(PDO $pdo, int $idPeluquero): void
{
    if ($idPeluquero <= 0) {
        throw new InvalidArgumentException(
            'El ID del usuario de auditoría no es válido'
        );
    }

    $stmt = $pdo->prepare("
        SET @usuario_actual = :idPeluquero
    ");

    $stmt->execute([
        ':idPeluquero' => $idPeluquero
    ]);
}
