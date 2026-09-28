<?php

/* ########################################################################
 * Clase estática encargada de gestionar la sesión de la aplicación.
 *
 * Agrupa todas las operaciones relacionadas con la sesión del usuario,
 * evitando el acceso directo a la superglobal $_SESSION desde el resto
 * de la aplicación.
 *
 * Responsabilidades:
 *  - Iniciar y configurar la sesión de PHP.
 *  - Gestionar la autenticación del usuario mediante la sesión.
 *  - Almacenar, obtener y eliminar variables de sesión.
 *  - Regenerar el identificador de sesión para prevenir ataques
 *    de Session Fixation.
 *  - Destruir completamente la sesión al cerrar la autenticación.
 *  - Generar el token CSRF utilizado para proteger las peticiones
 *    que modifican información.
 *
 * Al agrupar esta lógica en una única clase se facilita el
 * mantenimiento del código, se evita la duplicación y se mejora
 * la consistencia del manejo de sesiones en toda la aplicación.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\Auth;

final class Session
{
    /* ==================================================================================
     * Inicia y configura la sesión de la aplicación.
     *
     * Si todavía no existe una sesión activa:
     *  - Configura los parámetros de la cookie de sesión.
     *  - Inicia la sesión de PHP.
     *  - Comprueba si la sesión ha expirado por inactividad.
     *  - Registra el instante de la última actividad del usuario.
     *
     * Si la sesión ya está iniciada, no realiza ninguna acción.
     * ================================================================================== */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        /* --------------------------------------------------------------------------
           Configuración de la cookie de sesión
           --------------------------------------------------------------------------
        
         Define las propiedades de la cookie utilizada por PHP para mantener
         la sesión del usuario autenticado.
        
         - lifetime: la cookie expira al cerrar el navegador.
         - path: la cookie será válida para toda la aplicación.
         - domain: utiliza el dominio actual.
         - secure: envía la cookie únicamente mediante HTTPS cuando está disponible.
         - httponly: impide el acceso a la cookie desde JavaScript, reduciendo
           el riesgo de robo de sesión mediante ataques XSS.
         - samesite: ayuda a mitigar ataques CSRF limitando el envío automático
           de la cookie en peticiones iniciadas desde otros sitios. 
        */

        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "domain" => "",
            "secure" => !empty($_SERVER["HTTPS"]),
            "httponly" => true,
            "samesite" => "Lax"            
        ]);

        /* --------------------------------------------------------------------------
           Inicia la sesión.
           -------------------------------------------------------------------------- 

        La sesión se utiliza para mantener autenticado al usuario.
        Debe ejecutarse antes de acceder a $_SESSION. 
        */

        session_start();

        /* --------------------------------------------------------------------------
           Expiración por inactividad
           --------------------------------------------------------------------------
        
         Si el usuario permanece inactivo durante más de 30 minutos,
         la sesión se destruye automáticamente.
        
         Esto reduce el riesgo de que una sesión quede abierta en un
         equipo compartido o desatendido.        
        */

        $sessionTimeout = 1800;

        if (
            isset($_SESSION["last_activity"]) &&
            (time() - $_SESSION["last_activity"]) > $sessionTimeout
        ) {
            session_unset();
            session_destroy();
            session_start();
        }

        $_SESSION["last_activity"] = time();
    }

    /* ==================================================================================
     * Regenera el identificador de la sesión actual.
     *
     * Se utiliza después de una autenticación correcta para prevenir
     * ataques de Session Fixation.
     *
     * En este tipo de ataque, un atacante intenta que la víctima utilice
     * un identificador de sesión conocido previamente. Al generar un nuevo
     * identificador después del login, la sesión anterior queda invalidada
     * y el usuario continúa utilizando una sesión completamente nueva.
     * ================================================================================== */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /* ==================================================================================
     * Destruye completamente la sesión actual.
     *
     * Elimina toda la información almacenada en la sesión, invalida
     * la cookie de sesión en el navegador (cuando se utilizan cookies)
     * y destruye la sesión mantenida por PHP en el servidor.
     *
     * Se utiliza durante el cierre de sesión para impedir que el
     * identificador de sesión pueda seguir reutilizándose.
     * ================================================================================== */
    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();

            // Vaciar $_SESSION solo borra los datos de sesión en el servidor.
            // La cookie con el session_id sigue almacenada en el navegador y
            // se seguiría enviando en cada request siguiente. Por eso hace
            // falta este setcookie(): le indica explícitamente al navegador
            // que expire y elimine esa cookie, para que no vuelva a mandar
            // un session_id que ya no tiene datos asociados en el servidor.
            //
            // Se expira la cookie seteando una fecha en el pasado
            // (time() - 42000, es decir, unos 11.6 hs atrás). El valor 42000
            // no tiene ningún significado especial, es simplemente un margen
            // amplio para garantizar que la fecha quede en el pasado y el
            // navegador elimine la cookie inmediatamente al recibir esta
            // respuesta.
            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
    }

    /* ==================================================================================
     * Almacena un valor dentro de la sesión.
     *
     * Parámetros:
     *  - key: nombre de la variable de sesión.
     *  - value: información que se desea almacenar.
     *
     * Si la clave ya existe, su valor será reemplazado.
     * ================================================================================== */    
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /* ==================================================================================
     * Obtiene el valor asociado a una clave de la sesión.
     *
     * Devuelve:
     *  - El valor almacenado si la clave existe.
     *  - null cuando la clave no está definida.
     * ================================================================================== */
    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /* ==================================================================================
     * Comprueba si una clave existe dentro de la sesión.
     *
     * Devuelve:
     *  - true cuando la clave está definida.
     *  - false en caso contrario.
     * ================================================================================== */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /* ==================================================================================
     * Elimina una clave de la sesión.
     *
     * Si la clave no existe, la operación no produce ningún efecto.
     * ================================================================================== */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /* ==================================================================================
     * Indica si existe un usuario autenticado.
     *
     * Devuelve:
     *  - true cuando la sesión contiene un user_id.
     *  - false cuando no existe un usuario autenticado.
     * ================================================================================== */
    public static function isAuthenticated(): bool
    {        
        return self::has("user_id");
    }

    /* ==================================================================================
     * Obtiene el id del usuario autenticado.
     *
     * Devuelve:
     *  - El id del usuario si existe una sesión válida.
     *  - null si no hay usuario autenticado.
     * ================================================================================== */
    public static function getUserId(): ?int
    {
        return self::has("user_id")
            ? (int) self::get("user_id")
            : null;
    }

    /* ==================================================================================
     * Genera un nuevo token CSRF para la sesión actual.
     *
     * Un token CSRF es un valor aleatorio y único asociado a la sesión del
     * usuario. Su finalidad es verificar que las peticiones que modifican
     * información provienen realmente de la aplicación y no de un sitio externo.
     *
     * El token se genera mediante un mecanismo criptográficamente seguro,
     * se almacena en la sesión y posteriormente deberá enviarse desde
     * el cliente en todas las peticiones que modifiquen información
     * (POST, PUT y DELETE).
     *
     * Devuelve:
     *  - El token CSRF generado.
     * ================================================================================== */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(
            random_bytes(32)
        );

        self::set("csrf_token", $token);

        return $token;
    }    

    /* ==================================================================================
     * Obtiene el token CSRF almacenado en la sesión.
     *
     * Devuelve:
     *  - El token CSRF cuando existe.
     *  - null si todavía no se ha generado.
     * ================================================================================== */
    public static function getCsrfToken(): ?string
    {
        return self::has("csrf_token")
            ? (string) self::get("csrf_token")
            : null;
    }

    /* ==================================================================================
     * Comprueba si el token CSRF recibido coincide con el almacenado
     * en la sesión actual.
     *
     * Se utiliza para verificar que la petición fue iniciada por el
     * cliente autenticado y no por un sitio externo.
     *
     * Parámetros:
     *  - token: valor recibido mediante el encabezado X-CSRF-Token.
     *
     * Devuelve:
     *  - true cuando ambos tokens coinciden.
     *  - false en caso contrario.
     * ================================================================================== */
    public static function validateCsrfToken(string $token): bool
    {
        // hash_equals() realiza una comparación en tiempo constante,
        // evitando ataques de Timing Attack que intentan deducir el
        // valor del token midiendo el tiempo empleado en la comparación.
        
        // Tiempo constante significa que la comparación tarda prácticamente
        // lo mismo tanto si los valores coinciden como si no. De esta forma
        // se evita que un atacante pueda deducir el token correcto midiendo
        // el tiempo que tarda el servidor en realizar la comparación
        // (Timing Attack).
        return hash_equals(
            self::getCsrfToken() ?? "",
            $token
        );
    }
}