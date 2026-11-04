<?php
/**
 * Gestión de peluqueros.
 *
 * Operaciones disponibles:
 * - Listar peluqueros.
 */

function listarPeluqueros(): void
{
    try {
        $pdo = getPDO();

        // Solo los administradores pueden consultar el listado.
        requireAdmin($pdo);

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
                FK_rol,
                FechaAlta,
                FechaBaja,
                baja
            FROM PELUQUEROS
            ORDER BY Nombre ASC, Apellido1 ASC
        ";

        $stmt = $pdo->query($sql);
        $peluqueros = $stmt->fetchAll();

        response(200, [
            'status' => 'ok',
            'total' => count($peluqueros),
            'peluqueros' => $peluqueros
        ]);

    } catch (PDOException $e) {
        error_log(
            'Error de base de datos al listar peluqueros: '
            . $e->getMessage()
        );

        response(500, [
            'status' => 'error',
            'message' => 'Error al consultar los peluqueros'
        ]);
    }
}


/**
 * Obtiene la ficha de un peluquero por su ID.
 */
function obtenerPeluquero(int $id): void
{
    try {
        $pdo = getPDO();

        // Solo los administradores pueden consultar fichas.
        requireAdmin($pdo);

        if ($id <= 0) {
            response(400, [
                'status' => 'error',
                'message' => 'El identificador del peluquero no es válido'
            ]);
        }

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
                FK_rol,
                FechaAlta,
                FechaBaja,
                baja
            FROM PELUQUEROS
            WHERE IDPeluquero = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $peluquero = $stmt->fetch();

        if (!$peluquero) {
            response(404, [
                'status' => 'error',
                'message' => 'No se ha encontrado el peluquero'
            ]);
        }

        response(200, [
            'status' => 'ok',
            'peluquero' => $peluquero
        ]);

    } catch (PDOException $e) {
        error_log(
            'Error de base de datos al consultar peluquero: '
            . $e->getMessage()
        );

        response(500, [
            'status' => 'error',
            'message' => 'Error al consultar el peluquero'
        ]);
    }
}




/**
 * Registra un nuevo peluquero.
 */
function crearPeluquero(): void
{
    try {
        $pdo = getPDO();

        // Solo los administradores pueden crear peluqueros.
        requireAdmin($pdo);

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            response(400, [
                'status' => 'error',
                'message' => 'La petición no contiene un JSON válido'
            ]);
        }

        // Datos obligatorios.
        $usuario = trim($data['Usuario'] ?? '');
        $nombre = trim($data['Nombre'] ?? '');
        $apellido1 = trim($data['Apellido1'] ?? '');
        $password = $data['PW'] ?? '';
        $rol = filter_var(
            $data['FK_rol'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $usuario === ''
            || $nombre === ''
            || $apellido1 === ''
            || !is_string($password)
            || $password === ''
            || $rol === false
            || $rol === null
            || $rol <= 0
        ) {
            response(400, [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o alguno no es válido'
            ]);
        }

        // Datos opcionales.
        $apellido2 = trim($data['Apellido2'] ?? '') ?: null;
        $telefono = trim($data['Telefono'] ?? '') ?: null;
        $direccion = trim($data['Direccion'] ?? '') ?: null;
        $email = trim($data['Email'] ?? '') ?: null;
        $notas = trim($data['Notas'] ?? '') ?: null;

        // Comprobar longitudes según la estructura de la tabla.
        if (
            strlen($usuario) > 50
            || strlen($nombre) > 50
            || strlen($apellido1) > 50
            || ($apellido2 !== null && strlen($apellido2) > 50)
            || ($telefono !== null && strlen($telefono) > 20)
            || ($direccion !== null && strlen($direccion) > 255)
            || ($email !== null && strlen($email) > 100)
        ) {
            response(400, [
                'status' => 'error',
                'message' => 'Uno o varios campos superan la longitud permitida'
            ]);
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response(400, [
                'status' => 'error',
                'message' => 'El correo electrónico no es válido'
            ]);
        }

        // Comprobar que el rol existe.
        $stmt = $pdo->prepare("
            SELECT IDRoles
            FROM ROLES
            WHERE IDRoles = :rol
        ");

        $stmt->execute(['rol' => $rol]);

        if (!$stmt->fetch()) {
            response(400, [
                'status' => 'error',
                'message' => 'El rol indicado no existe'
            ]);
        }

        // Comprobar si el usuario ya existe.
        $stmt = $pdo->prepare("
            SELECT IDPeluquero
            FROM PELUQUEROS
            WHERE Usuario = :usuario
        ");

        $stmt->execute(['usuario' => $usuario]);

        if ($stmt->fetch()) {
            response(409, [
                'status' => 'error',
                'message' => 'El nombre de usuario ya está registrado'
            ]);
        }

        // Comprobar si el correo ya existe.
        if ($email !== null) {
            $stmt = $pdo->prepare("
                SELECT IDPeluquero
                FROM PELUQUEROS
                WHERE Email = :email
            ");

            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                response(409, [
                    'status' => 'error',
                    'message' => 'El correo electrónico ya está registrado'
                ]);
            }
        }

        // Generar el hash de la contraseña.
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Insertar el nuevo peluquero.
        $sql = "
            INSERT INTO PELUQUEROS (
                Usuario,
                Nombre,
                Apellido1,
                Apellido2,
                Telefono,
                Direccion,
                Email,
                Notas,
                PW,
                Restarurar,
                FK_rol,
                baja
            )
            VALUES (
                :usuario,
                :nombre,
                :apellido1,
                :apellido2,
                :telefono,
                :direccion,
                :email,
                :notas,
                :password,
                0,
                :rol,
                0
            )
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            'usuario' => $usuario,
            'nombre' => $nombre,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'email' => $email,
            'notas' => $notas,
            'password' => $passwordHash,
            'rol' => $rol
        ]);

        $id = (int) $pdo->lastInsertId();

        response(201, [
            'status' => 'ok',
            'message' => 'Peluquero registrado correctamente',
            'id' => $id
        ]);

    } catch (PDOException $e) {
        error_log(
            'Error de base de datos al crear peluquero: '
            . $e->getMessage()
        );

        // 23000: violación de una restricción de integridad.
        if ($e->getCode() === '23000') {
            response(409, [
                'status' => 'error',
                'message' => 'El usuario o correo electrónico ya está registrado'
            ]);
        }

        response(500, [
            'status' => 'error',
            'message' => 'Error al registrar el peluquero'
        ]);
    }
}



