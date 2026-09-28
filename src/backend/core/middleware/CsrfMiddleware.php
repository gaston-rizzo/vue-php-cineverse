<?php

/* ######################################################################## 
 * Middleware encargado de validar el token CSRF en la petición.
 *
 * Obtiene el token enviado en el encabezado HTTP X-CSRF-Token y lo compara
 * con el token almacenado en la sesión del usuario.
 *
 * Protege las operaciones que modifican datos frente a ataques Cross-Site
 * Request Forgery (CSRF), donde un sitio externo intenta forzar acciones
 * sin el consentimiento del usuario autenticado.
 *
 * Un middleware es una capa intermedia que se ejecuta antes del controlador
 * para validar o procesar la petición.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth\Session;
use App\Core\Http\Response;

class CsrfMiddleware
{
    /* ==================================================================================
     * Verifica que la petición incluya un token CSRF válido.
     *
     * Obtiene el valor del encabezado HTTP X-CSRF-Token y comprueba
     * que coincida con el token almacenado en la sesión del usuario.
     *
     * Si el token no existe o es inválido:
     *  - Devuelve HTTP 403.
     *  - Envía el código INVALID_CSRF_TOKEN.
     *  - Retorna false.
     * 
     * Si el token es válido:
     *  - Retorna true.
     *
     * Este método protege las operaciones que modifican información
     * frente a ataques Cross-Site Request Forgery (CSRF).
     * una técnica mediante la cual un sitio malicioso intenta que el navegador del
     * usuario autenticado ejecute acciones sin su consentimiento.
     * ================================================================================== */
    public function validateCsrf(): bool
    {
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

        if (!Session::validateCsrfToken($token)) {
            Response::error("INVALID_CSRF_TOKEN", 403);
            return false;
        }

        return true;
    }
}