<?php

/* ########################################################################
 * Servicio encargado de la lógica de autenticación, verificación de
 * cuentas, recuperación/restablecimiento de contraseñas y administración
 * de sesiones de los usuarios.
 *
 * Aplica medidas de seguridad como límite de intentos fallidos de login,
 * regeneración del ID de sesión y generación de token CSRF.
 *
 * Utiliza los modelos User y LoginAttempt para acceder a los datos, y
 * delega el envío de correos en MailService.
 * ######################################################################## */

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Core\Auth\Session;
use App\Models\LoginAttempt;
use App\Services\MailService;

use PDO;
use RuntimeException;

class AuthService
{
    private PDO $pdo;
    private MailService $mailService;

    /* ==================================================================================
     * Inicializa el servicio de autenticación.
     *
     * Recibe la conexión a la base de datos utilizada para acceder
     * a la información de los usuarios.
     * ================================================================================== */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->mailService = new MailService();
    }

    /* ==================================================================================
     * Registra un nuevo usuario.
     *
     * Verifica que el username y el email no estén siendo utilizados por otro
     * usuario, almacena la contraseña de forma segura mediante un hash y genera
     * la información necesaria para la verificación de la cuenta por correo
     * electrónico.
     *
     * El usuario se crea inicialmente con la cuenta sin verificar junto con
     * un token de verificación y su fecha de expiración.
     * 
     * Si ya existe un usuario con el mismo username o email, lanza una
     * RuntimeException indicando el motivo:
     *  - USERNAME_ALREADY_EXISTS
     *  - EMAIL_ALREADY_EXISTS
     * ================================================================================== */
    public function register(
        string $username,
        string $email,
        string $password,
        string $language
    ): void {

        $existing = User::existsByUsernameOrEmail(
                                    $this->pdo, 
                                    $username, 
                                    $email
                    );

        if ($existing) {

            if ($existing["username"] === $username) {
                throw new RuntimeException("USERNAME_ALREADY_EXISTS");
            }

            if ($existing["email"] === $email) {
                throw new RuntimeException("EMAIL_ALREADY_EXISTS");
            }
        }

        // Transforma la contraseña plana en un hash seguro usando password_hash().
        // Esto evita guardar la contraseña real del usuario en la base de datos
        $passwordHash = password_hash(
            $password, 
            PASSWORD_DEFAULT
        );

        // Genera un token único para verificar la cuenta por email.
        $verificationToken = bin2hex(
            random_bytes(32)
        );

        // Fecha de expiración del token (24 horas).
        // Se almacena en formato DATETIME de MySQL: YYYY-MM-DD HH:MM:SS
        // Ejemplo: 2025-07-01 15:30:00
        $verificationExpiresAt = date(
            "Y-m-d H:i:s",
            strtotime("+24 hours")
        );

        $this->pdo->beginTransaction();

        try {

            // Inserta el nuevo usuario en la base de datos.
            User::create(
                $this->pdo,
                $username,
                $email,
                $passwordHash,
                $verificationToken,
                $verificationExpiresAt
            );

            // Genera y envía el correo de verificación utilizando
            // el idioma solicitado por el cliente.
            $this->mailService->generateVerificationEmail(
                $email,
                $verificationToken,
                $language
            );         
            
            $this->pdo->commit();
                                                
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ==================================================================================
     * Verifica la cuenta de un usuario mediante el token de verificación.
     *
     * Busca el usuario asociado al token recibido y comprueba que:
     *  - el token exista;
     *  - la cuenta aún no haya sido verificada;
     *  - el token no haya expirado.
     *
     * Si todas las validaciones son correctas, se activa la cuenta del usuario.
     *
     * Lanza:
     *  - RuntimeException cuando el token es inválido.     
     *  - RuntimeException cuando el token expiró.
     * ================================================================================== */
    public function verifyEmail(
        string $token
    ): void {

        // Busca el usuario asociado al token de verificación recibido.
        $user = User::findByVerificationToken(
            $this->pdo,
            $token
        );

        // El token no corresponde a ningún usuario registrado.
        if (!$user) {
            throw new RuntimeException(
                "INVALID_VERIFICATION_TOKEN"
            );
        }

        // El período de validez del token ya expiró.
        if (strtotime($user["verification_expires_at"]) < time()) {
            throw new RuntimeException(
                "VERIFICATION_TOKEN_EXPIRED"
            );
        }

        // Activa la cuenta e invalida el token de verificación.
        User::activateEmail(
            $this->pdo,
            (int) $user["id"]
        );
    }

   /* ==================================================================================
    * Reenvía el correo de verificación de una cuenta.
    *
    * Si el usuario existe y aún no verificó su cuenta, genera un nuevo
    * token de verificación, actualiza su fecha de expiración y envía
    * nuevamente el correo electrónico.
    *
    * Si el usuario no existe o la cuenta ya fue verificada, no realiza
    * ninguna acción para evitar revelar información sobre las cuentas
    * registradas.
    * ================================================================================== */
    public function resendVerificationEmail(
        string $email,
        string $language
    ): void {

        $user = User::findByEmail(
            $this->pdo,
            $email
        );

        // No revelar si el email existe.
        if (!$user) {
            return;
        }

        // Si la cuenta ya fue verificada no se hace nada.
        if ($user["is_verified"]) {
            return;
        }

        // Genera un nuevo token de verificación.
        $verificationToken = bin2hex(
            random_bytes(32)
        );

        // Fecha de expiración del nuevo token (24 horas).
        $verificationExpiresAt = date(
            "Y-m-d H:i:s",
            strtotime("+24 hours")
        );

        $this->pdo->beginTransaction();

        try {

            // Actualiza el token en la base de datos.
            User::updateVerificationToken(
                $this->pdo,
                (int) $user["id"],
                $verificationToken,
                $verificationExpiresAt
            );

            // Envía el nuevo correo de verificación.
            $this->mailService->generateVerificationEmail(
                $email,
                $verificationToken,
                $language
            );

            $this->pdo->commit();

        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ==================================================================================
     * Autentica a un usuario mediante email y contraseña.
     *
     * Antes de validar las credenciales, comprueba que la combinación de IP
     * y email no se encuentre temporalmente bloqueada por exceso de intentos.
     *
     * Si las credenciales son incorrectas, registra el intento fallido
     * correspondiente. Si son válidas, comprueba que la cuenta haya sido
     * verificada mediante correo electrónico.
     *
     * Si la autenticación es correcta:
     *  - Elimina los intentos fallidos registrados.
     *  - Regenera el identificador de sesión.
     *  - Guarda el ID del usuario en la sesión.
     *  - Genera un nuevo token CSRF.
     *  - Devuelve los datos básicos del usuario autenticado junto con el token.
     *
     * Lanza:
     *  - RuntimeException cuando las credenciales son inválidas.
     *  - RuntimeException cuando la cuenta aún no fue verificada.
     *  - RuntimeException cuando el inicio de sesión se encuentra
     *    temporalmente bloqueado.
     * ================================================================================== */
    public function login(
        string $email,
        string $password
    ): array {

        // Obtiene la dirección IP del cliente para aplicar control de intentos de login.
        // Si no está disponible, se asigna una cadena vacía como fallback.
        $ip = $_SERVER["REMOTE_ADDR"] ?? "";

        // Verifica si la combinación IP + email tiene intentos fallidos previos
        // y si el usuario no está bloqueado por exceder el límite permitido.
        $attempt = $this->checkLoginAttempts(
            $ip,
            $email
        );

        // Busca el usuario en la base de datos a partir del email ingresado.
        $user = User::findByEmail(
            $this->pdo,
            $email
        );

        // Si el usuario no existe o la contraseña no coincide:
        // - Se registra o incrementa un intento fallido de login.
        // - Luego se lanza una excepción de credenciales inválidas.
        if (!$user || !password_verify($password, $user["password_hash"])) {

            // Si ya existe un registro de intentos, se incrementa el contador.
            if ($attempt) {

                LoginAttempt::increment(
                    $this->pdo,
                    $attempt["id"]
                );

            // Si no existe aún un registro, se crea uno nuevo.
            } else {

                LoginAttempt::create(
                    $this->pdo,
                    $ip,
                    $email
                );
            }

            throw new RuntimeException(
                "INVALID_CREDENTIALS"
            );
        }

        // Impide el inicio de sesión mientras la cuenta no haya sido
        // verificada mediante el enlace enviado al correo electrónico.
        if (!$user["is_verified"]) {
            throw new RuntimeException(
                "EMAIL_NOT_VERIFIED"
            );
        }

        // Si existían intentos fallidos registrados para esta IP y email,
        // se eliminan porque la autenticación fue correcta.
        if ($attempt) {

            LoginAttempt::reset(
                $this->pdo,
                $attempt["id"]
            );
        }

        // Regenera el identificador de sesión para impedir ataques
        // de Session Fixation después de una autenticación correcta.
        Session::regenerate();

        // Guarda el identificador del usuario autenticado en la sesión.
        Session::set(
            "user_id",
            $user["id"]
        );

        // Genera un nuevo token CSRF para proteger las operaciones
        // que modifiquen información durante la sesión autenticada.
        $csrfToken = Session::generateCsrfToken();

        return [
            "user" => [
                "id" => $user["id"],
                "username" => $user["username"],
                "email" => $user["email"],
                "created_at" => $user["created_at"]
            ],
            "csrf_token" => $csrfToken
        ];
    }

    /* ==================================================================================    
     * Comprueba si una combinación de dirección IP y correo electrónico
     * ha superado el número máximo de intentos fallidos de inicio de sesión.
     *
     * Antes de realizar la comprobación se eliminan los registros cuyo período
     * de bloqueo ya expiró para evitar conservar información innecesaria.
     *
     * Devuelve:
     *  - El registro de intentos fallidos asociado a la combinación de IP
     *    y email si existe.
     *  - false si todavía no existe ningún registro.
     *
     * Lanza:
     *  - RuntimeException cuando el usuario se encuentra temporalmente
     *    bloqueado por haber alcanzado el límite de intentos permitidos.
     * ================================================================================== */
    private function checkLoginAttempts(
        string $ip,
        string $email
    ): array|false {

        LoginAttempt::deleteExpired($this->pdo);

        $attempt = LoginAttempt::find(
            $this->pdo,
            $ip,
            $email
        );

        if ($attempt && $attempt["attempts"] >= 3) {
            throw new RuntimeException(
                "LOGIN_TEMPORARILY_BLOCKED"
            );
        }

        return $attempt;
    }

    /* ==================================================================================
     * Inicia el proceso de recuperación de contraseña de un usuario.
     *
     * Si el email corresponde a una cuenta registrada, genera un token
     * temporal de recuperación, almacena su fecha de expiración y envía
     * un correo electrónico con las instrucciones para restablecer la
     * contraseña.
     *
     * Si el email no existe, no realiza ninguna acción para evitar
     * revelar información sobre las cuentas registradas.
    * ================================================================================== */
    public function forgotPassword(
        string $email,
        string $language
    ): void {

        $user = User::findByEmail(
            $this->pdo,
            $email
        );

        // No revelar si el email existe.
        if (!$user) {
            return;
        }

        // Genera un nuevo token de recuperación.
        $resetToken = bin2hex(
            random_bytes(32)
        );

        // Expira en 1 hora.
        $resetExpiresAt = date(
            "Y-m-d H:i:s",
            strtotime("+1 hour")
        );

        $this->pdo->beginTransaction();

        try {

            // Guarda el token en la base de datos.
            User::updatePasswordResetToken(
                $this->pdo,
                (int) $user["id"],
                $resetToken,
                $resetExpiresAt
            );

            // Envía el correo de recuperación.
            $this->mailService->generatePasswordResetEmail(
                $email,
                $resetToken,
                $language
            );

            $this->pdo->commit();

        } catch (\Throwable $e) {

            $this->pdo->rollBack();
            throw $e;
        }
    }

    /* ==================================================================================
     * Valida un token de recuperación de contraseña sin modificar la contraseña.
     *
     * Se usa desde el frontend para comprobar, antes de mostrar el formulario
     * de restablecimiento, si el enlace recibido por email todavía es válido.
     * No tiene efectos secundarios: solo confirma existencia y vigencia del token.
     *
     * Lanza:
     *  - RuntimeException cuando el token es inválido.
     *  - RuntimeException cuando el token expiró.
     * ================================================================================== */
    public function validateResetToken(
        string $token
    ): void {
        $this->getValidPasswordResetUser($token);
    }

    /* ==================================================================================
     * Restablece la contraseña de un usuario mediante un token de recuperación.
     *
     * Busca el usuario asociado al token recibido y comprueba que:
     *  - el token exista;
     *  - el token no haya expirado.
     *
     * Si las validaciones son correctas, genera un nuevo hash para la
     * contraseña y actualiza el registro del usuario, invalidando el
     * token de recuperación para impedir que vuelva a utilizarse.
     *
     * Lanza:
     *  - RuntimeException cuando el token es inválido.
     *  - RuntimeException cuando el token expiró.
     * ================================================================================== */
    public function resetPassword(
        string $token,
        string $password
    ): void {

        // Reutiliza la misma validación que validateResetToken(), para no
        // duplicar la lógica de existencia/expiración del token en dos lugares.
        $user = $this->getValidPasswordResetUser($token);

        // Genera el hash seguro de la nueva contraseña.
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        // Actualiza la contraseña e invalida el token de recuperación.
        User::updatePassword(
            $this->pdo,
            (int) $user["id"],
            $passwordHash
        );
    }

    /* ==================================================================================
     * Busca al usuario asociado a un token de recuperación de contraseña y
     * comprueba que el token exista y no haya expirado.
     *
     * Método privado compartido por resetPassword() y validateResetToken(),
     * ya que ambos necesitan exactamente la misma validación: uno para
     * confirmarla antes de mostrar el formulario, el otro para aplicar
     * el cambio de contraseña.
     *
     * Lanza:
     *  - RuntimeException cuando el token es inválido.
     *  - RuntimeException cuando el token expiró.
     * ================================================================================== */
    private function getValidPasswordResetUser(
        string $token
    ): array {

        $user = User::findByPasswordResetToken(
            $this->pdo,
            $token
        );

        if (!$user) {
            throw new RuntimeException(
                "INVALID_PASSWORD_RESET_TOKEN"
            );
        }

        if (strtotime($user["password_reset_expires_at"]) < time()) {
            throw new RuntimeException(
                "PASSWORD_RESET_TOKEN_EXPIRED"
            );
        }

        return $user;
    }

    /* ==================================================================================
     * Cierra la sesión del usuario autenticado.
     *
     * Elimina todos los datos almacenados en la sesión actual,
     * invalida la cookie de sesión en el navegador y destruye
     * la sesión del servidor para evitar reutilizaciones.
     * ================================================================================== */
    public function logout(): void
    {
        Session::destroy();
    }

    /* ==================================================================================
     * Obtiene los datos de un usuario mediante su identificador.
     *
     * Devuelve:
     * - Los datos del usuario si existe.
     * - null si el usuario no existe.
     * ================================================================================== */
    public function getAuthenticatedUser(
        int $userId
    ): ?array {

        return User::findById(
            $this->pdo,
            $userId
        );
    }
}