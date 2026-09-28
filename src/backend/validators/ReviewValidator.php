<?php

/* ########################################################################
 * Validador de datos de reviews.
 *
 * Se encarga de validar los datos de entrada relacionados con:
 *  - Creación de reviews
 *  - Actualización de reviews
 *
 * Solo valida formato, campos obligatorios y reglas básicas.
 * No contiene lógica de negocio ni acceso a base de datos.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Validators;

use InvalidArgumentException;

class ReviewValidator
{
    private const MOVIE_TITLE_MAX_LENGTH = 255;

    private const RATING_MIN = 1;
    private const RATING_MAX = 5;

    private const COMMENT_MIN_LENGTH = 20;
    private const COMMENT_MAX_LENGTH = 1000;

    /* ==================================================================================
     * Normaliza el comentario recibido antes de validarlo o persistirlo.
     *
     *  - Recorta espacios al inicio y final.
     *  - Colapsa 2 o más saltos de línea consecutivos en un máximo de 1
     *    línea en blanco (2 saltos), evitando que un usuario deje bloques
     *    enormes de espacio en blanco entre párrafos (abuso de "enter" repetido).
     * ================================================================================== */
    public static function normalizeComment(string $comment): string
    {
        $comment = trim($comment);

        // Colapsa también saltos con espacios/tabs intercalados (\n  \n  \n)
        $comment = preg_replace('/[ \t]*\n[ \t]*/', "\n", $comment);
        $comment = preg_replace('/\n{2,}/', "\n\n", $comment);

        return $comment;
    }

    /* ==================================================================================
     * Valida los datos necesarios para crear una nueva review.
     *
     * Reglas:
     *  - movieId debe ser mayor a 0
     *  - movieTitle es obligatorio y no puede superar los 255 caracteres
     *  - rating debe estar entre 1 y 5
     *  - comment es obligatorio
     *  - comment no puede superar los 1000 caracteres
     * ================================================================================== */
    public static function validateCreate(
        int $movieId,
        string $movieTitle,
        int $rating,
        string $comment
    ): void {

        if ($movieId <= 0) {
            throw new InvalidArgumentException(
                "INVALID_MOVIE_ID"
            );
        }

        self::validateMovieTitle($movieTitle);

        self::validateContent(
            $rating,
            $comment
        );
    }

    /* ==================================================================================
     * Valida los datos necesarios para actualizar una review.
     *
     * Reglas:
     *  - rating debe estar entre 1 y 5
     *  - comment es obligatorio
     *  - comment no puede superar los 1000 caracteres
     * ================================================================================== */
    public static function validateUpdate(
        int $rating,
        string $comment
    ): void {
        self::validateContent(
            $rating,
            $comment
        );
    }

    /* ==================================================================================
     * Valida el título de la película recibido al crear una review.
     *
     * Reglas:
     *  - movieTitle es obligatorio (no puede quedar vacío tras recortar espacios)
     *  - movieTitle no puede superar los 255 caracteres
     * ================================================================================== */
    private static function validateMovieTitle(
        string $movieTitle
    ): void {

        $movieTitle = trim($movieTitle);

        if ($movieTitle === "") {
            throw new InvalidArgumentException(
                "MOVIE_TITLE_REQUIRED"
            );
        }

        if (mb_strlen($movieTitle) > self::MOVIE_TITLE_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "MOVIE_TITLE_TOO_LONG"
            );
        }
    }

    /* ==================================================================================
     * Valida el contenido común de una review (creación y actualización).
     *
     * Reglas:
     *  - rating debe estar entre 1 y 5
     *  - comment es obligatorio (no puede quedar vacío tras recortar espacios)
     *  - comment debe tener al menos 20 caracteres
     *  - comment no puede superar los 1000 caracteres
     * ================================================================================== */
    private static function validateContent(
        int $rating,
        string $comment
    ): void {

        if ($rating < self::RATING_MIN || $rating > self::RATING_MAX) {
            throw new InvalidArgumentException(
                "INVALID_RATING"
            );
        }

        $comment = trim($comment);

        if ($comment === "") {
            throw new InvalidArgumentException(
                "COMMENT_REQUIRED"
            );
        }

        $length = mb_strlen($comment);

        if ($length < self::COMMENT_MIN_LENGTH) {
            throw new InvalidArgumentException(
                "COMMENT_TOO_SHORT"
            );
        }

        if ($length > self::COMMENT_MAX_LENGTH) {
            throw new InvalidArgumentException(
                "COMMENT_TOO_LONG"
            );
        }
    }
}