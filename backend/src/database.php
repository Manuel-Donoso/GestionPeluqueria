<?php
/**
 * Gestión de la conexión con MariaDB mediante PDO.
 */

/**
 * Devuelve una conexión PDO con la base de datos.
 *
 * @return PDO
 * @throws PDOException Si no se puede conectar.
 */
function getPDO(): PDO
{
    $dbHost = getenv('DB_HOST') ?: 'database';
    $dbPort = getenv('DB_PORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: 'peluqueria';
    $dbUser = getenv('DB_USER') ?: 'peluqueria_app';
    $dbPassword = getenv('DB_PASSWORD') ?: '';

    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

    return new PDO(
        $dsn,
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
}
