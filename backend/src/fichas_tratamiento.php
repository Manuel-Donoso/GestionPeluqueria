<?php
/**
 * ==========================================================
 * GESTIÓN DE FICHAS DE TRATAMIENTO
 * ==========================================================
 *
 * Funciones disponibles inicialmente:
 *
 * GET  /api/fichas-tratamiento
 * GET  /api/fichas-tratamiento/{id}
 * POST /api/fichas-tratamiento
 *
 * Estados:
 * - borrador
 * - enviada
 * - finalizada
 *
 * Roles:
 * - 1: Administrador.
 * - 2: Peluquero.
 * - 3: Profesor.
 *
 * Permisos:
 * - Administrador: consulta todas las fichas y puede crear
 *   asignando el peluquero responsable.
 * - Peluquero: consulta sus propias fichas y puede crear
 *   fichas a su nombre.
 * - Profesor: puede consultar todas las fichas.
 *
 * Las operaciones de escritura identifican al usuario
 * mediante establecerUsuarioAuditoria().
 */


// ==========================================================
// LISTAR FICHAS DE TRATAMIENTO
// ==========================================================

/**
 * Lista las fichas de tratamiento.
 *
 * Parámetros GET opcionales:
 *
 * - busqueda: nombre o apellidos del cliente.
 * - estado: borrador | enviada | finalizada | todos.
 * - cliente: identificador del cliente.
 * - peluquero: identificador del peluquero.
 *
 * El peluquero solamente puede consultar sus propias fichas.
 */
function listarFichasTratamiento(): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    try {

        // Obtener filtros.
        $busqueda = trim($_GET['busqueda'] ?? '');
        $estado = $_GET['estado'] ?? 'todos';

        $idCliente = isset($_GET['cliente'])
            ? (int) $_GET['cliente']
            : 0;

        $idPeluquero = isset($_GET['peluquero'])
            ? (int) $_GET['peluquero']
            : 0;

        // Validar estado.
        $estadosPermitidos = [
            'borrador',
            'enviada',
            'finalizada',
            'todos'
        ];

        if (!in_array($estado, $estadosPermitidos, true)) {

            response(400, [
                'success' => false,
                'message' => 'El estado indicado no es válido'
            ]);
        }

        // Consulta principal.
        $sql = "
            SELECT
                f.IDFicha,
                f.Estado,
                f.FueFinalizada,
                f.FechaCreacion,
                f.FechaEnvio,
                f.FechaFinalizacion,
                f.FechaTratamiento,
                f.FechaModificacion,

                f.FK_Cliente,

                c.Nombre AS NombreCliente,
                c.Apellido1 AS Apellido1Cliente,
                c.Apellido2 AS Apellido2Cliente,

                f.FK_Peluquero,

                p.Nombre AS NombrePeluquero,
                p.Apellido1 AS Apellido1Peluquero,
                p.Apellido2 AS Apellido2Peluquero,

                f.FK_Profesor,

                pr.Nombre AS NombreProfesor,
                pr.Apellido1 AS Apellido1Profesor,
                pr.Apellido2 AS Apellido2Profesor,

                (
                    SELECT COUNT(*)
                    FROM FORMULAS_TRATAMIENTO ft
                    WHERE ft.FK_Ficha = f.IDFicha
                ) AS NumeroFormulas

            FROM FICHAS_TRATAMIENTO f

            INNER JOIN CLIENTES c
                ON c.IDCliente = f.FK_Cliente

            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = f.FK_Peluquero

            LEFT JOIN PELUQUEROS pr
                ON pr.IDPeluquero = f.FK_Profesor

            WHERE 1 = 1
        ";

        $params = [];

        // El peluquero solamente consulta sus propias fichas.
        if ((int) $usuario['FK_rol'] === 2) {

            $sql .= "
                AND f.FK_Peluquero = :idPeluqueroUsuario
            ";

            $params[':idPeluqueroUsuario'] =
                (int) $usuario['IDPeluquero'];
        }

        // Filtro por cliente.
        if ($idCliente > 0) {

            $sql .= "
                AND f.FK_Cliente = :idCliente
            ";

            $params[':idCliente'] = $idCliente;
        }

        // Filtro por peluquero.
        // Solamente se aplica a administrador y profesor.
        if (
            $idPeluquero > 0
            && (int) $usuario['FK_rol'] !== 2
        ) {

            $sql .= "
                AND f.FK_Peluquero = :idPeluquero
            ";

            $params[':idPeluquero'] = $idPeluquero;
        }

        // Filtro por estado.
        if ($estado !== 'todos') {

            $sql .= "
                AND f.Estado = :estado
            ";

            $params[':estado'] = $estado;
        }

        // Búsqueda por nombre o apellidos del cliente.
        if ($busqueda !== '') {

            $sql .= "
                AND (
                    c.Nombre LIKE :busquedaNombre
                    OR c.Apellido1 LIKE :busquedaApellido1
                    OR c.Apellido2 LIKE :busquedaApellido2
                )
            ";

            $busquedaLike = '%' . $busqueda . '%';

            $params[':busquedaNombre'] = $busquedaLike;
            $params[':busquedaApellido1'] = $busquedaLike;
            $params[':busquedaApellido2'] = $busquedaLike;
        }

        // Ordenar por fecha de creación descendente.
        $sql .= "
            ORDER BY
                f.FechaCreacion DESC,
                f.IDFicha DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response(200, [
            'success' => true,
            'total' => count($fichas),
            'fichas' => $fichas
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al listar fichas de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al obtener las fichas de tratamiento'
        ]);
    }
}


