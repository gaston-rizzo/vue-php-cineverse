<?php

/* ########################################################################
 * Clase encargada de crear y devolver la conexión PDO de la aplicación.
 *
 * Lee la configuración de la base de datos desde variables de entorno
 * cargadas previamente en el bootstrap.
 *
 * Tener la conexión en esta clase permite:
 *  - Evitar duplicación de lógica de conexión.
 *  - Mantener una única configuración de PDO.
 *  - Separar la creación de la conexión del resto de la aplicación.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $host = $_ENV['DB_HOST'];
        $port = $_ENV['DB_PORT'];
        $dbName = $_ENV['DB_NAME'];
        $user = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'] ?? '';
        $charset = $_ENV['DB_CHARSET'];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        return new PDO(
            $dsn,
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    }
}