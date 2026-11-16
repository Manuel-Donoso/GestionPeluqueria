<?php
/**
 * ==========================================================
 * GESTIÓN DE CLIENTES
 * ==========================================================
 *
 * Funciones disponibles:
 *
 * GET    /api/clientes
 * GET    /api/clientes/{id}
 * POST   /api/clientes
 * PUT    /api/clientes/{id}
 * PATCH  /api/clientes/{id}/baja
 * PATCH  /api/clientes/{id}/reactivar
 *
 * Todos los roles autenticados pueden gestionar clientes.
 *
 * Los clientes se eliminan mediante baja lógica para
 * conservar sus relaciones e historial.
 *
 * El teléfono es opcional y puede estar compartido
 * por varios clientes.
 */


/**
 * ==========================================================
 * LISTAR CLIENTES
 * ==========================================================
 *
 * Permite:
 * - Buscar por nombre, apellidos, teléfono o email.
 * - Filtrar por activos, bajas o todos.
 *
 * Parámetros GET:
 * - busqueda
 * - estado: activos | bajas | todos
 */
function listarClientes(): void
{
    $pdo = getPDO();

    // Comprobar que el usuario está autenticado.
    authenticateUser($pdo);

    try {

        $busqueda = trim($_GET['busqueda'] ?? '');
        $estado = $_GET['estado'] ?? 'todos';

        // Consulta inicial.
        $sql = "
            SELECT
                IDCliente,
                Nombre,
                Apellido1,
                Apellido2,
                Telefono,
                Direccion,
                Email,
                Notas,
                FechaAlta,
                FechaBaja,
                baja
            FROM CLIENTES
            WHERE 1 = 1
        ";

        $params = [];

        // Aplicar filtro de búsqueda.
        if ($busqueda !== '') {

            $sql .= "
                AND (
                    Nombre LIKE :busqueda
                    OR Apellido1 LIKE :busqueda
                    OR Apellido2 LIKE :busqueda
                    OR Telefono LIKE :busqueda
                    OR Email LIKE :busqueda
                )
            ";

            $params[':busqueda'] = '%' . $busqueda . '%';
        }

        // Aplicar filtro de estado.
        if ($estado === 'activos') {

            $sql .= " AND baja = FALSE";

        } elseif ($estado === 'bajas') {

            $sql .= " AND baja = TRUE";
        }

        // Ordenar alfabéticamente.
        $sql .= " ORDER BY Apellido1 ASC, Nombre ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response(200, [
            'success' => true,
            'clientes' => $clientes
        ]);

    } catch (PDOException $e) {

        error_log('Error al listar clientes: ' . $e->getMessage());

        response(500, [
            'success' => false,
            'message' => 'Error al obtener los clientes'
        ]);
    }
}


/**
 * ==========================================================
 * OBTENER CLIENTE
 * ==========================================================
 *
 * Devuelve los datos de un cliente concreto.
 */
