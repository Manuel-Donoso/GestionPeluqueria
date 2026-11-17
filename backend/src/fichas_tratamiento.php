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



    // ==========================================================
// ACTUALIZAR FICHA DE TRATAMIENTO
// ==========================================================

/**
 * Actualiza una ficha de tratamiento y sus fórmulas.
 *
 * PUT /api/fichas-tratamiento/{id}
 *
 * Permisos:
 * - Administrador: puede modificar cualquier ficha.
 * - Peluquero: solamente sus propias fichas.
 * - Profesor: no puede modificar mediante este endpoint.
 *
 * Restricciones:
 * - Solamente se pueden modificar fichas en borrador.
 * - Las fichas enviadas deben devolverse previamente a borrador.
 * - Las fichas finalizadas deben reabrirse mediante el procedimiento
 *   específico del profesor.
 *
 * Los campos no enviados permanecen sin cambios.
 *
 * Si se incluye Formulas, se sustituye la lista completa de fórmulas
 * de la ficha por la lista recibida.
 *
 * Toda la operación se realiza dentro de una transacción.
 */
function actualizarFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    $usuario = authenticateUser($pdo);

    $rol = (int) $usuario['FK_rol'];
    $idUsuario = (int) $usuario['IDPeluquero'];

    // Solamente administrador y peluquero pueden modificar.
    if (!in_array($rol, [1, 2], true)) {
        response(403, [
            'success' => false,
            'message' => 'No tienes permisos para modificar fichas de tratamiento'
        ]);
    }

    establecerUsuarioAuditoria($pdo, $idUsuario);

    try {

        // Decodificar JSON.
        try {
            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            response(400, [
                'success' => false,
                'message' => 'El JSON enviado no es válido'
            ]);
        }

        if (!is_array($data)) {
            response(400, [
                'success' => false,
                'message' => 'Los datos enviados no son válidos'
            ]);
        }

        if (empty($data)) {
            response(400, [
                'success' => false,
                'message' => 'No se han enviado datos para actualizar'
            ]);
        }

        // Campos de FICHAS_TRATAMIENTO que se pueden modificar.
        // Se excluyen los identificadores, estados y datos de auditoría.
        $camposPermitidos = [
            'FechaTratamiento',
            'ColorCompleto',
            'Raiz',
            'Mechas',
            'Matiz',
            'OtroServicio',
            'UltimoColorProcesos',
            'Alergias',
            'AlergiasDetalles',
            'CueroCabelludoAlteraciones',
            'Longitud',
            'RaizCrecidaCm',
            'Grosor',
            'Canas',
            'Distribucion',
            'ZonaRaizTonoReflejos',
            'ZonaMediosTonoReflejos',
            'ZonaPuntasTonoReflejos',
            'ZonaRaizPorosidadResistencia',
            'ZonaMediosPorosidadResistencia',
            'ZonaPuntasPorosidadResistencia',
            'PruebaAlergia',
            'PruebaMechon',
            'ObjetivoAltura',
            'ObjetivoReflejo',
            'ObjetivoFondoPrevisto',
            'TecnicaJustificacion',
            'MarcaLinea',
            'PreparacionPrevia',
            'OrdenAplicacion',
            'InicioAplicacion',
            'RetiradaAplicacion',
            'TiempoAplicacionMinutos',
            'IncidenciasAjustesTratamientoFinal',
            'TonoReflejoFinal',
            'CanasCubiertas',
            'Uniforme',
            'CuidadosMantenimiento',
            'QueSalioBienMejoraria',
            'ValoracionRevisionFinalDocente'
        ];

        // Comprobar que no se envían propiedades desconocidas.
        $camposDesconocidos = array_diff(
            array_keys($data),
            array_merge($camposPermitidos, ['Formulas'])
        );

        if (!empty($camposDesconocidos)) {
            response(400, [
                'success' => false,
                'message' => 'Se han enviado campos no permitidos',
                'campos' => array_values($camposDesconocidos)
            ]);
        }

        // Validar fecha del tratamiento si se modifica.
        if (array_key_exists('FechaTratamiento', $data)) {

            $fecha = $data['FechaTratamiento'];

            if (
                $fecha !== null
                && $fecha !== ''
                && (
                    !is_string($fecha)
                    || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)
                    || !checkdate(
                        (int) substr($fecha, 5, 2),
                        (int) substr($fecha, 8, 2),
                        (int) substr($fecha, 0, 4)
                    )
                )
            ) {
                response(400, [
                    'success' => false,
                    'message' => 'La fecha del tratamiento no es válida'
                ]);
            }

            if ($fecha === '') {
                $data['FechaTratamiento'] = null;
            }
        }

        // Comprobar que Formulas sea una lista.
        if (
            array_key_exists('Formulas', $data)
            && !is_array($data['Formulas'])
        ) {
            response(400, [
                'success' => false,
                'message' => 'El campo Formulas debe contener una lista'
            ]);
        }

        // Iniciar transacción.
        $pdo->beginTransaction();

        // Obtener y bloquear la ficha durante la operación.
        $sqlFicha = "
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ";

        $paramsFicha = [
            ':idFicha' => $id
        ];

        // El peluquero solamente puede modificar sus propias fichas.
        if ($rol === 2) {
            $sqlFicha .= "
                AND FK_Peluquero = :idPeluquero
            ";

            $paramsFicha[':idPeluquero'] = $idUsuario;
        }

        $sqlFicha .= " FOR UPDATE";

        $stmtFicha = $pdo->prepare($sqlFicha);
        $stmtFicha->execute($paramsFicha);

        $fichaAnterior = $stmtFicha->fetch(PDO::FETCH_ASSOC);

        if (!$fichaAnterior) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe o no tienes permiso para modificarla'
            ]);
        }

        // Comprobar el estado.
        if ($fichaAnterior['Estado'] !== 'borrador') {

            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'Solamente se pueden modificar fichas en estado borrador',
                'estadoActual' => $fichaAnterior['Estado']
            ]);
        }

        // Obtener fórmulas anteriores.
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

        $formulasAnteriores = $stmtFormulas->fetchAll(
            PDO::FETCH_ASSOC
        );

        // Preparar los campos que realmente se han enviado.
        $camposActualizar = [];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $data)) {
                $camposActualizar[$campo] = $data[$campo];
            }
        }

        // Actualizar datos de la ficha.
        if (!empty($camposActualizar)) {

            $asignaciones = [];
            $parametros = [];

            foreach ($camposActualizar as $campo => $valor) {
                $asignaciones[] = "`{$campo}` = :{$campo}";
                $parametros[":{$campo}"] = $valor;
            }

            // Registrar quién realizó la modificación.
            $asignaciones[] = "FK_modificadoPor = :idModificadoPor";

            $parametros[':idModificadoPor'] = $idUsuario;
            $parametros[':idFicha'] = $id;

            $sqlUpdate = "
                UPDATE FICHAS_TRATAMIENTO
                SET " . implode(', ', $asignaciones) . "
                WHERE IDFicha = :idFicha
            ";

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute($parametros);
        }

        // Sustituir las fórmulas si se ha enviado Formulas.
        if (array_key_exists('Formulas', $data)) {

            $formulasNuevas = $data['Formulas'];

            // Validar cada fórmula antes de modificar la tabla.
            $zonasPermitidas = [
                'Raiz',
                'Medios y puntas',
                'Mechas',
                'Matiz'
            ];

            $camposFormula = [
                'ZonaFase',
                'Tono',
                'Gramos',
                'Oxidante',
                'CantidadOxidante',
                'Proporcion',
                'Tiempo'
            ];

            foreach ($formulasNuevas as $indice => $formula) {

                if (!is_array($formula)) {
                    throw new InvalidArgumentException(
                        "La fórmula de la posición {$indice} no es válida"
                    );
                }

                foreach ($camposFormula as $campo) {
                    if (!array_key_exists($campo, $formula)) {
                        throw new InvalidArgumentException(
                            "Falta el campo {$campo} en la fórmula de la posición {$indice}"
                        );
                    }
                }

                if (
                    !is_string($formula['ZonaFase'])
                    || !in_array(
                        $formula['ZonaFase'],
                        $zonasPermitidas,
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        "La zona de la fórmula de la posición {$indice} no es válida"
                    );
                }

                foreach (['Gramos', 'CantidadOxidante'] as $campo) {
                    if (
                        !is_numeric($formula[$campo])
                        || (float) $formula[$campo] < 0
                    ) {
                        throw new InvalidArgumentException(
                            "El campo {$campo} de la fórmula de la posición {$indice} no es válido"
                        );
                    }
                }
            }

            // Eliminar las fórmulas anteriores.
            // La transacción garantiza que se recuperen si algo falla.
            $stmtDelete = $pdo->prepare("
                DELETE FROM FORMULAS_TRATAMIENTO
                WHERE FK_Ficha = :idFicha
            ");

            $stmtDelete->execute([
                ':idFicha' => $id
            ]);

            // Insertar las fórmulas recibidas.
            if (!empty($formulasNuevas)) {

                $stmtInsertFormula = $pdo->prepare("
                    INSERT INTO FORMULAS_TRATAMIENTO (
                        FK_Ficha,
                        ZonaFase,
                        Tono,
                        Gramos,
                        Oxidante,
                        CantidadOxidante,
                        Proporcion,
                        Tiempo
                    ) VALUES (
                        :idFicha,
                        :zonaFase,
                        :tono,
                        :gramos,
                        :oxidante,
                        :cantidadOxidante,
                        :proporcion,
                        :tiempo
                    )
                ");

                foreach ($formulasNuevas as $formula) {

                    $stmtInsertFormula->execute([
                        ':idFicha' => $id,
                        ':zonaFase' => $formula['ZonaFase'],
                        ':tono' => $formula['Tono'],
                        ':gramos' => $formula['Gramos'],
                        ':oxidante' => $formula['Oxidante'],
                        ':cantidadOxidante' => $formula['CantidadOxidante'],
                        ':proporcion' => $formula['Proporcion'],
                        ':tiempo' => $formula['Tiempo']
                    ]);
                }
            }
        }

        // Recuperar los datos actualizados de la ficha.
        $stmtFichaNueva = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ");

        $stmtFichaNueva->execute([
            ':idFicha' => $id
        ]);

        $fichaNueva = $stmtFichaNueva->fetch(PDO::FETCH_ASSOC);

        // Recuperar las fórmulas actualizadas.
        $stmtFormulasNuevas = $pdo->prepare("
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

        $stmtFormulasNuevas->execute([
            ':idFicha' => $id
        ]);

        $formulasNuevas = $stmtFormulasNuevas->fetchAll(
            PDO::FETCH_ASSOC
        );

        // Construir los datos completos para el historial.
        $datosAnteriores = [
            'Ficha' => $fichaAnterior,
            'Formulas' => $formulasAnteriores
        ];

        $datosNuevos = [
            'Ficha' => $fichaNueva,
            'Formulas' => $formulasNuevas
        ];

        // Registrar la modificación.
        registrarHistorialFicha(
            $pdo,
            $id,
            $idUsuario,
            'MODIFICACION',
            'borrador',
            'borrador',
            $datosAnteriores,
            $datosNuevos
        );

        // Confirmar todos los cambios.
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'Ficha de tratamiento actualizada correctamente',
            'id' => $id,
            'estado' => 'borrador'
        ]);

    } catch (InvalidArgumentException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        response(400, [
            'success' => false,
            'message' => $e->getMessage()
        ]);

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al actualizar ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al actualizar la ficha de tratamiento'
        ]);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error inesperado al actualizar ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al actualizar la ficha'
        ]);
    }
}


