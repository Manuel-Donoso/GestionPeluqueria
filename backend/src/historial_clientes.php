
<?php
/**
 * ==========================================================
 * HISTORIAL DE CLIENTES
 * ==========================================================
 *
 * Gestiona las observaciones del historial profesional
 * de cada cliente.
 *
 * Endpoints:
 * GET  /api/clientes/{id}/historial
 * POST /api/clientes/{id}/historial
 *
 * Todos los usuarios autenticados pueden consultar y añadir
 * observaciones al historial.
 */

// ==========================================================
// OBTENER HISTORIAL DE UN CLIENTE
// ==========================================================

/**
 * Obtiene todas las observaciones del historial de un cliente.
 *
 * @param int $id ID del cliente.
 */
function listarHistorialCliente(int $id): void
{
    // Validar identificador
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador del cliente no es válido'
        ]);
    }

    try {

        // Obtener conexión a la base de datos
        $pdo = getPDO();

        // Comprobar que el usuario está autenticado
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

        // Obtener las observaciones y el peluquero que las registró
        $stmt = $pdo->prepare("
            SELECT
                h.IDHistorial,
                h.observaciones,
                h.FK_Cliente,
                h.FK_peluquero,
                CONCAT_WS(
                    ' ',
                    p.Nombre,
                    p.Apellido1,
                    p.Apellido2
                ) AS peluquero
            FROM HISTORIALCLIENTES h
            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = h.FK_peluquero
            WHERE h.FK_Cliente = :id
            ORDER BY h.IDHistorial DESC
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Devolver el historial
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
 * El peluquero que realiza la operación se obtiene
 * de la sesión autenticada.
 *
 * @param int $id ID del cliente.
 */
function crearEntradaHistorialCliente(int $id): void
{
    // Validar identificador
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador del cliente no es válido'
        ]);
    }

    try {

        // Obtener conexión a la base de datos
        $pdo = getPDO();

        // Comprobar que el usuario está autenticado
        $usuario = authenticateUser($pdo);

        // Leer los datos enviados en el cuerpo de la petición
        $datos = json_decode(
            file_get_contents('php://input'),
            true
        );

        // Comprobar que el contenido recibido es un objeto JSON válido
        if (!is_array($datos)) {
            response(400, [
                'success' => false,
                'message' => 'El cuerpo de la petición no contiene datos JSON válidos'
            ]);
        }

        // Obtener y limpiar la observación
        $observaciones = trim($datos['observaciones'] ?? '');

        // Validar que la observación no esté vacía
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

        // Insertar la observación y el usuario responsable
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

        // Obtener el identificador de la nueva entrada
        $idHistorial = (int) $pdo->lastInsertId();

        // Responder indicando que se ha creado correctamente
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
