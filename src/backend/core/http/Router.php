<?php

/* ########################################################################
 * Clase encargada de resolver las peticiones HTTP de la aplicación.
 *
 * Recibe el método HTTP y la ruta solicitada por el cliente,
 * localiza la ruta correspondiente en la configuración definida
 * y ejecuta el controlador asociado.
 * 
 * Soporta rutas estáticas (/login) y dinámicas (/reviews/{id}),
 * extrayendo automáticamente los parámetros de la URL.
 *
 * Además, antes de delegar la petición al controlador:
 *  - verifica los requisitos de autenticación cuando la ruta lo exige;
 *  - valida el token CSRF en las operaciones protegidas.
 * 
 * Si la ruta no existe, devuelve una respuesta de error 404.
 *
 * Ejemplos de peticiones recibidas:
 *  - POST /login ejecuta AuthController::login().
 *  - GET /profile/stats ejecuta ProfileController::stats().
 *  - PUT /reviews/15 ejecuta ReviewController::update(15).     
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\Http;

use App\Core\Session;
use App\Core\Http\Response;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\CsrfMiddleware;

use PDO;

class Router
{
    private array $routes;

    /* ==================================================================================
     * Inicializa el router de la aplicación.
     *
     * Recibe la configuración de rutas que será utilizada
     * para resolver las peticiones entrantes.
     * ================================================================================== */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /* ==================================================================================
     * Procesa una petición HTTP y ejecuta la ruta correspondiente.
     *
     * Busca las rutas registradas para el método HTTP solicitado
     * (GET, POST, PUT, DELETE, etc.) y verifica si alguna coincide
     * con la URL recibida.
     *
     * Soporta:
     *  - Rutas estáticas:
     *     /login
     *     /profile/stats    
     *  - Rutas dinámicas con parámetros:
     *     /reviews/{id}
     *
     * Los parámetros definidos en la ruta dinámica son convertidos
     * internamente a expresiones regulares para extraer sus valores
     * y pasarlos al método del controlador correspondiente.
     *
     * Ejemplo:    
     *
     * Petición recibida:
     *     PUT /reviews/15
     *
     * Ruta registrada:
     *     /reviews/{id}
     *
     * Parámetro extraído:
     *     id = 15
     *
     * Si la ruta coincide:
     *  - Aplica las validaciones configuradas (autenticación y CSRF).
     *  - Instancia el controlador asociado.
     *  - Ejecuta el método configurado pasando los parámetros extraídos.
     *
     * Si no existe ninguna coincidencia:
     *  - Devuelve HTTP 404.
     *  - Responde con ROUTE_NOT_FOUND.
     * ================================================================================== */
    public function dispatch(
        string $method,
        string $path,
        PDO $pdo
    ): void {

        // Obtiene las rutas registradas para el método HTTP solicitado.
        // Si no existen rutas para ese método, utiliza un array vacío.
        $methodRoutes = $this->routes[$method] ?? [];

        /*
        * Se instancian los middlewares porque no se usan como métodos estáticos en toda la aplicación.
        * Esto permite mantener un diseño consistente: todo lo que forma parte del flujo HTTP
        * (controladores, servicios y middlewares) se maneja como objetos.
        *
        * Además, evita acoplar el Router a llamadas estáticas distribuidas por toda la aplicación,
        * manteniendo el flujo de ejecución más controlado dentro de esta capa.
        */
        $authMiddleware = new AuthMiddleware();
        $csrfMiddleware = new CsrfMiddleware();

        // Recorre todas las rutas registradas para el método actual.
        foreach ($methodRoutes as $route => $routeData) {

            // Convierte cada parámetro dinámico definido entre llaves ({...})
            // en un grupo de captura de una expresión regular.
            //
            // Esto permite que la expresión regular capture el valor enviado
            // por el cliente en esa posición de la URL.
            //
            // Ejemplo:
            //
            // Ruta registrada:
            //     /reviews/{id}
            //
            // Se transforma en:
            //     /reviews/([0-9]+)
            //
            // donde:
            // - [0-9]  → cualquier dígito.
            // - +      → uno o más dígitos.
            // - (...)  → captura el valor para recuperarlo posteriormente.
            $pattern = preg_replace(
                '#\{[a-zA-Z_]+\}#',
                '([0-9]+)',
                $route
            );

            // Construye el patrón final utilizado por preg_match().
            //
            // Se añaden:
            // - ^ : obliga a que la coincidencia comience al inicio de la URL.
            // - $ : obliga a que termine al final de la URL.
            //
            // De esta forma sólo se aceptan coincidencias exactas.
            //
            // Ejemplo:
            //
            // Ruta:
            //     /reviews/{id}
            //
            // Tras reemplazar el parámetro:
            //     /reviews/([0-9]+)
            //
            // Patrón final:
            //     #^/reviews/([0-9]+)$#
            //
            // Coincide con:
            //     /reviews/15
            //
            // No coincide con:
            //     /reviews/15/edit
            //     /api/reviews/15
            //     /reviews/15/extra
            $pattern = '#^' . $pattern . '$#';

            // Verifica si la URL solicitada coincide con el patrón de la ruta.
            //
            // Parámetros:
            // - $pattern: expresión regular generada a partir de la ruta registrada.
            // - $path: URL solicitada por el cliente.
            // - $matches: array donde preg_match() almacena la coincidencia
            //   completa y los parámetros capturados.
            //
            // Ejemplo:
            //
            // Ruta registrada:
            //     /movies/{movieId}/reviews
            //
            // Patrón generado:
            //     #^/movies/([0-9]+)$#
            //
            // URL solicitada:
            //     /movies/550/reviews
            //
            // Si la URL coincide con el patrón, preg_match() devuelve true
            // y $matches queda:
            //
            // [
            //     0 => "/movies/550/reviews",
            //     1 => "550"
            // ]
            if (preg_match($pattern, $path, $matches)) {

                // Validar sesión
                if ($routeData["auth"]) {
                    if (!$authMiddleware->requireAuth()) {
                        return;
                    }
                }

                // Validar token CSRF
                if ($routeData["csrf"]) {                                    
                    // Verifica que el token CSRF enviado por el cliente
                    // coincida con el almacenado en la sesión para impedir
                    // solicitudes realizadas desde sitios externos.
                    if (!$csrfMiddleware->validateCsrf()) {
                        return;
                    }
                }

                // preg_match() guarda en la primera posición del array ($matches[0])
                // la URL completa que coincidió con la ruta.
                //
                // Los parámetros capturados por los grupos de la expresión regular
                // comienzan a partir de la posición 1.
                //
                // Ejemplo:
                //
                // Ruta registrada:
                //     /movies/{movieId}/reviews
                //
                // URL solicitada:
                //     /movies/550/reviews
                //
                // Resultado de preg_match():
                // [
                //     0 => "/movies/550/reviews",
                //     1 => "550"
                // ]                                
                //
                // array_shift() elimina el primer elemento del array (la URL completa)
                // y reindexa los elementos restantes, dejando únicamente los parámetros
                // capturados:
                //
                // [
                //     0 => "550"
                // ]
                array_shift($matches);

                // Los valores capturados por preg_match() siempre son strings.
                //
                // array_map() recorre cada parámetro capturado y, si contiene
                // únicamente dígitos, lo convierte a int mediante ctype_digit().
                // En caso contrario, conserva el valor original.
                //
                // Esto permite que los argumentos enviados al controlador
                // respeten los tipos declarados en sus métodos.
                //
                // Ejemplo:
                //
                // Antes:
                // [
                //     "550"
                // ]
                //
                // Después:
                // [
                //     550
                // ]
                //
                // De este modo, la llamada:
                //
                //     $controller->getMovieReviews(...$matches);
                //
                // equivale a:
                //
                //     $controller->getMovieReviews(550);
                //
                // permitiendo que el método:
                //
                //     public function getMovieReviews(int $movieId): void
                //
                // reciba un entero en lugar de un string.
                $matches = array_map(
                    static fn (string $value) => ctype_digit($value)
                        ? (int) $value
                        : $value,
                    $matches
                );

                // La configuración de la ruta (routes.php) define un "handler",
                // que indica qué controlador y qué método deben ejecutarse.
                //
                // Ejemplo:
                //
                // "handler" => [ReviewController::class, "update"]
                //
                // Se extraen ambos valores para poder instanciar el controlador
                // e invocar el método correspondiente.
                [$controllerClass, $method] = $routeData["handler"];

                // Crea una instancia del controlador utilizando la conexión
                // a la base de datos.
                $controller = new $controllerClass($pdo);

                // Ejecuta el método del controlador asociado a la ruta.
                //
                // El array $matches contiene los parámetros extraídos de la URL.
                // El operador "..." toma cada elemento del array y lo pasa como
                // un argumento independiente al método.
                //
                // Ejemplo:
                //
                // $matches:
                // [
                //     15
                // ]
                //
                // La siguiente llamada:
                //
                //      $controller->$method(...$matches);
                //
                // equivale a:
                //
                //      $controller->update(15);
                //
                // Si hubiera varios parámetros:
                //
                // $matches:
                // [
                //     550,
                //     8
                // ]
                //
                // equivaldría a:
                //
                //      $controller->algúnMétodo(550, 8);
                $controller->$method(...$matches);

                return;
            }
        }

        // No se encontró ninguna coincidencia para la
        // combinación de método HTTP y ruta solicitada.
        Response::error("ROUTE_NOT_FOUND", 404);
    }
}