/**
 * Envía una ficha de tratamiento al profesor.
 *
 * PATCH /api/fichas-tratamiento/{id}/enviar
 *
 * Permisos:
 * - Administrador: puede enviar cualquier ficha.
 * - Peluquero: solamente puede enviar sus propias fichas.
 * - Profesor: puede enviar cualquier ficha.
 *
 * Transición permitida:
 * - borrador -> enviada.
 *
 * Registra la operación en HISTORIAL_FICHAS_TRATAMIENTO.
 */
function enviarFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    $rol = (int) $usuario['FK_rol'];
    $idUsuario = (int) $usuario['IDPeluquero'];

    // Comprobar que el identificador es válido.
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la ficha no es válido'
        ]);
    }

    // Los tres roles pueden realizar el envío.
    if (!in_array($rol, [1, 2, 3], true)) {
        response(403, [
            'success' => false,
            'message' => 'No tienes permisos para enviar fichas de tratamiento'
        ]);
    }

    // Identificar al usuario para los triggers de auditoría.
    establecerUsuarioAuditoria($pdo, $idUsuario);

    try {

        // Iniciar transacción.
        $pdo->beginTransaction();

        // Obtener y bloquear la ficha.
        $sql = "
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ";

        $parametros = [
            ':idFicha' => $id
        ];

        // El peluquero solamente puede enviar sus propias fichas.
        if ($rol === 2) {
            $sql .= "
                AND FK_Peluquero = :idPeluquero
            ";

            $parametros[':idPeluquero'] = $idUsuario;
        }

        $sql .= " FOR UPDATE";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        $fichaAnterior = $stmt->fetch(PDO::FETCH_ASSOC);

        // Comprobar que existe y que el usuario puede acceder a ella.
        if (!$fichaAnterior) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe o no tienes permiso para enviarla'
            ]);
        }

        // Solo se pueden enviar fichas en estado borrador.
        if ($fichaAnterior['Estado'] !== 'borrador') {
            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'Solamente se pueden enviar fichas en estado borrador',
                'estadoActual' => $fichaAnterior['Estado']
            ]);
        }

        // Obtener las fórmulas anteriores.
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

        $formulas = $stmtFormulas->fetchAll(PDO::FETCH_ASSOC);

        // Cambiar el estado y registrar los datos del envío.
        $stmtUpdate = $pdo->prepare("
            UPDATE FICHAS_TRATAMIENTO
            SET
                Estado = 'enviada',
                FechaEnvio = NOW(),
                FechaModificacion = NOW(),
                FK_modificadoPor = :idModificadoPor
            WHERE IDFicha = :idFicha
        ");

        $stmtUpdate->execute([
            ':idModificadoPor' => $idUsuario,
            ':idFicha' => $id
        ]);

        // Obtener los datos actualizados de la ficha.
        $stmtFichaNueva = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ");

        $stmtFichaNueva->execute([
            ':idFicha' => $id
        ]);

        $fichaNueva = $stmtFichaNueva->fetch(PDO::FETCH_ASSOC);

        // Preparar los datos anteriores y posteriores para el historial.
        $datosAnteriores = [
            'Ficha' => $fichaAnterior,
            'Formulas' => $formulas
        ];

        $datosNuevos = [
            'Ficha' => $fichaNueva,
            'Formulas' => $formulas
        ];

        // Registrar el envío en el historial.
        registrarHistorialFicha(
            $pdo,
            $id,
            $idUsuario,
            'ENVIO',
            'borrador',
            'enviada',
            $datosAnteriores,
            $datosNuevos
        );

        // Confirmar la transacción.
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'Ficha enviada correctamente',
            'id' => $id,
            'estadoAnterior' => 'borrador',
            'estadoNuevo' => 'enviada'
        ]);

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al enviar ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al enviar la ficha de tratamiento'
        ]);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error inesperado al enviar ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al enviar la ficha'
        ]);
    }
}

