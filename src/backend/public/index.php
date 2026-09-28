<?php

/* ########################################################################
 * Punto de entrada principal de la API.
 *
 * Todas las peticiones HTTP son procesadas a través de este archivo.
 *
 * Responsabilidades:
 * - Cargar la configuración inicial de la aplicación.
 * - Obtener la conexión principal a la base de datos.
 * - Inicializar el sistema de sesiones.
 * - Cargar la configuración de rutas.
 * - Obtener el método HTTP y la ruta solicitada.
 * - Delegar la petición al Router.
 *
 * Flujo:
 *
 * Request
 *   ->
 * index.php
 *   ->
 * Router
 *   ->
 * Controller
 *   ->
 * Service
 *   ->
 * Model
 * ######################################################################## */

declare(strict_types=1);

use App\Core\Auth\Session;
use App\Core\Http\Response;
use App\Core\Http\Router;
use App\Core\logging\AppLogger;
use App\Core\Middleware\CorsMiddleware;
use App\Database\Database;

/* --------------------------------------------------------------------------
 * Carga la configuración inicial de la aplicación
 * --------------------------------------------------------------------------
 *
 * core/bootstrap.php se encarga de:
 * - cargar Composer y el autoloader del proyecto;
 * - cargar el archivo .env;
 * - validar las variables obligatorias.
 *
 * La conexión PDO se abre aparte para poder capturar una base de datos caída
 * y responder 503 con log personalizado antes de llegar al Router.
 */
require_once __DIR__ . '/../core/bootstrap.php';

/* --------------------------------------------------------------------------
 * Carga la conexión principal de la aplicación
 * --------------------------------------------------------------------------
 *
 * Si MySQL no está disponible, Database::connect() lanza PDOException antes
 * de que existan Router o controllers. Por eso se captura acá.
 */
try {
    $pdo = Database::connect();
} catch (\PDOException $exception) {
    AppLogger::error($exception->getMessage(), [
        'controller' => 'Application',
        'method' => 'bootstrap',
        'status' => 503,
        'pdo_code' => $exception->getCode(),
    ]);

    Response::error('DATABASE_UNAVAILABLE', 503);
    exit;
}

/* --------------------------------------------------------------------------
 * Inicializa el sistema de sesiones de la aplicación.
 * --------------------------------------------------------------------------
 *
 * Session::start() se encarga de:
 * - configurar los parámetros de la cookie de sesión;
 * - iniciar la sesión de PHP si todavía no existe;
 * - controlar la expiración por inactividad;
 * - registrar la actividad del usuario.
 *
 * Esta inicialización debe ejecutarse antes de procesar cualquier ruta que
 * dependa del estado de autenticación.
 */
Session::start();

CorsMiddleware::handle();

/* --------------------------------------------------------------------------
 * Carga la configuración de rutas de la aplicación.
 * --------------------------------------------------------------------------
 *
 * routes/api.php devuelve un array con todas las rutas disponibles organizadas
 * por método HTTP.
 *
 * Cada ruta define:
 * - el controlador y método que deben ejecutarse;
 * - si requiere autenticación;
 * - si requiere validación CSRF.
 */
$routes = require __DIR__ . '/../routes/api.php';

/* --------------------------------------------------------------------------
 * Obtiene el método HTTP utilizado en la petición.
 * --------------------------------------------------------------------------
 *
 * Se utiliza para determinar qué conjunto de rutas debe evaluar el Router.
 */
$method = $_SERVER['REQUEST_METHOD'];

/* --------------------------------------------------------------------------
 * Obtiene únicamente la ruta solicitada.
 * --------------------------------------------------------------------------
 *
 * parse_url() elimina parámetros de consulta y conserva solamente el path.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Elimina el directorio base cuando la aplicación no está instalada
// directamente en la raíz del servidor.
$basePath = '/cineverse/backend/public';

$path = str_replace($basePath, '', $path);

/* --------------------------------------------------------------------------
 * Delega la resolución de la ruta al Router.
 * --------------------------------------------------------------------------
 *
 * El Router busca una coincidencia entre método HTTP y ruta solicitada.
 * Si encuentra una coincidencia ejecuta el controller correspondiente.
 */
$router = new Router($routes);

$router->dispatch(
    $method,
    $path,
    $pdo
);
