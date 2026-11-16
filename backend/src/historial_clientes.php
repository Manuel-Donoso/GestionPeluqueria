<?php
/**
 * ==========================================================
 * HISTORIAL DE CLIENTES
 * ==========================================================
 *
 * Gestiona las observaciones del historial profesional
 * de cada cliente.
 *
 * Endpoints previstos:
 * GET    /api/clientes/{id}/historial
 * POST   /api/clientes/{id}/historial
 * PUT    /api/historial/{id}
 * PATCH  /api/historial/{id}/eliminar
 * GET    /api/historial/{id}/auditoria
 *
 * Todos los usuarios autenticados pueden consultar,
 * añadir, modificar y eliminar lógicamente observaciones.
 *
 * Las modificaciones y eliminaciones se registran en
 * HISTORIALCLIENTES_AUDITORIA.
 */


// ==========================================================
// OBTENER HISTORIAL DE UN CLIENTE
// ==========================================================

/**
 * Obtiene las observaciones activas del historial de un cliente.
 *
 * Las observaciones eliminadas lógicamente no se muestran.
 *
 * @param int $id ID del cliente.
 */
function listarHistorialCliente(int $id): void
{
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador del cliente no es válido'
        ]);
    }

    try {

        $pdo = getPDO();

        // Comprobar autenticación
        authenticateUser($pdo);

        // Comprobar que existe el cliente
        $stmt = $pdo->prepare("
            SELECT IDCliente
            FROM CLIENTES
            WHERE IDCliente = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            response(404, [
                'success' => false,
                'message' => 'El cliente no existe'
            ]);
        }

        // Obtener únicamente las observaciones no eliminadas
        $stmt = $pdo->prepare("
            SELECT
                h.IDHistorial,
                h.observaciones,
                h.FechaCreacion,
                h.FechaModificacion,
                h.FK_Cliente,
                h.FK_peluquero,

                CONCAT_WS(
                    ' ',
                    p.Nombre,
                    p.Apellido1,
                    p.Apellido2
                ) AS peluquero,

                CONCAT_WS(
                    ' ',
                    pm.Nombre,
                    pm.Apellido1,
                    pm.Apellido2
                ) AS modificadoPor

            FROM HISTORIALCLIENTES h

            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = h.FK_peluquero

            LEFT JOIN PELUQUEROS pm
                ON pm.IDPeluquero = h.FK_modificadoPor

            WHERE h.FK_Cliente = :id
              AND h.Eliminado = FALSE

            ORDER BY h.IDHistorial DESC
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response(200, [
            'success' => true,
            'total' => count($historial),
            'historial' => $historial
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al consultar el historial del cliente: ' . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'No se ha podido consultar el historial del cliente'
        ]);
    }
}


// ==========================================================
// AÑADIR OBSERVACIÓN AL HISTORIAL
// ==========================================================

/**
 * Añade una observación al historial de un cliente.
 *
 * @param int $id ID del cliente.
 */
function crearEntradaHistorialCliente(int $id): void
{
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador del cliente no es válido'
        ]);
    }

    try {

        $pdo = getPDO();

        // Obtener usuario autenticado
        $usuario = authenticateUser($pdo);

        // Leer los datos recibidos
        $datos = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($datos)) {
            response(400, [
                'success' => false,
                'message' => 'El cuerpo de la petición no contiene datos JSON válidos'
            ]);
        }

        $observaciones = trim($datos['observaciones'] ?? '');

        if ($observaciones === '') {
            response(400, [
                'success' => false,
                'message' => 'La observación no puede estar vacía'
            ]);
        }

        // Comprobar que existe el cliente
        $stmt = $pdo->prepare("
            SELECT IDCliente
            FROM CLIENTES
            WHERE IDCliente = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            response(404, [
                'success' => false,
                'message' => 'El cliente no existe'
            ]);
        }

        // Insertar la observación
        $stmt = $pdo->prepare("
            INSERT INTO HISTORIALCLIENTES (
                observaciones,
                FK_Cliente,
                FK_peluquero
            ) VALUES (
                :observaciones,
                :idCliente,
                :idPeluquero
            )
        ");

        $stmt->execute([
            ':observaciones' => $observaciones,
            ':idCliente' => $id,
            ':idPeluquero' => (int) $usuario['IDPeluquero']
        ]);

        $idHistorial = (int) $pdo->lastInsertId();

        response(201, [
            'success' => true,
            'message' => 'La observación se ha añadido correctamente al historial',
            'id' => $idHistorial
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al añadir una entrada al historial del cliente: ' . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'No se ha podido añadir la observación al historial'
        ]);
    }
}