/**
 * Devuelve una ficha de tratamiento al peluquero.
 *
 * PATCH /api/fichas-tratamiento/{id}/devolver
 *
 * Permisos:
 * - Solamente el profesor (rol 3).
 *
 * Transición permitida:
 * - enviada -> borrador.
 *
 * El motivo de devolución es obligatorio.
 */
function devolverFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    $rol = (int) $usuario['FK_rol'];
    $idUsuario = (int) $usuario['IDPeluquero'];

    // Comprobar el identificador.
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la ficha no es válido'
        ]);
    }

    // Solamente el profesor puede devolver fichas.
    if ($rol !== 1 && $rol !== 3) {
        response(403, [
            'success' => false,
            'message' => 'Solamente un profesor o administrador puede devolver fichas de tratamiento'
        ]);
    }

    // Identificar al usuario para los triggers de auditoría.
    establecerUsuarioAuditoria($pdo, $idUsuario);

    try {

        // Decodificar el JSON recibido.
        try {
            $data = json_decode(
                file_get_contents('php://input'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            response(400, [
                'success' => false,
                'message' => 'El JSON enviado no es válido'
            ]);
        }

        if (!is_array($data)) {
            response(400, [
                'success' => false,
                'message' => 'Los datos enviados no son válidos'
            ]);
        }

        // Comprobar el motivo.
        $motivo = isset($data['Motivo'])
            && is_string($data['Motivo'])
            ? trim($data['Motivo'])
            : '';

        if ($motivo === '') {
            response(400, [
                'success' => false,
                'message' => 'Debes indicar el motivo de la devolución'
            ]);
        }

        // Iniciar transacción.
        $pdo->beginTransaction();

        // Obtener y bloquear la ficha.
        $stmt = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
            FOR UPDATE
        ");

        $stmt->execute([
            ':idFicha' => $id
        ]);

        $fichaAnterior = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fichaAnterior) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe'
            ]);
        }

        // Solamente se pueden devolver fichas enviadas.
        if ($fichaAnterior['Estado'] !== 'enviada') {
            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'Solamente se pueden devolver fichas en estado enviada',
                'estadoActual' => $fichaAnterior['Estado']
            ]);
        }

        // Obtener las fórmulas.
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

        $formulas = $stmtFormulas->fetchAll(PDO::FETCH_ASSOC);

        // Cambiar el estado a borrador.
        $stmtUpdate = $pdo->prepare("
            UPDATE FICHAS_TRATAMIENTO
            SET
                Estado = 'borrador',
                FechaModificacion = NOW(),
                FK_modificadoPor = :idModificadoPor
            WHERE IDFicha = :idFicha
        ");

        $stmtUpdate->execute([
            ':idModificadoPor' => $idUsuario,
            ':idFicha' => $id
        ]);

        // Obtener la ficha actualizada.
        $stmtNueva = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ");

        $stmtNueva->execute([
            ':idFicha' => $id
        ]);

        $fichaNueva = $stmtNueva->fetch(PDO::FETCH_ASSOC);

        // Preparar los datos para el historial.
        $datosAnteriores = [
            'Ficha' => $fichaAnterior,
            'Formulas' => $formulas
        ];

        $datosNuevos = [
            'Ficha' => $fichaNueva,
            'Formulas' => $formulas
        ];

        // Registrar la devolución.
        registrarHistorialFicha(
            $pdo,
            $id,
            $idUsuario,
            'DEVOLUCION',
            'enviada',
            'borrador',
            $datosAnteriores,
            $datosNuevos,
            $motivo
        );

        // Confirmar la transacción.
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'Ficha devuelta correctamente',
            'id' => $id,
            'estadoAnterior' => 'enviada',
            'estadoNuevo' => 'borrador',
            'motivo' => $motivo
        ]);

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al devolver ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al devolver la ficha de tratamiento'
        ]);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error inesperado al devolver ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al devolver la ficha'
        ]);
    }
}


