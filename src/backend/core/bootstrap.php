<?php

/* ########################################################################
 * Bootstrap principal de la aplicación. Es el archivo de arranque.
 *
 * Se encarga de:
 *  - Cargar el autoloader de Composer.
 *  - Cargar el autoloader propio de la aplicación.
 *  - Cargar las variables de entorno desde el archivo .env.
 *  - Validar variables obligatorias de entorno.
 * ######################################################################## */

declare(strict_types=1);

// Carga el autoloader generado por Composer. 
// Permite cargar automáticamente tanto las dependencias externas 
// instaladas mediante Composer como las clases de la aplicación 
// configuradas mediante PSR-4.
//
// PSR-4 es un estándar de autoloading que define cómo mapear namespaces
// a rutas de archivos. Por ejemplo, una clase con namespace App\Controllers
// se resuelve automáticamente en el archivo controllers/Controllers.php (según la configuración),
// evitando tener que usar require_once manualmente.
require_once __DIR__ . '/../vendor/autoload.php';

// Define la zona horaria global de la aplicación.
// Esto asegura que todas las fechas (logs, timestamps, etc.)
// usen la hora local del servidor (Argentina en este caso).
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Carga las variables del archivo .env en la aplicación.
// createImmutable() es un método estático de la librería phpdotenv
// que carga las variables del archivo .env como inmutables (no pueden modificarse desde PHP)
// y las deja disponibles en $_ENV y $_SERVER.
//
// phpdotenv es una librería que se encarga de leer el archivo .env
// para no tener credenciales ni configuraciones hardcodeadas en el código.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Verifica que existan todas las variables de entorno obligatorias 
// necesarias para el funcionamiento de la aplicación.
$dotenv->required([
    "DB_HOST",
    "DB_PORT",
    "DB_NAME",
    "DB_USER",
    "DB_CHARSET",
    "APP_ENV",
    "APP_DEBUG",
    "CINEVERSE_LOG_PATH"
]);

// Valida que APP_ENV contenga únicamente uno de los entornos permitidos.
$dotenv->required('APP_ENV')->allowedValues([
    "local",
    "development",
    "production"
]);

// Valida que APP_DEBUG tenga un valor booleano válido.
$dotenv->required('APP_DEBUG')->allowedValues([
    "true",
    "false",
    "1",
    "0"
]);
