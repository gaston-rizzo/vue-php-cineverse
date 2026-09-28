<?php

/* ########################################################################
 * Servicio encargado de gestionar la información del perfil del usuario.
 *
 * Agrupa la lógica relacionada con estadísticas y datos derivados
 * de la actividad del usuario autenticado.
 *
 * Define la cantidad de reviews recientes a mostrar en el perfil
 * (LATEST_REVIEWS_LIMIT), regla de negocio que se pasa al modelo Profile
 * en lugar de vivir en él.
 *
 * Utiliza el modelo Profile para acceder a la información almacenada
 * en la base de datos.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Services;

use App\Models\Profile;

use PDO;

class ProfileService
{
    private PDO $pdo;
    private const LATEST_REVIEWS_LIMIT = 6;

    /* ==================================================================================
     * Inicializa el servicio del perfil
     *
     * Recibe la conexión a la base de datos utilizada para acceder
     * a la información del perfil.
     * ================================================================================== */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* =========================================================================
     * Obtiene un resumen del perfil de un usuario.
     *
     * Devuelve:
     *  - Cantidad total de reviews realizadas.
     *  - Listado con las últimas LATEST_REVIEWS_LIMIT reviews del usuario.
     * ========================================================================= */
    public function getProfile(int $userId): array
    {
        return Profile::getProfile(
            $this->pdo, 
            $userId, 
            self::LATEST_REVIEWS_LIMIT
        );
    }
}