/**
 * Finaliza una ficha de tratamiento.
 *
 * PATCH /api/fichas-tratamiento/{id}/finalizar
 *
 * Permisos:
 * - Administrador (rol 1).
 * - Profesor (rol 3).
 *
 * Transición permitida:
 * - enviada -> finalizada.
 *
 * Acciones:
 * - Guarda una nueva versión inmutable de la ficha.
 * - Actualiza el estado y los datos de finalización.
 * - Registra la operación en el historial.
 * - El trigger de la tabla registra automáticamente la auditoría.
 */
function finalizarFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    $rol = (int) $usuario['FK_rol'];
    $idUsuario = (int) $usuario['IDPeluquero'];

    // Comprobar que el identificador es válido.
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la ficha no es válido'
        ]);
    }

    // Solamente el administrador y el profesor pueden finalizar.
    if (!in_array($rol, [1, 3], true)) {
        response(403, [
            'success' => false,
            'message' => 'Solamente un profesor o administrador puede finalizar fichas de tratamiento'
        ]);
    }

    // Identificar al usuario para los triggers de auditoría.
    establecerUsuarioAuditoria($pdo, $idUsuario);

    try {

        // Iniciar la transacción.
        $pdo->beginTransaction();

        // Obtener y bloquear la ficha para evitar finalizaciones simultáneas.
        $stmt = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
            FOR UPDATE
        ");

        $stmt->execute([
            ':idFicha' => $id
        ]);

        $fichaAnterior = $stmt->fetch(PDO::FETCH_ASSOC);

        // Comprobar que existe la ficha.
        if (!$fichaAnterior) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe'
            ]);
        }

        // Solamente se pueden finalizar fichas enviadas.
        if ($fichaAnterior['Estado'] !== 'enviada') {
            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'Solamente se pueden finalizar fichas en estado enviada',
                'estadoActual' => $fichaAnterior['Estado']
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

        $formulas = $stmtFormulas->fetchAll(PDO::FETCH_ASSOC);

        // Calcular el siguiente número de versión.
        // La ficha está bloqueada, por lo que no pueden generarse
        // dos versiones simultáneas para la misma ficha.
        $stmtVersion = $pdo->prepare("
            SELECT COALESCE(MAX(NumeroVersion), 0) + 1
            FROM VERSIONES_FICHAS_TRATAMIENTO
            WHERE FK_Ficha = :idFicha
        ");

        $stmtVersion->execute([
            ':idFicha' => $id
        ]);

        $numeroVersion = (int) $stmtVersion->fetchColumn();

        // Cambiar el estado y registrar los datos de finalización.
        // Si finaliza un profesor, se registra como FK_Profesor.
        // Si finaliza un administrador, se conserva el profesor anterior.
        $stmtUpdate = $pdo->prepare("
            UPDATE FICHAS_TRATAMIENTO
            SET
                Estado = 'finalizada',
                FueFinalizada = TRUE,
                FechaFinalizacion = NOW(),
                FechaModificacion = NOW(),
                FK_Profesor = CASE
                    WHEN :rol = 3 THEN :idProfesor
                    ELSE FK_Profesor
                END,
                FK_modificadoPor = :idModificadoPor
            WHERE IDFicha = :idFicha
        ");

        $stmtUpdate->execute([
            ':rol' => $rol,
            ':idProfesor' => $idUsuario,
            ':idModificadoPor' => $idUsuario,
            ':idFicha' => $id
        ]);

        // Obtener los datos actualizados de la ficha.
        $stmtFichaNueva = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ");

        $stmtFichaNueva->execute([
            ':idFicha' => $id
        ]);

        $fichaNueva = $stmtFichaNueva->fetch(PDO::FETCH_ASSOC);

        // Preparar los datos anteriores y posteriores.
        $datosAnteriores = [
            'Ficha' => $fichaAnterior,
            'Formulas' => $formulas
        ];

        $datosNuevos = [
            'Ficha' => $fichaNueva,
            'Formulas' => $formulas
        ];

        // Convertir la ficha y sus fórmulas a JSON.
        $jsonFicha = json_encode(
            $fichaNueva,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        $jsonFormulas = json_encode(
            $formulas,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        // Guardar una nueva versión inmutable.
        $stmtInsertVersion = $pdo->prepare("
            INSERT INTO VERSIONES_FICHAS_TRATAMIENTO (
                FK_Ficha,
                NumeroVersion,
                DatosFicha,
                DatosFormulas,
                FK_finalizadoPor
            )
            VALUES (
                :idFicha,
                :numeroVersion,
                :datosFicha,
                :datosFormulas,
                :idFinalizadoPor
            )
        ");

        $stmtInsertVersion->execute([
            ':idFicha' => $id,
            ':numeroVersion' => $numeroVersion,
            ':datosFicha' => $jsonFicha,
            ':datosFormulas' => $jsonFormulas,
            ':idFinalizadoPor' => $idUsuario
        ]);

        // Registrar la finalización en el historial de fichas.
        registrarHistorialFicha(
            $pdo,
            $id,
            $idUsuario,
            'FINALIZACION',
            'enviada',
            'finalizada',
            $datosAnteriores,
            $datosNuevos
        );

        // Confirmar la transacción.
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'Ficha finalizada correctamente',
            'id' => $id,
            'estadoAnterior' => 'enviada',
            'estadoNuevo' => 'finalizada',
            'numeroVersion' => $numeroVersion
        ]);

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al finalizar ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al finalizar la ficha de tratamiento'
        ]);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error inesperado al finalizar ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al finalizar la ficha'
        ]);
    }
}