// ==========================================================
// OBTENER UNA FICHA DE TRATAMIENTO
// ==========================================================

/**
 * Obtiene una ficha concreta junto con sus fórmulas.
 *
 * Parámetro:
 * - $id: identificador de la ficha.
 *
 * El peluquero solamente puede consultar sus propias fichas.
 */
function obtenerFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    $usuario = authenticateUser($pdo);

    try {

        // Obtener los datos principales de la ficha.
        $sql = "
            SELECT
                f.*,

                c.Nombre AS NombreCliente,
                c.Apellido1 AS Apellido1Cliente,
                c.Apellido2 AS Apellido2Cliente,

                p.Nombre AS NombrePeluquero,
                p.Apellido1 AS Apellido1Peluquero,
                p.Apellido2 AS Apellido2Peluquero,

                pr.Nombre AS NombreProfesor,
                pr.Apellido1 AS Apellido1Profesor,
                pr.Apellido2 AS Apellido2Profesor

            FROM FICHAS_TRATAMIENTO f

            INNER JOIN CLIENTES c
                ON c.IDCliente = f.FK_Cliente

            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = f.FK_Peluquero

            LEFT JOIN PELUQUEROS pr
                ON pr.IDPeluquero = f.FK_Profesor

            WHERE f.IDFicha = :idFicha
        ";

        $params = [
            ':idFicha' => $id
        ];

        // Restringir la consulta del peluquero.
        if ((int) $usuario['FK_rol'] === 2) {

            $sql .= "
                AND f.FK_Peluquero = :idPeluquero
            ";

            $params[':idPeluquero'] =
                (int) $usuario['IDPeluquero'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ficha) {

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe o no tienes permiso para consultarla'
            ]);
        }

        // Obtener las fórmulas de la ficha.
        $stmtFormulas = $pdo->prepare("
            SELECT
                IDFormula,
                FK_Ficha,
                ZonaFase,
                Tono,
                Gramos,
                Oxidante,
                CantidadOxidante,
                Proporcion,
                Tiempo

            FROM FORMULAS_TRATAMIENTO

            WHERE FK_Ficha = :idFicha

            ORDER BY IDFormula ASC
        ");

        $stmtFormulas->execute([
            ':idFicha' => $id
        ]);

        $ficha['Formulas'] =
            $stmtFormulas->fetchAll(PDO::FETCH_ASSOC);

        response(200, [
            'success' => true,
            'ficha' => $ficha
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al obtener ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al obtener la ficha de tratamiento'
        ]);
    }
}


// ==========================================================
// REGISTRAR OPERACIONES EN EL HISTORIAL
// ==========================================================

