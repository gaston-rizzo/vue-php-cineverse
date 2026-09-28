<?php

/* ########################################################################
 * Controlador encargado de gestionar las operaciones relacionadas con la
 * autenticación, recuperación de contraseñas y administración de sesiones
 * de los usuarios.
 *
 * Permite registrar usuarios, iniciar sesión, verificar cuentas mediante
 * correo electrónico, reenviar correos de verificación, iniciar el proceso
 * de recuperación de contraseñas, validar el token de recuperación antes
 * de mostrar el formulario de restablecimiento, restablecer la contraseña
 * mediante dicho token, cerrar la sesión y obtener la información del
 * usuario autenticado.
 *
 * Delega la lógica de negocio al servicio de autenticación y utiliza
 * sesiones de PHP para mantener el estado de autenticación entre las
 * distintas peticiones HTTP.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth\Session;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\logging\AppLogger;
use App\Core\Validation\Validator;
use App\Services\AuthService;
use App\Validators\AuthValidator;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use PDO;

class AuthController
{
    private AuthService $authService;
    private Request $request;

    /* ==================================================================================
     * Inicializa el controlador de autenticación.
     *
     * Crea una instancia del servicio encargado de gestionar
     * la lógica de autenticación y sesiones.
     * ================================================================================== */
    public function __construct(PDO $pdo)
    {
        $this->request = new Request();
        $this->authService = new AuthService($pdo);
    }

    /* ==================================================================================
     * Registra un nuevo usuario en el sistema.
     *
     * Obtiene y valida los datos enviados por el cliente,
     * incluyendo el idioma solicitado para generar el correo
     * de verificación, y delega el proceso de registro al
     * servicio de autenticación.
     *
     * Respuestas posibles:
     *  - 200: usuario registrado correctamente.
     *  - 409: username o email ya existentes.
     *  - 422: datos de entrada inválidos.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function register(): void
    {
        // Indica que todas las respuestas del controlador se enviarán
        // en formato JSON codificado en UTF-8.
        header("Content-Type: application/json; charset=utf-8");

        $username = "";
        $email = "";

        try {

            // Obtiene el body completo de la request ya parseado desde JSON
            // Retorna un array asociativo con los datos enviados por el cliente
            $data = $this->request->json();

            // Extrae y valida los campos obligatorios del usuario
            // Si algún valor no es string válido, lanza excepción
            $username = Validator::stringTrim($data["username"] ?? "");
            $email = Validator::stringTrim($data["email"] ?? "");
            $password = Validator::string($data["password"] ?? "");
            $language = Validator::stringTrim($data["language"] ?? "");
            
            // Valida que el idioma solicitado corresponda a uno
            // de los idiomas soportados por la aplicación.
            AuthValidator::validateLanguage($language);

            // Se validan si los datos de entrada son correctos
            AuthValidator::validateRegister(
                $username,
                $email,
                $password
            );

            // Registra al usuario
            $this->authService->register(
                $username,
                $email,
                $password,
                $language
            );

            // Respuesta de registro exitoso
            Response::success(
                "USER_REGISTERED"
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

            if ($e->getMessage() === "EMAIL_DELIVERY_FAILED") {
                AppLogger::error($this->mailExceptionMessage($e), [
                    "controller" => "AuthController",
                    "method" => "register",
                    "username" => $username,
                    "email" => $email
                ]);

                Response::error(
                    $e->getMessage(),
                    422
                );
                return;
            }

            Response::error(
                $e->getMessage(),
                409
            );

        // Captura cualquier error o excepción no controlada previamente.
        // Se utiliza Throwable porque engloba tanto Exception como Error.
        } catch (Throwable $e) {

            // Registra el error inesperado en el log de la aplicación
            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "register",
                "username" => $username,
                "email" => $email                
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Verifica la dirección de correo electrónico de un usuario mediante
     * el token recibido desde el frontend.
     *
     * El token se obtiene desde los parámetros de la URL y se delega
     * al servicio de autenticación para completar la verificación.
     *
     * Respuestas posibles:
     *  - 200: cuenta verificada correctamente.
     *  - 400: token inválido o expirado.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function verifyEmail(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        try {

            $token = Validator::stringTrim($this->request->query("token", ""));

            $this->authService->verifyEmail($token);

            Response::success(
                "EMAIL_VERIFIED"
            );

        } catch (InvalidArgumentException $e) {

            Response::error(
                $e->getMessage(),
                422
            );

        } catch (RuntimeException $e) {

            Response::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "verifyEmail"
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

     /* ==================================================================================
     * Reenvía el correo de verificación de una cuenta.
     *
     * Recibe el email y el idioma solicitado por el cliente.
     *
     * Si la cuenta existe y aún no fue verificada, genera un nuevo
     * token de verificación y envía nuevamente el correo.
     *
     * Por motivos de seguridad, siempre devuelve una respuesta
     * exitosa aunque el email no exista o la cuenta ya esté verificada,
     * evitando revelar información sobre las cuentas registradas.
     *
     * Respuestas posibles:
     *  - 200: solicitud procesada correctamente.
     *  - 422: datos de entrada inválidos.
     *  - 400: cuerpo JSON inválido.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function resendVerificationEmail(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $email = "";

        try {

            $data = $this->request->json();

            $email = Validator::stringTrim($data["email"] ?? "");
            $language = Validator::stringTrim($data["language"] ?? "");
            
            AuthValidator::validateEmailRequest($email);
            AuthValidator::validateLanguage($language);

            $this->authService->resendVerificationEmail(
                $email,
                $language
            );

            Response::success(
                "VERIFICATION_EMAIL_SENT"
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

            if ($e->getMessage() === "EMAIL_DELIVERY_FAILED") {
                AppLogger::error($this->mailExceptionMessage($e), [
                    "controller" => "AuthController",
                    "method" => "resendVerificationEmail",
                    "email" => $email
                ]);

                Response::error(
                    $e->getMessage(),
                    422
                );

                return;
            }

            Response::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "resendVerificationEmail",
                "email" => $email
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Autentica a un usuario mediante email y contraseña.
     *
     * Obtiene y valida las credenciales enviadas por el cliente,
     * delegando el proceso de autenticación al servicio correspondiente.
     *
     * Si la autenticación es correcta, devuelve los datos básicos
     * del usuario autenticado junto con el token CSRF.
     *
     * Respuestas posibles:
     *  - 200: login realizado correctamente.
     *  - 401: credenciales inválidas.
     *  - 403: cuenta no verificada por email.
     *  - 422: datos de entrada inválidos.
     *  - 429: inicio de sesión temporalmente bloqueado por exceso de intentos.
     *  - 400: error de formato de request o error controlado.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function login(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $email = "";

        try {

            $data = $this->request->json();

            $email = Validator::stringTrim($data["email"] ?? "");
            $password = Validator::string($data["password"] ?? "");

            // Si los datos de inicio de sesión son válidos
            AuthValidator::validateLogin(
                $email,
                $password
            );

            // Se verifica si existe el usuario en la base de datos
            $data = $this->authService->login(
                $email,
                $password
            );

            // Devuelve al cliente la información necesaria para la sesión. Ejemplo:
            //
            // {
            //   "success": true,
            //   "code": "LOGIN_SUCCESS",
            //   "data": {
            //     "user": {
            //       "id": 1,
            //       "username": "test",
            //       "email": "test@mail.com",
            //       "created_at": "2025-07-20 18:30:00"
            //     },
            //     "csrf_token": "7c2d7d8b8d4e4f5a..."
            //   }
            // }            
            Response::success(
                "LOGIN_SUCCESS",
                $data
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

            if ($e->getMessage() === "INVALID_CREDENTIALS") {
                Response::error(
                    $e->getMessage(),
                    401
                );
                return;
            }

             if ($e->getMessage() === "EMAIL_NOT_VERIFIED") {
                Response::error(
                    $e->getMessage(),
                    403
                );
                return;
            }

            if ($e->getMessage() === "LOGIN_TEMPORARILY_BLOCKED") {
                Response::error(
                    $e->getMessage(),
                    429
                );
                return;
            }

            Response::error(
                    $e->getMessage(),
                    400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "login",                
                "email" => $email                
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Inicia el proceso de recuperación de contraseña.
     *
     * Recibe el email del usuario y el idioma para enviar el correo.
     *
     * Por seguridad, siempre devuelve una respuesta exitosa aunque el email
     * no exista en el sistema.
     * ================================================================================== */
    public function forgotPassword(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $email = "";

        try {

            $data = $this->request->json();

            $email = Validator::stringTrim($data["email"] ?? "");
            $language = Validator::stringTrim($data["language"] ?? "");
            
            AuthValidator::validateEmailRequest($email);
            AuthValidator::validateLanguage($language);

            $this->authService->forgotPassword(
                $email,
                $language
            );

            Response::success(
                "PASSWORD_RESET_EMAIL_SENT"
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

            if ($e->getMessage() === "EMAIL_DELIVERY_FAILED") {
                AppLogger::error($this->mailExceptionMessage($e), [
                    "controller" => "AuthController",
                    "method" => "forgotPassword",
                    "email" => $email
                ]);

                Response::error(
                    $e->getMessage(),
                    422
                );

                return;
            }

            Response::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "forgotPassword",
                "email" => $email
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Valida un token de recuperación de contraseña sin restablecerla.
     *
     * Se usa desde el frontend al cargar la vista de restablecimiento de
     * contraseña, para avisarle al usuario si el enlace ya no es válido
     * antes de que complete el formulario.
     *
     * Respuestas posibles:
     *  - 200: token válido.
     *  - 400: token inválido o expirado.
     *  - 422: datos de entrada inválidos.
     *  - 500: error interno del servidor.
     * ================================================================================== */
     public function validateResetToken(): void
     {
        header("Content-Type: application/json; charset=utf-8");

        try {

            $token = Validator::stringTrim($this->request->query("token", ""));

            $this->authService->validateResetToken($token);

            Response::success(
                "VALID_RESET_TOKEN"
            );

        } catch (InvalidArgumentException $e) {

            Response::error(
                $e->getMessage(),
                422
            );

        } catch (RuntimeException $e) {

            Response::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "validateResetToken"
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Restablece la contraseña de un usuario mediante un token de recuperación.
     *
     * Obtiene y valida el token de recuperación junto con la nueva contraseña
     * enviada por el cliente y delega el proceso al servicio de autenticación.
     *
     * Respuestas posibles:
     *  - 200: contraseña restablecida correctamente.
     *  - 400: token inválido o expirado.
     *  - 422: datos de entrada inválidos.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function resetPassword(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        try {

            $data = $this->request->json();

            $token = Validator::stringTrim($data["token"] ?? "");
            $password = Validator::string($data["password"] ?? "");

            // Si el password nuevo tiene un formato válido
            AuthValidator::validateResetPassword($password);

            // Se reemplaza el password viejo por el nuevo
            $this->authService->resetPassword(
                $token,
                $password
            );

            Response::success(
                "PASSWORD_RESET_SUCCESS"
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

            Response::error(
                $e->getMessage(),
                400
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "resetPassword"
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Cierra la sesión del usuario autenticado.
     *
     * Requiere:
     *  - Usuario autenticado.
     *  - Token CSRF válido.
     *
     * Elimina los datos de sesión activos y finaliza
     * el estado de autenticación del usuario.
     *
     * Respuestas posibles:
     *  - 200: sesión cerrada correctamente.
     * ================================================================================== */
    public function logout(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        try {

            // Se elimina la sesión del usuario
            $this->authService->logout();

            Response::success(
                "LOGOUT_SUCCESS"
            );

        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "logout"                
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    /* ==================================================================================
     * Obtiene la información de la sesión autenticada.
     *
     * Comprueba que la sesión contenga tanto el identificador del usuario
     * como el token CSRF. Si la sesión es válida, obtiene los datos del
     * usuario autenticado y devuelve al cliente la información necesaria
     * para reconstruir el estado de autenticación en el frontend.
     *
     * Respuestas posibles:
     *  - 200: usuario autenticado correctamente.
     *  - 401: no existe una sesión válida.
     *  - 404: usuario no encontrado.
     *  - 500: error interno del servidor.
     * ================================================================================== */
    public function getAuthenticatedUser(): void
    {
        header("Content-Type: application/json; charset=utf-8");

        $userId = null;

        try {

            // Se obtiene el id del usuario de la sesión
            $userId = Session::getUserId();
            // Se obtiene el token
            $token = Session::getCsrfToken();

            // Una sesión autenticada debe contener tanto el identificador del
            // usuario como el token CSRF. Si falta cualquiera de los dos,
            // la sesión se considera inválida y el cliente deberá volver
            // a autenticarse.
            if (!$userId || !$token) {
                Response::error(
                    "AUTH_REQUIRED",
                    401
                );
                return;
            }

            // Se obtienen los datos del usuario
            $user = $this->authService->getAuthenticatedUser($userId);

            if (!$user) {                
                Response::error(
                    "USER_NOT_FOUND",
                    404
                );
                return;
            }

            Response::success(
                "AUTHENTICATED_USER",
                [
                    "user"       => $user,
                    "csrf_token" => $token
                ]
            );
            
        } catch (Throwable $e) {

            AppLogger::error($e->getMessage(), [
                "controller" => "AuthController",
                "method" => "getAuthenticatedUser",
                "user_Id" => $userId                
            ]);

            Response::error(
                "INTERNAL_SERVER_ERROR",
                500
            );
        }
    }

    private function mailExceptionMessage(RuntimeException $e): string
    {
        return $e->getPrevious()?->getMessage() ?? $e->getMessage();
    }
}
