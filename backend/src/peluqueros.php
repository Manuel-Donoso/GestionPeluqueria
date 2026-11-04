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
