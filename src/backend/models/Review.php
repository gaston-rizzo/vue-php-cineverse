<?php

/* ########################################################################
 * Modelo que gestiona las operaciones relacionadas con las reviews
 * publicadas por los usuarios sobre películas.
 *
 * Responsabilidades principales:
 *  - Crear nuevas reviews.
 *  - Obtener reviews por usuario, película o identificador.
 *  - Obtener estadísticas de las reviews de una película.
 *  - Verificar si un usuario ya realizó una review sobre una película.
 *  - Actualizar la puntuación y el comentario de una review.
 *  - Eliminar reviews existentes.
 *
 * Este modelo agrupa las consultas de lectura y escritura sobre la
 * tabla reviews, garantizando que cada operación se realice de forma
 * consistente.
 *
 * Todas las operaciones se realizan mediante consultas preparadas con PDO,
 * garantizando protección frente a inyección SQL.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Models;

use PDO;

class Review
{
    /* ==================================================================================
     * Crea una nueva review asociada a un usuario y una película.
     *
     * Inserta:
     *  - Usuario propietario de la review.
     *  - Película valorada (id y título, guardado tal como estaba en el
     *    momento de creación, sin dependencia de TMDB en lecturas futuras).
     *  - Puntuación asignada.
     *  - Comentario realizado.     
     * ================================================================================== */
    public static function create(
        PDO $pdo,
        int $userId,
        int $movieId,
        string $movieTitle,
        int $rating,
        string $comment
    ): void {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (
                user_id,
                movie_id,
                movie_title,
                rating,
                comment
            )
            VALUES (
                :user_id,
                :movie_id,
                :movie_title,
                :rating,
                :comment
            )
        ");

        $stmt->execute([
            ":user_id" => $userId,
            ":movie_id" => $movieId,
            ":movie_title" => $movieTitle,
            ":rating" => $rating,
            ":comment" => $comment
        ]);
    }

    /* ==================================================================================
     * Actualiza los datos modificables de una review existente.
     *
     * Modifica:
     *  - Rating asignado.
     *  - Comentario asociado.
     *
     * Devuelve:
     *  - true si la operación fue ejecutada correctamente.
     *  - false si ocurrió un error durante la actualización.     
     * ================================================================================== */
    public static function update(
        PDO $pdo,
        int $reviewId,
        int $rating,
        string $comment
    ): bool {
        $stmt = $pdo->prepare("
            UPDATE reviews
            SET
                rating = :rating,
                comment = :comment
            WHERE id = :id
        ");

        return $stmt->execute([
            ":id" => $reviewId,
            ":rating" => $rating,
            ":comment" => $comment
        ]);
    }

    /* ==================================================================================
     * Elimina una review existente mediante su id.
     *
     * Devuelve:
     *  - true si la eliminación fue ejecutada correctamente.
     *  - false si ocurrió un error durante la operación.     
     * ================================================================================== */
    public static function delete(
        PDO $pdo,
        int $reviewId
    ): bool {
        $stmt = $pdo->prepare("
            DELETE FROM reviews
            WHERE id = :id
        ");

        return $stmt->execute([
            ":id" => $reviewId
        ]);
    }

    /* ==================================================================================
     * Obtiene las reviews creadas por un usuario, de forma paginada.
     *
     * Devuelve:
     *  - Lista paginada de reviews pertenecientes al usuario indicado.
     *
     * Las reviews se ordenan desde la más reciente hasta la más antigua.
     * ================================================================================== */
    public static function findReviewsByUserId(
        PDO $pdo,
        int $userId,
        int $limit,
        int $offset
    ): array {
        $stmt = $pdo->prepare("
            SELECT
                id,
                user_id,
                movie_id,
                movie_title,
                rating,
                comment,
                created_at,
                updated_at
            FROM reviews
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");

        // Esto es seguro porque:
        // $limit no viene del usuario; lo define el backend.
        // $offset se calcula a partir de un número de página validado como entero.
        // No hay posibilidad de SQL Injection.
        $stmt->execute([
            ":user_id" => $userId
        ]);

        return $stmt->fetchAll();
    }

    /* ==================================================================================
     * Cuenta la cantidad total de reviews publicadas por un usuario.
     *
     * Se utiliza para calcular correctamente la paginación del listado
     * de reviews del usuario en su perfil.
     * ================================================================================== */
    public static function countReviewsByUserId(
        PDO $pdo,
        int $userId
    ): int {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total
            FROM reviews
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            ":user_id" => $userId
        ]);

        return (int) $stmt->fetchColumn();
    }

    /* ==================================================================================
     * Verifica si un usuario ya realizó una review sobre una película específica.
     *
     * Se utiliza para evitar que un mismo usuario publique más de una review
     * para la misma película. No trae el contenido de la review, solo
     * confirma su existencia.
     *
     * Devuelve:
     *  - true si el usuario ya tiene una review para esa película.
     *  - false si no existe.     
    * ================================================================================== */
    public static function existsReviewByUserIdAndMovieId(
        PDO $pdo,
        int $userId,
        int $movieId
    ): bool {
        $stmt = $pdo->prepare("
            SELECT 1
            FROM reviews
            WHERE user_id = :user_id
            AND movie_id = :movie_id
            LIMIT 1
        ");

        $stmt->execute([
            ":user_id" => $userId,
            ":movie_id" => $movieId
        ]);

        return (bool) $stmt->fetchColumn();
    }

    /* ==================================================================================
     * Busca si un usuario ya realizó una review sobre una película específica.
     *
     * Se utiliza para evitar que un mismo usuario publique más de una review
     * para la misma película.
     *
     * Devuelve:
     *  - Datos de la review encontrada.
     *  - false si no existe.     
     * ================================================================================== */
    public static function findReviewByUserIdAndMovieId(
        PDO $pdo,
        int $userId,
        int $movieId
    ): array|false {
        $stmt = $pdo->prepare("
            SELECT
                id,
                user_id,
                movie_id,
                rating,
                comment,
                created_at,
                updated_at
            FROM reviews
            WHERE user_id = :user_id
              AND movie_id = :movie_id
            LIMIT 1
        ");

        $stmt->execute([
            ":user_id" => $userId,
            ":movie_id" => $movieId
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Busca una review mediante su id único.
     *
     * Devuelve:
     *  - Datos completos de la review.
     *  - false si no existe una review con ese ID.     
     * ================================================================================== */
    public static function findReviewById(
        PDO $pdo,
        int $id
    ): array|false {
        $stmt = $pdo->prepare("
            SELECT
                id,
                user_id,
                movie_id,
                rating,
                comment,
                created_at,
                updated_at
            FROM reviews
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ":id" => $id
        ]);

        return $stmt->fetch();
    }

    /* ==================================================================================
     * Obtiene las reviews asociadas a una película.
     *
     * Devuelve:
     *  - Lista paginada de reviews.
     *  - Información básica del usuario que realizó la valoración.
     *
     * Las reviews se ordenan desde la más reciente hasta la más antigua.
     * ================================================================================== */
    public static function findReviewsByMovieId(
        PDO $pdo,
        int $movieId,
        int $limit,
        int $offset,
        ?int $excludeUserId = null
    ): array {

        $sql = "
            SELECT
                r.id,
                r.user_id,
                r.movie_id,
                r.rating,
                r.comment,
                r.created_at,
                r.updated_at,
                u.username
            FROM reviews r
            INNER JOIN users u
                ON u.id = r.user_id
            WHERE r.movie_id = :movie_id
        ";

        if ($excludeUserId !== null) {
            $sql .= "
                AND r.user_id <> :exclude_user_id
            ";
        }

        $sql .= "
            ORDER BY r.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        // Esto es seguro porque:
        // $limit no viene del usuario; lo define el backend (10).
        // $offset se calcula a partir de un número de página validado como entero.
        // No hay posibilidad de SQL Injection.
        $stmt = $pdo->prepare($sql);

        $params = [
            ":movie_id" => $movieId
        ];

        if ($excludeUserId !== null) {
            $params[":exclude_user_id"] = $excludeUserId;
        }

        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /* ==================================================================================
     * Cuenta la cantidad de reviews de la comunidad para una película,
     * excluyendo, si corresponde, la review del usuario autenticado.
     *
     * Se utiliza para calcular correctamente la paginación del listado
     * de reviews de la comunidad (sin contar la propia del usuario,
     * que se muestra por separado).
     * ================================================================================== */
    public static function countCommunityReviews(
        PDO $pdo,
        int $movieId,
        ?int $excludeUserId = null
    ): int {

        $sql = "
            SELECT COUNT(*) AS total
            FROM reviews
            WHERE movie_id = :movie_id
        ";

        if ($excludeUserId !== null) {
            $sql .= "
                AND user_id <> :exclude_user_id
            ";
        }

        $stmt = $pdo->prepare($sql);

        $params = [
            ":movie_id" => $movieId
        ];

        if ($excludeUserId !== null) {
            $params[":exclude_user_id"] = $excludeUserId;
        }

        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /* ==================================================================================
     * Obtiene las estadísticas generales de las reviews de una película.
     *
     * Devuelve:
     *  - La cantidad total de reviews registradas.
     *  - La puntuación promedio de la película.
     *
     * Si la película aún no tiene reviews, devuelve:
     *  - total_reviews = 0
     *  - average_rating = 0
     * ================================================================================== */
    public static function getMovieReviewStats(
        PDO $pdo,
        int $movieId
    ): array
    {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) AS total_reviews,
                ROUND(AVG(rating), 1) AS average_rating
            FROM reviews
            WHERE movie_id = :movie_id
        ");

        $stmt->execute([
            ":movie_id" => $movieId
        ]);

        return $stmt->fetch() ?: [
            "total_reviews" => 0,
            "average_rating" => 0
        ];
    }
}