/**
 * Reabre una ficha de tratamiento finalizada.
 *
 * PATCH /api/fichas-tratamiento/{id}/reabrir
 *
 * Permisos:
 * - Administrador (rol 1).
 * - Profesor (rol 3).
 *
 * Transición permitida:
 * - finalizada -> borrador.
 *
 * El motivo de reapertura es obligatorio.
 *
 * Las versiones anteriores se conservan y no se modifican:
 * - FueFinalizada permanece TRUE.
 * - FechaFinalizacion conserva la última fecha de finalización.
 * - FK_Profesor conserva el profesor de la última finalización.
 */
function reabrirFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    $rol = (int) $usuario['FK_rol'];
    $idUsuario = (int) $usuario['IDPeluquero'];

    // Comprobar que el identificador es válido.
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la ficha no es válido'
        ]);
    }

    // Solamente el administrador y el profesor pueden reabrir.
    if (!in_array($rol, [1, 3], true)) {
        response(403, [
            'success' => false,
            'message' => 'Solamente un profesor o administrador puede reabrir fichas de tratamiento'
        ]);
    }

    // Obtener el cuerpo JSON de la petición.
    try {
        $datos = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        response(400, [
            'success' => false,
            'message' => 'El cuerpo de la petición no contiene un JSON válido'
        ]);
    }

    // Comprobar que se ha indicado un motivo.
    $motivo = $datos['Motivo'] ?? null;

    if (!is_string($motivo) || trim($motivo) === '') {
        response(400, [
            'success' => false,
            'message' => 'El motivo de reapertura es obligatorio'
        ]);
    }

    $motivo = trim($motivo);

    // Identificar al usuario para los triggers de auditoría.
    establecerUsuarioAuditoria($pdo, $idUsuario);

    try {

        // Iniciar la transacción.
        $pdo->beginTransaction();

        // Obtener y bloquear la ficha.
        $stmt = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
            FOR UPDATE
        ");

        $stmt->execute([
            ':idFicha' => $id
        ]);

        $fichaAnterior = $stmt->fetch(PDO::FETCH_ASSOC);

        // Comprobar que existe la ficha.
        if (!$fichaAnterior) {
            $pdo->rollBack();

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe'
            ]);
        }

        // Solamente se pueden reabrir fichas finalizadas.
        if ($fichaAnterior['Estado'] !== 'finalizada') {
            $pdo->rollBack();

            response(409, [
                'success' => false,
                'message' => 'Solamente se pueden reabrir fichas en estado finalizada',
                'estadoActual' => $fichaAnterior['Estado']
            ]);
        }

        // Obtener las fórmulas actuales.
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

        $formulas = $stmtFormulas->fetchAll(PDO::FETCH_ASSOC);

        // Cambiar el estado a borrador.
        // No modificamos FueFinalizada, FechaFinalizacion ni FK_Profesor.
        $stmtUpdate = $pdo->prepare("
            UPDATE FICHAS_TRATAMIENTO
            SET
                Estado = 'borrador',
                FechaModificacion = NOW(),
                FK_modificadoPor = :idModificadoPor
            WHERE IDFicha = :idFicha
        ");

        $stmtUpdate->execute([
            ':idModificadoPor' => $idUsuario,
            ':idFicha' => $id
        ]);

        // Obtener los datos actualizados de la ficha.
        $stmtFichaNueva = $pdo->prepare("
            SELECT *
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ");

        $stmtFichaNueva->execute([
            ':idFicha' => $id
        ]);

        $fichaNueva = $stmtFichaNueva->fetch(PDO::FETCH_ASSOC);

        // Preparar los datos anteriores y posteriores.
        $datosAnteriores = [
            'Ficha' => $fichaAnterior,
            'Formulas' => $formulas
        ];

        $datosNuevos = [
            'Ficha' => $fichaNueva,
            'Formulas' => $formulas
        ];

        // Registrar la reapertura en el historial.
        registrarHistorialFicha(
            $pdo,
            $id,
            $idUsuario,
            'REAPERTURA',
            'finalizada',
            'borrador',
            $datosAnteriores,
            $datosNuevos,
            $motivo
        );

        // Confirmar la transacción.
        $pdo->commit();

        response(200, [
            'success' => true,
            'message' => 'Ficha reabierta correctamente',
            'id' => $id,
            'estadoAnterior' => 'finalizada',
            'estadoNuevo' => 'borrador',
            'motivo' => $motivo
        ]);

    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error al reabrir ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al reabrir la ficha de tratamiento'
        ]);

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Error inesperado al reabrir ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al reabrir la ficha'
        ]);
    }
}


