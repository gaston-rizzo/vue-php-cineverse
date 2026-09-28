<?php

/* ######################################################################## 
 * Middleware encargado de validar la autenticación del usuario.
 *
 * Verifica si existe un usuario autenticado en la sesión antes de permitir
 * el acceso a rutas protegidas.
 * Este middleware se utiliza para proteger endpoints que requieren login.
 * 
 * Un middleware es una capa intermedia que se ejecuta antes del controlador
 * para validar o procesar la petición. 
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth\Session;
use App\Core\Http\Response;

class AuthMiddleware
{
    /* ==================================================================================
     * Verifica que exista un usuario autenticado.
     *
     * Si no existe una sesión válida:
     *  - Devuelve HTTP 401.
     *  - Envía el código AUTH_REQUIRED.
     *  - Retorna null.
     *
     * Si la sesión es válida:
     *  - Devuelve el id del usuario autenticado.
     *
     * Esto permite reutilizar la validación de autenticación
     * en cualquier controlador que requiera acceso protegido.        
     * ================================================================================== */
    public function requireAuth(): bool
    {
        if (!Session::has("user_id")) {
            Response::error("AUTH_REQUIRED", 401);
            return false;
        }

        return true;
    }
}