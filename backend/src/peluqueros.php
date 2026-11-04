
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