// ==========================================================
// MODIFICAR UNA OBSERVACIÓN
// ==========================================================

/**
 * Modifica una observación existente.
 *
 * Antes de actualizarla, guarda el contenido anterior y
 * el nuevo en HISTORIALCLIENTES_AUDITORIA.
 *
 * @param int $id ID de la entrada del historial.
 */
function actualizarEntradaHistorialCliente(int $id): void
{
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la observación no es válido'
        ]);
    }

    try {

        $pdo = getPDO();

        // Obtener usuario autenticado
        $usuario = authenticateUser($pdo);

        // Leer los datos recibidos
        $datos = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($datos)) {
            response(400, [
                'success' => false,
                'message' => 'El cuerpo de la petición no contiene datos JSON válidos'
            ]);
        }

        $observaciones = trim($datos['observaciones'] ?? '');
        $motivo = trim($datos['motivo'] ?? '');

        if ($observaciones === '') {
            response(400, [
                'success' => false,
                'message' => 'La observación no puede estar vacía'
            ]);
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Obtener la observación y bloquearla durante la operación
        $stmt = $pdo->prepare("
            SELECT
                IDHistorial,
                observaciones,
                Eliminado
            FROM HISTORIALCLIENTES
            WHERE IDHistorial = :id
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $entrada = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entrada) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La observación no existe'
            ]);
        }

        // No permitir modificar entradas eliminadas
        if ((bool) $entrada['Eliminado']) {
            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'No se puede modificar una observación eliminada'
            ]);
        }

        $observacionesAnteriores = $entrada['observaciones'];

        // Evitar registrar modificaciones si el texto no cambia
        if ($observaciones === $observacionesAnteriores) {
            $pdo->rollBack();

            response(400, [
                'success' => false,
                'message' => 'La nueva observación es idéntica a la actual'
            ]);
        }

        // Registrar la modificación en la auditoría
        $stmt = $pdo->prepare("
            INSERT INTO HISTORIALCLIENTES_AUDITORIA (
                FK_Historial,
                operacion,
                observaciones_anteriores,
                observaciones_nuevas,
                FK_peluquero,
                motivo
            ) VALUES (
                :idHistorial,
                'MODIFICACION',
                :anteriores,
                :nuevas,
                :idPeluquero,
                :motivo
            )
        ");

        $stmt->execute([
            ':idHistorial' => $id,
            ':anteriores' => $observacionesAnteriores,
            ':nuevas' => $observaciones,
            ':idPeluquero' => (int) $usuario['IDPeluquero'],
            ':motivo' => $motivo !== '' ? $motivo : null
        ]);

        // Actualizar la observación
        $stmt = $pdo->prepare("
            UPDATE HISTORIALCLIENTES
            SET
                observaciones = :observaciones,
                FechaModificacion = CURRENT_TIMESTAMP,
                FK_modificadoPor = :idPeluquero
            WHERE IDHistorial = :id
        ");

        $stmt->execute([
            ':observaciones' => $observaciones,
            ':idPeluquero' => (int) $usuario['IDPeluquero'],
            ':id' => $id
        ]);

        // Confirmar ambas operaciones
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'La observación se ha modificado correctamente'
        ]);

    } catch (PDOException $e) {

        // Revertir los cambios si la transacción sigue abierta
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al modificar una entrada del historial: ' . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'No se ha podido modificar la observación'
        ]);
    }
}


// ==========================================================
// ELIMINAR LÓGICAMENTE UNA OBSERVACIÓN
// ==========================================================

/**
 * Marca una observación como eliminada sin borrarla físicamente.
 *
 * Conserva el texto original y registra el usuario, la fecha
 * y el motivo de la eliminación.
 *
 * @param int $id ID de la entrada del historial.
 */
