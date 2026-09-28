<?php

/* ########################################################################
 * Controlador encargado de gestionar la información del perfil del usuario.
 *
 * Permite obtener un resumen del perfil del usuario autenticado,
 * incluyendo estadísticas y las últimas reviews publicadas.
 *
 * La autenticación del usuario es validada previamente por el middleware.
 * Este controlador delega la obtención de la información al ProfileService.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth\Session;
use App\Core\Http\Response;
use App\Services\ProfileService;
use App\Core\logging\AppLogger;

use Throwable;
use PDO;

class ProfileController
{
    private ProfileService $profileService;

    /* ==================================================================================
     * Inicializa el controlador de perfil.
     *
     * Crea una instancia del servicio encargado de gestionar
     * la lógica relacionada con el perfil del usuario.
     * ================================================================================== */
    public function __construct(PDO $pdo)
    {
        $this->profileService = new ProfileService($pdo);
    }

    /* ==================================================================================
     * Obtiene un resumen del perfil del usuario autenticado.
     *
     * Requiere:
     *  - Usuario autenticado mediante sesión activa.
     *
     * Devuelve:
     *  - Cantidad total de reviews realizadas.
     *  - Listado con las últimas seis reviews del usuario.
     *
     * Respuestas posibles:
     *  - 200: resumen del perfil obtenido correctamente.
     *  - 401: usuario no autenticado.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function getProfile(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $userId = null;

        try {

            // Se obtiene el id del usuario de la sesión
            $userId = Session::getUserId();

            // Se obtiene el resumen del perfil del usuario
            $profileSummary = $this->profileService->getProfile($userId);

            // Se envía la respuesta con el resumen del perfil
            Response::success(
                "PROFILE_FETCHED",
                $profileSummary
            );
        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "ProfileController",
                "method" => "getProfile",
                "user_id" => $userId
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }
}
