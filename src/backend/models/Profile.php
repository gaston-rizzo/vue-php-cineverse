<?php

/* ########################################################################
 * Modelo encargado de obtener la información necesaria para construir
 * el perfil de un usuario.
 *
 * Responsabilidades principales:
 *  - Obtener un resumen del perfil del usuario.
 *  - Recuperar las últimas reviews publicadas por el usuario.
 *
 * Este modelo agrupa consultas de lectura que combinan información
 * relacionada con la actividad del usuario para ser mostrada en la
 * sección de perfil.
 *
 * Todas las operaciones se realizan mediante consultas preparadas con PDO,
 * garantizando protección frente a inyección SQL.
 * ######################################################################## */
declare(strict_types=1);

namespace App\Models;

use PDO;

class Profile
{
    /* ==================================================================================
     * Obtiene un resumen del perfil de un usuario.
     *
     * Recupera:
     *  - La cantidad total de reviews publicadas por el usuario.
     *  - Las últimas $latestReviewsLimit reviews, ordenadas por fecha
     *    de creación descendente.
     *
     * Devuelve:
     *  - Array con:
     *    - reviews_count: cantidad total de reviews publicadas.
     *    - latest_reviews: listado con las últimas $latestReviewsLimit
     *      reviews del usuario. Si el usuario no posee reviews, se
     *      devuelve un arreglo vacío.      
     * ================================================================================== */
    public static function getProfile(
        PDO $pdo, 
        int $userId, 
        int $latestReviewsLimit
    ): array {

        $reviewsCountStmt = $pdo->prepare(
            "SELECT COUNT(*) AS total
             FROM reviews
             WHERE user_id = ?"
        );
        
        $reviewsCountStmt->execute([$userId]);
        $reviewsCount = (int) $reviewsCountStmt->fetchColumn();

        $latestReviewsStmt = $pdo->prepare(
            "SELECT
                id,
                movie_id,
                movie_title,
                rating,
                comment,
                created_at
            FROM reviews
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT " . $latestReviewsLimit
        );
        $latestReviewsStmt->execute([$userId]);
        $latestReviews = $latestReviewsStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "reviews_count" => $reviewsCount,
            "latest_reviews" => $latestReviews
        ];
    }
}