/**
 * Consulta el historial de operaciones de una ficha.
 *
 * GET /api/fichas-tratamiento/{id}/historial
 *
 * Permisos:
 * - Administrador (rol 1): todas las fichas.
 * - Profesor (rol 3): todas las fichas.
 * - Peluquero (rol 2): solamente sus propias fichas.
 *
 * Devuelve las operaciones ordenadas de más reciente a más antigua,
 * incluyendo los datos anteriores y posteriores de cada operación.
 */
function listarHistorialFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    // Obtener el usuario autenticado.
    $usuario = authenticateUser($pdo);

    $rol = (int) $usuario['FK_rol'];
    $idUsuario = (int) $usuario['IDPeluquero'];

    // Comprobar que el identificador es válido.
    if ($id <= 0) {
        response(400, [
            'success' => false,
            'message' => 'El identificador de la ficha no es válido'
        ]);
    }

    try {

        // Comprobar que existe la ficha y obtener su peluquero.
        $sqlFicha = "
            SELECT
                IDFicha,
                FK_Peluquero
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ";

        $stmtFicha = $pdo->prepare($sqlFicha);

        $stmtFicha->execute([
            ':idFicha' => $id
        ]);

        $ficha = $stmtFicha->fetch(PDO::FETCH_ASSOC);

        // No revelar si existe una ficha que el usuario no puede consultar.
        if (
            !$ficha
            || (
                $rol === 2
                && (int) $ficha['FK_Peluquero'] !== $idUsuario
            )
        ) {
            response(404, [
                'success' => false,
                'message' => 'La ficha no existe o no tienes permiso para consultar su historial'
            ]);
        }

        // Obtener todas las operaciones del historial.
        $stmtHistorial = $pdo->prepare("
            SELECT
                h.IDHistorialFicha,
                h.IDFichaReferencia,
                h.FK_Ficha,
                h.Operacion,
                h.EstadoAnterior,
                h.EstadoNuevo,
                h.DatosAnteriores,
                h.DatosNuevos,
                h.Motivo,
                h.FechaOperacion,
                h.FK_peluquero,

                p.Usuario AS UsuarioResponsable,
                p.Nombre AS NombreResponsable,
                p.Apellido1 AS Apellido1Responsable,
                p.Apellido2 AS Apellido2Responsable

            FROM HISTORIAL_FICHAS_TRATAMIENTO h

            LEFT JOIN PELUQUEROS p
                ON p.IDPeluquero = h.FK_peluquero

            WHERE h.FK_Ficha = :idFicha

            ORDER BY
                h.FechaOperacion DESC,
                h.IDHistorialFicha DESC
        ");

        $stmtHistorial->execute([
            ':idFicha' => $id
        ]);

        $historial = $stmtHistorial->fetchAll(PDO::FETCH_ASSOC);

        // Convertir los datos JSON a estructuras PHP.
        // De esta forma el cliente recibe objetos JSON y no cadenas
        // que contienen JSON.
        foreach ($historial as &$registro) {

            if ($registro['DatosAnteriores'] !== null) {
                $registro['DatosAnteriores'] = json_decode(
                    $registro['DatosAnteriores'],
                    true
                );
            }

            if ($registro['DatosNuevos'] !== null) {
                $registro['DatosNuevos'] = json_decode(
                    $registro['DatosNuevos'],
                    true
                );
            }
        }

        unset($registro);

        // Devolver el historial.
        response(200, [
            'success' => true,
            'idFicha' => $id,
            'total' => count($historial),
            'historial' => $historial
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al consultar historial de ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al consultar el historial de la ficha'
        ]);

    } catch (Throwable $e) {

        error_log(
            'Error inesperado al consultar historial de ficha: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Se ha producido un error al consultar el historial'
        ]);
    }
}


