<?php

/* ######################################################################## 
 * Clase encargada de realizar validaciones básicas de tipos
 * y normalización de valores de entrada.
 *
 * Proporciona utilidades reutilizables para validar datos provenientes
 * de requests (principalmente JSON), evitando que los controladores
 * trabajen con valores inválidos o de tipos incorrectos.
 *
 * Esta clase no contiene lógica de negocio, únicamente validaciones
 * técnicas de estructura y tipo de datos.
 *
 * Su objetivo es agrupar reglas comunes de validación como:
 *
 * - Validación de strings.
 * - Validación de enteros.
 *
 * Esto ayuda a mantener consistencia en toda la aplicación y reduce
 * duplicación de código en controladores y servicios.
 * ######################################################################## */

namespace App\Core\Validation;

use InvalidArgumentException;

final class Validator
{
    /* ==================================================================================
     * Valida que el valor recibido sea un string.
     *
     * Esta función únicamente verifica el tipo de dato, sin modificarlo.
     * No aplica normalización ni transformaciones sobre el contenido.
     *
     * Ejemplos:
     *  "prueba"  -> válido
     *  123       -> inválido, lanza excepción
     *  true      -> inválido, lanza excepción
     *
     * @throws InvalidArgumentException si el valor no es un string
     * ================================================================================== */
    public static function string(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("MUST_BE_STRING");
        }

        return $value;
    }

    /* ==================================================================================
     * Valida que el valor recibido sea un string y aplica trim().
     *
     * Esta variante está pensada para campos donde los espacios
     * al inicio y al final no son relevantes (ej: email, username).
     *
     * No se utiliza trim() en la contraseña porque los espacios pueden formar
     * parte real del password.
     *
     * Ejemplos:
     *  " prueba " -> "prueba"
     *  123        -> inválido, lanza excepción
     *  true       -> inválido, lanza excepción
     *
     * @throws InvalidArgumentException si el valor no es un string
     * ================================================================================== */
    public static function stringTrim(mixed $value): string
    {
        return trim(self::string($value));
    }

    /* ==================================================================================
     * Valida que el valor recibido sea un entero válido.
     *
     * Acepta:
     *  - enteros reales (int)
     *  - strings numéricos ("123")
     *
     * Rechaza:
     *  - "abc"
     *  - true / false
     *  - arrays u objetos
     *
     * Ejemplos:
     *  123   -> válido
     * "123" -> válido
     * "abc" -> inválido
     *  true  -> inválido
     *
     * @throws InvalidArgumentException si el valor no es un entero válido
     * ================================================================================== */
    public static function int(mixed $value): int
    {
        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        if (!is_int($value)) {
            throw new InvalidArgumentException("MUST_BE_INTEGER");
        }

        return $value;
    }
}