<?php

/* ########################################################################
 * Clase que encapsula la petición HTTP entrante.
 * Representa la solicitud enviada por el cliente al servidor.
 * 
 * Proporciona una interface para acceder a los datos enviados
 * por el cliente. Esta clase no realiza validaciones de negocio ni
 * transformaciones complejas. Solo se limita a:
 *
 *  - Leer el body crudo de la petición HTTP (php://input).
 *  - Decodificar JSON a un array asociativo de PHP.
 *  - Exponer los datos de entrada mediante métodos simples.
 *  - Leer los parámetros de la URL (query string).
 * ######################################################################## */

declare(strict_types=1);

namespace App\Core\Http;

use RuntimeException;

final class Request
{
    private ?array $body = null;

    /* =========================================================================
     * Inicializa la petición HTTP.
     *
     * El cuerpo no se procesa en este momento.
     * Se leerá y decodificará automáticamente la primera vez que se invoque
     * json(), evitando trabajo innecesario en peticiones que no envían body
     * (por ejemplo, la mayoría de las peticiones GET).
    * ========================================================================= */
    public function __construct()
    {
    }

    /* =========================================================================
    * Lee el cuerpo crudo de la petición HTTP y lo convierte desde JSON
    * a un array asociativo de PHP.
    *
    * Se usa internamente para inicializar la clase.
    *
    * IMPORTANTE:
    *  - No debe ser llamado desde fuera.
    *  - Se ejecuta automáticamente la primera vez que se necesita acceder
    *    al body mediante json().
    *
    * Ejemplo de JSON recibido:
    *
    * {
    *   "username": "test",
    *   "email": "test@mail.com",
    *   "password": "123456"
    * }
    *
    * Resultado después del parseo:
    *
    * [
    *   "username" => "test",
    *   "email" => "test@mail.com",
    *   "password" => "123456"
    * ]
    *
    * @throws RuntimeException si el JSON es inválido o no es un array.
    * ========================================================================= */
    private function getJson(): array
    {
        // Lee el cuerpo crudo de la petición HTTP (raw input del cliente)
        $rawBody = file_get_contents("php://input");

        // Convierte el JSON recibido a un array asociativo de PHP
        $data = json_decode($rawBody, true);

        // Valida que el JSON haya sido decodificado correctamente y sea un array
        // Si no es válido, se corta la ejecución con una excepción
        if (!is_array($data)) {
            throw new RuntimeException("INVALID_JSON_BODY");
        }

        return $data;
    }

    /* =========================================================================
     * Devuelve el body de la petición como un array asociativo.
     *
     * La primera vez que se invoca este método, el body se lee y se
     * decodifica desde JSON. En las siguientes llamadas se reutiliza
     * el resultado almacenado, evitando procesarlo nuevamente.
     *
     * Retorna:
     *  - El body de la petición como un array asociativo.
     * ========================================================================= */
    public function json(): array
    {
        if ($this->body === null) {
            $this->body = $this->getJson();
        }

        return $this->body;
    }

    /* =========================================================================
     * Obtiene el valor de un campo del body de la petición.
     *
     * Si el body aún no fue procesado, se leerá y decodificará
     * automáticamente antes de acceder al valor solicitado.     
     *
     * Parámetros:
     *  - $key: nombre del campo a obtener.
     *  - $default: valor que se devolverá si la clave no existe.
     *
     * Retorna:
     *  - El valor asociado a la clave o el valor por defecto.
    * ========================================================================= */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->json()[$key] ?? $default;
    }

    /* =========================================================================
     * Obtiene el valor de un parámetro de la URL (query string).
     *
     * Ejemplo:
     * GET /verify-email?token=abc123
     *
     * query("token") devolverá:
     * abc123
     *
     * Parámetros:
     *  - $key: nombre del parámetro.
     *  - $default: valor que se devolverá si el parámetro no existe.
     *
     * Retorna:
     *  - El valor del parámetro o el valor por defecto.
     * ========================================================================= */
    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}