function eliminarEntradaHistorialCliente(int $id): void
{
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la observación no es válido'
        ]);
    }

    try {

        $pdo = getPDO();

        // Obtener usuario autenticado
        $usuario = authenticateUser($pdo);

        // Leer el motivo de la eliminación
        $datos = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($datos)) {
            response(400, [
                'success' => false,
                'message' => 'El cuerpo de la petición no contiene datos JSON válidos'
            ]);
        }

        $motivo = trim($datos['motivo'] ?? '');

        // Exigir un motivo para eliminar una anotación profesional
        if ($motivo === '') {
            response(400, [
                'success' => false,
                'message' => 'Debes indicar el motivo de la eliminación'
            ]);
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Obtener y bloquear la observación
        $stmt = $pdo->prepare("
            SELECT
                IDHistorial,
                observaciones,
                Eliminado
            FROM HISTORIALCLIENTES
            WHERE IDHistorial = :id
            FOR UPDATE
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $entrada = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entrada) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La observación no existe'
            ]);
        }

        // Comprobar que no se haya eliminado anteriormente
        if ((bool) $entrada['Eliminado']) {
            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'La observación ya está eliminada'
            ]);
        }

        // Registrar la eliminación en la auditoría
        $stmt = $pdo->prepare("
            INSERT INTO HISTORIALCLIENTES_AUDITORIA (
                FK_Historial,
                operacion,
                observaciones_anteriores,
                observaciones_nuevas,
                FK_peluquero,
                motivo
            ) VALUES (
                :idHistorial,
                'ELIMINACION',
                :anteriores,
                NULL,
                :idPeluquero,
                :motivo
            )
        ");

        $stmt->execute([
            ':idHistorial' => $id,
            ':anteriores' => $entrada['observaciones'],
            ':idPeluquero' => (int) $usuario['IDPeluquero'],
            ':motivo' => $motivo
        ]);

        // Realizar la eliminación lógica
        $stmt = $pdo->prepare("
            UPDATE HISTORIALCLIENTES
            SET
                Eliminado = TRUE,
                FechaEliminacion = CURRENT_TIMESTAMP,
                FK_eliminadoPor = :idPeluquero,
                MotivoEliminacion = :motivo
            WHERE IDHistorial = :id
        ");

        $stmt->execute([
            ':idPeluquero' => (int) $usuario['IDPeluquero'],
            ':motivo' => $motivo,
            ':id' => $id
        ]);

        // Confirmar ambas operaciones
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'La observación se ha eliminado correctamente'
        ]);

    } catch (PDOException $e) {

        // Revertir los cambios si la transacción sigue abierta
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al eliminar una entrada del historial: ' . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'No se ha podido eliminar la observación'
        ]);
    }
}


// ==========================================================
// CONSULTAR AUDITORÍA DE UNA OBSERVACIÓN
// ==========================================================

/**
 * Obtiene todas las modificaciones y eliminaciones
 * realizadas sobre una entrada del historial.
 *
 * @param int $id ID de la entrada del historial.
 */
function listarAuditoriaHistorialCliente(int $id): void
{
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la observación no es válido'
        ]);
    }

    try {

        $pdo = getPDO();

        // Comprobar autenticación
        authenticateUser($pdo);

        // Comprobar que existe la observación
        $stmt = $pdo->prepare("
            SELECT IDHistorial
            FROM HISTORIALCLIENTES
            WHERE IDHistorial = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            response(404, [
                'success' => false,
                'message' => 'La observación no existe'
            ]);
        }

        // Obtener todas las operaciones registradas
        $stmt = $pdo->prepare("
            SELECT
                a.IDAuditoriaHistorial,
                a.FK_Historial,
                a.operacion,
                a.observaciones_anteriores,
                a.observaciones_nuevas,
                a.motivo,
                a.FechaOperacion,
                a.FK_peluquero,

                CONCAT_WS(
                    ' ',
                    p.Nombre,
                    p.Apellido1,
                    p.Apellido2
                ) AS peluquero

            FROM HISTORIALCLIENTES_AUDITORIA a

            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = a.FK_peluquero

            WHERE a.FK_Historial = :id

            ORDER BY a.FechaOperacion DESC, a.IDAuditoriaHistorial DESC
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $auditoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response(200, [
            'success' => true,
            'total' => count($auditoria),
            'auditoria' => $auditoria
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al consultar la auditoría del historial: ' . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'No se ha podido consultar la auditoría de la observación'
        ]);
    }
}