/**
 * Actualiza los datos de un peluquero existente.
 */
function actualizarPeluquero(int $id): void
{
    try {
        $pdo = getPDO();

        // Solo los administradores pueden modificar peluqueros.
        requireAdmin($pdo);

        if ($id <= 0) {
            response(400, [
                'status' => 'error',
                'message' => 'El identificador del peluquero no es válido'
            ]);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!is_array($data)) {
            response(400, [
                'status' => 'error',
                'message' => 'La petición no contiene un JSON válido'
            ]);
        }

        // Comprobar que el peluquero existe.
        $stmt = $pdo->prepare("
            SELECT IDPeluquero
            FROM PELUQUEROS
            WHERE IDPeluquero = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        if (!$stmt->fetch()) {
            response(404, [
                'status' => 'error',
                'message' => 'No se ha encontrado el peluquero'
            ]);
        }

        // Datos obligatorios.
        $usuario = trim($data['Usuario'] ?? '');
        $nombre = trim($data['Nombre'] ?? '');
        $apellido1 = trim($data['Apellido1'] ?? '');
        $rol = filter_var(
            $data['FK_rol'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $usuario === ''
            || $nombre === ''
            || $apellido1 === ''
            || $rol === false
            || $rol === null
            || $rol <= 0
        ) {
            response(400, [
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o alguno no es válido'
            ]);
        }

        // Datos opcionales.
        $apellido2 = trim($data['Apellido2'] ?? '') ?: null;
        $telefono = trim($data['Telefono'] ?? '') ?: null;
        $direccion = trim($data['Direccion'] ?? '') ?: null;
        $email = trim($data['Email'] ?? '') ?: null;
        $notas = trim($data['Notas'] ?? '') ?: null;

        // La contraseña es opcional al modificar.
        $password = $data['PW'] ?? '';

        if (!is_string($password)) {
            response(400, [
                'status' => 'error',
                'message' => 'La contraseña no es válida'
            ]);
        }

        // Validar longitudes.
        if (
            strlen($usuario) > 50
            || strlen($nombre) > 50
            || strlen($apellido1) > 50
            || ($apellido2 !== null && strlen($apellido2) > 50)
            || ($telefono !== null && strlen($telefono) > 20)
            || ($direccion !== null && strlen($direccion) > 255)
            || ($email !== null && strlen($email) > 100)
        ) {
            response(400, [
                'status' => 'error',
                'message' => 'Uno o varios campos superan la longitud permitida'
            ]);
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response(400, [
                'status' => 'error',
                'message' => 'El correo electrónico no es válido'
            ]);
        }

        // Comprobar que el rol existe.
        $stmt = $pdo->prepare("
            SELECT IDRoles
            FROM ROLES
            WHERE IDRoles = :rol
        ");

        $stmt->execute(['rol' => $rol]);

        if (!$stmt->fetch()) {
            response(400, [
                'status' => 'error',
                'message' => 'El rol indicado no existe'
            ]);
        }

        // Comprobar que el usuario no pertenece a otro peluquero.
        $stmt = $pdo->prepare("
            SELECT IDPeluquero
            FROM PELUQUEROS
            WHERE Usuario = :usuario
              AND IDPeluquero != :id
        ");

        $stmt->execute([
            'usuario' => $usuario,
            'id' => $id
        ]);

        if ($stmt->fetch()) {
            response(409, [
                'status' => 'error',
                'message' => 'El nombre de usuario ya está registrado'
            ]);
        }

        // Comprobar que el email no pertenece a otro peluquero.
        if ($email !== null) {
            $stmt = $pdo->prepare("
                SELECT IDPeluquero
                FROM PELUQUEROS
                WHERE Email = :email
                  AND IDPeluquero != :id
            ");

            $stmt->execute([
                'email' => $email,
                'id' => $id
            ]);

            if ($stmt->fetch()) {
                response(409, [
                    'status' => 'error',
                    'message' => 'El correo electrónico ya está registrado'
                ]);
            }
        }

        // Actualizar los datos de la ficha.
        $sql = "
            UPDATE PELUQUEROS
            SET
                Usuario = :usuario,
                Nombre = :nombre,
                Apellido1 = :apellido1,
                Apellido2 = :apellido2,
                Telefono = :telefono,
                Direccion = :direccion,
                Email = :email,
                Notas = :notas,
                FK_rol = :rol
        ";

        $params = [
            'usuario' => $usuario,
            'nombre' => $nombre,
            'apellido1' => $apellido1,
            'apellido2' => $apellido2,
            'telefono' => $telefono,
            'direccion' => $direccion,
            'email' => $email,
            'notas' => $notas,
            'rol' => $rol,
            'id' => $id
        ];

        // Cambiar la contraseña únicamente si se ha enviado una nueva.
        if ($password !== '') {
            $sql .= ", PW = :password";
            $params['password'] = password_hash(
                $password,
                PASSWORD_DEFAULT
            );
        }

        $sql .= " WHERE IDPeluquero = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        response(200, [
            'status' => 'ok',
            'message' => 'Peluquero actualizado correctamente',
            'id' => $id
        ]);

    } catch (PDOException $e) {
        error_log(
            'Error de base de datos al actualizar peluquero: '
            . $e->getMessage()
        );

        if ($e->getCode() === '23000') {
            response(409, [
                'status' => 'error',
                'message' => 'El usuario o correo electrónico ya está registrado'
            ]);
        }

        response(500, [
            'status' => 'error',
            'message' => 'Error al actualizar el peluquero'
        ]);
    }
}


/**
 * Da de baja a un peluquero sin eliminar su registro.
 */
function darDeBajaPeluquero(int $id): void
{
    try {
        $pdo = getPDO();

        // Solo los administradores pueden dar de baja.
        requireAdmin($pdo);

        if ($id <= 0) {
            response(400, [
                'status' => 'error',
                'message' => 'El identificador del peluquero no es válido'
            ]);
        }

        // Comprobar que el peluquero existe.
        $stmt = $pdo->prepare("
            SELECT IDPeluquero, baja
            FROM PELUQUEROS
            WHERE IDPeluquero = :id
            LIMIT 1
        ");

        $stmt->execute(['id' => $id]);

        $peluquero = $stmt->fetch();

        if (!$peluquero) {
            response(404, [
                'status' => 'error',
                'message' => 'No se ha encontrado el peluquero'
            ]);
        }

        // Evitar dar de baja a alguien que ya está de baja.
        if ((bool) $peluquero['baja']) {
            response(409, [
                'status' => 'error',
                'message' => 'El peluquero ya está dado de baja'
            ]);
        }

        // Realizar la baja lógica.
        $stmt = $pdo->prepare("
            UPDATE PELUQUEROS
            SET
                baja = TRUE,
                FechaBaja = NOW()
            WHERE IDPeluquero = :id
        ");

        $stmt->execute(['id' => $id]);

        response(200, [
            'status' => 'ok',
            'message' => 'Peluquero dado de baja correctamente',
            'id' => $id
        ]);

    } catch (PDOException $e) {
        error_log(
            'Error de base de datos al dar de baja peluquero: '
            . $e->getMessage()
        );

        response(500, [
            'status' => 'error',
            'message' => 'Error al dar de baja al peluquero'
        ]);
    }
}