/**
 * Lista las versiones históricas de una ficha de tratamiento.
 *
 * Administrador y profesor pueden consultar cualquier ficha.
 * El peluquero solamente puede consultar sus propias fichas.
 *
 * No devuelve el contenido completo de los JSON, únicamente
 * los datos identificativos de cada versión.
 */
function listarVersionesFichaTratamiento(int $id): void
{
    $pdo = getPDO();

    $usuario = authenticateUser($pdo);

    try {

        // Comprobar que la ficha existe y que el usuario tiene permiso.
        $sqlFicha = "
            SELECT
                IDFicha,
                FK_Peluquero
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ";

        $paramsFicha = [
            ':idFicha' => $id
        ];

        // Los peluqueros solamente pueden consultar sus propias fichas.
        if ((int) $usuario['FK_rol'] === 2) {

            $sqlFicha .= "
                AND FK_Peluquero = :idPeluquero
            ";

            $paramsFicha[':idPeluquero'] =
                (int) $usuario['IDPeluquero'];
        }

        $stmtFicha = $pdo->prepare($sqlFicha);
        $stmtFicha->execute($paramsFicha);

        $ficha = $stmtFicha->fetch(PDO::FETCH_ASSOC);

        if (!$ficha) {

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe o no tienes permiso para consultarla'
            ]);
        }

        // Obtener el listado de versiones de la ficha.
        $stmtVersiones = $pdo->prepare("
            SELECT
                v.IDVersion,
                v.FK_Ficha,
                v.NumeroVersion,
                v.FechaVersion,
                v.FK_finalizadoPor,

                p.Usuario AS UsuarioFinalizador,
                p.Nombre AS NombreFinalizador,
                p.Apellido1 AS Apellido1Finalizador,
                p.Apellido2 AS Apellido2Finalizador

            FROM VERSIONES_FICHAS_TRATAMIENTO v

            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = v.FK_finalizadoPor

            WHERE v.FK_Ficha = :idFicha

            ORDER BY v.NumeroVersion DESC
        ");

        $stmtVersiones->execute([
            ':idFicha' => $id
        ]);

        $versiones = $stmtVersiones->fetchAll(PDO::FETCH_ASSOC);

        response(200, [
            'success' => true,
            'idFicha' => $id,
            'total' => count($versiones),
            'versiones' => $versiones
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al listar versiones de ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al consultar las versiones de la ficha'
        ]);
    }
}