function obtenerCliente(int $id): void
{
    $pdo = getPDO();

    authenticateUser($pdo);

    try {

        $stmt = $pdo->prepare("
            SELECT
                IDCliente,
                Nombre,
                Apellido1,
                Apellido2,
                Telefono,
                Direccion,
                Email,
                Notas,
                FechaAlta,
                FechaBaja,
                baja
            FROM CLIENTES
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {

            response(404, [
                'success' => false,
                'message' => 'El cliente no existe'
            ]);
        }

        response(200, [
            'success' => true,
            'cliente' => $cliente
        ]);

    } catch (PDOException $e) {

        error_log('Error al obtener cliente: ' . $e->getMessage());

        response(500, [
            'success' => false,
            'message' => 'Error al obtener el cliente'
        ]);
    }
}


/**
 * ==========================================================
 * CREAR CLIENTE
 * ==========================================================
 *
 * Campos obligatorios:
 * - Nombre
 * - Apellido1
 *
 * Campos opcionales:
 * - Apellido2
 * - Telefono
 * - Direccion
 * - Email
 * - Notas
 *
 * El teléfono no es único y puede compartirse.
 */

function crearCliente(): void
{
    $pdo = getPDO();

    authenticateUser($pdo);

    try {

        // Obtener los datos enviados en formato JSON.
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {

            response(400, [
                'success' => false,
                'message' => 'Los datos enviados no son válidos'
            ]);
        }

        // Obtener y limpiar los campos.
        $nombre = trim($data['Nombre'] ?? '');
        $apellido1 = trim($data['Apellido1'] ?? '');
        $apellido2 = trim($data['Apellido2'] ?? '') ?: null;

        // El teléfono es opcional.
        // Si está vacío, se almacena como NULL.
        $telefono = trim($data['Telefono'] ?? '') ?: null;

        $direccion = trim($data['Direccion'] ?? '') ?: null;
        $email = trim($data['Email'] ?? '') ?: null;
        $notas = trim($data['Notas'] ?? '') ?: null;

        // Comprobar campos obligatorios.
        if ($nombre === '' || $apellido1 === '') {

            response(400, [
                'success' => false,
                'message' => 'El nombre y el primer apellido son obligatorios'
            ]);
        }

        // Comprobar longitudes máximas.
        if (
            strlen($nombre) > 50 ||
            strlen($apellido1) > 50 ||
            ($apellido2 !== null && strlen($apellido2) > 50) ||
            ($telefono !== null && strlen($telefono) > 20) ||
            ($direccion !== null && strlen($direccion) > 255) ||
            ($email !== null && strlen($email) > 100)
        ) {

            response(400, [
                'success' => false,
                'message' => 'Uno o varios campos superan la longitud permitida'
            ]);
        }

        // Validar formato del email si se proporciona.
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            response(400, [
                'success' => false,
                'message' => 'El formato del email no es válido'
            ]);
        }

        // --------------------------------------------------
        // COMPROBAR SI EL CLIENTE YA ESTÁ REGISTRADO
        // --------------------------------------------------
        //
        // Se considera duplicado cuando coinciden:
        // Nombre, Apellido1, Apellido2 y Telefono.
        //
        // El operador <=> permite comparar correctamente
        // incluso cuando alguno de los campos es NULL.
        //
        // También se comprueban los clientes dados de baja.
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            SELECT IDCliente
            FROM CLIENTES
            WHERE
                Nombre = :nombre
                AND Apellido1 = :apellido1
                AND Apellido2 <=> :apellido2
                AND Telefono <=> :telefono
            LIMIT 1
        ");

        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido1' => $apellido1,
            ':apellido2' => $apellido2,
            ':telefono' => $telefono
        ]);

        $clienteExistente = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si encontramos una coincidencia, no insertar.
        if ($clienteExistente) {

            response(409, [
                'success' => false,
                'message' => 'Ya existe un cliente registrado con el mismo nombre, apellidos y teléfono',
                'id' => (int) $clienteExistente['IDCliente']
            ]);
        }

        // --------------------------------------------------
        // INSERTAR EL NUEVO CLIENTE
        // --------------------------------------------------

        $stmt = $pdo->prepare("
            INSERT INTO CLIENTES (
                Nombre,
                Apellido1,
                Apellido2,
                Telefono,
                Direccion,
                Email,
                Notas
            ) VALUES (
                :nombre,
                :apellido1,
                :apellido2,
                :telefono,
                :direccion,
                :email,
                :notas
            )
        ");

        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido1' => $apellido1,
            ':apellido2' => $apellido2,
            ':telefono' => $telefono,
            ':direccion' => $direccion,
            ':email' => $email,
            ':notas' => $notas
        ]);

        response(201, [
            'success' => true,
            'message' => 'Cliente creado correctamente',
            'id' => (int) $pdo->lastInsertId()
        ]);

    } catch (PDOException $e) {

        error_log('Error al crear cliente: ' . $e->getMessage());

        response(500, [
            'success' => false,
            'message' => 'Error al crear el cliente'
        ]);
    }
}



/**
 * ==========================================================
 * ACTUALIZAR CLIENTE
 * ==========================================================
 *
 * Permite modificar los datos de un cliente existente.
 */
