
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