/**
 * Recupera el contenido completo de una versión histórica
 * de una ficha de tratamiento.
 *
 * Las versiones son inmutables y se recuperan directamente
 * desde VERSIONES_FICHAS_TRATAMIENTO.
 *
 * Administrador y profesor pueden consultar cualquier ficha.
 * El peluquero solamente puede consultar sus propias fichas.
 */
function obtenerVersionFichaTratamiento(
    int $id,
    int $numeroVersion
): void {

    $pdo = getPDO();

    $usuario = authenticateUser($pdo);

    try {

        // Comprobar que la ficha existe y que el usuario tiene permiso.
        $sqlFicha = "
            SELECT
                IDFicha,
                FK_Peluquero
            FROM FICHAS_TRATAMIENTO
            WHERE IDFicha = :idFicha
        ";

        $paramsFicha = [
            ':idFicha' => $id
        ];

        // Restringir la consulta a las fichas del peluquero.
        if ((int) $usuario['FK_rol'] === 2) {

            $sqlFicha .= "
                AND FK_Peluquero = :idPeluquero
            ";

            $paramsFicha[':idPeluquero'] =
                (int) $usuario['IDPeluquero'];
        }

        $stmtFicha = $pdo->prepare($sqlFicha);
        $stmtFicha->execute($paramsFicha);

        $ficha = $stmtFicha->fetch(PDO::FETCH_ASSOC);

        if (!$ficha) {

            response(404, [
                'success' => false,
                'message' => 'La ficha no existe o no tienes permiso para consultarla'
            ]);
        }

        // Recuperar la versión solicitada y los datos de su autor.
        $stmtVersion = $pdo->prepare("
            SELECT
                v.IDVersion,
                v.FK_Ficha,
                v.NumeroVersion,
                v.DatosFicha,
                v.DatosFormulas,
                v.FechaVersion,
                v.FK_finalizadoPor,

                p.Usuario AS UsuarioFinalizador,
                p.Nombre AS NombreFinalizador,
                p.Apellido1 AS Apellido1Finalizador,
                p.Apellido2 AS Apellido2Finalizador

            FROM VERSIONES_FICHAS_TRATAMIENTO v

            INNER JOIN PELUQUEROS p
                ON p.IDPeluquero = v.FK_finalizadoPor

            WHERE
                v.FK_Ficha = :idFicha
                AND v.NumeroVersion = :numeroVersion
        ");

        $stmtVersion->execute([
            ':idFicha' => $id,
            ':numeroVersion' => $numeroVersion
        ]);

        $version = $stmtVersion->fetch(PDO::FETCH_ASSOC);

        if (!$version) {

            response(404, [
                'success' => false,
                'message' => 'La versión solicitada no existe'
            ]);
        }

        // Decodificar los datos históricos de la ficha.
        $datosFicha = json_decode(
            $version['DatosFicha'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Decodificar las fórmulas históricas.
        $datosFormulas = json_decode(
            $version['DatosFormulas'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Construir la respuesta sin modificar los datos almacenados.
        unset(
            $version['DatosFicha'],
            $version['DatosFormulas']
        );

        response(200, [
            'success' => true,
            'version' => array_merge(
                $version,
                [
                    'Ficha' => $datosFicha,
                    'Formulas' => $datosFormulas
                ]
            )
        ]);

    } catch (JsonException $e) {

        error_log(
            'Error al decodificar versión de ficha: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Los datos almacenados de esta versión no tienen un formato válido'
        ]);

    } catch (PDOException $e) {

        error_log(
            'Error al obtener versión de ficha de tratamiento: '
            . $e->getMessage()
        );

        response(500, [
            'success' => false,
            'message' => 'Error al consultar la versión de la ficha'
        ]);
    }
}