/**
 * Registra una operación en HISTORIAL_FICHAS_TRATAMIENTO.
 *
 * Esta función auxiliar se utilizará en las operaciones
 * de creación, modificación, envío, devolución,
 * finalización, reapertura y eliminación.
 *
 * IDFichaReferencia conserva el identificador original
 * incluso cuando la ficha se elimina físicamente.
 */
function registrarHistorialFicha(
    PDO $pdo,
    int $idFicha,
    int $idPeluquero,
    string $operacion,
    ?string $estadoAnterior = null,
    ?string $estadoNuevo = null,
    ?array $datosAnteriores = null,
    ?array $datosNuevos = null,
    ?string $motivo = null
): void {

    $operacionesPermitidas = [
        'CREACION',
        'MODIFICACION',
        'ENVIO',
        'DEVOLUCION',
        'FINALIZACION',
        'REAPERTURA',
        'ELIMINACION'
    ];

    if (!in_array($operacion, $operacionesPermitidas, true)) {

        throw new InvalidArgumentException(
            'La operación de historial no es válida'
        );
    }

    $jsonAnterior = $datosAnteriores !== null
        ? json_encode(
            $datosAnteriores,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        )
        : null;

    $jsonNuevo = $datosNuevos !== null
        ? json_encode(
            $datosNuevos,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        )
        : null;

    $sql = "
        INSERT INTO HISTORIAL_FICHAS_TRATAMIENTO (
            IDFichaReferencia,
            FK_Ficha,
            Operacion,
            EstadoAnterior,
            EstadoNuevo,
            DatosAnteriores,
            DatosNuevos,
            Motivo,
            FechaOperacion,
            FK_peluquero
        ) VALUES (
            :idFichaReferencia,
            :idFicha,
            :operacion,
            :estadoAnterior,
            :estadoNuevo,
            :datosAnteriores,
            :datosNuevos,
            :motivo,
            NOW(),
            :idPeluquero
        )
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':idFichaReferencia' => $idFicha,
        ':idFicha' => $idFicha,
        ':operacion' => $operacion,
        ':estadoAnterior' => $estadoAnterior,
        ':estadoNuevo' => $estadoNuevo,
        ':datosAnteriores' => $jsonAnterior,
        ':datosNuevos' => $jsonNuevo,
        ':motivo' => $motivo,
        ':idPeluquero' => $idPeluquero
    ]);
}


// ==========================================================
// CREAR FICHA DE TRATAMIENTO
// ==========================================================

/**
 * Crea una nueva ficha en estado borrador.
 *
 * Campos JSON:
 *
 * Obligatorios:
 * - FK_Cliente
 *
 * Opcionales:
 * - FK_Peluquero (solamente administrador).
 * - FechaTratamiento.
 *
 * El peluquero crea fichas a su propio nombre.
 * El administrador puede asignar otro peluquero.
 *
 * La creación y su registro en el historial se realizan
 * dentro de una única transacción.
 */
