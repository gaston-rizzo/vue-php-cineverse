<?php

/* ########################################################################
 * Clase encargada de proporcionar una instancia del logger
 * de la aplicación.
 *
 * Utiliza Monolog para registrar errores inesperados ocurridos durante
 * la ejecución del backend.
 *
 * Los logs se almacenan automáticamente en archivos diarios,
 * conservando un historial limitado para evitar el crecimiento
 * indefinido del almacenamiento.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

use Throwable;

final class AppLogger
{
    private static ?Logger $logger = null;

    /* ==================================================================================
     * Obtiene la instancia única del logger de la aplicación.
     *
     * Si el logger aún no fue inicializado:
     *  - Crea una nueva instancia de Monolog.
     *  - Configura un manejador con rotación diaria de archivos.
     *  - Conserva los últimos 30 archivos de log eliminando de forma
     *    automática los archivos más antiguos cuando supera la cantidad
     *    configurada.
     *  - Configura un formato de salida legible para los registros.
     *
     * Los archivos generados tienen el formato:
     *
     *      app-AAAA-MM-DD.log
     *
     * Ejemplos:
     *
     *      storage/logs/app-2026-06-25.log
     *      storage/logs/app-2026-06-26.log
     *
     * Cada registro posee el siguiente formato:
     *
     *      [2026-06-25 21:47:13] ERROR: mensaje del error
     *      { contexto en JSON }
     *
     * El contexto (array PHP) se convierte a JSON y se concatena al mensaje del log.     
     *
     * Devuelve:
     *  - Instancia compartida del logger.
     * ================================================================================== */
    public static function get(): Logger
    {
        if (self::$logger === null) {

            // Crea la instancia del logger (canal "app") y la guarda en una propiedad estática
            // para reutilizarla en toda la aplicación.
            // "app" es el nombre del logger.
            // Sirve para identificar y agrupar los logs de esta aplicación.
            self::$logger = new Logger("app");

            // Se obtiene la ruta final al path lista para que Monolog escriba el archivo.
            $logPath = self::resolveLogPath();

            // Configura un manejador con rotación diaria de archivos.
            // Se conservarán los últimos 30 archivos de log y los más
            // antiguos serán eliminados automáticamente.
            //
            // RotatingFileHandler es una clase de Monolog que se encarga de escribir logs
            // en archivos y crear automáticamente un archivo nuevo por cada día (rotación diaria).
            // Además, gestiona la eliminación de logs antiguos según la cantidad configurada.
            $handler = new RotatingFileHandler(
                $logPath,
                30,
                Level::Error
            );

            // setFormatter define el formato de salida de cada línea del log.
            // Es decir, controla cómo Monolog escribirá cada registro en el archivo.
            //
            // En este caso se utiliza LineFormatter, que reemplaza automáticamente
            // los placeholders (%...%) por la información correspondiente del log:
            //
            // - %datetime%   : fecha y hora en que ocurrió el error.
            // - %level_name% : nivel del log (por ejemplo: ERROR, WARNING, INFO).
            // Los dos puntos (:) se agregan en el formato del LineFormatter.
            // - %message%    : mensaje enviado al logger (incluye el contexto en JSON
            //                  porque AppLogger::error() lo concatena manualmente).
            $handler->setFormatter(
                new LineFormatter( 
                    "[%datetime%] %level_name%: %message%\n", /* formato del log (estructura de la línea) */
                    "Y-m-d H:i:s",  /* formato de la fecha/hora */
                    true,           /* permite saltos de línea dentro de message/context sin romper el log */
                    true            /* evita imprimir context/extra vacíos para no ensuciar el archivo */
                )
            );

            // Registra el manejador configurado en la instancia
            // del logger de la aplicación.
            self::$logger->pushHandler($handler);
        }

        return self::$logger;
    }

    /* ==================================================================================
     * Devuelve la ruta donde se guardan los logs de la aplicación.
     *
     * Usa CINEVERSE_LOG_PATH como carpeta destino y genera ahi el archivo base
     * cineverse-backend.log. Monolog agrega la fecha al rotar:
     * cineverse-backend-YYYY-MM-DD.log.
     * ================================================================================== */
    private static function resolveLogPath(): string
    {
        // Base absoluta del proyecto.
        //
        // Es el directorio raíz físico del backend en el sistema de archivos.
        // Desde aca se construyen todas las rutas internas del proyecto.
        //
        // Ejemplo:
        // /path/to/project/backend
        //
        // Se utiliza como punto de referencia fijo para resolver rutas absolutas
        // hacia recursos como storage, logs, uploads, cache, etc.
        $basePath = dirname(__DIR__, 2);

        // Carpeta de logs definida en .env.
        $relativePath = $_ENV["CINEVERSE_LOG_PATH"] ?? "storage/logs";

        // Permite rutas relativas al proyecto y rutas absolutas de Windows/Linux.
        $isAbsolutePath = preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/])/', $relativePath) === 1;
        $logDir = $isAbsolutePath
            ? $relativePath
            : $basePath . DIRECTORY_SEPARATOR . $relativePath;

        // Archivo base. RotatingFileHandler lo guarda como cineverse-backend-YYYY-MM-DD.log.
        $logPath = rtrim($logDir, "\\/") . DIRECTORY_SEPARATOR . "cineverse-backend.log";

        // Obtiene el directorio donde se va a guardar el archivo de log.
        $logDir = dirname($logPath);

        // Verifica si el directorio de logs existe físicamente en el sistema.
        // Si no existe, se crea automáticamente.
        if (!is_dir($logDir)) {
            // Crea el directorio de logs.            
            // 0777 = permisos completos (lectura, escritura, ejecución)
            // true  = creación recursiva (crea carpetas intermedias si no existen)
            mkdir($logDir, 0777, true);
        }

        return $logPath;
    }

    /* ==================================================================================
     * Registra un error en el log de la aplicación.
     *
     * Este método construye un único string combinando:
     *  - El mensaje del error (message)
     *  - El contexto adicional (context) convertido a JSON legible
     *
     * El objetivo es tener logs claros y completos en una sola entrada,
     * evitando depender del formato automático de Monolog para el contexto.
     *
     * Ejemplo:
     *
     *  AppLogger::error($e->getMessage(), [
     *      "controller" => "AuthController",
     *      "method" => "register",
     *      "username" => $username,
     *      "email" => $email,     
     * ]);
     *
     * Salida en el log:
     *
     *  [2026-06-30 11:43:16] ERROR: SMTP Error: Could not authenticate.
     *  {
     *       "controller": "AuthController",
     *       "method": "register",
     *       "username": "test",
     *       "email": "test@gmail.com"
     *  }
     * ================================================================================== */    
    public static function error(
        string $message,
        array $context = []
    ): void
    {
        // Registra el error en el logger principal de la aplicación.
        // Se construye un único string combinando el mensaje + contexto.
        // El método error() de Monolog crea el registro con nivel ERROR.
        // Ese nivel luego es utilizado por LineFormatter para reemplazar
        // el placeholder %level_name% en el formato del log.
        self::get()->error(
            // json_encode convierte el array $context a texto JSON legible,
            // para poder verlo directamente en el archivo de logs.    
            // JSON_PRETTY_PRINT: lo formatea con saltos de línea e indentación
            // JSON_UNESCAPED_UNICODE: evita que caracteres como acentos se escapen
            $message . "\n" .
            json_encode(
                $context,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            )
        );
    }
}
