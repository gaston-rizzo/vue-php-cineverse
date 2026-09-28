<?php

/* ########################################################################
 * Configuración principal de la aplicación.
 *
 * Inicializa el entorno mediante bootstrap y devuelve
 * la conexión PDO lista para ser utilizada.
 * ######################################################################## */

declare(strict_types=1);

use App\Database\Database;

require_once __DIR__ . '/../core/bootstrap.php';

return Database::connect();