function actualizarCliente(int $id): void
{
    $pdo = getPDO();

    authenticateUser($pdo);

    try {

        // Comprobar que el cliente existe.
        $stmt = $pdo->prepare("
            SELECT IDCliente
            FROM CLIENTES
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        if (!$stmt->fetch()) {

            response(404, [
                'success' => false,
                'message' => 'El cliente no existe'
            ]);
        }

        // Obtener los datos enviados.
        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {

            response(400, [
                'success' => false,
                'message' => 'Los datos enviados no son válidos'
            ]);
        }

        // Obtener y limpiar los campos.
        $nombre = trim($data['Nombre'] ?? '');
        $apellido1 = trim($data['Apellido1'] ?? '');
        $apellido2 = trim($data['Apellido2'] ?? '');

        // El teléfono sigue siendo opcional.
        // No se comprueba si está registrado en otro cliente.
        $telefono = trim($data['Telefono'] ?? '') ?: null;

        $direccion = trim($data['Direccion'] ?? '') ?: null;
        $email = trim($data['Email'] ?? '') ?: null;
        $notas = trim($data['Notas'] ?? '') ?: null;

        // Comprobar campos obligatorios.
        if ($nombre === '' || $apellido1 === '') {

            response(400, [
                'success' => false,
                'message' => 'El nombre y el primer apellido son obligatorios'
            ]);
        }

        // Comprobar longitudes máximas.
        if (
            strlen($nombre) > 50 ||
            strlen($apellido1) > 50 ||
            strlen($apellido2) > 50 ||
            ($telefono !== null && strlen($telefono) > 20) ||
            ($direccion !== null && strlen($direccion) > 255) ||
            ($email !== null && strlen($email) > 100)
        ) {

            response(400, [
                'success' => false,
                'message' => 'Uno o varios campos superan la longitud permitida'
            ]);
        }

        // Validar el email si se proporciona.
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            response(400, [
                'success' => false,
                'message' => 'El formato del email no es válido'
            ]);
        }

        // Actualizar los datos del cliente.
        $stmt = $pdo->prepare("
            UPDATE CLIENTES
            SET
                Nombre = :nombre,
                Apellido1 = :apellido1,
                Apellido2 = :apellido2,
                Telefono = :telefono,
                Direccion = :direccion,
                Email = :email,
                Notas = :notas
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':nombre' => $nombre,
            ':apellido1' => $apellido1,
            ':apellido2' => $apellido2 ?: null,
            ':telefono' => $telefono,
            ':direccion' => $direccion,
            ':email' => $email,
            ':notas' => $notas,
            ':id' => $id
        ]);

        response(200, [
            'success' => true,
            'message' => 'Cliente actualizado correctamente'
        ]);

    } catch (PDOException $e) {

        error_log('Error al actualizar cliente: ' . $e->getMessage());

        response(500, [
            'success' => false,
            'message' => 'Error al actualizar el cliente'
        ]);
    }
}


/**
 * ==========================================================
 * DAR DE BAJA CLIENTE
 * ==========================================================
 *
 * No se elimina el registro físicamente.
 * Se marca como baja y se guarda la fecha.
 */
function darDeBajaCliente(int $id): void
{
    $pdo = getPDO();

    authenticateUser($pdo);

    try {

        // Comprobar que el cliente existe.
        $stmt = $pdo->prepare("
            SELECT baja
            FROM CLIENTES
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {

            response(404, [
                'success' => false,
                'message' => 'El cliente no existe'
            ]);
        }

        // Comprobar que no esté ya dado de baja.
        if ((bool) $cliente['baja']) {

            response(409, [
                'success' => false,
                'message' => 'El cliente ya está dado de baja'
            ]);
        }

        // Aplicar baja lógica.
        $stmt = $pdo->prepare("
            UPDATE CLIENTES
            SET
                baja = TRUE,
                FechaBaja = NOW()
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        response(200, [
            'success' => true,
            'message' => 'Cliente dado de baja correctamente'
        ]);

    } catch (PDOException $e) {

        error_log('Error al dar de baja cliente: ' . $e->getMessage());

        response(500, [
            'success' => false,
            'message' => 'Error al dar de baja al cliente'
        ]);
    }
}


/**
 * ==========================================================
 * REACTIVAR CLIENTE
 * ==========================================================
 *
 * Permite recuperar un cliente que estaba dado de baja.
 */
function reactivarCliente(int $id): void
{
    $pdo = getPDO();

    authenticateUser($pdo);

    try {

        // Comprobar que el cliente existe.
        $stmt = $pdo->prepare("
            SELECT baja
            FROM CLIENTES
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cliente) {

            response(404, [
                'success' => false,
                'message' => 'El cliente no existe'
            ]);
        }

        // Comprobar que realmente esté dado de baja.
        if (!(bool) $cliente['baja']) {

            response(409, [
                'success' => false,
                'message' => 'El cliente ya está activo'
            ]);
        }

        // Reactivar cliente.
        $stmt = $pdo->prepare("
            UPDATE CLIENTES
            SET
                baja = FALSE,
                FechaBaja = NULL
            WHERE IDCliente = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        response(200, [
            'success' => true,
            'message' => 'Cliente reactivado correctamente'
        ]);

    } catch (PDOException $e) {

        error_log('Error al reactivar cliente: ' . $e->getMessage());

        response(500, [
            'success' => false,
            'message' => 'Error al reactivar el cliente'
        ]);
    }
}
