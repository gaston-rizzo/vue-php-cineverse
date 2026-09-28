<?php

/* ########################################################################
 * Servicio encargado de gestionar la lógica de negocio de las reviews.
 *
 * Permite crear, actualizar, eliminar y consultar reviews de usuarios.
 *
 * Aplica las reglas de negocio relacionadas con:
 *  - Quién puede modificar o eliminar una review.
 *  - Que un usuario no pueda crear más de una review por película.
 *  - La consulta de reviews públicas con paginación.
 *  - Las estadísticas y la información de paginación de una película. 
 *
 * Define la cantidad de reviews por página (REVIEWS_PER_PAGE)
 * 
 * Utiliza el modelo Review para interactuar con la base de datos.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Services;

use App\Models\Review;

use PDO;
use RuntimeException;

class ReviewService
{
    private PDO $pdo;
    private const REVIEWS_PER_PAGE = 12;
    
    /* ==================================================================================
     * Inicializa el servicio de reviews.
     *
     * Recibe la conexión a la base de datos utilizada para acceder
     * a la información de las reviews.
     * ================================================================================== */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /* ==================================================================================
     * Crea una nueva review.
     *
     * Verifica que el usuario no haya creado previamente una review 
     * para la la misma película.
     *
     * Requiere:
     *  - Usuario válido.
     *  - Película válida.
     *
     * Lanza:
     *  - RuntimeException si ya existe una review del usuario para la película.
     * ================================================================================== */
    public function create(
        int $userId,
        int $movieId,
        string $movieTitle,
        int $rating,
        string $comment
    ): void {

        $existingReview = Review::existsReviewByUserIdAndMovieId(
            $this->pdo,
            $userId,
            $movieId
        );

        if ($existingReview) {
            throw new RuntimeException(
                "REVIEW_ALREADY_EXISTS"
            );
        }

        Review::create(
                $this->pdo,
                $userId,
                $movieId,
                $movieTitle,
                $rating,
                $comment
            );
    }

    /* ==================================================================================
     * Actualiza una review existente.
     *
     * Verifica:
     *  - Que la review exista.
     *  - Que pertenezca al usuario autenticado.
     *
     * Lanza:
     *  - RuntimeException si la review no existe.
     *  - RuntimeException si el usuario no es propietario de la review.
    * ================================================================================== */
    public function update(
        int $userId,
        int $reviewId,
        int $rating,
        string $comment
    ): void {

        $review = Review::findReviewById(
            $this->pdo,
            $reviewId
        );

        if (!$review) {
            throw new RuntimeException(
                "REVIEW_NOT_FOUND"
            );
        }

        if ((int) $review["user_id"] !== $userId) {
            throw new RuntimeException(
                "NOT_REVIEW_OWNER"
            );
        }

        Review::update(
            $this->pdo,
            $reviewId,
            $rating,
            $comment
        );
    }

    /* ==================================================================================
     * Elimina una review existente.
     *
     * Verifica:
     *  - Que la review exista.
     *  - Que pertenezca al usuario autenticado.
     *
     * Lanza:
     *  - RuntimeException si la review no existe.
     *  - RuntimeException si el usuario no es propietario de la review.
     * ================================================================================== */
    public function delete(
        int $userId,
        int $reviewId
    ): void {

        $review = Review::findReviewById(
            $this->pdo,
            $reviewId
        );

        if (!$review) {
            throw new RuntimeException(
                "REVIEW_NOT_FOUND"
            );
        }

        if ((int) $review["user_id"] !== $userId) {
            throw new RuntimeException(
                "NOT_REVIEW_OWNER"
            );
        }

        Review::delete(
            $this->pdo,
            $reviewId
        );
    }

    /* ==================================================================================
     * Obtiene las reviews creadas por el usuario autenticado, de forma paginada.
     *
     * Devuelve:
     *  - reviews: listado paginado de hasta REVIEWS_PER_PAGE reviews del usuario,
     *    ordenadas desde la más reciente hasta la más antigua.
     *  - pagination: página actual e indicador de si existe una página siguiente.
     * ================================================================================== */
    public function getUserReviews(
        int $userId,
        int $page
    ): array {

        // Calcula el desplazamiento correspondiente a la página solicitada.
        // Ej: si REVIEWS_PER_PAGE es 12 y se solicita la página 3,
        // offset = (3 - 1) * 12 = 24, por lo que se omiten las primeras
        // 24 reviews (ya mostradas en las páginas 1 y 2) y se obtienen
        // las reviews 25 a 36.
        $offset = ($page - 1) * self::REVIEWS_PER_PAGE;

        // Obtiene las reviews del usuario correspondientes a la página solicitada.
        $reviews = Review::findReviewsByUserId(
            $this->pdo,
            $userId,
            self::REVIEWS_PER_PAGE,
            $offset
        );

        // Obtiene la cantidad total de reviews del usuario, para poder
        // calcular correctamente la paginación.
        $total = Review::countReviewsByUserId(
            $this->pdo,
            $userId
        );

        // Indica si existe una página adicional de reviews del usuario.
        // Ej: si el usuario tiene $total = 25 reviews y estamos en la página 3
        // (offset = 24), 24 + 12 = 36, y como 36 no es menor que 25,
        // hasNext será false: no hay más páginas después de esta.
        $hasNext = ($offset + self::REVIEWS_PER_PAGE) < $total;

        return [
            "reviews" => $reviews,
            "pagination" => [
                "page" => $page,
                "has_next" => $hasNext
            ]
        ];
    }

    /* ==================================================================================
     * Obtiene las reviews públicas asociadas a una película.
     *
     * Incluye:
     *  - La review del usuario autenticado (solo en la primera página y si existe).
     *  - Reviews de la comunidad paginadas.
     *  - Estadísticas generales.
     *  - Información de paginación.
     *
     * La review del usuario se devuelve por separado para facilitar su
     * edición o eliminación desde el frontend y evitar duplicarla dentro
     * del listado de reviews de la comunidad.
     *
     * Devuelve:
     *  - stats
     *  - pagination
     *  - user_review
     *  - reviews: listado paginado de hasta REVIEWS_PER_PAGE reviews de la comunidad
     * ================================================================================== */
    public function getMovieReviews(
        int $movieId,
        int $page,
        ?int $userId = null
    ): array {

        $userReview = null;

        // Se obtiene el contenido completo de la review del usuario
        // únicamente en la página 1, ya que es la única página en la
        // que se devuelve "user_review" en el payload. En el resto de
        // las páginas no hace falta traer esta información.
        if ($page === 1 && $userId !== null) {
            $userReview = Review::findReviewByUserIdAndMovieId(
                $this->pdo,
                $userId,
                $movieId
            );
        }

        // Calcula el desplazamiento correspondiente a la página solicitada.
        $offset = ($page - 1) * self::REVIEWS_PER_PAGE;

        // Obtiene las reviews de la comunidad excluyendo, si corresponde,
        // la review del usuario autenticado para evitar duplicarla.
        $reviews = Review::findReviewsByMovieId(
            $this->pdo,
            $movieId,
            self::REVIEWS_PER_PAGE,
            $offset,
            $userId
        );

        // Obtiene las estadísticas generales de la película (promedio y
        // cantidad total de reviews, incluyendo la del usuario si existe).
        $stats = Review::getMovieReviewStats(
            $this->pdo,
            $movieId
        );

        // Cantidad de reviews de la comunidad, calculada directamente en
        // la base de datos con el mismo criterio de exclusión que usa
        // findReviewsByMovieId. Se calcula así, en lugar de restar 1 al
        // total general, para que el resultado sea correcto en TODAS las
        // páginas (no solo en la página 1), evitando el bug de que
        // "has_next" quedara mal calculado en páginas posteriores cuando
        // el usuario autenticado tenía una review propia.
        $totalCommunityReviews = Review::countCommunityReviews(
            $this->pdo,
            $movieId,
            $userId
        );

        // Indica si existe una página adicional de reviews de la comunidad.
        $hasNext =
            ($offset + self::REVIEWS_PER_PAGE) < $totalCommunityReviews;

        return [

            "stats" => [
                "average_rating" =>
                    (float) ($stats["average_rating"] ?? 0),
                "total_reviews" =>
                    (int) $stats["total_reviews"]
            ],

            "pagination" => [
                "page" => $page,
                "has_next" => $hasNext
            ],

            // Solo tiene contenido en la página 1 (ver comentario arriba).
            // En el resto de las páginas siempre es null.
            "user_review" => $userReview,

            "reviews" => $reviews
        ];
    }
}