<?php

/* ########################################################################
 * Clase responsable de construir respuestas HTTP en formato JSON.
 * Representa la respuesta que el servidor le va a enviar de vuelta al cliente 
 *
 * Unifica el formato de respuesta para éxito y error,
 * garantizando consistencia en toda la API.
 *
 * Proporciona métodos para:
 *  - Enviar respuestas exitosas con código interno y datos opcionales.
 *  - Enviar respuestas de error con código interno y status HTTP.
 *  - Convertir la respuesta a formato JSON y la devolverla como HTTP response.
 *  - Establecer el código de estado HTTP que se enviará en la respuesta.
 *
 * Todas las respuestas siguen este formato:
 *
 * {
 *   "success": true|false,
 *   "code": "CODIGO_INTERNO",
 *   "data": { ... } // opcional
 * }
 *
 * Esta clase evita duplicación de lógica en los controladores y mantiene
 * consistente la forma en que la API responde.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\Http;

class Response
{
    /* ==================================================================================
     * Envía una respuesta JSON de éxito con el formato estándar de la API.
     *
     * Parámetros:
     *  - code: código interno de éxito.
     *  - data: datos opcionales que se incluirán dentro de la clave "data".
     *  - statusCode: código HTTP de la respuesta (por defecto 200).
     *
     * Ejemplo:
     * {
     *   "success": true,
     *   "code": "REVIEWS_FETCHED",
     *   "data": {
     *     "reviews": [...]
     *   }
     * }
     * ================================================================================== */
    public static function success(
        string $code,
        array $data = [],
        int $statusCode = 200
    ): void {
        self::json(
            $statusCode,
            true,
            $code,
            $data
        );
    }

    /* ==================================================================================
     * Envía una respuesta JSON de error con el formato estándar de la API.
     *
     * Parámetros:
     *  - code: código interno del error.
     *  - statusCode: código HTTP de la respuesta.
     *
     * Ejemplo:
     * {
     *   "success": false,
     *   "code": "INVALID_EMAIL"
     * }
     * ================================================================================== */
    public static function error(
        string $code,
        int $statusCode
    ): void {
        self::json(
            $statusCode,
            false,
            $code
        );
    }

    /* ==================================================================================
    * Envía una respuesta HTTP en formato JSON con la estructura estándar de la API.
    *
    * Estructura base:
    *  - success: indica si la operación fue exitosa o no.
    *  - code: código interno de éxito o error.
    *  - data: información adicional opcional.
    *
    * Este método unifica el formato de respuesta en toda la aplicación.
    * ================================================================================== */
    public static function json(
        int $statusCode,
        bool $success,
        string $code,
        array $data = []
    ): void {

        // Define el código de estado HTTP que se enviará en la respuesta.
        // Ejemplo: 200 (OK), 404 (No encontrado), 500 (Error del servidor)
        http_response_code($statusCode);

        $response = [
            "success" => $success,
            "code" => $code
        ];

        if (!empty($data)) {
            $response["data"] = $data;
        }

        // Convierte el array de respuesta a JSON y lo envía al cliente.
        // JSON_UNESCAPED_UNICODE evita que los caracteres especiales se "escapen",
        // es decir, que se conviertan en secuencias como \u00f1 en lugar de ñ.
        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE
        );
    }
}