function crearFichaTratamiento(): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    // Solamente administrador y peluquero pueden crear fichas.
    $rol = (int) $usuario['FK_rol'];

    if (!in_array($rol, [1, 2], true)) {

        response(403, [
            'success' => false,
            'message' => 'No tienes permisos para crear fichas de tratamiento'
        ]);
    }

    // Identificar al usuario para los triggers de auditoría.
    establecerUsuarioAuditoria(
        $pdo,
        (int) $usuario['IDPeluquero']
    );

    try {

        // Obtener los datos enviados en JSON.
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($data)) {

            response(400, [
                'success' => false,
                'message' => 'Los datos enviados no son válidos'
            ]);
        }

        // Obtener el identificador del cliente.
        $idCliente = isset($data['FK_Cliente'])
            ? (int) $data['FK_Cliente']
            : 0;

        if ($idCliente <= 0) {

            response(400, [
                'success' => false,
                'message' => 'Debes indicar un cliente válido'
            ]);
        }

        // Determinar el peluquero responsable.
        if ($rol === 2) {

            // El peluquero solamente puede crear fichas a su nombre.
            $idPeluquero = (int) $usuario['IDPeluquero'];

        } else {

            // El administrador puede asignar un peluquero.
            $idPeluquero = isset($data['FK_Peluquero'])
                ? (int) $data['FK_Peluquero']
                : (int) $usuario['IDPeluquero'];
        }

        if ($idPeluquero <= 0) {

            response(400, [
                'success' => false,
                'message' => 'Debes indicar un peluquero responsable válido'
            ]);
        }

        // Obtener la fecha del tratamiento.
        $fechaTratamiento = $data['FechaTratamiento'] ?? null;

        if ($fechaTratamiento === '') {
            $fechaTratamiento = null;
        }

        // Validar el formato de la fecha si se proporciona.
        if (
            $fechaTratamiento !== null
            && (
                !is_string($fechaTratamiento)
                || !preg_match(
                    '/^\d{4}-\d{2}-\d{2}$/',
                    $fechaTratamiento
                )
                || !checkdate(
                    (int) substr($fechaTratamiento, 5, 2),
                    (int) substr($fechaTratamiento, 8, 2),
                    (int) substr($fechaTratamiento, 0, 4)
                )
            )
        ) {

            response(400, [
                'success' => false,
                'message' => 'La fecha del tratamiento no es válida'
            ]);
        }

        // Comprobar que el cliente existe y está activo.
        $stmtCliente = $pdo->prepare("
            SELECT IDCliente
            FROM CLIENTES
            WHERE IDCliente = :idCliente
              AND baja = FALSE
              AND FechaBaja IS NULL
        ");

        $stmtCliente->execute([
            ':idCliente' => $idCliente
        ]);

        if (!$stmtCliente->fetchColumn()) {

            response(404, [
                'success' => false,
                'message' => 'El cliente no existe o está dado de baja'
            ]);
        }

        // Comprobar que el peluquero existe y está activo.
        $stmtPeluquero = $pdo->prepare("
            SELECT IDPeluquero
            FROM PELUQUEROS
            WHERE IDPeluquero = :idPeluquero
              AND baja = FALSE
              AND FechaBaja IS NULL
        ");

        $stmtPeluquero->execute([
            ':idPeluquero' => $idPeluquero
        ]);

        if (!$stmtPeluquero->fetchColumn()) {

            response(404, [
                'success' => false,
                'message' => 'El peluquero no existe o está dado de baja'
            ]);
        }

        // Iniciar transacción.
        $pdo->beginTransaction();

        // Insertar la ficha.
        $stmt = $pdo->prepare("
            INSERT INTO FICHAS_TRATAMIENTO (
                Estado,
                FueFinalizada,
                FechaTratamiento,
                FK_Cliente,
                FK_Peluquero,
                FK_Profesor,
                FK_modificadoPor
            ) VALUES (
                'borrador',
                FALSE,
                :fechaTratamiento,
                :idCliente,
                :idPeluquero,
                NULL,
                :idModificadoPor
            )
        ");

        $stmt->execute([
            ':fechaTratamiento' => $fechaTratamiento,
            ':idCliente' => $idCliente,
            ':idPeluquero' => $idPeluquero,
            ':idModificadoPor' => (int) $usuario['IDPeluquero']
        ]);

        // Obtener el identificador generado.
        $idFicha = (int) $pdo->lastInsertId();

        // Datos iniciales de la ficha para el historial.
        $datosNuevos = [
            'IDFicha' => $idFicha,
            'Estado' => 'borrador',
            'FueFinalizada' => false,
            'FechaTratamiento' => $fechaTratamiento,
            'FK_Cliente' => $idCliente,
            'FK_Peluquero' => $idPeluquero,
            'FK_Profesor' => null
        ];

        // Registrar la creación.
        registrarHistorialFicha(
            $pdo,
            $idFicha,
            (int) $usuario['IDPeluquero'],
            'CREACION',
            null,
            'borrador',
            null,
            $datosNuevos
        );

        // Confirmar la transacción.
        $pdo->commit();

        response(201, [
            'success' => true,
            'message' => 'Ficha de tratamiento creada correctamente',
            'id' => $idFicha,
            'estado' => 'borrador'
        ]);

    } catch (PDOException $e) {

        // Revertir la transacción si se ha iniciado.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al crear ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al crear la ficha de tratamiento'
        ]);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error inesperado al crear ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al crear la ficha'
        ]);
    }
}
