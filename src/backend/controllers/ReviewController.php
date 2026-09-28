<?php

/* ########################################################################
 * Controlador encargado de la gestión de reviews de películas.
 *
 * Permite a los usuarios autenticados crear, actualizar, eliminar
 * y consultar sus reviews, además de consultar las reviews de una
 * película.
 *
 * Obtiene y valida los datos enviados por el cliente y delega
 * la lógica de negocio en ReviewService.
 *
 * La autenticación del usuario y la validación del token CSRF
 * en las rutas protegidas son realizadas previamente por el Router
 * mediante los middlewares correspondientes.
 *
 * ReviewService se encarga de aplicar las reglas de negocio, como:
 *  - Impedir que un usuario cree más de una review para la misma película.
 *  - Permitir modificar o eliminar únicamente las reviews propias.
 *  - Obtener las reviews y estadísticas de una película.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth\Session;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Validation\Validator;
use App\Core\logging\AppLogger;
use App\Services\ReviewService;
use App\Validators\ReviewValidator;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use PDO;

class ReviewController
{
    private ReviewService $reviewService;
    private Request $request;

    /* ==================================================================================
     * Inicializa el controlador de reviews.
     *
     * Crea una instancia del servicio encargado de gestionar
     * la lógica de negocio relacionada con reviews.
     * ================================================================================== */
    public function __construct(PDO $pdo)
    {
        $this->request = new Request();
        $this->reviewService = new ReviewService($pdo);
    }

   /* ==================================================================================
    * Crea una nueva review.
    *
    * Requiere:
    *  - Usuario autenticado.
    *  - Token CSRF válido.
    *
    * Obtiene y valida los datos enviados por el cliente,
    * delegando en ReviewService la creación de la review.
    *
    * ReviewService impide que un usuario publique más de una
    * review para la misma película.
    *
    * Respuestas posibles:
    *  - 200: review creada correctamente.
    *  - 409: la review ya existe.
    *  - 422: datos inválidos.
    *  - 500: error interno del servidor.
    * ================================================================================== */
    public function create(): void    
    {
        header("Content-Type: application/json; charset=utf-8");

        $userId = null; 
        $movieId = null;

        try {

            // Se obtiene el id del usuario de la sesión
            $userId = Session::getUserId();

            $data = $this->request->json();

            $movieId = Validator::int($data["movie_id"]);

            $movieTitle = Validator::stringTrim($data["movie_title"] ?? "");

            $rating = Validator::int($data["rating"]);

            $comment = ReviewValidator::normalizeComment(
                            Validator::stringTrim($data["comment"] ?? "")
                       );

            // Se validan los datos de la review
            ReviewValidator::validateCreate(
                $movieId,
                $movieTitle,
                $rating,
                $comment
            );

            // Se crea la review
            $this->reviewService->create(
                $userId,
                $movieId,
                $movieTitle,
                $rating,
                $comment
            );

            // Respuesta de registro exitoso
            Response::success(
                "REVIEW_CREATED"
            );

        } catch (InvalidArgumentException $e) {
            Response::error(
                $e->getMessage(),
                422
            );
        } catch (RuntimeException $e) {

            if ($e->getMessage() === "INVALID_JSON_BODY") {
                Response::error(
                    $e->getMessage(),
                    400
                );
                return;
            }

            if ($e->getMessage() === "REVIEW_ALREADY_EXISTS") {
                Response::error(
                    $e->getMessage(),
                    409
                );                
                return;
            }

            Response::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "ReviewController",
                "method" => "create",
                "user_id" => $userId,
                "movie_id" => $movieId
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

   /* ==================================================================================
    * Actualiza una review existente.
    *
    * Requiere:
    *  - Usuario autenticado.
    *  - Token CSRF válido.
    *
    * Obtiene y valida los datos enviados por el cliente y delega la
    * actualización de la review al ReviewService.
    *
    * En una actualización no se valida movie_id porque la película
    * asociada a la review no cambia.
    *
    * Respuestas posibles:
    *  - 200: review actualizada correctamente.
    *  - 403: la review pertenece a otro usuario.
    *  - 404: review no encontrada.
    *  - 422: datos inválidos.
    *  - 500: error interno del servidor.
    * ================================================================================== */
    public function update(int $reviewId): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $userId = null; 
        $movieId = null;

        try {

            $userId = Session::getUserId();
            
            $data = $this->request->json();
            
            $rating = Validator::int($data["rating"]);            

            $comment = ReviewValidator::normalizeComment(
                            Validator::stringTrim($data["comment"] ?? "")
                       );

            // Se validan los datos de la review
            ReviewValidator::validateUpdate(
                $rating,
                $comment
            );

            // Se actualiza la review
            $this->reviewService->update(
                $userId,
                $reviewId,
                $rating,
                $comment
            );

            // Respuesta de registro exitoso
            Response::success(
                "REVIEW_UPDATED"
            );

        } catch (InvalidArgumentException $e) {                        
            Response::error(
                $e->getMessage(),
                422
            );
        } catch (RuntimeException $e) {

            switch ($e->getMessage()) {

                case "INVALID_JSON_BODY":

                    Response::error(
                        $e->getMessage(),
                        400
                    );

                break;

                case "REVIEW_NOT_FOUND":

                    Response::error(
                        $e->getMessage(),
                        404
                    );

                    break;

                case "NOT_REVIEW_OWNER":

                    Response::error(
                        $e->getMessage(),
                        403
                    );

                    break;

                default:

                    Response::error(
                        $e->getMessage(),
                        400
                    );
            }

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "ReviewController",
                "method" => "update",
                "user_id" => $userId,
                "movie_id" => $movieId
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Elimina una review existente.
     *
     * Requiere:
     *  - Usuario autenticado.
     *  - Token CSRF válido.
     *  - Que la review exista.
     *  - Que la review pertenezca al usuario autenticado.
     *
     * Respuestas posibles:
     *  - 200: review eliminada correctamente.
     *  - 401: usuario no autenticado.
     *  - 403: la review pertenece a otro usuario.
     *  - 404: review no encontrada.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function delete(int $reviewId): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $userId = null; 
        $movieId = null;

        try {

            $userId = Session::getUserId();

            $this->reviewService->delete(
                $userId,
                $reviewId
            );

            // Respuesta de registro exitoso
            Response::success(
                "REVIEW_DELETED"
            );

        } catch (RuntimeException $e) {

            switch ($e->getMessage()) {

                case "REVIEW_NOT_FOUND":

                    Response::error(
                        $e->getMessage(),
                        404
                    );

                break;

                case "NOT_REVIEW_OWNER":

                    Response::error(
                        $e->getMessage(),
                        403
                    );

                break;

                default:

                    Response::error(
                        $e->getMessage(),
                        400
                    );
            }
        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "ReviewController",
                "method" => "delete",
                "user_id" => $userId,
                "movie_id" => $movieId                
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

   /* ==================================================================================
    * Obtiene las reviews del usuario autenticado, paginadas.
    *
    * Requiere:
    *  - Usuario autenticado.
    *
    * No requiere validación CSRF porque esta operación únicamente consulta
    * información y no modifica el estado del servidor.
    *
    * Parámetros:
    *  - page (query string, opcional, default 1): página solicitada.
    *
    * Devuelve:
    *  - Listado paginado de reviews creadas por el usuario.
    *  - Información de paginación (page, has_next).
    *
    * Respuestas posibles:
    *  - 200: reviews obtenidas correctamente.
    *  - 401: usuario no autenticado.
    *  - 422: parámetro de página inválido.
    *  - 500: error interno del servidor.
    * ================================================================================== */
    public function getUserReviews(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $userId = null;

        try {

            $userId = Session::getUserId();

            $page = Validator::int(
                $this->request->query("page", 1)
            );

            $data = $this->reviewService->getUserReviews(
                $userId,
                $page
            );

            // Respuesta de registro exitoso
            Response::success(
                "REVIEWS_FETCHED",
                $data
            );

        } catch (InvalidArgumentException $e) {
            Response::error(
                $e->getMessage(),
                422
            );
        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "ReviewController",
                "method" => "getUserReviews",
                "user_id" => $userId                
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Obtiene las reviews de una película específica con paginación.
     *
     * Este método:
     *  - Recibe el ID de la película desde la ruta.
     *  - Obtiene el número de página desde el query string (?page=N).
     *  - Obtiene el usuario autenticado (si existe sesión).
     *  - Solicita a ReviewService:
     *      - Las estadísticas de la película (promedio y cantidad total de reviews).
     *      - La review del usuario autenticado (si existe).
     *      - El listado paginado de reviews de la comunidad.
     *
     * Notas:
     *  - Si el usuario no está autenticado, "user_review" será null.
     *  - Es un endpoint de solo lectura, por lo que no requiere validación CSRF.
     *
     * Respuestas posibles:
     *  - 200: REVIEWS_FETCHED.
     *  - 422: parámetro de página inválido.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function getMovieReviews(int $movieId): void
    {
        header("Content-Type: application/json; charset=utf-8");

        try {

            $page = Validator::int(
                $this->request->query("page", 1)
            );

            $userId = Session::getUserId();

            $data = $this->reviewService->getMovieReviews(
                $movieId,
                $page,
                $userId
            );

            Response::success(
                "REVIEWS_FETCHED",
                $data
            );

        } catch (InvalidArgumentException $e) {
            Response::error(
                $e->getMessage(),
                422
        );
        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "ReviewController",
                "method" => "getMovieReviews",
                "movie_id" => $movieId
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }
}